{{-- Barre du haut : la marque, la page active, et trois accès (feed, messages, compte). --}}
<nav class="pro-topbar" aria-label="Navigation de l'espace professionnel">
    <div class="pro-topbar-left">
        @if($proNavGroups !== [])
            <button type="button" class="pro-sidebar-toggle" id="sidebarToggle" aria-label="Ouvrir le menu de l'espace professionnel" aria-controls="proSidebar" aria-expanded="false">
                <i class="fas fa-bars" aria-hidden="true"></i>
            </button>
        @endif
        <a href="{{ route('pro.dashboard') }}" class="pro-topbar-brand">
            <span class="brand-icon"><x-brand-mark :size="34" /></span>
            <span class="pro-topbar-brand__name">{{ config('app.name', 'Prokejem') }}</span>
            <span class="pro-badge">PRO</span>
        </a>
    </div>

    <div class="pro-topbar-context" aria-label="Page active">
        <span class="pro-topbar-workspace">Espace professionnel</span>
        <span class="pro-topbar-page-title">{{ $proPageTitle }}</span>
    </div>

    <div class="pro-topbar-right">
        <a href="{{ route('feed') }}" class="pro-topbar-action pro-topbar-return" aria-label="Retour au feed">
            <i class="fas fa-arrow-left" aria-hidden="true"></i>
            <span class="pro-topbar-action-label">Retour au feed</span>
        </a>
        <a href="{{ route('messages.index') }}" class="pro-topbar-action" aria-label="Messages{{ $proUnreadMessages > 0 ? ' ('.$proUnreadMessages.' non lus)' : '' }}">
            <i class="fas fa-envelope" aria-hidden="true"></i>
            <span class="pro-topbar-action-label">Messages</span>
            @if($proUnreadMessages > 0)
                <span class="badge-count" aria-hidden="true">{{ $proUnreadMessages }}</span>
            @endif
        </a>
        {{-- Cloche : mène à la page des notifications, avec le nombre de non lues. --}}
        <a href="{{ route('notifications.index') }}" class="pro-topbar-action pro-topbar-bell" data-pro-notifications
           aria-label="Notifications{{ $proUnreadNotifications > 0 ? ' ('.$proUnreadNotifications.' non lues)' : '' }}">
            <i class="fas fa-bell" aria-hidden="true"></i>
            @if($proUnreadNotifications > 0)
                <span class="badge-count" aria-hidden="true">{{ $proUnreadNotifications > 99 ? '99+' : $proUnreadNotifications }}</span>
            @endif
        </a>
        <div class="dropdown">
            <a href="#" class="pro-user-menu dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Mon compte">
                @if(Auth::user()->avatar)
                    <img src="{{ storage_url(Auth::user()->avatar) }}" class="pro-user-avatar" alt="Photo de profil de {{ Auth::user()->name }}">
                @else
                    <span class="pro-user-avatar">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</span>
                @endif
                <span class="pro-user-info d-none d-md-flex">
                    <span class="pro-user-name">{{ Str::limit(Auth::user()->company_name ?? Auth::user()->name, 18) }}</span>
                    <span class="pro-user-role">{{ Auth::user()->getAccountTypeLabel() }}</span>
                </span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end mt-2">
                <li><a class="dropdown-item" href="{{ route('profile.show') }}"><i class="fas fa-user me-2" aria-hidden="true"></i>Mon profil public</a></li>
                <li><a class="dropdown-item" href="{{ route('settings.index') }}"><i class="fas fa-gear me-2" aria-hidden="true"></i>Paramètres du compte</a></li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="dropdown-item text-danger"><i class="fas fa-right-from-bracket me-2" aria-hidden="true"></i>Déconnexion</button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</nav>
