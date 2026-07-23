@props([
    'size' => 40,
    'decorative' => true,
])

@php
    $diameter = max(24, min((int) $size, 72));
    $fontSize = (int) round($diameter * 0.5);
@endphp

<span
    {{ $attributes->class(['lunamars-brand-mark'])->merge([
        'style' => "width:{$diameter}px;height:{$diameter}px;display:inline-flex;align-items:center;justify-content:center;flex:0 0 {$diameter}px;border-radius:28%;background:#4f46e5;color:#ffffff;font-family:'Segoe UI',Arial,sans-serif;font-size:{$fontSize}px;font-weight:800;line-height:1;letter-spacing:-0.04em;",
    ]) }}
    @if($decorative)
        aria-hidden="true"
    @else
        role="img"
        aria-label="Logo typographique {{ config('app.name', 'Lunamars') }}"
    @endif
>L</span>
