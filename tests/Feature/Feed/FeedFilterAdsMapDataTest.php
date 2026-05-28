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

    public function test_filter_ads_limits_results_and_map_markers_to_user_radius(): void
    {
        $viewer = User::factory()->create();
        $author = User::factory()->create([
            'plan' => 'pro',
        ]);

        $nearAd = Ad::create([
            'title' => 'Annonce proche du lecteur',
            'description' => 'Visible car elle est dans le rayon geographique',
            'category' => 'Plomberie',
            'location' => 'Mamoudzou',
            'price' => 80,
            'service_type' => 'offre',
            'status' => 'active',
            'visibility' => 'public',
            'user_id' => $author->id,
            'country' => 'Mayotte',
            'latitude' => -12.7806,
            'longitude' => 45.2279,
        ]);

        Ad::create([
            'title' => 'Annonce boostee trop loin',
            'description' => 'Ne doit pas remonter hors rayon meme si elle est boostee',
            'category' => 'Electricite',
            'location' => 'Paris',
            'price' => 200,
            'service_type' => 'offre',
            'status' => 'active',
            'visibility' => 'public',
            'user_id' => $author->id,
            'country' => 'France',
            'latitude' => 48.8566,
            'longitude' => 2.3522,
            'is_boosted' => true,
            'boost_end' => now()->addDay(),
        ]);

        $response = $this->withoutMiddleware()->actingAs($viewer)->get(route('feed.filter-ads', [
            'format' => 'json',
            'lat' => -12.7806,
            'lng' => 45.2279,
            'radius' => 20,
        ]));

        $response->assertOk();
        $response->assertJsonPath('geo_applied', true);
        $response->assertJsonPath('ads.0.id', $nearAd->id);
        $response->assertJsonMissing(['title' => 'Annonce boostee trop loin']);
        $response->assertJsonPath('map_markers.0.id', $nearAd->id);
    }

    public function test_filter_ads_can_show_all_ads_when_user_disables_nearby_scope(): void
    {
        $viewer = User::factory()->create();
        $author = User::factory()->create([
            'plan' => 'pro',
        ]);

        $farAd = Ad::create([
            'title' => 'Annonce volontairement eloignee',
            'description' => 'Visible quand l utilisateur demande toutes les annonces',
            'category' => 'Electricite',
            'location' => 'Paris',
            'price' => 200,
            'service_type' => 'offre',
            'status' => 'active',
            'visibility' => 'public',
            'user_id' => $author->id,
            'country' => 'France',
            'latitude' => 48.8566,
            'longitude' => 2.3522,
        ]);

        $response = $this->withoutMiddleware()->actingAs($viewer)->get(route('feed.filter-ads', [
            'format' => 'json',
            'scope' => 'all',
            'lat' => -12.7806,
            'lng' => 45.2279,
            'radius' => 20,
        ]));

        $response->assertOk();
        $response->assertJsonPath('geo_applied', false);
        $response->assertJsonPath('scope', 'all');
        $response->assertJsonPath('ads.0.id', $farAd->id);
    }

    public function test_filter_ads_falls_back_to_other_zones_when_nearby_has_no_results(): void
    {
        $viewer = User::factory()->create();
        $author = User::factory()->create([
            'plan' => 'pro',
        ]);

        $farAd = Ad::create([
            'title' => 'Annonce affichee hors zone',
            'description' => 'Fallback quand aucune annonce locale n existe',
            'category' => 'Electricite',
            'location' => 'Paris',
            'price' => 200,
            'service_type' => 'offre',
            'status' => 'active',
            'visibility' => 'public',
            'user_id' => $author->id,
            'country' => 'France',
            'latitude' => 48.8566,
            'longitude' => 2.3522,
        ]);

        $response = $this->withoutMiddleware()->actingAs($viewer)->get(route('feed.filter-ads', [
            'format' => 'json',
            'scope' => 'nearby',
            'lat' => -12.7806,
            'lng' => 45.2279,
            'radius' => 20,
        ]));

        $response->assertOk();
        $response->assertJsonPath('geo_applied', false);
        $response->assertJsonPath('geo_fallback_used', true);
        $response->assertJsonPath('ads.0.id', $farAd->id);
        $response->assertJsonPath('map_markers.0.id', $farAd->id);
    }
}
