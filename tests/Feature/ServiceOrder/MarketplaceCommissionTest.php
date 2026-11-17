<?php

namespace Tests\Feature\ServiceOrder;

use App\Models\Ad;
use App\Models\ServiceOrder;
use App\Models\User;
use App\Notifications\ServiceOrderStatusNotification;
use App\Services\StripeConnectService;
use App\Support\MarketplaceCommission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Mockery;
use Tests\TestCase;

/**
 * Commission de la plateforme : un seul calcul, des versements qui ne peuvent
 * ni partir deux fois ni rester bloqués, et un taux affiché partout où il engage.
 */
class MarketplaceCommissionTest extends TestCase
{
    use RefreshDatabase;

    private function payableSeller(array $attributes = []): User
    {
        return User::factory()->create($attributes + [
            'stripe_connect_account_id' => 'acct_test_seller', 'stripe_connect_payouts_enabled' => true,
        ]);
    }

    private function order(User $buyer, User $seller, array $attributes = []): ServiceOrder
    {
        $ad = Ad::create([
            'title' => 'Pose de carrelage', 'description' => 'Pose de carrelage dans une cuisine.',
            'category' => 'Carreleur', 'location' => 'Mamoudzou', 'price' => 200,
            'service_type' => 'offre', 'status' => 'active', 'user_id' => $seller->id,
        ]);

        return ServiceOrder::create($attributes + [
            'order_number' => 'CMD-TEST-'.fake()->unique()->numerify('#####'),
            'ad_id' => $ad->id, 'buyer_id' => $buyer->id, 'seller_id' => $seller->id,
            'amount' => 200, 'commission_amount' => 20, 'seller_amount' => 180,
            'status' => ServiceOrder::STATUS_AWAITING_PAYMENT, 'payment_status' => ServiceOrder::PAYMENT_AWAITING,
        ]);
    }

    public function test_the_split_is_computed_in_one_place_from_the_configured_rate(): void
    {
        $this->assertSame(10.0, MarketplaceCommission::percent());
        $this->assertSame('10 %', MarketplaceCommission::label());
        $this->assertSame(['amount' => 150.0, 'commission_amount' => 15.0, 'seller_amount' => 135.0], MarketplaceCommission::split(150));
        // La somme de la commission et du versement redonne toujours le prix payé.
        foreach ([1, 9.99, 33.33, 1234.56] as $amount) {
            $split = MarketplaceCommission::split($amount);
            $this->assertSame(round($amount, 2), round($split['commission_amount'] + $split['seller_amount'], 2));
        }

        config(['marketplace.commission_percent' => 12.5]);
        $this->assertSame('12,5 %', MarketplaceCommission::label());
        $this->assertSame(['amount' => 80.0, 'commission_amount' => 10.0, 'seller_amount' => 70.0], MarketplaceCommission::split(80));
        $this->assertSame('87,50 €', MarketplaceCommission::sellerAmountLabel(100));

        config(['marketplace.commission_percent' => 250]);
        $this->assertSame(100.0, MarketplaceCommission::percent());
        config(['marketplace.commission_percent' => -5]);
        $this->assertSame(0.0, MarketplaceCommission::percent());
    }

    public function test_a_new_order_freezes_the_commission_of_the_day(): void
    {
        Notification::fake();
        config(['marketplace.commission_percent' => 15]);
        $buyer = User::factory()->create();
        $seller = User::factory()->create();
        $ad = Ad::create([
            'title' => 'Cours de maths', 'description' => 'Cours particuliers de mathématiques.',
            'category' => 'Soutien scolaire', 'location' => 'Mamoudzou', 'price' => 40,
            'service_type' => 'offre', 'status' => 'active', 'user_id' => $seller->id,
        ]);

        $this->actingAs($buyer)->post(route('service-orders.store', $ad), ['amount' => 40])->assertRedirect();

        $order = ServiceOrder::sole();
        $this->assertSame('6.00', (string) $order->commission_amount);
        $this->assertSame('34.00', (string) $order->seller_amount);

        // Un changement de taux ne touche pas une commande existante.
        config(['marketplace.commission_percent' => 30]);
        $this->assertSame('6.00', (string) $order->fresh()->commission_amount);
    }

