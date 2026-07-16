<?php

namespace Tests\Feature\Security;

use App\Models\Ad;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BoostPaymentSecurityFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_offer_cannot_be_marked_as_urgent_with_points(): void
    {
        $user = User::factory()->create(['available_points' => 100]);
        $ad = $this->ad($user, 'offre');

        $this->actingAs($user)->post(route('ads.make-urgent', $ad))->assertRedirect();

        $this->assertFalse((bool) $ad->fresh()->is_urgent);
        $this->assertSame(100, (int) $user->fresh()->available_points);
    }

    public function test_direct_stripe_success_url_cannot_activate_a_boost_without_paid_session(): void
    {
        $user = User::factory()->create();
        $ad = $this->ad($user, 'demande');

        $this->actingAs($user)->get(route('boost.success', [
            'ad' => $ad->id,
            'package' => 'boost_7',
        ]))->assertRedirect(route('boost.show', $ad));

        $this->assertFalse((bool) $ad->fresh()->is_boosted);
    }

    public function test_offer_promotion_page_does_not_offer_urgent_purchase(): void
    {
        $user = User::factory()->create();
        $ad = $this->ad($user, 'offre');

        $this->actingAs($user)
            ->get(route('boost.after-creation', $ad))
            ->assertOk()
            ->assertDontSee('data-type="urgent"', false)
            ->assertSee('data-type="boost"', false);
    }

    private function ad(User $user, string $serviceType): Ad
    {
        return Ad::create([
            'title' => 'Annonce sécurisée',
            'description' => 'Description de test suffisamment détaillée.',
            'category' => 'Plomberie',
            'location' => 'Mamoudzou',
            'service_type' => $serviceType,
            'status' => 'active',
            'visibility' => 'public',
            'user_id' => $user->id,
        ]);
    }
}
