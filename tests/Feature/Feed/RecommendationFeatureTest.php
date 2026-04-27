<?php

namespace Tests\Feature\Feed;

use App\Models\Ad;
use App\Models\User;
use App\Services\RecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecommendationFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_prioritizes_matching_categories_and_excludes_saved_and_own_ads(): void
    {
        $user = User::factory()->create([
            'preferred_categories' => ['Plomberie'],
            'user_type' => 'particulier',
            'latitude' => -12.7806,
            'longitude' => 45.2279,
            'geo_radius' => 50,
        ]);

        $matchingAuthor = User::factory()->create([
            'is_verified' => true,
            'plan' => 'pro',
        ]);

        $otherAuthor = User::factory()->create([
            'plan' => 'pro',
        ]);

        $ownAd = Ad::create([
            'title' => 'Ma propre annonce',
            'description' => 'Ne doit pas remonter',
            'category' => 'Plomberie',
            'location' => 'Mamoudzou',
            'price' => 90,
            'service_type' => 'offre',
            'status' => 'active',
            'user_id' => $user->id,
            'latitude' => -12.7806,
            'longitude' => 45.2279,
        ]);

        $bestMatch = Ad::create([
            'title' => 'Depannage plomberie en urgence',
            'description' => 'Intervention rapide sur fuite et chauffe-eau',
            'category' => 'Plomberie',
            'location' => 'Mamoudzou',
            'price' => 120,
            'service_type' => 'offre',
            'status' => 'active',
            'user_id' => $matchingAuthor->id,
            'latitude' => -12.7820,
            'longitude' => 45.2285,
            'is_boosted' => true,
            'boost_end' => now()->addDay(),
        ]);

        $savedMatch = Ad::create([
            'title' => 'Plombier deja sauvegarde',
            'description' => 'Doit etre exclu des recommandations',
            'category' => 'Plomberie',
            'location' => 'Koungou',
            'price' => 80,
            'service_type' => 'offre',
            'status' => 'active',
            'user_id' => $otherAuthor->id,
            'latitude' => -12.7460,
            'longitude' => 45.2040,
        ]);

        $weakMatch = Ad::create([
            'title' => 'Cours de guitare',
            'description' => 'Annonce hors categorie',
            'category' => 'Musique',
            'location' => 'Mamoudzou',
            'price' => 45,
            'service_type' => 'offre',
            'status' => 'active',
            'user_id' => $otherAuthor->id,
            'latitude' => -12.7900,
            'longitude' => 45.2300,
        ]);

        $user->savedAds()->attach($savedMatch->id);

        $recommendations = app(RecommendationService::class)->getFeedRecommendations($user, [
            'latitude' => -12.7806,
            'longitude' => 45.2279,
            'radius' => 50,
        ], 5);

        $this->assertCount(2, $recommendations);
        $this->assertSame($bestMatch->id, $recommendations->first()->id);
        $this->assertFalse($recommendations->contains(fn (Ad $ad) => $ad->id === $savedMatch->id));
        $this->assertFalse($recommendations->contains(fn (Ad $ad) => $ad->id === $ownAd->id));
        $this->assertContains('Dans vos categories', $recommendations->first()->recommendation_reasons);
    }
}