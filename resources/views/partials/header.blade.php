{{-- Header partagé pour toutes les pages standalone --}}
<header class="main-header-modern">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center h-100">
            <!-- Logo -->
            <a href="{{ route('feed') }}" class="navbar-brand-modern">
                <div class="brand-logo">P</div>
                <div class="d-none d-sm-block">
                    <span class="brand-text">ProxiPro</span>
                </div>
            </a>
            
            @auth
            <!-- Navigation centrale -->
            <div class="d-none d-lg-flex align-items-center gap-1 mx-4">
                <a href="{{ route('feed') }}" class="nav-link-modern active">
                    <i class="fas fa-home"></i>Accueil
                </a>
                <a href="{{ route('home') }}" class="nav-link-modern">
                    <i class="fas fa-th-large"></i>Dashboard
                </a>
                <a href="{{ route('ads.index') }}" class="nav-link-modern">
                    <i class="fas fa-bullhorn"></i>Annonces
                </a>
                @php
                    $lostItemsCountHeader = \App\Models\LostItem::where('status', 'active')->count();
                @endphp
                <a href="{{ route('lost-items.index') }}" class="nav-link-modern nav-link-lost position-relative">
                    <i class="fas fa-search-location"></i>Objets perdus
                    @if($lostItemsCountHeader > 0)
                        <span class="lost-badge">{{ $lostItemsCountHeader }}</span>
                    @endif
                </a>
                <div class="dropdown">
                    <a href="#" class="nav-link-modern dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-tools"></i>Outils
                    </a>
                    <ul class="dropdown-menu dropdown-menu-modern mt-2">
                        <li><a class="dropdown-item dropdown-item-modern" href="{{ route('tools.pdf-converter') }}"><i class="fas fa-file-pdf text-danger"></i>Convertisseur PDF</a></li>
                        <li><a class="dropdown-item dropdown-item-modern" href="{{ route('tools.image-compressor') }}"><i class="fas fa-compress text-primary"></i>Compresseur d'images</a></li>
                        <li><a class="dropdown-item dropdown-item-modern" href="{{ route('tools.qr-generator') }}"><i class="fas fa-qrcode text-dark"></i>Générateur QR Code</a></li>
                    </ul>
                </div>
            </div>

            <!-- Actions droite -->
            <div class="d-flex align-items-center gap-2">
                <!-- Bouton Devenir Prestataire -->
                @php
                    $user = Auth::user();
                    $isOAuthWithoutProfile = $user->isOAuthUser() && !$user->profile_completed && !$user->is_service_provider;
                    $isRegularParticulier = !$user->isOAuthUser() && $user->user_type === 'particulier' && !$user->is_service_provider;
                    $isActiveProvider = $user->is_service_provider;
                @endphp
                
                {{-- Utilisateur OAuth sans profil complété : Modal spécial --}}
                @if($isOAuthWithoutProfile)
                <button type="button" class="btn-become-provider d-none d-md-flex" data-bs-toggle="modal" data-bs-target="#becomeProviderOAuthModal">
                    <i class="fas fa-rocket"></i>
                    <span>Devenir prestataire</span>
                </button>
                {{-- Particulier classique (inscription normale) sans statut prestataire --}}
                @elseif($isRegularParticulier)
                <button type="button" class="btn-become-provider d-none d-md-flex" data-bs-toggle="modal" data-bs-target="#becomeProviderModal">
                    <i class="fas fa-user-plus"></i>
                    <span>Devenir prestataire</span>
                </button>
                {{-- Prestataire actif (particulier) : gérer ses services --}}
                @elseif($isActiveProvider && $user->user_type === 'particulier')
                <button type="button" class="btn-provider-badge d-none d-md-flex" data-bs-toggle="modal" data-bs-target="#becomeProviderModal" title="Gérer mes services">
                    <i class="fas fa-check-circle"></i>
                    <span>Prestataire</span>
                </button>
                @endif

                <!-- Messages -->
                <a href="{{ route('messages.index') }}" class="nav-messages-btn position-relative" title="Messages">
                    <i class="fas fa-envelope"></i>
                    <span class="d-none d-sm-inline">Messages</span>
                    @php
                        $unreadMessages = Auth::user()->unreadMessagesCount();
                    @endphp
                    @if($unreadMessages > 0)
                        <span class="messages-counter">{{ $unreadMessages > 99 ? '99+' : $unreadMessages }}</span>
                    @endif
                </a>

                <!-- Tarifs -->
                <a href="{{ route('pricing.index') }}" class="nav-icon-btn" title="Tarifs">
                    <i class="fas fa-crown" style="color: #f59e0b;"></i>
                </a>
                
                <!-- Points -->
                <a href="{{ route('points.dashboard') }}" class="points-badge text-decoration-none d-none d-sm-flex">
                    <span>🪙</span>
                    <span>{{ Auth::user()->available_points ?? 0 }}</span>
                </a>
                
                <!-- User Dropdown -->
                <div class="dropdown">
                    <button class="user-dropdown-modern d-flex align-items-center border-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        @if(Auth::user()->avatar)
                            <img src="{{ storage_url(Auth::user()->avatar) }}" alt="Avatar" class="user-avatar">
                        @else
                            <div class="user-avatar-placeholder">
                                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                            </div>
                        @endif
                        <span class="d-none d-md-inline ms-2 user-name-text">{{ Str::limit(Auth::user()->name, 12) }}</span>
                        <i class="fas fa-chevron-down ms-2 dropdown-chevron"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-modern mt-2" style="min-width: 240px;">
                        <li class="px-3 py-2 border-bottom">
                            <div class="d-flex align-items-center">
                                @if(Auth::user()->avatar)
                                    <img src="{{ storage_url(Auth::user()->avatar) }}" alt="" class="user-avatar me-2">
                                @else
                                    <div class="user-avatar-placeholder me-2">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</div>
                                @endif
                                <div>
                                    <div style="font-weight: 600; color: #1e293b;">{{ Auth::user()->name }}</div>
                                    <small style="color: #64748b;">{{ Auth::user()->email }}</small>
                                </div>
                            </div>
                        </li>
                        <li><a class="dropdown-item dropdown-item-modern" href="{{ route('profile.show') }}"><i class="fas fa-user text-primary"></i>Mon Profil</a></li>
                        <li><a class="dropdown-item dropdown-item-modern" href="{{ route('home') }}"><i class="fas fa-th-large text-secondary"></i>Tableau de bord</a></li>
                        <li><a class="dropdown-item dropdown-item-modern" href="{{ route('messages.index') }}"><i class="fas fa-envelope text-info"></i>Messages @if($unreadMessages > 0)<span class="badge bg-danger ms-auto" style="font-size: 0.65rem;">{{ $unreadMessages }}</span>@endif</a></li>
                        <li><a class="dropdown-item dropdown-item-modern" href="{{ route('points.dashboard') }}"><i class="fas fa-coins text-warning"></i>Mes Points <span class="badge bg-success ms-auto">{{ Auth::user()->available_points ?? 0 }}</span></a></li>
                        <li><hr class="dropdown-divider my-2"></li>
                        <li><a class="dropdown-item dropdown-item-modern" href="{{ route('pricing.index') }}"><i class="fas fa-crown text-warning"></i>Tarifs</a></li>
                        <li><a class="dropdown-item dropdown-item-modern" href="{{ route('settings.index') }}"><i class="fas fa-cog text-secondary"></i>Paramètres</a></li>
                        @if(Auth::user()->email === config('admin.principal_admin.email'))
                        <li><hr class="dropdown-divider my-2"></li>
                        <li><a class="dropdown-item dropdown-item-modern" href="{{ route('admin.dashboard') }}"><i class="fas fa-shield-alt text-danger"></i>Administration</a></li>
                        @endif
                        <li><hr class="dropdown-divider my-2"></li>
                        <li>
                            <a class="dropdown-item dropdown-item-modern text-danger" href="{{ route('logout') }}" 
                               onclick="event.preventDefault(); document.getElementById('header-logout-form').submit();">
                                <i class="fas fa-sign-out-alt"></i>Déconnexion
                            </a>
                        </li>
                    </ul>
                    <form id="header-logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
                </div>
            </div>
            @else
            <!-- Guest Navigation -->
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('login') }}" class="btn-auth btn-login">Connexion</a>
                <a href="{{ route('register') }}" class="btn-auth btn-register">S'inscrire</a>
            </div>
            @endauth
        </div>
    </div>
