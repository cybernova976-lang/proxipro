@extends('layouts.app')

@section('title', 'Boutique de Points - ProxiPro')

@push('styles')
<style>
/* ========== BASE ========== */
.pts-page { background: #f8fafc; min-height: 100vh; padding-bottom: 80px; }

/* ========== HERO ========== */
.pts-header {
    padding: 30px 0 10px;
    text-align: center;
}
.pts-header .breadcrumb {
    margin-bottom: 8px;
    font-size: 0.85rem;
}
.pts-header .breadcrumb a {
    color: var(--primary, #2563eb);
    text-decoration: none;
}
.pts-header h1 {
    font-size: 1.75rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 4px;
    display: inline-flex;
    align-items: center;
    gap: 10px;
}
.pts-header h1 i {
    color: #f59e0b;
    font-size: 1.4rem;
}
.pts-header p {
    font-size: 0.95rem;
    color: #64748b;
    margin-bottom: 0;
}
.pts-balance-card {
    display: inline-flex; align-items: center; gap: 14px;
    background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 60%, #2563eb 100%);
    color: white;
    border-radius: 60px;
    padding: 12px 28px 12px 14px;
    margin-top: 14px;
}
.pts-balance-coin {
    width: 40px; height: 40px; border-radius: 50%;
    background: linear-gradient(135deg, #f59e0b, #ef4444);
    display: flex; align-items: center; justify-content: center;
    color: white; font-size: 1rem;
    box-shadow: 0 4px 16px rgba(245,158,11,0.3);
}
.pts-balance-info { text-align: left; }
.pts-balance-label { color: rgba(255,255,255,0.6); font-size: 0.72rem; text-transform: uppercase; letter-spacing: 1px; font-weight: 600; }
.pts-balance-amount { color: white; font-size: 1.4rem; font-weight: 800; line-height: 1.2; }

/* ========== CONFIRMATION BANNER ========== */
.pts-confirm-banner {
    background: linear-gradient(135deg, #059669, #10b981);
    border-radius: 20px; padding: 24px 28px;
    margin-top: -40px; position: relative; z-index: 10;
    box-shadow: 0 12px 40px rgba(16,185,129,0.3);
    animation: ptsBannerIn 0.6s cubic-bezier(0.16,1,0.3,1);
}
@keyframes ptsBannerIn { from { opacity: 0; transform: translateY(-24px) scale(0.96); } to { opacity: 1; transform: translateY(0) scale(1); } }
.pts-confirm-inner { display: flex; align-items: center; gap: 18px; }
.pts-confirm-icon {
    width: 56px; height: 56px; border-radius: 50%;
    background: rgba(255,255,255,0.2);
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; font-size: 1.5rem; color: white;
}
.pts-confirm-text { flex-grow: 1; }
.pts-confirm-text h5 { color: white; font-weight: 700; margin-bottom: 3px; font-size: 1.05rem; }
.pts-confirm-text p { color: rgba(255,255,255,0.85); margin: 0; font-size: 0.88rem; }
.pts-confirm-close {
    background: rgba(255,255,255,0.2); border: none; color: white;
    width: 34px; height: 34px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; flex-shrink: 0; transition: background 0.2s;
}
.pts-confirm-close:hover { background: rgba(255,255,255,0.35); }

/* ========== NAV TABS ========== */
.pts-nav {
    display: flex; justify-content: center; gap: 4px;
    margin: 36px auto 40px;
    background: white; border-radius: 16px; padding: 5px;
    max-width: 360px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}
.pts-nav-btn {
    flex: 1; padding: 12px 20px; border-radius: 12px;
    border: none; background: transparent;
    color: #94a3b8; font-weight: 600; font-size: 0.88rem;
    cursor: pointer; transition: all 0.3s ease;
}
.pts-nav-btn:hover { color: #1e293b; }
.pts-nav-btn.active {
    background: linear-gradient(135deg, #0f172a, #1e3a5f);
    color: white;
    box-shadow: 0 4px 16px rgba(15,23,42,0.25);
}

/* ========== SECTION HEADERS ========== */
.pts-section-head { text-align: center; margin-bottom: 24px; }
.pts-section-head h3 { font-size: 1.2rem; font-weight: 800; color: #0f172a; margin-bottom: 4px; }
.pts-section-head p { color: #64748b; font-size: 0.85rem; margin: 0; }

/* ========== PACK GRID ========== */
.pts-pack {
    background: white; border-radius: 14px;
    border: 2px solid #e2e8f0;
    padding: 0; text-align: center;
    transition: all 0.35s cubic-bezier(0.16,1,0.3,1);
    position: relative; height: auto;
    display: flex; flex-direction: column;
    overflow: hidden;
}
.pts-pack:hover {
    transform: translateY(-4px);
    border-color: #3b82f6;
    box-shadow: 0 12px 36px rgba(59,130,246,0.12);
}
.pts-pack.pts-featured {
    border-color: #3b82f6;
    box-shadow: 0 4px 20px rgba(59,130,246,0.1);
}
.pts-pack.pts-featured .pts-pack-head { background: linear-gradient(135deg, #1e3a5f, #2563eb); }
.pts-pack.pts-featured .pts-pack-head * { color: white !important; }

.pts-pack-ribbon {
    position: absolute; top: 10px; right: -28px;
    padding: 3px 36px; font-size: 0.6rem;
    font-weight: 700; letter-spacing: 1px;
    text-transform: uppercase; color: white;
    transform: rotate(45deg); z-index: 3;
}
.pts-pack-ribbon.popular { background: linear-gradient(135deg, #f59e0b, #f97316); }
.pts-pack-ribbon.best { background: linear-gradient(135deg, #059669, #10b981); }

.pts-pack-head {
    padding: 12px 14px 10px;
    background: linear-gradient(180deg, #f8fafc, #ffffff);
    border-bottom: 1px solid #f1f5f9;
}
.pts-pack-emoji { font-size: 1.3rem; margin-bottom: 2px; line-height: 1; }
.pts-pack-pts { font-size: 1.5rem; font-weight: 900; color: #0f172a; line-height: 1; }
.pts-pack-pts-label { color: #94a3b8; font-size: 0.65rem; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; margin-top: 1px; }

.pts-pack-body { padding: 10px 14px 14px; flex-grow: 1; display: flex; flex-direction: column; }
.pts-pack-desc { color: #64748b; font-size: 0.75rem; margin-bottom: 3px; min-height: auto; }
.pts-pack-unit { color: #94a3b8; font-size: 0.68rem; margin-bottom: 6px; }
.pts-pack-price {
    font-size: 1.2rem; font-weight: 900; color: #0f172a;
    margin-bottom: 2px; line-height: 1;
}
.pts-pack-price small { font-size: 0.5em; font-weight: 500; color: #94a3b8; vertical-align: middle; }
.pts-pack-divider { height: 1px; background: #f1f5f9; margin: 6px 0; }

.btn-pts-buy {
    width: 100%; padding: 8px 14px;
    border-radius: 10px;
    border: none;
    background: linear-gradient(135deg, #0f172a, #1e3a5f);
    color: white; font-weight: 700;
    font-size: 0.78rem;
    cursor: pointer; transition: all 0.3s ease;
    margin-top: auto;
    display: flex; align-items: center; justify-content: center; gap: 6px;
}
.btn-pts-buy:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(15,23,42,0.3);
}
.btn-pts-buy:disabled { opacity: 0.5; cursor: not-allowed; transform: none; box-shadow: none; }
.pts-featured .btn-pts-buy {
    background: linear-gradient(135deg, #f59e0b, #f97316);
    box-shadow: 0 6px 20px rgba(245,158,11,0.3);
}
.pts-featured .btn-pts-buy:hover {
    box-shadow: 0 10px 32px rgba(245,158,11,0.4);
}

/* ========== SERVICES TABLE ========== */
.pts-table-wrap {
    background: white; border-radius: 16px;
    overflow: hidden; margin-top: 32px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}
.pts-table-head {
    background: linear-gradient(135deg, #0f172a, #1e3a5f);
    color: white; padding: 14px 22px;
    font-weight: 700; font-size: 0.9rem;
    display: flex; align-items: center; gap: 8px;
}
.pts-table-wrap table { width: 100%; border-collapse: collapse; }
.pts-table-wrap th {
    background: #f8fafc; color: #64748b;
    padding: 10px 18px; font-weight: 600;
    font-size: 0.72rem; text-transform: uppercase;
    letter-spacing: 0.8px; text-align: left;
}
.pts-table-wrap td {
    padding: 12px 18px; border-bottom: 1px solid #f1f5f9;
    font-size: 0.82rem; color: #334155;
}
.pts-table-wrap tr:last-child td { border-bottom: none; }
.pts-table-wrap tbody tr:hover td { background: #fafbfd; }
.pts-tag {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 4px 12px; border-radius: 8px;
    font-weight: 600; font-size: 0.82rem;
}
.pts-tag.green { background: rgba(16,185,129,0.1); color: #059669; }
.pts-tag.blue { background: rgba(59,130,246,0.1); color: #2563eb; }
.pts-tag.orange { background: rgba(245,158,11,0.1); color: #d97706; }
.pts-tag.free { background: #059669; color: white; padding: 5px 14px; border-radius: 8px; font-size: 0.78rem; }

/* ========== STEPS ========== */
.pts-steps { display: grid; grid-template-columns: repeat(4,1fr); gap: 12px; margin-top: 32px; }
.pts-step {
    background: white; border-radius: 14px;
    padding: 22px 14px; text-align: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    position: relative;
    transition: transform 0.3s;
}
.pts-step:hover { transform: translateY(-3px); }
.pts-step-num {
    position: absolute; top: -10px; left: 50%; transform: translateX(-50%);
    width: 24px; height: 24px; border-radius: 50%;
    background: linear-gradient(135deg, #0f172a, #1e3a5f);
    color: white; display: flex; align-items: center; justify-content: center;
    font-weight: 800; font-size: 0.65rem;
}
.pts-step-icon {
    width: 40px; height: 40px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    margin: 4px auto 10px; font-size: 1rem;
}
.pts-step h6 { font-weight: 700; color: #0f172a; margin-bottom: 3px; font-size: 0.8rem; }
.pts-step p { color: #64748b; font-size: 0.72rem; margin: 0; line-height: 1.3; }

/* ========== FREE SECTION ========== */
.pts-free-wrap {
    background: linear-gradient(135deg, #0f172a, #1e293b);
    border-radius: 20px; padding: 32px;
    position: relative; overflow: hidden;
}
.pts-free-wrap::before {
    content: ''; position: absolute; top: -80px; right: -60px;
    width: 250px; height: 250px; border-radius: 50%;
    background: radial-gradient(circle, rgba(251,191,36,0.08), transparent 70%);
}
.pts-social-card {
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 16px; padding: 24px 16px;
    text-align: center; transition: all 0.35s;
}
.pts-social-card:hover:not(.claimed) { background: rgba(255,255,255,0.1); transform: translateY(-6px); }
.pts-social-card.claimed { opacity: 0.45; }
.pts-social-emoji { font-size: 2.4rem; margin-bottom: 10px; }
.pts-social-card h6 { color: white; font-weight: 600; margin-bottom: 10px; font-size: 0.92rem; }
.pts-social-reward {
    display: inline-block; background: rgba(251,191,36,0.15);
    color: #fbbf24; padding: 5px 14px; border-radius: 20px;
    font-weight: 700; font-size: 0.82rem; margin-bottom: 16px;
}
.btn-pts-share {
    width: 100%; padding: 11px 16px; border-radius: 10px;
    border: 1px solid rgba(255,255,255,0.15);
    background: rgba(255,255,255,0.06);
    color: rgba(255,255,255,0.9); font-weight: 600;
    font-size: 0.85rem; cursor: pointer;
    transition: all 0.25s;
}
.btn-pts-share:hover:not(:disabled) { background: rgba(255,255,255,0.15); color: white; }
.btn-pts-share:disabled { background: rgba(16,185,129,0.2); border-color: transparent; color: #10b981; cursor: default; }

.pts-earn-card {
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.06);
    border-radius: 14px; padding: 22px 16px;
    text-align: center; transition: transform 0.3s;
}
.pts-earn-card:hover { transform: translateY(-4px); }
.pts-earn-card i { font-size: 1.6rem; margin-bottom: 10px; }
.pts-earn-card h6 { color: white; font-weight: 700; font-size: 0.88rem; margin-bottom: 4px; }
.pts-earn-card p { color: rgba(255,255,255,0.45); font-size: 0.78rem; margin: 0; }

/* ========== GUARANTEES ========== */
.pts-guarantees {
    display: flex; justify-content: center; gap: 24px;
    margin-top: 28px; flex-wrap: wrap;
}
.pts-guarantee {
    display: flex; align-items: center; gap: 8px;
    color: #64748b; font-size: 0.78rem; font-weight: 500;
}
.pts-guarantee i { color: #10b981; font-size: 0.9rem; }

/* ========== SHARE MODAL ========== */
.share-modal .modal-content { border-radius: 24px; border: none; overflow: hidden; }
.share-modal .modal-body { padding: 0; }
.share-step { text-align: center; padding: 48px 28px; }
.share-step-icon {
    width: 88px; height: 88px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 24px; font-size: 2.6rem;
}
.countdown-ring {
    width: 100px; height: 100px; border-radius: 50%;
    background: linear-gradient(135deg, #0f172a, #2563eb);
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 24px;
    font-size: 2.6rem; font-weight: 900; color: white;
    animation: cntPulse 1.5s ease-in-out infinite;
    box-shadow: 0 8px 32px rgba(37,99,235,0.3);
}
@keyframes cntPulse { 0%,100% { transform: scale(1); } 50% { transform: scale(1.06); } }

/* ========== TOAST ========== */
.pts-toast {
    position: fixed; top: 90px; right: 20px;
    padding: 16px 24px; border-radius: 14px; color: white;
    font-weight: 600; z-index: 9999; display: none;
    box-shadow: 0 12px 40px rgba(0,0,0,0.2);
    animation: ptsSlideIn 0.35s cubic-bezier(0.16,1,0.3,1);
    backdrop-filter: blur(12px);
}
.pts-toast.success { background: linear-gradient(135deg, #059669, #10b981); }
.pts-toast.error { background: linear-gradient(135deg, #dc2626, #ef4444); }
@keyframes ptsSlideIn { from { transform: translateX(120px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }

/* ========== TAB CONTENT ========== */
.pts-tab-content { display: none; }
.pts-tab-content.active { display: block; animation: ptsFadeIn 0.4s ease; }
@keyframes ptsFadeIn { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }

/* ========== RESPONSIVE ========== */
@media (max-width: 991px) {
    .pts-steps { grid-template-columns: repeat(2,1fr); }
}
@media (max-width: 767px) {
    .pts-header h1 { font-size: 1.5rem; }
    .pts-free-wrap { padding: 28px 18px; }
    .pts-confirm-inner { flex-direction: column; text-align: center; }
    .pts-confirm-close { margin: 0 auto; }
    .pts-guarantees { flex-direction: column; align-items: center; gap: 12px; }
}
@media (max-width: 575px) {
    .pts-steps { grid-template-columns: 1fr 1fr; gap: 10px; }
    .pts-step { padding: 20px 12px; }
}
</style>
@endpush

@section('content')
<div class="pts-page">
    <div id="pts-toast" class="pts-toast"></div>

    {{-- ===== HEADER ===== --}}
    <div class="container">
        <div class="pts-header">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb justify-content-center">
                    <li class="breadcrumb-item"><a href="{{ url('/') }}"><i class="fas fa-home"></i> Accueil</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('pricing.index') }}">Tarifs</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Boutique de Points</li>
                </ol>
            </nav>
            <h1><i class="fas fa-coins"></i> Boutique de Points</h1>
            <p>Achetez des points pour booster vos annonces et gagner en visibilité. La publication reste <strong>100% gratuite</strong>.</p>
            <div class="pts-balance-card">
                <div class="pts-balance-coin"><i class="fas fa-coins"></i></div>
                <div class="pts-balance-info">
                    <div class="pts-balance-label">Votre solde</div>
                    <div class="pts-balance-amount" id="user-points">{{ number_format($userPoints ?? 0, 0, ',', ' ') }} pts</div>
                </div>
            </div>
        </div>
        <hr class="mb-4">
    </div>

    <div class="container">

        {{-- ===== SUCCESS BANNER ===== --}}
        @if(session('success'))
        <div class="pts-confirm-banner mb-4" id="successBanner">
            <div class="pts-confirm-inner">
                <div class="pts-confirm-icon"><i class="fas fa-check-circle"></i></div>
                <div class="pts-confirm-text">
                    <h5>{{ session('success') }}</h5>
                    <p>Votre nouveau solde est de <strong>{{ number_format($userPoints ?? 0, 0, ',', ' ') }} points</strong>. Utilisez-les pour booster vos annonces !</p>
                </div>
                <button class="pts-confirm-close" onclick="document.getElementById('successBanner').style.display='none'">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        @endif

        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-radius: 14px; margin-bottom: 20px; border: none; box-shadow: 0 2px 8px rgba(239,68,68,0.15);">
            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        {{-- ===== TAB NAV ===== --}}
        <div class="pts-nav">
            <button class="pts-nav-btn active" data-tab="buy"><i class="fas fa-shopping-bag me-1"></i> Acheter</button>
            <button class="pts-nav-btn" data-tab="free"><i class="fas fa-gift me-1"></i> Gratuit</button>
        </div>

        {{-- ===================== BUY POINTS ===================== --}}
        <div id="tab-buy" class="pts-tab-content active">
            <div class="pts-section-head">
                <h3>Choisissez votre pack</h3>
                <p>Paiement unique et sécurisé — pas d'abonnement, pas de surprises</p>
            </div>

            <div class="row g-3">
                @php
                    $packs = [
                        ['key' => 'POINTS_5',   'pts' => 5,   'price' => 4,  'desc' => 'Boost 3 jours',          'emoji' => '⚡', 'badge' => null],
                        ['key' => 'POINTS_10',  'pts' => 10,  'price' => 6,  'desc' => 'Boost 7 jours',          'emoji' => '🚀', 'badge' => 'popular'],
                        ['key' => 'POINTS_20',  'pts' => 20,  'price' => 10, 'desc' => 'Boost 15 jours',         'emoji' => '⭐', 'badge' => null],
                        ['key' => 'POINTS_30',  'pts' => 30,  'price' => 15, 'desc' => 'Boost 30 jours',         'emoji' => '👑', 'badge' => null],
                        ['key' => 'POINTS_50',  'pts' => 50,  'price' => 22, 'desc' => 'Boost + Vérification',   'emoji' => '🛡️', 'badge' => null],
                        ['key' => 'POINTS_100', 'pts' => 100, 'price' => 40, 'desc' => 'Utilisation intensive',  'emoji' => '💎', 'badge' => 'best'],
                    ];
                @endphp

                @foreach($packs as $pack)
                <div class="col-6 col-lg-4">
                    <div class="pts-pack {{ $pack['badge'] === 'popular' ? 'pts-featured' : '' }}">
                        @if($pack['badge'] === 'popular')
                            <div class="pts-pack-ribbon popular">Populaire</div>
                        @elseif($pack['badge'] === 'best')
                            <div class="pts-pack-ribbon best">Meilleur prix</div>
                        @endif

                        <div class="pts-pack-head">
                            <div class="pts-pack-emoji">{{ $pack['emoji'] }}</div>
                            <div class="pts-pack-pts">{{ $pack['pts'] }}</div>
                            <div class="pts-pack-pts-label">points</div>
                        </div>

                        <div class="pts-pack-body">
                            <div class="pts-pack-desc">{{ $pack['desc'] }}</div>
                            <div class="pts-pack-price">{{ number_format($pack['price'], 2, ',', ' ') }}€ <small>TTC</small></div>
                            <div class="pts-pack-unit">soit {{ number_format($pack['price'] / $pack['pts'], 2, ',', ' ') }}€ / point</div>
                            <div class="pts-pack-divider"></div>
                            <button class="btn-pts-buy" onclick="purchasePoints('{{ $pack['key'] }}', this)">
                                <i class="fas fa-lock"></i> Acheter
                            </button>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            {{-- Guarantees --}}
            <div class="pts-guarantees">
                <div class="pts-guarantee"><i class="fas fa-shield-alt"></i> Paiement sécurisé Stripe</div>
                <div class="pts-guarantee"><i class="fas fa-bolt"></i> Points crédités instantanément</div>
                <div class="pts-guarantee"><i class="fas fa-undo"></i> Satisfait ou remboursé</div>
            </div>

            {{-- Services Table --}}
            <div class="pts-table-wrap">
                <div class="pts-table-head">
                    <i class="fas fa-list-ul"></i> Tarifs des services
                </div>
                <table>
                    <thead>
                        <tr><th>Service</th><th>Prix €</th><th>Prix Points</th></tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><i class="fas fa-plus-circle text-success me-2"></i><strong>Publier une annonce</strong></td>
                            <td colspan="2"><span class="pts-tag free">GRATUIT</span></td>
                        </tr>
                        <tr>
                            <td><i class="fas fa-bolt text-primary me-2"></i>Boost 3 jours</td>
                            <td>4,00 €</td>
                            <td><span class="pts-tag green"><i class="fas fa-coins me-1"></i>5 pts</span></td>
                        </tr>
                        <tr>
                            <td><i class="fas fa-rocket text-warning me-2"></i>Boost 7 jours</td>
                            <td>6,00 €</td>
                            <td><span class="pts-tag green"><i class="fas fa-coins me-1"></i>10 pts</span></td>
                        </tr>
                        <tr>
                            <td><i class="fas fa-star text-warning me-2"></i>Boost 15 jours</td>
                            <td>10,00 €</td>
                            <td><span class="pts-tag green"><i class="fas fa-coins me-1"></i>20 pts</span></td>
                        </tr>
                        <tr>
                            <td><i class="fas fa-crown text-danger me-2"></i>Boost 30 jours</td>
                            <td>15,00 €</td>
                            <td><span class="pts-tag green"><i class="fas fa-coins me-1"></i>30 pts</span></td>
                        </tr>
                        <tr>
                            <td><i class="fas fa-sync text-info me-2"></i>Rafraîchir une annonce</td>
                            <td>—</td>
                            <td><span class="pts-tag orange"><i class="fas fa-coins me-1"></i>10 pts</span></td>
                        </tr>
                        <tr>
                            <td><i class="fas fa-user-check text-primary me-2"></i>Vérification de profil</td>
                            <td>10,00 €</td>
                            <td><span class="pts-tag blue"><i class="fas fa-coins me-1"></i>20 pts</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- How it works --}}
            <div class="pts-section-head mt-4">
                <h3>Comment ça marche ?</h3>
                <p>4 étapes simples pour booster vos annonces</p>
            </div>
            <div class="pts-steps">
                <div class="pts-step">
                    <div class="pts-step-num">1</div>
                    <div class="pts-step-icon" style="background: rgba(59,130,246,0.1); color: #3b82f6;"><i class="fas fa-coins"></i></div>
                    <h6>Achetez des points</h6>
                    <p>Choisissez le pack adapté à vos besoins</p>
                </div>
                <div class="pts-step">
                    <div class="pts-step-num">2</div>
                    <div class="pts-step-icon" style="background: rgba(16,185,129,0.1); color: #10b981;"><i class="fas fa-credit-card"></i></div>
                    <h6>Paiement sécurisé</h6>
                    <p>Par carte bancaire via Stripe</p>
                </div>
                <div class="pts-step">
                    <div class="pts-step-num">3</div>
                    <div class="pts-step-icon" style="background: rgba(245,158,11,0.1); color: #f59e0b;"><i class="fas fa-rocket"></i></div>
                    <h6>Boostez vos annonces</h6>
                    <p>Utilisez vos points quand vous voulez</p>
                </div>
                <div class="pts-step">
                    <div class="pts-step-num">4</div>
                    <div class="pts-step-icon" style="background: rgba(168,85,247,0.1); color: #a855f7;"><i class="fas fa-chart-line"></i></div>
                    <h6>Plus de visibilité</h6>
                    <p>Apparaissez en haut du fil d'actualité</p>
                </div>
            </div>
        </div>

        {{-- ===================== FREE POINTS ===================== --}}
        <div id="tab-free" class="pts-tab-content">
            <div class="pts-free-wrap">
                <div class="text-center mb-4" style="position: relative; z-index: 2;">
                    <h3 class="text-white fw-bold mb-2" style="font-size: 1.5rem;">
                        <i class="fas fa-gift me-2" style="color: #fbbf24;"></i>Points gratuits
                    </h3>
                    <p class="mb-3" style="color: rgba(255,255,255,0.55); font-size: 0.92rem;">
                        Partagez ProxiPro sur les réseaux sociaux et recevez <strong class="text-warning">5 points</strong> par réseau.
                    </p>
                    <div class="d-inline-block px-4 py-2 rounded-pill" style="background: rgba(251,191,36,0.12); border: 1px solid rgba(251,191,36,0.2);">
                        <span class="text-warning fw-bold" id="total-social-earned">0 / 30 points réclamés</span>
                    </div>
                </div>

                <div class="row g-3" id="social-platforms">
                    <div class="col-12 text-center py-4"><div class="spinner-border text-light spinner-border-sm" role="status"></div></div>
                </div>

                <hr style="border-color: rgba(255,255,255,0.08); margin: 36px 0;">

                <h5 class="text-white fw-bold mb-3" style="font-size: 1.1rem;">
                    <i class="fas fa-coins text-warning me-2"></i>Autres façons de gagner
                </h5>
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="pts-earn-card">
                            <i class="fas fa-user-plus text-primary"></i>
                            <h6>Inscription</h6>
                            <p>5 points offerts à l'inscription</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="pts-earn-card">
                            <i class="fas fa-share-alt text-success"></i>
                            <h6>Partage réseaux</h6>
                            <p>5 pts / réseau (1 fois)</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="pts-earn-card">
                            <i class="fas fa-star text-warning"></i>
                            <h6>Engagement</h6>
                            <p>Points bonus en interagissant</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ===== SHARE MODAL ===== --}}
<div class="modal fade share-modal" id="shareModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body" id="share-modal-content"></div>
        </div>
    </div>
</div>

{{-- ===== PURCHASE CONFIRMATION MODAL ===== --}}
<div class="modal fade" id="purchaseConfirmModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 24px; border: none; overflow: hidden;">
            <div class="modal-body p-0">
                <div style="background: linear-gradient(135deg, #059669, #10b981); padding: 40px 28px; text-align: center;">
                    <div style="width: 80px; height: 80px; border-radius: 50%; background: rgba(255,255,255,0.2); display: inline-flex; align-items: center; justify-content: center; margin-bottom: 16px;">
                        <i class="fas fa-check-circle" style="font-size: 2.4rem; color: white;"></i>
                    </div>
                    <h3 class="text-white fw-bold mb-2">Achat réussi !</h3>
                    <p class="mb-0" style="color: rgba(255,255,255,0.85); font-size: 0.95rem;" id="confirmMsg"></p>
                </div>
                <div style="padding: 28px; text-align: center; background: white;">
                    <div class="d-flex justify-content-center gap-4 mb-3">
                        <div>
                            <div style="font-size: 0.75rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">Points ajoutés</div>
                            <div style="font-size: 1.8rem; font-weight: 900; color: #059669;" id="confirmPts">+0</div>
                        </div>
                        <div style="width: 1px; background: #e2e8f0;"></div>
                        <div>
                            <div style="font-size: 0.75rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">Nouveau solde</div>
                            <div style="font-size: 1.8rem; font-weight: 900; color: #0f172a;" id="confirmBalance">0</div>
                        </div>
                    </div>
                    <p style="color: #64748b; font-size: 0.85rem; margin-bottom: 20px;">Utilisez vos points pour booster vos annonces et gagner en visibilité.</p>
                    <div class="d-flex gap-3 justify-content-center">
                        <a href="/" class="btn btn-outline-secondary px-4" style="border-radius: 12px; font-weight: 600;">
                            <i class="fas fa-home me-1"></i> Accueil
                        </a>
                        <button class="btn px-4" style="border-radius: 12px; font-weight: 600; background: linear-gradient(135deg, #0f172a, #1e3a5f); color: white;" data-bs-dismiss="modal">
                            <i class="fas fa-coins me-1"></i> Continuer
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
let shareModal, currentPlatform = null, countdownInterval = null;

const socialPlatforms = [
    { key: 'facebook',  name: 'Facebook',  icon: '📘', points: 5 },
    { key: 'twitter',   name: 'Twitter/X', icon: '🐦', points: 5 },
    { key: 'instagram', name: 'Instagram', icon: '📸', points: 5 },
    { key: 'linkedin',  name: 'LinkedIn',  icon: '💼', points: 5 },
    { key: 'whatsapp',  name: 'WhatsApp',  icon: '💬', points: 5 },
    { key: 'telegram',  name: 'Telegram',  icon: '✈️', points: 5 },
];

const siteUrl = window.location.origin;
const shareText = 'Découvrez ProxiPro - Trouvez des professionnels près de chez vous ! ';

const shareUrls = {
    facebook:  'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(siteUrl),
    twitter:   'https://twitter.com/intent/tweet?url=' + encodeURIComponent(siteUrl) + '&text=' + encodeURIComponent(shareText),
    instagram: 'https://www.instagram.com/',
    linkedin:  'https://www.linkedin.com/sharing/share-offsite/?url=' + encodeURIComponent(siteUrl),
    whatsapp:  'https://wa.me/?text=' + encodeURIComponent(shareText + siteUrl),
    telegram:  'https://t.me/share/url?url=' + encodeURIComponent(siteUrl) + '&text=' + encodeURIComponent(shareText),
};

document.addEventListener('DOMContentLoaded', function() {
    shareModal = new bootstrap.Modal(document.getElementById('shareModal'));

    // Tab switching
    document.querySelectorAll('.pts-nav-btn').forEach(function(tab) {
        tab.addEventListener('click', function() {
            document.querySelectorAll('.pts-nav-btn').forEach(function(t) { t.classList.remove('active'); });
            document.querySelectorAll('.pts-tab-content').forEach(function(c) { c.classList.remove('active'); });
            this.classList.add('active');
            document.getElementById('tab-' + this.dataset.tab).classList.add('active');
        });
    });

    loadSocialStatus();

    // Handle canceled payment
    var params = new URLSearchParams(window.location.search);
    if (params.get('canceled') === 'true') {
        showToast('Paiement annulé', 'error');
        window.history.replaceState({}, '', '{{ route("pricing.index") }}');
    }

    // Show confirmation modal if purchase just succeeded
    @if(session('success'))
    setTimeout(function() {
        var pts = '{{ $userPoints ?? 0 }}';
        document.getElementById('confirmBalance').textContent = pts + ' pts';
        document.getElementById('confirmMsg').textContent = '{{ session("success") }}';
        new bootstrap.Modal(document.getElementById('purchaseConfirmModal')).show();
    }, 800);
    @endif
});

function showToast(msg, type) {
    var t = document.getElementById('pts-toast');
    t.innerHTML = '<i class="fas fa-' + (type === 'success' ? 'check-circle' : 'exclamation-circle') + ' me-2"></i>' + msg;
    t.className = 'pts-toast ' + type;
    t.style.display = 'block';
    setTimeout(function() { t.style.display = 'none'; }, 5000);
}

function purchasePoints(productKey, btn) {
    var orig = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Redirection...';

    fetch('{{ route("stripe.create-checkout") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
        },
        body: JSON.stringify({ product_key: productKey })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.url) {
            window.location.href = data.url;
        } else {
            showToast(data.error || 'Erreur lors du paiement', 'error');
            btn.disabled = false;
            btn.innerHTML = orig;
        }
    })
    .catch(function() {
        showToast('Erreur réseau, veuillez réessayer', 'error');
        btn.disabled = false;
        btn.innerHTML = orig;
    });
}

// ===== Social sharing =====
function loadSocialStatus() {
    fetch('{{ route("social.status") }}')
        .then(function(r) { return r.json(); })
        .then(function(data) { renderSocial(data); })
        .catch(function() {
            document.getElementById('social-platforms').innerHTML =
                '<div class="col-12 text-center py-3" style="color: rgba(255,255,255,0.4);">Impossible de charger les réseaux</div>';
        });
}

function renderSocial(status) {
    var container = document.getElementById('social-platforms');
    var claimed = status.claimed_platforms || [];
    var totalEarned = status.total_earned || 0;
    var maxPossible = status.max_possible || 30;
    document.getElementById('total-social-earned').textContent =
        totalEarned + ' / ' + maxPossible + ' points réclamés';

    var html = '';
    socialPlatforms.forEach(function(p) {
        var done = claimed.includes(p.key);
        html += '<div class="col-6 col-md-4 col-lg-2"><div class="pts-social-card ' + (done ? 'claimed' : '') + '">'
            + '<div class="pts-social-emoji">' + p.icon + '</div>'
            + '<h6>' + p.name + '</h6>'
            + '<div class="pts-social-reward">+' + p.points + ' pts</div>'
            + '<button class="btn-pts-share" onclick="startSocialShare(\'' + p.key + '\')" ' + (done ? 'disabled' : '') + '>'
            + (done ? '<i class="fas fa-check me-1"></i>Réclamé' : '<i class="fas fa-share-alt me-1"></i>Partager')
            + '</button></div></div>';
    });
    container.innerHTML = html;
}

function startSocialShare(key) {
    currentPlatform = socialPlatforms.find(function(p) { return p.key === key; });
    if (!currentPlatform) return;

    showShareStep('sharing');
    shareModal.show();

    // Instagram doesn't have a share URL API - open the app/website
    var shareUrl = shareUrls[key];
    var win = window.open(shareUrl, '_blank', 'width=600,height=500');
    
    if (!win || win.closed) {
        // For mobile or popup-blocked, try direct navigation
        if (key === 'whatsapp' || key === 'telegram' || key === 'instagram') {
            window.open(shareUrl, '_blank');
            startCountdown(10);
        } else {
            showShareStep('popup_blocked');
        }
        return;
    }
    startCountdown(10);
}

function showShareStep(step, msg) {
    msg = msg || '';
    var el = document.getElementById('share-modal-content');
    var templates = {
        sharing: '<div class="share-step">'
            + '<div class="share-step-icon" style="background:#f0f2f5;">' + (currentPlatform ? currentPlatform.icon : '') + '</div>'
            + '<h4 class="fw-bold">Partagez sur ' + (currentPlatform ? currentPlatform.name : '') + '</h4>'
            + '<p class="text-muted">Complétez le partage dans la fenêtre ouverte...</p>'
            + '<div class="spinner-border text-primary mt-3"></div>'
            + '<p class="text-muted small mt-2">Veuillez patienter quelques secondes</p>'
            + '</div>',
        countdown: '<div class="share-step">'
            + '<div class="countdown-ring" id="countdown-number">30</div>'
            + '<h4 class="fw-bold">Vérification en cours...</h4>'
            + '<p class="text-muted">Ne fermez pas cette fenêtre</p>'
            + '</div>',
        confirm: '<div class="share-step">'
            + '<div class="share-step-icon" style="background:rgba(16,185,129,0.1);">'
            + '<i class="fas fa-question-circle text-success" style="font-size:2rem;"></i>'
            + '</div>'
            + '<h4 class="fw-bold">Avez-vous partagé sur ' + (currentPlatform ? currentPlatform.name : '') + ' ?</h4>'
            + '<p class="text-muted mb-4">Confirmez pour recevoir vos <strong>' + (currentPlatform ? currentPlatform.points : 5) + ' points gratuits</strong></p>'
            + '<div class="d-flex gap-3 justify-content-center">'
            + '<button class="btn btn-outline-secondary px-4" style="border-radius:12px;" onclick="shareModal.hide()">Annuler</button>'
            + '<button class="btn btn-success px-4" style="border-radius:12px;" onclick="confirmShare()"><i class="fas fa-check me-2"></i>Oui, j\'ai partagé !</button>'
            + '</div>'
            + '</div>',
        crediting: '<div class="share-step">'
            + '<div class="spinner-border text-primary" style="width:3rem;height:3rem;"></div>'
            + '<h4 class="fw-bold mt-4">Crédit en cours...</h4>'
            + '</div>',
        success: '<div class="share-step">'
            + '<div class="share-step-icon" style="background:rgba(16,185,129,0.1);">'
            + '<i class="fas fa-check-circle text-success" style="font-size:2.4rem;"></i>'
            + '</div>'
            + '<h4 class="fw-bold text-success">+' + (currentPlatform ? currentPlatform.points : 5) + ' points !</h4>'
            + '<p class="text-muted">' + (msg || 'Merci pour votre partage !') + '</p>'
            + '</div>',
        popup_blocked: '<div class="share-step">'
            + '<div class="share-step-icon" style="background:rgba(245,158,11,0.1);">'
            + '<i class="fas fa-exclamation-triangle text-warning" style="font-size:2rem;"></i>'
            + '</div>'
            + '<h4 class="fw-bold">Popup bloquée</h4>'
            + '<p class="text-muted">Autorisez les popups pour partager sur ce réseau.</p>'
            + '<button class="btn btn-outline-secondary mt-3 px-4" style="border-radius:12px;" onclick="shareModal.hide()">Fermer</button>'
            + '</div>',
        error: '<div class="share-step">'
            + '<div class="share-step-icon" style="background:rgba(239,68,68,0.1);">'
            + '<i class="fas fa-times-circle text-danger" style="font-size:2rem;"></i>'
            + '</div>'
            + '<h4 class="fw-bold text-danger">Erreur</h4>'
            + '<p class="text-muted">' + msg + '</p>'
            + '<button class="btn btn-outline-secondary mt-3 px-4" style="border-radius:12px;" onclick="shareModal.hide()">Fermer</button>'
            + '</div>'
    };
    el.innerHTML = templates[step] || '';
}

function startCountdown(secs) {
    showShareStep('countdown');
    var count = secs;
    var el = document.getElementById('countdown-number');
    if (el) el.textContent = count;

    countdownInterval = setInterval(function() {
        count--;
        var n = document.getElementById('countdown-number');
        if (n) n.textContent = count;
        if (count <= 0) {
            clearInterval(countdownInterval);
            showShareStep('confirm');
        }
    }, 1000);
}

function confirmShare() {
    showShareStep('crediting');

    fetch('{{ route("social.share") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ platform: currentPlatform.key })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            showShareStep('success', data.message);
            // Update the points display in the hero section
            if (data.new_balance !== undefined) {
                document.getElementById('user-points').textContent =
                    new Intl.NumberFormat('fr-FR').format(data.new_balance) + ' pts';
            }
            setTimeout(function() { shareModal.hide(); loadSocialStatus(); }, 2500);
        } else if (data.already_claimed) {
            showShareStep('error', 'Points déjà réclamés pour ' + currentPlatform.name + '. Vous pouvez toujours partager librement !');
        } else {
            showShareStep('error', data.error || data.message || 'Erreur inconnue');
        }
    })
    .catch(function(err) {
        console.error('Social share error:', err);
        showShareStep('error', 'Erreur réseau. Vérifiez votre connexion et réessayez.');
    });
}
</script>
@endsection
