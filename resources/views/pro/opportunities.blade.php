@extends('pro.layout')

@section('title', 'Mes opportunités - Espace Pro')
@section('topbar_title', 'Mes opportunités')

@section('styles')
.opportunity-intro {
    position: relative;
    overflow: hidden;
    border: 1px solid #bfdbfe;
    background: linear-gradient(135deg, #eff6ff 0%, #ffffff 58%, #ecfeff 100%);
}

.opportunity-intro::after {
    content: '';
    position: absolute;
    top: -80px;
    right: -55px;
    width: 210px;
    height: 210px;
    border-radius: 50%;
    background: rgba(37, 99, 235, 0.08);
    pointer-events: none;
}

.opportunity-summary {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: .75rem;
    position: relative;
    z-index: 1;
}

.opportunity-summary-item {
    padding: .9rem 1rem;
    border: 1px solid rgba(148, 163, 184, .28);
    border-radius: 14px;
    background: rgba(255, 255, 255, .85);
}

.opportunity-summary-value {
    display: block;
    color: #0f172a;
    font-size: 1.45rem;
    font-weight: 800;
    line-height: 1;
}

.opportunity-summary-label {
    display: block;
    margin-top: .35rem;
    color: #64748b;
    font-size: .72rem;
    font-weight: 700;
}

.opportunity-categories {
    display: flex;
    flex-wrap: wrap;
    gap: .45rem;
}

.opportunity-category {
    padding: .36rem .65rem;
    border: 1px solid #bfdbfe;
    border-radius: 999px;
    color: #1d4ed8;
    background: #eff6ff;
    font-size: .72rem;
    font-weight: 700;
}

.pipeline-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(250px, 1fr));
    gap: 1rem;
    align-items: start;
}

.pipeline-stage {
    min-width: 0;
    padding: .85rem;
    border: 1px solid var(--pro-border);
    border-radius: 16px;
    background: rgba(248, 250, 252, .78);
}

.pipeline-stage-header {
    display: flex;
    align-items: center;
    gap: .65rem;
    min-height: 40px;
    margin-bottom: .8rem;
}

.pipeline-stage-icon {
    width: 34px;
    height: 34px;
    flex: 0 0 34px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
}

.pipeline-stage-title {
    min-width: 0;
    flex: 1;
}

.pipeline-stage-title h2 {
    margin: 0;
    color: #0f172a;
    font-size: .86rem;
    font-weight: 800;
}

.pipeline-stage-title p {
    margin: .12rem 0 0;
    color: #64748b;
    font-size: .68rem;
}

.pipeline-count {
    min-width: 27px;
    height: 27px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 999px;
    color: #334155;
    background: white;
    font-size: .72rem;
    font-weight: 800;
    box-shadow: 0 1px 3px rgba(15, 23, 42, .09);
}

.pipeline-card {
    display: flex;
    flex-direction: column;
    gap: .75rem;
    margin-bottom: .75rem;
    padding: .9rem;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    background: #fff;
    box-shadow: 0 5px 18px rgba(15, 23, 42, .045);
}

.pipeline-card:last-child { margin-bottom: 0; }

.pipeline-card-eyebrow {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .5rem;
    color: #64748b;
    font-size: .67rem;
    font-weight: 700;
}

.pipeline-card h3 {
    margin: 0;
    color: #0f172a;
    font-size: .9rem;
    font-weight: 800;
    line-height: 1.35;
}

.pipeline-client,
.pipeline-meta {
    display: flex;
    align-items: center;
    gap: .45rem;
    min-width: 0;
    color: #475569;
    font-size: .72rem;
}

.pipeline-client span,
.pipeline-meta span {
    min-width: 0;
    overflow-wrap: anywhere;
}

.pipeline-avatar {
    width: 28px;
    height: 28px;
    flex: 0 0 28px;
    border-radius: 50%;
    object-fit: cover;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #1d4ed8;
    background: #dbeafe;
    font-size: .7rem;
    font-weight: 800;
}

.pipeline-facts {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: .45rem;
}

.pipeline-fact {
    min-width: 0;
    padding: .48rem .55rem;
    border-radius: 9px;
    color: #475569;
    background: #f8fafc;
    font-size: .66rem;
}

