<?php

namespace Tests\Feature\Payments;

use App\Models\Ad;
use App\Models\IdentityVerification;
use App\Models\User;
use App\Support\RetiredPoints;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RetiredPointsFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_no_longer_grants_welcome_credit(): void
    {
        \Illuminate\Support\Facades\Mail::fake();
        $this->post(route('register'), ['firstname' => 'Camille', 'lastname' => 'Test',
            'email' => 'camille@example.test', 'account_type' => 'particulier', 'country' => 'Mayotte',
            'city' => 'Mamoudzou', 'password' => 'PasswordSecure!63', 'password_confirmation' => 'PasswordSecure!63',
            'terms' => '1'])->assertSessionHasNoErrors()->assertRedirect();
        $user = User::where('email', 'camille@example.test')->firstOrFail();
        $this->assertSame(0, (int) $user->available_points);
        $this->assertSame(0, (int) $user->total_points);
        $this->assertFalse($user->isPremium());
        $this->assertDatabaseCount('point_transactions', 0);
    }

    public function test_all_old_purchase_and_spending_endpoints_are_closed_without_mutation(): void
    {
        $user = User::factory()->create(['available_points' => 10000, 'total_points' => 10000]);
        $ad = Ad::create(['user_id' => $user->id, 'title' => 'Service de plomberie',
            'description' => 'Installation et dépannage de sanitaires.', 'category' => 'Plombier',
            'location' => 'Mamoudzou', 'service_type' => 'offre', 'status' => 'active', 'visibility' => 'public']);
        $verification = IdentityVerification::create(['user_id' => $user->id, 'type' => 'service_provider', 'document_type' => 'passport',
            'status' => 'awaiting_payment', 'payment_status' => 'pending']);
        $this->actingAs($user);

        foreach ([route('stripe.create-checkout'), route('pricing.purchase-points'), route('purchase-points'),
            route('boost.purchase.points', $ad), route('ads.make-urgent', $ad), route('verification.pay.points')] as $url) {
            $this->postJson($url, ['product' => 'points_100', 'package' => 'boost_30',
                'verification_id' => $verification->id])->assertStatus(410)->assertJsonPath('message', RetiredPoints::MESSAGE);
        }
        $this->post(route('boost.purchase.points', $ad), ['package' => 'boost_30'])
            ->assertStatus(410)->assertSee('Cette option n’est plus disponible')
            ->assertSee(RetiredPoints::MESSAGE);

        $this->assertSame(10000, (int) $user->fresh()->available_points);
        $this->assertFalse((bool) $ad->fresh()->is_boosted);
        $this->assertFalse((bool) $ad->fresh()->is_urgent);
        $this->assertSame('pending', $verification->fresh()->payment_status);
        $this->assertDatabaseCount('point_transactions', 0);
        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('pro_subscriptions', 0);
    }

    public function test_rewards_balances_and_levels_never_unlock_premium_or_spendable_credit(): void
    {
        $user = User::factory()->create(['available_points' => 10000, 'total_points' => 10000, 'level' => 100]);
        foreach (['welcome_bonus', 'bonus', 'referral', 'daily', 'social_share'] as $type) {
            $user->addPoints(50, $type, 'Ancienne récompense');
        }
        $this->assertFalse($user->spendPoints(1, 'boost', 'Ancienne option'));
        $this->assertFalse($user->checkLevelUp());
        $this->assertFalse($user->isPremium());
        $this->assertFalse($user->hasActiveSubscription());
        $this->assertSame(10000, (int) $user->fresh()->available_points);
        $this->assertDatabaseCount('point_transactions', 0);

        $subscription = $user->proSubscriptions()->create(['plan' => 'monthly', 'amount' => 9.99,
            'status' => 'active', 'starts_at' => now(), 'ends_at' => now()->addMonth()]);
        $this->assertTrue($user->fresh()->isPremium());
        $subscription->update(['status' => 'expired', 'ends_at' => now()->subMinute()]);
        $this->assertFalse($user->fresh()->isPremium());
    }

    public function test_old_history_remains_private_and_no_longer_promotes_spending(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $owner->pointTransactions()->create(['points' => 5, 'type' => 'purchase', 'description' => 'Achat historique A', 'source' => 'stripe']);
        $other->pointTransactions()->create(['points' => 50, 'type' => 'bonus', 'description' => 'Historique privé B']);
        $this->actingAs($owner)->get(route('points.transactions'))->assertOk()
            ->assertSee('Achat historique A')->assertDontSee('Historique privé B')
            ->assertDontSee('Acheter des points')->assertDontSee('Gagner des points');
        $this->get(route('points.dashboard'))->assertOk()->assertSee('Le programme de points est terminé');
    }
}
