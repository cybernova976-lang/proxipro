<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceOrder;
use App\Services\ServiceOrderWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminServiceOrderController extends Controller
{
    public function __construct(protected ServiceOrderWorkflowService $workflowService)
    {
        $this->middleware('admin');
    }

    public function index(Request $request)
    {
        $query = ServiceOrder::with(['ad', 'buyer', 'seller']);

        // Payées, tous rappels envoyés, client sans réponse : à trancher par l'administrateur.
        $awaitingDecision = ServiceOrder::with('ad')
            ->where('status', ServiceOrder::STATUS_FUNDED)->where('payment_status', ServiceOrder::PAYMENT_PAID)
            ->get()->filter->isAwaitingAdminDecision();

        if ($request->status === 'unconfirmed') {
            $query->whereIn('id', $awaitingDecision->pluck('id'));
        } elseif ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($innerQuery) use ($search) {
                $innerQuery->where('order_number', 'like', '%' . $search . '%')
                    ->orWhereHas('ad', fn ($adQuery) => $adQuery->where('title', 'like', '%' . $search . '%'))
                    ->orWhereHas('buyer', fn ($buyerQuery) => $buyerQuery->where('name', 'like', '%' . $search . '%'))
                    ->orWhereHas('seller', fn ($sellerQuery) => $sellerQuery->where('name', 'like', '%' . $search . '%'));
            });
        }

        $orders = $query->latest()->paginate(20)->withQueryString();

        $stats = [
            'disputed' => ServiceOrder::where('status', ServiceOrder::STATUS_DISPUTED)->count(),
            'funded' => ServiceOrder::where('status', ServiceOrder::STATUS_FUNDED)->count(),
            'completed' => ServiceOrder::where('status', ServiceOrder::STATUS_COMPLETED)->count(),
            'refunded' => ServiceOrder::where('status', ServiceOrder::STATUS_REFUNDED)->count(),
            'unconfirmed' => $awaitingDecision->count(),
        ];

        // Commissions mesurées sur les commandes : acquises une fois les fonds
        // versés au prestataire, en attente tant que les fonds sont bloqués.
        $commissions = [
            'earned' => (float) ServiceOrder::where('payment_status', ServiceOrder::PAYMENT_RELEASED)->sum('commission_amount'),
            'pending' => (float) ServiceOrder::whereIn('payment_status', [ServiceOrder::PAYMENT_PAID, ServiceOrder::PAYMENT_DISPUTED])->sum('commission_amount'),
            'rate' => \App\Support\MarketplaceCommission::label(),
        ];

        return view('admin.service-orders.index', compact('orders', 'stats', 'commissions'));
    }

    public function release(Request $request, ServiceOrder $serviceOrder)
    {
        $request->validate([
            'resolution_note' => 'nullable|string|max:1000',
        ]);

        if ($failure = $this->attempt(fn () => $this->workflowService->adminReleaseDispute($serviceOrder, Auth::user(), $request->resolution_note))) {
            return $failure;
        }

        return redirect()->route('admin.service-orders.index')
            ->with('success', 'Décision enregistrée : le prestataire est payé. Les deux parties sont prévenues.');
    }

    public function refund(Request $request, ServiceOrder $serviceOrder)
    {
        $request->validate([
            'resolution_note' => 'required|string|min:10|max:1000',
        ]);

        if ($failure = $this->attempt(fn () => $this->workflowService->adminRefundDispute($serviceOrder, Auth::user(), $request->resolution_note))) {
            return $failure;
        }

        return redirect()->route('admin.service-orders.index')
            ->with('success', 'Décision enregistrée : le client est remboursé. Les deux parties sont prévenues.');
    }

    /**
     * Exécute un versement ou un remboursement. Si Stripe le refuse, la
     * commande reste en l'état et l'administrateur voit pourquoi, au lieu
     * d'une page d'erreur.
     */
    private function attempt(callable $operation): ?\Illuminate\Http\RedirectResponse
    {
        try {
            $operation();
        } catch (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $exception) {
            throw $exception;
        } catch (\RuntimeException $exception) {
            return redirect()->route('admin.service-orders.index')->with('error', $exception->getMessage());
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()->route('admin.service-orders.index')
                ->with('error', 'Stripe a refusé l’opération : la commande n’a pas été modifiée. Réessayez ou consultez le tableau de bord Stripe.');
        }

        return null;
    }
}