<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - @yield('title', 'ProxiPro')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --sidebar-width: 250px;
        }
        
        body {
            background-color: #f8f9fa;
        }
        
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            background: linear-gradient(180deg, #2c3e50, #1a252f);
            color: white;
            transition: all 0.3s;
        }
        
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 20px;
            position: relative;
            z-index: 1;
        }
        
        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar-menu li {
            margin: 5px 0;
        }
        
        .sidebar-menu a {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            padding: 10px 20px;
            display: block;
            transition: all 0.3s;
        }
        
        .sidebar-menu a:hover, .sidebar-menu a.active {
            color: white;
            background: rgba(255,255,255,0.1);
            border-left: 4px solid #3498db;
        }
        
        .stat-card {
            border-radius: 10px;
            border: none;
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        @media (max-width: 768px) {
            .sidebar {
                margin-left: -250px;
                z-index: 1050;
                overflow-y: auto;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .sidebar.active {
                margin-left: 0;
            }

            .sidebar-close-btn {
                display: block !important;
            }
        }

        .sidebar-close-btn {
            display: none;
            background: none;
            border: none;
            color: rgba(255,255,255,0.8);
            font-size: 1.2rem;
            cursor: pointer;
            padding: 0;
            line-height: 1;
        }

        .sidebar-close-btn:hover {
            color: white;
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1040;
        }

        .sidebar-overlay.active {
            display: block;
        }
    </style>
</head>
<body class="device-{{ $deviceType ?? 'desktop' }}{{ ($isMobile ?? false) ? ' is-mobile' : '' }}">
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header d-flex align-items-start justify-content-between">
            <div>
                <h4 class="mb-0">
                    <i class="fas fa-crown me-2"></i>Admin Panel
                </h4>
                <small class="text-muted">ProxiPro Platform</small>
            </div>
            <button class="sidebar-close-btn d-md-none" id="sidebarClose" aria-label="Fermer le menu">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <ul class="sidebar-menu mt-4">
            <li>
                <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="fas fa-tachometer-alt me-2"></i> Tableau de bord
                </a>
            </li>
            <li>
                <a href="{{ route('admin.users') }}" class="{{ request()->routeIs('admin.users*') ? 'active' : '' }}">
                    <i class="fas fa-users me-2"></i> Utilisateurs
                </a>
            </li>
            <li>
                <a href="{{ route('admin.ads') }}" class="{{ request()->routeIs('admin.ads*') ? 'active' : '' }}">
                    <i class="fas fa-bullhorn me-2"></i> Annonces
                </a>
            </li>
            <li>
                @php
                    $activeBoostsCount = \App\Models\Ad::where('is_boosted', true)->where('boost_end', '>', now())->count()
                        + \App\Models\Ad::where('is_urgent', true)->where(function($q) { $q->whereNull('urgent_until')->orWhere('urgent_until', '>', now()); })->count();
                @endphp
                <a href="{{ route('admin.boosts') }}" class="{{ request()->routeIs('admin.boosts*') ? 'active' : '' }}" style="position: relative;">
                    <i class="fas fa-rocket me-2" style="color: #f59e0b;"></i> Boosts & Urgents
                    @if($activeBoostsCount > 0)
                        <span class="badge bg-warning text-dark rounded-pill" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); font-size: 0.7rem;">{{ $activeBoostsCount }}</span>
                    @endif
                </a>
            </li>
            <li>
                @php
                    $pendingVerifCount = \App\Models\IdentityVerification::whereIn('status', ['pending', 'returned'])->count();
                @endphp
                <a href="{{ route('admin.verifications') }}" class="{{ request()->routeIs('admin.verifications*') ? 'active' : '' }}" style="position: relative;">
                    <i class="fas fa-shield-alt me-2" style="color: #10b981;"></i> Vérifications
                    @if($pendingVerifCount > 0)
                        <span class="badge bg-danger rounded-pill" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); font-size: 0.7rem;">{{ $pendingVerifCount }}</span>
                    @endif
                </a>
            </li>
            <li>
                <a href="{{ route('admin.subscriptions') }}" class="{{ request()->routeIs('admin.subscriptions*') ? 'active' : '' }}">
                    <i class="fas fa-crown me-2" style="color: #f59e0b;"></i> Abonnements
                </a>
            </li>
            <li>
                <a href="{{ route('admin.deleted-accounts') }}" class="{{ request()->routeIs('admin.deleted-accounts') ? 'active' : '' }}">
                    <i class="fas fa-trash me-2"></i> Comptes supprimés
                </a>
            </li>
            <li>
                <a href="{{ route('admin.stats') }}" class="{{ request()->routeIs('admin.stats') ? 'active' : '' }}">
                    <i class="fas fa-chart-bar me-2"></i> Statistiques
                </a>
            </li>
            <li>
                <a href="{{ route('admin.advertisements') }}" class="{{ request()->routeIs('admin.advertisements*') ? 'active' : '' }}">
                    <i class="fas fa-ad me-2" style="color: #f59e0b;"></i> Publicités
                </a>
            </li>
            <li>
                @php
                    $pendingReportsCount = \App\Models\Report::where('status', 'pending')->count();
                @endphp
                <a href="{{ route('admin.reports') }}" class="{{ request()->routeIs('admin.reports*') ? 'active' : '' }}" style="position: relative;">
                    <i class="fas fa-flag me-2" style="color: #e41e3f;"></i> Signalements
                    @if($pendingReportsCount > 0)
                        <span class="badge bg-danger rounded-pill" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); font-size: 0.7rem;">{{ $pendingReportsCount }}</span>
                    @endif
                </a>
            </li>
            <li>
                @php
                    $pendingContactCount = \App\Models\ContactMessage::where('status', 'pending')->count();
                @endphp
                <a href="{{ route('admin.contact-messages') }}" class="{{ request()->routeIs('admin.contact-messages*') ? 'active' : '' }}" style="position: relative;">
                    <i class="fas fa-envelope me-2" style="color: #3b82f6;"></i> Messages contact
                    @if($pendingContactCount > 0)
                        <span class="badge bg-danger rounded-pill" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); font-size: 0.7rem;">{{ $pendingContactCount }}</span>
                    @endif
                </a>
            </li>
            <li>
                <a href="{{ route('admin.settings') }}" class="{{ request()->routeIs('admin.settings') ? 'active' : '' }}">
                    <i class="fas fa-cog me-2"></i> Paramètres
                </a>
            </li>
            @if(Auth::user()->email === config('admin.principal_admin.email'))
            <li class="mt-3">
                <a href="{{ route('admin.admins') }}" class="{{ request()->routeIs('admin.admins*') ? 'active' : '' }}" style="border-left: 3px solid #f59e0b;">
                    <i class="fas fa-user-shield me-2" style="color: #f59e0b;"></i> Gestion Admins
                </a>
            </li>
            @endif
            <li class="mt-4">
                <a href="{{ url('/') }}">
                    <i class="fas fa-arrow-left me-2"></i> Retour au site
                </a>
            </li>
            <li>
                <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    <i class="fas fa-sign-out-alt me-2"></i> Déconnexion
                </a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                    @csrf
                </form>
            </li>
        </ul>
    </div>

    <!-- Overlay mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Main Content -->
    <div class="main-content">
        <nav class="navbar navbar-light bg-white shadow-sm rounded mb-4">
            <div class="container-fluid">
                <button class="btn btn-outline-primary d-md-none" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
                
                <div class="d-flex align-items-center">
                    <span class="navbar-text me-3">
                        <i class="fas fa-user-shield me-2"></i>
                        {{ Auth::user()->name }}
                    </span>
                    <span class="badge bg-success">Administrateur</span>
                </div>
            </div>
        </nav>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Contenu de la page -->
        <div class="container-fluid">
            @yield('content')
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar sur mobile
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const toggleBtn = document.getElementById('sidebarToggle');
        const closeBtn = document.getElementById('sidebarClose');

        function openSidebar() {
            sidebar.classList.add('active');
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeSidebar() {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        }

        if (toggleBtn) {
            toggleBtn.addEventListener('click', function() {
                if (sidebar.classList.contains('active')) {
                    closeSidebar();
                } else {
                    openSidebar();
                }
            });
        }

        if (closeBtn) {
            closeBtn.addEventListener('click', closeSidebar);
        }

        if (overlay) {
            overlay.addEventListener('click', closeSidebar);
        }
    </script>
</body>
</html>
