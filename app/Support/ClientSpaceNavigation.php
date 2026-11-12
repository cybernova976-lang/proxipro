<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;

/**
 * Navigation unique de l'espace particulier.
 *
 * Une page = un nom, partout : onglets « Mon espace » (tablette et ordinateur),
 * barre du bas (téléphone), en-tête, menu du compte. Le bouton de publication
 * suit le parcours : une demande pour un particulier, une offre pour un
 * prestataire, avec la même adresse dans l'en-tête et dans la barre du bas.
 */
class ClientSpaceNavigation
{
    public const HOME_LABEL = 'Accueil';

    public const REQUESTS_LABEL = 'Mes demandes';

    /** Forme courte pour la barre du bas : « Mes demandes » passe sur deux lignes à 375 px. */
    public const REQUESTS_SHORT_LABEL = 'Demandes';

    /** Pages du compte qui gardent les onglets, sans qu'aucun soit actif. */
    private const ACCOUNT_ROUTES = ['profile.show', 'profile.edit', 'settings.index'];

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
            ['key' => 'discover', 'label' => self::HOME_LABEL, 'icon' => 'far fa-compass', 'route' => 'feed',
                'matches' => ['feed', 'feed.professionals', 'feed.mockup.preview']],
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

    public static function forRequest(Request $request, ?User $user, ?string $feedRole = null): array
    {
        if (! $user || self::role($request, $user, $feedRole) !== 'client') {
            return [];
        }

        $sections = self::sections();
        $inSpace = $request->routeIs(...self::ACCOUNT_ROUTES)
            || collect($sections)->contains(fn ($section) => $request->routeIs(...$section['matches']));
        if (! $inSpace) {
            return [];
        }

        return array_map(fn ($section) => [
            ...$section,
            'url' => self::sectionUrl($section, $request, $user),
            'active' => $request->routeIs(...$section['matches']),
        ], $sections);
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
     * Barre du bas (téléphone), avec les mêmes noms que les onglets.
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

    /** L'onglet Emploi mène directement à la page du territoire quand il est connu. */
    private static function sectionUrl(array $section, Request $request, User $user): string
    {
        if ($section['key'] === 'employment') {
            $territory = app(TerritoryResolver::class)->forUser($user, (array) ($request->attributes->get('user_geo') ?? []));

            return $territory !== null
                ? route('employment.territory', ['territoire' => $territory->slug])
                : route('employment.index');
        }

        return route($section['route'], $section['key'] === 'discover' ? ['mode' => 'client'] : []);
    }
}
