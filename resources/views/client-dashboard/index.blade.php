{{-- Tableau de bord du particulier : un sommaire groupé (à gauche sur ordinateur), l'essentiel à côté. --}}
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
        <p>Bonjour{{ $pkBoardFirstName !== '' ? ' '.$pkBoardFirstName : '' }} : vos demandes, vos messages, vos alertes et votre compte, au même endroit.</p>
    </header>

    @include('client-dashboard.partials.tiles')
    {{-- « À faire » passe avant le sommaire : sur téléphone, c'est la première chose à voir après les compteurs. --}}
    @include('client-dashboard.partials.todo')
    @include('client-dashboard.partials.menu')

    <div class="pk-board__sections">
        @include('client-dashboard.partials.requests')
        @include('client-dashboard.partials.alerts')
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/activity-dashboard.js') }}?v={{ filemtime(public_path('js/activity-dashboard.js')) }}" defer></script>
@endpush
