<?php

namespace Tests\Feature\Site;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileExperienceFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_user_agent_is_explicitly_detected_in_the_main_layout(): void
    {
        $iphoneUserAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) '
            .'AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 Mobile/15E148 Safari/604.1';

        $this->withHeader('User-Agent', $iphoneUserAgent)
            ->get(route('ads.index'))
            ->assertOk()
            ->assertSee('body class="device-mobile is-mobile"', false);
    }

    public function test_home_mobile_navigation_is_accessible_and_avoids_the_pwa_control(): void
    {
        $response = $this->get(route('homepage'));

        $response->assertOk()
            ->assertSee('aria-controls="mobileMenu"', false)
            ->assertSee('aria-expanded="false"', false)
            ->assertSee('id="mobileMenu" aria-hidden="true"', false)
            ->assertSee('aria-label="Revenir en haut de la page"', false)
            ->assertSee('html.pwa-install-available #scrollTopBtn', false);
    }

    public function test_mobile_messaging_uses_dynamic_viewport_and_safe_input_spacing(): void
    {
        $indexView = file_get_contents(resource_path('views/messages/index.blade.php'));
        $conversationView = file_get_contents(resource_path('views/messages/show.blade.php'));
        $pwaInstall = file_get_contents(resource_path('views/partials/pwa-install.blade.php'));

        $this->assertStringContainsString('height: calc(100dvh - 68px);', $indexView);
        $this->assertStringContainsString('height: calc(100dvh - 68px);', $conversationView);
        $this->assertStringContainsString('env(safe-area-inset-bottom)', $conversationView);
        $this->assertStringContainsString('.message-input-wrapper', $conversationView);
        $this->assertStringContainsString('min-width: 0;', $conversationView);
        $this->assertStringContainsString("request()->routeIs('messages.*')", $pwaInstall);
        $this->assertStringContainsString("classList.add('pwa-install-available')", $pwaInstall);
    }

    public function test_dashboard_navigation_does_not_hijack_settings_page_anchors(): void
    {
        $sidebar = file_get_contents(resource_path('views/partials/sidebar.blade.php'));

        $this->assertStringContainsString('if (!section || !dashboardSections.includes(section)) return;', $sidebar);
        $this->assertStringContainsString("if (!document.getElementById('dashboardContent')) return;", $sidebar);
        $this->assertStringContainsString('closeDashboardSidebar();', $sidebar);
        $this->assertStringNotContainsString("if (window.innerWidth <= 1024) {\n        toggleSidebar();", $sidebar);
    }

    public function test_admin_mobile_sidebar_is_fully_hidden_outside_the_viewport(): void
    {
        $layout = file_get_contents(resource_path('views/admin/layouts/app.blade.php'));

        $this->assertStringContainsString('transform: translateX(-100%);', $layout);
        $this->assertStringContainsString('transform: translateX(0);', $layout);
        $this->assertStringContainsString('aria-controls="adminSidebar" aria-expanded="false"', $layout);
        $this->assertStringNotContainsString('margin-left: -250px;', $layout);
    }

    public function test_admin_layout_offers_a_direct_return_to_the_feed(): void
    {
        $layout = file_get_contents(resource_path('views/admin/layouts/app.blade.php'));

        $this->assertSame(2, substr_count($layout, 'href="{{ route(\'feed\') }}"'));
        $this->assertStringContainsString('id="adminFeedReturn"', $layout);
        $this->assertStringContainsString('Retourner à la page d’accueil feed', $layout);
        $this->assertStringContainsString('admin-feed-link-label', $layout);
    }

    public function test_pro_subscription_uses_the_desktop_workspace_without_mobile_chrome(): void
    {
        $layout = file_get_contents(resource_path('views/pro/layout.blade.php'));
        $subscription = file_get_contents(resource_path('views/pro/subscription.blade.php'));

        $this->assertMatchesRegularExpression(
            '/@media \(min-width: 992px\).*?\.pro-sidebar-toggle\s*\{\s*display: none;/s',
            $layout
        );
        $this->assertStringContainsString('pro-content-header subscription-page-header', $subscription);
        $this->assertStringContainsString('.subscription-page-header,', $subscription);
        $this->assertStringContainsString('max-width: none;', $subscription);
        $this->assertStringContainsString('@media (min-width: 1200px)', $subscription);
        $this->assertStringNotContainsString('max-width: 960px', $subscription);

        $provider = User::factory()->create(['is_service_provider' => true]);

        $this->actingAs($provider)
            ->get(route('pro.subscription'))
            ->assertOk()
            ->assertSee('pro-content-header subscription-page-header', false)
            ->assertSee('sub-page-card', false);
    }
}
