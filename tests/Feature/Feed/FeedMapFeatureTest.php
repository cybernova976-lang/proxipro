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
}