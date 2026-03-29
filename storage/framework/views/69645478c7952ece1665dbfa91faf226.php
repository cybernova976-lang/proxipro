




<aside class="sidebar-nav" id="sidebarNav">
    
    <nav class="sidebar-menu">
        <div class="menu-section">
            <span class="menu-label">Navigation</span>
            
            <a href="#" class="menu-item active" data-dashboard-section="overview" onclick="dashboardNav('overview'); return false;">
                <div class="menu-icon">
                    <i class="fas fa-th-large"></i>
                </div>
                <span class="menu-text">Tableau de bord</span>
            </a>

            <a href="#" class="menu-item" data-dashboard-section="profile" onclick="dashboardNav('profile'); return false;">
                <div class="menu-icon">
                    <i class="fas fa-user-circle"></i>
                </div>
                <span class="menu-text">Profil</span>
            </a>

            <a href="#" class="menu-item" data-dashboard-section="my-ads" onclick="dashboardNav('my-ads'); return false;">
                <div class="menu-icon">
                    <i class="fas fa-briefcase"></i>
                </div>
                <span class="menu-text">Mes annonces</span>
                <?php
                    $myAdsCount = \App\Models\Ad::where('user_id', Auth::id())->count();
                ?>
                <?php if($myAdsCount > 0): ?>
                    <span class="menu-badge"><?php echo e($myAdsCount); ?></span>
                <?php endif; ?>
            </a>

            <a href="#" class="menu-item menu-item-highlight" data-dashboard-section="create-ad" onclick="dashboardNav('create-ad'); return false;">
                <div class="menu-icon highlight">
                    <i class="fas fa-plus-circle"></i>
                </div>
                <span class="menu-text">Publier une Offre</span>
            </a>
        </div>

        <div class="menu-section">
            <span class="menu-label">Communication</span>
            
            <a href="#" class="menu-item" data-dashboard-section="messages" onclick="dashboardNav('messages'); return false;">
                <div class="menu-icon">
                    <i class="fas fa-envelope"></i>
                </div>
                <span class="menu-text">Messages</span>
                <?php
                    $unreadMessages = Auth::user()->unreadMessagesCount();
                ?>
                <?php if($unreadMessages > 0): ?>
                    <span class="menu-badge urgent"><?php echo e($unreadMessages > 99 ? '99+' : $unreadMessages); ?></span>
                <?php endif; ?>
            </a>

            <a href="<?php echo e(route('contact.index')); ?>" class="menu-item <?php echo e(request()->routeIs('contact.*') ? 'active' : ''); ?>">
                <div class="menu-icon">
                    <i class="fas fa-headset"></i>
                </div>
                <span class="menu-text">Contact</span>
            </a>
        </div>

        <div class="menu-section">
            <span class="menu-label">Mon compte</span>
            
            <a href="#" class="menu-item" data-dashboard-section="points" onclick="dashboardNav('points'); return false;">
                <div class="menu-icon">
                    <i class="fas fa-coins"></i>
                </div>
                <span class="menu-text">Mes Points</span>
                <span class="menu-badge points"><?php echo e(Auth::user()->available_points ?? 0); ?></span>
            </a>

            <a href="<?php echo e(route('pricing.index')); ?>" class="menu-item <?php echo e(request()->routeIs('pricing.*') ? 'active' : ''); ?>">
                <div class="menu-icon">
                    <i class="fas fa-tags"></i>
                </div>
                <span class="menu-text">Tarifs & Points</span>
            </a>

            <a href="#" class="menu-item" data-dashboard-section="transactions" onclick="dashboardNav('transactions'); return false;">
                <div class="menu-icon">
                    <i class="fas fa-receipt"></i>
                </div>
                <span class="menu-text">Transactions</span>
            </a>
        </div>
    </nav>

    
    <div class="sidebar-footer">
        <a href="<?php echo e(route('feed')); ?>" class="footer-link">
            <i class="fas fa-home"></i>
            <span>Retour à l'accueil</span>
        </a>
        <a href="#" class="footer-link" data-dashboard-section="settings" onclick="dashboardNav('settings'); return false;">
            <i class="fas fa-cog"></i>
            <span>Paramètres</span>
        </a>
    </div>
</aside>


<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>


