@extends('layouts.app')
@section('title', 'Mes demandes et missions en cours - Prokejem')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/feed.css') }}?v={{ @filemtime(public_path('css/feed.css')) ?: 1 }}">
@endpush
@section('content')
<div class="pk-feed">
    <div class="pk-activity-page">
        <a class="pk-state__secondary" href="{{ route('feed', ['mode' => 'client']) }}"><i class="fas fa-arrow-left" aria-hidden="true"></i> Retour à l’accueil</a>
        <header class="pk-activity-page__head">
            <h1>Mes demandes et missions en cours</h1>
            <p>{{ $total }} élément{{ $total > 1 ? 's' : '' }} en cours. Retrouvez chaque besoin et son action suivante.</p>
            <a class="pk-btn" href="{{ route('demand.create') }}">Publier un besoin</a>
        </header>
        @foreach(['Demandes publiées' => $requests, 'Missions et commandes' => $orders] as $label => $items)
            <section class="pk-activity pk-activity-page__section" aria-label="{{ $label }}">
                <h2>{{ $label }} <span class="pk-activity__count">{{ $items->total() }}</span></h2>
                @if($items->count())
                    <ul class="pk-activity__list">
                        @foreach($items as $item)
                            @include('feed.partials.client-activity-row', ['item' => $item])
                        @endforeach
                    </ul>
                    {{ $items->links() }}
                @else
                    <p class="pk-activity-page__empty">Aucun élément en cours dans cette rubrique.</p>
                @endif
            </section>
        @endforeach
        <nav class="pk-activity-page__history" aria-label="Historique"><a href="{{ route('demands.tracking') }}">Historique de mes demandes</a><a href="{{ route('service-orders.index') }}">Toutes mes commandes</a></nav>
    </div>
</div>
@endsection
