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

    public function test_boost_is_refused_for_a_request_and_no_point_is_spent(): void
    {
        $owner = User::factory()->create(['available_points' => 50]);
        $demand = $this->ad($owner, ['service_type' => 'demande', 'title' => 'Cherche un plombier']);

        $this->actingAs($owner)->post(route('boost.purchase.points', $demand), ['package' => 'boost_3'])
            ->assertRedirect(route('ads.show', $demand))
            ->assertSessionHas('error', 'Le boost est réservé aux offres de services.');
        $this->assertSame(50, (int) $owner->fresh()->available_points);
        $this->assertFalse((bool) $demand->fresh()->is_boosted);

        $this->get(route('boost.show', $demand))->assertRedirect(route('ads.show', $demand));
        $this->post(route('boost.purchase.stripe', $demand), ['package' => 'boost_3'])
            ->assertRedirect(route('ads.show', $demand));

        // Après publication : l'Urgent reste proposé, aucun forfait de boost.
        $this->get(route('boost.after-creation', $demand))->assertOk()
            ->assertSee('data-type="urgent"', false)
            ->assertDontSee('data-type="boost"', false)
            ->assertDontSee('votre offre porte la mention');
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
            ->post(route('boost.purchase.points', $offer), ['package' => 'boost_3'])
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

        $this->post(route('boost.purchase.points', $offer), ['package' => 'boost_7'])
            ->assertRedirect(route('ads.show', $offer));
        $this->assertTrue($offer->fresh()->isCurrentlyBoosted());
        $this->assertLessThan(50, (int) $owner->fresh()->available_points);
    }

    private function ad(User $owner, array $attributes = []): Ad
    {
        return Ad::create(array_merge(['user_id' => $owner->id, 'title' => 'Service de plomberie',
            'description' => 'Installation et dépannage de sanitaires.', 'category' => 'Plombier',
            'city' => 'Mamoudzou', 'country' => 'Mayotte', 'location' => 'Mamoudzou', 'postal_code' => '97600',
            'service_type' => 'offre', 'status' => 'active', 'visibility' => 'public'], $attributes));
    }
}
