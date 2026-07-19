<?php

namespace Tests\Feature\Admin;

use App\Models\Ad;
use App\Models\User;
use App\Support\MarketplaceCategoryRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MarketplaceCategoryAvailabilityFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake(['nominatim.openstreetmap.org/*' => Http::response([], 200)]);
    }

    public function test_services_are_open_and_specialized_verticals_are_closed_by_default(): void
    {
        $this->assertTrue(MarketplaceCategoryRegistry::isEnabled('Bricolage & Travaux', 'Plombier'));
        $this->assertFalse(MarketplaceCategoryRegistry::isEnabled('Vente', 'High-tech'));

        $response = $this->actingAs(User::factory()->create())->get(route('ads.create'));

        $response->assertOk()
            ->assertSee('value="Bricolage &amp; Travaux"', false)
            ->assertDontSee('value="Vente"', false);
    }

    public function test_disabled_vertical_cannot_be_published_even_with_a_forged_request(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('ads.create'))
            ->post(route('ads.store'), $this->salePayload())
            ->assertSessionHasErrors('category');

        $this->assertDatabaseCount('ads', 0);
    }

    public function test_admin_can_activate_a_vertical_without_deleting_historical_ads(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $enabledIds = collect(MarketplaceCategoryRegistry::definitions())
            ->filter(fn (array $definition): bool => $definition['default_enabled'] || $definition['name'] === 'Vente')
            ->keys()
            ->all();

        $this->actingAs($admin)
            ->post(route('admin.settings.catalog'), ['enabled_categories' => $enabledIds])
            ->assertRedirect();

        $this->assertTrue(MarketplaceCategoryRegistry::isEnabled('Vente', 'High-tech'));

        $user = User::factory()->create();
        $this->actingAs($user)->post(route('ads.store'), $this->salePayload())->assertRedirect();
        $this->assertDatabaseHas('ads', ['main_category' => 'Vente', 'category' => 'High-tech']);
    }

    public function test_disabled_historical_ad_is_hidden_publicly_but_remains_available_to_its_owner(): void
    {
        $owner = User::factory()->create();
        $ad = Ad::create([
            'user_id' => $owner->id,
            'title' => 'Ordinateur portable à vendre',
            'description' => 'Annonce historique conservée pendant la fermeture temporaire de la verticale vente.',
            'main_category' => 'Vente',
            'category' => 'High-tech',
            'publication_domain' => 'sale',
            'service_type' => 'offre',
            'price_type' => 'fixed',
            'price' => 400,
            'location' => 'Paris',
            'country' => 'France',
            'status' => 'active',
            'expires_at' => now()->addMonth(),
        ]);

        $this->assertSame(0, Ad::marketplaceActive()->count());
        $this->get(route('ads.show', $ad))->assertNotFound();
        $this->actingAs($owner)->get(route('ads.show', $ad))->assertOk();

        $enabledIds = collect(MarketplaceCategoryRegistry::definitions())
            ->filter(fn (array $definition): bool => $definition['default_enabled'] || $definition['name'] === 'Vente')
            ->keys()
            ->all();
        MarketplaceCategoryRegistry::storeEnabledIds($enabledIds);

        $this->assertSame(1, Ad::marketplaceActive()->count());
    }

    public function test_pro_subscription_checkout_is_parked_during_launch(): void
    {
        $provider = User::factory()->create(['is_service_provider' => true]);

        $this->actingAs($provider)
            ->postJson(route('pro.onboarding.subscribe'), ['plan' => 'monthly'])
            ->assertUnprocessable()
            ->assertJsonPath('subscriptions_unavailable', true);
    }

    private function salePayload(): array
    {
        return [
            'title' => 'Ordinateur portable récent à vendre',
            'description' => 'Description suffisamment détaillée pour publier une annonce de vente correctement renseignée.',
            'main_category' => 'Vente',
            'category' => 'High-tech',
            'country' => 'France',
            'city' => 'Paris',
            'location' => 'Paris',
            'price' => 750,
            'price_type' => 'fixed',
            'service_type' => 'demande',
            'visibility' => 'public',
            'reply_restriction' => 'everyone',
            'accept_conditions' => '1',
            'ad_details' => [
                'condition' => 'good',
                'delivery_method' => 'pickup',
            ],
        ];
    }
}
