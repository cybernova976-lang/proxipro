<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;

/**
 * Navigation unique de l'espace particulier.
 *
 * Une page = un nom, partout : panneau latéral du tableau de bord, barre du bas
 * (téléphone), en-tête, menu du compte. Depuis le 23/11/2026, il n'y a plus
 * d'onglets : toutes les pages de l'espace sont rangées dans le panneau latéral
 * du tableau de bord (colonne à gauche sur ordinateur, tiroir rétractable en
 * dessous de 1180 px) et s'affichent dans le même cadre. Le bouton de
 * publication suit le parcours : une demande pour un particulier, une offre
 * pour un prestataire, avec la même adresse dans l'en-tête et la barre du bas.
 */
class ClientSpaceNavigation
{
    public const HOME_LABEL = 'Accueil';

    public const DASHBOARD_LABEL = 'Tableau de bord';

    public const REQUESTS_LABEL = 'Mes demandes';

    /**
     * Vocabulaire (24/11/2026) : une demande = « je cherche un prestataire » ;
     * une offre = « je présente mon activité » ; une annonce = l'une ou l'autre.
     */
    public const ADS_LABEL = 'Gérer mes annonces';

    public const OFFERS_LABEL = 'Mes offres';

    /** Forme courte pour la barre du bas : « Mes demandes » passe sur deux lignes à 375 px. */
    public const REQUESTS_SHORT_LABEL = 'Demandes';

    /** The chosen journey changes navigation, never account permissions. */
    public static function role(Request $request, ?User $user, ?string $feedRole = null): string
    {
        if (! $user || ! ($user->isProfessionnel() || $user->isServiceProvider())) {
            return 'client';
        }

        return $feedRole ?? ($request->hasSession() ? $request->session()->get('feed_mode.'.$user->id, 'provider') : 'provider');
    }

    /** @return array<int, array{key: string, label: string, icon: string, route: string, matches: array<int, string>}> */
    public static function sections(): array
    {
        return [
            ['key' => 'requests', 'label' => self::REQUESTS_LABEL, 'icon' => 'far fa-clipboard', 'route' => 'home',
                'matches' => ['home', 'demands.tracking', 'client-activity.index', 'service-orders.index',
                    'demand.matching', 'proposals.compare']],
            ['key' => 'messages', 'label' => 'Messages', 'icon' => 'far fa-comments', 'route' => 'messages.index',
                'matches' => ['messages.index', 'messages.show']],
            ['key' => 'saved', 'label' => 'Favoris', 'icon' => 'far fa-bookmark', 'route' => 'saved-ads.index',
                'matches' => ['saved-ads.index', 'saved-searches.index']],
            ['key' => 'employment', 'label' => 'Emploi', 'icon' => 'fas fa-briefcase', 'route' => 'employment.index',
                'matches' => ['employment.index', 'employment.territory']],
        ];
    }

