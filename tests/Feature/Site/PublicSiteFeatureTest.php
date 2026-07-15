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
            ->assertDontSee('Plateforme N°1')
            ->assertDontSee('Sophie M.');
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
