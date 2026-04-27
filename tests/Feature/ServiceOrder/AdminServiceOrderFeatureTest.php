<?php

namespace Tests\Feature\ServiceOrder;

use App\Models\Ad;
use App\Models\ServiceOrder;
use App\Models\User;
use App\Services\StripeConnectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AdminServiceOrderFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_service_orders_control_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.service-orders.index'));

        $response->assertOk();
        $response->assertSee('Commandes securisees');
    }

    public function test_admin_can_release_disputed_order_with_real_transfer_service(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $buyer = User::factory()->create();
        $seller = User::factory()->create([
            'stripe_connect_account_id' => 'acct_test_vendor',
            'stripe_connect_payouts_enabled' => true,
        ]);

        $ad = Ad::create([
            'title' => 'Annonce litige admin',
            'description' => 'Annonce test admin release',
            'category' => 'Plomberie',
            'location' => 'Mamoudzou',
            'price' => 220,
            'service_type' => 'offre',
            'status' => 'active',
            'user_id' => $seller->id,
        ]);

        $order = ServiceOrder::create([
            'order_number' => 'CMD-ADMIN-1',
            'ad_id' => $ad->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'amount' => 220,
            'commission_amount' => 22,
            'seller_amount' => 198,
            'status' => ServiceOrder::STATUS_DISPUTED,
            'payment_status' => ServiceOrder::PAYMENT_DISPUTED,
            'paid_at' => now(),
            'stripe_payment_intent_id' => 'pi_admin_release',
        ]);

        $connectMock = Mockery::mock(StripeConnectService::class);
        $connectMock->shouldReceive('transferToSeller')->once()->andReturn('tr_admin_release');
        $this->app->instance(StripeConnectService::class, $connectMock);

        $this->actingAs($admin)
            ->post(route('admin.service-orders.release', $order), ['resolution_note' => 'Documents et preuve d execution conformes.'])
            ->assertRedirect(route('admin.service-orders.index'));

        $this->assertDatabaseHas('service_orders', [
            'id' => $order->id,
            'status' => ServiceOrder::STATUS_COMPLETED,
            'payment_status' => ServiceOrder::PAYMENT_RELEASED,
            'admin_resolution' => 'released',
            'stripe_transfer_id' => 'tr_admin_release',
        ]);
    }

    public function test_admin_can_refund_disputed_order_via_stripe(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $buyer = User::factory()->create();
        $seller = User::factory()->create();

        $ad = Ad::create([
            'title' => 'Annonce litige remboursement',
            'description' => 'Annonce test admin refund',
            'category' => 'Peinture',
            'location' => 'Mamoudzou',
            'price' => 190,
            'service_type' => 'offre',
            'status' => 'active',
            'user_id' => $seller->id,
        ]);

        $order = ServiceOrder::create([
            'order_number' => 'CMD-ADMIN-2',
            'ad_id' => $ad->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'amount' => 190,
            'commission_amount' => 19,
            'seller_amount' => 171,
            'status' => ServiceOrder::STATUS_DISPUTED,
            'payment_status' => ServiceOrder::PAYMENT_DISPUTED,
            'paid_at' => now(),
            'stripe_payment_intent_id' => 'pi_admin_refund',
        ]);

        $connectMock = Mockery::mock(StripeConnectService::class);
        $connectMock->shouldReceive('refundOrder')->once()->andReturn('re_admin_refund');
        $this->app->instance(StripeConnectService::class, $connectMock);

        $this->actingAs($admin)
            ->post(route('admin.service-orders.refund', $order), ['resolution_note' => 'Le vendeur n\'a pas fourni la prestation.'])
            ->assertRedirect(route('admin.service-orders.index'));

        $this->assertDatabaseHas('service_orders', [
            'id' => $order->id,
            'status' => ServiceOrder::STATUS_REFUNDED,
            'payment_status' => ServiceOrder::PAYMENT_REFUNDED,
            'admin_resolution' => 'refunded',
            'stripe_refund_id' => 're_admin_refund',
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }
}