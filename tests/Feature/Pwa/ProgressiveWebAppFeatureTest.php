<?php

namespace Tests\Feature\Pwa;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgressiveWebAppFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_expose_the_pwa_metadata_and_install_control(): void
    {
        $homepage = $this->get(route('homepage'));

        $homepage->assertOk()
            ->assertSee('rel="manifest"', false)
            ->assertSee('manifest.webmanifest', false)
            ->assertSee('apple-mobile-web-app-capable', false)
            ->assertSee('pwa/apple-touch-icon.png', false)
            ->assertSee('id="pwaInstallButton"', false)
            ->assertSee('Installer Prokejem')
            ->assertSee('beforeinstallprompt', false)
            ->assertSee("serviceWorker.register('/service-worker.js'", false);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('rel="manifest"', false)
            ->assertSee('id="pwaInstallButton"', false);
    }

    public function test_manifest_service_worker_offline_page_and_icons_are_production_ready(): void
    {
        $manifestPath = public_path('manifest.webmanifest');
        $serviceWorkerPath = public_path('service-worker.js');
        $offlinePath = public_path('offline.html');

        $this->assertFileExists($manifestPath);
        $this->assertFileExists($serviceWorkerPath);
        $this->assertFileExists($offlinePath);

        $manifest = json_decode(file_get_contents($manifestPath), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('Prokejem', $manifest['name']);
        $this->assertSame('/', $manifest['start_url']);
        $this->assertSame('/', $manifest['scope']);
        $this->assertSame('standalone', $manifest['display']);
        $this->assertSame('#4f46e5', $manifest['theme_color']);

        $iconsBySizeAndPurpose = collect($manifest['icons'])->keyBy(
            fn (array $icon) => $icon['sizes'].'-'.$icon['purpose']
        );

        foreach ([
            '192x192-any' => [192, 192],
            '512x512-any' => [512, 512],
            '192x192-maskable' => [192, 192],
            '512x512-maskable' => [512, 512],
        ] as $key => $expectedSize) {
            $this->assertTrue($iconsBySizeAndPurpose->has($key));
            $iconPath = public_path(ltrim($iconsBySizeAndPurpose->get($key)['src'], '/'));
            $this->assertFileExists($iconPath);
            $this->assertSame($expectedSize, array_slice(getimagesize($iconPath), 0, 2));
        }

        $this->assertSame([180, 180], array_slice(getimagesize(public_path('pwa/apple-touch-icon.png')), 0, 2));
        $this->assertStringContainsString("request.mode === 'navigate'", file_get_contents($serviceWorkerPath));
        $this->assertStringContainsString("caches.match(OFFLINE_PAGE)", file_get_contents($serviceWorkerPath));
        $this->assertStringContainsString('Vous êtes hors connexion', file_get_contents($offlinePath));
    }
}
