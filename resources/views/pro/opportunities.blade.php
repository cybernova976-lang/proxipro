@extends('pro.layout')

@section('title', 'Demandes et missions - Espace Pro')
@section('topbar_title', 'Demandes et missions')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/pro-opportunities.css') }}?v={{ filemtime(public_path('css/pro-opportunities.css')) }}">
@endpush

@section('content')
@php
    // Une seule couleur par état, la même que pour les devis et les factures (.pro-status-*).
    $pkOrderTone = fn ($order) => match ($order->status) {
        \App\Models\ServiceOrder::STATUS_FUNDED, \App\Models\ServiceOrder::STATUS_COMPLETED => 'success',
        \App\Models\ServiceOrder::STATUS_DISPUTED => 'danger',
        default => 'warning',
    };
@endphp
<div class="pro-content-header">
    <div>
        <h1>Demandes et missions</h1>
        <p class="pk-opps__lead">Repérez une demande, proposez votre prix et suivez la prestation jusqu’au paiement.</p>
    </div>
    <a href="{{ route('proposals.index') }}" class="btn btn-pro-outline pro-mobile-full">
        <i class="fas fa-paper-plane" aria-hidden="true"></i> Toutes mes propositions
    </a>
</div>

@if($providerTrades->isNotEmpty())
    {{-- Les métiers qui rattachent une demande au prestataire, et l'endroit où les changer. --}}
    <div class="pk-opps-trades">
        <span class="pk-opps-trades__label">Demandes reçues pour</span>
        <ul class="pk-opps-trades__list" aria-label="Métiers pris en compte">
            @foreach($providerTrades->take(5) as $trade)
                <li>{{ $trade }}</li>
            @endforeach
            @if($providerTrades->count() > 5)
                <li>+{{ $providerTrades->count() - 5 }}</li>
            @endif
        </ul>
        <a href="{{ route('service-provider.mes-services') }}" class="pk-opps-trades__edit">Modifier mes compétences</a>
    </div>
@else
    <div class="pk-opps-setup" role="alert">
        <span class="pk-opps-setup__icon" aria-hidden="true"><i class="fas fa-screwdriver-wrench"></i></span>
        <div>
            <strong>Indiquez vos métiers pour recevoir les bonnes demandes.</strong>
            <p>Sans catégorie active, Prokejem ne peut pas déterminer quelles opportunités vous correspondent.</p>
        </div>
        <a href="{{ route('service-provider.mes-services') }}" class="btn btn-pro-primary">Ajouter mes compétences</a>
    </div>
@endif

<div class="pipeline-grid" aria-label="Demandes et missions, étape par étape">
    <section class="pipeline-stage" data-stage="new" aria-labelledby="pipeline-new-title">
        @include('pro.opportunities.stage-head', ['stage' => 'new', 'title' => 'À examiner', 'subtitle' => 'Commandes à accepter, demandes compatibles', 'count' => $pipelineCounts['new']])

        @foreach($ordersToAnswer as $order)
            @include('pro.opportunities.order-to-answer', ['order' => $order])
        @endforeach

        @forelse($newOpportunities as $opportunity)
            @php
                $opportunityDetails = \App\Support\ServiceDemandIntakeSchema::presentationDetails(
                    $opportunity->main_category ?: $opportunity->category,
                    $opportunity->ad_details
                );
            @endphp
            <article class="pipeline-card" data-opportunity-id="{{ $opportunity->id }}">
                <div class="pipeline-card-eyebrow">
                    <span>{{ $opportunity->category }}</span>
                    @if($opportunity->isCurrentlyUrgent())
                        <span class="pro-status pro-status-danger"><i class="fas fa-bolt" aria-hidden="true"></i> Urgent</span>
                    @else
                        <span>{{ $opportunity->created_at->diffForHumans() }}</span>
                    @endif
                </div>
                <h3>{{ $opportunity->title }}</h3>
                <div class="pipeline-client">
                    @if($opportunity->user?->avatar)
                        <img class="pipeline-avatar" src="{{ storage_url($opportunity->user->avatar) }}" alt="">
                    @else
                        <span class="pipeline-avatar">{{ strtoupper(mb_substr($opportunity->user?->name ?? 'C', 0, 1)) }}</span>
                    @endif
                    <span>{{ $opportunity->user?->name ?? 'Client' }}</span>
                </div>
                <dl class="pipeline-facts">
                    <div class="pipeline-fact"><dt>Lieu</dt><dd>{{ $opportunity->city ?: $opportunity->location }}</dd></div>
                    <div class="pipeline-fact"><dt>Budget</dt><dd>{{ $opportunity->formatted_price }}</dd></div>
                    @foreach(collect($opportunityDetails)->take(2) as $detail)
                        <div class="pipeline-fact"><dt>{{ $detail['label'] }}</dt><dd>{{ $detail['value'] }}</dd></div>
                    @endforeach
                </dl>
                <a href="{{ route('ads.show', $opportunity) }}" class="pipeline-card-action">
                    Étudier et proposer <i class="fas fa-arrow-right" aria-hidden="true"></i>
                </a>
            </article>
        @empty
            @if($ordersToAnswer->isEmpty())
                @include('pro.opportunities.empty', ['icon' => 'fa-inbox', 'text' => 'Aucune nouvelle demande compatible pour le moment.',
                    'linkUrl' => route('ads.index', ['type' => 'demandes']), 'linkLabel' => 'Parcourir toutes les demandes'])
            @endif
        @endforelse
    </section>

    <section class="pipeline-stage" data-stage="proposed" aria-labelledby="pipeline-proposed-title">
        @include('pro.opportunities.stage-head', ['stage' => 'proposed', 'title' => 'Propositions envoyées', 'subtitle' => 'Décision du client attendue', 'count' => $pipelineCounts['proposed']])

        @forelse($sentProposals as $proposal)
            <article class="pipeline-card" data-proposal-id="{{ $proposal->id }}">
                <div class="pipeline-card-eyebrow">
                    <span class="pro-status pro-status-warning"><i class="fas fa-clock" aria-hidden="true"></i> En attente</span>
                    <span>{{ $proposal->created_at->diffForHumans() }}</span>
                </div>
                <h3>{{ $proposal->ad?->title ?? 'Demande indisponible' }}</h3>
                <div class="pipeline-client">
                    <i class="fas fa-circle-user" aria-hidden="true"></i>
                    <span>{{ $proposal->ad?->user?->name ?? 'Client' }}</span>
                </div>
                <dl class="pipeline-facts">
                    <div class="pipeline-fact"><dt>Votre prix</dt><dd>{{ \App\Support\Money::eur($proposal->amount) }}</dd></div>
                    <div class="pipeline-fact"><dt>Intervention</dt><dd>{{ $proposal->scheduled_for?->format('d/m/Y') ?? 'À convenir' }}</dd></div>
                </dl>
                <a href="{{ route('proposals.index') }}" class="pipeline-card-action">
                    Gérer ma proposition <i class="fas fa-arrow-right" aria-hidden="true"></i>
                </a>
            </article>
        @empty
            @include('pro.opportunities.empty', ['icon' => 'fa-paper-plane', 'text' => 'Vos propositions en attente apparaîtront ici.'])
        @endforelse
    </section>

    <section class="pipeline-stage" data-stage="active" aria-labelledby="pipeline-active-title">
        @include('pro.opportunities.stage-head', ['stage' => 'active', 'title' => 'Missions en cours', 'subtitle' => 'Paiement et réalisation', 'count' => $pipelineCounts['active']])

        @forelse($activeMissions as $mission)
            <article class="pipeline-card" data-order-id="{{ $mission->id }}">
                <div class="pipeline-card-eyebrow">
                    <span class="pro-status pro-status-{{ $pkOrderTone($mission) }}">{{ $mission->status_label }}</span>
                    <span>{{ $mission->order_number }}</span>
                </div>
                <h3>{{ $mission->ad?->title ?? 'Mission' }}</h3>
                <div class="pipeline-client">
                    <i class="fas fa-circle-user" aria-hidden="true"></i>
                    <span>{{ $mission->buyer?->name ?? 'Client' }}</span>
                </div>
                <dl class="pipeline-facts">
                    <div class="pipeline-fact"><dt>Montant</dt><dd>{{ \App\Support\Money::eur($mission->amount) }}</dd></div>
                    <div class="pipeline-fact"><dt>Date</dt><dd>{{ $mission->scheduled_for?->format('d/m/Y') ?? 'À convenir' }}</dd></div>
                </dl>
                <a href="{{ route('service-orders.index') }}" class="pipeline-card-action">
                    Suivre la mission <i class="fas fa-arrow-right" aria-hidden="true"></i>
                </a>
            </article>
        @empty
            @include('pro.opportunities.empty', ['icon' => 'fa-briefcase', 'text' => 'Les commandes acceptées apparaîtront ici, jusqu’à la fin de la prestation.'])
        @endforelse
    </section>

    <section class="pipeline-stage" data-stage="completed" aria-labelledby="pipeline-completed-title">
        @include('pro.opportunities.stage-head', ['stage' => 'completed', 'title' => 'Terminées', 'subtitle' => 'Paiement versé', 'count' => $pipelineCounts['completed']])

        @forelse($completedMissions as $mission)
            <article class="pipeline-card" data-order-id="{{ $mission->id }}">
                <div class="pipeline-card-eyebrow">
                    <span class="pro-status pro-status-success"><i class="fas fa-check" aria-hidden="true"></i> Terminée</span>
                    <span>{{ $mission->released_at?->format('d/m/Y') }}</span>
                </div>
                <h3>{{ $mission->ad?->title ?? 'Mission terminée' }}</h3>
                <div class="pipeline-client">
                    <i class="fas fa-circle-user" aria-hidden="true"></i>
                    <span>{{ $mission->buyer?->name ?? 'Client' }}</span>
                </div>
                <dl class="pipeline-facts">
                    <div class="pipeline-fact"><dt>Montant net</dt><dd>{{ \App\Support\Money::eur($mission->seller_amount) }}</dd></div>
                    <div class="pipeline-fact"><dt>Paiement</dt><dd>{{ $mission->payment_status_label }}</dd></div>
                </dl>
                <a href="{{ route('service-orders.index') }}" class="pipeline-card-action">
                    Voir l’historique <i class="fas fa-arrow-right" aria-hidden="true"></i>
                </a>
            </article>
        @empty
            @include('pro.opportunities.empty', ['icon' => 'fa-flag-checkered', 'text' => 'L’historique des missions payées apparaîtra ici.'])
        @endforelse
    </section>
</div>
@endsection
