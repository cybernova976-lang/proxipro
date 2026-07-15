@extends('pro.layout')
@section('title', 'Tableau de bord - Espace Pro')

@section('content')

{{-- ===== Flash message pour tentative d'accès à une fonctionnalité Pro ===== --}}
@if(session('warning'))
<div class="alert alert-warning d-flex align-items-center gap-2 mb-4" style="border-radius: 12px; border-left: 4px solid #f59e0b;">
    <i class="fas fa-exclamation-triangle"></i>
    {{ session('warning') }}
</div>
@endif

@if($isPro ?? false)
{{-- ============================= VUE PROS ============================= --}}
<div class="pro-content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1" style="font-size: 0.8rem;">
                <li class="breadcrumb-item"><a href="{{ route('pro.dashboard') }}" style="color: var(--pro-primary);">Espace Pro</a></li>
                <li class="breadcrumb-item active">Tableau de bord</li>
            </ol>
        </nav>
        <h1>Bonjour, {{ Auth::user()->company_name ?? Auth::user()->name }} 👋</h1>
        <p class="text-muted mb-0" style="font-size: 0.88rem;">Voici un aperçu de votre activité professionnelle</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('pro.quotes.create') }}" class="btn btn-pro-outline">
            <i class="fas fa-plus me-1"></i> Nouveau devis
        </a>
        <a href="{{ route('ads.create') }}" class="btn btn-pro-primary">
            <i class="fas fa-bullhorn me-1"></i> Publier
        </a>
    </div>
</div>

{{-- Stats Grid --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="pro-stat-card">
            <div class="pro-stat-icon" style="background: rgba(59,130,246,0.1); color: #3b82f6;">
                <i class="fas fa-users"></i>
            </div>
            <div class="pro-stat-value">{{ $stats['total_clients'] }}</div>
            <div class="pro-stat-label">Clients</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="pro-stat-card">
            <div class="pro-stat-icon" style="background: rgba(245,158,11,0.1); color: #f59e0b;">
                <i class="fas fa-file-alt"></i>
            </div>
            <div class="pro-stat-value">{{ $stats['total_quotes'] }}</div>
            <div class="pro-stat-label">Devis</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="pro-stat-card">
            <div class="pro-stat-icon" style="background: rgba(16,185,129,0.1); color: #10b981;">
                <i class="fas fa-euro-sign"></i>
            </div>
            <div class="pro-stat-value">{{ number_format($stats['total_revenue'], 0, ',', ' ') }}€</div>
            <div class="pro-stat-label">Chiffre d'affaires</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="pro-stat-card">
            <div class="pro-stat-icon" style="background: rgba(236,72,153,0.1); color: #ec4899;">
                <i class="fas fa-star"></i>
            </div>
            <div class="pro-stat-value">{{ $stats['average_rating'] }}<span style="font-size: 0.8rem; color: var(--pro-text-secondary);">/5</span></div>
            <div class="pro-stat-label">Note moyenne ({{ $stats['reviews_count'] }} avis)</div>
        </div>
    </div>
</div>

{{-- Secondary stats --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="pro-card text-center py-3">
            <div class="text-primary fw-bold fs-4">{{ $stats['monthly_revenue'] }}€</div>
            <div class="text-muted" style="font-size: 0.78rem;">Revenus ce mois</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="pro-card text-center py-3">
            <div class="text-warning fw-bold fs-4">{{ $stats['pending_quotes'] }}</div>
            <div class="text-muted" style="font-size: 0.78rem;">Devis en attente</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="pro-card text-center py-3">
            <div class="text-danger fw-bold fs-4">{{ $stats['unpaid_invoices'] }}</div>
            <div class="text-muted" style="font-size: 0.78rem;">Factures impayées</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="pro-card text-center py-3">
            <div class="text-success fw-bold fs-4">{{ $stats['active_ads'] }}</div>
            <div class="text-muted" style="font-size: 0.78rem;">Annonces actives</div>
        </div>
    </div>
</div>

<div class="row g-4">
    {{-- Recent Quotes --}}
    <div class="col-lg-6">
        <div class="pro-card">
            <div class="pro-card-title">
                <i class="fas fa-file-alt text-warning"></i> Derniers devis
                <a href="{{ route('pro.quotes') }}" class="ms-auto" style="font-size: 0.8rem; color: var(--pro-primary);">Voir tout →</a>
            </div>
            @if($recentQuotes->isEmpty())
                <div class="pro-empty py-4">
                    <div class="pro-empty-icon">📄</div>
                    <p class="mb-2">Aucun devis pour le moment</p>
                    <a href="{{ route('pro.quotes.create') }}" class="btn btn-pro-primary btn-sm">Créer un devis</a>
                </div>
            @else
                <div class="table-responsive">
                    <table class="pro-table">
                        <thead>
                            <tr>
                                <th>N°</th>
                                <th>Client</th>
                                <th>Montant</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentQuotes as $quote)
                            <tr>
                                <td><a href="{{ route('pro.quotes.show', $quote->id) }}" style="color: var(--pro-primary); font-weight: 600;">{{ $quote->quote_number }}</a></td>
                                <td>{{ Str::limit($quote->client_name, 20) }}</td>
                                <td class="fw-bold">{{ number_format($quote->total, 2, ',', ' ') }}€</td>
                                <td><span class="pro-status pro-status-{{ $quote->getStatusColor() }}">{{ $quote->getStatusLabel() }}</span></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- Recent Invoices --}}
    <div class="col-lg-6">
        <div class="pro-card">
            <div class="pro-card-title">
                <i class="fas fa-file-invoice-dollar text-danger"></i> Dernières factures
                <a href="{{ route('pro.invoices') }}" class="ms-auto" style="font-size: 0.8rem; color: var(--pro-primary);">Voir tout →</a>
            </div>
            @if($recentInvoices->isEmpty())
                <div class="pro-empty py-4">
                    <div class="pro-empty-icon">🧾</div>
                    <p class="mb-2">Aucune facture encore</p>
                    <a href="{{ route('pro.invoices.create') }}" class="btn btn-pro-primary btn-sm">Créer une facture</a>
                </div>
            @else
                <div class="table-responsive">
                    <table class="pro-table">
                        <thead>
                            <tr>
                                <th>N°</th>
                                <th>Client</th>
                                <th>Montant</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentInvoices as $invoice)
                            <tr>
                                <td><a href="{{ route('pro.invoices.show', $invoice->id) }}" style="color: var(--pro-primary); font-weight: 600;">{{ $invoice->invoice_number }}</a></td>
                                <td>{{ Str::limit($invoice->client_name, 20) }}</td>
                                <td class="fw-bold">{{ number_format($invoice->total, 2, ',', ' ') }}€</td>
                                <td><span class="pro-status pro-status-{{ $invoice->getStatusColor() }}">{{ $invoice->getStatusLabel() }}</span></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Recent Clients --}}
