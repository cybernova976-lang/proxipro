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
            ->assertSee('images/brand/lunamars-mark.png', false)
            ->assertSee('images/social-card.png', false)
            ->assertDontSee($legacyName);

        $user = User::factory()->create();
        $authenticatedPage = $this->actingAs($user)->get(route('profile.show'));

        $authenticatedPage->assertOk()
            ->assertSee('Lunamars')
            ->assertSee('images/brand/lunamars-mark.png', false)
            ->assertDontSee($legacyName);

        $mailHtml = (new EmailVerificationCode('482913', 'Sophie Martin'))->render();

        $this->assertStringContainsString('Lunamars', $mailHtml);
        $this->assertStringContainsString('images/brand/lunamars-mark.png', $mailHtml);
        $this->assertStringNotContainsString($legacyName, $mailHtml);
    }

    public function test_brand_assets_have_production_ready_dimensions(): void
    {
        $markPath = public_path('images/brand/lunamars-mark.png');
        $socialCardPath = public_path('images/social-card.png');
        $faviconPath = public_path('favicon.ico');

        $this->assertFileExists($markPath);
        $this->assertFileExists($socialCardPath);
        $this->assertFileExists($faviconPath);
        $this->assertGreaterThan(0, filesize($faviconPath));

        $markSize = getimagesize($markPath);
        $this->assertSame([1024, 1024], array_slice($markSize, 0, 2));
        $this->assertSame(IMAGETYPE_PNG, $markSize[2]);

        $socialCardSize = getimagesize($socialCardPath);
        $this->assertSame([1200, 630], array_slice($socialCardSize, 0, 2));
        $this->assertSame(IMAGETYPE_PNG, $socialCardSize[2]);
    }
}
