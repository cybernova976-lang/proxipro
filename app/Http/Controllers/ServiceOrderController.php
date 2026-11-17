<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use App\Models\ServiceOrder;
use App\Notifications\ServiceOrderRequestedNotification;
use App\Services\ServiceOrderWorkflowService;
use App\Services\StripeConnectService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Stripe;

class ServiceOrderController extends Controller
{
    public function __construct(
        protected ServiceOrderWorkflowService $workflowService,
        protected StripeConnectService $stripeConnectService,
    ) {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    public function index()
    {
        $user = Auth::user();

        $ordersAsBuyer = ServiceOrder::with(['ad', 'seller', 'serviceReminder'])
            ->where('buyer_id', $user->id)
            ->latest()
            ->get();

        $ordersAsSeller = ServiceOrder::with(['ad', 'buyer'])
            ->where('seller_id', $user->id)
            ->latest()
            ->get();

        $needsStripeConnectOnboarding = $ordersAsSeller->count() > 0
            && (! $user->stripe_connect_account_id || ! $user->stripe_connect_payouts_enabled);

        return view('service-orders.index', compact('ordersAsBuyer', 'ordersAsSeller', 'needsStripeConnectOnboarding'));
    }

    public function store(Request $request, Ad $ad)
    {
        $user = Auth::user();

        abort_if($ad->user_id === $user->id, 403);
        abort_unless(
            $ad->service_type === 'offre'
                && $ad->status === 'active'
                && (! $ad->expires_at || $ad->expires_at->isFuture()),
            422
        );

        $request->validate([
            'amount' => 'required|numeric|min:1',
            'message' => 'nullable|string|max:1000',
            'scheduled_for' => 'nullable|date|after_or_equal:today',
        ]);

        $split = \App\Support\MarketplaceCommission::split((float) $request->amount);

        $serviceOrder = ServiceOrder::create([
            'order_number' => 'CMD-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
            'ad_id' => $ad->id,
            'buyer_id' => $user->id,
            'seller_id' => $ad->user_id,
            'amount' => $split['amount'],
            'commission_amount' => $split['commission_amount'],
            'seller_amount' => $split['seller_amount'],
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
        \App\Support\SafeNotifier::send($serviceOrder->seller, new ServiceOrderRequestedNotification($serviceOrder));

        return redirect()->route('service-orders.index')
            ->with('success', 'Commande sécurisée envoyée. Le vendeur peut maintenant l\'examiner.');
    }

    public function accept(ServiceOrder $serviceOrder)
    {
        $this->workflowService->accept($serviceOrder, Auth::user());

        // Sans compte de paiement actif, le client ne pourra pas régler : on le dit tout de suite.
        if (! $this->stripeConnectService->sellerCanBePaid(Auth::user())) {
            return redirect()->route('service-orders.index')
                ->with('success', 'Commande acceptée. Activez la réception de vos paiements pour que le client puisse régler.');
        }

        return redirect()->route('service-orders.index')
            ->with('success', 'Commande acceptée. Le client peut maintenant payer en ligne.');
    }

    public function refuse(Request $request, ServiceOrder $serviceOrder)
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $this->workflowService->refuse($serviceOrder, Auth::user(), $request->reason);

        return redirect()->route('service-orders.index')
            ->with('success', 'Commande refusée.');
    }

    public function checkout(ServiceOrder $serviceOrder)
    {
        $buyer = Auth::user();

        abort_if($serviceOrder->buyer_id !== $buyer->id, 403);
        abort_unless($serviceOrder->canBuyerPay(), 422);

        if (! config('services.stripe.secret')) {
            return redirect()->route('service-orders.index')
                ->with('error', 'Le paiement sécurisé est momentanément indisponible.');
        }

        // On n'encaisse jamais une somme qui ne pourrait pas être versée ensuite :
        // le prestataire doit d'abord pouvoir recevoir ses paiements.
        if (! $this->stripeConnectService->sellerCanBePaid($serviceOrder->seller)) {
            $this->workflowService->remindSellerToEnablePayouts($serviceOrder);

            return redirect()->route('service-orders.index')
                ->with('error', 'Le prestataire n’a pas encore activé la réception de ses paiements. Nous venons de le lui rappeler : vous pourrez payer dès que ce sera fait.');
        }

        try {
            $stripeCustomerId = $this->ensureStripeCustomer($buyer);
            $amountCents = (int) round(((float) $serviceOrder->amount) * 100);
            $currency = strtolower((string) config('marketplace.currency', 'eur'));

            $session = StripeSession::create([
                'customer' => $stripeCustomerId,
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => $currency,
                        'product_data' => [
                            'name' => 'Commande sécurisée '.$serviceOrder->order_number,
                            'description' => $serviceOrder->ad->title,
                        ],
                        'unit_amount' => $amountCents,
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => route('stripe.success').'?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('service-orders.index').'?payment=canceled&order='.$serviceOrder->id,
                'metadata' => [
                    'type' => 'service_order',
                    'service_order_id' => $serviceOrder->id,
                    'buyer_id' => $serviceOrder->buyer_id,
                    'seller_id' => $serviceOrder->seller_id,
                    'order_number' => $serviceOrder->order_number,
                    'expected_amount_cents' => $amountCents,
                    'expected_currency' => $currency,
                ],
            ]);
        } catch (\Throwable $exception) {
            Log::error('Unable to create service order Stripe checkout.', [
                'service_order_id' => $serviceOrder->id,
                'buyer_id' => $buyer->id,
                'error' => $exception->getMessage(),
            ]);

            return redirect()->route('service-orders.index')
                ->with('error', 'Impossible d’ouvrir le paiement sécurisé. Veuillez réessayer.');
        }

        $serviceOrder->forceFill([
            'payment_status' => ServiceOrder::PAYMENT_CHECKOUT_OPEN,
            'stripe_checkout_session_id' => $session->id,
        ])->save();

        return redirect()->away($session->url);
    }

    /** Annulation d'une commande non payée, par le client ou le prestataire. */
    public function cancel(ServiceOrder $serviceOrder)
    {
        $user = Auth::user();

        try {
            $this->workflowService->cancel($serviceOrder, $user);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $exception) {
            throw $exception;
        } catch (\RuntimeException $exception) {
            return redirect()->route('service-orders.index')->with('error', $exception->getMessage());
        } catch (\Throwable $exception) {
            // Stripe injoignable : on n'annule pas sans avoir fermé la page de paiement.
            report($exception);

            return redirect()->route('service-orders.index')
                ->with('error', 'La commande n’a pas pu être annulée pour le moment. Réessayez dans quelques minutes.');
        }

        $message = (int) $user->id === (int) $serviceOrder->buyer_id
            ? 'Commande annulée : rien ne vous sera prélevé. Si vous n’avez plus besoin de prestataire, pensez à supprimer votre demande.'
            : 'Commande annulée. Le client en est informé.';

        return redirect()->route('service-orders.index')->with('success', $message);
    }

    public function release(ServiceOrder $serviceOrder)
    {
        try {
            $this->workflowService->release($serviceOrder, Auth::user());
        } catch (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $exception) {
            // Un refus (403, 422) n'est pas une erreur de versement : il garde son code.
            throw $exception;
        } catch (\RuntimeException $exception) {
            return redirect()->route('service-orders.index')->with('error', $exception->getMessage());
        } catch (\Throwable $exception) {
            // Refus de Stripe : la commande reste payée et les fonds bloqués, rien n'est perdu.
            report($exception);

            return redirect()->route('service-orders.index')
                ->with('error', 'Le versement n’a pas pu être effectué pour le moment. Vos fonds restent bloqués : réessayez plus tard ou contactez-nous.');
        }

        return redirect()->route('service-orders.index')
            ->with('success', 'Fonds libérés au prestataire.');
    }

    public function dispute(Request $request, ServiceOrder $serviceOrder)
    {
        $request->validate([
            'reason' => 'required|string|min:10|max:1000',
        ]);

        $this->workflowService->dispute($serviceOrder, Auth::user(), $request->reason);

        return redirect()->route('service-orders.index')
            ->with('success', 'Litige ouvert. Les fonds restent bloqués.');
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
            ->with('success', 'L’état de votre compte de paiement a été mis à jour.');
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
