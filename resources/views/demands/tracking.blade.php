@extends('layouts.app')

@section('title', 'Suivi de mes demandes - Prokejem')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/demand-tracking.css') }}?v={{ @filemtime(public_path('css/demand-tracking.css')) ?: 1 }}">
@endpush

@section('content')
<main class="tracking-page">
    <a class="tracking-back" href="{{ route('feed') }}"><i class="fas fa-arrow-left"></i> Retour à l’accueil</a>

    <header class="tracking-hero">
        <div>
            <p class="tracking-kicker">Votre parcours client</p>
            <h1>Suivi de mes demandes</h1>
            <p>Retrouvez l’étape actuelle de chaque besoin et l’action utile à effectuer, sans chercher entre plusieurs écrans.</p>
        </div>
        <a class="tracking-new" href="{{ route('demand.create') }}"><i class="fas fa-plus"></i> Publier un besoin</a>
    </header>

    <section class="tracking-summary" aria-label="Résumé de vos demandes">
        <div><strong>{{ $summary['total'] }}</strong><span>demande{{ $summary['total'] > 1 ? 's' : '' }} au total</span></div>
        <div><strong>{{ $summary['awaiting'] }}</strong><span>en recherche</span></div>
        <div><strong>{{ $summary['responses'] }}</strong><span>avec réponse à examiner</span></div>
        <div><strong>{{ $summary['active'] }}</strong><span>mission{{ $summary['active'] > 1 ? 's' : '' }} à suivre</span></div>
    </section>

    @if($demands->count())
        <section class="tracking-list" aria-label="Demandes et étapes">
            @foreach($demands as $item)
                @php($demand = $item['demand'])
                <article id="request-{{ $demand->id }}" class="tracking-card tracking-card--{{ $item['tone'] }}">
                    <div class="tracking-card__main">
                        <div>
                            <div class="tracking-card__eyebrow">
                                <b>{{ $demand->category }}</b>
                                <span>·</span>
                                <span>Publiée {{ $demand->created_at?->diffForHumans() }}</span>
                            </div>
                            <h2>{{ $demand->title }}</h2>
                            <div class="tracking-status">{{ $item['status'] }}</div>
                            <p class="tracking-explanation">{{ $item['explanation'] }}</p>
                            <div class="tracking-meta">
                                <span><i class="fas fa-map-marker-alt"></i> {{ $demand->city ?: $demand->location ?: 'Lieu à préciser' }}</span>
                                <span><i class="fas fa-file-signature"></i> {{ $item['proposal_count'] }} proposition{{ $item['proposal_count'] > 1 ? 's' : '' }}</span>
                                @if($item['order'])
                                    <span><i class="fas fa-shield-alt"></i> {{ $item['order']->order_number }}</span>
                                @endif
                            </div>
                        </div>
                        <a class="tracking-action" href="{{ $item['action_url'] }}">{{ $item['action_label'] }} <i class="fas fa-arrow-right"></i></a>
                    </div>

                    <div class="tracking-steps" aria-label="Progression de la demande">
                        @foreach($item['steps'] as $stepNumber => $stepLabel)
                            <div class="tracking-step {{ $stepNumber < $item['step'] ? 'is-done' : ($stepNumber === $item['step'] ? 'is-current' : '') }}"
                                 @if($stepNumber === $item['step']) aria-current="step" @endif>
                                <span class="tracking-step__dot">{{ $stepNumber < $item['step'] ? '✓' : $stepNumber }}</span>
                                <span>{{ $stepLabel }}</span>
                            </div>
                        @endforeach
                    </div>
                </article>
            @endforeach
        </section>
        <div class="tracking-pagination">{{ $demands->links() }}</div>
    @else
        <section class="tracking-empty">
            <i class="far fa-clipboard"></i>
            <h2>Aucune demande publiée</h2>
            <p>Décrivez votre besoin : vous pourrez ensuite suivre toutes les étapes depuis cette page.</p>
            <a class="tracking-new" href="{{ route('demand.create') }}">Publier ma première demande</a>
        </section>
    @endif
</main>
@endsection
