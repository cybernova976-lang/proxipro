{{-- Mise en page de l'espace Pro. Styles : public/css/pro-space.css · script : public/js/pro-space.js --}}
@php
    $proNavGroups = \App\Support\ProSpaceNavigation::groups(request(), Auth::user());
    // Le titre d'une section est déjà échappé : on le décode, l'affichage l'échappe une seule fois.
    $proPageTitle = html_entity_decode(trim($__env->yieldContent('topbar_title')), ENT_QUOTES | ENT_HTML5, 'UTF-8')
        ?: \App\Support\ProSpaceNavigation::activeLabel($proNavGroups);
    $proUnreadMessages = (int) Auth::user()->unreadMessagesCount();
    $proUnreadNotifications = (int) Auth::user()->unreadNotifications()->count();
@endphp
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    @include('partials.pwa-head')
    <title>@yield('title', 'Espace Pro - Prokejem')</title>
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Inter:400,500,600,700,800&display=swap" rel="stylesheet">
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/form-validation.css') }}?v=20260902">
    <script defer src="{{ asset('js/form-validation.js') }}?v=20260902"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/pro-space.css') }}?v=20261113">
    <style>
    @yield('styles')
    </style>
    @stack('head')
</head>
<body @class(['pro-no-sidebar' => $proNavGroups === []])>
    @include('pro.layout.topbar')

    @if($proNavGroups !== [])
        <div class="pro-sidebar-overlay" id="sidebarOverlay" aria-hidden="true"></div>
        @include('pro.layout.sidebar')
    @endif

    <main class="pro-main" id="proMain">
        @include('partials.billing-alert')
        @include('pro.layout.flash')

        @yield('content')
    </main>

    @include('partials.provider-modal')

    <script defer src="{{ asset('js/pro-space.js') }}?v=20261113"></script>
    @yield('scripts')
    @include('partials.usage-analytics')
    @include('partials.push-notifications')
    @include('partials.pwa-install')
    @include('partials.user-presence')
</body>
</html>
