<?php

namespace Tests\Feature\Brand;

use App\Mail\EmailVerificationCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandIdentityFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_lunamars_is_the_platform_identity_everywhere(): void
    {
        $legacyName = 'Proxi'.'Pro';

        $this->assertSame('Lunamars', config('app.name'));
        $this->assertSame('Lunamars', config('mail.from.name'));
        $this->assertDatabaseHas('settings', [
            'key' => 'site_name',
            'value' => 'Lunamars',
        ]);

        $homepage = $this->get(route('homepage'));

        $homepage->assertOk()
            ->assertSee('Lunamars')
            ->assertSee('lunamars-brand-mark', false)
            ->assertSee('images/brand/lunamars-logo.png', false)
            ->assertDontSee('images/brand/lunamars-mark.png', false)
            ->assertSee('images/social-card.png', false)
            ->assertSee('"@context":"https://schema.org"', false)
            ->assertDontSee($legacyName);

        $user = User::factory()->create();
        $authenticatedPage = $this->actingAs($user)->get(route('profile.show'));

        $authenticatedPage->assertOk()
            ->assertSee('Lunamars')
            ->assertSee('lunamars-brand-mark', false)
            ->assertSee('images/brand/lunamars-symbol.png', false)
            ->assertDontSee('images/brand/lunamars-mark.png', false)
            ->assertDontSee($legacyName);

        $mailHtml = (new EmailVerificationCode('482913', 'Sophie Martin'))->render();

        $this->assertStringContainsString('Lunamars', $mailHtml);
        $this->assertStringContainsString('images/brand/lunamars-logo.png', $mailHtml);
        $this->assertStringNotContainsString('images/brand/lunamars-mark.png', $mailHtml);
        $this->assertStringNotContainsString($legacyName, $mailHtml);
    }

    public function test_brand_assets_have_production_ready_dimensions(): void
    {
        $socialCardPath = public_path('images/social-card.png');
        $faviconPath = public_path('favicon.ico');
        $logoPath = public_path('images/brand/lunamars-logo.png');
        $symbolPath = public_path('images/brand/lunamars-symbol.png');

        $this->assertFileDoesNotExist(public_path('images/brand/lunamars-mark.png'));
        $this->assertFileExists($logoPath);
        $this->assertFileExists($symbolPath);
        $this->assertFileExists($socialCardPath);
        $this->assertFileExists($faviconPath);
        $this->assertGreaterThan(0, filesize($faviconPath));

        $socialCardSize = getimagesize($socialCardPath);
        $this->assertSame([1200, 630], array_slice($socialCardSize, 0, 2));
        $this->assertSame(IMAGETYPE_PNG, $socialCardSize[2]);

        $logoSize = getimagesize($logoPath);
        $this->assertSame([1090, 250], array_slice($logoSize, 0, 2));
        $this->assertSame(IMAGETYPE_PNG, $logoSize[2]);

        $symbolSize = getimagesize($symbolPath);
        $this->assertSame([512, 512], array_slice($symbolSize, 0, 2));
        $this->assertSame(IMAGETYPE_PNG, $symbolSize[2]);
    }
}
