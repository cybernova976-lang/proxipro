<?php

namespace Tests\Feature\Feed;

use App\Models\Ad;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedMapFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_feed_routes_geographic_exploration_to_the_full_announcements_page(): void
    {
        $viewer = User::factory()->create();
        $author = User::factory()->create(['plan' => 'pro']);

        Ad::create([
            'title' => 'Annonce cartographiée',
            'description' => 'Une annonce géolocalisée accessible depuis la liste complète.',
            'category' => 'Plomberie',
            'location' => 'Mamoudzou',
            'price' => 110,
            'service_type' => 'offre',
            'status' => 'active',
            'visibility' => 'public',
            'user_id' => $author->id,
            'country' => 'Mayotte',
            'latitude' => -12.7806,
            'longitude' => 45.2279,
        ]);

        $response = $this->withoutMiddleware()->actingAs($viewer)->get(route('feed'));

        $response
            ->assertOk()
            ->assertSee('Annonce cartographiée')
            ->assertSee('Voir toutes les annonces, la carte et les filtres')
            ->assertSee(route('ads.index'), false)
            ->assertDontSee('id="adsFeedMap"', false);
    }

    public function test_geographic_payload_keeps_markers_beyond_the_first_results_page(): void
    {
        $viewer = User::factory()->create(['user_type' => 'particulier']);
        $author = User::factory()->create([
            'user_type' => 'professionnel',
            'plan' => 'pro',
        ]);

        $ads = collect(range(1, 13))->map(function (int $index) use ($author): Ad {
            return Ad::create([
                'title' => 'Annonce carte '.$index,
                'description' => 'Annonce géolocalisée pour tester les marqueurs.',
                'category' => 'Plomberie',
                'location' => 'Mamoudzou',
                'price' => 100 + $index,
                'service_type' => 'demande',
                'status' => 'active',
                'visibility' => 'public',
                'user_id' => $author->id,
                'country' => 'Mayotte',
                'latitude' => -12.7806,
                'longitude' => 45.2279,
                'created_at' => now()->subMinutes($index),
                'updated_at' => now()->subMinutes($index),
            ]);
        });

        $response = $this->withoutMiddleware()->actingAs($viewer)->getJson(route('feed.filter-ads', [
            'format' => 'json',
        ]));

        $response->assertOk();

        $markerIds = collect($response->json('map_markers'))->pluck('id')->map(fn ($id) => (int) $id);

        $this->assertCount(13, $markerIds);
        $this->assertContains($ads->last()->id, $markerIds);
    }
}
