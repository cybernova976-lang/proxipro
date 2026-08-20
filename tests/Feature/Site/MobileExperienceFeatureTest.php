<?php

namespace Tests\Feature\Site;

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
}
