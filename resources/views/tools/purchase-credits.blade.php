@extends('layouts.app')

@section('title', 'Cr&eacute;dits documents - Devis & Factures | Lunamars')

@section('content')
<style>
    .credits-hero {
        background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%);
        border-radius: 16px; padding: 2rem; margin-bottom: 2rem; text-align: center;
    }
    .pack-card {
        border: 2px solid #e2e8f0; border-radius: 16px; padding: 1.75rem;
        text-align: center; transition: all 0.25s ease; cursor: pointer;
        background: white; height: 100%; position: relative;
    }
    .pack-card:hover { border-color: #6366f1; box-shadow: 0 8px 24px rgba(99,102,241,0.15); transform: translateY(-3px); }
    .pack-card.popular { border-color: #6366f1; }
    .pack-card.popular::before {
        content: 'Populaire'; position: absolute; top: -12px; left: 50%; transform: translateX(-50%);
        background: linear-gradient(135deg, #6366f1, #4f46e5); color: white;
        padding: 3px 16px; border-radius: 20px; font-size: 0.75rem; font-weight: 600;
    }
    .pack-icon { width: 56px; height: 56px; border-radius: 14px; display: inline-flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 1rem; }
    .pack-name { font-size: 1.1rem; font-weight: 700; color: #1e293b; margin-bottom: 0.25rem; }
    .pack-credits { font-size: 0.85rem; color: #64748b; margin-bottom: 1rem; }
    .pack-price { font-size: 2rem; font-weight: 800; color: #1e293b; margin-bottom: 0.15rem; }
    .pack-unit { font-size: 0.8rem; color: #94a3b8; margin-bottom: 1.25rem; }
    .btn-pack {
        border: none; border-radius: 10px; font-weight: 600; font-size: 0.95rem;
        padding: 0.7rem 1.5rem; width: 100%; transition: all 0.2s;
    }
    .btn-pack-primary { background: linear-gradient(135deg, #6366f1, #4f46e5); color: white; box-shadow: 0 4px 12px rgba(99,102,241,0.3); }
    .btn-pack-primary:hover { opacity: 0.9; color: white; transform: translateY(-1px); }
    .btn-pack-outline { background: white; color: #6366f1; border: 2px solid #6366f1; }
    .btn-pack-outline:hover { background: #eef2ff; color: #4f46e5; }
    .credits-balance-card {
        background: white; border: 2px solid #e2e8f0; border-radius: 14px;
        padding: 1.25rem 1.5rem; display: flex; align-items: center; gap: 1rem;
        margin-bottom: 2rem;
    }
    .credits-balance-card .balance-icon {
        width: 52px; height: 52px; border-radius: 12px; display: flex;
        align-items: center; justify-content: center; font-size: 1.3rem; flex-shrink: 0;
    }
    .subscription-banner {
        background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
        border: 2px solid #6ee7b7; border-radius: 16px; padding: 2rem;
        text-align: center; margin-bottom: 2rem;
    }
    .promo-subscription {
        background: linear-gradient(135deg, #fefce8 0%, #fef3c7 100%);
        border: 2px solid #fbbf24; border-radius: 16px; padding: 1.5rem;
        margin-bottom: 2rem;
    }
</style>

<div class="container py-4" style="max-width: 860px;">

    @if(session('success'))
        <div class="alert alert-success d-flex align-items-center" style="border-radius: 12px;">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger d-flex align-items-center" style="border-radius: 12px;">
            <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
        </div>
    @endif
    @if(session('warning'))
        <div class="alert alert-warning d-flex align-items-center" style="border-radius: 12px;">
            <i class="fas fa-exclamation-triangle me-2"></i> {{ session('warning') }}
        </div>
    @endif

    @if($hasSubscription)
        {{-- ======== ABONN&Eacute; : DOCUMENTS GRATUITS ======== --}}
        <div class="subscription-banner">
            <div style="width: 64px; height: 64px; background: #d1fae5; border-radius: 16px; display: inline-flex; align-items: center; justify-content: center; font-size: 1.8rem; color: #059669; margin-bottom: 1rem;">
                <i class="fas fa-crown"></i>
            </div>
            <h1 class="fw-bold mb-2" style="font-size: 1.5rem; color: #065f46;">G&eacute;n&eacute;ration de documents incluse</h1>
            <p style="color: #047857; margin-bottom: 1.25rem; max-width: 520px; margin-left: auto; margin-right: auto;">
                Votre <strong>abonnement professionnel</strong> vous donne acc&egrave;s &agrave; la cr&eacute;ation <strong>illimit&eacute;e</strong> de devis et factures au format PDF. Aucun cr&eacute;dit suppl&eacute;mentaire n&eacute;cessaire !
            </p>
            <div class="d-flex justify-content-center gap-3 flex-wrap">
                <a href="{{ route('quote-tool.create-invoice') }}" class="btn" style="background: #059669; color: white; border-radius: 10px; font-weight: 600; padding: 0.65rem 1.5rem;">
                    <i class="fas fa-file-invoice me-1"></i> Cr&eacute;er une facture
                </a>
                <a href="{{ route('quote-tool.create-quote') }}" class="btn" style="background: white; color: #059669; border: 2px solid #059669; border-radius: 10px; font-weight: 600; padding: 0.65rem 1.5rem;">
                    <i class="fas fa-file-alt me-1"></i> Cr&eacute;er un devis
                </a>
            </div>
        </div>

        {{-- Avantages abonnement --}}
        <div class="row g-3">
            <div class="col-md-4">
                <div class="d-flex align-items-center gap-2 p-2">
                    <i class="fas fa-check-circle" style="color: #059669; font-size: 1.1rem;"></i>
                    <span style="font-size: 0.85rem; font-weight: 500; color: #475569;">Devis et factures illimit&eacute;s</span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="d-flex align-items-center gap-2 p-2">
                    <i class="fas fa-check-circle" style="color: #059669; font-size: 1.1rem;"></i>
                    <span style="font-size: 0.85rem; font-weight: 500; color: #475569;">PDF professionnels inclus</span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="d-flex align-items-center gap-2 p-2">
                    <i class="fas fa-check-circle" style="color: #059669; font-size: 1.1rem;"></i>
                    <span style="font-size: 0.85rem; font-weight: 500; color: #475569;">Aucun co&ucirc;t suppl&eacute;mentaire</span>
                </div>
            </div>
        </div>

    @else
        {{-- ======== NON ABONN&Eacute; : PACKS + PROMO ABONNEMENT ======== --}}
        <div class="credits-hero">
            <h1 class="fw-bold mb-2" style="font-size: 1.6rem; color: #1e293b;">Cr&eacute;dits documents</h1>
            <p class="text-muted mb-0" style="max-width: 520px; margin: 0 auto;">
                Chaque cr&eacute;dit vous permet de cr&eacute;er <strong>un devis ou une facture</strong> professionnelle au format PDF.
            </p>
        </div>

        {{-- Promotion abonnement --}}
        <div class="promo-subscription">
            <div class="d-flex align-items-start gap-3">
                <div style="width: 48px; height: 48px; background: #fef3c7; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <i class="fas fa-crown" style="color: #d97706; font-size: 1.2rem;"></i>
                </div>
                <div class="flex-grow-1">
                    <h5 class="fw-bold mb-1" style="font-size: 1rem; color: #92400e;">Documents illimit&eacute;s avec un abonnement</h5>
                    <p class="mb-2" style="font-size: 0.87rem; color: #a16207;">
                        Souscrivez &agrave; un <strong>abonnement mensuel</strong> (9,99&euro;/mois) ou <strong>annuel</strong> (85&euro;/an) et b&eacute;n&eacute;ficiez de la <strong>g&eacute;n&eacute;ration illimit&eacute;e</strong> de devis et factures, sans avoir &agrave; acheter de cr&eacute;dits.
                    </p>
                    <a href="{{ route('pro.subscription') }}" class="btn btn-sm" style="background: #d97706; color: white; border-radius: 8px; font-weight: 600; padding: 6px 18px;">
                        <i class="fas fa-crown me-1"></i> D&eacute;couvrir les abonnements
                    </a>
                </div>
            </div>
        </div>

        {{-- Solde actuel --}}
        <div class="credits-balance-card">
            <div class="balance-icon" style="background: {{ $creditsRemaining > 0 ? '#ecfdf5' : '#fef2f2' }}; color: {{ $creditsRemaining > 0 ? '#059669' : '#dc2626' }};">
                <i class="fas {{ $creditsRemaining > 0 ? 'fa-file-invoice' : 'fa-file-circle-exclamation' }}"></i>
            </div>
            <div>
                <div class="fw-bold" style="font-size: 1.15rem; color: #1e293b;">
                    {{ $creditsRemaining }} cr&eacute;dit(s) document(s)
                </div>
                <div class="text-muted" style="font-size: 0.85rem;">
                    @if($creditsRemaining > 0)
                        Vous pouvez encore cr&eacute;er {{ $creditsRemaining }} devis ou facture(s).
                    @else
                        Vous n'avez plus de cr&eacute;dits. Achetez un pack ou souscrivez &agrave; un abonnement.
                    @endif
                </div>
            </div>
        </div>

        <h5 class="fw-bold mb-3" style="color: #1e293b;">
            <i class="fas fa-shopping-cart me-2" style="color: #6366f1;"></i>Achat de cr&eacute;dits &agrave; l'unit&eacute;
        </h5>

        {{-- Packs --}}
        <div class="row g-4 mb-4">
            @foreach($packs as $key => $pack)
                <div class="col-md-4">
                    <div class="pack-card {{ $key === 'pack_20' ? 'popular' : '' }}">
                        <div class="pack-icon" style="background: {{ $key === 'pack_5' ? '#eef2ff' : ($key === 'pack_20' ? '#ede9fe' : '#fef3c7') }}; color: {{ $key === 'pack_5' ? '#6366f1' : ($key === 'pack_20' ? '#7c3aed' : '#d97706') }};">
                            <i class="fas {{ $key === 'pack_5' ? 'fa-file-alt' : ($key === 'pack_20' ? 'fa-briefcase' : 'fa-building') }}"></i>
                        </div>
                        <div class="pack-name">{{ $pack['label'] }}</div>
                        <div class="pack-credits">{{ $pack['credits'] }} documents</div>
                        <div class="pack-price">{{ $pack['price_display'] }}&euro;</div>
                        <div class="pack-unit">soit {{ number_format($pack['price'] / $pack['credits'] / 100, 2, ',', '') }}&euro; / document</div>

                        <form action="{{ route('quote-tool.credits.purchase') }}" method="POST">
                            @csrf
                            <input type="hidden" name="pack" value="{{ $key }}">
                            <button type="submit" class="btn btn-pack {{ $key === 'pack_20' ? 'btn-pack-primary' : 'btn-pack-outline' }}">
                                <i class="fas fa-credit-card me-1"></i> Acheter
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Garanties --}}
        <div class="row g-3 mt-2">
            <div class="col-md-4">
                <div class="d-flex align-items-center gap-2 p-2">
                    <i class="fas fa-shield-alt" style="color: #6366f1; font-size: 1.1rem;"></i>
                    <span style="font-size: 0.85rem; font-weight: 500; color: #475569;">Paiement s&eacute;curis&eacute; via Stripe</span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="d-flex align-items-center gap-2 p-2">
                    <i class="fas fa-infinity" style="color: #6366f1; font-size: 1.1rem;"></i>
                    <span style="font-size: 0.85rem; font-weight: 500; color: #475569;">Cr&eacute;dits sans expiration</span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="d-flex align-items-center gap-2 p-2">
                    <i class="fas fa-file-pdf" style="color: #6366f1; font-size: 1.1rem;"></i>
                    <span style="font-size: 0.85rem; font-weight: 500; color: #475569;">PDF professionnels inclus</span>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
