@extends('layouts.app')

@section('title', 'Annonce publiée - Options de visibilité - ProxiPro')

@push('styles')
<style>
    body { background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); min-height: 100vh; }
    
    .success-container {
        max-width: 1000px;
        margin: 0 auto;
        padding: 40px 20px;
    }
    
    /* Success Header */
    .success-header {
        text-align: center;
        margin-bottom: 32px;
    }
    
    .success-icon {
        width: 90px;
        height: 90px;
        background: linear-gradient(135deg, #10b981, #059669);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        animation: successPop 0.6s ease-out;
    }
    
    @keyframes successPop {
        0% { transform: scale(0); opacity: 0; }
        50% { transform: scale(1.2); }
        100% { transform: scale(1); opacity: 1; }
    }
    
    .success-icon i {
        font-size: 2.5rem;
        color: white;
    }
    
    .success-header h1 {
        font-size: 1.9rem;
        font-weight: 800;
        color: #166534;
        margin-bottom: 10px;
    }
    
    .success-header p {
        color: #16a34a;
        font-size: 1.05rem;
    }
    
    /* Points Earned + Ad Summary Row */
    .top-info {
        display: flex;
        gap: 16px;
        margin-bottom: 32px;
        flex-wrap: wrap;
    }
    
    .ad-summary {
        background: white;
        border-radius: 16px;
        padding: 18px 24px;
        box-shadow: 0 6px 24px rgba(0,0,0,0.06);
        display: flex;
        align-items: center;
        gap: 16px;
        flex: 1.5;
        min-width: 300px;
    }
    
    .ad-summary-img {
        width: 60px;
        height: 60px;
        border-radius: 12px;
        background: linear-gradient(135deg, #3b82f6, #8b5cf6);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.2rem;
        flex-shrink: 0;
        overflow: hidden;
    }
    
    .ad-summary-img img { width: 100%; height: 100%; object-fit: cover; }
    .ad-summary-info h4 { font-size: 1rem; font-weight: 700; color: #1e293b; margin-bottom: 2px; }
    .ad-summary-info p { color: #64748b; font-size: 0.85rem; margin: 0; }
    
    /* Section Title */
    .section-title {
        text-align: center;
        margin-bottom: 28px;
    }
    
    .section-title h2 {
        font-size: 1.7rem;
        font-weight: 800;
        color: #1e293b;
        margin-bottom: 8px;
        white-space: nowrap;
    }
    
    .section-title p {
        color: #64748b;
        font-size: 1rem;
        max-width: 600px;
        margin: 0 auto;
    }
    
    /* ===== CARD GRID ===== */
    .options-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        gap: 20px;
        margin-bottom: 32px;
    }
    
    /* Single Option Card (pancarte) */
    .option-card {
        background: white;
        border: 3px solid #e2e8f0;
        border-radius: 22px;
        padding: 28px 22px 24px;
        cursor: pointer;
        transition: all 0.35s cubic-bezier(.4,0,.2,1);
        position: relative;
        overflow: hidden;
        text-align: center;
    }
    
    .option-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 5px;
        background: var(--card-color, #e2e8f0);
        transition: height 0.3s;
    }
    
    .option-card:hover {
        border-color: var(--card-color, #3b82f6);
        transform: translateY(-6px);
        box-shadow: 0 16px 40px rgba(0,0,0,0.12);
    }
    
    .option-card:hover::before {
        height: 6px;
    }
    
    .option-card.selected {
        border-color: var(--card-color, #3b82f6);
        box-shadow: 0 0 0 4px rgba(var(--card-rgb, 59,130,246), 0.15), 0 16px 40px rgba(0,0,0,0.1);
        transform: translateY(-6px) scale(1.02);
    }
    
    .option-card.selected::before {
        height: 7px;
    }
    
    /* Selected checkmark */
    .option-card .check-badge {
        position: absolute;
        top: 14px;
        right: 14px;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background: var(--card-color, #3b82f6);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transform: scale(0);
        transition: all 0.3s;
    }
    
    .option-card.selected .check-badge {
        opacity: 1;
        transform: scale(1);
    }
    
    .option-card .check-badge i {
        color: white;
        font-size: 0.75rem;
    }
    
    /* Card icon */
    .card-icon {
        width: 64px;
        height: 64px;
        border-radius: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 16px;
        font-size: 1.6rem;
        color: white;
        background: var(--card-color, #3b82f6);
        box-shadow: 0 8px 20px rgba(var(--card-rgb, 59,130,246), 0.3);
    }
    
    /* Card label/tag */
    .card-tag {
        display: inline-block;
        font-size: 0.7rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
        padding: 4px 12px;
        border-radius: 20px;
        margin-bottom: 10px;
        color: white;
        background: var(--card-color, #3b82f6);
    }
    
    .option-card h3 {
        font-size: 1.15rem;
        font-weight: 800;
        color: #1e293b;
        margin-bottom: 6px;
    }
    
    .card-duration {
        color: #64748b;
        font-size: 0.88rem;
        font-weight: 600;
        margin-bottom: 14px;
    }
    
    .card-features {
        text-align: left;
        margin-bottom: 18px;
        padding: 0;
        list-style: none;
    }
    
    .card-features li {
        display: flex;
        align-items: flex-start;
        gap: 8px;
        font-size: 0.85rem;
        color: #475569;
        margin-bottom: 6px;
        line-height: 1.4;
    }
    
    .card-features li i {
        color: var(--card-color, #3b82f6);
        margin-top: 3px;
        flex-shrink: 0;
    }
    
    /* Price */
    .card-price {
        background: linear-gradient(135deg, rgba(var(--card-rgb, 59,130,246), 0.08), rgba(var(--card-rgb, 59,130,246), 0.04));
        border: 2px solid rgba(var(--card-rgb, 59,130,246), 0.15);
        border-radius: 14px;
        padding: 12px 16px;
    }
    
    .card-price .pts {
        font-size: 1.4rem;
        font-weight: 800;
        color: var(--card-color, #3b82f6);
    }
    
    .card-price .euros {
        font-size: 0.9rem;
        color: #64748b;
        font-weight: 600;
    }
    
    .card-price .separator {
        color: #cbd5e1;
        margin: 0 6px;
        font-weight: 400;
    }
    
    /* Popular badge */
    .popular-badge {
        position: absolute;
        top: 18px;
        left: -32px;
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: white;
        font-size: 0.65rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
        padding: 5px 40px;
        transform: rotate(-45deg);
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    }
    
    /* ===== CONFIRMATION PANEL ===== */
    .confirm-panel {
        background: white;
        border-radius: 20px;
        padding: 32px;
        box-shadow: 0 16px 48px rgba(0,0,0,0.1);
        margin-bottom: 24px;
        display: none;
        animation: slideUp 0.4s ease-out;
    }
    
    .confirm-panel.visible {
        display: block;
    }
    
    @keyframes slideUp {
        0% { opacity: 0; transform: translateY(20px); }
        100% { opacity: 1; transform: translateY(0); }
    }
    
    .confirm-panel-header {
        display: flex;
        align-items: center;
        gap: 16px;
        margin-bottom: 20px;
        padding-bottom: 16px;
        border-bottom: 2px solid #f1f5f9;
    }
    
    .confirm-panel-icon {
        width: 52px;
        height: 52px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
        color: white;
        flex-shrink: 0;
    }
    
    .confirm-panel-header h3 {
        font-size: 1.3rem;
        font-weight: 800;
        color: #1e293b;
        margin: 0 0 4px;
    }
    
    .confirm-panel-header p {
        color: #64748b;
        font-size: 0.9rem;
        margin: 0;
    }
    
    .confirm-details {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
        margin-bottom: 20px;
    }
    
    .confirm-balance {
        background: #f8fafc;
        border-radius: 12px;
        padding: 14px 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .confirm-balance i { color: #f59e0b; font-size: 1.2rem; }
    .confirm-balance .balance-label { color: #64748b; font-size: 0.85rem; }
    .confirm-balance .balance-value { font-weight: 800; color: #1e293b; font-size: 1.1rem; }
    
    .confirm-cost {
        background: #f0fdf4;
        border-radius: 12px;
        padding: 14px 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .confirm-cost i { color: #16a34a; font-size: 1.2rem; }
    .confirm-cost .cost-label { color: #64748b; font-size: 0.85rem; }
    .confirm-cost .cost-value { font-weight: 800; color: #16a34a; font-size: 1.1rem; }
    
    .confirm-actions {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }
    
    .btn-confirm-points {
        flex: 1;
        min-width: 200px;
        padding: 16px 24px;
        border-radius: 14px;
        font-size: 1.05rem;
        font-weight: 700;
        border: none;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        color: white;
    }
    
    .btn-confirm-points:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 30px rgba(0,0,0,0.2);
        color: white;
    }
    
    .btn-confirm-points:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }
    
    .btn-confirm-stripe {
        flex: 1;
        min-width: 200px;
        padding: 16px 24px;
        border-radius: 14px;
        font-size: 1.05rem;
        font-weight: 700;
        border: 2px solid #e2e8f0;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        background: white;
        color: #475569;
        text-decoration: none;
    }
    
    .btn-confirm-stripe:hover {
        border-color: #6366f1;
        background: #f8fafc;
        color: #4f46e5;
    }
    
    .insufficient-msg {
        background: #fef2f2;
        border: 1px solid #fecaca;
        border-radius: 12px;
        padding: 14px 18px;
        color: #991b1b;
        font-size: 0.9rem;
        font-weight: 600;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .btn-buy-points {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: white;
        padding: 12px 24px;
        border-radius: 12px;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 0.95rem;
        transition: all 0.3s;
        border: none;
    }
    
    .btn-buy-points:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(245, 158, 11, 0.4);
        color: white;
    }
    
    /* Skip button */
    .skip-section {
        text-align: center;
        margin-bottom: 20px;
    }
    
    .btn-skip {
        background: transparent;
        color: #64748b;
        padding: 14px 32px;
        border-radius: 14px;
        font-size: 1rem;
        font-weight: 600;
        border: 2px solid #e2e8f0;
        cursor: pointer;
        transition: all 0.3s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .btn-skip:hover {
        background: #f8fafc;
        border-color: #cbd5e1;
        color: #475569;
    }
    
    /* User Points Footer */
    .points-footer {
        text-align: center;
        color: #64748b;
        font-size: 0.9rem;
    }
    
    .points-footer a { color: #3b82f6; font-weight: 600; }
    
    /* ===== RESPONSIVE ===== */
    @media (max-width: 768px) {
        .success-container { padding: 24px 16px; }
        .success-header h1 { font-size: 1.5rem; }
        .options-grid { grid-template-columns: 1fr; }
        .section-title h2 { font-size: 1.4rem; white-space: normal; }
        .confirm-details { flex-direction: column; }
        .confirm-actions { flex-direction: column; }
        .top-info { flex-direction: column; }
    }
</style>
@endpush

@section('content')
<div class="success-container">
    <!-- Success Header -->
    <div class="success-header">
        <div class="success-icon">
            <i class="fas fa-check"></i>
        </div>
        <h1>🎉 Votre annonce est publiée !</h1>
        <p>Elle est maintenant visible par tous les utilisateurs de ProxiPro</p>
    </div>
    
    <!-- Top Info Row -->
    <div class="top-info">
        <div class="ad-summary">
            <div class="ad-summary-img">
                @if($ad->photos && count($ad->photos) > 0)
                    <img src="{{ storage_url($ad->photos[0]) }}" alt="{{ $ad->title }}">
                @else
                    <i class="fas fa-image"></i>
                @endif
            </div>
            <div class="ad-summary-info">
                <h4>{{ Str::limit($ad->title, 40) }}</h4>
                <p><i class="fas fa-tag me-1"></i>{{ $ad->category }} • <i class="fas fa-map-marker-alt me-1"></i>{{ $ad->location }}</p>
            </div>
        </div>
    </div>
    
    <!-- Section Title -->
    <div class="section-title">
        <h2>🚀 Boostez la visibilité de votre annonce</h2>
        <p>Sélectionnez une pancarte pour augmenter vos chances d'être contacté rapidement</p>
    </div>
    
    <!-- Options Grid (Pancartes) -->
    <div class="options-grid">
        
        <!-- 🔥 URGENT Card -->
        <div class="option-card" 
             style="--card-color: #dc2626; --card-rgb: 220,38,38;" 
             data-type="urgent"
             data-name="Publication Urgente"
             data-points="15"
             data-euros="14"
             data-duration="7 jours"
             data-icon="fas fa-fire"
             onclick="selectOption(this)">
            <div class="check-badge"><i class="fas fa-check"></i></div>
            <div class="card-icon"><i class="fas fa-fire"></i></div>
            <span class="card-tag">Urgent</span>
            <h3>🔥 Publication Urgente</h3>
            <div class="card-duration"><i class="fas fa-clock me-1"></i>7 jours</div>
            <ul class="card-features">
                <li><i class="fas fa-check-circle"></i> Épinglée dans la section "Urgentes"</li>
                <li><i class="fas fa-check-circle"></i> Visible en priorité sur la page d'accueil</li>
                <li><i class="fas fa-check-circle"></i> Badge URGENT sur l'annonce</li>
                <li><i class="fas fa-check-circle"></i> Notifications aux utilisateurs proches</li>
            </ul>
            <div class="card-price">
                <span class="pts">15 pts</span>
                <span class="separator">ou</span>
                <span class="euros">14,00 €</span>
            </div>
        </div>
        
        <!-- Boost Packages -->
        @foreach($packages as $key => $package)
        <div class="option-card" 
             style="--card-color: {{ $package['color'] }}; --card-rgb: {{ implode(',', sscanf($package['color'], '#%02x%02x%02x')) }};"
             data-type="boost"
             data-key="{{ $key }}"
             data-name="{{ $package['name'] }}"
             data-points="{{ $package['price_points'] }}"
             data-euros="{{ $package['price_euros'] }}"
             data-duration="{{ $package['duration_days'] }} jours"
             data-icon="{{ $package['icon'] }}"
             onclick="selectOption(this)">
            <div class="check-badge"><i class="fas fa-check"></i></div>
            @if($key === 'boost_7')
                <div class="popular-badge">Populaire</div>
            @endif
            <div class="card-icon"><i class="{{ $package['icon'] }}"></i></div>
            <span class="card-tag">Boost</span>
            <h3>{{ $package['name'] }}</h3>
            <div class="card-duration"><i class="fas fa-clock me-1"></i>{{ $package['duration_days'] }} jours</div>
            <ul class="card-features">
                @foreach($package['features'] as $feature)
                <li><i class="fas fa-check-circle"></i> {{ $feature }}</li>
                @endforeach
            </ul>
            <div class="card-price">
                <span class="pts">{{ $package['price_points'] }} pts</span>
                <span class="separator">ou</span>
                <span class="euros">{{ number_format($package['price_euros'], 2, ',', ' ') }} €</span>
            </div>
        </div>
        @endforeach
    </div>
    
    <!-- Confirmation Panel (hidden by default, appears on card selection) -->
    <div class="confirm-panel" id="confirmPanel">
        <div class="confirm-panel-header">
            <div class="confirm-panel-icon" id="confirmIcon"></div>
            <div>
                <h3 id="confirmTitle">—</h3>
                <p id="confirmDuration">—</p>
            </div>
        </div>
        
        <div class="confirm-details">
            <div class="confirm-balance">
                <i class="fas fa-wallet"></i>
                <div>
                    <div class="balance-label">Votre solde</div>
                    <div class="balance-value">{{ number_format($userPoints, 0, ',', ' ') }} pts</div>
                </div>
            </div>
            <div class="confirm-cost">
                <i class="fas fa-tag"></i>
                <div>
                    <div class="cost-label">Coût</div>
                    <div class="cost-value" id="confirmCost">—</div>
                </div>
            </div>
        </div>
        
        <!-- If enough points -->
        <div id="confirmEnough">
            <div class="confirm-actions">
                <!-- Points form -->
                <form id="formPoints" method="POST" style="flex: 1; min-width: 200px;">
                    @csrf
                    <input type="hidden" name="package" id="inputPackage">
                    <button type="submit" class="btn-confirm-points" id="btnPoints" style="width: 100%;">
                        <i class="fas fa-coins"></i> <span id="btnPointsLabel">Payer avec points</span>
                    </button>
                </form>
                <!-- Stripe form (only for boost, not urgent) -->
                <form id="formStripe" method="POST" style="flex: 1; min-width: 200px;">
                    @csrf
                    <input type="hidden" name="package" id="inputPackageStripe">
                    <button type="submit" class="btn-confirm-stripe" style="width: 100%;">
                        <i class="fas fa-credit-card"></i> <span id="btnStripeLabel">Payer par carte</span>
                    </button>
                </form>
            </div>
        </div>
        
        <!-- If NOT enough points -->
        <div id="confirmNotEnough" style="display: none;">
            <div class="insufficient-msg">
                <i class="fas fa-exclamation-triangle"></i>
                <span id="insufficientText">Points insuffisants</span>
            </div>
            <div class="confirm-actions">
                <a href="{{ route('pricing.index') }}" class="btn-buy-points">
                    <i class="fas fa-coins"></i> Acheter des points
                </a>
                <!-- Stripe alternative for boosts -->
                <form id="formStripeAlt" method="POST" style="flex: 1; min-width: 200px; display: none;">
                    @csrf
                    <input type="hidden" name="package" id="inputPackageStripeAlt">
                    <button type="submit" class="btn-confirm-stripe" style="width: 100%;">
                        <i class="fas fa-credit-card"></i> <span id="btnStripeAltLabel">Payer par carte</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Skip -->
    <div class="skip-section">
        <a href="{{ route('ads.show', $ad) }}" class="btn-skip">
            <i class="fas fa-arrow-right"></i> Passer et voir mon annonce
        </a>
    </div>
    
    <!-- Footer Points -->
    <div class="points-footer">
        <i class="fas fa-coins text-warning me-1"></i>
        Vous avez actuellement <strong>{{ number_format($userPoints, 0, ',', ' ') }} points</strong>.
        <a href="{{ route('pricing.index') }}">Acheter des points</a>
    </div>
</div>

<script>
const userPoints = {{ $userPoints ?? 0 }};
const adId = {{ $ad->id }};
let currentSelection = null;

function selectOption(card) {
    // Deselect all
    document.querySelectorAll('.option-card').forEach(c => c.classList.remove('selected'));
    // Select this one
    card.classList.add('selected');
    
    const type = card.dataset.type;
    const name = card.dataset.name;
    const points = parseInt(card.dataset.points);
    const euros = parseFloat(card.dataset.euros);
    const duration = card.dataset.duration;
    const icon = card.dataset.icon;
    const color = getComputedStyle(card).getPropertyValue('--card-color').trim();
    const key = card.dataset.key || '';
    
    currentSelection = { type, name, points, euros, duration, icon, color, key };
    
    // Show confirm panel
    const panel = document.getElementById('confirmPanel');
    panel.classList.add('visible');
    
    // Update header
    document.getElementById('confirmIcon').style.background = color;
    document.getElementById('confirmIcon').innerHTML = '<i class="' + icon + '" style="color:white;font-size:1.3rem;"></i>';
    document.getElementById('confirmTitle').textContent = name;
    document.getElementById('confirmDuration').textContent = 'Durée : ' + duration;
    document.getElementById('confirmCost').textContent = points + ' points';
    
    // Style confirm button
    const btnPoints = document.getElementById('btnPoints');
    btnPoints.style.background = 'linear-gradient(135deg, ' + color + ', ' + color + 'cc)';
    
    const enoughPoints = userPoints >= points;
    
    if (type === 'urgent') {
        // Urgent: points + stripe (14€)
        document.getElementById('formPoints').action = '/ads/' + adId + '/make-urgent';
        document.getElementById('inputPackage').value = '';
        document.getElementById('btnPointsLabel').textContent = 'Publier en URGENT — ' + points + ' pts';
        
        // Show stripe for urgent too
        document.getElementById('formStripe').action = '/ads/' + adId + '/make-urgent/stripe';
        document.getElementById('inputPackageStripe').value = '';
        document.getElementById('btnStripeLabel').textContent = 'Payer 14,00 € par carte';
        
        if (enoughPoints) {
            document.getElementById('confirmEnough').style.display = 'block';
            document.getElementById('confirmNotEnough').style.display = 'none';
            document.getElementById('formStripe').style.display = 'block';
            btnPoints.disabled = false;
        } else {
            document.getElementById('confirmEnough').style.display = 'none';
            document.getElementById('confirmNotEnough').style.display = 'block';
            document.getElementById('insufficientText').textContent = 
                'Vous avez ' + userPoints + ' points, il vous en faut ' + points + '. Payez par carte ou achetez des points.';
            // Show stripe alternative
            document.getElementById('formStripeAlt').style.display = 'block';
            document.getElementById('formStripeAlt').action = '/ads/' + adId + '/make-urgent/stripe';
            document.getElementById('inputPackageStripeAlt').value = '';
            document.getElementById('btnStripeAltLabel').textContent = 'Payer 14,00 € par carte';
        }
    } else {
        // Boost: points + stripe
        document.getElementById('formPoints').action = '/ads/' + adId + '/boost/points';
        document.getElementById('inputPackage').value = key;
        document.getElementById('btnPointsLabel').textContent = 'Payer ' + points + ' points';
        
        document.getElementById('formStripe').action = '/ads/' + adId + '/boost/stripe';
        document.getElementById('inputPackageStripe').value = key;
        document.getElementById('btnStripeLabel').textContent = 'Payer ' + euros.toFixed(2).replace('.', ',') + ' €';
        
        if (enoughPoints) {
            document.getElementById('confirmEnough').style.display = 'block';
            document.getElementById('confirmNotEnough').style.display = 'none';
            document.getElementById('formStripe').style.display = 'block';
            btnPoints.disabled = false;
        } else {
            document.getElementById('confirmEnough').style.display = 'none';
            document.getElementById('confirmNotEnough').style.display = 'block';
            document.getElementById('insufficientText').textContent = 
                'Vous avez ' + userPoints + ' points, il vous en faut ' + points + '. Payez par carte ou achetez des points.';
            // Show stripe alternative
            document.getElementById('formStripeAlt').style.display = 'block';
            document.getElementById('formStripeAlt').action = '/ads/' + adId + '/boost/stripe';
            document.getElementById('inputPackageStripeAlt').value = key;
            document.getElementById('btnStripeAltLabel').textContent = 'Payer ' + euros.toFixed(2).replace('.', ',') + ' € par carte';
        }
    }
    
    // Smooth scroll to panel
    setTimeout(() => {
        panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }, 100);
}
</script>
@endsection
