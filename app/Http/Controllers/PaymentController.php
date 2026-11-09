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
        abort(410, \App\Support\RetiredPoints::MESSAGE);
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
        return app(PointController::class)->transactions();
    }
}
