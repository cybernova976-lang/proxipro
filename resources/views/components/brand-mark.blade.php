@props([
    'size' => 40,
    'decorative' => true,
])

<img
    src="{{ asset('images/brand/lunamars-mark.png') }}"
    width="{{ $size }}"
    height="{{ $size }}"
    {{ $attributes->class(['lunamars-brand-mark']) }}
    @if($decorative)
        alt=""
        aria-hidden="true"
    @else
        alt="Logo {{ config('app.name', 'Lunamars') }}"
    @endif
>
