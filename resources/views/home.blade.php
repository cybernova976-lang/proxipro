@extends('layouts.app')

@section('title', 'Tableau de bord - ProxiPro')

@push('styles')
<style>
    /* ===== CLEAN FLAT DASHBOARD ===== */
    .main-content-with-sidebar {
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%) !important;
        min-height: 100vh;
    }
    .dash-wrap {
        max-width: 1200px;
        margin: 0 auto;
        padding: 28px 20px 60px;
    }

    /* Greeting */
    .dash-greeting {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 28px;
        flex-wrap: wrap;
        gap: 12px;
    }
    .dash-greeting h1 {
        font-size: 1.45rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
    }
    .dash-greeting h1 span {
        color: #64748b;
        font-weight: 400;
    }
    .dash-greeting p {
        margin: 4px 0 0;
        font-size: 0.88rem;
        color: #94a3b8;
    }
    .btn-publish {
        background: linear-gradient(135deg, #7c3aed, #6d28d9);
        color: white;
        border: none;
        padding: 10px 22px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.9rem;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s;
    }
    .btn-publish:hover {
        background: linear-gradient(135deg, #6d28d9, #5b21b6);
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 4px 14px rgba(109, 40, 217, 0.35);
    }

    /* Stats + Points Row */
    .top-row {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr 1fr 280px;
        gap: 16px;
        margin-bottom: 24px;
    }

    .mini-stat {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 14px;
        transition: border-color 0.2s;
    }
    .mini-stat:hover { border-color: #cbd5e1; }

    .mini-stat-icon {
        width: 44px;
        height: 44px;
        border-radius: 11px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        flex-shrink: 0;
    }
    .mini-stat-val {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1e293b;
        line-height: 1;
    }
    .mini-stat-label {
        font-size: 0.78rem;
        color: #94a3b8;
        margin-top: 2px;
    }

    /* Points compact card */
    .points-card {
        background: linear-gradient(135deg, #fef3c7, #fde68a);
        border: 2px solid #fcd34d;
        border-radius: 14px;
        padding: 18px 20px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        text-decoration: none;
        color: inherit;
        transition: all 0.2s;
    }
    .points-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(252, 211, 77, 0.4);
        color: inherit;
    }
    .points-card-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 8px;
    }
    .points-card-top i {
        font-size: 1.2rem;
        color: #f59e0b;
    }
    .points-card-val {
        font-size: 1.6rem;
        font-weight: 800;
        color: #92400e;
        line-height: 1;
    }
    .points-card-label {
        font-size: 0.75rem;
        color: #a16207;
        font-weight: 500;
    }
    .points-card-actions {
        display: flex;
        gap: 6px;
        margin-top: 10px;
    }
    .points-card-actions a {
        font-size: 0.72rem;
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 6px;
        text-decoration: none;
        transition: all 0.15s;
    }
    .pts-buy {
        background: #f59e0b;
        color: white;
    }
    .pts-buy:hover { background: #d97706; color: white; }
    .pts-history {
        background: rgba(146, 64, 14, 0.1);
        color: #92400e;
    }
    .pts-history:hover { background: rgba(146, 64, 14, 0.18); color: #78350f; }

    /* Quick Actions Row */
    .actions-row {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 24px;
    }

    .action-card {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 22px 20px;
        text-decoration: none;
        color: inherit;
        display: flex;
        align-items: center;
        gap: 14px;
        transition: all 0.2s;
    }
    .action-card:hover {
        border-color: #a78bfa;
        background: #faf5ff;
        color: inherit;
        transform: translateY(-2px);
        box-shadow: 0 4px 14px rgba(0,0,0,0.06);
    }
    .action-icon {
        width: 46px;
        height: 46px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
        flex-shrink: 0;
    }
    .action-card h5 {
        font-size: 0.88rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0 0 3px;
    }
    .action-card p {
        font-size: 0.76rem;
        color: #94a3b8;
        margin: 0;
        line-height: 1.3;
    }

    /* Table Section */
    .table-section {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        overflow: hidden;
    }
    .table-section-head {
        padding: 18px 22px;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .table-section-head h3 {
        font-size: 1rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .table-section-head h3 i { color: #7c3aed; font-size: 0.95rem; }

    .dash-table {
        width: 100%;
        margin: 0;
    }
    .dash-table thead th {
        background: #f8fafc;
        font-weight: 600;
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #94a3b8;
        border: none;
        padding: 12px 18px;
    }
    .dash-table tbody td {
        padding: 14px 18px;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
        font-size: 0.88rem;
        color: #334155;
    }
    .dash-table tbody tr:last-child td { border-bottom: none; }
    .dash-table tbody tr:hover { background: #fafbfd; }

    .dash-table a.ad-title-link {
        color: #1e293b;
        text-decoration: none;
        font-weight: 600;
        transition: color 0.15s;
    }
    .dash-table a.ad-title-link:hover { color: #7c3aed; }

    .cat-badge {
        background: #f1f5f9;
        color: #64748b;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 0.78rem;
        font-weight: 500;
    }

    .status-dot {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 0.82rem;
        font-weight: 500;
    }
    .status-dot::before {
        content: '';
        width: 8px;
        height: 8px;
        border-radius: 50%;
        flex-shrink: 0;
    }
    .status-active::before { background: #22c55e; }
    .status-active { color: #166534; }
    .status-pending::before { background: #f59e0b; }
    .status-pending { color: #b45309; }
    .status-expired::before { background: #ef4444; }
    .status-expired { color: #dc2626; }

    .btn-table-action {
        width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        background: white;
        color: #64748b;
        font-size: 0.82rem;
        text-decoration: none;
        transition: all 0.15s;
    }
    .btn-table-action:hover {
        background: #f1f5f9;
        color: #1e293b;
        border-color: #cbd5e1;
    }
    .btn-table-boost {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: white;
        border: none;
    }
    .btn-table-boost:hover {
        background: linear-gradient(135deg, #d97706, #b45309);
        color: white;
    }
    .btn-table-delete {
        background: #fef2f2;
        color: #ef4444;
        border-color: #fecaca;
    }
    .btn-table-delete:hover {
        background: #ef4444;
        color: white;
        border-color: #ef4444;
    }

    .empty-box {
        text-align: center;
        padding: 50px 20px;
    }
    .empty-box i {
        font-size: 3rem;
        color: #e2e8f0;
        margin-bottom: 16px;
    }
    .empty-box h5 {
        font-weight: 700;
        color: #475569;
        margin-bottom: 8px;
    }
    .empty-box p {
        color: #94a3b8;
        font-size: 0.9rem;
        margin-bottom: 20px;
    }

    /* Responsive */
    @media (max-width: 992px) {
        .top-row {
            grid-template-columns: repeat(2, 1fr);
        }
        .top-row .points-card {
            grid-column: span 2;
        }
        .actions-row {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 768px) {
        .dash-wrap { padding: 20px 14px 40px; }
        .dash-greeting { margin-bottom: 20px; }
        .dash-greeting h1 { font-size: 1.25rem; }
        .dash-greeting p { font-size: 0.82rem; }
        .btn-publish { padding: 8px 16px; font-size: 0.85rem; }
        .top-row { gap: 12px; margin-bottom: 20px; }
        .mini-stat { padding: 16px; gap: 10px; border-radius: 12px; }
        .mini-stat-icon { width: 38px; height: 38px; font-size: 1rem; border-radius: 10px; }
        .mini-stat-val { font-size: 1.3rem; }
        .mini-stat-label { font-size: 0.73rem; }
        .actions-row { gap: 12px; margin-bottom: 20px; }
        .action-card { padding: 16px 14px; gap: 10px; border-radius: 12px; }
        .action-icon { width: 40px; height: 40px; font-size: 1rem; border-radius: 10px; }
        .action-card h5 { font-size: 0.82rem; }
        .action-card p { font-size: 0.72rem; }
        .table-section { border-radius: 12px; }
        .table-section-head { padding: 14px 16px; }
        .table-section-head h3 { font-size: 0.92rem; }
        .points-card { padding: 14px 16px; border-radius: 12px; }
        .points-card-val { font-size: 1.4rem; }
    }

    @media (max-width: 576px) {
        .top-row {
            grid-template-columns: 1fr 1fr;
        }
        .actions-row {
            grid-template-columns: 1fr;
        }
        .dash-wrap { padding: 14px 10px 30px; }
        .dash-greeting h1 { font-size: 1.1rem; }
        .dash-greeting { gap: 8px; }
        .btn-publish { padding: 7px 14px; font-size: 0.82rem; gap: 6px; }
        .mini-stat { padding: 14px 12px; border-radius: 10px; }
        .mini-stat-icon { width: 34px; height: 34px; font-size: 0.9rem; }
        .mini-stat-val { font-size: 1.15rem; }
        .action-card { padding: 14px 12px; border-radius: 10px; }
        .action-icon { width: 36px; height: 36px; font-size: 0.9rem; }
        .table-section-head { padding: 12px 14px; }
        .dash-table thead th { font-size: 0.72rem; padding: 10px 8px; }
        .dash-table tbody td { font-size: 0.78rem; padding: 10px 8px; }
    }

    @media (max-width: 420px) {
        .dash-wrap { padding: 10px 8px 24px; }
        .top-row { gap: 8px; }
        .mini-stat { padding: 12px 10px; }
        .mini-stat-val { font-size: 1.05rem; }
        .points-card { padding: 12px; }
        .points-card-val { font-size: 1.2rem; }
        .points-card-actions a { font-size: 0.68rem; padding: 3px 8px; }
        .action-card h5 { font-size: 0.78rem; }
        .action-card p { font-size: 0.68rem; }
        .dash-table { font-size: 0.72rem; }
    }

    /* Transaction History Styles */
    .tx-type-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 0.78rem;
        font-weight: 600;
    }
    .tx-type-points {
        background: #fef3c7;
        color: #92400e;
    }
    .tx-type-subscription {
        background: #f3e8ff;
        color: #7c3aed;
    }
    .tx-type-boost {
        background: #fff7ed;
        color: #ea580c;
    }
    .tx-type-other {
        background: #f1f5f9;
        color: #64748b;
    }
    .tx-amount {
        font-weight: 700;
        color: #1e293b;
        font-size: 0.9rem;
    }
    .tx-points {
        font-weight: 700;
        font-size: 0.9rem;
    }
    .tx-points-positive {
        color: #16a34a;
    }
    .tx-points-negative {
        color: #ef4444;
    }

    /* Active Subscriptions / Purchases Section */
    .active-subs-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 16px;
        padding: 20px;
    }
    .sub-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 18px;
        transition: all 0.2s;
    }
    .sub-card:hover {
        border-color: #cbd5e1;
        box-shadow: 0 4px 14px rgba(0,0,0,0.06);
    }
    .sub-card-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 12px;
    }
    .sub-card-icon {
        width: 42px;
        height: 42px;
        border-radius: 11px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        flex-shrink: 0;
        color: white;
    }
    .sub-card-title {
        font-size: 0.88rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0 0 2px;
    }
    .sub-card-subtitle {
        font-size: 0.76rem;
        color: #94a3b8;
        margin: 0;
    }
    .sub-card-badge {
        margin-left: auto;
        font-size: 0.7rem;
        font-weight: 700;
        padding: 3px 10px;
        border-radius: 20px;
    }
    .sub-card-progress {
        margin-bottom: 8px;
    }
    .sub-progress-bar {
        height: 6px;
        background: #e2e8f0;
        border-radius: 3px;
        overflow: hidden;
        margin-bottom: 6px;
    }
    .sub-progress-fill {
        height: 100%;
        border-radius: 3px;
        transition: width 0.6s ease;
    }
    .sub-progress-text {
        display: flex;
        justify-content: space-between;
        font-size: 0.72rem;
        color: #94a3b8;
    }
    .sub-card-actions {
        display: flex;
        gap: 8px;
        margin-top: 10px;
    }
    .sub-card-actions a {
        font-size: 0.76rem;
        font-weight: 600;
        padding: 5px 12px;
        border-radius: 8px;
        text-decoration: none;
        transition: all 0.15s;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
</style>
@endpush

@section('content')
<div id="dashboardContent">
<div class="dash-wrap">
    @if(session('success'))
        <div class="alert alert-success d-flex align-items-center gap-2 mb-3" role="alert" style="border-radius: 12px; font-size: 0.9rem;">
            <i class="fas fa-check-circle"></i>
            {{ session('success') }}
        </div>
    @endif

    <!-- Greeting -->
    <div class="dash-greeting">
        <div>
            <h1>Bonjour, <span>{{ Auth::user()->name }}</span></h1>
            <p>Gérez vos annonces et votre activité sur ProxiPro</p>
        </div>
    </div>

    <!-- Stats + Points -->
    <div class="top-row">
        <div class="mini-stat" style="background: linear-gradient(135deg, #3b82f6, #2563eb); border: none;">
            <div class="mini-stat-icon" style="background: rgba(255,255,255,0.2); color: white;">
                <i class="fas fa-bullhorn"></i>
            </div>
            <div>
                <div class="mini-stat-val" style="color: white;">{{ $ads->count() }}</div>
                <div class="mini-stat-label" style="color: rgba(255,255,255,0.8);">Annonces</div>
            </div>
        </div>
        <div class="mini-stat" style="background: linear-gradient(135deg, #16a34a, #15803d); border: none;">
            <div class="mini-stat-icon" style="background: rgba(255,255,255,0.2); color: white;">
                <i class="fas fa-check-circle"></i>
            </div>
            <div>
                <div class="mini-stat-val" style="color: white;">{{ $ads->where('status', 'active')->count() }}</div>
                <div class="mini-stat-label" style="color: rgba(255,255,255,0.8);">Actives</div>
            </div>
        </div>
        <div class="mini-stat" style="background: linear-gradient(135deg, #f59e0b, #d97706); border: none;">
            <div class="mini-stat-icon" style="background: rgba(255,255,255,0.2); color: white;">
                <i class="fas fa-eye"></i>
            </div>
            <div>
                <div class="mini-stat-val" style="color: white;">{{ $ads->sum('views') ?? 0 }}</div>
                <div class="mini-stat-label" style="color: rgba(255,255,255,0.8);">Vues totales</div>
            </div>
        </div>
        <div class="mini-stat" style="background: linear-gradient(135deg, #ec4899, #db2777); border: none;">
            <div class="mini-stat-icon" style="background: rgba(255,255,255,0.2); color: white;">
                <i class="fas fa-envelope"></i>
            </div>
            <div>
                <div class="mini-stat-val" style="color: white;">{{ Auth::user()->unreadMessagesCount() ?? 0 }}</div>
                <div class="mini-stat-label" style="color: rgba(255,255,255,0.8);">Messages non lus</div>
            </div>
        </div>

        <!-- Points (compact) -->
        <div class="points-card">
            <div class="points-card-top">
                <a href="#points" onclick="dashboardNav('points'); return false;" style="color: #f59e0b; text-decoration: none;"><i class="fas fa-coins"></i></a>
                <div class="points-card-val">{{ Auth::user()->available_points ?? 0 }}</div>
            </div>
            <div class="points-card-label">Points disponibles</div>
            <div class="points-card-actions">
                <a href="{{ route('pricing.index') }}" class="pts-buy"><i class="fas fa-plus me-1"></i>Acheter</a>
                <a href="#transactions" onclick="dashboardNav('transactions'); return false;" class="pts-history"><i class="fas fa-history me-1"></i>Historique</a>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="actions-row">
        <a href="#my-ads" onclick="dashboardNav('my-ads'); return false;" class="action-card" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed); border: none;">
            <div class="action-icon" style="background: rgba(255,255,255,0.2); color: white;">
                <i class="fas fa-list-alt"></i>
            </div>
            <div>
                <h5 style="color: white;">Mes Annonces</h5>
                <p style="color: rgba(255,255,255,0.8);">Gérez vos annonces publiées</p>
            </div>
        </a>
        <a href="#profile-edit" onclick="dashboardNav('profile-edit'); return false;" class="action-card" style="background: linear-gradient(135deg, #3b82f6, #2563eb); border: none;">
            <div class="action-icon" style="background: rgba(255,255,255,0.2); color: white;">
                <i class="fas fa-user-edit"></i>
            </div>
            <div>
                <h5 style="color: white;">Mon Profil</h5>
                <p style="color: rgba(255,255,255,0.8);">Complétez votre profil</p>
            </div>
        </a>
        <a href="{{ route('verification.index') }}" class="action-card" style="background: linear-gradient(135deg, #16a34a, #15803d); border: none;">
            <div class="action-icon" style="background: rgba(255,255,255,0.2); color: white;">
                <i class="fas fa-shield-alt"></i>
            </div>
            <div>
                <h5 style="color: white;">Vérification</h5>
                <p style="color: rgba(255,255,255,0.8);">
                    @if(Auth::user()->identity_verified)
                        Identité vérifiée ✓
                    @else
                        Vérifiez votre identité
                    @endif
                </p>
            </div>
        </a>
        <a href="{{ route('contact.index') }}" class="action-card" style="background: linear-gradient(135deg, #f59e0b, #d97706); border: none;">
            <div class="action-icon" style="background: rgba(255,255,255,0.2); color: white;">
                <i class="fas fa-headset"></i>
            </div>
            <div>
                <h5 style="color: white;">Support</h5>
                <p style="color: rgba(255,255,255,0.8);">Contactez notre équipe</p>
            </div>
        </a>
    </div>

    <!-- Active Subscriptions & Purchases -->
    @if((isset($activeBoostedAds) && $activeBoostedAds->count() > 0) || (isset($activeUrgentAds) && $activeUrgentAds->count() > 0) || (isset($proSubscription) && $proSubscription))
    <div class="table-section" style="margin-bottom: 24px;">
        <div class="table-section-head">
            <h3><i class="fas fa-crown"></i>Mes abonnements & achats actifs</h3>
        </div>
        <div class="active-subs-grid">
            {{-- Pro Subscription --}}
            @if(isset($proSubscription) && $proSubscription && $proSubscription['is_active'])
            @php
                $planLabels = ['starter' => 'Starter', 'pro' => 'Pro', 'premium' => 'Premium', 'enterprise' => 'Entreprise'];
                $planLabel = $planLabels[$proSubscription['plan']] ?? ucfirst($proSubscription['plan']);
                $planColors = ['starter' => '#3b82f6', 'pro' => '#8b5cf6', 'premium' => '#f59e0b', 'enterprise' => '#1e293b'];
                $planColor = $planColors[$proSubscription['plan']] ?? '#7c3aed';
                $subEnd = $proSubscription['ends_at'];
                $subStart = $proSubscription['started_at'];
                if ($subEnd && $subStart) {
                    $totalDays = max(1, $subStart->diffInDays($subEnd));
                    $elapsedDays = $subStart->diffInDays(now());
                    $subProgress = min(100, round(($elapsedDays / $totalDays) * 100));
                    $daysLeft = max(0, (int) now()->diffInDays($subEnd, false));
                } else {
                    $subProgress = 0;
                    $daysLeft = null;
                }
            @endphp
            <div class="sub-card" style="border-left: 4px solid {{ $planColor }};">
                <div class="sub-card-header">
                    <div class="sub-card-icon" style="background: {{ $planColor }};">
                        <i class="fas fa-briefcase"></i>
                    </div>
                    <div>
                        <div class="sub-card-title">Abonnement {{ $planLabel }}</div>
                        <div class="sub-card-subtitle">
                            @if($subEnd)
                                Expire le {{ $subEnd->format('d/m/Y') }}
                            @else
                                Abonnement actif (sans limite)
                            @endif
                        </div>
                    </div>
                    <span class="sub-card-badge" style="background: {{ $planColor }}20; color: {{ $planColor }};">Actif</span>
                </div>
                @if($subEnd)
                <div class="sub-card-progress">
                    <div class="sub-progress-bar">
                        <div class="sub-progress-fill" style="width: {{ $subProgress }}%; background: {{ $planColor }};"></div>
                    </div>
                    <div class="sub-progress-text">
                        <span>{{ $daysLeft }} jour{{ $daysLeft > 1 ? 's' : '' }} restant{{ $daysLeft > 1 ? 's' : '' }}</span>
                        <span>{{ $subProgress }}% écoulé</span>
                    </div>
                </div>
                @endif
                <div class="sub-card-actions">
                    <a href="{{ route('pricing.index') }}" style="background: {{ $planColor }}15; color: {{ $planColor }};">
                        <i class="fas fa-sync-alt"></i> Renouveler
                    </a>
                </div>
            </div>
            @endif

            {{-- Active Boosts --}}
            @if(isset($activeBoostedAds))
            @foreach($activeBoostedAds as $bAd)
            @php
                $boostColors = ['boost_3' => '#3b82f6', 'boost_7' => '#10b981', 'boost_15' => '#f59e0b', 'boost_30' => '#8b5cf6'];
                $boostLabels = ['boost_3' => 'Boost 3j', 'boost_7' => 'Boost 7j', 'boost_15' => 'Boost 15j', 'boost_30' => 'Boost 30j'];
                $bColor = $boostColors[$bAd->boost_type] ?? '#3b82f6';
                $bLabel = $boostLabels[$bAd->boost_type] ?? 'Boost';
                $bDaysLeft = max(0, (int) now()->diffInDays($bAd->boost_end, false));
                $bDurations = ['boost_3' => 3, 'boost_7' => 7, 'boost_15' => 15, 'boost_30' => 30];
                $bTotal = $bDurations[$bAd->boost_type] ?? 7;
                $bElapsed = max(0, $bTotal - $bDaysLeft);
                $bProgress = min(100, round(($bElapsed / max(1, $bTotal)) * 100));
            @endphp
            <div class="sub-card" style="border-left: 4px solid {{ $bColor }};">
                <div class="sub-card-header">
                    <div class="sub-card-icon" style="background: {{ $bColor }};">
                        <i class="fas fa-rocket"></i>
                    </div>
                    <div>
                        <div class="sub-card-title">{{ $bLabel }} — {{ Str::limit($bAd->title, 25) }}</div>
                        <div class="sub-card-subtitle">Expire le {{ $bAd->boost_end->format('d/m/Y à H:i') }}</div>
                    </div>
                    <span class="sub-card-badge" style="background: {{ $bColor }}20; color: {{ $bColor }};">
                        {{ $bDaysLeft }}j
                    </span>
                </div>
                <div class="sub-card-progress">
                    <div class="sub-progress-bar">
                        <div class="sub-progress-fill" style="width: {{ $bProgress }}%; background: {{ $bColor }};"></div>
                    </div>
                    <div class="sub-progress-text">
                        <span>{{ $bDaysLeft }} jour{{ $bDaysLeft > 1 ? 's' : '' }} restant{{ $bDaysLeft > 1 ? 's' : '' }}</span>
                        <span>{{ $bProgress }}% écoulé</span>
                    </div>
                </div>
                <div class="sub-card-actions">
                    <a href="{{ route('ads.show', $bAd) }}" style="background: {{ $bColor }}15; color: {{ $bColor }};">
                        <i class="fas fa-eye"></i> Voir
                    </a>
                    <a href="{{ route('boost.show', $bAd) }}" style="background: {{ $bColor }}15; color: {{ $bColor }};">
                        <i class="fas fa-rocket"></i> Prolonger
                    </a>
                </div>
            </div>
            @endforeach
            @endif

            {{-- Active Urgent Publications --}}
            @if(isset($activeUrgentAds))
            @foreach($activeUrgentAds as $uAd)
            @php
                $uDaysLeft = $uAd->urgent_until ? max(0, (int) now()->diffInDays($uAd->urgent_until, false)) : null;
                $uProgress = $uDaysLeft !== null ? min(100, round(((7 - $uDaysLeft) / 7) * 100)) : 0;
            @endphp
            <div class="sub-card" style="border-left: 4px solid #dc2626;">
                <div class="sub-card-header">
                    <div class="sub-card-icon" style="background: #dc2626;">
                        <i class="fas fa-fire"></i>
                    </div>
                    <div>
                        <div class="sub-card-title">URGENT — {{ Str::limit($uAd->title, 25) }}</div>
                        <div class="sub-card-subtitle">
                            @if($uAd->urgent_until)
                                Expire le {{ $uAd->urgent_until->format('d/m/Y') }}
                            @else
                                Sans limite
                            @endif
                        </div>
                    </div>
                    <span class="sub-card-badge" style="background: #fef2f2; color: #dc2626;">
                        @if($uDaysLeft !== null)
                            {{ $uDaysLeft }}j
                        @else
                            Actif
                        @endif
                    </span>
                </div>
                @if($uDaysLeft !== null)
                <div class="sub-card-progress">
                    <div class="sub-progress-bar">
                        <div class="sub-progress-fill" style="width: {{ $uProgress }}%; background: #dc2626;"></div>
                    </div>
                    <div class="sub-progress-text">
                        <span>{{ $uDaysLeft }} jour{{ $uDaysLeft > 1 ? 's' : '' }} restant{{ $uDaysLeft > 1 ? 's' : '' }}</span>
                        <span>{{ $uProgress }}% écoulé</span>
                    </div>
                </div>
                @endif
                <div class="sub-card-actions">
                    <a href="{{ route('ads.show', $uAd) }}" style="background: #fef2f220; color: #dc2626; border: 1px solid #fecaca;">
                        <i class="fas fa-eye"></i> Voir
                    </a>
                </div>
            </div>
            @endforeach
            @endif
        </div>
    </div>
    @endif

    <!-- Ads Table -->
    <div class="table-section">
        <div class="table-section-head">
            <h3><i class="fas fa-bullhorn"></i>Mes annonces récentes</h3>
            <a href="#my-ads" onclick="dashboardNav('my-ads'); return false;" class="btn btn-outline-secondary btn-sm" style="border-radius: 8px; font-size: 0.8rem;">
                Voir tout
            </a>
        </div>

        @if($ads->isEmpty())
        <div class="empty-box">
            <i class="fas fa-bullhorn"></i>
            <h5>Aucune annonce pour le moment</h5>
            <p>Publiez votre première annonce et commencez à proposer vos services.<br>Utilisez le bouton <strong>"Publier une offre"</strong> dans le menu pour commencer.</p>
        </div>
        @else
        <div class="table-responsive">
            <table class="dash-table">
                <thead>
                    <tr>
                        <th>Titre</th>
                        <th>Catégorie</th>
                        <th>Statut</th>
                        <th>Vues</th>
                        <th>Date</th>
                        <th style="text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($ads->take(5) as $ad)
                    <tr>
                        <td>
                            <a href="{{ route('ads.show', $ad->id) }}" class="ad-title-link">
                                {{ Str::limit($ad->title, 40) }}
                            </a>
                        </td>
                        <td><span class="cat-badge">{{ $ad->category }}</span></td>
                        <td>
                            <span class="status-dot status-{{ $ad->status == 'active' ? 'active' : ($ad->status == 'pending' ? 'pending' : 'expired') }}">
                                {{ $ad->status == 'active' ? 'Active' : ($ad->status == 'pending' ? 'En attente' : 'Expirée') }}
                            </span>
                        </td>
                        <td style="color: #94a3b8;">
                            <i class="fas fa-eye me-1" style="font-size: 0.75rem;"></i>{{ $ad->views ?? 0 }}
                        </td>
                        <td style="color: #94a3b8; font-size: 0.84rem;">{{ $ad->created_at->format('d/m/Y') }}</td>
                        <td style="text-align: center;">
                            <div class="d-flex justify-content-center gap-4">
                                <a href="{{ route('ads.show', $ad->id) }}" class="btn-table-action" title="Voir">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('ads.edit', $ad->id) }}" class="btn-table-action" title="Modifier">
                                    <i class="fas fa-pen"></i>
                                </a>
                                <form action="{{ route('ads.destroy', $ad->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette annonce ?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-table-action btn-table-delete" title="Supprimer">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                                <a href="{{ route('boost.show', $ad->id) }}" class="btn-table-action btn-table-boost" title="Booster">
                                    <i class="fas fa-rocket"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    <!-- Transaction History Section -->
    <div class="table-section" id="transactions-history" style="margin-top: 24px;">
        <div class="table-section-head">
            <h3><i class="fas fa-receipt"></i>Historique des transactions</h3>
            <a href="{{ route('home.export-transactions-pdf') }}" class="btn btn-sm" style="border-radius: 8px; font-size: 0.8rem; background: linear-gradient(135deg, #ef4444, #dc2626); color: white; display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px;">
                <i class="fas fa-file-pdf"></i>Exporter PDF
            </a>
        </div>

        @if($transactions->isEmpty() && $pointTransactions->isEmpty())
        <div class="empty-box">
            <i class="fas fa-receipt"></i>
            <h5>Aucune transaction pour le moment</h5>
            <p>Vos achats de points, paiements et transactions apparaîtront ici.</p>
        </div>
        @else
        <div class="table-responsive">
            <table class="dash-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Description</th>
                        <th>Montant / Points</th>
                        <th>Statut</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transactions as $tx)
                    <tr>
                        <td style="color: #94a3b8; font-size: 0.84rem;">{{ $tx->created_at->format('d/m/Y H:i') }}</td>
                        <td>
                            <span class="tx-type-badge tx-type-{{ strtolower($tx->type ?? 'other') }}">
                                @if($tx->type === 'POINTS')
                                    <i class="fas fa-coins"></i> Achat de points
                                @elseif($tx->type === 'SUBSCRIPTION')
                                    <i class="fas fa-crown"></i> Abonnement
                                @elseif($tx->type === 'BOOST')
                                    <i class="fas fa-rocket"></i> Boost
                                @else
                                    <i class="fas fa-credit-card"></i> {{ $tx->type ?? 'Paiement' }}
                                @endif
                            </span>
                        </td>
                        <td style="font-size: 0.88rem; color: #334155;">{{ Str::limit($tx->description ?? '-', 50) }}</td>
                        <td>
                            <span class="tx-amount">{{ number_format($tx->amount, 0, ',', ' ') }} €</span>
                        </td>
                        <td>
                            <span class="status-dot status-{{ $tx->status == 'completed' ? 'active' : ($tx->status == 'pending' ? 'pending' : 'expired') }}">
                                {{ $tx->status == 'completed' ? 'Complété' : ($tx->status == 'pending' ? 'En attente' : ucfirst($tx->status ?? 'Inconnu')) }}
                            </span>
                        </td>
                        <td>
                            @if($tx->status === 'completed')
                                <a href="{{ route('purchase.invoice', ['type' => 'points', 'id' => $tx->id]) }}" class="btn btn-sm btn-outline-secondary" style="font-size: 0.7rem; padding: 2px 8px; border-radius: 6px;" title="Télécharger la facture">
                                    <i class="fas fa-file-pdf"></i>
                                </a>
                            @endif
                        </td>
                    </tr>
                    @endforeach

                    @foreach($pointTransactions as $ptx)
                    <tr>
                        <td style="color: #94a3b8; font-size: 0.84rem;">{{ $ptx->created_at->format('d/m/Y H:i') }}</td>
                        <td>
                            <span class="tx-type-badge tx-type-points">
                                <i class="fas fa-coins"></i> Points
                            </span>
                        </td>
                        <td style="font-size: 0.88rem; color: #334155;">{{ Str::limit($ptx->description ?? '-', 50) }}</td>
                        <td>
                            <span class="tx-points {{ $ptx->points >= 0 ? 'tx-points-positive' : 'tx-points-negative' }}">
                                {{ $ptx->points >= 0 ? '+' : '' }}{{ $ptx->points }} pts
                            </span>
                        </td>
                        <td>
                            <span class="status-dot status-active">Complété</span>
                        </td>
                        <td></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>
</div>
@endsection