<button class="sidebar-toggle" id="sidebarToggle" onclick="toggleSidebar()">
    <i class="fas fa-bars"></i>
</button>

<style>
/* ============================================== */
/* SIDEBAR NAVIGATION - THÈME CLAIR              */
/* ============================================== */

:root {
    --sidebar-width: 260px;
    --sidebar-bg: #ffffff;
    --sidebar-bg-gradient: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    --sidebar-text: #64748b;
    --sidebar-text-dark: #1e293b;
    --sidebar-accent: #6366f1;
    --sidebar-accent-light: #e0e7ff;
    --sidebar-hover: #f1f5f9;
    --sidebar-active: #e0e7ff;
    --sidebar-border: #e2e8f0;
}

/* When sidebar is present, header extends full width */
.header-modern.sticky-top {
    margin-left: 0;
    width: 100%;
    z-index: 1040;
}

/* Main Sidebar Container - starts below header with gap */
.sidebar-nav {
    position: fixed;
    left: 0;
    top: 102px; /* header 90px + 12px gap */
    width: var(--sidebar-width);
    height: calc(100vh - 102px);
    background: var(--sidebar-bg-gradient);
    display: flex;
    flex-direction: column;
    z-index: 1000;
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 4px 0 20px rgba(0, 0, 0, 0.06);
    border-right: 1px solid var(--sidebar-border);
    overflow: hidden;
}

/* Menu Section */

/* User Card Section - Removed */
.sidebar-user {
    display: none;
}

.user-card {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 14px;
    background: var(--sidebar-accent-light);
    border-radius: 12px;
    border: 1px solid rgba(99, 102, 241, 0.15);
    position: relative;
}

.user-avatar-sidebar {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    object-fit: cover;
    border: 2px solid var(--sidebar-accent);
    box-shadow: 0 3px 10px rgba(99, 102, 241, 0.2);
}

.user-avatar-placeholder-sidebar {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    font-weight: 700;
    box-shadow: 0 3px 10px rgba(99, 102, 241, 0.3);
}

.user-info {
    display: flex;
    flex-direction: column;
    flex: 1;
    min-width: 0;
}

.user-name {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--sidebar-text-dark);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.user-email {
    font-size: 0.7rem;
    color: var(--sidebar-text);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.user-status {
    width: 9px;
    height: 9px;
    border-radius: 50%;
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
}

.user-status.online {
    background: #22c55e;
    box-shadow: 0 0 8px rgba(34, 197, 94, 0.5);
}

/* Menu Sections */
.sidebar-menu {
    flex: 1;
    padding: 16px 0;
    overflow: hidden;
}

.menu-section {
    margin-bottom: 20px;
}

.menu-label {
    display: block;
    padding: 0 20px;
    margin-bottom: 8px;
    font-size: 0.68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: var(--sidebar-text);
    opacity: 0.7;
}

/* Menu Items */
.menu-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 11px 20px;
    margin: 3px 10px;
    border-radius: 10px;
    text-decoration: none;
    color: var(--sidebar-text);
    font-size: 0.88rem;
    font-weight: 500;
    transition: all 0.2s ease;
    position: relative;
}

.menu-item:hover {
    background: var(--sidebar-hover);
    color: var(--sidebar-text-dark);
    transform: translateX(3px);
}

.menu-item.active {
    background: var(--sidebar-active);
    color: var(--sidebar-text-dark);
    box-shadow: inset 3px 0 0 var(--sidebar-accent);
    font-weight: 600;
}

.menu-item.active .menu-icon {
    background: var(--sidebar-accent);
    color: white;
}

.menu-icon {
    width: 34px;
    height: 34px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 9px;
    background: #f1f5f9;
    font-size: 0.92rem;
    transition: all 0.2s ease;
    color: var(--sidebar-text);
}

.menu-icon.highlight {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    color: white !important;
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
}

.menu-item:hover .menu-icon {
    background: var(--sidebar-accent-light);
    transform: scale(1.05);
    color: var(--sidebar-accent);
}

.menu-item.active .menu-icon {
    background: var(--sidebar-accent);
    color: white;
}

.menu-text {
    flex: 1;
    white-space: nowrap;
}

