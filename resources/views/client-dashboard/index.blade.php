@extends('layouts.app')

@section('title', 'Tableau de bord - Prokejem')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/client-dashboard.css') }}?v={{ filemtime(public_path('css/client-dashboard.css')) }}">
@endpush

@section('content')
<div class="pk-board" data-client-dashboard>
    <h1>Tableau de bord</h1>
    <nav class="pk-board__nav" aria-label="Rubriques du tableau de bord">
        @foreach(['activite' => ['Activité', 'fas fa-list-check'], 'alertes' => ['Alertes', 'far fa-bell'], 'compte' => ['Compte et services', 'far fa-user']] as $key => [$label, $icon])
            <a href="{{ route('client.dashboard', $key === 'activite' ? [] : ['rubrique' => $key]) }}"
               @if($key === $section) aria-current="page" @endif>
                <i class="{{ $icon }}" aria-hidden="true"></i><span>{{ $label }}</span>
            </a>
        @endforeach
    </nav>

    <div class="pk-board__sections">
        @switch($section)
            @case('alertes')
                @include('client-dashboard.partials.alerts')
                @break
            @case('compte')
                @foreach($groups as $title => $links)
                    <section class="pk-board-card">
                        <h2>{{ $title }}</h2>
                        <ul class="pk-board-rows">
                            @foreach($links as [$label, $icon, $url])
                                <li @class(['pk-board-in-tabbar' => $url === route('profile.show')])><a class="pk-board-row" href="{{ $url }}">
                                    <i class="pk-board-row__icon {{ $icon }}" aria-hidden="true"></i>
                                    <strong>{{ $label }}</strong>
                                    <i class="fas fa-chevron-right pk-board-link__arrow" aria-hidden="true"></i>
                                </a></li>
                            @endforeach
                        </ul>
                    </section>
                @endforeach
                @break
            @default
                @include('client-dashboard.partials.requests')
                <section class="pk-board-card">
                    <h2>Accès rapides</h2>
                    <ul class="pk-board-rows">
                        <li @class(['pk-board-in-tabbar' => $unreadMessages == 0])><a class="pk-board-row" href="{{ route('messages.index') }}">
                            <i class="pk-board-row__icon far fa-comments" aria-hidden="true"></i>
                            <strong>Messages</strong>
                            @if($unreadMessages > 0)
                                <span class="pk-board-count">{{ $unreadMessages }} non {{ $unreadMessages > 1 ? 'lus' : 'lu' }}</span>
                            @endif
                            <i class="fas fa-chevron-right pk-board-link__arrow" aria-hidden="true"></i>
                        </a></li>
                        <li><a class="pk-board-row" href="{{ route('saved-ads.index') }}">
                            <i class="pk-board-row__icon far fa-bookmark" aria-hidden="true"></i>
                            <strong>Favoris</strong><span class="pk-board-meta">{{ $favoritesCount }}</span>
                            <i class="fas fa-chevron-right pk-board-link__arrow" aria-hidden="true"></i>
                        </a></li>
                        <li><a class="pk-board-row" href="{{ route('service-orders.index') }}">
                            <i class="pk-board-row__icon fas fa-receipt" aria-hidden="true"></i><strong>Mes commandes</strong>
                            <i class="fas fa-chevron-right pk-board-link__arrow" aria-hidden="true"></i>
                        </a></li>
                    </ul>
                </section>
        @endswitch
    </div>
</div>
@endsection

@if($section === 'alertes')
    @push('scripts')
    <script src="{{ asset('js/activity-dashboard.js') }}?v={{ filemtime(public_path('js/activity-dashboard.js')) }}" defer></script>
    @endpush
@endif
