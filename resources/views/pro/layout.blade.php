{{-- Layout principal du tableau de bord professionnel --}}
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Espace Pro - ProxiPro')</title>
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Inter:400,500,600,700,800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    :root {
        --sidebar-width: 270px;
        --sidebar-collapsed: 70px;
        --header-height: 60px;
        --pro-primary: #6366f1;
        --pro-primary-dark: #4f46e5;
        --pro-gradient: linear-gradient(135deg, #6366f1, #8b5cf6);
        --pro-bg: #f1f5f9;
        --pro-card: #ffffff;
        --pro-border: #e2e8f0;
        --pro-text: #1e293b;
        --pro-text-secondary: #64748b;
        --pro-success: #10b981;
        --pro-warning: #f59e0b;
        --pro-danger: #ef4444;
        --pro-info: #3b82f6;
    }

    * { box-sizing: border-box; }

    body {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        background: var(--pro-bg);
        color: var(--pro-text);
        margin: 0;
        overflow-x: hidden;
    }

    /* ===== TOP BAR ===== */
    .pro-topbar {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        height: var(--header-height);
        background: white;
        border-bottom: 1px solid var(--pro-border);
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 1.5rem;
        z-index: 1050;
    }

    .pro-topbar-left {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .pro-topbar-brand {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        text-decoration: none;
        color: var(--pro-text);
    }

    .pro-topbar-brand .brand-icon {
        width: 36px;
        height: 36px;
        background: var(--pro-gradient);
        color: white;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 1.1rem;
    }

    .pro-topbar-brand span {
        font-weight: 700;
        font-size: 1.1rem;
    }

    .pro-topbar-brand .pro-badge {
        background: var(--pro-gradient);
        color: white;
        font-size: 0.65rem;
        padding: 2px 8px;
        border-radius: 20px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .pro-sidebar-toggle {
        background: none;
        border: none;
        font-size: 1.2rem;
        color: var(--pro-text-secondary);
        cursor: pointer;
        padding: 8px;
        border-radius: 8px;
        transition: background 0.2s;
    }

    .pro-sidebar-toggle:hover {
        background: #f1f5f9;
    }

    .pro-topbar-right {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .pro-topbar-btn {
        position: relative;
        background: none;
        border: none;
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--pro-text-secondary);
        cursor: pointer;
        transition: all 0.2s;
    }

    .pro-topbar-btn:hover {
        background: #f1f5f9;
        color: var(--pro-primary);
    }

    .pro-topbar-btn .badge-count {
        position: absolute;
        top: 4px;
        right: 4px;
        width: 18px;
        height: 18px;
        background: var(--pro-danger);
        color: white;
        border-radius: 50%;
        font-size: 0.65rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
    }

    .pro-user-menu {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 4px 12px 4px 4px;
        border-radius: 12px;
        cursor: pointer;
        transition: background 0.2s;
        text-decoration: none;
        color: var(--pro-text);
    }

    .pro-user-menu:hover {
        background: #f1f5f9;
        color: var(--pro-text);
    }

    .pro-user-avatar {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        object-fit: cover;
        background: var(--pro-gradient);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.9rem;
    }

    .pro-user-info {
        display: flex;
        flex-direction: column;
        line-height: 1.2;
    }

    .pro-user-name {
        font-weight: 600;
        font-size: 0.85rem;
    }

    .pro-user-role {
        font-size: 0.7rem;
        color: var(--pro-text-secondary);
    }

    /* ===== SIDEBAR ===== */
    .pro-sidebar {
        position: fixed;
        top: calc(var(--header-height) + 12px);
        left: 0;
        bottom: 0;
        width: var(--sidebar-width);
        background: white;
        border-right: 1px solid var(--pro-border);
        overflow-y: auto;
        overflow-x: hidden;
        z-index: 1040;
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        scrollbar-width: none;
        -ms-overflow-style: none;
        transform: translateX(0);
    }

    .pro-sidebar::-webkit-scrollbar {
        display: none;
    }

    .pro-sidebar-section {
        padding: 1rem 0.75rem;
    }

    .pro-sidebar-section + .pro-sidebar-section {
        border-top: 1px solid var(--pro-border);
    }

    .pro-sidebar-label {
        font-size: 0.68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: var(--pro-text-secondary);
        padding: 0 0.75rem;
        margin-bottom: 0.5rem;
    }

    .pro-nav-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.65rem 0.75rem;
        border-radius: 10px;
        text-decoration: none;
        color: var(--pro-text-secondary);
        font-size: 0.88rem;
        font-weight: 500;
        transition: all 0.2s;
        margin-bottom: 2px;
        position: relative;
    }

    .pro-nav-item:hover {
        background: #f1f5f9;
        color: var(--pro-text);
        text-decoration: none;
    }

    .pro-nav-item.active {
        background: rgba(99, 102, 241, 0.08);
        color: var(--pro-primary);
        font-weight: 600;
    }

    .pro-nav-item.active::before {
        content: '';
        position: absolute;
        left: 0;
        top: 50%;
        transform: translateY(-50%);
        width: 3px;
        height: 60%;
        background: var(--pro-primary);
        border-radius: 0 3px 3px 0;
    }

    .pro-nav-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.9rem;
        flex-shrink: 0;
    }

    .pro-nav-badge {
        margin-left: auto;
        background: var(--pro-danger);
        color: white;
        font-size: 0.65rem;
        padding: 2px 7px;
        border-radius: 10px;
        font-weight: 700;
    }

    .pro-nav-badge.warning { background: var(--pro-warning); }
    .pro-nav-badge.success { background: var(--pro-success); }
    .pro-nav-badge.info { background: var(--pro-info); }

    /* Subscription card in sidebar */
    .pro-sidebar-sub-card {
        margin: 0.5rem 0.75rem;
        padding: 1rem;
        background: var(--pro-gradient);
        border-radius: 12px;
        color: white;
    }

    .pro-sidebar-sub-card h6 {
        font-size: 0.8rem;
        font-weight: 700;
        margin-bottom: 0.25rem;
    }

    .pro-sidebar-sub-card p {
        font-size: 0.7rem;
        opacity: 0.85;
        margin: 0;
    }

    .pro-sidebar-sub-card .btn-upgrade {
        display: block;
        margin-top: 0.75rem;
        padding: 0.5rem;
        background: rgba(255,255,255,0.2);
        border: 1px solid rgba(255,255,255,0.3);
        border-radius: 8px;
        color: white;
        font-size: 0.78rem;
        font-weight: 600;
        text-align: center;
        text-decoration: none;
        transition: background 0.2s;
    }

    .pro-sidebar-sub-card .btn-upgrade:hover {
        background: rgba(255,255,255,0.3);
    }

    /* ===== MAIN CONTENT ===== */
    .pro-main {
        margin-left: var(--sidebar-width);
        margin-top: var(--header-height);
        padding: 1.5rem;
        min-height: calc(100vh - var(--header-height));
        transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* Content header */
    .pro-content-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1.5rem;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .pro-content-header h1 {
        font-size: 1.5rem;
        font-weight: 700;
        margin: 0;
        color: var(--pro-text);
    }

    .pro-content-header .breadcrumb {
        font-size: 0.8rem;
        margin-bottom: 0.25rem;
    }

    /* Cards */
    .pro-card {
        background: var(--pro-card);
        border: 1px solid var(--pro-border);
        border-radius: 14px;
        padding: 1.5rem;
        margin-bottom: 1.25rem;
        transition: box-shadow 0.2s;
    }

    .pro-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.04);
    }

    .pro-card-title {
        font-size: 0.88rem;
        font-weight: 600;
        color: var(--pro-text);
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    /* Stat cards */
    .pro-stat-card {
        background: var(--pro-card);
        border: 1px solid var(--pro-border);
        border-radius: 14px;
        padding: 1.25rem;
        transition: all 0.2s;
    }

    .pro-stat-card:hover {
        border-color: var(--pro-primary);
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.08);
    }

    .pro-stat-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        margin-bottom: 0.75rem;
    }

    .pro-stat-value {
        font-size: 1.5rem;
        font-weight: 800;
        color: var(--pro-text);
        line-height: 1.2;
    }

    .pro-stat-label {
        font-size: 0.78rem;
        color: var(--pro-text-secondary);
        font-weight: 500;
    }

    /* Buttons */
    .btn-pro-primary {
        background: var(--pro-gradient);
        color: white;
        border: none;
        padding: 0.6rem 1.25rem;
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.88rem;
        transition: all 0.2s;
    }

    .btn-pro-primary:hover {
        opacity: 0.9;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        color: white;
    }

    .btn-pro-outline {
        background: transparent;
        color: var(--pro-primary);
        border: 1px solid var(--pro-primary);
        padding: 0.6rem 1.25rem;
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.88rem;
        transition: all 0.2s;
    }

    .btn-pro-outline:hover {
        background: var(--pro-primary);
        color: white;
    }

    /* Status badges */
    .pro-status {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 10px;
        border-radius: 8px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .pro-status-success { background: rgba(16, 185, 129, 0.1); color: #059669; }
    .pro-status-warning { background: rgba(245, 158, 11, 0.1); color: #d97706; }
    .pro-status-danger { background: rgba(239, 68, 68, 0.1); color: #dc2626; }
    .pro-status-info { background: rgba(59, 130, 246, 0.1); color: #2563eb; }
    .pro-status-gray { background: rgba(100, 116, 139, 0.1); color: #475569; }

    /* Tables */
    .pro-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    .pro-table thead th {
        background: #f8fafc;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--pro-text-secondary);
        padding: 0.75rem 1rem;
        border-bottom: 1px solid var(--pro-border);
    }

    .pro-table tbody td {
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #f1f5f9;
        font-size: 0.88rem;
        vertical-align: middle;
    }

    .pro-table tbody tr:hover {
        background: #f8fafc;
    }

    /* Empty state */
    .pro-empty {
        text-align: center;
        padding: 3rem 1rem;
    }

    .pro-empty-icon {
        font-size: 3rem;
        margin-bottom: 1rem;
        opacity: 0.4;
    }

    .pro-empty h5 {
        font-weight: 600;
        color: var(--pro-text);
    }

    .pro-empty p {
        color: var(--pro-text-secondary);
        font-size: 0.9rem;
    }

    /* ===== RESPONSIVE ===== */
    .pro-sidebar-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.3);
        z-index: 1039;
    }

    @media (max-width: 991px) {
        .pro-sidebar {
            transform: translateX(-100%);
        }

        .pro-sidebar.show {
            transform: translateX(0);
        }

        .pro-sidebar-overlay.show {
            display: block;
        }

        .pro-main {
            margin-left: 0;
        }
    }

    @media (min-width: 992px) {
        .pro-sidebar {
            transform: translateX(0) !important;
            display: block !important;
        }

        .pro-main {
            margin-left: var(--sidebar-width) !important;
        }
    }

    @media (max-width: 576px) {
        .pro-main {
            padding: 1rem;
        }

        .pro-content-header h1 {
            font-size: 1.25rem;
        }

        .pro-stat-value {
            font-size: 1.25rem;
        }
    }

    @yield('styles')
    </style>
</head>
<body>
    {{-- TOP BAR --}}
    <nav class="pro-topbar">
        <div class="pro-topbar-left">
            <button class="pro-sidebar-toggle" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
            <a href="{{ route('pro.dashboard') }}" class="pro-topbar-brand">
                <div class="brand-icon">P</div>
                <span>ProxiPro</span>
                <span class="pro-badge">PRO</span>
            </a>
        </div>
        <div class="pro-topbar-right">
            <a href="{{ route('feed') }}" class="pro-topbar-btn" title="Retour au feed">
                <i class="fas fa-home"></i>
            </a>
            <a href="{{ route('messages.index') }}" class="pro-topbar-btn" title="Messages">
                <i class="fas fa-envelope"></i>
                @if(Auth::user()->unreadMessagesCount() > 0)
                    <span class="badge-count">{{ Auth::user()->unreadMessagesCount() }}</span>
                @endif
            </a>
            <button class="pro-topbar-btn" title="Notifications">
                <i class="fas fa-bell"></i>
            </button>
            <div class="dropdown">
                <a href="#" class="pro-user-menu dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    @if(Auth::user()->avatar)
                        <img src="{{ storage_url(Auth::user()->avatar) }}" class="pro-user-avatar" alt="">
                    @else
                        <div class="pro-user-avatar">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</div>
                    @endif
                    <div class="pro-user-info d-none d-md-flex">
                        <span class="pro-user-name">{{ Str::limit(Auth::user()->company_name ?? Auth::user()->name, 18) }}</span>
                        <span class="pro-user-role">{{ Auth::user()->getAccountTypeLabel() }}</span>
                    </div>
                </a>
                <ul class="dropdown-menu dropdown-menu-end mt-2">
                    <li><a class="dropdown-item" href="{{ route('pro.profile') }}"><i class="fas fa-user me-2"></i>Mon profil pro</a></li>
                    <li><a class="dropdown-item" href="{{ route('pro.subscription') }}"><i class="fas fa-crown me-2"></i>Mon abonnement</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="{{ route('feed') }}"><i class="fas fa-home me-2"></i>Retour au feed</a></li>
                    <li><a class="dropdown-item" href="{{ route('settings.index') }}"><i class="fas fa-cog me-2"></i>Paramètres</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="dropdown-item text-danger"><i class="fas fa-sign-out-alt me-2"></i>Déconnexion</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    {{-- SIDEBAR OVERLAY --}}
    <div class="pro-sidebar-overlay" id="sidebarOverlay"></div>

    {{-- SIDEBAR --}}
    <aside class="pro-sidebar" id="proSidebar">
        {{-- Principal --}}
        <div class="pro-sidebar-section">
            <div class="pro-sidebar-label">Principal</div>
            <a href="{{ route('pro.dashboard') }}" class="pro-nav-item {{ request()->routeIs('pro.dashboard') ? 'active' : '' }}">
                <div class="pro-nav-icon" style="background: rgba(99,102,241,0.1); color: #6366f1;">
                    <i class="fas fa-th-large"></i>
                </div>
                Tableau de bord
            </a>
            <a href="{{ route('pro.profile') }}" class="pro-nav-item {{ request()->routeIs('pro.profile*') ? 'active' : '' }}">
                <div class="pro-nav-icon" style="background: rgba(16,185,129,0.1); color: #10b981;">
                    <i class="fas fa-user-tie"></i>
                </div>
                Mon profil
            </a>
        </div>

        {{-- Commercial --}}
        <div class="pro-sidebar-section">
            <div class="pro-sidebar-label">Commercial</div>
            <a href="{{ route('pro.clients') }}" class="pro-nav-item {{ request()->routeIs('pro.clients*') ? 'active' : '' }}">
                <div class="pro-nav-icon" style="background: rgba(59,130,246,0.1); color: #3b82f6;">
                    <i class="fas fa-users"></i>
                </div>
                Mes clients
                @php $clientCount = Auth::user()->proClients()->count(); @endphp
                @if($clientCount > 0)
                    <span class="pro-nav-badge info">{{ $clientCount }}</span>
                @endif
            </a>
            <a href="{{ route('pro.quotes') }}" class="pro-nav-item {{ request()->routeIs('pro.quotes*') ? 'active' : '' }}">
                <div class="pro-nav-icon" style="background: rgba(245,158,11,0.1); color: #f59e0b;">
                    <i class="fas fa-file-alt"></i>
                </div>
                Création de devis
                @php $pendingQuotes = Auth::user()->proQuotes()->where('status', 'pending')->count(); @endphp
                @if($pendingQuotes > 0)
                    <span class="pro-nav-badge warning">{{ $pendingQuotes }}</span>
                @endif
            </a>
            <a href="{{ route('pro.invoices') }}" class="pro-nav-item {{ request()->routeIs('pro.invoices*') ? 'active' : '' }}">
                <div class="pro-nav-icon" style="background: rgba(236,72,153,0.1); color: #ec4899;">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
                Création de facture
                @php $unpaidInvoices = Auth::user()->proInvoices()->where('status', 'sent')->count(); @endphp
                @if($unpaidInvoices > 0)
                    <span class="pro-nav-badge danger">{{ $unpaidInvoices }}</span>
                @endif
            </a>
        </div>

        {{-- Modules --}}
        <div class="pro-sidebar-section">
            <div class="pro-sidebar-label">Modules</div>
            <a href="{{ route('pro.documents') }}" class="pro-nav-item {{ request()->routeIs('pro.documents*') ? 'active' : '' }}">
                <div class="pro-nav-icon" style="background: rgba(168,85,247,0.1); color: #a855f7;">
                    <i class="fas fa-folder-open"></i>
                </div>
                Documents
            </a>
            <a href="{{ route('pro.analytics') }}" class="pro-nav-item {{ request()->routeIs('pro.analytics*') ? 'active' : '' }}">
                <div class="pro-nav-icon" style="background: rgba(59,130,246,0.1); color: #3b82f6;">
                    <i class="fas fa-chart-line"></i>
                </div>
                Statistiques
            </a>
            <a href="{{ route('pro.agenda') }}" class="pro-nav-item {{ request()->routeIs('pro.agenda*') ? 'active' : '' }}">
                <div class="pro-nav-icon" style="background: rgba(245,158,11,0.1); color: #f59e0b;">
                    <i class="fas fa-calendar-check"></i>
                </div>
                Agenda
            </a>
            <a href="{{ route('pro.account-status') }}" class="pro-nav-item {{ request()->routeIs('pro.account-status*') ? 'active' : '' }}">
                <div class="pro-nav-icon" style="background: rgba(20,184,166,0.1); color: #14b8a6;">
                    <i class="fas fa-exchange-alt"></i>
                </div>
                Statut du compte
            </a>
            <a href="{{ route('pro.subscription') }}" class="pro-nav-item {{ request()->routeIs('pro.subscription*') ? 'active' : '' }}">
                <div class="pro-nav-icon" style="background: rgba(249,115,22,0.1); color: #f97316;">
                    <i class="fas fa-crown"></i>
                </div>
                Abonnement
            </a>
        </div>

        {{-- Liens rapides --}}
        <div class="pro-sidebar-section">
            <div class="pro-sidebar-label">Liens rapides</div>
            <a href="{{ route('ads.create') }}" class="pro-nav-item">
                <div class="pro-nav-icon" style="background: rgba(34,197,94,0.1); color: #22c55e;">
                    <i class="fas fa-plus-circle"></i>
                </div>
                Publier une annonce
            </a>
            <a href="{{ route('messages.index') }}" class="pro-nav-item">
                <div class="pro-nav-icon" style="background: rgba(59,130,246,0.1); color: #3b82f6;">
                    <i class="fas fa-comments"></i>
                </div>
                Messagerie
                @if(Auth::user()->unreadMessagesCount() > 0)
                    <span class="pro-nav-badge danger">{{ Auth::user()->unreadMessagesCount() }}</span>
                @endif
            </a>
            <a href="{{ route('feed') }}" class="pro-nav-item">
                <div class="pro-nav-icon" style="background: rgba(100,116,139,0.1); color: #64748b;">
                    <i class="fas fa-arrow-left"></i>
                </div>
                Retour au feed
            </a>
        </div>

        {{-- Subscription card --}}
        @php $activeSub = Auth::user()->proSubscription; @endphp
        <div class="pro-sidebar-section">
            @if($activeSub)
                <div class="pro-sidebar-sub-card">
                    <h6><i class="fas fa-crown me-1"></i> {{ $activeSub->getPlanLabel() }}</h6>
                    <p>Actif jusqu'au {{ $activeSub->ends_at->format('d/m/Y') }}</p>
                    <p>{{ $activeSub->daysRemaining() }} jours restants</p>
                </div>
            @else
                <div class="pro-sidebar-sub-card" style="background: linear-gradient(135deg, #f97316, #ef4444);">
                    <h6><i class="fas fa-star me-1"></i> Pas d'abonnement</h6>
                    <p>Activez votre abonnement pour recevoir des demandes clients</p>
                    <a href="{{ route('pro.subscription') }}" class="btn-upgrade">
                        <i class="fas fa-rocket me-1"></i> S'abonner
                    </a>
                </div>
            @endif
        </div>
    </aside>

    {{-- MAIN CONTENT --}}
    <main class="pro-main">
        {{-- Flash messages --}}
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" style="border-radius: 12px; border: none; background: rgba(16,185,129,0.1); color: #059669;">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" style="border-radius: 12px; border: none; background: rgba(239,68,68,0.1); color: #dc2626;">
                <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" style="border-radius: 12px; border: none;">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('content')
    </main>

    @include('partials.provider-modal')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Sidebar toggle
    const sidebar = document.getElementById('proSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const toggle = document.getElementById('sidebarToggle');

    toggle?.addEventListener('click', () => {
        sidebar.classList.toggle('show');
        overlay.classList.toggle('show');
    });

    overlay?.addEventListener('click', () => {
        sidebar.classList.remove('show');
        overlay.classList.remove('show');
    });
    </script>
    @yield('scripts')
</body>
</html>
