<?php

namespace Tests\Feature\Site;

use App\Models\Ad;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchIndexingTest extends TestCase
{
    use RefreshDatabase;

    public function test_authentication_pages_can_be_crawled_but_not_indexed(): void
    {
        foreach (['login', 'register', 'password.request'] as $route) {
            $response = $this->get(route($route))->assertOk()
                ->assertHeader('X-Robots-Tag', 'noindex, follow');

            $this->assertMatchesRegularExpression('/<meta name="robots" content="noindex,[^"]*"/', $response->getContent());
        }

        $robots = file_get_contents(public_path('robots.txt'));
        $this->assertStringContainsString('Sitemap: https://www.prokejem.fr/sitemap.xml', $robots);
        $this->assertStringNotContainsString('Disallow: /login', $robots);
        $this->assertStringNotContainsString('Disallow: /register', $robots);
    }

    public function test_sitemap_only_lists_live_public_ads_from_active_owners_and_public_providers(): void
    {
        $owner = User::factory()->create(['is_active' => true]);
        $attributes = [
            'user_id' => $owner->id,
            'title' => 'Service public',
            'description' => 'Présentation du service.',
            'category' => 'Plombier',
            'location' => 'Mamoudzou',
            'service_type' => 'offre',
            'status' => 'active',
            'visibility' => 'public',
        ];
        $visible = Ad::create($attributes);
        $excluded = collect([
            Ad::create(array_merge($attributes, ['expires_at' => now()->subMinute()])),
            Ad::create(array_merge($attributes, ['visibility' => 'pro_targeted'])),
            Ad::create(array_merge($attributes, ['status' => 'inactive'])),
            Ad::create(array_merge($attributes, ['user_id' => User::factory()->create(['is_active' => false])->id])),
        ]);
        $provider = User::factory()->create(['is_active' => true, 'profile_public' => true, 'is_service_provider' => true]);
        $privateProvider = User::factory()->create(['is_active' => true, 'profile_public' => false, 'is_service_provider' => true]);
        $inactiveProvider = User::factory()->create(['is_active' => false, 'profile_public' => true, 'is_service_provider' => true]);

        $response = $this->get(route('sitemap'))->assertOk()
            ->assertSee(route('homepage'), false)
            ->assertSee(route('ads.show', $visible), false)
            ->assertSee(route('profile.public', $provider->id), false)
            ->assertDontSee(route('profile.public', $privateProvider->id), false)
            ->assertDontSee(route('profile.public', $inactiveProvider->id), false)
            ->assertDontSee(route('login'), false)
            ->assertDontSee(route('register'), false);

        foreach ($excluded as $ad) {
            $response->assertDontSee('<loc>'.route('ads.show', $ad).'</loc>', false);
        }

        $xml = simplexml_load_string($response->getContent());
        $this->assertNotFalse($xml);
        $this->assertSame('urlset', $xml->getName());
    }

    public function test_public_content_keeps_its_own_metadata_and_remains_indexable(): void
    {
        $owner = User::factory()->create(['is_active' => true]);
        $ad = Ad::create([
            'user_id' => $owner->id,
            'title' => 'Entretien & jardin à Mamoudzou',
            'description' => '<p>Taille de haies & entretien du jardin.</p>',
            'category' => 'Jardinier',
            'location' => 'Mamoudzou',
            'service_type' => 'offre',
            'status' => 'active',
            'visibility' => 'public',
        ]);

        foreach ([route('homepage'), route('ads.index'), route('ads.show', $ad)] as $url) {
            $this->get($url)->assertOk()->assertHeaderMissing('X-Robots-Tag')
                ->assertDontSee('<meta name="robots" content="noindex', false);
        }

        $this->get(route('ads.show', $ad))->assertOk()
            ->assertSee('<title>Entretien &amp; jardin à Mamoudzou - Prokejem</title>', false)
            ->assertSee('<meta name="description" content="Taille de haies &amp; entretien du jardin.">', false)
            ->assertSee('<link rel="canonical" href="'.route('ads.show', $ad).'">', false);

        $ad->update(['visibility' => 'pro_targeted']);
        $this->get(route('ads.show', $ad))->assertOk()
            ->assertSee('<meta name="robots" content="noindex,nofollow">', false);
    }

    public function test_account_workspace_is_not_indexable_even_with_a_standalone_layout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('profile.show'))->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }
}
