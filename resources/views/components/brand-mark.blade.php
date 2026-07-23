@props([
    'size' => 40,
    'decorative' => true,
    'variant' => 'symbol',
])

@php
    $height = max(24, min((int) $size, 96));
    $isWordmark = $variant === 'wordmark';
    $width = $isWordmark ? (int) round($height * 1090 / 250) : $height;
    $source = $isWordmark
        ? asset('images/brand/lunamars-logo.png')
        : asset('images/brand/lunamars-symbol.png');
@endphp

<img
    src="{{ $source }}"
    width="{{ $width }}"
    height="{{ $height }}"
    {{ $attributes->class(['lunamars-brand-mark'])->merge([
        'style' => "width:{$width}px;height:{$height}px;display:inline-block;object-fit:contain;flex:0 0 auto;",
    ]) }}
    @if($decorative)
        alt=""
        aria-hidden="true"
    @else
        alt="Logo {{ config('app.name', 'Lunamars') }}"
    @endif
>
