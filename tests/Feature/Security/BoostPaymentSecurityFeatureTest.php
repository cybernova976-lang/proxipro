<?php

namespace Tests\Feature\Security;

use App\Models\Ad;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
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

    public static function promotionCheckouts(): array
    {
        return [
            'urgent' => ['ads.make-urgent.stripe', 'boost.urgent.success', 'AD_URGENT', []],
            'refresh' => ['ads.refresh.stripe', 'ads.refresh.success', 'AD_REFRESH', []],
            'boost' => ['boost.purchase.stripe', 'boost.success', 'AD_BOOST', ['package' => 'boost_3']],
        ];
    }

    #[DataProvider('promotionCheckouts')]
    public function test_paid_promotion_accepts_old_and_new_return_urls_once(
        string $purchaseRoute,
        string $successRoute,
        string $transactionType,
        array $options
    ): void {
        config(['services.stripe.secret' => 'sk_test_simulated']);
        $user = User::factory()->create(['user_type' => 'particulier', 'available_points' => 0]);
        $ad = $this->ad($user, 'demande');
        $this->actingAs($user);

        $sessionId = 'cs_test_promotion';
        $checkoutUrl = 'https://checkout.example.test/promotion';
        $stripe = Mockery::mock('alias:Stripe\\Checkout\\Session');
        $stripe->shouldReceive('create')->once()->andReturnUsing(function (array $params) use (&$checkout, $checkoutUrl) {
            $checkout = $params;

            return (object) ['url' => $checkoutUrl];
        });
        $this->post(route($purchaseRoute, $ad), $options)->assertRedirect($checkoutUrl);

        $this->assertStringContainsString('{CHECKOUT_SESSION_ID}', $checkout['success_url']);
        $returnUrl = str_replace('{CHECKOUT_SESSION_ID}', $sessionId, $checkout['success_url']);
        parse_str(parse_url($returnUrl, PHP_URL_QUERY), $query);
        $expectedQuery = ['ad' => (string) $ad->id, 'session_id' => $sessionId] + $options;
        $this->assertEquals($expectedQuery, $query);
        $this->assertSame(parse_url(route($successRoute), PHP_URL_PATH), parse_url($returnUrl, PHP_URL_PATH));

        $stripe->shouldReceive('retrieve')->twice()->with($sessionId)->andReturn((object) [
            'id' => $sessionId,
            'payment_status' => 'paid',
            'currency' => 'eur',
            'amount_total' => $checkout['line_items'][0]['price_data']['unit_amount'],
            'metadata' => (object) $checkout['metadata'],
        ]);
        $oldReturnUrl = route($successRoute, ['ad' => $ad->id] + $options).'?session_id='.$sessionId;
        $this->get($oldReturnUrl)->assertRedirect(route('ads.show', $ad))->assertSessionHas('success');
        $this->get($returnUrl)->assertRedirect(route('ads.show', $ad))
            ->assertSessionHas('success', 'Ce paiement avait déjà été confirmé.');
        $this->assertDatabaseHas('transactions', ['stripe_session_id' => $sessionId, 'type' => $transactionType]);
        $this->assertDatabaseCount('transactions', 1);
        $this->assertSame(0, (int) $user->fresh()->available_points);
        if ($transactionType === 'AD_URGENT') {
            $this->assertTrue($ad->fresh()->isCurrentlyUrgent());
        }
    }

    public function test_invalid_or_unpaid_urgent_return_does_not_change_the_ad(): void
    {
        $user = User::factory()->create();
        $ad = $this->ad($user, 'demande');
        $this->actingAs($user)->get(route('boost.urgent.success', ['ad' => '33?invalid']))->assertNotFound();

        config(['services.stripe.secret' => 'sk_test_simulated']);
        Mockery::mock('alias:Stripe\\Checkout\\Session')->shouldReceive('retrieve')->once()
            ->with('cs_test_unpaid')->andReturn((object) ['id' => 'cs_test_unpaid', 'payment_status' => 'unpaid']);
        $this->get(route('boost.urgent.success', ['ad' => $ad->id]).'?session_id=cs_test_unpaid')
            ->assertRedirect(route('boost.show', $ad))->assertSessionHas('error');
        $this->assertFalse($ad->fresh()->isCurrentlyUrgent());
        $this->assertDatabaseCount('transactions', 0);
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
