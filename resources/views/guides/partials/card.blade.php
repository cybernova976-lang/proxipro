<a class="guide-card" href="{{ route('guides.show', $guide['slug']) }}">
    <span class="guide-card__icon"><i class="{{ $guide['icon'] }}"></i></span>
    <span class="guide-card__body">
        <span class="guide-card__meta">{{ $guide['kicker'] }} · {{ $guide['reading_time'] }} min</span>
        <strong>{{ $guide['title'] }}</strong>
        <span class="guide-card__summary">{{ $guide['summary'] }}</span>
    </span>
    <span class="guide-card__arrow" aria-hidden="true"><i class="fas fa-arrow-right"></i></span>
</a>
