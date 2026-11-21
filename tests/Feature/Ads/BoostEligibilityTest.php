<?php

namespace Tests\Feature\Ads;

use App\Models\Ad;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Le boost n'est vendu que là où il a un effet : sur une offre (le flux des
 * clients ne montre que des offres), et pas pour une durée déjà couverte par
 * l'abonnement Premium de l'auteur.
 */
class BoostEligibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
    }

    public function test_a_request_can_be_boosted_to_reach_the_client_home_and_no_point_is_spent(): void
    {
        $owner = User::factory()->create(['available_points' => 50]);
        $demand = $this->ad($owner, ['service_type' => 'demande', 'title' => 'Cherche un plombier']);

        $this->actingAs($owner)->post(route('boost.purchase.points', $demand), ['package' => 'boost_3'])
            ->assertStatus(410);
        $this->assertSame(50, (int) $owner->fresh()->available_points);
        $this->assertFalse((bool) $demand->fresh()->is_boosted);

        // La page d'achat est ouverte à une demande, avec l'effet réellement appliqué.
        $this->get(route('boost.show', $demand))->assertOk()
            ->assertSee('votre demande est affichée sur l’accueil des particuliers')
            ->assertDontSee('votre offre porte la mention');
        $this->get(route('ads.show', $demand))->assertOk()->assertSee(route('boost.show', $demand), false);

        // Après publication : l'option Urgent et les forfaits de boost sont proposés.
        $this->get(route('boost.after-creation', $demand))->assertOk()
            ->assertSee('data-type="urgent"', false)
            ->assertSee('data-type="boost"', false)
            ->assertSee('data-request-boost', false)
            ->assertDontSee('votre offre porte la mention');

        // Un pack inconnu reste refusé, et la demande d'un abonné n'a pas besoin de boost.
        $this->from(route('boost.show', $demand))->post(route('boost.purchase.stripe', $demand), ['package' => 'boost_999'])
            ->assertRedirect(route('boost.show', $demand))->assertSessionHasErrors('package');
        $owner->proSubscriptions()->create(['plan' => 'monthly', 'amount' => 9.99, 'status' => 'active',
            'starts_at' => now()->subDay(), 'ends_at' => now()->addMonths(2)]);
        $this->from(route('boost.show', $demand))->post(route('boost.purchase.stripe', $demand), ['package' => 'boost_3'])
            ->assertRedirect(route('boost.show', $demand))
            ->assertSessionHas('error', fn ($message) => str_contains($message, 'cette demande parmi les annonces sponsorisées'));
        $this->get(route('boost.after-creation', $demand))->assertOk()
            ->assertSee('data-request-covered', false)->assertDontSee('data-type="boost"', false);
    }

    public function test_boost_fully_covered_by_an_active_premium_subscription_is_refused(): void
    {
        $owner = User::factory()->create(['available_points' => 50]);
        $owner->proSubscriptions()->create(['plan' => 'monthly', 'amount' => 9.99, 'status' => 'active',
            'starts_at' => now()->subDay(), 'ends_at' => now()->addMonth()]);
        $offer = $this->ad($owner);

        $this->actingAs($owner)->get(route('boost.show', $offer))->assertOk()
            ->assertSee('Inutile : votre abonnement Premium affiche déjà cette offre parmi les offres sponsorisées');

        $this->from(route('boost.show', $offer))
            ->post(route('boost.purchase.stripe', $offer), ['package' => 'boost_3'])
            ->assertRedirect(route('boost.show', $offer))
            ->assertSessionHas('error');
        $this->assertSame(50, (int) $owner->fresh()->available_points);
        $this->assertFalse((bool) $offer->fresh()->is_boosted);
    }

    public function test_boost_extending_beyond_the_subscription_is_accepted_with_a_warning(): void
    {
        $owner = User::factory()->create(['available_points' => 50]);
        $owner->proSubscriptions()->create(['plan' => 'monthly', 'amount' => 9.99, 'status' => 'active',
            'starts_at' => now()->subDay(), 'ends_at' => now()->addDays(2)]);
        $offer = $this->ad($owner);

        $this->actingAs($owner)->get(route('boost.show', $offer))->assertOk()
            ->assertSee('le boost ne change l’affichage qu’après cette date');

        config(['services.stripe.secret' => 'sk_test_simulated']);
        \Mockery::mock('alias:Stripe\\Checkout\\Session')->shouldReceive('create')->once()
            ->andReturnUsing(function (array $params) {
                $this->assertSame(480, $params['line_items'][0]['price_data']['unit_amount']);
                return (object) ['url' => 'https://checkout.example.test/boost'];
            });
        $this->post(route('boost.purchase.stripe', $offer), ['package' => 'boost_7'])
            ->assertRedirect('https://checkout.example.test/boost');
        $this->assertFalse($offer->fresh()->isCurrentlyBoosted());
        $this->assertSame(50, (int) $owner->fresh()->available_points);
    }

    public function test_urgent_option_is_not_sold_when_the_request_is_already_flagged_urgent_for_free(): void
    {
        $owner = User::factory()->create();
        // Urgence choisie gratuitement au dépôt : is_urgent sans date de fin.
        $demand = $this->ad($owner, ['service_type' => 'demande', 'title' => 'Cherche un plombier',
            'is_urgent' => true, 'urgent_until' => null]);
        $this->assertFalse($demand->canBuyUrgentOption());

        // Cette page répondait par une erreur 500 (date de fin absente).
        $this->actingAs($owner)->get(route('boost.after-creation', $demand))->assertOk()
            ->assertSee('data-urgent-already-free', false)
            ->assertDontSee(route('ads.make-urgent.stripe', $demand), false);

        $this->from(route('boost.after-creation', $demand))
            ->post(route('ads.make-urgent.stripe', $demand))
            ->assertRedirect(route('boost.after-creation', $demand))
            ->assertSessionHas('error');

        // Écran « Demande publiée » : l'encart payant n'est plus proposé.
        $this->withSession(['demand_published' => true])
            ->get(route('demand.matching', $demand))->assertOk()
            ->assertSee('Demande publiée')
            ->assertDontSee('Choisir l’option Urgent');

        // Demande sans urgence déclarée : l'option reste proposée.
        $plain = $this->ad($owner, ['service_type' => 'demande', 'title' => 'Cherche un jardinier']);
        $this->assertTrue($plain->canBuyUrgentOption());
        $this->withSession(['demand_published' => true])
            ->get(route('demand.matching', $plain))->assertOk()
            ->assertSee('Choisir l’option Urgent');
    }

    private function ad(User $owner, array $attributes = []): Ad
    {
        return Ad::create(array_merge(['user_id' => $owner->id, 'title' => 'Service de plomberie',
            'description' => 'Installation et dépannage de sanitaires.', 'category' => 'Plombier',
            'city' => 'Mamoudzou', 'country' => 'Mayotte', 'location' => 'Mamoudzou', 'postal_code' => '97600',
            'service_type' => 'offre', 'status' => 'active', 'visibility' => 'public'], $attributes));
    }
}
