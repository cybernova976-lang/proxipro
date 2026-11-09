<?php

namespace Tests\Feature\Referral;

use App\Models\ReferralReward;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\ReferralRewardNotification;
use App\Services\ReferralService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ReferralFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_referral_rewards_remain_disabled_even_for_a_legacy_paid_purchase(): void
    {
        Notification::fake();

        $referrer = User::factory()->create([
            'referral_code' => 'REFR123456',
            'available_points' => 0,
            'total_points' => 0,
            'level' => 1,
            'daily_points' => 0,
        ]);

        $referee = User::factory()->create([
            'referred_by_user_id' => $referrer->id,
            'referral_code' => 'REFE123456',
            'available_points' => 0,
            'total_points' => 0,
            'level' => 1,
            'daily_points' => 0,
        ]);

        $transaction = Transaction::create([
            'user_id' => $referee->id,
            'amount' => 4.99,
            'type' => 'POINTS',
            'description' => 'Premier achat qualifiant',
            'status' => 'completed',
            'stripe_session_id' => 'sess_referral_1',
        ]);

        $service = app(ReferralService::class);

        $this->assertFalse($service->grantFirstPurchaseRewards($referee->fresh(), $transaction));
        $this->assertFalse($service->grantFirstPurchaseRewards($referee->fresh(), $transaction));
        $this->assertDatabaseCount('referral_rewards', 0);
        $this->assertDatabaseCount('point_transactions', 0);
        $this->assertSame(0, $referrer->fresh()->available_points);
        $this->assertSame(0, $referee->fresh()->available_points);
        $this->assertNull($referee->fresh()->referral_bonus_granted_at);
        Notification::assertNothingSent();
    }

    public function test_register_page_no_longer_promotes_referral_points(): void
    {
        $response = $this->get(route('register', ['ref' => 'ABCD1234']));

        $response->assertOk();
        $response->assertDontSee('name="referral_code"', false);
        $response->assertDontSee('value="ABCD1234"', false);
    }

    public function test_register_page_uses_shared_location_selects(): void
    {
        $response = $this->get(route('register'));

        $response->assertOk();
        $response->assertSee('name="country"', false);
        $response->assertSee('name="city"', false);
        $response->assertSee('Guadeloupe', false);
        $response->assertSee('Bulgarie', false);
        $response->assertSee('Bénin', false);
        $response->assertSee('registerCitiesByCountry', false);
        $response->assertSee('Mamoudzou', false);
    }
}
