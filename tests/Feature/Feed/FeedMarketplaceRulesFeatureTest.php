<?php

namespace Tests\Feature\Feed;

use App\Models\Ad;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FeedMarketplaceRulesFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_filter_type_really_separates_requests_and_services(): void
    {
        $viewer = User::factory()->create();
        $author = User::factory()->create(['user_type' => 'professionnel']);
        $requestAd = $this->ad($author, 'Demande uniquement', 'demande');
        $serviceAd = $this->ad($author, 'Service uniquement', 'offre');

        $response = $this->withoutMiddleware()->actingAs($viewer)->getJson(route('feed.filter-ads', [
            'format' => 'json',
            'type' => 'demandes',
        ]));

        $response->assertOk()->assertJsonFragment(['id' => $requestAd->id]);
        $this->assertNotContains($serviceAd->id, $response->json('ads.*.id'));
    }

    public function test_home_feed_does_not_query_each_category_individually(): void
    {
        $viewer = User::factory()->create();
        $queryCount = 0;
        DB::listen(function () use (&$queryCount) {
            $queryCount++;
        });

        $this->withoutMiddleware()->actingAs($viewer)->get(route('feed'))->assertOk();

        $this->assertLessThan(80, $queryCount, 'Le feed exécute trop de requêtes SQL pour une page vide.');
    }

    public function test_targeted_request_is_only_visible_to_matching_providers(): void
    {
        $author = User::factory()->create();
        $matchingProvider = User::factory()->create([
            'user_type' => 'professionnel',
            'service_category' => 'Plomberie',
        ]);
        $otherProvider = User::factory()->create([
            'user_type' => 'professionnel',
            'service_category' => 'Electricité',
        ]);
        $particular = User::factory()->create(['user_type' => 'particulier']);

        $targeted = $this->ad($author, 'Demande ciblée plomberie', 'demande', [
            'visibility' => 'pro_targeted',
            'target_categories' => ['Plomberie'],
        ]);

        $this->withoutMiddleware()->actingAs($matchingProvider)
            ->getJson(route('feed.filter-ads', ['format' => 'json']))
            ->assertJsonFragment(['id' => $targeted->id]);

        $this->withoutMiddleware()->actingAs($otherProvider)
            ->getJson(route('feed.filter-ads', ['format' => 'json']))
            ->assertJsonMissing(['id' => $targeted->id]);

        $this->withoutMiddleware()->actingAs($particular)
            ->getJson(route('feed.filter-ads', ['format' => 'json']))
            ->assertJsonMissing(['id' => $targeted->id]);
    }

    public function test_request_coordinates_are_approximate_in_public_feed_payloads(): void
    {
        $viewer = User::factory()->create();
        $author = User::factory()->create();
        $ad = $this->ad($author, 'Demande avec position privée', 'demande', [
            'latitude' => -12.7806,
            'longitude' => 45.2279,
        ]);

        $response = $this->withoutMiddleware()->actingAs($viewer)->getJson(route('feed.filter-ads', [
            'format' => 'json',
        ]));

        $response->assertJsonFragment([
            'id' => $ad->id,
            'latitude' => -12.78,
            'longitude' => 45.23,
            'location_is_approximate' => true,
        ]);
    }

    public function test_expired_ads_are_not_returned_even_if_status_is_still_active(): void
    {
        $viewer = User::factory()->create();
        $author = User::factory()->create();
        $expired = $this->ad($author, 'Annonce arrivée à expiration', 'demande', [
            'expires_at' => now()->subMinute(),
        ]);

        $this->withoutMiddleware()->actingAs($viewer)
            ->getJson(route('feed.filter-ads', ['format' => 'json']))
            ->assertJsonMissing(['id' => $expired->id]);
    }

    private function ad(User $author, string $title, string $serviceType, array $overrides = []): Ad
    {
        return Ad::create(array_merge([
            'title' => $title,
            'description' => 'Description suffisamment longue pour représenter une annonce fiable.',
            'category' => 'Plomberie',
            'location' => 'Mamoudzou',
            'country' => 'Mayotte',
            'service_type' => $serviceType,
            'status' => 'active',
            'visibility' => 'public',
            'user_id' => $author->id,
        ], $overrides));
    }
}
