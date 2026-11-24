{{-- Menu de l'espace Pro : les groupes et les compteurs viennent de App\Support\ProSpaceNavigation. --}}
<aside class="pro-sidebar" id="proSidebar" aria-label="Menu de l'espace professionnel">
    {{-- En-tête du tiroir, visible seulement sous 1024 px. --}}
    <div class="pro-sidebar-head">
        <span class="pro-sidebar-head__title">Espace professionnel</span>
        <button type="button" class="pro-sidebar-close" data-pro-sidebar-close aria-label="Fermer le menu">
            <i class="fas fa-xmark" aria-hidden="true"></i>
        </button>
    </div>
    @foreach($proNavGroups as $group)
        <div class="pro-nav-group">
            @if($group['label'])
                <p class="pro-nav-label">{{ $group['label'] }}</p>
            @endif
            @foreach($group['items'] as $item)
                <a href="{{ $item['url'] }}" class="pro-nav-item{{ $item['active'] ? ' active' : '' }}" data-pro-nav="{{ $item['key'] }}"
                   @if($item['active']) aria-current="page" @endif>
                    <i class="{{ $item['icon'] }} pro-nav-icon" aria-hidden="true"></i>
                    <span class="pro-nav-text">
                        {{ $item['label'] }}
                        @if($item['hint'])
                            <span class="pro-nav-hint">{{ $item['hint'] }}</span>
                        @endif
                    </span>
                    @if($item['badge'])
                        <span class="pro-nav-badge pro-nav-badge--{{ $item['tone'] }}">{{ $item['badge'] }}</span>
                    @endif
                </a>
            @endforeach
        </div>
    @endforeach
</aside>
