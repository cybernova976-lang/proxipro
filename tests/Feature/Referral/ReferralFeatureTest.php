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

    public function test_it_grants_referral_rewards_only_once_for_the_first_qualifying_purchase(): void
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

        $this->assertTrue($service->grantFirstPurchaseRewards($referee->fresh(), $transaction));
        $this->assertFalse($service->grantFirstPurchaseRewards($referee->fresh(), $transaction));

        $this->assertDatabaseCount('referral_rewards', 2);
        $this->assertDatabaseHas('referral_rewards', [
            'referrer_user_id' => $referrer->id,
            'referee_user_id' => $referee->id,
            'reward_type' => 'first_purchase_referrer',
            'points' => 50,
        ]);
        $this->assertDatabaseHas('referral_rewards', [
            'referrer_user_id' => $referrer->id,
            'referee_user_id' => $referee->id,
            'reward_type' => 'first_purchase_referee',
            'points' => 20,
        ]);

        $this->assertSame(50, $referrer->fresh()->available_points);
        $this->assertSame(20, $referee->fresh()->available_points);
        $this->assertNotNull($referee->fresh()->referral_bonus_granted_at);

        Notification::assertSentTo($referrer, ReferralRewardNotification::class);
        Notification::assertSentTo($referee, ReferralRewardNotification::class);
    }

    public function test_register_page_prefills_referral_code_from_query_string(): void
    {
        $response = $this->get(route('register', ['ref' => 'ABCD1234']));

        $response->assertOk();
        $response->assertSee('name="referral_code"', false);
        $response->assertSee('value="ABCD1234"', false);
    }
}