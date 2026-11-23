{{-- Quatre compteurs, tous comptés sur le compte : aucun chiffre estimé. --}}
<ul class="pk-board__tiles" aria-label="Votre espace en chiffres">
    @foreach($tiles as $tile)
        <li>
            <a class="pk-board-tile" href="{{ $tile['url'] }}">
                <span class="pk-board-tile__icon" aria-hidden="true"><i class="{{ $tile['icon'] }}"></i></span>
                <strong>{{ $tile['value'] }}</strong>
                <span class="pk-board-tile__label">{{ $tile['label'] }}</span>
            </a>
        </li>
    @endforeach
</ul>