    /**
     * Pages rangées dans le tableau de bord, groupées par nature. Chacune s'affiche
     * dans le même cadre : ce menu à gauche, le contenu à côté.
     *
     * Deux pages n'en font pas partie, à la demande du fondateur (23/11/2026) :
     * les notifications (on y vient par la cloche de l'en-tête) et le profil, qui
     * s'affiche en pleine page et s'ouvre depuis le menu du compte.
     *
     * @return array<int, array{title: string, items: array<int, array{key: string, label: string, icon: string, route: string, matches: array<int, string>, fragment?: string}>}>
     */
    public static function menuGroups(): array
    {
        $sections = collect(self::sections())->keyBy('key');

        return [
            ['title' => 'Mon espace', 'items' => [
                ['key' => 'overview', 'label' => 'Vue d’ensemble', 'icon' => 'fas fa-table-columns',
                    'route' => 'client.dashboard', 'matches' => ['client.dashboard']],
                ['key' => 'requests', 'label' => self::REQUESTS_LABEL, 'icon' => 'far fa-clipboard', 'route' => 'home',
                    'matches' => array_values(array_diff($sections['requests']['matches'], ['service-orders.index']))],
                // Gestion des publications (modifier, mettre hors ligne, supprimer) ;
                // « Mes demandes » reste le suivi des réponses et des réservations.
                ['key' => 'ads', 'label' => self::ADS_LABEL, 'icon' => 'fas fa-bullhorn',
                    'route' => 'ads.myads', 'matches' => ['ads.myads']],
                ['key' => 'orders', 'label' => 'Mes commandes', 'icon' => 'fas fa-receipt',
                    'route' => 'service-orders.index', 'matches' => ['service-orders.index']],
                ['key' => 'messages', 'label' => 'Messages', 'icon' => 'far fa-comments',
                    'route' => 'messages.index', 'matches' => $sections['messages']['matches']],
                ['key' => 'saved', 'label' => 'Favoris', 'icon' => 'far fa-bookmark',
                    'route' => 'saved-ads.index', 'matches' => ['saved-ads.index']],
                ['key' => 'alerts', 'label' => 'Mes alertes', 'icon' => 'far fa-bell',
                    'route' => 'saved-searches.index', 'matches' => ['saved-searches.index']],
            ]],
            ['title' => 'Emploi', 'items' => [
                ['key' => 'employment', 'label' => 'Offres d’emploi', 'icon' => 'fas fa-briefcase',
                    'route' => 'employment.index', 'matches' => $sections['employment']['matches']],
                ['key' => 'offer', 'label' => 'Proposer mes services', 'icon' => 'fas fa-screwdriver-wrench',
                    'route' => 'service-provider.start', 'matches' => ['service-provider.start']],
            ]],
            ['title' => 'Mon compte', 'items' => [
                ['key' => 'identity', 'label' => 'Vérification d’identité', 'icon' => 'fas fa-shield-halved',
                    'route' => 'verification.index', 'matches' => ['verification.index']],
                ['key' => 'billing', 'label' => 'Abonnement et factures', 'icon' => 'far fa-credit-card',
                    'route' => 'billing.index', 'matches' => ['billing.index']],
                ['key' => 'settings', 'label' => 'Paramètres', 'icon' => 'fas fa-gear',
                    'route' => 'settings.index', 'matches' => ['settings.index']],
            ]],
            ['title' => 'Aide', 'items' => [
                ['key' => 'guides', 'label' => 'Conseils pratiques', 'icon' => 'far fa-compass',
                    'route' => 'guides.index', 'matches' => ['guides.index', 'guides.show']],
                ['key' => 'ranking', 'label' => 'Classement des offres', 'icon' => 'fas fa-arrow-down-wide-short',
                    'route' => 'legal.platform-rules', 'matches' => ['legal.platform-rules'], 'fragment' => 'classement'],
                ['key' => 'contact', 'label' => 'Assistance', 'icon' => 'fas fa-headset',
                    'route' => 'contact.index', 'matches' => ['contact.index', 'contact.my-messages']],
            ]],
        ];
    }

    /**
     * Panneau latéral du tableau de bord pour la page demandée. Vide hors du
     * parcours particulier et hors des pages rangées dans le tableau de bord.
     *
     * @return array<int, array{title: string, items: array<int, array{key: string, label: string, icon: string, url: string, active: bool}>}>
     */
    public static function menu(Request $request, ?User $user, ?string $feedRole = null): array
    {
        if (! $user || self::role($request, $user, $feedRole) !== 'client' || ! self::inDashboard($request)) {
            return [];
        }

        return array_map(fn (array $group) => [
            'title' => $group['title'],
            'items' => array_map(fn (array $item) => [
                'key' => $item['key'], 'label' => $item['label'], 'icon' => $item['icon'],
                'url' => self::itemUrl($item, $request, $user),
                'active' => $request->routeIs(...$item['matches']),
            ], $group['items']),
        ], self::menuGroups());
    }

