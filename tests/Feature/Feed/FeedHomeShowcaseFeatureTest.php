<?php

namespace Tests\Feature\Feed;

use App\Models\Ad;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedHomeShowcaseFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_unified_home_feed_prioritizes_requests_and_limits_the_main_list(): void
    {
        $viewer = User::factory()->create([
            'user_type' => 'particulier',
            'is_service_provider' => false,
        ]);
        $requester = User::factory()->create([
            'user_type' => 'particulier',
            'is_service_provider' => false,
        ]);

        collect(range(1, 7))->each(function (int $index) use ($requester): void {
            Ad::create([
                'title' => 'Demande récente '.$index,
                'description' => 'Une demande réelle présentée dans le nouveau feed unifié.',
                'category' => 'Plomberie',
                'location' => 'Mamoudzou',
                'service_type' => 'demande',
                'status' => 'active',
                'visibility' => 'public',
                'user_id' => $requester->id,
                'created_at' => now()->subMinutes($index),
                'updated_at' => now()->subMinutes($index),
            ]);
        });

        $urgentRequest = Ad::create([
            'title' => 'Demande urgente prioritaire',
            'description' => 'Cette demande urgente doit apparaître en tête du nouveau feed.',
            'category' => 'Plomberie',
            'location' => 'Mamoudzou',
            'service_type' => 'demande',
            'status' => 'active',
            'visibility' => 'public',
            'user_id' => $requester->id,
            'is_urgent' => true,
            'urgent_until' => now()->addDay(),
            'created_at' => now()->subDays(30),
            'updated_at' => now()->subDays(30),
        ]);

        User::factory()->create([
            'name' => 'Artisan recommandé',
            'user_type' => 'professionnel',
            'is_service_provider' => true,
            'plan' => 'pro',
            'profession' => 'Plombier',
            'city' => 'Mamoudzou',
            'hourly_rate' => 35,
            'show_hourly_rate' => true,
            'specialties' => ['Dépannage rapide', 'Recherche de fuite'],
            'bio' => 'Intervient pour les dépannages et travaux de plomberie.',
        ]);

        $response = $this->withoutMiddleware()->actingAs($viewer)->get(route('feed'));

        $response
            ->assertOk()
            ->assertViewIs('feed.index')
            ->assertViewHas('pkRole', 'client')
            ->assertViewHas('pkFeedAds', function ($ads) use ($urgentRequest): bool {
                return $ads->count() === 6 && $ads->first()?->is($urgentRequest);
            })
            ->assertSee('id="pkFeedList"', false)
            ->assertSee('Demande urgente prioritaire')
            ->assertSee('Prestataires recommandés')
            ->assertSee('Artisan recommandé')
            ->assertSee('35 €/h')
            ->assertSee('Dépannage rapide')
            ->assertSee('pk-pro__visual', false)
            ->assertSee('Découvrir ce profil')
            ->assertSee('Voir toutes les annonces, la carte et les filtres')
            ->assertDontSee('home-showcase-section', false)
            ->assertDontSee('adsFeedMap', false);

        $css = file_get_contents(public_path('css/feed.css'));

        $this->assertStringContainsString('linear-gradient(135deg, #f8fbff', $css);
        $this->assertStringContainsString('.pk-pro__visual', $css);
        $this->assertStringContainsString('scroll-snap-type: x mandatory', $css);
        $this->assertStringNotContainsString('linear-gradient(150deg, var(--pk-950), var(--pk-800))', $css);
    }
}
