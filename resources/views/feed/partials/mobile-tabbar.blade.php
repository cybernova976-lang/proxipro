{{--
    Barre d'onglets mobile — « Publier » au centre, surelevee.

    Incluse par layouts/app.blade.php, donc presente sur toutes les pages
    connectees du gabarit commun. Hors du feed, elle reprend le mode memorise
    en session.

    · les liens, les noms et le bouton de publication viennent de
      App\Support\ClientSpaceNavigation : les memes que les onglets
      « Mon espace » et que l'en-tete ;
    · rien ne s'affiche pour un visiteur non connecte : quatre des cinq liens
      menent a des pages qui exigent une session.

    Les styles vivent dans public/css/tabbar.css.
--}}
@auth
    @php
        $pkTabs = \App\Support\ClientSpaceNavigation::bottomTabs(
            request(),
            \App\Support\ClientSpaceNavigation::role(request(), auth()->user(), $pkRole ?? null)
        );
    @endphp

    <nav class="pk-tabbar" aria-label="Navigation principale">
        @foreach($pkTabs as $pkTab)
            <a href="{{ $pkTab['url'] }}"
               data-pk-tab
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
    <script src="{{ asset('js/tabbar.js') }}?v={{ @filemtime(public_path('js/tabbar.js')) ?: 1 }}" defer></script>
@endauth
