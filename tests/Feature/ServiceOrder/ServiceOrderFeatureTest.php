<?php

namespace Tests\Feature\ServiceOrder;

use App\Models\Ad;
use App\Models\ServiceOrder;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\ServiceOrderRequestedNotification;
use App\Notifications\ServiceOrderStatusNotification;
use App\Services\StripeConnectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Mockery;
use Tests\TestCase;

class ServiceOrderFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_a_secure_service_order_from_an_ad(): void
    {
        Notification::fake();

        $seller = User::factory()->create();
        $buyer = User::factory()->create();

        $ad = Ad::create([
            'title' => 'Mission plomberie securisee',
            'description' => 'Intervention sous commande securisee',
            'category' => 'Plomberie',
            'location' => 'Mamoudzou',
            'price' => 120,
            'service_type' => 'offre',
            'status' => 'active',
            'user_id' => $seller->id,
        ]);

        $response = $this->actingAs($buyer)->post(route('service-orders.store', $ad), [
            'amount' => 120,
            'message' => 'Je souhaite reserver cette mission.',
            'scheduled_for' => now()->addDays(2)->toDateString(),
        ]);

        $response->assertRedirect(route('service-orders.index'));

        $this->assertDatabaseHas('service_orders', [
            'ad_id' => $ad->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'amount' => 120,
            'commission_amount' => 12,
            'seller_amount' => 108,
            'status' => 'pending_acceptance',
            'payment_status' => 'awaiting_payment',
        ]);

        Notification::assertSentTo($seller, ServiceOrderRequestedNotification::class);
    }

    public function test_seller_can_accept_or_refuse_a_service_order(): void
    {
        Notification::fake();

        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        $ad = Ad::create([
            'title' => 'Annonce test acceptation',
            'description' => 'Annonce pour tester les transitions vendeur',
            'category' => 'Plomberie',
            'location' => 'Mamoudzou',
            'price' => 100,
            'service_type' => 'offre',
            'status' => 'active',
            'user_id' => $seller->id,
        ]);

        $serviceOrder = ServiceOrder::create([
            'order_number' => 'CMD-TEST-1',
            'ad_id' => $ad->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'amount' => 100,
            'commission_amount' => 10,
            'seller_amount' => 90,
            'status' => ServiceOrder::STATUS_PENDING_ACCEPTANCE,
            'payment_status' => ServiceOrder::PAYMENT_AWAITING,
        ]);

        $this->actingAs($seller)
            ->post(route('service-orders.accept', $serviceOrder))
            ->assertRedirect(route('service-orders.index'));

        $this->assertDatabaseHas('service_orders', [
            'id' => $serviceOrder->id,
            'status' => ServiceOrder::STATUS_AWAITING_PAYMENT,
            'payment_status' => ServiceOrder::PAYMENT_AWAITING,
        ]);

        Notification::assertSentTo($buyer, ServiceOrderStatusNotification::class);

        $refusedOrder = ServiceOrder::create([
            'order_number' => 'CMD-TEST-2',
            'ad_id' => $ad->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'amount' => 120,
            'commission_amount' => 12,
            'seller_amount' => 108,
            'status' => ServiceOrder::STATUS_PENDING_ACCEPTANCE,
            'payment_status' => ServiceOrder::PAYMENT_AWAITING,
        ]);

        $this->actingAs($seller)
            ->post(route('service-orders.refuse', $refusedOrder), ['reason' => 'Delai incompatible'])
            ->assertRedirect(route('service-orders.index'));

        $this->assertDatabaseHas('service_orders', [
            'id' => $refusedOrder->id,
            'status' => ServiceOrder::STATUS_REFUSED,
            'payment_status' => ServiceOrder::PAYMENT_CANCELED,
            'refused_reason' => 'Delai incompatible',
        ]);
    }

    public function test_buyer_can_start_stripe_checkout_for_accepted_order(): void
    {
        $buyer = User::factory()->create(['stripe_id' => 'cus_existing']);
        $seller = User::factory()->create();
        $ad = Ad::create([
            'title' => 'Annonce test checkout',
            'description' => 'Annonce pour tester Stripe Checkout',
            'category' => 'Electricite',
            'location' => 'Mamoudzou',
            'price' => 150,
            'service_type' => 'offre',
            'status' => 'active',
            'user_id' => $seller->id,
        ]);

        $serviceOrder = ServiceOrder::create([
            'order_number' => 'CMD-TEST-3',
            'ad_id' => $ad->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'amount' => 150,
            'commission_amount' => 15,
            'seller_amount' => 135,
            'status' => ServiceOrder::STATUS_AWAITING_PAYMENT,
            'payment_status' => ServiceOrder::PAYMENT_AWAITING,
        ]);

        $sessionAlias = Mockery::mock('alias:Stripe\\Checkout\\Session');
        $sessionAlias->shouldReceive('create')->once()->andReturn((object) [
            'id' => 'cs_test_service_order',
            'url' => 'https://checkout.stripe.test/session/service-order',
        ]);

        $this->actingAs($buyer)
            ->post(route('service-orders.checkout', $serviceOrder))
            ->assertRedirect('https://checkout.stripe.test/session/service-order');

        $this->assertDatabaseHas('service_orders', [
            'id' => $serviceOrder->id,
            'payment_status' => ServiceOrder::PAYMENT_CHECKOUT_OPEN,
            'stripe_checkout_session_id' => 'cs_test_service_order',
        ]);
    }

    public function test_seller_can_start_stripe_connect_onboarding(): void
    {
        $seller = User::factory()->create();

        $connectMock = Mockery::mock(StripeConnectService::class);
        $connectMock->shouldReceive('createOnboardingLink')
            ->once()
            ->andReturn('https://connect.stripe.test/onboarding');
        $this->app->instance(StripeConnectService::class, $connectMock);

        $this->actingAs($seller)
            ->get(route('service-orders.connect.onboarding'))
            ->assertRedirect('https://connect.stripe.test/onboarding');
    }

    public function test_success_callback_marks_service_order_paid_then_buyer_can_release_or_dispute(): void
    {
        Notification::fake();

        $buyer = User::factory()->create();
        $seller = User::factory()->create();
        $ad = Ad::create([
            'title' => 'Annonce test paiement',
            'description' => 'Annonce pour tester la confirmation Stripe',
            'category' => 'Peinture',
            'location' => 'Mamoudzou',
            'price' => 200,
            'service_type' => 'offre',
            'status' => 'active',
            'user_id' => $seller->id,
        ]);

        $serviceOrder = ServiceOrder::create([
            'order_number' => 'CMD-TEST-4',
            'ad_id' => $ad->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'amount' => 200,
            'commission_amount' => 20,
            'seller_amount' => 180,
            'status' => ServiceOrder::STATUS_AWAITING_PAYMENT,
            'payment_status' => ServiceOrder::PAYMENT_CHECKOUT_OPEN,
        ]);

        $sessionAlias = Mockery::mock('alias:Stripe\\Checkout\\Session');
        $sessionAlias->shouldReceive('retrieve')->once()->andReturn((object) [
            'id' => 'cs_paid_service_order',
            'payment_status' => 'paid',
            'payment_intent' => 'pi_service_order_paid',
            'metadata' => (object) [
                'type' => 'service_order',
                'service_order_id' => $serviceOrder->id,
            ],
        ]);

        $this->actingAs($buyer)
            ->get(route('stripe.success', ['session_id' => 'cs_paid_service_order']))
            ->assertRedirect(route('service-orders.index'));

        $this->assertDatabaseHas('service_orders', [
            'id' => $serviceOrder->id,
            'status' => ServiceOrder::STATUS_FUNDED,
            'payment_status' => ServiceOrder::PAYMENT_PAID,
            'stripe_checkout_session_id' => 'cs_paid_service_order',
            'stripe_payment_intent_id' => 'pi_service_order_paid',
        ]);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $buyer->id,
            'type' => 'SERVICE_ORDER_PAYMENT',
            'stripe_session_id' => 'cs_paid_service_order',
        ]);

        $connectMock = Mockery::mock(StripeConnectService::class);
        $connectMock->shouldReceive('transferToSeller')->once()->andReturn('tr_service_order_release');
        $this->app->instance(StripeConnectService::class, $connectMock);

        $this->actingAs($buyer)
            ->post(route('service-orders.release', $serviceOrder))
            ->assertRedirect(route('service-orders.index'));

        $this->assertDatabaseHas('service_orders', [
            'id' => $serviceOrder->id,
            'status' => ServiceOrder::STATUS_COMPLETED,
            'payment_status' => ServiceOrder::PAYMENT_RELEASED,
            'stripe_transfer_id' => 'tr_service_order_release',
        ]);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $seller->id,
            'type' => 'SERVICE_ORDER_RELEASE',
            'description' => 'Liberation commande ' . $serviceOrder->order_number,
        ]);

        $disputedOrder = ServiceOrder::create([
            'order_number' => 'CMD-TEST-5',
            'ad_id' => $ad->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'amount' => 210,
            'commission_amount' => 21,
            'seller_amount' => 189,
            'status' => ServiceOrder::STATUS_FUNDED,
            'payment_status' => ServiceOrder::PAYMENT_PAID,
            'paid_at' => now(),
        ]);

        $this->actingAs($buyer)
            ->post(route('service-orders.dispute', $disputedOrder), ['reason' => 'Le travail livre ne correspond pas a la commande.'])
            ->assertRedirect(route('service-orders.index'));

        $this->assertDatabaseHas('service_orders', [
            'id' => $disputedOrder->id,
            'status' => ServiceOrder::STATUS_DISPUTED,
            'payment_status' => ServiceOrder::PAYMENT_DISPUTED,
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }
}