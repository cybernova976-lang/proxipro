<?php

namespace App\Http\Controllers;

use App\Services\ProviderActivityReport;
use App\Support\PlatformFeatures;
use App\Support\ProviderSubscriptionPlans;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * Rapport d'activité mensuel de l'Espace Pro, réservé aux abonnés Premium.
 * Sans abonnement actif, la page explique ce que contient le rapport sans
 * calculer ni afficher aucun chiffre.
 */
class ProActivityReportController extends Controller
{
    public function show(Request $request, ProviderActivityReport $reports, ?string $mois = null)
    {
        $user = $request->user();
        if (! $user->isProfessionnel() && ! $user->isServiceProvider()) {
            return redirect()->route('pro.dashboard')
                ->with('warning', 'Le rapport d’activité est réservé aux prestataires enregistrés.');
        }

        if (! $user->hasActiveProSubscription()) {
            return view('pro.report', [
                'user' => $user,
                'locked' => true,
                'subscriptionsOpen' => PlatformFeatures::proSubscriptionsEnabled(),
                'monthlyPlan' => ProviderSubscriptionPlans::get('monthly'),
            ]);
        }

        $months = $reports->months($user);
        $selected = $mois !== null
            ? collect($months)->first(fn (CarbonImmutable $month) => $month->format('Y-m') === $mois)
            : (collect($months)->first(fn (CarbonImmutable $month) => $month->equalTo($reports->lastCompletedMonth())) ?? $months[0]);
        abort_if($selected === null, 404);

        return view('pro.report', [
            'user' => $user,
            'locked' => false,
            'report' => $reports->forMonth($user, $selected),
            'months' => collect($months)->map(fn (CarbonImmutable $month) => [
                'key' => $month->format('Y-m'),
                'label' => $reports->label($month),
                'current' => $month->equalTo($selected),
            ]),
        ]);
    }
}
