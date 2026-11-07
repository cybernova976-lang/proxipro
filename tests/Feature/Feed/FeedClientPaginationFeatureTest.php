<?php

namespace Tests\Feature\Feed;

use App\Models\Ad;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedClientPaginationFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_seven_promotions_span_two_pages_and_ordinary_offers_fill_the_second_page(): void
    {
        $this->freezeTime();
        $viewer = User::factory()->create();
        $author = User::factory()->create();
        $subscriber = User::factory()->create();
        $subscriber->proSubscriptions()->create(['plan' => 'monthly', 'amount' => 9.99,
            'status' => 'active', 'starts_at' => now()->subDay(), 'ends_at' => now()->addMonth()]);
        $promoted = collect([$this->offer($subscriber)]);
        for ($i = 0; $i < 6; $i++) {
            $promoted->push($this->offer($author, ['is_boosted' => true, 'boost_end' => now()->addDay()]));
        }
        $ordinary = collect();
        for ($i = 0; $i < 5; $i++) $ordinary->push($this->offer($author));

        $first = $this->actingAs($viewer)->get(route('feed'))->assertOk();
        $second = $this->get(route('feed', ['offers_page' => 2]))->assertOk();
        $firstAds = $first->viewData('pkFeedAds');
        $secondAds = $second->viewData('pkFeedAds');
        $this->assertCount(6, $firstAds);
        $this->assertTrue($firstAds->every(fn ($ad) => (bool) $ad->feed_is_promoted));
        $this->assertCount(6, $secondAds);
        $this->assertTrue((bool) $secondAds->first()->feed_is_promoted);
        $this->assertEqualsCanonicalizing($ordinary->pluck('id')->all(), $secondAds->skip(1)->pluck('id')->all());
        $this->assertCount(12, $firstAds->concat($secondAds)->unique('id'));
        $this->assertSame(12, $first->viewData('pkFeedPagination')->total());
        $this->assertStringContainsString('offers_page=2#pkFeedTitle', $first->viewData('pkFeedPagination')->nextPageUrl());
        $this->assertSame($firstAds->pluck('id')->all(), $this->get(route('feed'))->assertOk()->viewData('pkFeedAds')->pluck('id')->all());
    }

    public function test_old_promotions_beyond_120_candidates_remain_reachable(): void
    {
        $this->freezeTime();
        $viewer = User::factory()->create();
        $author = User::factory()->create();
        $oldest = $this->offer($author, ['created_at' => now()->subYear(), 'is_boosted' => true, 'boost_end' => now()->addDay()]);
        for ($i = 0; $i < 125; $i++) {
            $this->offer($author, ['is_boosted' => true, 'boost_end' => now()->addDay()]);
        }
        $last = $this->actingAs($viewer)->get(route('feed', ['offers_page' => 21]))->assertOk();
        $this->assertSame(126, $last->viewData('pkFeedPagination')->total());
        $this->assertCount(6, $last->viewData('pkFeedAds'));
        $this->assertTrue($last->viewData('pkFeedAds')->contains('id', $oldest->id));
        $this->assertNull($last->viewData('pkFeedPagination')->nextPageUrl());
    }

    public function test_service_feed_excludes_employment_and_retains_legacy_null_domains(): void
    {
        $viewer = User::factory()->create();
        $author = User::factory()->create();
        $legacy = $this->offer($author, ['publication_domain' => null]);
        $service = $this->offer($author, ['publication_domain' => 'service']);
        $this->offer($author, ['publication_domain' => 'employment', 'is_boosted' => true, 'boost_end' => now()->addDay()]);
        $this->actingAs($viewer)->get(route('feed'))->assertOk()->assertViewHas('pkFeedAds',
            fn ($ads) => $ads->count() === 2 && $ads->contains('id', $legacy->id) && $ads->contains('id', $service->id));
    }

    public function test_pagination_preserves_location_visibility_and_saved_state(): void
    {
        $viewer = User::factory()->create(['city' => 'Mamoudzou', 'country' => 'Mayotte']);
        $author = User::factory()->create();
        $oldest = $this->offer($author, ['created_at' => now()->subDay()]);
        for ($i = 0; $i < 6; $i++) $this->offer($author);
        foreach ([['visibility' => 'pro_targeted'], ['status' => 'inactive'], ['expires_at' => now()],
            ['city' => 'Paris', 'country' => 'France', 'location' => 'Paris']] as $attributes) {
            $this->offer($author, array_merge(['is_boosted' => true, 'boost_end' => now()->addDay()], $attributes));
        }
        $this->offer($viewer);
        $viewer->savedAds()->attach($oldest->id);
        $response = $this->actingAs($viewer)->get(route('feed', ['scope' => 'nearby', 'offers_page' => 2]))->assertOk();
        $this->assertSame(7, $response->viewData('pkFeedPagination')->total());
        $this->assertSame([$oldest->id], $response->viewData('pkFeedAds')->pluck('id')->all());
        $this->assertTrue($response->viewData('pkSavedAdIds')->contains($oldest->id));
        $this->assertStringContainsString('scope=nearby', $response->viewData('pkFeedPagination')->previousPageUrl());
    }

    public function test_provider_mode_does_not_use_client_pagination(): void
    {
        $viewer = User::factory()->create(['user_type' => 'professionnel', 'is_service_provider' => true, 'service_category' => 'Plombier']);
        $author = User::factory()->create();
        $request = $this->offer($author, ['service_type' => 'demande']);
        $this->actingAs($viewer)->get(route('feed', ['offers_page' => 99]))->assertOk()
            ->assertViewHas('pkFeedPagination', null)
            ->assertViewHas('pkFeedAds', fn ($ads) => $ads->pluck('id')->all() === [$request->id]);
    }

    public function test_many_boosts_from_one_author_do_not_hide_other_promoted_authors(): void
    {
        $viewer = User::factory()->create();
        $otherAuthor = User::factory()->create();
        $this->offer($otherAuthor, ['is_boosted' => true, 'boost_end' => now()->addDay(), 'created_at' => now()->subMonth()]);
        $prolific = User::factory()->create();
        for ($i = 0; $i < 8; $i++) {
            $this->offer($prolific, ['is_boosted' => true, 'boost_end' => now()->addDay()]);
        }
        $response = $this->actingAs($viewer)->get(route('feed'))->assertOk();
        $firstThree = $response->viewData('pkFeedAds')->take(3);
        $this->assertSame(2, $firstThree->where('user_id', $prolific->id)->count());
        $this->assertSame(1, $firstThree->where('user_id', $otherAuthor->id)->count());
        $response->assertSee('Pages des offres de services')->assertSee('rel="next"', false);
        $this->get(route('feed', ['offers_page' => 2]))->assertOk()->assertSee('rel="prev"', false);
    }

    private function offer(User $author, array $attributes = []): Ad
    {
        return Ad::create(array_merge(['user_id' => $author->id, 'title' => 'Service de plomberie',
            'description' => 'Installation et dépannage de sanitaires.', 'category' => 'Plombier',
            'city' => 'Mamoudzou', 'country' => 'Mayotte', 'location' => 'Mamoudzou',
            'service_type' => 'offre', 'status' => 'active', 'visibility' => 'public'], $attributes));
    }
}
