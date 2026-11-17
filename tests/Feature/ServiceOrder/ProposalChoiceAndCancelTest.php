<?php

namespace Tests\Feature\ServiceOrder;

use App\Models\Ad;
use App\Models\ServiceOrder;
use App\Models\ServiceProposal;
use App\Models\User;
use App\Notifications\ServiceOrderStatusNotification;
use App\Services\ClientActivityService;
use App\Services\StripeConnectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Mockery;
use RuntimeException;
use Tests\TestCase;

/**
 * Du choix d'une proposition au paiement : le client est prévenu avant de
 * s'engager, une commande non payée peut être annulée, et les nombres
 * affichés ne comptent que les propositions encore à comparer.
 */
class ProposalChoiceAndCancelTest extends TestCase
{
    use RefreshDatabase;

    private function provider(array $attributes = []): User
    {
        return User::factory()->create($attributes + [
            'user_type' => 'professionnel', 'account_type' => 'professionnel',
            'is_service_provider' => true, 'service_category' => 'Plombier',
        ]);
    }

    private function demand(User $client, array $attributes = []): Ad
    {
        return Ad::create($attributes + [
            'user_id' => $client->id, 'title' => 'Réparer une fuite sous l’évier',
            'description' => 'Fuite sous l’évier de la cuisine, à réparer rapidement.', 'category' => 'Plombier',
            'city' => 'Mamoudzou', 'country' => 'Mayotte', 'location' => 'Mamoudzou', 'postal_code' => '97600',
            'service_type' => 'demande', 'status' => 'active', 'visibility' => 'public',
        ]);
    }

    private function proposal(Ad $demand, User $provider, array $attributes = []): ServiceProposal
    {
        return ServiceProposal::create($attributes + [
            'ad_id' => $demand->id, 'provider_id' => $provider->id, 'amount' => 120,
            'message' => 'Je peux intervenir demain matin avec le matériel nécessaire.',
            'status' => ServiceProposal::STATUS_PENDING,
        ]);
    }

    /** Commande née du choix d'une proposition, en attente de paiement. */
    private function chosenOrder(User $client, User $provider): ServiceOrder
    {
        $proposal = $this->proposal($this->demand($client), $provider);
        $this->actingAs($client)->post(route('proposals.accept', $proposal))->assertRedirect(route('service-orders.index'));

        return ServiceOrder::sole();
    }

    public function test_the_client_is_told_what_choosing_a_proposal_does(): void
    {
        $client = User::factory()->create();
        $demand = $this->demand($client);
        $this->proposal($demand, $this->provider(['name' => 'Amina Plomberie']));

        $this->actingAs($client)->get(route('proposals.compare', $demand))->assertOk()
            ->assertSee('Quand vous choisissez une proposition :')
            ->assertSee('une commande est créée au prix proposé, à régler en ligne, sans frais ajoutés')
            ->assertSee('Tant que vous n’avez pas payé, vous pouvez annuler la commande.')
            ->assertSee('data-pk-confirm="Choisir la proposition de Amina Plomberie à 120,00 € ?', false)
            ->assertSee('js/confirm-submit.js', false);

        $this->actingAs($client)->get(route('proposals.index'))->assertOk()
            ->assertSee('data-pk-confirm="Choisir la proposition de Amina Plomberie à 120,00 € ?', false);
    }

    public function test_the_chosen_proposal_leads_to_payment_only_while_the_order_is_unpaid(): void
    {
        $client = User::factory()->create();
        $order = $this->chosenOrder($client, $this->provider());
        $compare = route('proposals.compare', $order->ad_id);
        $link = route('service-orders.index').'#order-'.$order->id;

        $this->actingAs($client)->get($compare)->assertOk()
            ->assertSee('href="'.$link.'"', false)
            ->assertSee('Continuer vers le paiement')
            // Plus aucune proposition en attente : rien à expliquer sur le choix.
            ->assertDontSee('Quand vous choisissez une proposition :');

        $order->forceFill(['status' => ServiceOrder::STATUS_FUNDED, 'payment_status' => ServiceOrder::PAYMENT_PAID])->save();

        $this->actingAs($client)->get($compare)->assertOk()
            ->assertSee('href="'.$link.'"', false)
            ->assertSee('Voir la commande')
            ->assertDontSee('Continuer vers le paiement');
    }

