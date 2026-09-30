<?php

namespace Tests\Feature\Feed;

use App\Models\Ad;
use App\Models\User;
use App\Services\HomeProviderDiscoveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeProviderDiscoveryFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_old_request_does_not_hide_local_providers_and_profiles_precede_ads(): void
    {
        $client = User::factory()->create(['city' => 'Mamoudzou', 'country' => 'Mayotte']);
        Ad::create(['user_id' => $client->id, 'title' => 'Ancienne recherche de peintre',
            'description' => 'Travaux sur un autre territoire.', 'category' => 'Peintre en bâtiment',
            'city' => 'Cayenne', 'country' => 'Guyane', 'location' => 'Cayenne',
            'service_type' => 'demande', 'status' => 'active', 'visibility' => 'public']);
        $provider = $this->provider();
        $response = $this->actingAs($client)->get(route('feed'))->assertOk()
            ->assertViewHas('homeProfessionalProfiles', fn ($profiles) => $profiles->modelKeys() === [$provider->id])
            ->assertViewHas('pkProviderCategory', null)->assertSee('Prestataires à Mamoudzou');
        $this->assertLessThan(strpos($response->getContent(), 'id="pkFeedList"'),
            strpos($response->getContent(), 'id="pkProviderList"'));
    }

    public function test_discovery_explains_country_then_global_fallback_and_uses_matching_directory_links(): void
    {
        $client = User::factory()->create(['city' => 'Dzaoudzi', 'country' => 'Mayotte']);
        $localCountry = $this->provider();
        $elsewhere = $this->provider(['name' => 'Profil de Paris', 'city' => 'Paris', 'country' => 'France']);
        $this->actingAs($client)->get(route('feed'))->assertOk()
            ->assertViewHas('homeProfessionalProfiles', fn ($profiles) => $profiles->modelKeys() === [$localCountry->id])
            ->assertViewHas('pkProviderDiscovery', fn ($result) => $result['scope'] === 'country' && $result['expanded'])
            ->assertSee('Pas encore de profil public à Dzaoudzi · Mayotte')
            ->assertSee('prestataires basés ailleurs')
            ->assertSee(route('feed.professionals', ['country' => 'Mayotte']), false);
        $client->update(['city' => 'Cayenne', 'country' => 'Guyane']);
        $this->get(route('feed'))->assertOk()
            ->assertViewHas('homeProfessionalProfiles', fn ($profiles) => $profiles->count() === 2 && $profiles->contains($elsewhere))
            ->assertViewHas('pkProviderDiscovery', fn ($result) => $result['scope'] === 'all' && $result['expanded'])
            ->assertSee('Pas encore de profil public à Cayenne · Guyane')
            ->assertSee('Paris · France')->assertSee('Mamoudzou · Mayotte')
            ->assertDontSee('Prestataires à Cayenne');
    }

    public function test_local_selection_is_not_padded_with_remote_profiles(): void
    {
        $viewer = User::factory()->create();
        $local = $this->provider();
        $this->provider(['city' => 'Paris', 'country' => 'France']);
        $result = app(HomeProviderDiscoveryService::class)->discover($viewer, ' mamoudzou ', 'mayotte');
        $this->assertSame([$local->id], $result['profiles']->modelKeys());
        $this->assertFalse($result['expanded']);
    }

    public function test_daily_selection_includes_newcomers_diversifies_trades_and_does_not_use_subscriptions(): void
    {
        $viewer = User::factory()->create();
        foreach (['Plombier', 'Plombier', 'Plombier', 'Plombier', 'Jardinier', 'Électricien', 'Baby-sitter', 'Peintre'] as $job) {
            $this->provider(['profession' => $job]);
        }
        $this->travelTo(now()->setDate(2026, 9, 30)->startOfDay());
        $discovery = app(HomeProviderDiscoveryService::class);
        $first = $discovery->discover($viewer, 'Mamoudzou', 'Mayotte')['profiles'];
        $this->assertCount(4, $first);
        $this->assertCount(4, $first->pluck('profession')->unique());
        $this->assertTrue($first->every(fn ($pro) => $pro->verified_reviews_count === 0));
        User::where('id', '!=', $viewer->id)->update(['plan' => 'pro']);
        $this->assertSame($first->modelKeys(), $discovery->discover($viewer, 'Mamoudzou', 'Mayotte')['profiles']->modelKeys());
        $seen = collect();
        for ($day = 0; $day < 8; $day++) {
            $seen = $seen->merge($discovery->discover($viewer, 'Mamoudzou', 'Mayotte')['profiles']->modelKeys());
            $this->travel(1)->days();
        }
        $this->assertCount(8, $seen->unique());
        $this->travelBack();
    }

    public function test_no_location_or_empty_pool_does_not_invent_proximity_reviews_or_availability(): void
    {
        $viewer = User::factory()->create();
        $provider = $this->provider(['city' => null, 'country' => null, 'is_verified' => false]);
        $this->actingAs($viewer)->get(route('feed'))->assertOk()
            ->assertSee('Prestataires à découvrir')->assertSee('Localisation non renseignée')
            ->assertDontSee('Identité vérifiée')->assertDontSee('(0 avis)');
        $provider->update(['profile_public' => false]);
        $result = app(HomeProviderDiscoveryService::class)->discover($viewer, null, null);
        $this->assertTrue($result['profiles']->isEmpty());
        $this->assertSame('empty', $result['scope']);
    }

    public function test_clients_can_find_profiles_or_publish_and_providers_keep_their_demand_feed(): void
    {
        $viewer = User::factory()->create(['city' => 'Mamoudzou', 'country' => 'Mayotte']);
        $provider = $this->provider();
        $response = $this->actingAs($viewer)->get(route('feed'))->assertOk()
            ->assertSee('Trouver un prestataire')->assertSee('Vous préférez recevoir des propositions ?');
        $this->assertStringContainsString('action="'.route('feed.professionals').'"', $response->getContent());
        $this->get(route('feed.professionals', ['subcategory' => 'Plombier', 'city' => 'Mamoudzou', 'country' => 'Mayotte']))
            ->assertOk()->assertViewHas('professionals', fn ($profiles) => $profiles->contains($provider));
        $this->get(route('profile.public', $provider))->assertOk();
        $this->actingAs($provider)->get(route('feed'))->assertOk()->assertDontSee('id="pkProviderList"', false);
        $this->get(route('feed', ['mode' => 'client']))->assertOk()
            ->assertViewHas('homeProfessionalProfiles', fn ($profiles) => ! $profiles->contains($provider));
    }

    private function provider(array $attributes = []): User
    {
        return User::factory()->create(array_merge(['user_type' => 'particulier', 'account_type' => 'particulier',
            'is_service_provider' => true, 'profile_public' => true, 'is_active' => true,
            'profession' => 'Plombier', 'city' => 'Mamoudzou', 'country' => 'Mayotte'], $attributes));
    }
}
