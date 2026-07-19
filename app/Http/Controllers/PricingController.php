<?php

namespace App\Http\Controllers;

use App\Support\PointPackCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PricingController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        return view('pricing.checkout', [
            'pointPacks' => PointPackCatalog::all(),
            'userPoints' => $user->available_points ?? 0,
        ]);
    }

    public function subscribe(Request $request)
    {
        return redirect()->route('pricing.index')
            ->with('info', 'Les abonnements ne sont pas encore commercialisés.');
    }

    public function cancel()
    {
        return redirect()->route('pricing.index')
            ->with('info', 'Aucun abonnement récurrent n’est actif sur ce parcours.');
    }

    public function resume()
    {
        return redirect()->route('pricing.index')
            ->with('info', 'Les abonnements ne sont pas encore commercialisés.');
    }

    /**
     * Compatibilité avec l'ancien formulaire : tous les nouveaux achats passent
     * par StripeCheckoutController, qui vérifie un catalogue unique côté serveur.
     */
    public function purchasePoints(Request $request)
    {
        return redirect()->route('pricing.index')
            ->with('info', 'Utilisez le paiement Stripe sécurisé proposé sur cette page.');
    }
}
