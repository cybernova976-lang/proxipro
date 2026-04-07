@extends('layouts.app')

@section('title', 'Booster votre annonce - ProxiPro')

@push('styles')
<style>
    body { background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); min-height: 100vh; }
    
    .boost-container {
        max-width: 1100px;
        margin: 0 auto;
        padding: 40px 20px;
    }
    
    .boost-header {
        text-align: center;
        margin-bottom: 40px;
    }
    
    .boost-header h1 {
        font-size: 2.2rem;
        font-weight: 800;
        background: linear-gradient(135deg, #3b82f6, #8b5cf6);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-bottom: 12px;
    }
    
    .boost-header p {
        color: #64748b;
        font-size: 1.1rem;
    }
    
    /* Ad Preview */
    .ad-preview-card {
        background: white;
        border-radius: 20px;
        padding: 24px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.08);
        display: flex;
        align-items: center;
        gap: 20px;
        margin-bottom: 24px;
        border: 2px solid #e2e8f0;
    }
    
    .ad-preview-image {
        width: 100px;
        height: 100px;
        border-radius: 16px;
        object-fit: cover;
        background: linear-gradient(135deg, #3b82f6, #8b5cf6);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 2rem;
        flex-shrink: 0;
    }
    
    .ad-preview-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 16px;
    }
    
    .ad-preview-info h3 {
        font-size: 1.2rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 6px;
    }
    
    .ad-preview-meta {
        display: flex;
        gap: 16px;
        color: #64748b;
        font-size: 0.9rem;
        flex-wrap: wrap;
    }
    
    .ad-preview-meta i {
        margin-right: 4px;
        color: #3b82f6;
    }
    
    /* ===== SMART STATUS DASHBOARD ===== */
    .visibility-dashboard {
        background: white;
        border-radius: 20px;
        padding: 28px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.08);
        margin-bottom: 30px;
        border: 2px solid #e2e8f0;
    }
    
    .visibility-dashboard h3 {
        font-size: 1.15rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .status-bars {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }
    
    .status-bar-item {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 14px 18px;
        border-radius: 14px;
    }
    
    .status-bar-item.urgent-bar {
        background: linear-gradient(135deg, #fef2f2, #fee2e2);
        border: 2px solid #fca5a5;
    }
    
    .status-bar-item.boost-bar {
        background: linear-gradient(135deg, #fefce8, #fef3c7);
        border: 2px solid #fcd34d;
    }
    
    .status-bar-item.inactive-bar {
        background: #f8fafc;
        border: 2px dashed #cbd5e1;
    }
    
    .status-bar-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        flex-shrink: 0;
    }
    
    .status-bar-icon.urgent-icon {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: white;
    }
    
    .status-bar-icon.boost-icon {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: white;
    }
    
    .status-bar-icon.inactive-icon {
        background: #e2e8f0;
        color: #94a3b8;
    }
    
    .status-bar-info {
        flex: 1;
    }
    
    .status-bar-info strong {
        display: block;
        font-size: 0.95rem;
        color: #1e293b;
        margin-bottom: 2px;
    }
    
    .status-bar-info small {
        color: #64748b;
        font-size: 0.82rem;
    }
    
    .status-bar-countdown {
        text-align: right;
        flex-shrink: 0;
    }
    
    .status-bar-countdown .days-left {
        font-size: 1.5rem;
        font-weight: 800;
        line-height: 1;
    }
    
    .status-bar-countdown .days-label {
        font-size: 0.72rem;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .days-warning { color: #ef4444; }
    .days-ok { color: #f59e0b; }
    .days-good { color: #10b981; }
    
    /* Smart Alert */
    .smart-alert {
        display: flex;
        align-items: flex-start;
        gap: 14px;
        padding: 18px 22px;
        border-radius: 16px;
        margin-bottom: 24px;
    }
    
    .smart-alert.alert-warning-custom {
        background: linear-gradient(135deg, #fffbeb, #fef3c7);
        border: 2px solid #fbbf24;
    }
    
    .smart-alert.alert-danger-custom {
        background: linear-gradient(135deg, #fef2f2, #fee2e2);
        border: 2px solid #f87171;
    }
    
    .smart-alert.alert-info-custom {
        background: linear-gradient(135deg, #eff6ff, #dbeafe);
        border: 2px solid #60a5fa;
    }
    
    .smart-alert.alert-success-custom {
        background: linear-gradient(135deg, #f0fdf4, #dcfce7);
        border: 2px solid #4ade80;
    }
    
    .smart-alert-icon {
        font-size: 1.5rem;
        flex-shrink: 0;
        margin-top: 2px;
    }
    
    .smart-alert-content h4 {
        font-weight: 700;
        font-size: 1rem;
        margin-bottom: 4px;
        color: #1e293b;
    }
    
    .smart-alert-content p {
        margin: 0;
        font-size: 0.9rem;
        color: #475569;
        line-height: 1.5;
    }
    
    /* User Points */
    .user-points-box {
        background: linear-gradient(135deg, #10b981, #059669);
        border-radius: 16px;
        padding: 20px 28px;
        color: white;
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 30px;
        box-shadow: 0 8px 25px rgba(16, 185, 129, 0.3);
    }
    
    .user-points-box .points-info {
        display: flex;
        align-items: center;
        gap: 14px;
    }
    
    .user-points-box i { font-size: 2rem; }
    .user-points-box h4 { margin: 0; font-size: 1.6rem; font-weight: 800; }
    .user-points-box span { opacity: 0.9; }
    
    .btn-buy-points {
        background: rgba(255,255,255,0.2);
        border: 2px solid white;
        color: white;
        padding: 10px 20px;
        border-radius: 10px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s;
    }
    
    .btn-buy-points:hover {
        background: white;
        color: #10b981;
    }
    
    /* ===== PACKAGES GRID ===== */
    .packages-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 24px;
        margin-bottom: 40px;
    }
    
    .package-card {
        background: white;
        border-radius: 24px;
        padding: 28px;
        box-shadow: 0 15px 50px rgba(0,0,0,0.08);
        border: 3px solid transparent;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }
    
    .package-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 25px 60px rgba(0,0,0,0.15);
    }
    
    .package-card.recommended {
        border-color: #10b981;
    }
    
    .package-card.recommended::before {
        content: 'Recommandé';
        position: absolute;
        top: 20px;
        right: -35px;
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
        padding: 6px 45px;
        font-size: 0.75rem;
        font-weight: 700;
        transform: rotate(45deg);
        text-transform: uppercase;
    }
    
    .package-card.not-useful {
        opacity: 0.55;
        filter: grayscale(40%);
    }
    
    .package-card.not-useful:hover {
        opacity: 0.75;
        transform: translateY(-4px);
    }
    
    .package-card.popular {
        border-color: #f59e0b;
    }
    
    .package-card.popular::before {
        content: 'Populaire';
        position: absolute;
        top: 20px;
        right: -35px;
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: white;
        padding: 6px 45px;
        font-size: 0.75rem;
        font-weight: 700;
        transform: rotate(45deg);
        text-transform: uppercase;
    }
    
    .package-icon {
        width: 64px;
        height: 64px;
        border-radius: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.6rem;
        color: white;
        margin-bottom: 18px;
    }
    
    .package-card h3 {
        font-size: 1.3rem;
        font-weight: 800;
        color: #1e293b;
        margin-bottom: 8px;
    }
    
    .package-duration {
        color: #64748b;
        font-size: 0.9rem;
        margin-bottom: 14px;
    }
    
    .package-description {
        color: #475569;
        font-size: 0.9rem;
        line-height: 1.6;
        margin-bottom: 16px;
    }
    
    .package-features {
        list-style: none;
        padding: 0;
        margin: 0 0 20px;
    }
    
    .package-features li {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 6px 0;
        color: #334155;
        font-size: 0.85rem;
    }
    
    .package-features li i { color: #10b981; font-size: 0.8rem; }
    
    /* Warning inside card */
    .package-warning {
        background: #fef3c7;
        border: 1px solid #fcd34d;
        border-radius: 10px;
        padding: 10px 14px;
        margin-bottom: 16px;
        display: flex;
        align-items: flex-start;
        gap: 8px;
        font-size: 0.82rem;
        color: #92400e;
    }
    
    .package-warning i {
        color: #f59e0b;
        margin-top: 2px;
        flex-shrink: 0;
    }
    
    .package-recommendation {
        background: #dcfce7;
        border: 1px solid #86efac;
        border-radius: 10px;
        padding: 10px 14px;
        margin-bottom: 16px;
        display: flex;
        align-items: flex-start;
        gap: 8px;
        font-size: 0.82rem;
        color: #166534;
    }
    
    .package-recommendation i {
        color: #10b981;
        margin-top: 2px;
        flex-shrink: 0;
    }
    
    /* Pricing */
    .package-pricing {
        border-top: 2px solid #f1f5f9;
        padding-top: 18px;
        margin-bottom: 18px;
    }
    
    .price-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }
    
    .price-label { color: #64748b; font-size: 0.88rem; }
    .price-value { font-size: 1.25rem; font-weight: 800; color: #1e293b; }
    .price-value.points { color: #10b981; }
    .price-value.euros { color: #3b82f6; }
    
    /* Purchase Buttons */
    .purchase-buttons {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    
    .btn-boost {
        width: 100%;
        padding: 12px 18px;
        border-radius: 12px;
        font-weight: 700;
        font-size: 0.92rem;
        border: none;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    
    .btn-boost-points {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
    }
    
    .btn-boost-points:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(16, 185, 129, 0.4);
    }
    
    .btn-boost-points:disabled {
        background: #cbd5e1;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }
    
    .btn-boost-stripe {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: white;
    }
    
    .btn-boost-stripe:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(59, 130, 246, 0.4);
    }
    
    /* Refresh Section */
    .refresh-section {
        background: linear-gradient(135deg, #fef3c7, #fde68a);
        border: 2px solid #f59e0b;
        border-radius: 20px;
        padding: 30px;
        margin-bottom: 30px;
        text-align: center;
    }
    
    .refresh-section h3 { color: #92400e; font-weight: 700; margin-bottom: 12px; }
    .refresh-section p { color: #a16207; margin-bottom: 16px; }
    
    .btn-refresh {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: white;
        border: none;
        padding: 12px 28px;
        border-radius: 12px;
        font-weight: 700;
        transition: all 0.3s;
    }
    
    .btn-refresh:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(245, 158, 11, 0.4);
        color: white;
    }
    
    .btn-refresh:disabled {
        background: #cbd5e1;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }

    .btn-refresh-stripe {
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        color: white;
        border: none;
        padding: 12px 28px;
        border-radius: 12px;
        font-weight: 700;
        transition: all 0.3s;
        margin-left: 10px;
    }

    .btn-refresh-stripe:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(99, 102, 241, 0.4);
        color: white;
    }

    .pro-discount-banner {
        background: linear-gradient(135deg, #fef3c7, #fde68a);
        border: 2px solid #f59e0b;
        border-radius: 16px;
        padding: 16px 24px;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        gap: 12px;
        font-weight: 600;
        color: #92400e;
    }

    .pro-discount-banner i { font-size: 1.4rem; color: #f59e0b; }

    .price-original {
        text-decoration: line-through;
        color: #94a3b8;
        font-size: 0.85em;
        margin-right: 6px;
    }
    
    /* Benefits */
    .benefits-section {
        background: white;
        border-radius: 24px;
        padding: 40px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.06);
    }
    
    .benefits-section h2 {
        text-align: center;
        font-size: 1.5rem;
        font-weight: 800;
        color: #1e293b;
        margin-bottom: 30px;
    }
    
    .benefits-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
        gap: 24px;
    }
    
    .benefit-item { text-align: center; padding: 20px; }
    
    .benefit-icon {
        width: 56px;
        height: 56px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        margin: 0 auto 14px;
    }
    
    .benefit-item h4 { font-size: 1.05rem; font-weight: 700; color: #1e293b; margin-bottom: 6px; }
    .benefit-item p { color: #64748b; font-size: 0.88rem; line-height: 1.5; }
    
    @media (max-width: 768px) {
        .packages-grid { grid-template-columns: 1fr; }
        .ad-preview-card { flex-direction: column; text-align: center; }
        .user-points-box { flex-direction: column; text-align: center; gap: 16px; }
        .status-bar-item { flex-wrap: wrap; }
    }
</style>
@endpush

@section('content')
<div class="boost-container">
    <!-- Header -->
    <div class="boost-header">
        <h1><i class="fas fa-rocket me-2"></i>Boostez votre annonce</h1>
        <p>Multipliez votre visibilité et apparaissez dans la section "Professionnels Premium"</p>
    </div>
    
    <!-- Ad Preview -->
    <div class="ad-preview-card">
        <div class="ad-preview-image">
            @if($ad->photos && count($ad->photos) > 0)
                <img src="{{ storage_url($ad->photos[0]) }}" alt="{{ $ad->title }}">
            @else
                <i class="fas fa-image"></i>
            @endif
        </div>
        <div class="ad-preview-info">
            <h3>{{ $ad->title }}</h3>
            <div class="ad-preview-meta">
                <span><i class="fas fa-tag"></i> {{ $ad->category }}</span>
                <span><i class="fas fa-map-marker-alt"></i> {{ $ad->location }}</span>
                @if($ad->price)
                    <span><i class="fas fa-euro-sign"></i> {{ number_format($ad->price, 0, ',', ' ') }} €</span>
                @endif
            </div>
        </div>
    </div>
    
    <!-- ========== SMART VISIBILITY DASHBOARD ========== -->
    @if($boostStatus['has_any_visibility'])
    <div class="visibility-dashboard">
        <h3>
            <i class="fas fa-chart-bar" style="color: #3b82f6;"></i>
            Statut de visibilité de cette annonce
        </h3>
        
        <div class="status-bars">
            {{-- Urgent Status --}}
            @if($boostStatus['is_urgent'])
            <div class="status-bar-item urgent-bar">
                <div class="status-bar-icon urgent-icon">
                    <i class="fas fa-fire"></i>
                </div>
                <div class="status-bar-info">
                    <strong>Mode Urgent actif</strong>
                    <small>
                        @if($boostStatus['is_permanent_urgent'])
                            Permanent
                        @else
                            Expire le {{ $boostStatus['urgent_until']->format('d/m/Y à H:i') }}
                        @endif
                    </small>
                </div>
                <div class="status-bar-countdown">
                    @php $urgDays = $boostStatus['urgent_days_left']; @endphp
                    @if($boostStatus['is_permanent_urgent'])
                    <div class="days-left days-good">∞</div>
                    <div class="days-label">permanent</div>
                    @else
                    <div class="days-left {{ $urgDays <= 1 ? 'days-warning' : ($urgDays <= 3 ? 'days-ok' : 'days-good') }}">
                        {{ $urgDays }}
                    </div>
                    <div class="days-label">jour{{ $urgDays > 1 ? 's' : '' }} restant{{ $urgDays > 1 ? 's' : '' }}</div>
                    @endif
                </div>
            </div>
            @endif
            
            {{-- Boost Status --}}
            @if($boostStatus['is_boosted'])
            <div class="status-bar-item boost-bar">
                <div class="status-bar-icon boost-icon">
                    <i class="fas fa-rocket"></i>
                </div>
                <div class="status-bar-info">
                    <strong>Boost {{ ucfirst(str_replace('_', ' ', $boostStatus['boost_type'] ?? '')) }} actif</strong>
                    <small>Expire le {{ $boostStatus['boost_end']->format('d/m/Y à H:i') }}</small>
                </div>
                <div class="status-bar-countdown">
                    @php $bstDays = $boostStatus['boost_days_left']; @endphp
                    <div class="days-left {{ $bstDays <= 1 ? 'days-warning' : ($bstDays <= 3 ? 'days-ok' : 'days-good') }}">
                        {{ $bstDays }}
                    </div>
                    <div class="days-label">jour{{ $bstDays > 1 ? 's' : '' }} restant{{ $bstDays > 1 ? 's' : '' }}</div>
                </div>
            </div>
            @endif
            
            {{-- No boost but has urgent --}}
            @if(!$boostStatus['is_boosted'] && $boostStatus['is_urgent'])
            <div class="status-bar-item inactive-bar">
                <div class="status-bar-icon inactive-icon">
                    <i class="fas fa-rocket"></i>
                </div>
                <div class="status-bar-info">
                    <strong>Boost classique</strong>
                    <small>Non activé — choisissez un plan ci-dessous</small>
                </div>
            </div>
            @endif
        </div>
    </div>
    @endif
    
    <!-- ========== SMART ALERTS ========== -->
    @if($boostStatus['is_urgent'] && !$boostStatus['is_boosted'] && ($boostStatus['is_permanent_urgent'] || $boostStatus['urgent_days_left'] > 3))
    <div class="smart-alert alert-info-custom">
        <i class="fas fa-info-circle smart-alert-icon" style="color: #3b82f6;"></i>
        <div class="smart-alert-content">
            <h4>Votre mode Urgent est déjà actif</h4>
            <p>
                @if($boostStatus['is_permanent_urgent'])
                    Votre annonce est déjà en mode <strong>Urgent permanent</strong>.
                    Vous pouvez ajouter un boost classique pour améliorer encore sa visibilité.
                @else
                    Votre annonce est en mode Urgent pour encore <strong>{{ $boostStatus['urgent_days_left'] }} jours</strong>.
                    Les plans de boost inférieurs à cette durée n'apporteront pas de visibilité supplémentaire.
                    Seuls les plans plus longs sont recommandés.
                @endif
            </p>
        </div>
    </div>
    @endif
    
    @if($boostStatus['is_expiring_soon'])
    <div class="smart-alert alert-danger-custom">
        <i class="fas fa-exclamation-triangle smart-alert-icon" style="color: #ef4444;"></i>
        <div class="smart-alert-content">
            <h4>Votre visibilité expire bientôt !</h4>
            <p>
                @if(!$boostStatus['is_permanent_urgent'] && $boostStatus['is_urgent'] && $boostStatus['urgent_days_left'] <= 2)
                    Le mode <strong>Urgent</strong> expire dans {{ $boostStatus['urgent_days_left'] }} jour{{ $boostStatus['urgent_days_left'] > 1 ? 's' : '' }}.
                @endif
                @if($boostStatus['is_boosted'] && $boostStatus['boost_days_left'] <= 2)
                    Votre <strong>Boost</strong> expire dans {{ $boostStatus['boost_days_left'] }} jour{{ $boostStatus['boost_days_left'] > 1 ? 's' : '' }}.
                @endif
                Prolongez maintenant pour ne pas perdre votre positionnement premium !
            </p>
        </div>
    </div>
    @endif
    
    @if($boostStatus['is_boosted'] && $boostStatus['is_urgent'])
    <div class="smart-alert alert-success-custom">
        <i class="fas fa-shield-alt smart-alert-icon" style="color: #10b981;"></i>
        <div class="smart-alert-content">
            <h4>Double visibilité active !</h4>
            <p>Votre annonce bénéficie du mode <strong>Urgent</strong> ({{ $boostStatus['is_permanent_urgent'] ? 'permanent' : $boostStatus['urgent_days_left'].'j' }}) ET d'un <strong>Boost</strong> ({{ $boostStatus['boost_days_left'] }}j).
            Visibilité maximale jusqu'au {{ $boostStatus['best_visibility_end']->format('d/m/Y') }}.</p>
        </div>
    </div>
    @endif
    
    <!-- Pro Discount Banner -->
    @if($isPro)
    <div class="pro-discount-banner">
        <i class="fas fa-crown"></i>
        <span>Abonné Pro — Vous bénéficiez de <strong>-20%</strong> sur tous les boosts, rafraîchissements et publications urgentes !</span>
    </div>
    @endif

    <!-- User Points -->
    <div class="user-points-box">
        <div class="points-info">
            <i class="fas fa-coins"></i>
            <div>
                <h4>{{ number_format($userPoints, 0, ',', ' ') }} points</h4>
                <span>Solde disponible</span>
            </div>
        </div>
        <a href="{{ route('pricing.index') }}" class="btn-buy-points">
            <i class="fas fa-plus me-1"></i> Acheter des points
        </a>
    </div>
    
    <!-- Refresh Section (shown when boost expired) -->
    @if(!$boostStatus['is_boosted'] && $ad->boost_end && $ad->boost_end->isPast())
    <div class="refresh-section">
        <h3><i class="fas fa-sync me-2"></i>Rafraîchir votre annonce</h3>
        <p>Le boost précédent a expiré. Rafraîchissez pour remonter dans les résultats.</p>
        <div class="d-flex justify-content-center align-items-center gap-3 flex-wrap">
            <form action="{{ route('ads.refresh', $ad) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn-refresh" {{ $userPoints < $refreshConfig['price_points'] ? 'disabled' : '' }}>
                    <i class="fas fa-coins me-2"></i>
                    @if($userPoints >= $refreshConfig['price_points'])
                        @if($isPro)<span class="price-original">10 pts</span>@endif
                        Rafraîchir pour {{ $refreshConfig['price_points'] }} points
                    @else
                        Points insuffisants ({{ $refreshConfig['price_points'] }} requis)
                    @endif
                </button>
            </form>
            <form action="{{ route('ads.refresh.stripe', $ad) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn-refresh-stripe">
                    <i class="fas fa-credit-card me-2"></i>
                    @if($isPro)<span class="price-original">3,00 €</span>@endif
                    Rafraîchir pour {{ number_format($refreshConfig['price_euros'], 2, ',', ' ') }} €
                </button>
            </form>
        </div>
    </div>
    @endif
    
    <!-- ========== PACKAGES GRID ========== -->
    <div class="packages-grid">
        @foreach($packages as $key => $package)
        @php
            $isRecommended = ($recommendedKey === $key) && ($package['is_useful'] ?? true);
            $isNotUseful = !($package['is_useful'] ?? true);
            $isPopular = ($key === 'boost_7') && !$isRecommended;
        @endphp
        <div class="package-card {{ $isRecommended ? 'recommended' : ($isPopular ? 'popular' : '') }} {{ $isNotUseful ? 'not-useful' : '' }}">
            <div class="package-icon" style="background: {{ $package['color'] }};">
                <i class="{{ $package['icon'] }}"></i>
            </div>
            
            <h3>{{ $package['name'] }}</h3>
            <div class="package-duration">
                <i class="fas fa-calendar-alt me-1"></i> {{ $package['duration_days'] }} jours de visibilité
            </div>
            
            {{-- Smart warning --}}
            @if(!empty($package['warning']))
            <div class="package-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <span>{{ $package['warning'] }}</span>
            </div>
            @endif
            
            {{-- Smart recommendation --}}
            @if(!empty($package['recommendation']))
            <div class="package-recommendation">
                <i class="fas fa-lightbulb"></i>
                <span>{{ $package['recommendation'] }}</span>
            </div>
            @endif
            
            <p class="package-description">{{ $package['description'] }}</p>
            
            <ul class="package-features">
                @foreach($package['features'] as $feature)
                <li><i class="fas fa-check-circle"></i> {{ $feature }}</li>
                @endforeach
            </ul>
            
            <div class="package-pricing">
                <div class="price-row">
                    <span class="price-label">Avec points</span>
                    <span class="price-value points">
                        @if($isPro && isset($package['price_points_original']))
                            <span class="price-original">{{ $package['price_points_original'] }} pts</span>
                        @endif
                        {{ $package['price_points'] }} pts
                    </span>
                </div>
                <div class="price-row">
                    <span class="price-label">Par carte</span>
                    <span class="price-value euros">
                        @if($isPro && isset($package['price_euros_original']))
                            <span class="price-original">{{ number_format($package['price_euros_original'], 2, ',', ' ') }} €</span>
                        @endif
                        {{ number_format($package['price_euros'], 2, ',', ' ') }} €
                    </span>
                </div>
            </div>
            
            <div class="purchase-buttons">
                <form action="{{ route('boost.purchase.points', $ad) }}" method="POST" style="display: contents;">
                    @csrf
                    <input type="hidden" name="package" value="{{ $key }}">
                    <button type="submit" class="btn-boost btn-boost-points" 
                            {{ $userPoints < $package['price_points'] ? 'disabled' : '' }}
                            @if($isNotUseful) 
                                onclick="return confirm('Ce plan est inférieur à votre visibilité actuelle. Voulez-vous quand même acheter ce boost ?')" 
                            @endif>
                        <i class="fas fa-coins"></i>
                        @if($userPoints >= $package['price_points'])
                            Payer {{ $package['price_points'] }} points
                        @else
                            Points insuffisants
                        @endif
                    </button>
                </form>
                
                <form action="{{ route('boost.purchase.stripe', $ad) }}" method="POST" style="display: contents;">
                    @csrf
                    <input type="hidden" name="package" value="{{ $key }}">
                    <button type="submit" class="btn-boost btn-boost-stripe"
                            @if($isNotUseful) 
                                onclick="return confirm('Ce plan est inférieur à votre visibilité actuelle. Voulez-vous quand même payer ?')" 
                            @endif>
                        <i class="fas fa-credit-card"></i>
                        Payer {{ number_format($package['price_euros'], 2, ',', ' ') }} €
                    </button>
                </form>
            </div>
        </div>
        @endforeach
    </div>
    
    <!-- Benefits Section -->
    <div class="benefits-section">
        <h2><i class="fas fa-star me-2 text-warning"></i>Pourquoi booster votre annonce ?</h2>
        <div class="benefits-grid">
            <div class="benefit-item">
                <div class="benefit-icon" style="background: #dbeafe; color: #3b82f6;"><i class="fas fa-eye"></i></div>
                <h4>Visibilité x10</h4>
                <p>Apparaît dans la section "Professionnels Premium" vue par tous</p>
            </div>
            <div class="benefit-item">
                <div class="benefit-icon" style="background: #fef3c7; color: #f59e0b;"><i class="fas fa-crown"></i></div>
                <h4>Badge Premium</h4>
                <p>Un badge distinctif qui inspire confiance</p>
            </div>
            <div class="benefit-item">
                <div class="benefit-icon" style="background: #dcfce7; color: #10b981;"><i class="fas fa-chart-line"></i></div>
                <h4>Plus de contacts</h4>
                <p>En moyenne 5x plus de demandes</p>
            </div>
            <div class="benefit-item">
                <div class="benefit-icon" style="background: #f3e8ff; color: #8b5cf6;"><i class="fas fa-bolt"></i></div>
                <h4>Résultats rapides</h4>
                <p>Actif immédiatement après paiement</p>
            </div>
        </div>
    </div>
    
    <!-- Back Button -->
    <div class="text-center mt-4">
        <a href="{{ route('ads.show', $ad) }}" class="btn btn-outline-secondary rounded-pill px-4">
            <i class="fas fa-arrow-left me-2"></i>Retour à l'annonce
        </a>
    </div>
</div>
@endsection
