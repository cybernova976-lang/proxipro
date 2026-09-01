@if(collect($pkGuides ?? [])->isNotEmpty())
<section class="pk-guides" aria-labelledby="pkGuidesTitle">
    <div class="pk-sechead">
        <div>
            <h2 id="pkGuidesTitle">Conseils pour avancer sereinement</h2>
            <p class="pk-sechead__sub">Des repères pratiques adaptés à votre parcours.</p>
        </div>
        <a href="{{ route('guides.index') }}" class="pk-sechead__more">Tous les conseils <i class="fas fa-arrow-right"></i></a>
    </div>

    <div class="pk-guide-list">
        @foreach($pkGuides as $pkGuide)
            <a class="pk-guide-card" href="{{ route('guides.show', $pkGuide['slug']) }}">
                <span class="pk-guide-card__icon"><i class="{{ $pkGuide['icon'] }}"></i></span>
                <span class="pk-guide-card__copy">
                    <small>{{ $pkGuide['kicker'] }} · {{ $pkGuide['reading_time'] }} min</small>
                    <strong>{{ $pkGuide['title'] }}</strong>
                </span>
                <i class="fas fa-arrow-right pk-guide-card__arrow" aria-hidden="true"></i>
            </a>
        @endforeach
    </div>
</section>
@endif