<div class="pro-card mt-3">
    <div class="pro-card-title">
        <i class="fas fa-users text-primary"></i> Derniers clients
        <a href="{{ route('pro.clients') }}" class="ms-auto" style="font-size: 0.8rem; color: var(--pro-primary);">Voir tout →</a>
    </div>
    @if($recentClients->isEmpty())
        <div class="pro-empty py-4">
            <div class="pro-empty-icon">👥</div>
            <h5>Pas encore de clients</h5>
            <p>Vos clients apparaîtront ici quand ils interagiront avec vous</p>
        </div>
    @else
        <div class="row g-3">
            @foreach($recentClients as $client)
            <div class="col-md-6 col-lg-4">
                <div class="d-flex align-items-center gap-3 p-3" style="background: #f8fafc; border-radius: 12px;">
                    <div style="width: 44px; height: 44px; border-radius: 12px; background: rgba(59,130,246,0.1); color: #3b82f6; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1.1rem;">
                        {{ strtoupper(substr($client->name, 0, 1)) }}
                    </div>
                    <div>
                        <div class="fw-semibold" style="font-size: 0.9rem;">{{ $client->name }}</div>
                        <div class="text-muted" style="font-size: 0.78rem;">
                            {{ $client->city ?? 'Ville non renseignée' }}
                            · <span class="pro-status-{{ $client->getStatusColor() }}">{{ $client->getStatusLabel() }}</span>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    @endif
</div>

{{-- Subscription status --}}
@if(!$subscription)
<div class="pro-card mt-3" style="background: linear-gradient(135deg, rgba(249,115,22,0.05), rgba(239,68,68,0.05)); border-color: #fed7aa;">
    <div class="d-flex align-items-center gap-3 flex-wrap">
        <div style="font-size: 2.5rem;">🚀</div>
        <div class="flex-grow-1">
            <h5 class="fw-bold mb-1">Activez votre abonnement Pro</h5>
            <p class="text-muted mb-0">Retrouvez les demandes de clients et développez votre activité.</p>
        </div>
        <a href="{{ route('pro.subscription') }}" class="btn btn-pro-primary">
            <i class="fas fa-crown me-1"></i> S'abonner
        </a>
    </div>
</div>
@endif

@else
{{-- ============================= VUE NON-PROS (TEASER) ============================= --}}

