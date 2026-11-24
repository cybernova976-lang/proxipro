{{-- Colonne vide : une icône, une phrase, et un lien quand une action a du sens. --}}
<div class="pipeline-empty">
    <span class="pipeline-empty__icon" aria-hidden="true"><i class="fas {{ $icon }}"></i></span>
    <p>{{ $text }}</p>
    @isset($linkUrl)
        <a href="{{ $linkUrl }}">{{ $linkLabel }} <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
    @endisset
</div>
