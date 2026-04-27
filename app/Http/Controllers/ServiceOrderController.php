<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use App\Models\ServiceOrder;
use App\Notifications\ServiceOrderRequestedNotification;
use App\Services\StripeConnectService;
use App\Services\ServiceOrderWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Stripe;

class ServiceOrderController extends Controller
{
    public function __construct(
        protected ServiceOrderWorkflowService $workflowService,
        protected StripeConnectService $stripeConnectService,
    )
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    public function index()
    {
        $user = Auth::user();

        $ordersAsBuyer = ServiceOrder::with(['ad', 'seller'])
            ->where('buyer_id', $user->id)
            ->latest()
            ->get();

        $ordersAsSeller = ServiceOrder::with(['ad', 'buyer'])
            ->where('seller_id', $user->id)
            ->latest()
            ->get();

        $needsStripeConnectOnboarding = $ordersAsSeller->count() > 0
            && (!$user->stripe_connect_account_id || !$user->stripe_connect_payouts_enabled);

        return view('service-orders.index', compact('ordersAsBuyer', 'ordersAsSeller', 'needsStripeConnectOnboarding'));
    }

    public function store(Request $request, Ad $ad)
    {
        $user = Auth::user();

        abort_if($ad->user_id === $user->id, 403);

        $request->validate([
            'amount' => 'required|numeric|min:1',
            'message' => 'nullable|string|max:1000',
            'scheduled_for' => 'nullable|date|after_or_equal:today',
        ]);

        $amount = round((float) $request->amount, 2);
        $commissionAmount = round($amount * 0.10, 2);
        $sellerAmount = round($amount - $commissionAmount, 2);

        $serviceOrder = ServiceOrder::create([
            'order_number' => 'CMD-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6)),
            'ad_id' => $ad->id,
            'buyer_id' => $user->id,
            'seller_id' => $ad->user_id,
            'amount' => $amount,
            'commission_amount' => $commissionAmount,
            'seller_amount' => $sellerAmount,
            'status' => 'pending_acceptance',
            'payment_status' => 'awaiting_payment',
            'message' => $request->message,
            'scheduled_for' => $request->scheduled_for,
            'metadata' => [
                'source' => 'ad_show',
                'ad_title' => $ad->title,
            ],
        ]);

        $serviceOrder->load(['buyer', 'seller', 'ad']);
        $serviceOrder->seller?->notify(new ServiceOrderRequestedNotification($serviceOrder));

        return redirect()->route('service-orders.index')
            ->with('success', 'Commande securisee envoyee. Le vendeur peut maintenant l\'examiner.');
    }

    public function accept(ServiceOrder $serviceOrder)
    {
        $this->workflowService->accept($serviceOrder, Auth::user());

        return redirect()->route('service-orders.index')
            ->with('success', 'Commande acceptee. L\'acheteur peut maintenant payer via Stripe.');
    }

    public function refuse(Request $request, ServiceOrder $serviceOrder)
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $this->workflowService->refuse($serviceOrder, Auth::user(), $request->reason);

        return redirect()->route('service-orders.index')
            ->with('success', 'Commande refusee.');
    }

    public function checkout(ServiceOrder $serviceOrder)
    {
        $buyer = Auth::user();

        abort_if($serviceOrder->buyer_id !== $buyer->id, 403);
        abort_unless($serviceOrder->canBuyerPay(), 422);

        $stripeCustomerId = $this->ensureStripeCustomer($buyer);

        $session = StripeSession::create([
            'customer' => $stripeCustomerId,
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => 'Commande securisee ' . $serviceOrder->order_number,
                        'description' => $serviceOrder->ad->title,
                    ],
                    'unit_amount' => (int) round(((float) $serviceOrder->amount) * 100),
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => route('stripe.success') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('service-orders.index') . '?payment=canceled&order=' . $serviceOrder->id,
            'metadata' => [
                'type' => 'service_order',
                'service_order_id' => $serviceOrder->id,
                'buyer_id' => $serviceOrder->buyer_id,
                'seller_id' => $serviceOrder->seller_id,
                'order_number' => $serviceOrder->order_number,
            ],
        ]);

        $serviceOrder->forceFill([
            'payment_status' => ServiceOrder::PAYMENT_CHECKOUT_OPEN,
            'stripe_checkout_session_id' => $session->id,
        ])->save();

        return redirect()->away($session->url);
    }

    public function release(ServiceOrder $serviceOrder)
    {
        try {
            $this->workflowService->release($serviceOrder, Auth::user());
        } catch (\RuntimeException $exception) {
            return redirect()->route('service-orders.index')->with('error', $exception->getMessage());
        }

        return redirect()->route('service-orders.index')
            ->with('success', 'Fonds liberes au vendeur.');
    }

    public function dispute(Request $request, ServiceOrder $serviceOrder)
    {
        $request->validate([
            'reason' => 'required|string|min:10|max:1000',
        ]);

        $this->workflowService->dispute($serviceOrder, Auth::user(), $request->reason);

        return redirect()->route('service-orders.index')
            ->with('success', 'Litige ouvert. Les fonds restent bloques.');
    }

    public function connectOnboarding()
    {
        $user = Auth::user();

        $url = $this->stripeConnectService->createOnboardingLink(
            $user,
            route('service-orders.connect.return'),
            route('service-orders.connect.onboarding')
        );

        return redirect()->away($url);
    }

    public function connectReturn()
    {
        $this->stripeConnectService->syncAccountStatus(Auth::user());

        return redirect()->route('service-orders.index')
            ->with('success', 'Le statut Stripe Connect a ete mis a jour.');
    }

    protected function ensureStripeCustomer($user): string
    {
        if ($user->stripe_id) {
            return $user->stripe_id;
        }

        $customer = \Stripe\Customer::create([
            'email' => $user->email,
            'name' => $user->name,
            'metadata' => ['user_id' => $user->id],
        ]);

        $user->update(['stripe_id' => $customer->id]);

        return $customer->id;
    }
}