<style>
.teaser-locked {
    position: relative;
    border-radius: 16px;
    overflow: hidden;
}
.teaser-locked::after {
    content: '';
    position: absolute;
    inset: 0;
    background: rgba(248, 250, 252, 0.82);
    backdrop-filter: blur(4px);
    border-radius: 16px;
    z-index: 2;
    pointer-events: none;
}
.teaser-lock-badge {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 3;
    background: white;
    border: 2px solid #e0e7ff;
    border-radius: 50%;
    width: 52px;
    height: 52px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 16px rgba(79,70,229,0.15);
    pointer-events: none;
}
.teaser-lock-badge i {
    color: #7c3aed;
    font-size: 1.3rem;
}
.feature-item {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    padding: 14px 0;
    border-bottom: 1px solid #f1f5f9;
}
.feature-item:last-child {
    border-bottom: none;
}
.feature-icon {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}
</style>

{{-- Header teaser --}}
<div class="pro-content-header">
    <div>
        <h1 style="font-size: 1.5rem;">Bienvenue dans l'Espace Pro</h1>
        <p class="text-muted mb-0" style="font-size: 0.88rem;">
            Gérez votre activité professionnelle depuis un seul endroit
        </p>
    </div>
</div>

{{-- Bannière principale --}}
<div class="pro-card mb-4" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); border: none; color: white; padding: 24px 28px;">
    <div class="d-flex align-items-center gap-4 flex-wrap">
        <div style="width: 52px; height: 52px; background: rgba(255,255,255,0.15); border-radius: 14px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 1.6rem;">🚀</div>
        <div class="flex-grow-1">
            <div style="font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; opacity: 0.7; margin-bottom: 4px;">
                <i class="fas fa-crown me-1"></i> Espace Pro
            </div>
            <h4 style="font-size: 1.1rem; font-weight: 700; color: white; margin: 0 0 4px 0;">
                Développez votre activité professionnelle
            </h4>
            <p style="opacity: 0.8; font-size: 0.82rem; margin: 0; line-height: 1.5;">
                Devis, facturation, gestion clients et alertes centralisées — tout en un.
            </p>
        </div>
        <div class="d-flex gap-2 flex-wrap flex-shrink-0">
            <button type="button" data-bs-toggle="modal" data-bs-target="#becomeProviderModal"
                    class="btn" style="background: white; color: #4f46e5; font-weight: 700; border-radius: 20px; padding: 8px 18px; font-size: 0.87rem;">
                <i class="fas fa-rocket me-1"></i> Devenir Prestataire
            </button>
            <a href="{{ route('pricing.index') }}" class="btn"
               style="background: rgba(255,255,255,0.15); color: white; border: 1px solid rgba(255,255,255,0.35); border-radius: 20px; padding: 8px 16px; font-size: 0.87rem;">
                Tarifs
            </a>
        </div>
    </div>
</div>

