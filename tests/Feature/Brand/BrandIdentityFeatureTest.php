<?php

namespace Tests\Feature\Brand;

use App\Mail\EmailVerificationCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandIdentityFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_prokejem_is_the_platform_identity_everywhere(): void
    {
        $legacyName = 'Proxi'.'Pro';

        $this->assertSame('Prokejem', config('app.name'));
        $this->assertSame('Prokejem', config('mail.from.name'));
        $this->assertDatabaseHas('settings', [
            'key' => 'site_name',
            'value' => 'Prokejem',
        ]);

        $homepage = $this->get(route('homepage'));

        $homepage->assertOk()
            ->assertSee('Prokejem')
            ->assertSee('prokejem-brand-mark', false)
            ->assertSee('images/brand/prokejem-logo.png', false)
            ->assertDontSee('images/brand/prokejem-mark.png', false)
            ->assertSee('images/social-card.png', false)
            ->assertSee('"@context":"https://schema.org"', false)
            ->assertDontSee($legacyName);

        $user = User::factory()->create();
        $authenticatedPage = $this->actingAs($user)->get(route('profile.show'));

        $authenticatedPage->assertOk()
            ->assertSee('Prokejem')
            ->assertSee('prokejem-brand-mark', false)
            ->assertSee('images/brand/prokejem-symbol.png', false)
            ->assertDontSee('images/brand/prokejem-mark.png', false)
            ->assertDontSee($legacyName);

        $mailHtml = (new EmailVerificationCode('482913', 'Sophie Martin'))->render();

        $this->assertStringContainsString('Prokejem', $mailHtml);
        $this->assertStringContainsString('images/brand/prokejem-logo.png', $mailHtml);
        $this->assertStringNotContainsString('images/brand/prokejem-mark.png', $mailHtml);
        $this->assertStringNotContainsString($legacyName, $mailHtml);
    }

    public function test_brand_assets_have_production_ready_dimensions(): void
    {
        $socialCardPath = public_path('images/social-card.png');
        $faviconPath = public_path('favicon.ico');
        $logoPath = public_path('images/brand/prokejem-logo.png');
        $symbolPath = public_path('images/brand/prokejem-symbol.png');

        $this->assertFileDoesNotExist(public_path('images/brand/prokejem-mark.png'));
        $this->assertFileExists($logoPath);
        $this->assertFileExists($symbolPath);
        $this->assertFileExists($socialCardPath);
        $this->assertFileExists($faviconPath);
        $this->assertGreaterThan(0, filesize($faviconPath));

        $socialCardSize = getimagesize($socialCardPath);
        $this->assertSame([1200, 630], array_slice($socialCardSize, 0, 2));
        $this->assertSame(IMAGETYPE_PNG, $socialCardSize[2]);

        $logoSize = getimagesize($logoPath);
        $this->assertSame([1153, 214], array_slice($logoSize, 0, 2));
        $this->assertSame(IMAGETYPE_PNG, $logoSize[2]);

        $symbolSize = getimagesize($symbolPath);
        $this->assertSame([1024, 1024], array_slice($symbolSize, 0, 2));
        $this->assertSame(IMAGETYPE_PNG, $symbolSize[2]);
    }
}
