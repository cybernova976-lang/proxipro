{{--
    Barre d'onglets mobile — « Publier » au centre, surelevee.

    Incluse par layouts/app.blade.php, donc presente sur toutes les pages du
    gabarit commun. Elle doit rester autonome : aucune variable ne lui est
    transmise par la page qui l'affiche.

    · le role est deduit de l'utilisateur connecte, jamais d'un onglet clique,
      comme le fait FeedController ;
    · l'onglet actif est deduit de la route courante ;
    · rien ne s'affiche pour un visiteur non connecte : quatre des cinq liens
      mènent a des pages qui exigent une session.

    Les styles vivent dans public/css/tabbar.css.
--}}
@auth
    @php
        $pkTabUser = auth()->user();
        $pkTabRole = ($pkTabUser->isProfessionnel() || $pkTabUser->isServiceProvider())
            ? 'provider'
            : 'client';

        $pkPublishUrl = $pkTabRole === 'provider'
            ? route('ads.create', ['type' => 'service'])
            : route('demand.create');

        $pkTabs = [
            [
                'url'    => route('feed'),
                'icon'   => 'fas fa-home',
                'label'  => 'Accueil',
                'active' => request()->routeIs('feed'),
                'class'  => '',
            ],
            [
                'url'    => route('ads.index'),
                'icon'   => 'fas fa-clipboard-list',
                'label'  => 'Annonces',
                'active' => request()->routeIs('ads.index') || request()->routeIs('ads.show'),
                'class'  => '',
            ],
            [
                'url'    => $pkPublishUrl,
                'icon'   => 'fas fa-plus',
                'label'  => 'Publier',
                'active' => request()->routeIs('ads.create') || request()->routeIs('demand.create'),
                'class'  => 'pk-tabbar__pub',
            ],
            [
                'url'    => route('messages.index'),
                'icon'   => 'far fa-comments',
                'label'  => 'Messages',
                'active' => request()->routeIs('messages.*'),
                'class'  => '',
            ],
            [
                'url'    => route('profile.show'),
                'icon'   => 'far fa-user',
                'label'  => 'Profil',
                'active' => request()->routeIs('profile.*'),
                'class'  => '',
            ],
        ];
    @endphp

    <nav class="pk-tabbar" aria-label="Navigation principale">
        @foreach($pkTabs as $pkTab)
            <a href="{{ $pkTab['url'] }}"
               class="{{ trim($pkTab['class'].($pkTab['active'] ? ' is-active' : '')) }}"
               @if($pkTab['active']) aria-current="page" @endif>
                <i class="{{ $pkTab['icon'] }}"></i><span>{{ $pkTab['label'] }}</span>
            </a>
        @endforeach
    </nav>

    {{-- Reserve la hauteur de la barre sous le contenu. Rendue ici plutot que
         par une regle sur body : elle n'existe ainsi que lorsque la barre
         existe, et ne creuse pas un vide sur les pages sans barre. --}}
    <div class="pk-tabbar-spacer" aria-hidden="true"></div>
@endauth
