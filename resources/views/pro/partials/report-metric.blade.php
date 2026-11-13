@php
    $pkFormat = function ($value) use ($metric): string {
        if ($metric['unit'] === '€') {
            return number_format((float) $value, 2, ',', ' ');
        }

        return rtrim(rtrim(number_format((float) $value, 1, ',', ' '), '0'), ',');
    };
    $pkDelta = $metric['delta'];
@endphp
<li class="pk-metric" data-metric="{{ $metric['key'] }}">
    <span class="pk-metric__label">{{ $metric['label'] }}</span>
    @if($metric['value'] === null)
        <span class="pk-metric__value pk-metric__value--none">Non mesuré</span>
    @else
        <span class="pk-metric__value">{{ $pkFormat($metric['value']) }}@if($metric['unit']) <small>{{ $metric['unit'] }}</small>@endif</span>
        @if($pkDelta === null)
            <span class="pk-metric__delta pk-metric__delta--same">{{ $metric['key'] === 'rating' ? 'Aucun avis reçu en '.$previousLabel : 'Pas de comparaison : non mesuré en '.$previousLabel }}</span>
        @elseif($pkDelta == 0)
            <span class="pk-metric__delta pk-metric__delta--same">Identique à {{ $previousLabel }}</span>
        @else
            <span class="pk-metric__delta {{ $pkDelta > 0 ? 'pk-metric__delta--up' : 'pk-metric__delta--down' }}">{{ $pkDelta > 0 ? '+' : '−' }}{{ $pkFormat(abs($pkDelta)) }}{{ $metric['unit'] === '€' ? ' €' : '' }} par rapport à {{ $previousLabel }}</span>
        @endif
    @endif
    <span class="pk-metric__hint">{{ $metric['hint'] }}</span>
</li>
