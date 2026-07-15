<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\ServiceOrder;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    public function store(Request $request, $id)
    {
        $reviewedUser = User::findOrFail($id);

        if (Auth::id() === $reviewedUser->id) {
            abort(403);
        }

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
            'service_order_id' => 'required|integer|exists:service_orders,id',
        ]);

        $serviceOrder = ServiceOrder::query()
            ->whereKey($validated['service_order_id'])
            ->where('status', ServiceOrder::STATUS_COMPLETED)
            ->where('payment_status', ServiceOrder::PAYMENT_RELEASED)
            ->where(function ($query) use ($reviewedUser) {
                $query->where(function ($pair) use ($reviewedUser) {
                    $pair->where('buyer_id', Auth::id())
                        ->where('seller_id', $reviewedUser->id);
                })->orWhere(function ($pair) use ($reviewedUser) {
                    $pair->where('seller_id', Auth::id())
                        ->where('buyer_id', $reviewedUser->id);
                });
            })
            ->firstOrFail();

        Review::updateOrCreate(
            [
                'reviewer_id' => Auth::id(),
                'service_order_id' => $serviceOrder->id,
            ],
            [
                'reviewed_user_id' => $reviewedUser->id,
                'ad_id' => $serviceOrder->ad_id,
                'rating' => $validated['rating'],
                'comment' => $validated['comment'] ?? null,
            ]
        );

        return redirect()->route('profile.public', $reviewedUser->id)
            ->with('success', 'Merci pour votre avis !');
    }
}
