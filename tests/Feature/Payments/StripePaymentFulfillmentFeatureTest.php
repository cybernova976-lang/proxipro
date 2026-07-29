<?php

namespace Tests\Feature\Payments;

use App\Models\Ad;
use App\Models\IdentityVerification;
use App\Models\StripeWebhookEvent;
use App\Models\User;
use App\Services\AdPromotionPaymentService;
use App\Services\IdentityVerificationPaymentService;
use App\Services\PointPurchasePaymentService;
use App\Services\ProviderSubscriptionService;
use App\Services\StripeCheckoutFulfillmentService;
use App\Services\StripeWebhookService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class StripePaymentFulfillmentFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_point_purchase_is_applied_once_even_when_confirmation_is_replayed(): void
    {
        $user = User::factory()->create(['available_points' => 0, 'total_points' => 0]);
        $session = [
            'id' => 'cs_points_idempotent',
            'payment_status' => 'paid',
            'amount_total' => 400,
            'currency' => 'eur',
            'payment_intent' => 'pi_points_idempotent',
            'metadata' => [
                'type' => 'points',
                'user_id' => (string) $user->id,
                'product_key' => 'POINTS_5',
            ],
        ];

        $service = app(PointPurchasePaymentService::class);
        $this->assertSame('processed', $service->fulfill($session, $user->id)['status']);
        $this->assertSame('duplicate', $service->fulfill($session, $user->id)['status']);

        $this->assertSame(5, (int) $user->fresh()->available_points);
        $this->assertDatabaseCount('transactions', 1);
        $this->assertDatabaseHas('transactions', [
            'stripe_session_id' => 'cs_points_idempotent',
            'type' => 'POINTS',
            'status' => 'completed',
        ]);
    }

    public function test_point_purchase_rejects_an_incorrect_amount_before_crediting_the_user(): void
    {
        $user = User::factory()->create(['available_points' => 0, 'total_points' => 0]);

        try {
            app(PointPurchasePaymentService::class)->fulfill([
                'id' => 'cs_points_wrong_amount',
                'payment_status' => 'paid',
                'amount_total' => 1,
                'currency' => 'eur',
                'metadata' => [
                    'type' => 'points',
                    'user_id' => (string) $user->id,
                    'product_key' => 'POINTS_100',
                ],
            ], $user->id);
            $this->fail('Le montant falsifié aurait dû être refusé.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('montant', $exception->getMessage());
        }

        $this->assertSame(0, (int) $user->fresh()->available_points);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_ad_boost_can_be_confirmed_by_webhook_and_is_only_extended_once(): void
    {
        $user = User::factory()->create();
        $ad = Ad::create([
            'user_id' => $user->id,
            'title' => 'Annonce à promouvoir',
            'description' => 'Description suffisamment longue pour le test.',
            'category' => 'Plombier',
            'location' => 'Mamoudzou',
            'service_type' => 'offre',
            'status' => 'active',
        ]);
        $session = [
            'id' => 'cs_boost_idempotent',
            'payment_status' => 'paid',
            'amount_total' => 400,
            'currency' => 'eur',
            'metadata' => [
                'type' => 'ad_boost',
                'user_id' => (string) $user->id,
                'ad_id' => (string) $ad->id,
                'package' => 'boost_3',
                'expected_amount_cents' => '400',
            ],
        ];

        $service = app(AdPromotionPaymentService::class);
        $this->assertSame('processed', $service->fulfill($session)['status']);
        $firstEnd = $ad->fresh()->boost_end;
        $this->assertSame('duplicate', $service->fulfill($session)['status']);

        $this->assertTrue($ad->fresh()->is_boosted);
        $this->assertTrue($ad->fresh()->boost_end->equalTo($firstEnd));
        $this->assertDatabaseCount('transactions', 1);
    }

    public function test_failed_webhook_is_logged_then_can_be_retried_exactly_once(): void
    {
        $event = (object) [
            'id' => 'evt_retry_payment',
            'type' => 'checkout.session.completed',
            'data' => (object) ['object' => (object) ['id' => 'cs_retry_payment']],
        ];
        $subscriptions = Mockery::mock(ProviderSubscriptionService::class);
        $failingFulfillment = Mockery::mock(StripeCheckoutFulfillmentService::class);
        $failingFulfillment->shouldReceive('fulfill')->once()->andThrow(new DomainException('Échec simulé'));

        try {
            (new StripeWebhookService($failingFulfillment, $subscriptions))->process($event);
            $this->fail('Le webhook simulé aurait dû échouer.');
        } catch (DomainException) {
            $this->assertDatabaseHas('stripe_webhook_events', [
                'event_id' => 'evt_retry_payment',
                'status' => StripeWebhookEvent::STATUS_FAILED,
                'attempts' => 1,
            ]);
        }

        $successfulFulfillment = Mockery::mock(StripeCheckoutFulfillmentService::class);
        $successfulFulfillment->shouldReceive('fulfill')->once()->andReturn('processed');
        $service = new StripeWebhookService($successfulFulfillment, $subscriptions);

        $this->assertSame('processed', $service->process($event, true));
        $this->assertSame('duplicate', $service->process($event));
        $this->assertDatabaseHas('stripe_webhook_events', [
            'event_id' => 'evt_retry_payment',
            'status' => StripeWebhookEvent::STATUS_PROCESSED,
            'attempts' => 2,
        ]);
    }

    public function test_identity_verification_paid_by_webhook_is_submitted_only_once(): void
    {
        $user = User::factory()->create();
        $verification = IdentityVerification::create([
            'user_id' => $user->id,
            'type' => 'profile_verification',
            'document_type' => 'passport',
            'document_front' => 'verification-documents/11111111-1111-4111-8111-111111111111.jpg',
            'document_front_status' => 'pending',
            'selfie' => 'verification-documents/22222222-2222-4222-8222-222222222222.jpg',
            'selfie_status' => 'pending',
            'payment_amount' => 5,
            'payment_status' => 'pending',
            'status' => 'awaiting_payment',
        ]);
        $session = [
            'id' => 'cs_identity_idempotent',
            'payment_status' => 'paid',
            'amount_total' => 500,
            'currency' => 'eur',
            'client_reference_id' => (string) $verification->id,
            'payment_intent' => 'pi_identity_idempotent',
            'metadata' => [
                'type' => 'identity_verification',
                'verification_id' => (string) $verification->id,
                'user_id' => (string) $user->id,
                'expected_amount_cents' => '500',
            ],
        ];

        $service = app(IdentityVerificationPaymentService::class);
        $this->assertSame('processed', $service->fulfill($session)['status']);
        $submittedAt = $verification->fresh()->submitted_at;
        $this->assertSame('duplicate', $service->fulfill($session)['status']);

        $verification->refresh();
        $this->assertSame('paid', $verification->payment_status);
        $this->assertSame('pending', $verification->status);
        $this->assertSame('pi_identity_idempotent', $verification->payment_id);
        $this->assertTrue($verification->submitted_at->equalTo($submittedAt));
        $this->assertDatabaseCount('transactions', 1);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
