<?php

namespace Tests\Feature\Feed;

use App\Models\Ad;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedMapFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_feed_displays_map_section_when_ads_have_coordinates(): void
    {
        $viewer = User::factory()->create();
        $author = User::factory()->create([
            'plan' => 'pro',
        ]);

        Ad::create([
            'title' => 'Annonce cartographiee',
            'description' => 'Une annonce visible sur la carte',
            'category' => 'Plomberie',
            'location' => 'Mamoudzou',
            'price' => 110,
            'service_type' => 'offre',
            'status' => 'active',
            'user_id' => $author->id,
            'country' => 'Mayotte',
            'latitude' => -12.7806,
            'longitude' => 45.2279,
        ]);

        $response = $this->withoutMiddleware()->actingAs($viewer)->get(route('feed'));

        $response->assertOk();
        $response->assertSee('Carte des annonces');
        $response->assertSee('adsFeedMap');
        $response->assertSee('Annonce cartographiee');
    }

    public function test_feed_map_uses_dedicated_marker_data_beyond_first_page(): void
    {
        $viewer = User::factory()->create([
            'user_type' => 'particulier',
        ]);
        $author = User::factory()->create([
            'user_type' => 'professionnel',
            'plan' => 'pro',
        ]);

        foreach (range(1, 13) as $index) {
            Ad::create([
                'title' => 'Annonce carte ' . $index,
                'description' => 'Annonce geolocalisee pour tester la carte',
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
        }

        $response = $this->withoutMiddleware()->actingAs($viewer)->get(route('feed'));

        $response->assertOk();
        $response->assertSee('Annonce carte 13');
    }
}
