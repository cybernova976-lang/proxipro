<?php

namespace App\Http\Controllers;

use App\Models\IdentityVerification;
use App\Services\ClientActivityService;
use App\Services\SavedSearchService;
use App\Support\ClientSpaceNavigation;
use App\Support\ProviderOfferLink;
use App\Support\TerritoryResolver;
use Illuminate\Http\Request;

/**
 * Tableau de bord du particulier : tout ce qui n'est pas la découverte des
 * services est rangé ici (demandes, messages, favoris, emploi, alertes et
 * notifications, compte, aide), regroupé par nature. Chaque nombre affiché
 * est compté sur le compte : rien n'est estimé.
 */
class ClientDashboardController extends Controller
{
    public function __invoke(
        Request $request,
        ClientActivityService $activity,
        SavedSearchService $alerts,
        TerritoryResolver $territories,
    ) {
        $user = $request->user();

        // Un prestataire dans son parcours prestataire a son propre poste de pilotage.
        if (ClientSpaceNavigation::role($request, $user) !== 'client') {
            return redirect()->route('pro.dashboard');
        }

        $summary = $activity->summary($user);
        $unreadMessages = (int) $user->unreadMessagesCount();
        $unreadNotifications = $user->unreadNotifications()->count();
        $savedSearches = $user->savedSearches()->orderByDesc('is_active')->latest()->take(20)->get();
        $activeAlerts = $savedSearches->where('is_active', true)->values();

        $territory = $territories->forUser($user, (array) ($request->attributes->get('user_geo') ?? []));
        $territoryAlert = $territory !== null && ! $alerts->hasActiveTerritoryOfferAlert($user, $territory)
            ? $alerts->territoryOfferAlertFields($territory)
            : null;
        $employmentUrl = $territory !== null
            ? route('employment.territory', ['territoire' => $territory->slug])
            : route('employment.index');

        $verificationStatus = IdentityVerification::where('user_id', $user->id)->latest()->first()?->status;
        $identity = match (true) {
            $verificationStatus === 'returned' => 'Corriger ma vérification d’identité',
            $verificationStatus === 'rejected' => 'Refaire ma vérification d’identité',
            $verificationStatus === 'pending' => 'Vérification d’identité en cours d’examen',
            ! $user->hasVerifiedProfileBadge() => 'Vérifier mon identité',
            default => null,
        };

        return view('client-dashboard.index', [
            'user' => $user,
            'territory' => $territory,
            'tiles' => [
                ['label' => 'Demandes en cours', 'value' => (int) $summary['total'], 'icon' => 'far fa-clipboard', 'url' => route('home')],
                ['label' => 'Messages non lus', 'value' => $unreadMessages, 'icon' => 'far fa-comments', 'url' => route('messages.index')],
                ['label' => 'Favoris', 'value' => $user->savedAds()->count(), 'icon' => 'far fa-bookmark', 'url' => route('saved-ads.index')],
                ['label' => 'Alertes actives', 'value' => $activeAlerts->count(), 'icon' => 'far fa-bell', 'url' => '#alertes'],
            ],
            'requests' => collect([$summary['primary']])->concat($summary['others'])->filter()->take(3)->values(),
            'requestsTotal' => (int) $summary['total'],
            'todo' => array_filter([
                'identity' => $identity,
                'city' => trim((string) $user->city) === '' && trim((string) $user->postal_code) === '',
            ]),
            'savedSearches' => $savedSearches,
            'activeAlerts' => $activeAlerts,
            'territoryAlert' => $territoryAlert,
            'unreadNotifications' => $unreadNotifications,
            'menu' => $this->menu($summary['total'], $unreadMessages, $unreadNotifications, $activeAlerts->count(), $employmentUrl, $user),
        ]);
    }

    /**
     * Les pages de l'espace, regroupées par nature : la même liste sert de
     * menu latéral sur ordinateur et de sommaire sur téléphone.
     *
     * @return array<int, array{title: string, links: array<int, array{label: string, icon: string, url: string, count?: int, current?: bool}>}>
     */
    private function menu(int $requests, int $messages, int $notifications, int $alerts, string $employmentUrl, $user): array
    {
        return [
            ['title' => 'Mon espace', 'links' => [
                ['label' => ClientSpaceNavigation::DASHBOARD_LABEL, 'icon' => 'fas fa-table-columns', 'url' => route('client.dashboard'), 'current' => true],
                ['label' => ClientSpaceNavigation::REQUESTS_LABEL, 'icon' => 'far fa-clipboard', 'url' => route('home'), 'count' => $requests],
                ['label' => 'Mes commandes', 'icon' => 'fas fa-receipt', 'url' => route('service-orders.index')],
                ['label' => 'Messages', 'icon' => 'far fa-comments', 'url' => route('messages.index'), 'count' => $messages],
                ['label' => 'Favoris', 'icon' => 'far fa-bookmark', 'url' => route('saved-ads.index')],
            ]],
            ['title' => 'Alertes et notifications', 'links' => [
                ['label' => 'Mes alertes', 'icon' => 'far fa-bell', 'url' => route('saved-searches.index'), 'count' => $alerts],
                ['label' => 'Notifications', 'icon' => 'far fa-envelope-open', 'url' => route('notifications.index'), 'count' => $notifications],
                ['label' => 'Réglages des e-mails', 'icon' => 'fas fa-sliders', 'url' => route('settings.index').'#notifications'],
            ]],
            ['title' => 'Emploi', 'links' => [
                ['label' => 'Offres d’emploi', 'icon' => 'fas fa-briefcase', 'url' => $employmentUrl],
                ['label' => 'Proposer mes services', 'icon' => 'fas fa-screwdriver-wrench', 'url' => ProviderOfferLink::url($user)],
            ]],
            ['title' => 'Mon compte', 'links' => [
                ['label' => 'Mon profil', 'icon' => 'far fa-user', 'url' => route('profile.show')],
                ['label' => 'Vérification d’identité', 'icon' => 'fas fa-shield-halved', 'url' => route('verification.index')],
                ['label' => 'Abonnement et factures', 'icon' => 'far fa-credit-card', 'url' => route('billing.index')],
                ['label' => 'Paramètres', 'icon' => 'fas fa-gear', 'url' => route('settings.index')],
            ]],
            ['title' => 'Aide', 'links' => [
                ['label' => 'Conseils pratiques', 'icon' => 'far fa-compass', 'url' => route('guides.index')],
                ['label' => 'Comment les offres sont classées', 'icon' => 'fas fa-arrow-down-wide-short', 'url' => route('legal.platform-rules').'#classement'],
                ['label' => 'Contacter l’assistance', 'icon' => 'fas fa-headset', 'url' => route('contact.index')],
            ]],
        ];
    }
}
