<?php

namespace Tests\Feature\Feed;

use App\Models\Ad;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedFilterAdsMapDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_filter_ads_json_includes_map_fields(): void
    {
        $viewer = User::factory()->create();
        $author = User::factory()->create([
            'plan' => 'pro',
        ]);

        Ad::create([
            'title' => 'Annonce geo ajax',
            'description' => 'Annonce pour mise a jour dynamique de carte',
            'category' => 'Plomberie',
            'location' => 'Mamoudzou',
            'price' => 80,
            'service_type' => 'offre',
            'status' => 'active',
            'user_id' => $author->id,
            'country' => 'Mayotte',
            'latitude' => -12.7806,
            'longitude' => 45.2279,
        ]);

        $response = $this->withoutMiddleware()->actingAs($viewer)->get(route('feed.filter-ads', ['format' => 'json']));

        $response->assertOk();
        $response->assertJsonPath('ads.0.latitude', -12.7806);
        $response->assertJsonPath('ads.0.longitude', 45.2279);
        $response->assertJsonPath('ads.0.url', route('ads.show', 1));
    }
}