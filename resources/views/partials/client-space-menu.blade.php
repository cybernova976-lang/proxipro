{{--
    Cadre du tableau de bord du particulier.
    · Le panneau latéral liste toutes les pages de l'espace, groupées par nature
      (App\Support\ClientSpaceNavigation::menuGroups). Colonne à gauche à partir
      de 1180 px ; tiroir rétractable en dessous.
    · La barre de repère (bouton « Menu » et page en cours) n'existe qu'en dessous
      de 1180 px.
    Styles : public/css/client-space.css. Script : public/js/client-space-menu.js.
--}}
@php
    $pkSpaceGroupTitle = null;
    $pkSpaceCurrent = null;
    foreach ($pkSpaceMenu as $pkSpaceGroup) {
        foreach ($pkSpaceGroup['items'] as $pkSpaceItem) {
            if ($pkSpaceItem['active']) {
                $pkSpaceGroupTitle = $pkSpaceGroup['title'];
                $pkSpaceCurrent = $pkSpaceItem['label'];
            }
        }
    }
    // Compteur déjà mesuré par l'en-tête : messages non lus.
    $pkSpaceBadges = [
        'messages' => [(int) ($unreadCount ?? 0), 'messages non lus'],
    ];
@endphp
<div class="pk-space-bar" data-space-bar>
    <button type="button" class="pk-space-bar__toggle" data-space-menu-open aria-controls="pkSpaceMenu" aria-expanded="false">
        <i class="fas fa-bars" aria-hidden="true"></i><span>Menu</span>
    </button>
    <p class="pk-space-bar__where">
        @if($pkSpaceGroupTitle)
            <span>{{ $pkSpaceGroupTitle }}</span><i class="fas fa-chevron-right" aria-hidden="true"></i>
        @endif
        <strong>{{ $pkSpaceCurrent ?? 'Tableau de bord' }}</strong>
    </p>
</div>
<div class="pk-space-backdrop" data-space-backdrop data-space-menu-close hidden></div>
<aside class="pk-space-menu" id="pkSpaceMenu" data-space-menu data-client-space>
    <div class="pk-space-menu__head">
        <p>Tableau de bord</p>
        <button type="button" class="pk-space-menu__close" data-space-menu-close aria-label="Fermer le menu">
            <i class="fas fa-xmark" aria-hidden="true"></i>
        </button>
    </div>
    <nav aria-label="Mon espace particulier">
        @foreach($pkSpaceMenu as $pkSpaceGroup)
            <p class="pk-space-menu__title" id="pkSpaceGroup{{ $loop->index }}">{{ $pkSpaceGroup['title'] }}</p>
            <ul aria-labelledby="pkSpaceGroup{{ $loop->index }}">
                @foreach($pkSpaceGroup['items'] as $pkSpaceItem)
                    @php $pkSpaceBadge = $pkSpaceBadges[$pkSpaceItem['key']] ?? [0, '']; @endphp
                    <li>
                        <a class="pk-space-link" href="{{ $pkSpaceItem['url'] }}" data-space-section="{{ $pkSpaceItem['key'] }}" @if($pkSpaceItem['active']) aria-current="page" @endif>
                            <i class="{{ $pkSpaceItem['icon'] }}" aria-hidden="true"></i>
                            <span>{{ $pkSpaceItem['label'] }}</span>
                            @if($pkSpaceBadge[0] > 0)
                                <b class="pk-space-badge" aria-label="{{ $pkSpaceBadge[0] }} {{ $pkSpaceBadge[1] }}">{{ $pkSpaceBadge[0] > 99 ? '99+' : $pkSpaceBadge[0] }}</b>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        @endforeach
    </nav>
</aside>
<script src="{{ asset('js/client-space-menu.js') }}?v={{ filemtime(public_path('js/client-space-menu.js')) }}" defer></script>