    /** La page demandée fait-elle partie du tableau de bord ? */
    public static function inDashboard(Request $request): bool
    {
        foreach (self::menuGroups() as $group) {
            foreach ($group['items'] as $item) {
                if ($request->routeIs(...$item['matches'])) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Entrée « Tableau de bord » de l'en-tête et du menu du compte : réservée au
     * parcours particulier (un prestataire a son espace Pro).
     *
     * @return array{url: string, label: string, active: bool}|null
     */
    public static function dashboardEntry(Request $request, ?User $user, ?string $feedRole = null): ?array
    {
        if (! $user || self::role($request, $user, $feedRole) !== 'client') {
            return null;
        }

        // Actif sur toutes les pages du tableau de bord : elles partagent le même cadre.
        return ['url' => route('client.dashboard'), 'label' => self::DASHBOARD_LABEL,
            'active' => self::inDashboard($request)];
    }

    /**
     * Accueil et profil du parcours particulier : hors du tableau de bord (pleine
     * page, sans panneau), ils gardent le fond, la barre du bas et le bouton de
     * publication en contour.
     */
    public static function isClientSurface(Request $request, ?User $user, ?string $feedRole = null): bool
    {
        return $user !== null && self::role($request, $user, $feedRole) === 'client'
            && $request->routeIs('feed', 'feed.professionals', 'feed.mockup.preview', 'profile.show', 'profile.edit');
    }

    /**
     * Bouton de publication : même libellé et même adresse partout pour un parcours donné.
     *
     * @return array{url: string, label: string, short: string}
     */
    public static function publishAction(string $role): array
    {
        return $role === 'provider'
            ? ['url' => route('ads.create', ['type' => 'offre']), 'label' => 'Publier une offre', 'short' => 'Publier']
            : ['url' => route('demand.create'), 'label' => 'Publier une demande', 'short' => 'Publier'];
    }

    /**
     * Accès à l'espace Pro depuis l'en-tête. La même adresse pour tous : un
     * prestataire y trouve son poste de pilotage, un autre membre le parcours
     * pour devenir prestataire.
     *
     * @return array{label: string, is_provider: bool}
     */
    public static function proEntry(?User $user): array
    {
        $isProvider = $user !== null && ($user->isProfessionnel() || $user->isServiceProvider());

        return ['label' => $isProvider ? 'Espace Pro' : 'Devenir pro', 'is_provider' => $isProvider];
    }

    /**
     * Barre du bas (téléphone), avec les mêmes noms que le panneau latéral.
     *
     * @return array<int, array{url: string, icon: string, label: string, active: bool, class: string}>
     */
    public static function bottomTabs(Request $request, string $role): array
    {
        $requests = collect(self::sections())->firstWhere('key', 'requests');
        $publish = self::publishAction($role);

        return [
            ['url' => route('feed'), 'icon' => 'fas fa-home', 'label' => self::HOME_LABEL,
                'active' => $request->routeIs('feed'), 'class' => ''],
            $role === 'client'
                ? ['url' => route('home'), 'icon' => 'fas fa-clipboard-list', 'label' => self::REQUESTS_SHORT_LABEL,
                    'active' => $request->routeIs(...$requests['matches']), 'class' => '']
                : ['url' => route('ads.index'), 'icon' => 'fas fa-clipboard-list', 'label' => 'Annonces',
                    'active' => $request->routeIs('ads.index', 'ads.show'), 'class' => ''],
            ['url' => $publish['url'], 'icon' => 'fas fa-plus', 'label' => $publish['short'],
                'active' => $request->routeIs('ads.create', 'demand.create'), 'class' => 'pk-tabbar__pub'],
            ['url' => route('messages.index'), 'icon' => 'far fa-comments', 'label' => 'Messages',
                'active' => $request->routeIs('messages.*'), 'class' => ''],
            ['url' => route('profile.show'), 'icon' => 'far fa-user', 'label' => 'Profil',
                'active' => $request->routeIs('profile.*', 'settings.index'), 'class' => ''],
        ];
    }

    /** « Offres d'emploi » mène directement à la page du territoire quand il est connu. */
    private static function itemUrl(array $item, Request $request, User $user): string
    {
        if ($item['key'] === 'employment') {
            $territory = app(TerritoryResolver::class)->forUser($user, (array) ($request->attributes->get('user_geo') ?? []));

            return $territory !== null
                ? route('employment.territory', ['territoire' => $territory->slug])
                : route('employment.index');
        }

        return route($item['route']).(isset($item['fragment']) ? '#'.$item['fragment'] : '');
    }
}
