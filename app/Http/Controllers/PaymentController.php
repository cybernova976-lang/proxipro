<?php

namespace App\Http\Controllers;

use App\Models\PointTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @deprecated Compatibilité des anciennes URL. Les achats sont centralisés
 * dans PricingController et StripeCheckoutController.
 */
class PaymentController extends Controller
{
    public function buyPoints()
    {
        return redirect()->route('pricing.index');
    }

    public function purchasePoints(Request $request)
    {
        return redirect()->route('pricing.index')
            ->with('info', 'Ce formulaire a été remplacé par le paiement Stripe sécurisé.');
    }

    public function pointsSuccess(Request $request)
    {
        return redirect()->route('pricing.index');
    }

    public function pointsCancel()
    {
        return redirect()->route('pricing.index')
            ->with('info', 'L’achat a été annulé.');
    }

    public function pointsHistory()
    {
        $user = Auth::user();
        $transactions = PointTransaction::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('payments.history', [
            'transactions' => $transactions,
            'userPoints' => $user->available_points ?? 0,
        ]);
    }
}
