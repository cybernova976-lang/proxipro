<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class StripeCheckoutSecurityFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_success_callback_ignores_a_tampered_product_query_parameter(): void
    {
        $user = User::factory()->create([
            'available_points' => 0,
            'total_points' => 0,
        ]);

        $sessionAlias = Mockery::mock('alias:Stripe\\Checkout\\Session');
        $sessionAlias->shouldReceive('retrieve')->once()->andReturn((object) [
            'id' => 'cs_points_secure',
            'payment_status' => 'paid',
            'metadata' => (object) [
                'type' => 'points',
                'user_id' => $user->id,
                'product_key' => 'POINTS_5',
            ],
        ]);

        $this->actingAs($user)
            ->get(route('stripe.success', [
                'session_id' => 'cs_points_secure',
                'product' => 'POINTS_100',
            ]))
            ->assertRedirect(route('pricing.index'));

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'stripe_session_id' => 'cs_points_secure',
            'amount' => 4,
        ]);
        $this->assertSame(5, (int) $user->fresh()->available_points);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
