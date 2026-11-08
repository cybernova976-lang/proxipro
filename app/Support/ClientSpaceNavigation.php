<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;

class ClientSpaceNavigation
{
    /** The chosen journey changes navigation, never account permissions. */
    public static function forRequest(Request $request, ?User $user, ?string $feedRole = null): array
    {
        if (! $user) {
            return [];
        }

        $canProvide = $user->isProfessionnel() || $user->isServiceProvider();
        $role = $canProvide
            ? ($feedRole ?? ($request->hasSession() ? $request->session()->get('feed_mode.'.$user->id, 'provider') : 'provider'))
            : 'client';

        if ($role !== 'client') {
            return [];
        }

        $sections = [
            ['key' => 'discover', 'label' => 'Découvrir', 'icon' => 'far fa-compass', 'route' => 'feed',
                'matches' => ['feed', 'feed.professionals', 'feed.mockup.preview']],
            ['key' => 'requests', 'label' => 'Mes demandes', 'icon' => 'far fa-clipboard', 'route' => 'home',
                'matches' => ['home']],
            ['key' => 'messages', 'label' => 'Messages', 'icon' => 'far fa-comments', 'route' => 'messages.index',
                'matches' => ['messages.index', 'messages.show']],
            ['key' => 'saved', 'label' => 'Favoris', 'icon' => 'far fa-bookmark', 'route' => 'saved-ads.index',
                'matches' => ['saved-ads.index']],
            ['key' => 'employment', 'label' => 'Emploi', 'icon' => 'fas fa-briefcase', 'route' => 'employment.index',
                'matches' => ['employment.index', 'employment.territory']],
        ];

        if (! collect($sections)->contains(fn ($section) => $request->routeIs(...$section['matches']))) {
            return [];
        }

        return array_map(fn ($section) => [
            ...$section,
            'url' => self::sectionUrl($section, $request, $user),
            'active' => $request->routeIs(...$section['matches']),
        ], $sections);
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
