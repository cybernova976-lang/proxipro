<?php

namespace Tests\Feature\Feed;

use App\Models\Ad;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedPromotedOffersFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_boost_and_subscription_offers_come_first_and_ordinary_offers_fill_remaining_slots(): void
    {
        $viewer = User::factory()->create();
        $author = User::factory()->create();
        $ordinary = $this->offer($author);
        $boost = $this->offer($author, ['is_boosted' => true, 'boost_end' => now()->addDay()]);
        $subscriber = User::factory()->create();
        $this->subscribe($subscriber);
        $premium = $this->offer($subscriber);
        for ($i = 0; $i < 5; $i++) $this->offer($author);
        $response = $this->actingAs($viewer)->get(route('feed'))->assertOk();
        $this->assertEqualsCanonicalizing([$boost->id, $premium->id], $response->viewData('pkFeedAds')->take(2)->pluck('id')->all());
        $this->assertCount(6, $response->viewData('pkFeedAds'));
        $this->assertCount(6, $response->viewData('pkFeedAds')->unique('id'));
    }

    public function test_expired_or_inactive_promotions_do_not_prevent_ordinary_fallback(): void
    {
        $viewer = User::factory()->create();
        $author = User::factory()->create();
        $ordinary = $this->offer($author);
        foreach ([
            ['status' => 'active', 'ends_at' => now()],
            ['status' => 'pending'],
            ['status' => 'cancelled'],
            ['status' => 'active', 'starts_at' => now()->addDay()],
        ] as $attributes) {
            $subscriber = User::factory()->create();
            $this->subscribe($subscriber, $attributes);
            $this->offer($subscriber);
        }
        $this->offer($author, ['is_boosted' => true, 'boost_end' => now()]);
        $ads = $this->actingAs($viewer)->get(route('feed'))->assertOk()->viewData('pkFeedAds');
        $this->assertCount(6, $ads);
        $this->assertTrue($ads->contains('id', $ordinary->id));
    }

    public function test_last_promotion_expiry_restores_ordinary_offers(): void
    {
        $viewer = User::factory()->create();
        $author = User::factory()->create();
        $ordinary = $this->offer($author);
        $boost = $this->offer($author, ['is_boosted' => true, 'boost_end' => now()->addMinute()]);
        $this->actingAs($viewer)->get(route('feed'))->assertOk()
            ->assertViewHas('pkFeedAds', fn ($ads) => $ads->count() >= 2 && $ads->first()->id === $boost->id);
        $this->travel(2)->minutes();
        $this->get(route('feed'))->assertOk()->assertViewHas('pkFeedAds', fn ($ads) => $ads->count() === 2 && $ads->contains('id', $ordinary->id));
    }

    public function test_hidden_expired_own_and_remote_promotions_do_not_suppress_local_fallback(): void
    {
        $viewer = User::factory()->create(['city' => 'Mamoudzou', 'country' => 'Mayotte']);
        $author = User::factory()->create();
        $ordinary = $this->offer($author);
        $boost = ['is_boosted' => true, 'boost_end' => now()->addDay()];
        // L'offre boostée du lecteur lui est montrée, en tête comme pour les autres membres.
        $own = $this->offer($viewer, $boost);
        foreach ([['visibility' => 'pro_targeted'], ['status' => 'inactive'], ['expires_at' => now()],
            ['city' => 'Paris', 'country' => 'France', 'location' => 'Paris']] as $attributes) {
            $this->offer($author, array_merge($boost, $attributes));
        }
        $this->actingAs($viewer)->get(route('feed', ['scope' => 'nearby']))->assertOk()
            ->assertViewHas('pkFeedAds', fn ($ads) => $ads->pluck('id')->all() === [$own->id, $ordinary->id]);
    }

    public function test_old_promoted_offer_is_found_before_candidate_limit_and_list_is_capped_at_six(): void
    {
        $viewer = User::factory()->create();
        $author = User::factory()->create();
        for ($i = 0; $i < 145; $i++) $this->offer($author);
        $boost = $this->offer($author, ['created_at' => now()->subYear(), 'is_boosted' => true, 'boost_end' => now()->addDay()]);
        $this->actingAs($viewer)->get(route('feed'))->assertOk()
            ->assertViewHas('pkFeedAds', fn ($ads) => $ads->count() >= 2 && $ads->first()->id === $boost->id);
        for ($i = 0; $i < 7; $i++) $this->offer($author, ['is_boosted' => true, 'boost_end' => now()->addDay()]);
        $this->get(route('feed'))->assertOk()->assertViewHas('pkFeedAds', fn ($ads) => $ads->count() === 6 && $ads->every(fn ($ad) => $ad->isCurrentlyBoosted()));
    }

    public function test_provider_requests_remain_visible_and_client_mode_uses_promoted_offers(): void
    {
        $viewer = User::factory()->create(['user_type' => 'professionnel', 'is_service_provider' => true, 'service_category' => 'Plombier']);
        $author = User::factory()->create();
        $request = $this->offer($author, ['service_type' => 'demande']);
        $this->offer($author);
        $boost = $this->offer($author, ['is_boosted' => true, 'boost_end' => now()->addDay()]);
        $this->actingAs($viewer)->get(route('feed'))->assertOk()
            ->assertViewHas('pkFeedAds', fn ($ads) => $ads->pluck('id')->all() === [$request->id]);
        $this->get(route('feed', ['mode' => 'client']))->assertOk()
            ->assertViewHas('pkFeedAds', fn ($ads) => $ads->count() >= 2 && $ads->first()->id === $boost->id);
    }

    public function test_requests_reach_the_client_home_only_when_boosted_or_published_by_a_subscriber(): void
    {
        $viewer = User::factory()->create();
        $author = User::factory()->create();
        $subscriber = User::factory()->create();
        $this->subscribe($subscriber);

        $freeOffer = $this->offer($author, ['title' => 'Offre gratuite']);
        $freeRequest = $this->offer($author, ['title' => 'Demande gratuite', 'service_type' => 'demande']);
        $premiumRequest = $this->offer($subscriber, ['title' => 'Demande d’un abonné', 'service_type' => 'demande']);
        $boostedRequest = $this->offer($author, ['title' => 'Demande boostée', 'service_type' => 'demande',
            'is_boosted' => true, 'boost_end' => now()->addDay()]);
        $expiredBoost = $this->offer($author, ['title' => 'Demande au boost terminé', 'service_type' => 'demande',
            'is_boosted' => true, 'boost_end' => now()->subMinute()]);

        $response = $this->actingAs($viewer)->get(route('feed'))->assertOk()
            ->assertSee('Demande d’un abonné')->assertSee('Demande boostée')->assertSee('Offre gratuite')
            ->assertDontSee('Demande gratuite')->assertDontSee('Demande au boost terminé')
            ->assertSee('Demande de service');
        $ids = $response->viewData('pkFeedAds')->pluck('id')->all();

        // Les annonces sponsorisées d'abord, les annonces gratuites complètent.
        $this->assertEqualsCanonicalizing([$premiumRequest->id, $boostedRequest->id], array_slice($ids, 0, 2));
        $this->assertSame([$freeOffer->id], array_slice($ids, 2));
        $this->assertNotContains($freeRequest->id, $ids);
        $this->assertNotContains($expiredBoost->id, $ids);
        $this->assertTrue($response->viewData('pkFeedAds')->firstWhere('id', $premiumRequest->id)->feed_is_promoted == 1);

        // L'abonné voit sa propre demande sur son accueil, signalée comme la sienne.
        $this->actingAs($subscriber)->get(route('feed'))->assertOk()
            ->assertSee('Demande d’un abonné')->assertSee('Votre annonce')
            ->assertViewHas('pkFeedAds', fn ($ads) => $ads->contains('id', $premiumRequest->id));

        // L'abonnement s'arrête : sa demande quitte l'accueil.
        $subscriber->proSubscriptions()->update(['status' => 'cancelled', 'ends_at' => now()->subDay()]);
        $this->actingAs($viewer)->get(route('feed'))->assertOk()->assertDontSee('Demande d’un abonné');
    }

    private function offer(User $author, array $attributes = []): Ad
    {
        return Ad::create(array_merge(['user_id' => $author->id, 'title' => 'Service de plomberie',
            'description' => 'Installation et dépannage de sanitaires.', 'category' => 'Plombier',
            'city' => 'Mamoudzou', 'country' => 'Mayotte', 'location' => 'Mamoudzou',
            'service_type' => 'offre', 'status' => 'active', 'visibility' => 'public'], $attributes));
    }

    private function subscribe(User $author, array $attributes = []): void
    {
        $author->proSubscriptions()->create(array_merge(['plan' => 'monthly', 'amount' => 9.99,
            'status' => 'active', 'starts_at' => now()->subDay(), 'ends_at' => now()->addMonth()], $attributes));
    }
}
