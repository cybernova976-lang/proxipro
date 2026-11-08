<?php

namespace Tests\Feature\Ads;

use App\Models\Ad;
use App\Models\User;
use App\Services\StripeCheckoutFulfillmentService;
use App\Support\AdPromotionCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

/**
 * Piste C du lot 1 : la vente de la remontée payante est suspendue. Elle est
 * refusée avec un message clair et n'annule plus jamais un boost en cours ;
 * un paiement engagé avant la suspension reste enregistré, une seule fois.
 */
class AdRefreshSuspendedTest extends TestCase
{
    use RefreshDatabase;

    public function test_refresh_with_points_is_refused_and_keeps_the_running_boost(): void
    {
        [$owner, $ad] = $this->boostedOffer(['available_points' => 100]);
        $boostEnd = $ad->boost_end->toIso8601String();
        $updatedAt = $ad->updated_at->toIso8601String();
        $this->travel(5)->minutes();

        $this->actingAs($owner)->post(route('ads.refresh', $ad))
            ->assertRedirect(route('boost.show', $ad))
            ->assertSessionHas('info', AdPromotionCatalog::REFRESH_SUSPENDED_MESSAGE);

        $fresh = $ad->fresh();
        $this->assertTrue($fresh->isCurrentlyBoosted());
        $this->assertSame($boostEnd, $fresh->boost_end->toIso8601String());
        $this->assertSame('boost_7', $fresh->boost_type);
        $this->assertSame($updatedAt, $fresh->updated_at->toIso8601String());
        $this->assertSame(100, (int) $owner->fresh()->available_points);
    }

    public function test_refresh_by_card_is_refused_without_opening_a_checkout(): void
    {
        config(['services.stripe.secret' => 'sk_test_simulated']);
        [$owner, $ad] = $this->boostedOffer();

        $this->actingAs($owner)->post(route('ads.refresh.stripe', $ad))
            ->assertRedirect(route('boost.show', $ad))
            ->assertSessionHas('info', AdPromotionCatalog::REFRESH_SUSPENDED_MESSAGE);

        $this->assertTrue($ad->fresh()->isCurrentlyBoosted());
        $this->assertDatabaseCount('transactions', 0);
        $this->assertFalse(AdPromotionCatalog::refreshAvailable());
    }

    public function test_refresh_is_refused_for_another_member_without_any_change(): void
    {
        [, $ad] = $this->boostedOffer();
        $other = User::factory()->create(['available_points' => 100]);

        $this->actingAs($other)->post(route('ads.refresh', $ad))
            ->assertRedirect(route('ads.show', $ad))
            ->assertSessionHas('info', AdPromotionCatalog::REFRESH_SUSPENDED_MESSAGE);

        $this->assertTrue($ad->fresh()->isCurrentlyBoosted());
        $this->assertSame(100, (int) $other->fresh()->available_points);
    }

    public function test_paid_refresh_arriving_by_webhook_is_recorded_once_logged_and_keeps_the_boost(): void
    {
        Log::spy();
        [$owner, $ad] = $this->boostedOffer();
        $boostEnd = $ad->boost_end->toIso8601String();
        $session = $this->paidRefreshSession($owner, $ad, 'cs_test_refresh_webhook');

        $fulfillment = app(StripeCheckoutFulfillmentService::class);
        $this->assertSame('processed', $fulfillment->fulfill($session));
        $this->assertSame('duplicate', $fulfillment->fulfill($session));

        $fresh = $ad->fresh();
        $this->assertTrue($fresh->isCurrentlyBoosted());
        $this->assertSame($boostEnd, $fresh->boost_end->toIso8601String());
        $this->assertSame('boost_7', $fresh->boost_type);
        $this->assertDatabaseCount('transactions', 1);
        $this->assertDatabaseHas('transactions', ['stripe_session_id' => 'cs_test_refresh_webhook',
            'type' => 'AD_REFRESH', 'user_id' => $owner->id]);

        Log::shouldHaveReceived('warning')->once()->withArgs(fn (string $message, array $context = []) => str_contains($message, 'suspendue')
            && ($context['ad_id'] ?? null) === $ad->id
            && ($context['stripe_session_id'] ?? null) === 'cs_test_refresh_webhook'
            && ($context['boost_end'] ?? null) !== null);
    }

    public function test_paid_refresh_returning_from_checkout_is_confirmed_once_without_changing_the_ad(): void
    {
        config(['services.stripe.secret' => 'sk_test_simulated']);
        [$owner, $ad] = $this->boostedOffer();
        $boostEnd = $ad->boost_end->toIso8601String();
        $sessionId = 'cs_test_refresh_return';

        Mockery::mock('alias:Stripe\\Checkout\\Session')->shouldReceive('retrieve')->twice()->with($sessionId)
            ->andReturn((object) $this->paidRefreshSession($owner, $ad, $sessionId));

        $this->actingAs($owner);
        // Ancien format de retour (session_id ajouté à la fin) puis format actuel.
        $this->get(route('ads.refresh.success', ['ad' => $ad->id]).'?session_id='.$sessionId)
            ->assertRedirect(route('ads.show', $ad))
            ->assertSessionHas('info', fn (string $message) => str_contains($message, AdPromotionCatalog::REFRESH_SUSPENDED_MESSAGE)
                && str_contains($message, 'n’ont pas été modifiés'));
        $this->get(route('ads.refresh.success').'?session_id='.$sessionId.'&ad='.$ad->id)
            ->assertRedirect(route('ads.show', $ad))
            ->assertSessionHas('success', 'Ce paiement avait déjà été confirmé.');

        $this->assertTrue($ad->fresh()->isCurrentlyBoosted());
        $this->assertSame($boostEnd, $ad->fresh()->boost_end->toIso8601String());
        $this->assertDatabaseCount('transactions', 1);
    }

    /** @return array<string, mixed> */
    private function paidRefreshSession(User $owner, Ad $ad, string $sessionId): array
    {
        // Session telle que la créait l'ancien bouton « Rafraîchir » (3,00 €).
        return [
            'id' => $sessionId,
            'payment_status' => 'paid',
            'currency' => 'eur',
            'amount_total' => 300,
            'metadata' => [
                'ad_id' => $ad->id,
                'user_id' => $owner->id,
                'type' => 'refresh',
                'expected_amount_cents' => 300,
                'expected_currency' => 'eur',
            ],
        ];
    }

    /** @return array{0: User, 1: Ad} */
    private function boostedOffer(array $ownerAttributes = []): array
    {
        $owner = User::factory()->create($ownerAttributes);
        $ad = Ad::create(['user_id' => $owner->id, 'title' => 'Service de plomberie',
            'description' => 'Installation et dépannage de sanitaires.', 'category' => 'Plombier',
            'city' => 'Mamoudzou', 'country' => 'Mayotte', 'location' => 'Mamoudzou', 'postal_code' => '97600',
            'service_type' => 'offre', 'status' => 'active', 'visibility' => 'public',
            'is_boosted' => true, 'boost_end' => now()->addDays(5)->startOfSecond(), 'boost_type' => 'boost_7']);

        return [$owner, $ad->fresh()];
    }
}
