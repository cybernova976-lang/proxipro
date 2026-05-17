<?php

namespace Tests\Feature\Feed;

use App\Models\Ad;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedHomeShowcaseFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_feed_home_showcase_limits_ads_and_prioritizes_paid_or_urgent_items(): void
    {
        $viewer = User::factory()->create();
        $author = User::factory()->create([
            'plan' => 'pro',
        ]);

        $oldNormalAds = collect(range(1, 9))->map(function ($index) use ($author) {
            return Ad::create([
                'title' => 'Annonce normale ' . $index,
                'description' => 'Annonce standard pour la vitrine',
                'category' => 'Plomberie',
                'location' => 'Mamoudzou',
                'price' => 80 + $index,
                'service_type' => 'offre',
                'status' => 'active',
                'user_id' => $author->id,
                'created_at' => now()->subDays(20 + $index),
                'updated_at' => now()->subDays(20 + $index),
            ]);
        });

        $urgentAd = Ad::create([
            'title' => 'Annonce urgente prioritaire',
            'description' => 'Cette annonce urgente doit rester dans la vitrine',
            'category' => 'Urgence',
            'location' => 'Mamoudzou',
            'price' => 120,
            'service_type' => 'demande',
            'status' => 'active',
            'user_id' => $author->id,
            'is_urgent' => true,
            'urgent_until' => now()->addDay(),
            'created_at' => now()->subDays(90),
            'updated_at' => now()->subDays(90),
        ]);

        $boostedAd = Ad::create([
            'title' => 'Annonce boostee prioritaire',
            'description' => 'Cette annonce boostee doit rester dans la vitrine',
            'category' => 'Bricolage',
            'location' => 'Koungou',
            'price' => 150,
            'service_type' => 'offre',
            'status' => 'active',
            'user_id' => $author->id,
            'is_boosted' => true,
            'boost_type' => 'vip',
            'boost_end' => now()->addDay(),
            'created_at' => now()->subDays(80),
            'updated_at' => now()->subDays(80),
        ]);

        $premiumPro = User::factory()->create([
            'name' => 'Premium Pro',
            'user_type' => 'professionnel',
            'plan' => 'pro',
        ]);
        $boostedPro = User::factory()->create([
            'name' => 'Boost Pro',
            'user_type' => 'professionnel',
        ]);
        Ad::create([
            'title' => 'Annonce boost profil',
            'description' => 'Boost pour faire remonter le profil',
            'category' => 'Electricite',
            'location' => 'Mamoudzou',
            'price' => 100,
            'service_type' => 'offre',
            'status' => 'active',
            'user_id' => $boostedPro->id,
            'is_boosted' => true,
            'boost_end' => now()->addDay(),
        ]);

        collect(range(1, 4))->each(function ($index) {
            User::factory()->create([
                'name' => 'Prestataire ' . $index,
                'user_type' => 'particulier',
                'is_service_provider' => true,
                'service_provider_since' => now()->subDays($index),
            ]);
        });

        $response = $this->withoutMiddleware()->actingAs($viewer)->get(route('feed'));

        $response->assertOk();
        $content = $response->getContent();

        preg_match_all('/data-showcase-ad-id="(\d+)"/', $content, $adMatches);
        $showcaseAdIds = array_map('intval', $adMatches[1]);

        $this->assertCount(8, $showcaseAdIds);
        $this->assertContains($urgentAd->id, $showcaseAdIds);
        $this->assertContains($boostedAd->id, $showcaseAdIds);
        $this->assertNotContains($oldNormalAds->last()->id, $showcaseAdIds);

        preg_match_all('/data-showcase-pro-id="(\d+)"/', $content, $proMatches);
        $showcaseProIds = array_map('intval', $proMatches[1]);

        $this->assertCount(4, $showcaseProIds);
        $this->assertContains($premiumPro->id, $showcaseProIds);
        $this->assertContains($boostedPro->id, $showcaseProIds);
        $this->assertLessThan(
            2,
            array_search($premiumPro->id, $showcaseProIds, true),
            'Subscribed professionals should stay in the first profile row slots.'
        );
    }
}
