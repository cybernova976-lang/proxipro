<?php

namespace App\Http\Controllers;

use App\Services\ClientActivityService;
use App\Services\SavedSearchService;
use App\Support\ClientSpaceNavigation;
use App\Support\TerritoryResolver;
use Illuminate\Http\Request;

/**
 * Vue d'ensemble du tableau de bord du particulier.
 *
 * La page n'affiche que du contenu : quatre compteurs mesurés sur le compte,
 * les demandes en cours, puis les alertes et les notifications. Les liens vers
 * les autres pages de l'espace sont dans le panneau latéral du gabarit
 * (ClientSpaceNavigation::menuGroups), pas ici.
 */
class ClientDashboardController extends Controller
{
    public function __invoke(Request $request, ClientActivityService $activity, SavedSearchService $alerts, TerritoryResolver $territories)
    {
        $user = $request->user();

        if (ClientSpaceNavigation::role($request, $user) !== 'client') {
            return redirect()->route('pro.dashboard');
        }

        $summary = $activity->summary($user);
        $territory = $territories->forUser($user, (array) ($request->attributes->get('user_geo') ?? []));
        $unreadMessages = $user->unreadMessagesCount();
        $favoritesCount = $user->savedAds()->count();
        $activeAlertCount = $user->savedSearches()->where('is_active', true)->count();

        return view('client-dashboard.index', [
            'user' => $user,
            // Aucun chiffre inventé : chaque compteur est lu sur le compte.
            'tiles' => [
                ['label' => 'Demandes en cours', 'value' => $summary['total'], 'icon' => 'far fa-clipboard', 'url' => route('home')],
                ['label' => 'Messages non lus', 'value' => $unreadMessages, 'icon' => 'far fa-comments', 'url' => route('messages.index')],
                ['label' => 'Favoris', 'value' => $favoritesCount, 'icon' => 'far fa-bookmark', 'url' => route('saved-ads.index')],
                ['label' => 'Alertes actives', 'value' => $activeAlertCount, 'icon' => 'far fa-bell', 'url' => route('saved-searches.index')],
            ],
            'requests' => collect([$summary['primary']])->concat($summary['others'])->filter()->take(3)->values(),
            'requestsTotal' => $summary['total'],
            'unreadMessages' => $unreadMessages,
            'favoritesCount' => $favoritesCount,
            'territory' => $territory,
            'territoryAlert' => $territory && ! $alerts->hasActiveTerritoryOfferAlert($user, $territory)
                ? $alerts->territoryOfferAlertFields($territory) : null,
            'savedSearches' => $user->savedSearches()->orderByDesc('is_active')->latest()->orderByDesc('id')->limit(3)->get(),
            'activeAlertCount' => $activeAlertCount,
        ]);
    }
}
