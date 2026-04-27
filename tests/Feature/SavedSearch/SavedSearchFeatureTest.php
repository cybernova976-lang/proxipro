<?php

namespace Tests\Feature\SavedSearch;

use App\Models\Ad;
use App\Models\SavedSearch;
use App\Models\SavedSearchMatch;
use App\Models\User;
use App\Notifications\SavedSearchMatchNotification;
use App\Services\SavedSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SavedSearchFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_saves_the_current_feed_search_once(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $payload = [
            'type' => 'offres',
            'category' => 'Plomberie',
            'search' => 'fuite',
            'radius' => 25,
            'geo_city' => 'Mamoudzou',
            'geo_country' => 'Mayotte',
            'geo_latitude' => -12.7806,
            'geo_longitude' => 45.2279,
        ];

        $this->post(route('saved-searches.store'), $payload)->assertRedirect();
        $this->post(route('saved-searches.store'), $payload)->assertRedirect();

        $this->assertDatabaseCount('saved_searches', 1);
        $this->assertDatabaseHas('saved_searches', [
            'user_id' => $user->id,
            'category' => 'Plomberie',
            'service_type' => 'offres',
            'city' => 'Mamoudzou',
        ]);
    }

    public function test_it_notifies_matching_saved_searches_only_once_per_ad(): void
    {
        Notification::fake();

        $searchOwner = User::factory()->create([
            'pro_notifications_email' => true,
        ]);

        $publisher = User::factory()->create();

        $savedSearch = SavedSearch::create([
            'user_id' => $searchOwner->id,
            'name' => 'Plomberie · offres · Mamoudzou',
            'category' => 'Plomberie',
            'service_type' => 'offres',
            'city' => 'Mamoudzou',
            'country' => 'Mayotte',
            'latitude' => -12.7806,
            'longitude' => 45.2279,
            'radius_km' => 30,
            'is_active' => true,
        ]);

        $ad = Ad::create([
            'title' => 'Depannage fuite plomberie',
            'description' => 'Intervention rapide sur fuite cuisine',
            'category' => 'Plomberie',
            'location' => 'Mamoudzou',
            'price' => 90,
            'service_type' => 'offre',
            'status' => 'active',
            'user_id' => $publisher->id,
            'country' => 'Mayotte',
            'latitude' => -12.7810,
            'longitude' => 45.2280,
        ]);

        $service = app(SavedSearchService::class);

        $this->assertSame(1, $service->processNewAd($ad));
        $this->assertSame(0, $service->processNewAd($ad));

        $this->assertDatabaseCount('saved_search_matches', 1);
        $this->assertDatabaseHas('saved_search_matches', [
            'saved_search_id' => $savedSearch->id,
            'ad_id' => $ad->id,
        ]);

        Notification::assertSentTo($searchOwner, SavedSearchMatchNotification::class);
    }
}