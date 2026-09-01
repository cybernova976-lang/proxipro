@extends('layouts.app')

@section('title', 'Comparer les propositions - ' . config('app.name'))

@push('styles')
<style>
    .compare-page{max-width:1240px;margin:0 auto;padding:30px 20px 60px;color:#0f172a}
    .compare-back{display:inline-flex;align-items:center;gap:7px;color:#475569;text-decoration:none;font-size:.86rem;font-weight:650;margin-bottom:16px}
    .compare-back:hover{color:#1d4ed8}
    .compare-hero{border:1px solid #cbdcf8;border-top:4px solid #2563eb;border-radius:22px;padding:24px;background:linear-gradient(135deg,#fff 0%,#eff6ff 62%,#ecfeff 100%);box-shadow:0 18px 44px -34px rgba(30,64,175,.65)}
    .compare-eyebrow{display:block;color:#1d4ed8;font-size:.72rem;font-weight:800;letter-spacing:.1em;text-transform:uppercase;margin-bottom:8px}
    .compare-hero h1{font-size:clamp(1.45rem,3vw,2rem);font-weight:850;letter-spacing:-.025em;margin:0 0 8px}
    .compare-hero p{margin:0;color:#475569;line-height:1.55}
    .compare-summary{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin-top:20px}
    .compare-summary>div{padding:13px 15px;border:1px solid #dbe6f5;border-radius:13px;background:rgba(255,255,255,.86)}
    .compare-summary b{display:block;font-size:1.16rem;color:#1d4ed8}
    .compare-summary span{display:block;color:#64748b;font-size:.75rem;margin-top:3px}
    .compare-guide{display:flex;align-items:flex-start;gap:11px;margin:18px 0 20px;padding:14px 16px;border:1px solid #fde3a7;border-radius:14px;background:#fffbeb;color:#784b08;font-size:.86rem;line-height:1.5}
    .compare-guide i{margin-top:3px;color:#d97706}
    .compare-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
    .compare-card{position:relative;display:flex;flex-direction:column;min-width:0;border:1px solid #dbe3ef;border-radius:19px;background:#fff;overflow:hidden;box-shadow:0 10px 30px -24px rgba(15,23,42,.6)}
    .compare-card.is-accepted{border-color:#86d5b9;box-shadow:0 10px 30px -24px rgba(5,150,105,.8)}
    .compare-card__badges{display:flex;flex-wrap:wrap;gap:7px;padding:13px 16px 0}
    .compare-badge{display:inline-flex;align-items:center;gap:5px;border-radius:999px;padding:5px 9px;font-size:.69rem;font-weight:800}
    .compare-badge--price{background:#ecfdf5;color:#047857}.compare-badge--date{background:#eff6ff;color:#1d4ed8}.compare-badge--accepted{background:#d1fae5;color:#065f46}
    .compare-provider{display:flex;align-items:center;gap:12px;padding:17px 18px 13px}
    .compare-avatar{width:54px;height:54px;border-radius:15px;overflow:hidden;display:grid;place-items:center;flex:none;background:linear-gradient(135deg,#dbeafe,#e0e7ff);color:#1d4ed8;font-weight:850}
    .compare-avatar img{width:100%;height:100%;object-fit:cover}
    .compare-provider__copy{min-width:0;display:grid;gap:3px}.compare-provider__name{display:flex;align-items:center;gap:6px;font-weight:800;min-width:0}.compare-provider__name a{color:#0f172a;text-decoration:none;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.compare-provider__name a:hover{color:#1d4ed8}.compare-verified{width:18px;height:18px;border-radius:50%;display:grid;place-items:center;flex:none;background:#059669;color:#fff;font-size:.62rem}
    .compare-job{font-size:.8rem;color:#475569}.compare-rating{font-size:.78rem;color:#64748b}.compare-rating i{color:#f59e0b}.compare-rating strong{color:#334155}
    .compare-facts{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));border-top:1px solid #edf1f6;border-bottom:1px solid #edf1f6}
    .compare-fact{padding:14px 18px;min-width:0}.compare-fact:nth-child(even){border-left:1px solid #edf1f6}.compare-fact span{display:block;color:#64748b;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em}.compare-fact b{display:block;margin-top:4px;font-size:1rem;overflow-wrap:anywhere}.compare-fact--amount b{color:#1d4ed8;font-size:1.3rem}
    .compare-message{padding:16px 18px;flex:1}.compare-message span{display:block;color:#64748b;font-size:.72rem;font-weight:750;margin-bottom:7px}.compare-message p{margin:0;color:#334155;font-size:.87rem;line-height:1.55;white-space:pre-line}
    .compare-actions{display:flex;flex-wrap:wrap;gap:8px;padding:14px 18px;border-top:1px solid #edf1f6;background:#fbfcfe}.compare-actions .btn{border-radius:10px;font-weight:700}.compare-actions form{display:inline-flex}
    .compare-empty{margin-top:18px;padding:38px 20px;text-align:center;border:1px dashed #b8c6d9;border-radius:18px;background:#f8fafc;color:#64748b}.compare-empty i{font-size:2rem;color:#94a3b8;margin-bottom:10px}.compare-empty h2{font-size:1.15rem;color:#334155}
    @media(max-width:760px){.compare-page{padding:18px 12px 44px}.compare-hero{padding:19px 17px;border-radius:17px}.compare-summary{grid-template-columns:1fr}.compare-grid{grid-template-columns:1fr}.compare-provider{padding:15px}.compare-facts{grid-template-columns:1fr}.compare-fact:nth-child(even){border-left:0;border-top:1px solid #edf1f6}.compare-actions{padding:13px 15px}.compare-actions .btn,.compare-actions form{flex:1 1 100%}.compare-actions form .btn{width:100%}}
</style>
@endpush

@section('content')
@php
    $proposalCount = $proposals->count();
    $pendingCount = $proposals->where('status', \App\Models\ServiceProposal::STATUS_PENDING)->count();
    $maximumAmount = $proposals->where('status', \App\Models\ServiceProposal::STATUS_PENDING)
        ->max(fn ($proposal) => (float) $proposal->amount);
@endphp

<main class="compare-page">
    <a href="{{ route('feed') }}" class="compare-back"><i class="fas fa-arrow-left"></i> Retour à l’accueil</a>

    <header class="compare-hero">
        <span class="compare-eyebrow">Votre demande</span>
        <h1>{{ $ad->title }}</h1>
        <p>Comparez les éléments communiqués par chaque prestataire. Prokejem ne choisit pas à votre place et n’attribue aucun score automatique.</p>

        <div class="compare-summary" aria-label="Résumé des propositions">
            <div><b>{{ $proposalCount }}</b><span>proposition{{ $proposalCount > 1 ? 's' : '' }} à comparer</span></div>
            <div>
                <b>
                    @if($minimumAmount !== null)
                        {{ number_format((float) $minimumAmount, 0, ',', ' ') }} €@if($maximumAmount !== null && (float) $maximumAmount !== (float) $minimumAmount) – {{ number_format((float) $maximumAmount, 0, ',', ' ') }} €@endif
                    @else
                        —
                    @endif
                </b>
                <span>fourchette des propositions en attente</span>
            </div>
            <div><b>{{ $earliestDate ? $earliestDate->translatedFormat('d M Y') : 'À convenir' }}</b><span>premier créneau communiqué</span></div>
        </div>
    </header>

    <div class="compare-guide">
        <i class="fas fa-lightbulb" aria-hidden="true"></i>
        <div><strong>Conseil :</strong> comparez aussi les avis vérifiés, le message et la disponibilité. Le prix le plus bas n’est pas toujours le choix le plus adapté.</div>
    </div>

    @if($proposals->isNotEmpty())
        <section class="compare-grid" aria-label="Comparaison des propositions">
            @foreach($proposals as $proposal)
                @php
                    $provider = $proposal->provider;
                    $isPending = $proposal->status === \App\Models\ServiceProposal::STATUS_PENDING;
                    $isAccepted = $proposal->status === \App\Models\ServiceProposal::STATUS_ACCEPTED;
                    $isLowest = $isPending && $pendingCount > 1 && $minimumAmount !== null
                        && (float) $proposal->amount === (float) $minimumAmount;
                    $isEarliest = $isPending && $pendingCount > 1 && $proposal->scheduled_for && $earliestDate
                        && $proposal->scheduled_for->equalTo($earliestDate);
                    $rating = $provider->verified_reviews_avg ?? null;
                    $reviews = (int) ($provider->verified_reviews_received_count ?? 0);
                    $job = $provider->profession
                        ?: ($provider->service_category
                        ?: ($provider->services->first()?->subcategory
                        ?: 'Prestataire de services'));
                @endphp

                <article class="compare-card{{ $isAccepted ? ' is-accepted' : '' }}">
                    @if($isLowest || $isEarliest || $isAccepted)
                        <div class="compare-card__badges">
                            @if($isAccepted)<span class="compare-badge compare-badge--accepted"><i class="fas fa-check"></i> Proposition choisie</span>@endif
                            @if($isLowest)<span class="compare-badge compare-badge--price"><i class="fas fa-euro-sign"></i> Prix le plus bas</span>@endif
                            @if($isEarliest)<span class="compare-badge compare-badge--date"><i class="far fa-calendar-check"></i> Créneau le plus proche</span>@endif
                        </div>
                    @endif

                    <div class="compare-provider">
                        <a href="{{ route('profile.public', $provider) }}" class="compare-avatar" aria-label="Voir le profil de {{ $provider->name }}">
                            @if($provider->avatar)
                                <img src="{{ storage_url($provider->avatar) }}" alt="" loading="lazy">
                            @else
                                {{ Str::upper(Str::substr($provider->name, 0, 1)) }}
                            @endif
                        </a>
                        <div class="compare-provider__copy">
                            <div class="compare-provider__name">
                                <a href="{{ route('profile.public', $provider) }}">{{ $provider->name }}</a>
                                @if($provider->hasVerifiedProfileBadge())
                                    <span class="compare-verified" title="Identité vérifiée" aria-label="Identité vérifiée"><i class="fas fa-check"></i></span>
                                @endif
                            </div>
                            <span class="compare-job">{{ $job }}</span>
                            <span class="compare-rating">
                                @if($reviews > 0 && $rating !== null)
                                    <i class="fas fa-star"></i> <strong>{{ number_format((float) $rating, 1, ',', '') }}</strong> ({{ $reviews }} avis vérifié{{ $reviews > 1 ? 's' : '' }})
                                @else
                                    Aucun avis vérifié
                                @endif
                            </span>
                        </div>
                    </div>

                    <div class="compare-facts">
                        <div class="compare-fact compare-fact--amount"><span>Prix proposé</span><b>{{ number_format((float) $proposal->amount, 2, ',', ' ') }} €</b></div>
                        <div class="compare-fact"><span>Intervention</span><b>{{ $proposal->scheduled_for ? $proposal->scheduled_for->translatedFormat('d M Y') : 'Date à convenir' }}</b></div>
                        @if($provider->years_experience)
                            <div class="compare-fact"><span>Expérience déclarée</span><b>{{ $provider->years_experience }} an{{ $provider->years_experience > 1 ? 's' : '' }}</b></div>
                        @endif
                        @if($provider->city)
                            <div class="compare-fact"><span>Localisation</span><b>{{ $provider->city }}</b></div>
                        @endif
                    </div>

                    <div class="compare-message">
                        <span>Message du prestataire</span>
                        <p>{{ $proposal->message }}</p>
                    </div>

                    <div class="compare-actions">
                        <a href="{{ route('profile.public', $provider) }}" class="btn btn-outline-secondary btn-sm">Voir le profil</a>
                        @if($isPending)
                            <form method="POST" action="{{ route('proposals.accept', $proposal) }}">
                                @csrf
                                <button class="btn btn-primary btn-sm"><i class="fas fa-check me-1"></i>Choisir cette proposition</button>
                            </form>
                            <form method="POST" action="{{ route('proposals.refuse', $proposal) }}">
                                @csrf
                                <button class="btn btn-outline-danger btn-sm">Écarter</button>
                            </form>
                        @elseif($proposal->serviceOrder)
                            <a href="{{ route('service-orders.index') }}" class="btn btn-success btn-sm">Continuer vers le paiement</a>
                        @endif
                    </div>
                </article>
            @endforeach
        </section>
    @else
        <section class="compare-empty">
            <i class="far fa-comments"></i>
            <h2>Aucune proposition à comparer</h2>
            <p>Les nouvelles propositions apparaîtront ici dès qu’un prestataire répondra à votre demande.</p>
            <a href="{{ route('ads.edit', $ad) }}" class="btn btn-primary">Améliorer ma demande</a>
        </section>
    @endif
</main>
@endsection