.pipeline-fact strong {
    display: block;
    margin-top: .1rem;
    color: #0f172a;
    font-size: .7rem;
    overflow-wrap: anywhere;
}

.pipeline-status {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    padding: .28rem .52rem;
    border-radius: 999px;
    font-size: .65rem;
    font-weight: 800;
}

.pipeline-status-new { color: #1d4ed8; background: #dbeafe; }
.pipeline-status-proposed { color: #92400e; background: #fef3c7; }
.pipeline-status-active { color: #047857; background: #d1fae5; }
.pipeline-status-completed { color: #475569; background: #e2e8f0; }

.pipeline-card-action {
    width: 100%;
    min-height: 40px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: .45rem;
    border: 1px solid #bfdbfe;
    border-radius: 10px;
    color: #1d4ed8;
    background: #eff6ff;
    font-size: .73rem;
    font-weight: 800;
    text-decoration: none;
    transition: transform .15s ease, background .15s ease;
}

.pipeline-card-action:hover {
    color: #1d4ed8;
    background: #dbeafe;
    transform: translateY(-1px);
}

.pipeline-empty {
    padding: 1.25rem .75rem;
    border: 1px dashed #cbd5e1;
    border-radius: 12px;
    color: #64748b;
    background: rgba(255, 255, 255, .7);
    text-align: center;
    font-size: .72rem;
    line-height: 1.55;
}

@media (max-width: 1399px) {
    .pipeline-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}

@media (max-width: 767px) {
    .opportunity-summary {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .pipeline-grid {
        grid-template-columns: minmax(0, 1fr);
    }

    .pipeline-stage {
        padding: .75rem;
    }
}
@endsection

@section('content')
<div class="pro-content-header">
    <div>
        <nav aria-label="Fil d’Ariane">
            <ol class="breadcrumb mb-1" style="font-size: .8rem;">
                <li class="breadcrumb-item"><a href="{{ route('pro.dashboard') }}" style="color: var(--pro-primary);">Espace Pro</a></li>
                <li class="breadcrumb-item active">Mes opportunités</li>
            </ol>
        </nav>
        <h1>Transformez les demandes en missions</h1>
        <p class="text-muted mb-0" style="font-size: .88rem;">Un seul parcours pour repérer une demande, proposer votre prix et suivre la prestation.</p>
    </div>
    <a href="{{ route('proposals.index') }}" class="btn btn-pro-outline pro-mobile-full">
        <i class="fas fa-paper-plane me-1"></i> Toutes mes propositions
    </a>
</div>

<section class="pro-card opportunity-intro mb-4" aria-labelledby="opportunity-summary-title">
    <div class="position-relative" style="z-index: 1;">
        <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-3">
            <div>
                <div class="text-primary text-uppercase fw-bold mb-1" style="font-size: .68rem; letter-spacing: .08em;">Votre activité commerciale</div>
                <h2 id="opportunity-summary-title" class="h5 fw-bold mb-1">Le prochain geste utile est visible immédiatement</h2>
                <p class="text-muted mb-0" style="font-size: .78rem;">Les demandes compatibles disparaissent de la première colonne dès que vous envoyez une proposition.</p>
            </div>
            @if($providerCategories->isNotEmpty())
                <div class="opportunity-categories" aria-label="Métiers pris en compte">
                    @foreach($providerCategories->take(5) as $category)
                        <span class="opportunity-category">{{ $category }}</span>
                    @endforeach
                    @if($providerCategories->count() > 5)
                        <span class="opportunity-category">+{{ $providerCategories->count() - 5 }}</span>
                    @endif
                </div>
            @endif
        </div>

        <div class="opportunity-summary">
            <div class="opportunity-summary-item">
                <span class="opportunity-summary-value">{{ $pipelineCounts['new'] }}</span>
                <span class="opportunity-summary-label">À examiner</span>
            </div>
            <div class="opportunity-summary-item">
                <span class="opportunity-summary-value">{{ $pipelineCounts['proposed'] }}</span>
                <span class="opportunity-summary-label">Propositions en attente</span>
            </div>
            <div class="opportunity-summary-item">
                <span class="opportunity-summary-value">{{ $pipelineCounts['active'] }}</span>
                <span class="opportunity-summary-label">Missions en cours</span>
            </div>
            <div class="opportunity-summary-item">
                <span class="opportunity-summary-value">{{ $pipelineCounts['completed'] }}</span>
                <span class="opportunity-summary-label">Terminées récemment</span>
            </div>
        </div>
    </div>
</section>

@if($providerCategories->isEmpty())
    <div class="alert alert-warning d-flex align-items-start gap-3 mb-4" role="alert" style="border-radius: 14px;">
        <i class="fas fa-tools mt-1" aria-hidden="true"></i>
        <div class="flex-grow-1">
            <strong>Indiquez vos métiers pour recevoir les bonnes demandes.</strong>
            <div class="small mt-1">Sans catégorie active, Prokejem ne peut pas déterminer quelles opportunités vous correspondent.</div>
            <a href="{{ route('pro.profile.edit') }}" class="btn btn-sm btn-warning mt-2">Configurer mes services</a>
        </div>
    </div>
@endif

<div class="pipeline-grid" aria-label="Pipeline des opportunités prestataire">
    <section class="pipeline-stage" data-stage="new" aria-labelledby="pipeline-new-title">
        <header class="pipeline-stage-header">
            <span class="pipeline-stage-icon" style="color: #1d4ed8; background: #dbeafe;"><i class="fas fa-compass"></i></span>
            <div class="pipeline-stage-title">
                <h2 id="pipeline-new-title">1. À examiner</h2>
                <p>Demandes compatibles</p>
            </div>
            <span class="pipeline-count">{{ $pipelineCounts['new'] }}</span>
        </header>

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
                        <span class="pipeline-status pipeline-status-proposed"><i class="fas fa-bolt"></i> Urgent</span>
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
                <div class="pipeline-facts">
                    <div class="pipeline-fact">Lieu<strong>{{ $opportunity->city ?: $opportunity->location }}</strong></div>
                    <div class="pipeline-fact">Budget<strong>{{ $opportunity->formatted_price }}</strong></div>
                    @foreach(collect($opportunityDetails)->take(2) as $detail)
                        <div class="pipeline-fact">{{ $detail['label'] }}<strong>{{ $detail['value'] }}</strong></div>
                    @endforeach
                </div>
                <a href="{{ route('ads.show', $opportunity) }}" class="pipeline-card-action">
                    Étudier et proposer <i class="fas fa-arrow-right"></i>
                </a>
            </article>
        @empty
            <div class="pipeline-empty">
                <i class="fas fa-check-circle text-success d-block mb-2" style="font-size: 1.2rem;"></i>
                Aucune nouvelle demande compatible pour le moment.
            </div>
        @endforelse
    </section>

    <section class="pipeline-stage" data-stage="proposed" aria-labelledby="pipeline-proposed-title">
        <header class="pipeline-stage-header">
            <span class="pipeline-stage-icon" style="color: #92400e; background: #fef3c7;"><i class="fas fa-paper-plane"></i></span>
            <div class="pipeline-stage-title">
                <h2 id="pipeline-proposed-title">2. Proposition envoyée</h2>
                <p>Décision du client attendue</p>
            </div>
            <span class="pipeline-count">{{ $pipelineCounts['proposed'] }}</span>
        </header>

        @forelse($sentProposals as $proposal)
            <article class="pipeline-card" data-proposal-id="{{ $proposal->id }}">
                <div class="pipeline-card-eyebrow">
                    <span class="pipeline-status pipeline-status-proposed"><i class="fas fa-clock"></i> En attente</span>
                    <span>{{ $proposal->created_at->diffForHumans() }}</span>
                </div>
                <h3>{{ $proposal->ad?->title ?? 'Demande indisponible' }}</h3>
                <div class="pipeline-client">
                    <i class="fas fa-user-circle text-muted"></i>
                    <span>{{ $proposal->ad?->user?->name ?? 'Client' }}</span>
                </div>
                <div class="pipeline-facts">
                    <div class="pipeline-fact">Votre prix<strong>{{ number_format((float) $proposal->amount, 2, ',', ' ') }} €</strong></div>
                    <div class="pipeline-fact">Intervention<strong>{{ $proposal->scheduled_for?->format('d/m/Y') ?? 'À convenir' }}</strong></div>
                </div>
                <a href="{{ route('proposals.index') }}" class="pipeline-card-action">
                    Gérer ma proposition <i class="fas fa-arrow-right"></i>
                </a>
            </article>
        @empty
            <div class="pipeline-empty">Vos propositions en attente apparaîtront ici.</div>
        @endforelse
    </section>

    <section class="pipeline-stage" data-stage="active" aria-labelledby="pipeline-active-title">
        <header class="pipeline-stage-header">
            <span class="pipeline-stage-icon" style="color: #047857; background: #d1fae5;"><i class="fas fa-briefcase"></i></span>
            <div class="pipeline-stage-title">
                <h2 id="pipeline-active-title">3. Mission en cours</h2>
                <p>Paiement et réalisation</p>
            </div>
            <span class="pipeline-count">{{ $pipelineCounts['active'] }}</span>
        </header>

        @forelse($activeMissions as $mission)
            <article class="pipeline-card" data-order-id="{{ $mission->id }}">
                <div class="pipeline-card-eyebrow">
                    <span class="pipeline-status pipeline-status-active"><i class="fas fa-circle" style="font-size: .4rem;"></i> {{ $mission->status_label }}</span>
                    <span>{{ $mission->order_number }}</span>
                </div>
                <h3>{{ $mission->ad?->title ?? 'Mission' }}</h3>
                <div class="pipeline-client">
                    <i class="fas fa-user-circle text-muted"></i>
                    <span>{{ $mission->buyer?->name ?? 'Client' }}</span>
                </div>
                <div class="pipeline-facts">
                    <div class="pipeline-fact">Montant<strong>{{ number_format((float) $mission->amount, 2, ',', ' ') }} €</strong></div>
                    <div class="pipeline-fact">Date<strong>{{ $mission->scheduled_for?->format('d/m/Y') ?? 'À convenir' }}</strong></div>
                </div>
                <a href="{{ route('service-orders.index') }}" class="pipeline-card-action">
                    Suivre la mission <i class="fas fa-arrow-right"></i>
                </a>
            </article>
        @empty
            <div class="pipeline-empty">Les propositions acceptées et commandes actives apparaîtront ici.</div>
        @endforelse
    </section>

    <section class="pipeline-stage" data-stage="completed" aria-labelledby="pipeline-completed-title">
        <header class="pipeline-stage-header">
            <span class="pipeline-stage-icon" style="color: #475569; background: #e2e8f0;"><i class="fas fa-flag-checkered"></i></span>
            <div class="pipeline-stage-title">
                <h2 id="pipeline-completed-title">4. Terminée</h2>
                <p>Fonds libérés</p>
            </div>
            <span class="pipeline-count">{{ $pipelineCounts['completed'] }}</span>
        </header>

        @forelse($completedMissions as $mission)
            <article class="pipeline-card" data-order-id="{{ $mission->id }}">
                <div class="pipeline-card-eyebrow">
                    <span class="pipeline-status pipeline-status-completed"><i class="fas fa-check"></i> Terminée</span>
                    <span>{{ $mission->released_at?->format('d/m/Y') }}</span>
                </div>
                <h3>{{ $mission->ad?->title ?? 'Mission terminée' }}</h3>
                <div class="pipeline-client">
                    <i class="fas fa-user-circle text-muted"></i>
                    <span>{{ $mission->buyer?->name ?? 'Client' }}</span>
                </div>
                <div class="pipeline-facts">
                    <div class="pipeline-fact">Montant net<strong>{{ number_format((float) $mission->seller_amount, 2, ',', ' ') }} €</strong></div>
                    <div class="pipeline-fact">Paiement<strong>{{ $mission->payment_status_label }}</strong></div>
                </div>
                <a href="{{ route('service-orders.index') }}" class="pipeline-card-action">
                    Voir l’historique <i class="fas fa-arrow-right"></i>
                </a>
            </article>
        @empty
            <div class="pipeline-empty">L’historique des missions payées apparaîtra ici.</div>
        @endforelse
    </section>
</div>
@endsection
