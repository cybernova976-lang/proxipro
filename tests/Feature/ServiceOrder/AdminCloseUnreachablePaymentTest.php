<?php

namespace Tests\Feature\ServiceOrder;

use App\Models\Ad;
use App\Models\ServiceOrder;
use App\Models\Transaction;
use App\Models\User;
use App\Services\StripeConnectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * Constaté le 19/11/2026 : une commande payée en septembre avec les clés de
 * test de Stripe restait « Fonds bloqués » pour toujours. Les clés réelles ne
 * voient pas ce paiement, donc ni versement ni remboursement possible, et sa
 * commission comptait dans les commissions en attente sans aucun argent réel.
 *
 * L'administrateur peut la clôturer, mais seulement après que Stripe a
 * confirmé que le paiement n'existe pas.
 */
class AdminCloseUnreachablePaymentTest extends TestCase
{
    use RefreshDatabase;

    private const NOTE = 'Commande de test payée avec les anciennes clés de test.';

    public function test_admin_closes_an_order_whose_payment_stripe_does_not_know(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $order = $this->paidOrder();
        $this->stripeSays(paymentExists: false);

        $this->actingAs($admin)->get(route('admin.service-orders.index'))->assertOk()
            ->assertSee('Paiement introuvable chez Stripe ?')
            ->assertSee('Clôturer sans mouvement d’argent')
            ->assertSee('2,00', false);

        $this->post(route('admin.service-orders.close-unreachable', $order), ['resolution_note' => self::NOTE])
            ->assertRedirect(route('admin.service-orders.index'))
            ->assertSessionHas('success');

        $order->refresh();
        $this->assertSame(ServiceOrder::STATUS_CANCELED, $order->status);
        $this->assertSame(ServiceOrder::PAYMENT_CANCELED, $order->payment_status);
        $this->assertSame('closed_unreachable', $order->admin_resolution);
        $this->assertSame(self::NOTE, $order->admin_resolution_note);
        $this->assertSame($admin->id, (int) $order->admin_resolved_by);
        // Aucun argent n'a bougé : ni versement, ni remboursement, ni écriture.
        $this->assertNull($order->stripe_transfer_id);
        $this->assertNull($order->stripe_refund_id);
        $this->assertNull($order->released_at);
        $this->assertNull($order->refunded_at);
        $this->assertSame(0, Transaction::count());

        // La commission fictive ne compte plus, et l'action n'est plus proposée.
        $this->get(route('admin.service-orders.index'))->assertOk()
            ->assertSee('Clôturée : paiement introuvable chez Stripe')
            ->assertDontSee('data-order-close-unreachable', false)
            ->assertViewHas('commissions', fn ($commissions) => $commissions['pending'] === 0.0 && $commissions['earned'] === 0.0);
    }

    public function test_an_order_whose_payment_exists_is_never_closed_this_way(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $order = $this->paidOrder();
        $this->stripeSays(paymentExists: true);

        $this->actingAs($admin)
            ->post(route('admin.service-orders.close-unreachable', $order), ['resolution_note' => self::NOTE])
            ->assertRedirect(route('admin.service-orders.index'))
            ->assertSessionHas('error', 'Stripe connaît ce paiement : la commande ne peut pas être clôturée ainsi. Utilisez le versement ou le remboursement.');

        $order->refresh();
        $this->assertSame(ServiceOrder::STATUS_FUNDED, $order->status);
        $this->assertSame(ServiceOrder::PAYMENT_PAID, $order->payment_status);
        $this->assertNull($order->admin_resolution);
    }

    public function test_nothing_changes_when_stripe_does_not_answer_clearly(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $order = $this->paidOrder();
        $connect = Mockery::mock(StripeConnectService::class);
        $connect->shouldReceive('paymentExists')->once()->andThrow(new \Exception('Connexion à Stripe impossible.'));
        $this->app->instance(StripeConnectService::class, $connect);

        $this->actingAs($admin)
            ->post(route('admin.service-orders.close-unreachable', $order), ['resolution_note' => self::NOTE])
            ->assertRedirect(route('admin.service-orders.index'))
            ->assertSessionHas('error');

        $this->assertSame(ServiceOrder::STATUS_FUNDED, $order->fresh()->status);
    }

    public function test_the_action_requires_a_reason_an_administrator_and_a_paid_order(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $order = $this->paidOrder();
        $connect = Mockery::mock(StripeConnectService::class);
        $connect->shouldNotReceive('paymentExists');
        $this->app->instance(StripeConnectService::class, $connect);

        // Sans motif : refusé avant toute question à Stripe.
        $this->actingAs($admin)->post(route('admin.service-orders.close-unreachable', $order), ['resolution_note' => 'court'])
            ->assertSessionHasErrors('resolution_note');

        // Une commande non payée, déjà versée ou déjà remboursée n'est pas concernée.
        $unpaid = $this->paidOrder(['order_number' => 'CMD-NON-PAYEE', 'status' => ServiceOrder::STATUS_AWAITING_PAYMENT,
            'payment_status' => ServiceOrder::PAYMENT_AWAITING, 'stripe_payment_intent_id' => null]);
        $released = $this->paidOrder(['order_number' => 'CMD-VERSEE', 'status' => ServiceOrder::STATUS_COMPLETED,
            'payment_status' => ServiceOrder::PAYMENT_RELEASED, 'stripe_transfer_id' => 'tr_deja_verse']);
        foreach ([$unpaid, $released] as $other) {
            $this->post(route('admin.service-orders.close-unreachable', $other), ['resolution_note' => self::NOTE])->assertStatus(422);
        }

        // Un membre ordinaire n'a pas accès à cette action.
        $response = $this->actingAs(User::factory()->create())
            ->post(route('admin.service-orders.close-unreachable', $order), ['resolution_note' => self::NOTE]);
        $this->assertContains($response->status(), [302, 403]);
        $this->assertSame(ServiceOrder::STATUS_FUNDED, $order->fresh()->status);
    }

    private function stripeSays(bool $paymentExists): void
    {
        $connect = Mockery::mock(StripeConnectService::class);
        $connect->shouldReceive('paymentExists')->once()->andReturn($paymentExists);
        $connect->shouldNotReceive('transferToSeller');
        $connect->shouldNotReceive('refundOrder');
        $this->app->instance(StripeConnectService::class, $connect);
    }

    private function paidOrder(array $attributes = []): ServiceOrder
    {
        $seller = User::factory()->create();
        $ad = Ad::create(['title' => 'Recherche un électricien', 'description' => 'Commande de test.',
            'category' => 'Électricien', 'location' => 'Mamoudzou', 'price' => 20,
            'service_type' => 'offre', 'status' => 'active', 'user_id' => $seller->id]);

        return ServiceOrder::create(array_merge([
            'order_number' => 'CMD-TEST-INTROUVABLE',
            'ad_id' => $ad->id,
            'buyer_id' => User::factory()->create()->id,
            'seller_id' => $seller->id,
            'amount' => 20,
            'commission_amount' => 2,
            'seller_amount' => 18,
            'status' => ServiceOrder::STATUS_FUNDED,
            'payment_status' => ServiceOrder::PAYMENT_PAID,
            'paid_at' => now()->subMonths(2),
            'stripe_payment_intent_id' => 'pi_test_introuvable',
        ], $attributes));
    }
}