{{-- Fonctionnalités (aperçu flou) --}}
<div class="row g-4 mb-4">
    {{-- Stats floutées --}}
    <div class="col-12">
        <div class="teaser-locked">
            <div class="teaser-lock-badge"><i class="fas fa-lock"></i></div>
            <div class="row g-3">
                @foreach([['fas fa-users','#3b82f6','Clients','—'],['fas fa-file-alt','#f59e0b','Devis','—'],['fas fa-euro-sign','#10b981','Chiffre d\'affaires','—€'],['fas fa-star','#ec4899','Note moyenne','—/5']] as $s)
                <div class="col-6 col-md-3">
                    <div class="pro-stat-card">
                        <div class="pro-stat-icon" style="background: rgba(0,0,0,0.05); color: {{ $s[1] }};"><i class="{{ $s[0] }}"></i></div>
                        <div class="pro-stat-value" style="filter: blur(6px); user-select: none;">{{ $s[3] }}</div>
                        <div class="pro-stat-label">{{ $s[2] }}</div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Fonctionnalités listées --}}
    <div class="col-lg-6">
        <div class="pro-card h-100">
            <div class="pro-card-title">
                <i class="fas fa-star text-warning"></i> Ce que vous débloquez
            </div>
            <div class="feature-item">
                <div class="feature-icon" style="background: rgba(59,130,246,0.1); color: #3b82f6;"><i class="fas fa-users"></i></div>
                <div>
                    <div class="fw-bold" style="font-size: 0.9rem;">Gestion des clients</div>
                    <div class="text-muted" style="font-size: 0.8rem;">Créez un CRM dédié à votre activité, gardez un historique complet de chaque client.</div>
                </div>
            </div>
            <div class="feature-item">
                <div class="feature-icon" style="background: rgba(245,158,11,0.1); color: #f59e0b;"><i class="fas fa-file-alt"></i></div>
                <div>
                    <div class="fw-bold" style="font-size: 0.9rem;">Devis professionnels</div>
                    <div class="text-muted" style="font-size: 0.8rem;">Générez des devis PDF personnalisés et envoyez-les directement par email ou messagerie.</div>
                </div>
            </div>
            <div class="feature-item">
                <div class="feature-icon" style="background: rgba(16,185,129,0.1); color: #10b981;"><i class="fas fa-file-invoice-dollar"></i></div>
                <div>
                    <div class="fw-bold" style="font-size: 0.9rem;">Facturation</div>
                    <div class="text-muted" style="font-size: 0.8rem;">Émettez des factures conformes, suivez les paiements et gérez votre chiffre d'affaires.</div>
                </div>
            </div>
            <div class="feature-item">
                <div class="feature-icon" style="background: rgba(124,58,237,0.1); color: #7c3aed;"><i class="fas fa-bell"></i></div>
                <div>
                    <div class="fw-bold" style="font-size: 0.9rem;">Alertes centralisées</div>
                    <div class="text-muted" style="font-size: 0.8rem;">Recevez des notifications instantanées dès qu'une annonce correspond à votre métier.</div>
                </div>
            </div>
            <div class="feature-item">
                <div class="feature-icon" style="background: rgba(236,72,153,0.1); color: #ec4899;"><i class="fas fa-chart-line"></i></div>
                <div>
                    <div class="fw-bold" style="font-size: 0.9rem;">Statistiques & Analytics</div>
                    <div class="text-muted" style="font-size: 0.8rem;">Visualisez vos revenus, taux de conversion et performance de vos annonces.</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Aperçu tableau flou --}}
    <div class="col-lg-6">
        <div class="pro-card h-100">
            <div class="pro-card-title">
                <i class="fas fa-file-alt text-warning"></i> Aperçu — Derniers devis
            </div>
            <div class="teaser-locked" style="border-radius: 12px;">
                <div class="teaser-lock-badge"><i class="fas fa-lock"></i></div>
                <div class="table-responsive" style="filter: blur(3px);">
                    <table class="pro-table">
                        <thead><tr><th>N°</th><th>Client</th><th>Montant</th><th>Statut</th></tr></thead>
                        <tbody>
                            <tr><td>DEV-001</td><td>Jean Dupont</td><td class="fw-bold">1 200€</td><td><span class="pro-status pro-status-success">Accepté</span></td></tr>
                            <tr><td>DEV-002</td><td>Marie Martin</td><td class="fw-bold">850€</td><td><span class="pro-status pro-status-warning">En attente</span></td></tr>
                            <tr><td>DEV-003</td><td>Société ABC</td><td class="fw-bold">3 400€</td><td><span class="pro-status pro-status-info">Envoyé</span></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="mt-3 pt-3" style="border-top: 1px solid #f1f5f9; text-align: center;">
                <p class="text-muted mb-2" style="font-size: 0.85rem;">Ces fonctionnalités sont réservées aux prestataires et professionnels.</p>
                <button type="button" data-bs-toggle="modal" data-bs-target="#becomeProviderModal" class="btn btn-pro-primary btn-sm">
                    <i class="fas fa-rocket me-1"></i> Activer mon Espace Pro
                </button>
            </div>
        </div>
    </div>
</div>

{{-- CTA final --}}
<div class="pro-card" style="background: linear-gradient(135deg, rgba(79,70,229,0.04) 0%, rgba(124,58,237,0.04) 100%); border-color: #c7d2fe; text-align: center; padding: 40px 24px;">
    <div style="font-size: 3rem; margin-bottom: 12px;">🚀</div>
    <h3 style="font-weight: 800; color: #1e293b; margin-bottom: 8px;">Prêt à développer votre activité ?</h3>
    <p class="text-muted mb-4" style="max-width: 480px; margin: 0 auto 24px;">
        Rejoignez les professionnels qui utilisent ProxiPro pour gérer leurs clients, leurs devis et leur facturation en un seul endroit.
    </p>
    <div class="d-flex gap-3 justify-content-center flex-wrap">
        <button type="button" data-bs-toggle="modal" data-bs-target="#becomeProviderModal"
                class="btn btn-pro-primary" style="padding: 12px 28px; font-size: 0.95rem;">
            <i class="fas fa-crown me-2"></i> Devenir Prestataire Pro
        </button>
        <a href="{{ route('pricing.index') }}" class="btn btn-pro-outline" style="padding: 12px 28px; font-size: 0.95rem;">
            Voir les tarifs
        </a>
    </div>
</div>

@endif
@endsection
