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

        if ($request->filled('status')) {
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
        ];

        return view('admin.service-orders.index', compact('orders', 'stats'));
    }

    public function release(Request $request, ServiceOrder $serviceOrder)
    {
        $request->validate([
            'resolution_note' => 'nullable|string|max:1000',
        ]);

        $this->workflowService->adminReleaseDispute($serviceOrder, Auth::user(), $request->resolution_note);

        return redirect()->route('admin.service-orders.index')
            ->with('success', 'Litige résolu: fonds libérés vers le vendeur.');
    }

    public function refund(Request $request, ServiceOrder $serviceOrder)
    {
        $request->validate([
            'resolution_note' => 'required|string|min:10|max:1000',
        ]);

        $this->workflowService->adminRefundDispute($serviceOrder, Auth::user(), $request->resolution_note);

        return redirect()->route('admin.service-orders.index')
            ->with('success', 'Litige résolu: remboursement Stripe déclenché.');
    }
}