    public function test_a_client_cannot_pay_while_the_provider_cannot_be_paid(): void
    {
        Notification::fake();
        config(['services.stripe.secret' => 'sk_test_placeholder']);
        $buyer = User::factory()->create(['stripe_id' => 'cus_existing']);
        $seller = User::factory()->create();
        $order = $this->order($buyer, $seller);

        // Aucun appel à Stripe : la commande s'arrête avant l'ouverture du paiement.
        foreach ([1, 2] as $attempt) {
            $this->actingAs($buyer)->post(route('service-orders.checkout', $order))
                ->assertRedirect(route('service-orders.index'))
                ->assertSessionHas('error', fn ($message) => str_contains($message, 'n’a pas encore activé la réception de ses paiements'));
        }

        $order->refresh();
        $this->assertSame(ServiceOrder::PAYMENT_AWAITING, $order->payment_status);
        $this->assertNull($order->stripe_checkout_session_id);
        // Le prestataire est prévenu une seule fois, même si le client réessaie.
        Notification::assertSentToTimes($seller, ServiceOrderStatusNotification::class, 1);
        Notification::assertNotSentTo($buyer, ServiceOrderStatusNotification::class);
    }

    public function test_the_provider_reminder_explains_what_to_do(): void
    {
        $seller = User::factory()->create();
        $order = $this->order(User::factory()->create(), $seller)->load(['ad', 'buyer', 'seller']);

        $data = (new ServiceOrderStatusNotification($order, 'payout_setup_required'))->toArray($seller);

        $this->assertSame('service_order_payout_setup_required', $data['type']);
        $this->assertSame('Un client attend pour vous payer', $data['title']);
        $this->assertStringContainsString('activé la réception de vos paiements', $data['message']);
        $this->assertSame(route('service-orders.index'), $data['action_url']);
    }

    public function test_provider_payment_state_is_read_without_calling_stripe_when_known(): void
    {
        $service = app(StripeConnectService::class);

        $this->assertFalse($service->sellerCanBePaid(null));
        $this->assertFalse($service->sellerCanBePaid(User::factory()->create()));
        $this->assertTrue($service->sellerCanBePaid($this->payableSeller()));
    }

    public function test_funds_are_never_released_twice(): void
    {
        Notification::fake();
        $buyer = User::factory()->create();
        $seller = $this->payableSeller();
        $order = $this->order($buyer, $seller, [
            'status' => ServiceOrder::STATUS_FUNDED, 'payment_status' => ServiceOrder::PAYMENT_PAID,
            'stripe_payment_intent_id' => 'pi_paid', 'paid_at' => now(),
        ]);

        $connect = Mockery::mock(StripeConnectService::class);
        $connect->shouldReceive('transferToSeller')->once()->andReturn('tr_once');
        $this->app->instance(StripeConnectService::class, $connect);

        $this->actingAs($buyer)->post(route('service-orders.release', $order))->assertRedirect(route('service-orders.index'));
        $this->actingAs($buyer)->post(route('service-orders.release', $order))->assertStatus(422);

        $order->refresh();
        $this->assertSame(ServiceOrder::PAYMENT_RELEASED, $order->payment_status);
        $this->assertSame('tr_once', $order->stripe_transfer_id);
        $this->assertDatabaseCount('transactions', 1);
        $this->assertDatabaseHas('transactions', ['user_id' => $seller->id, 'type' => 'SERVICE_ORDER_RELEASE', 'amount' => 180]);
    }