</header>

<style>
    /* ===== HEADER MODERNE ===== */
    .main-header-modern {
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border-bottom: 1px solid rgba(226, 232, 240, 0.8);
        position: sticky;
        top: 0;
        z-index: 1000;
        height: 64px;
    }
    
    .navbar-brand-modern {
        display: flex;
        align-items: center;
        gap: 10px;
        text-decoration: none;
    }
    
    .brand-logo {
        width: 36px;
        height: 36px;
        background: linear-gradient(135deg, #7c3aed, #9333ea);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 700;
        font-size: 1rem;
        box-shadow: 0 4px 6px -1px rgba(124, 58, 237, 0.3);
        transition: all 0.3s;
    }
    
    .navbar-brand-modern:hover .brand-logo {
        transform: scale(1.05);
        box-shadow: 0 8px 12px -2px rgba(124, 58, 237, 0.4);
    }
    
    .brand-text {
        font-weight: 700;
        font-size: 1.25rem;
        background: linear-gradient(135deg, #7c3aed, #9333ea);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    
    /* Bouton Objets perdus sobre */
    .nav-link-lost {
        color: #ea580c !important;
        border: 1px solid #fed7aa;
        background: #fff7ed !important;
    }
    .nav-link-lost:hover {
        background: #ffedd5 !important;
        border-color: #fdba74;
    }
    .nav-link-lost i {
        color: #ea580c !important;
    }
    .lost-badge {
        background: #ea580c;
        color: white;
        font-size: 0.65rem;
        padding: 2px 6px;
        border-radius: 10px;
        margin-left: 4px;
        font-weight: 600;
    }
    
    .nav-link-modern {
        font-weight: 500;
        font-size: 0.875rem;
        color: #64748b !important;
        padding: 8px 14px !important;
        border-radius: 10px;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 6px;
        text-decoration: none;
    }
    
    .nav-link-modern:hover {
        color: #1e293b !important;
        background: #f1f5f9;
    }
    
    .nav-link-modern.active {
        color: #7c3aed !important;
        background: #ffffff;
    }
    
    .nav-link-modern i {
        font-size: 0.9rem;
        opacity: 0.8;
    }
    
    .nav-icon-btn {
        position: relative;
        padding: 10px;
        border-radius: 10px;
        color: #64748b;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .nav-icon-btn:hover {
        background: #f1f5f9;
        color: #7c3aed;
    }
    
    .nav-icon-btn i {
        font-size: 1.1rem;
    }
    
    .notification-badge {
        position: absolute;
        top: 0;
        right: 0;
        background: linear-gradient(135deg, #ef4444, #f97316);
        color: white;
        font-size: 0.6rem;
        font-weight: 700;
        padding: 2px 5px;
        border-radius: 9999px;
        border: 2px solid white;
        min-width: 18px;
        text-align: center;
    }
    
    .points-badge {
        display: flex;
        align-items: center;
        gap: 6px;
        background: linear-gradient(135deg, #fffbeb, #fef3c7);
        padding: 6px 12px;
        border-radius: 10px;
        border: 1px solid rgba(245, 158, 11, 0.3);
        font-size: 0.8rem;
        font-weight: 600;
        color: #b45309;
    }
    
    .btn-publish-modern {
        background: linear-gradient(135deg, #7c3aed, #9333ea);
        color: white !important;
        font-weight: 600;
        font-size: 0.875rem;
        padding: 8px 16px;
        border-radius: 10px;
        text-decoration: none;
        display: flex;
        align-items: center;
        transition: all 0.2s;
        box-shadow: 0 4px 6px -1px rgba(124, 58, 237, 0.3);
    }
    
    .btn-publish-modern:hover {
        background: linear-gradient(135deg, #7c3aed, #7c3aed);
        color: white !important;
        transform: translateY(-1px);
        box-shadow: 0 6px 12px rgba(124, 58, 237, 0.4);
    }
    
    .user-dropdown-modern {
        background: linear-gradient(135deg, #7c3aed, #9333ea);
        padding: 8px 14px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s;
        cursor: pointer;
        box-shadow: 0 4px 6px -1px rgba(124, 58, 237, 0.3);
    }
    
    .user-dropdown-modern:hover {
        background: linear-gradient(135deg, #6d28d9, #7c3aed);
        box-shadow: 0 6px 12px rgba(124, 58, 237, 0.4);
        transform: translateY(-1px);
    }
    
    .user-dropdown-modern::after {
        display: none;
    }
    
    .user-name-text {
        font-weight: 600;
        font-size: 0.875rem;
        color: white;
    }
    
    .dropdown-chevron {
        font-size: 0.65rem;
        color: rgba(255, 255, 255, 0.8);
        transition: transform 0.2s;
    }
    
    .dropdown.show .dropdown-chevron {
        transform: rotate(180deg);
    }
    
    /* Bouton Messages amélioré */
    .nav-messages-btn {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 14px;
        border-radius: 10px;
        background: #f1f5f9;
        color: #475569;
        text-decoration: none;
        font-size: 0.875rem;
        font-weight: 500;
        transition: all 0.2s;
        position: relative;
    }
    
    .nav-messages-btn:hover {
        background: #e2e8f0;
        color: #7c3aed;
    }
    
    .nav-messages-btn i {
        font-size: 1rem;
    }
    
    .messages-counter {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: white;
        font-size: 0.7rem;
        font-weight: 700;
        padding: 2px 6px;
        border-radius: 9999px;
        min-width: 20px;
        text-align: center;
        margin-left: 4px;
    }
    
    .user-avatar {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        border: 2px solid white;
        object-fit: cover;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .user-avatar-placeholder {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        border: 2px solid white;
        background: rgba(255, 255, 255, 0.2);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        font-weight: 700;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .dropdown-menu-modern {
        border: none;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        border-radius: 12px;
        padding: 8px;
    }
    
    .dropdown-item-modern {
        padding: 10px 14px;
        border-radius: 8px;
        font-size: 0.875rem;
        font-weight: 500;
        color: #475569;
        transition: all 0.15s;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .dropdown-item-modern:hover {
        background: #f1f5f9;
        color: #1e293b;
    }
    
    .dropdown-item-modern i {
        width: 18px;
        font-size: 0.9rem;
    }
    
    .dropdown-item-modern.text-danger:hover {
        background: #fef2f2;
        color: #dc2626;
    }
    
    .btn-auth {
        font-weight: 500;
        font-size: 0.875rem;
        padding: 8px 16px;
        border-radius: 10px;
        transition: all 0.2s;
        text-decoration: none;
    }
    
    .btn-login {
        color: #475569;
        border: 1px solid #e2e8f0;
        background: transparent;
    }
    
    .btn-login:hover {
        border-color: #7c3aed;
        color: #7c3aed;
        background: #ffffff;
    }
    
    .btn-register {
        background: #7c3aed;
        color: white !important;
        border: none;
    }
    
    .btn-register:hover {
        background: #7c3aed;
        color: white !important;
    }
    
    /* Bouton Devenir Prestataire */
    .btn-become-provider {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        border-radius: 10px;
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
        font-size: 0.85rem;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: all 0.2s;
        box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.3);
    }
    
    .btn-become-provider:hover {
        background: linear-gradient(135deg, #059669, #047857);
        transform: translateY(-1px);
        box-shadow: 0 6px 12px rgba(16, 185, 129, 0.4);
    }
    
    .btn-become-provider i {
        font-size: 0.9rem;
    }
    
    /* Badge Prestataire actif */
    .btn-provider-badge {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 20px;
        background: linear-gradient(135deg, #dcfce7, #bbf7d0);
        color: #166534;
        font-size: 0.8rem;
        font-weight: 600;
        border: 1px solid #86efac;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .btn-provider-badge:hover {
        background: linear-gradient(135deg, #bbf7d0, #86efac);
        border-color: #4ade80;
    }
    
    .btn-provider-badge i {
        color: #22c55e;
    }

    /* =========================================
       MOBILE RESPONSIVE — STANDALONE HEADER
       ========================================= */
    @media (max-width: 768px) {
        .main-header-modern { height: 56px; }
        .main-header-modern .container-fluid { padding: 0 10px !important; }
        .brand-logo { width: 32px; height: 32px; font-size: 0.9rem; border-radius: 10px; }
        .brand-text { font-size: 1.1rem; }
        .nav-messages-btn { padding: 6px 10px; font-size: 0.82rem; gap: 6px; }
        .nav-icon-btn { padding: 8px; }
        .nav-icon-btn i { font-size: 1rem; }
        .points-badge { padding: 5px 10px; font-size: 0.75rem; }
        .user-dropdown-modern { padding: 6px 10px; border-radius: 10px; gap: 6px; }
        .user-avatar, .user-avatar-placeholder { width: 34px; height: 34px; }
        .user-name-text { font-size: 0.8rem; }
        .dropdown-menu-modern { min-width: 220px !important; max-width: calc(100vw - 16px); }
        .btn-become-provider { padding: 6px 12px; font-size: 0.8rem; }
        .btn-auth { padding: 7px 12px; font-size: 0.82rem; }
    }

    @media (max-width: 576px) {
        .main-header-modern { height: 52px; }
        .main-header-modern .container-fluid { padding: 0 8px !important; }
        .brand-logo { width: 30px; height: 30px; font-size: 0.85rem; }
        .nav-messages-btn span { display: none !important; }
        .nav-messages-btn { padding: 8px; border-radius: 8px; }
        .points-badge { padding: 5px 8px; font-size: 0.72rem; }
        .user-dropdown-modern { padding: 5px 8px; gap: 5px; }
        .user-avatar, .user-avatar-placeholder { width: 30px; height: 30px; font-size: 0.85rem; }
        .user-name-text { display: none !important; }
        .dropdown-chevron { display: none !important; }
        .btn-become-provider span { display: none; }
        .btn-become-provider { padding: 8px; border-radius: 8px; }
        .btn-provider-badge span { display: none; }
        .btn-provider-badge { padding: 6px; border-radius: 8px; }
        .btn-auth { padding: 6px 10px; font-size: 0.78rem; }
    }

    @media (max-width: 420px) {
        .main-header-modern .container-fluid { padding: 0 6px !important; }
        .points-badge span:last-child { display: none; }
        .points-badge { padding: 6px; border-radius: 8px; }
        .nav-icon-btn { padding: 6px; }
    }
</style>

{{-- Include Provider Modals --}}
@auth
    @include('partials.provider-modal')
    @if(Auth::user()->isOAuthUser() && !Auth::user()->profile_completed && !Auth::user()->hasCompletedProOnboarding() && !Auth::user()->hasActiveProSubscription())
        @include('partials.provider-oauth-modal')
    @endif
    @if(session('show_provider_welcome') && !Auth::user()->hasCompletedProOnboarding() && !Auth::user()->hasActiveProSubscription())
        @include('partials.provider-welcome-modal')
    @endif
@endauth