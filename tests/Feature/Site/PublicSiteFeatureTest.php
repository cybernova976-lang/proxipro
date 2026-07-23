<?php

namespace Tests\Feature\Site;

use App\Models\Ad;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PublicSiteFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_homepage_uses_real_marketplace_content_and_security_headers(): void
    {
        $owner = User::factory()->create();
        $ad = Ad::create([
            'title' => 'Besoin réel de plomberie',
            'description' => 'Réparer une fuite dans la cuisine.',
            'category' => 'Plombier',
            'location' => 'Mamoudzou',
            'city' => 'Mamoudzou',
            'service_type' => 'demande',
            'status' => 'active',
            'visibility' => 'public',
            'user_id' => $owner->id,
        ]);

        $response = $this->get(route('homepage'));

        $response->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertSee('Besoin réel de plomberie')
            ->assertSee('name="location"', false)
            ->assertSee(route('ads.show', $ad), false)
            ->assertSee('data-site-share-trigger', false)
            ->assertSee('id="sitePlatformShareModal"', false)
            ->assertSee('data-site-native-share', false)
            ->assertSee('navigator.canShare(fileShareData)', false)
            ->assertSee('navigator.share(nativeShareData)', false)
            ->assertSee('images/social-card.png', false)
            ->assertSee('property="og:image:width" content="1200"', false)
            ->assertSee('data-site-share-copy', false)
            ->assertSee('Partager Lunamars')
            ->assertDontSee('<title>Laravel', false)
            ->assertDontSee('Plateforme N°1')
            ->assertDontSee('Sophie M.');

        $this->assertFileExists(public_path('images/social-card.png'));
        $socialImageSize = getimagesize(public_path('images/social-card.png'));
        $this->assertSame([1200, 630], array_slice($socialImageSize, 0, 2));
        $this->assertSame(IMAGETYPE_PNG, $socialImageSize[2]);
        $this->assertMatchesRegularExpression(
            '/\.site-share-preview img\s*\{[^}]*height:\s*auto;[^}]*object-fit:\s*contain;/s',
            $response->getContent()
        );
        $this->assertSame(1, substr_count($response->getContent(), 'id="sitePlatformShareModal"'));
    }

    public function test_authenticated_layout_exposes_platform_sharing_from_the_user_menu(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('profile.show'));

        $response->assertOk()
            ->assertSee('Partager '.config('app.name', 'Lunamars'))
            ->assertSee('data-site-share-trigger', false)
            ->assertSee('id="sitePlatformShareModal"', false)
            ->assertSee('https://wa.me/', false)
            ->assertSee('facebook.com/sharer/sharer.php', false)
            ->assertSee('linkedin.com/sharing/share-offsite', false);

        $this->assertSame(1, substr_count($response->getContent(), 'id="sitePlatformShareModal"'));
    }

    public function test_notification_and_privacy_preferences_are_persisted(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('settings.notifications'), [])
            ->assertRedirect();

        $this->actingAs($user)
            ->post(route('settings.privacy'), [
                'profile_public' => '1',
                'show_phone' => '1',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email_notifications' => false,
            'profile_public' => true,
            'show_email' => false,
            'show_phone' => true,
        ]);
    }

    public function test_sitemap_exposes_active_ads_but_not_inactive_ads(): void
    {
        $owner = User::factory()->create();
        $activeAd = Ad::create([
            'title' => 'Annonce indexable',
            'description' => 'Une annonce active.',
            'category' => 'Plombier',
            'location' => 'Mamoudzou',
            'service_type' => 'offre',
            'status' => 'active',
            'visibility' => 'public',
            'user_id' => $owner->id,
        ]);
        $inactiveAd = Ad::create([
            'title' => 'Annonce privée',
            'description' => 'Une annonce inactive.',
            'category' => 'Plombier',
            'location' => 'Mamoudzou',
            'service_type' => 'offre',
            'status' => 'inactive',
            'visibility' => 'private',
            'user_id' => $owner->id,
        ]);

        $response = $this->get(route('sitemap'));

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee(route('ads.show', $activeAd), false)
            ->assertDontSee(route('ads.show', $inactiveAd), false);
    }

    public function test_private_profile_is_only_visible_to_its_owner(): void
    {
        $owner = User::factory()->create(['profile_public' => false]);

        $this->get(route('profile.public', $owner->id))->assertNotFound();
        $this->actingAs($owner)->get(route('profile.public', $owner->id))->assertOk();
    }
}
