<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @deprecated Ce contrôleur est obsolète.
 * Le vrai système d'abonnement est géré par ProDashboardController (monthly/annual).
 * Les points sont gérés par PricingController et StripeCheckoutController.
 * Toutes les méthodes redirigent vers les pages actives.
 */
class SubscriptionController extends Controller
{
    public function index()
    {
        return redirect()->route('pricing.index');
    }

    public function subscribe(Request $request)
    {
        return redirect()->route('pricing.index')
            ->with('info', 'Utilisez la page Espace Pro pour vous abonner.');
    }

    public function cancel()
    {
        return redirect()->route('pro.subscription')
            ->with('info', 'Gérez votre abonnement depuis l\'Espace Pro.');
    }

    public function resume()
    {
        return redirect()->route('pro.subscription')
            ->with('info', 'Gérez votre abonnement depuis l\'Espace Pro.');
    }

    public function success()
    {
        return redirect()->route('pricing.index')
            ->with('success', 'Paiement effectué avec succès !');
    }

    public function invoices()
    {
        $user = Auth::user();
        return view('subscriptions.invoices', [
            'invoices' => $user->invoices(),
        ]);
    }

    public function downloadInvoice($invoiceId)
    {
        return Auth::user()->downloadInvoice($invoiceId, [
            'vendor' => 'ProxiPro',
            'product' => 'Abonnement Pro',
        ]);
    }
}
