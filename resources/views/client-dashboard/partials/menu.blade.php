{{-- Toutes les pages de l'espace, regroupées par nature : menu latéral sur ordinateur, sommaire sur téléphone. --}}
<nav class="pk-board__menu" aria-label="Pages de mon espace">
    @foreach($menu as $group)
        <section class="pk-board-group">
            <h2>{{ $group['title'] }}</h2>
            <ul>
                @foreach($group['links'] as $link)
                    <li @class(['pk-board-group__current' => $link['current'] ?? false])>
                        <a class="pk-board-link" href="{{ $link['url'] }}" @if($link['current'] ?? false) aria-current="page" @endif>
                            <i class="{{ $link['icon'] }}" aria-hidden="true"></i>
                            <span>{{ $link['label'] }}</span>
                            @if(($link['count'] ?? 0) > 0)
                                <b class="pk-board-count">{{ $link['count'] > 99 ? '99+' : $link['count'] }}</b>
                            @endif
                            <i class="fas fa-chevron-right pk-board-link__arrow" aria-hidden="true"></i>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endforeach
</nav>
