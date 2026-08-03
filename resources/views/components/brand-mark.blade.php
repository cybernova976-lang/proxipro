@props([
    'size' => 40,
    'decorative' => true,
    'variant' => 'symbol',
])

@php
    $height = max(24, min((int) $size, 96));
    $isWordmark = $variant === 'wordmark';
    // Rapport exact du logo horizontal officiel (1153 x 214), afin d'éviter
    // tout étirement ou tassement dans les en-têtes et les e-mails.
    $width = $isWordmark ? (int) round($height * 1153 / 214) : $height;
    $source = $isWordmark
        ? asset('images/brand/prokejem-logo.png')
        : asset('images/brand/prokejem-symbol.png');
@endphp

<img
    src="{{ $source }}"
    width="{{ $width }}"
    height="{{ $height }}"
    {{ $attributes->class(['prokejem-brand-mark'])->merge([
        'style' => "width:{$width}px;height:{$height}px;display:inline-block;object-fit:contain;flex:0 0 auto;",
    ]) }}
    @if($decorative)
        alt=""
        aria-hidden="true"
    @else
        alt="Logo {{ config('app.name', 'Prokejem') }}"
    @endif
>