    public function test_the_client_can_cancel_an_order_he_has_not_paid(): void
    {
        Notification::fake();
        $client = User::factory()->create();
        $provider = $this->provider();
        $order = $this->chosenOrder($client, $provider);
        $activity = app(ClientActivityService::class);

        $this->assertSame(0, $activity->requestsQuery($client)->count(), 'La demande est suivie par sa commande.');
        $this->actingAs($client)->get(route('service-orders.index'))->assertOk()
            ->assertSee('data-order-cancel="buyer"', false);

        // Aucun paiement ouvert : aucun appel à Stripe n'est nécessaire.
        $this->actingAs($client)->post(route('service-orders.cancel', $order))
            ->assertRedirect(route('service-orders.index'))
            ->assertSessionHas('success', fn ($message) => str_contains($message, 'rien ne vous sera prélevé'));

        $order->refresh();
        $this->assertSame(ServiceOrder::STATUS_CANCELED, $order->status);
        $this->assertSame(ServiceOrder::PAYMENT_CANCELED, $order->payment_status);
        $this->assertSame('buyer', $order->metadata['canceled_by']);
        $this->assertNotNull($order->canceled_at);
        $this->assertSame('Annulée', $order->status_label);
        $this->assertSame(ServiceProposal::STATUS_REFUSED, ServiceProposal::sole()->status);
        // La demande redevient une demande ouverte dans le suivi du client.
        $this->assertSame(1, $activity->requestsQuery($client)->count());

        Notification::assertSentTo($provider, ServiceOrderStatusNotification::class,
            fn ($notification) => $notification->toArray($provider)['type'] === 'service_order_canceled_by_buyer');

        // Une commande annulée ne s'annule pas deux fois et ne se paie plus.
        $this->actingAs($client)->post(route('service-orders.cancel', $order))->assertStatus(422);
        $this->actingAs($client)->post(route('service-orders.checkout', $order))->assertStatus(422);
        $this->actingAs($client)->get(route('service-orders.index'))->assertOk()
            ->assertSee('Aucun paiement n’a été encaissé.')
            ->assertDontSee('data-order-cancel', false);
    }

    public function test_the_provider_can_cancel_an_unpaid_order_but_not_a_stranger(): void
    {
        Notification::fake();
        $client = User::factory()->create();
        $provider = $this->provider();
        $order = $this->chosenOrder($client, $provider);

        $this->actingAs(User::factory()->create())->post(route('service-orders.cancel', $order))->assertForbidden();

        $this->actingAs($provider)->get(route('service-orders.index'))->assertOk()
            ->assertSee('data-order-cancel="seller"', false);
        $this->actingAs($provider)->post(route('service-orders.cancel', $order))
            ->assertRedirect(route('service-orders.index'))
            ->assertSessionHas('success', 'Commande annulée. Le client en est informé.');

        $this->assertSame(ServiceOrder::STATUS_CANCELED, $order->fresh()->status);
        $this->assertSame('seller', $order->fresh()->metadata['canceled_by']);
        $this->assertSame(ServiceProposal::STATUS_WITHDRAWN, ServiceProposal::sole()->status);
        Notification::assertSentTo($client, ServiceOrderStatusNotification::class,
            fn ($notification) => $notification->toArray($client)['type'] === 'service_order_canceled_by_seller');
    }

    public function test_only_the_client_can_cancel_before_the_provider_accepts(): void
    {
        Notification::fake();
        $client = User::factory()->create();
        $provider = $this->provider();
        $offer = $this->demand($provider, ['service_type' => 'offre', 'title' => 'Dépannage plomberie']);
        $order = ServiceOrder::create([
            'order_number' => 'CMD-TEST-PENDING', 'ad_id' => $offer->id, 'buyer_id' => $client->id, 'seller_id' => $provider->id,
            'amount' => 80, 'commission_amount' => 8, 'seller_amount' => 72,
            'status' => ServiceOrder::STATUS_PENDING_ACCEPTANCE, 'payment_status' => ServiceOrder::PAYMENT_AWAITING,
        ]);

        // Avant d'accepter, le prestataire refuse : il n'annule pas.
        $this->actingAs($provider)->post(route('service-orders.cancel', $order))->assertStatus(422);
        $this->actingAs($client)->post(route('service-orders.cancel', $order))->assertRedirect(route('service-orders.index'));

        $this->assertSame(ServiceOrder::STATUS_CANCELED, $order->fresh()->status);
    }

    public function test_cancelling_closes_the_open_payment_page_first(): void
    {
        Notification::fake();
        $client = User::factory()->create();
        $order = $this->chosenOrder($client, $this->provider());
        $order->forceFill(['payment_status' => ServiceOrder::PAYMENT_CHECKOUT_OPEN, 'stripe_checkout_session_id' => 'cs_open'])->save();

        $connect = Mockery::mock(StripeConnectService::class);
        $connect->shouldReceive('expireCheckoutSession')->once()
            ->with(Mockery::on(fn (ServiceOrder $closed) => $closed->stripe_checkout_session_id === 'cs_open'));
        $this->app->instance(StripeConnectService::class, $connect);

        $this->actingAs($client)->post(route('service-orders.cancel', $order))->assertRedirect(route('service-orders.index'));

        $this->assertSame(ServiceOrder::STATUS_CANCELED, $order->fresh()->status);
    }