/* Badges */
.menu-badge {
    padding: 3px 9px;
    border-radius: 16px;
    font-size: 0.68rem;
    font-weight: 700;
    background: #e2e8f0;
    color: var(--sidebar-text);
}

.menu-badge.urgent {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
    animation: pulse-badge 2s infinite;
}

.menu-badge.points {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: white;
}

.menu-badge.premium {
    background: linear-gradient(135deg, #8b5cf6 0%, #a855f7 100%);
    color: white;
}

@keyframes pulse-badge {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

/* Sidebar Footer */
.sidebar-footer {
    padding: 14px;
    border-top: 1px solid var(--sidebar-border);
    display: flex;
    flex-direction: column;
    gap: 3px;
    background: #fafbfc;
}

.footer-link {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 14px;
    border-radius: 9px;
    text-decoration: none;
    color: var(--sidebar-text);
    font-size: 0.82rem;
    font-weight: 500;
    transition: all 0.2s ease;
}

.footer-link:hover {
    background: var(--sidebar-hover);
    color: var(--sidebar-text-dark);
}

.footer-link i {
    width: 18px;
    text-align: center;
    font-size: 0.85rem;
}

/* Mobile Toggle Button */
.sidebar-toggle {
    display: none;
    position: fixed;
    left: 16px;
    bottom: 16px;
    width: 52px;
    height: 52px;
    border-radius: 50%;
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    border: none;
    color: white;
    font-size: 1.2rem;
    cursor: pointer;
    z-index: 999;
    box-shadow: 0 6px 20px rgba(99, 102, 241, 0.4);
    transition: all 0.3s ease;
}

.sidebar-toggle:hover {
    transform: scale(1.08);
    box-shadow: 0 10px 30px rgba(99, 102, 241, 0.5);
}

/* Mobile Overlay */
.sidebar-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.4);
    backdrop-filter: blur(3px);
    z-index: 999;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.sidebar-overlay.active {
    opacity: 1;
}

/* Main Content Area Adjustment */
.main-content-with-sidebar {
    margin-left: var(--sidebar-width);
    min-height: 100vh;
    transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Responsive */
@media (max-width: 1024px) {
    .sidebar-nav {
        transform: translateX(-100%);
    }

    .sidebar-nav.active {
        transform: translateX(0);
    }

    .sidebar-toggle {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .sidebar-overlay {
        display: block;
        pointer-events: none;
    }

    .sidebar-overlay.active {
        pointer-events: all;
    }

    .main-content-with-sidebar {
        margin-left: 0;
    }

    .header-modern.sticky-top {
        margin-left: 0;
        width: 100%;
    }
}

@media (max-width: 768px) {
    .sidebar-nav {
        width: 280px;
        top: 0;
        height: 100vh;
    }
    .sidebar-toggle {
        width: 46px;
        height: 46px;
        left: 12px;
        bottom: 12px;
        font-size: 1.1rem;
    }
    .sidebar-menu {
        padding: 12px;
    }
    .menu-item {
        padding: 10px 12px;
        font-size: 0.88rem;
    }
    .menu-icon {
        width: 32px;
        height: 32px;
        font-size: 0.85rem;
    }
    .menu-label {
        font-size: 0.68rem;
    }
    .sidebar-footer {
        padding: 10px 12px;
    }
    .footer-link {
        font-size: 0.82rem;
        padding: 8px 10px;
    }
}

@media (max-width: 480px) {
    .sidebar-nav {
        width: 100%;
    }
    .sidebar-toggle {
        width: 42px;
        height: 42px;
        left: 10px;
        bottom: 10px;
        font-size: 1rem;
    }
}
</style>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebarNav');
    const overlay = document.getElementById('sidebarOverlay');
    
    sidebar.classList.toggle('active');
    overlay.classList.toggle('active');
    
    document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
}

window.addEventListener('resize', function() {
    if (window.innerWidth > 1024) {
        const sidebar = document.getElementById('sidebarNav');
        const overlay = document.getElementById('sidebarOverlay');
        if (sidebar) sidebar.classList.remove('active');
        if (overlay) overlay.classList.remove('active');
        document.body.style.overflow = '';
    }
});

// ===== DASHBOARD SPA NAVIGATION =====
var currentSection = 'overview';
var isNavigating = false;

function dashboardNav(section) {
    if (!section) return;
    // Prevent double-clicks while a fetch is in progress
    if (isNavigating) return;

    // Close mobile sidebar
    if (window.innerWidth <= 1024) {
        toggleSidebar();
    }

    // Update active state in sidebar
    document.querySelectorAll('.menu-item[data-dashboard-section], .footer-link[data-dashboard-section]').forEach(function(item) {
        item.classList.remove('active');
    });
    var activeItem = document.querySelector('[data-dashboard-section="' + section + '"]');
    if (activeItem) activeItem.classList.add('active');

    currentSection = section;

    // Find content area
    var contentArea = document.getElementById('dashboardContent');
    if (!contentArea) {
        // Not on dashboard page - redirect to dashboard
        window.location.href = '/home#' + section;
        return;
    }

    // Update URL without reload
    var newUrl = '/home#' + section;
    history.pushState({ section: section }, '', newUrl);

    isNavigating = true;

    // Safety timeout: reset lock after 15s in case fetch hangs
    var safetyTimer = setTimeout(function() {
        isNavigating = false;
        var existingLoader = document.getElementById('dashboardLoader');
        if (existingLoader) existingLoader.remove();
    }, 15000);

    // Show loading spinner overlay
    contentArea.style.position = 'relative';
    var loader = document.createElement('div');
    loader.id = 'dashboardLoader';
    loader.style.cssText = 'position:absolute;top:0;left:0;right:0;bottom:0;background:rgba(255,255,255,0.7);display:flex;align-items:center;justify-content:center;z-index:10;min-height:200px;';
    loader.innerHTML = '<div style="text-align:center;"><div class="spinner-border text-primary" role="status" style="width:2.5rem;height:2.5rem;"></div><p style="margin-top:12px;color:#64748b;font-size:0.9rem;">Chargement...</p></div>';
    contentArea.appendChild(loader);

    // Fetch content
    fetch('/dashboard/' + encodeURIComponent(section), {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'text/html'
        },
        credentials: 'same-origin'
    })
    .then(function(response) {
        if (!response.ok) throw new Error('HTTP ' + response.status);
        return response.text();
    })
    .then(function(html) {
        clearTimeout(safetyTimer);

        // Remove loader
        var existingLoader = document.getElementById('dashboardLoader');
        if (existingLoader) existingLoader.remove();

        // Set content
        contentArea.innerHTML = html;

        // Execute inline scripts from loaded content
        var scripts = contentArea.querySelectorAll('script');
        scripts.forEach(function(oldScript) {
            var newScript = document.createElement('script');
            if (oldScript.src) {
                newScript.src = oldScript.src;
            } else {
                newScript.textContent = oldScript.textContent;
            }
            oldScript.parentNode.replaceChild(newScript, oldScript);
        });

        // Scroll to top of content
        window.scrollTo({ top: 0, behavior: 'smooth' });
        isNavigating = false;
    })
    .catch(function(error) {
        clearTimeout(safetyTimer);

        // Remove loader
        var existingLoader = document.getElementById('dashboardLoader');
        if (existingLoader) existingLoader.remove();

        contentArea.innerHTML = '<div class="container py-5 text-center"><div class="alert alert-danger" style="border-radius:14px;"><i class="fas fa-exclamation-triangle me-2"></i>Erreur de chargement. <a href="#" onclick="dashboardNav(\'' + section + '\'); return false;" style="font-weight:600;">Réessayer</a></div></div>';
        isNavigating = false;
    });
}

// Handle browser back/forward
window.addEventListener('popstate', function(event) {
    if (event.state && event.state.section) {
        isNavigating = false; // Reset lock for back/forward
        dashboardNav(event.state.section);
    } else {
        var hash = window.location.hash.replace('#', '');
        if (hash && hash !== currentSection) {
            isNavigating = false;
            dashboardNav(hash);
        }
    }
});

// Load section from hash on page load
document.addEventListener('DOMContentLoaded', function() {
    var hash = window.location.hash.replace('#', '');
    if (hash && hash !== 'overview' && hash !== '') {
        dashboardNav(hash);
    }
});
</script>
<?php /**PATH C:\Users\PC\Desktop\MASSIWANI V2\resources\views/partials/sidebar.blade.php ENDPATH**/ ?>