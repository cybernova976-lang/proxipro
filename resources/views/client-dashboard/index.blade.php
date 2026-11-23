{{--
    Vue d'ensemble du tableau de bord du particulier : du contenu seulement.
    Le menu des pages de l'espace est le panneau latéral du gabarit
    (partials/client-space-menu), commun à toutes les pages du tableau de bord.
--}}
@extends('layouts.app')

@section('title', 'Tableau de bord - Prokejem')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/client-dashboard.css') }}?v={{ filemtime(public_path('css/client-dashboard.css')) }}">
@endpush

@section('content')
@php
    $pkBoardFirstName = trim((string) \Illuminate\Support\Str::of((string) $user->name)->before(' '));
@endphp
<div class="pk-board" data-client-dashboard>
    <header class="pk-board__head">
        <h1>Tableau de bord</h1>
        <p>Bonjour{{ $pkBoardFirstName !== '' ? ' '.$pkBoardFirstName : '' }}, voici où en sont vos demandes, vos messages et vos alertes.</p>
    </header>

    <ul class="pk-board__tiles" aria-label="Chiffres de mon compte">
        @foreach($tiles as $tile)
            <li>
                <a class="pk-board-tile" href="{{ $tile['url'] }}">
                    <strong>{{ $tile['value'] }}</strong>
                    <span class="pk-board-tile__label">{{ $tile['label'] }}</span>
                    <span class="pk-board-tile__icon" aria-hidden="true"><i class="{{ $tile['icon'] }}"></i></span>
                </a>
            </li>
        @endforeach
    </ul>

    @include('client-dashboard.partials.requests')
    @include('client-dashboard.partials.alerts')
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/activity-dashboard.js') }}?v={{ filemtime(public_path('js/activity-dashboard.js')) }}" defer></script>
@endpush