    public function test_a_failed_transfer_leaves_the_funds_blocked_and_explains_why(): void
    {
        Notification::fake();
        $buyer = User::factory()->create();
        $seller = User::factory()->create();
        $order = $this->order($buyer, $seller, [
            'status' => ServiceOrder::STATUS_FUNDED, 'payment_status' => ServiceOrder::PAYMENT_PAID,
            'stripe_payment_intent_id' => 'pi_paid', 'paid_at' => now(),
        ]);

        // Service réel : le prestataire n'a pas de compte de paiement, aucun appel à Stripe n'est tenté.
        $this->actingAs($buyer)->post(route('service-orders.release', $order))
            ->assertRedirect(route('service-orders.index'))
            ->assertSessionHas('error', 'Le prestataire doit d’abord activer la réception de ses paiements.');

        $order->refresh();
        $this->assertSame(ServiceOrder::STATUS_FUNDED, $order->status);
        $this->assertNull($order->stripe_transfer_id);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_the_provider_sees_the_payment_setup_banner_even_as_an_admin(): void
    {
        $seller = User::factory()->create(['role' => 'admin']);
        $this->order(User::factory()->create(), $seller);

        $this->actingAs($seller)->get(route('service-orders.index'))->assertOk()
            ->assertSee('Activez la réception de vos paiements.')
            ->assertSee('Activer mes paiements')
            ->assertSee('Vous recevez');
    }

    public function test_a_stripe_refusal_during_payment_setup_is_explained_instead_of_a_server_error(): void
    {
        $seller = User::factory()->create();
        $this->order(User::factory()->create(), $seller);

        $connect = Mockery::mock(StripeConnectService::class);
        $connect->shouldReceive('createOnboardingLink')->once()
            ->andThrow(new \RuntimeException('You must complete your platform profile to use Connect.'));
        $connect->shouldReceive('syncAccountStatus')->once()->andThrow(new \RuntimeException('Stripe est injoignable.'));
        $this->app->instance(StripeConnectService::class, $connect);

        // Le message technique de Stripe n'est jamais montré au prestataire.
        $this->actingAs($seller)->get(route('service-orders.connect.onboarding'))
            ->assertRedirect(route('service-orders.index'))
            ->assertSessionHas('error', 'L’activation des paiements est momentanément indisponible. Notre équipe est prévenue : réessayez un peu plus tard.');
        $this->actingAs($seller)->get(route('service-orders.connect.return'))
            ->assertRedirect(route('service-orders.index'))
            ->assertSessionHas('error', fn ($message) => str_contains($message, 'n’a pas pu être vérifié'));

        $this->actingAs($seller)->withSession(['error' => 'L’activation des paiements est momentanément indisponible.'])
            ->get(route('service-orders.index'))->assertOk()
            ->assertSee('L’activation des paiements est momentanément indisponible.')
            ->assertDontSee('platform profile');
    }

    public function test_admin_follows_earned_and_pending_commissions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $buyer = User::factory()->create();
        $seller = $this->payableSeller();
        $this->order($buyer, $seller, ['status' => ServiceOrder::STATUS_COMPLETED, 'payment_status' => ServiceOrder::PAYMENT_RELEASED, 'commission_amount' => 20]);
        $this->order($buyer, $seller, ['status' => ServiceOrder::STATUS_COMPLETED, 'payment_status' => ServiceOrder::PAYMENT_RELEASED, 'commission_amount' => 12.5]);
        $this->order($buyer, $seller, ['status' => ServiceOrder::STATUS_FUNDED, 'payment_status' => ServiceOrder::PAYMENT_PAID, 'commission_amount' => 7]);
        // Ni payée ni remboursée : n'entre dans aucun total.
        $this->order($buyer, $seller, ['commission_amount' => 99]);
        $this->order($buyer, $seller, ['status' => ServiceOrder::STATUS_REFUNDED, 'payment_status' => ServiceOrder::PAYMENT_REFUNDED, 'commission_amount' => 50]);

        $this->actingAs($admin)->get(route('admin.service-orders.index'))->assertOk()
            ->assertSee('data-commission="earned">32,50 €<', false)
            ->assertSee('data-commission="pending">7,00 €<', false)
            ->assertSee('data-commission="rate">10 %<', false);
    }

    public function test_the_rate_is_published_in_the_terms_and_on_the_offers_page(): void
    {
        config(['marketplace.commission_percent' => 12]);

        $this->get(route('legal.terms'))->assertOk()
            ->assertSee('La plateforme perçoit une commission de')
            ->assertSee('<strong>12 %</strong>', false)
            ->assertSee('ne donne lieu à aucune commission');
        $this->get(route('public.provider-offers'))->assertOk()->assertSee('12,00 %');
    }
}