    public function test_an_order_being_paid_or_already_paid_is_never_cancelled(): void
    {
        Notification::fake();
        $client = User::factory()->create();
        $order = $this->chosenOrder($client, $this->provider());
        $order->forceFill(['payment_status' => ServiceOrder::PAYMENT_CHECKOUT_OPEN, 'stripe_checkout_session_id' => 'cs_paid'])->save();

        // Le client vient de payer : Stripe le dit, la commande reste payable.
        $connect = Mockery::mock(StripeConnectService::class);
        $connect->shouldReceive('expireCheckoutSession')->once()
            ->andThrow(new RuntimeException('Un paiement vient d’être enregistré pour cette commande : elle ne peut plus être annulée.'));
        $this->app->instance(StripeConnectService::class, $connect);

        $this->actingAs($client)->post(route('service-orders.cancel', $order))
            ->assertRedirect(route('service-orders.index'))
            ->assertSessionHas('error', fn ($message) => str_contains($message, 'ne peut plus être annulée'));
        $this->assertSame(ServiceOrder::STATUS_AWAITING_PAYMENT, $order->fresh()->status);
        $this->assertSame(ServiceProposal::STATUS_ACCEPTED, ServiceProposal::sole()->status);

        // Une fois les fonds bloqués, l'annulation n'existe plus : il reste le litige.
        $order->forceFill(['status' => ServiceOrder::STATUS_FUNDED, 'payment_status' => ServiceOrder::PAYMENT_PAID])->save();
        $this->actingAs($client)->post(route('service-orders.cancel', $order))->assertStatus(422);
        Notification::assertNotSentTo($order->seller, ServiceOrderStatusNotification::class,
            fn ($notification) => str_contains($notification->toArray($order->seller)['type'], 'canceled'));
    }

    public function test_the_client_order_card_shows_no_commission_and_shows_errors(): void
    {
        $client = User::factory()->create();
        $this->chosenOrder($client, $this->provider(['name' => 'Karim Services']));

        $this->actingAs($client)->withSession(['error' => 'Le prestataire n’a pas encore activé la réception de ses paiements.'])
            ->get(route('service-orders.index'))->assertOk()
            ->assertSee('<div class="alert alert-danger" role="alert">Le prestataire n’a pas encore activé la réception de ses paiements.</div>', false)
            ->assertSee('Frais ajoutés')
            ->assertSee('Prestataire : <strong>Karim Services</strong>', false)
            ->assertSee('Payer en ligne')
            ->assertSee('le paiement en ligne sera possible dès que ce sera fait')
            ->assertDontSee('service-order-stat-label">Commission', false)
            ->assertDontSee('Connect vendeur')
            ->assertDontSee('Payer avec Stripe');
    }

    public function test_the_feed_counts_only_proposals_still_to_compare(): void
    {
        $viewer = $this->provider();
        $client = User::factory()->create();
        $answered = $this->demand($client, ['title' => 'Demande avec une réponse active']);
        $this->proposal($answered, $this->provider());
        $this->proposal($answered, $this->provider(), ['status' => ServiceProposal::STATUS_WITHDRAWN]);
        $this->proposal($answered, $this->provider(), ['status' => ServiceProposal::STATUS_REFUSED]);
        $emptied = $this->demand($client, ['title' => 'Demande dont la réponse a été retirée']);
        $this->proposal($emptied, $this->provider(), ['status' => ServiceProposal::STATUS_WITHDRAWN]);
        $untouched = $this->demand($client, ['title' => 'Demande sans aucune réponse']);

        $html = $this->actingAs($viewer)->get(route('feed'))->assertOk()->getContent();
        $cards = [];
        foreach (array_slice(explode('<article class="pk-ad', $html), 1) as $chunk) {
            if (preg_match('#<h3><a [^>]*>([^<]+)</a></h3>#u', $chunk, $match)) {
                $cards[html_entity_decode(trim($match[1]), ENT_QUOTES)] = preg_replace('/\s+/u', ' ', $chunk);
            }
        }

        $this->assertStringContainsString('1 réponse ', $cards[$answered->title]);
        $this->assertStringNotContainsString('3 réponses', $cards[$answered->title]);
        $this->assertStringContainsString('Aucune réponse en cours', $cards[$emptied->title]);
        $this->assertStringNotContainsString('Soyez le premier', $cards[$emptied->title]);
        $this->assertStringContainsString('Soyez le premier à répondre', $cards[$untouched->title]);
    }
}
