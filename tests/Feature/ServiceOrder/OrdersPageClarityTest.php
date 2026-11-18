<?php

namespace Tests\Feature\ServiceOrder;

use App\Models\Ad;
use App\Models\ServiceOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Page « Mes commandes » : des mots que l'on comprend sans connaître le site,
 * des rôles client / prestataire qui ne s'inversent jamais, et une page que
 * l'on trouve depuis les menus.
 */
class OrdersPageClarityTest extends TestCase
{
    use RefreshDatabase;

    private function ad(User $owner, string $type, string $title): Ad
    {
        return Ad::create([
            'title' => $title, 'description' => 'Description de l’annonce pour le test.',
            'category' => 'Informaticien', 'location' => 'Mamoudzou', 'price' => 120,
            'service_type' => $type, 'status' => 'active', 'user_id' => $owner->id,
        ]);
    }

    private function order(Ad $ad, User $buyer, User $seller, array $attributes = []): ServiceOrder
    {
        return ServiceOrder::create($attributes + [
            'order_number' => 'CMD-TEST-'.fake()->unique()->numerify('#####'),
            'ad_id' => $ad->id, 'buyer_id' => $buyer->id, 'seller_id' => $seller->id,
            'amount' => 120, 'commission_amount' => 12, 'seller_amount' => 108,
            'status' => ServiceOrder::STATUS_PENDING_ACCEPTANCE, 'payment_status' => ServiceOrder::PAYMENT_AWAITING,
        ]);
    }

    public function test_the_page_explains_itself_and_shows_only_the_roles_that_apply(): void
    {
        $client = User::factory()->create(['name' => 'Assia Cliente']);
        $provider = User::factory()->create(['name' => 'Karim Services', 'stripe_connect_account_id' => 'acct_test', 'stripe_connect_payouts_enabled' => true]);
        $this->order($this->ad($provider, 'offre', 'Dépannage informatique'), $client, $provider);

        $page = $this->actingAs($client)->get(route('service-orders.index'))->assertOk()
            ->assertSee('<h1 class="pk-orders__title">Mes commandes</h1>', false)
            ->assertSee('une prestation réservée et payée en ligne')
            ->assertSee('data-orders-role="client"', false)
            ->assertDontSee('data-orders-role="prestataire"', false)
            ->assertSee('doit d’abord accepter votre réservation')
            ->assertSee('css/service-orders.css', false);

        // Le contenu de la page, sans la mise en page commune : ni style en ligne, ni jargon.
        $content = \Illuminate\Support\Str::between($page->getContent(), '<div class="pk-orders">', '</main>');
        $this->assertNotSame('', $content);
        foreach (['<style', 'sécurisée', 'vendeur', 'acheteur', 'Fonds', 'fonds', 'Stripe'] as $unwanted) {
            $this->assertStringNotContainsString($unwanted, $content, "Mot à éviter sur la page : « {$unwanted} ».");
        }

        $this->actingAs($provider)->get(route('service-orders.index'))->assertOk()
            ->assertSee('data-orders-role="prestataire"', false)
            ->assertDontSee('data-orders-role="client"', false)
            ->assertSee('<dt>Client</dt><dd>Assia Cliente</dd>', false)
            ->assertSee('<dt>Commission Prokejem</dt><dd>− 12,00 €</dd>', false)
            ->assertSee('<dt>Vous recevez</dt><dd>108,00 €</dd>', false)
            ->assertSee('Accepter la commande')
            ->assertSee('Refuser cette commande');

        // Sans aucune commande : une seule explication, pas deux blocs vides.
        $this->actingAs(User::factory()->create())->get(route('service-orders.index'))->assertOk()
            ->assertSee('Aucune commande pour le moment')
            ->assertDontSee('data-orders-role', false);
    }

    public function test_a_paid_order_tells_each_side_what_happens_next(): void
    {
        $client = User::factory()->create();
        $provider = User::factory()->create(['name' => 'Karim Services', 'stripe_connect_account_id' => 'acct_test', 'stripe_connect_payouts_enabled' => true]);
        $this->order($this->ad($provider, 'offre', 'Dépannage informatique'), $client, $provider, [
            'status' => ServiceOrder::STATUS_FUNDED, 'payment_status' => ServiceOrder::PAYMENT_PAID, 'paid_at' => now(),
        ]);

        $this->actingAs($client)->get(route('service-orders.index'))->assertOk()
            ->assertSee('Payée')
            ->assertSee('<dt>Paiement</dt><dd>Reçu, conservé en sécurité</dd>', false)
            ->assertSee('Prestation terminée : payer le prestataire')
            ->assertSee('data-pk-confirm="Confirmer que la prestation est terminée ? Karim Services sera payé. Ce versement est définitif."', false)
            ->assertSee('Un problème avec cette prestation ?');

        $this->actingAs($provider)->get(route('service-orders.index'))->assertOk()
            ->assertSee('vous recevrez 108,00 € dès que le client aura confirmé la fin du travail')
            ->assertDontSee('data-order-release', false);
    }

    public function test_a_reservation_made_on_a_demand_can_only_be_refused(): void
    {
        Notification::fake();
        // Avant juillet 2026, on pouvait « réserver » une demande : son auteur devenait le prestataire.
        $author = User::factory()->create();
        $responder = User::factory()->create(['name' => 'Fatima Répondante']);
        $order = $this->order($this->ad($author, 'demande', 'Cherche informaticien'), $responder, $author);

        $this->assertTrue($order->isReservationOnDemand());
        $this->assertFalse($order->canSellerAccept());

        $this->actingAs($author)->get(route('service-orders.index'))->assertOk()
            ->assertSee('data-order-misdirected', false)
            ->assertSee('À refuser')
            ->assertSee('c’est vous le client')
            ->assertSee('<dt>Envoyée par</dt><dd>Fatima Répondante</dd>', false)
            ->assertSee('data-order-refuse', false)
            ->assertDontSee('Accepter la commande')
            ->assertDontSee('Vous recevez');

        $this->actingAs($responder)->get(route('service-orders.index'))->assertOk()
            ->assertSee('Cette annonce est une demande')
            ->assertSee('<dt>Auteur de la demande</dt>', false)
            ->assertDontSee('<dt>Prestataire</dt>', false)
            ->assertSee('data-order-cancel="buyer"', false);

        $this->actingAs($author)->post(route('service-orders.accept', $order))->assertStatus(422);
        $this->assertSame(ServiceOrder::STATUS_PENDING_ACCEPTANCE, $order->fresh()->status);

        $this->actingAs($author)->post(route('service-orders.refuse', $order))->assertRedirect(route('service-orders.index'));
        $this->assertSame(ServiceOrder::STATUS_REFUSED, $order->fresh()->status);
    }

    public function test_a_reservation_on_a_demand_already_accepted_is_cancelled_never_paid(): void
    {
        Notification::fake();
        config(['services.stripe.secret' => 'sk_test_placeholder']);
        // L'auteur de la demande a accepté avant la correction : la commande attend un paiement qui n'a pas lieu d'être.
        $author = User::factory()->create(['stripe_connect_account_id' => 'acct_test', 'stripe_connect_payouts_enabled' => true]);
        $responder = User::factory()->create(['name' => 'Fatima Répondante', 'stripe_id' => 'cus_existing']);
        $order = $this->order($this->ad($author, 'demande', 'Cherche informaticien'), $responder, $author, [
            'status' => ServiceOrder::STATUS_AWAITING_PAYMENT, 'accepted_at' => now(),
        ]);

        $this->actingAs($author)->get(route('service-orders.index'))->assertOk()
            ->assertSee('À annuler')
            ->assertSee('Annulez-la, puis invitez Fatima Répondante à vous envoyer une proposition.')
            ->assertSee('class="pk-orders__btn" data-order-cancel="seller"', false)
            ->assertDontSee('Le client doit maintenant payer en ligne')
            ->assertDontSee('Vous recevez');

        $this->actingAs($responder)->get(route('service-orders.index'))->assertOk()
            ->assertSee('À annuler')
            ->assertSee('Cette annonce est une demande')
            ->assertSee('class="pk-orders__btn" data-order-cancel="buyer"', false)
            ->assertDontSee('Payer en ligne');

        // Aucun appel à Stripe : le paiement est refusé avant.
        $this->actingAs($responder)->post(route('service-orders.checkout', $order))
            ->assertRedirect(route('service-orders.index'))
            ->assertSessionHas('error', fn ($message) => str_contains($message, 'ne peut pas être payée'));
        $this->assertNull($order->fresh()->stripe_checkout_session_id);

        $this->actingAs($author)->post(route('service-orders.cancel', $order))->assertRedirect(route('service-orders.index'));
        $this->assertSame(ServiceOrder::STATUS_CANCELED, $order->fresh()->status);
    }

    public function test_a_normal_order_keeps_its_roles(): void
    {
        $client = User::factory()->create();
        $provider = User::factory()->create();

        // Réservation d'une offre : l'auteur de l'offre est le prestataire.
        $this->assertFalse($this->order($this->ad($provider, 'offre', 'Dépannage'), $client, $provider)->isReservationOnDemand());
        // Proposition choisie sur une demande : l'auteur de la demande est le client.
        $this->assertFalse($this->order($this->ad($client, 'demande', 'Cherche un dépanneur'), $client, $provider)->isReservationOnDemand());
    }

    public function test_the_orders_page_is_reachable_from_the_menus_with_a_measured_counter(): void
    {
        $member = User::factory()->create(['user_type' => 'professionnel', 'account_type' => 'professionnel']);
        $other = User::factory()->create();
        $offer = $this->ad($member, 'offre', 'Dépannage informatique');
        $link = 'href="'.route('service-orders.index').'" class="menu-item active" data-menu="orders"';

        $this->assertSame(0, ServiceOrder::awaitingActionCount($member));
        $this->actingAs($member)->get(route('service-orders.index'))->assertOk()
            ->assertSee($link, false)
            ->assertSee('<i class="fas fa-receipt" style="color: #4f46e5;"></i>Mes commandes', false)
            ->assertDontSee('Commandes à accepter ou à payer');

        $this->order($offer, $other, $member);                                                              // à accepter
        $this->order($this->ad($other, 'offre', 'Jardinage'), $member, $other, ['status' => ServiceOrder::STATUS_AWAITING_PAYMENT]); // à payer
        $this->order($offer, $other, $member, ['status' => ServiceOrder::STATUS_COMPLETED]);                  // rien à faire
        $this->order($this->ad($other, 'offre', 'Peinture'), $member, $other);                               // attend l'autre partie

        $this->assertSame(2, ServiceOrder::awaitingActionCount($member));
        $this->actingAs($member)->get(route('service-orders.index'))->assertOk()
            ->assertSee('title="Commandes à accepter ou à payer">2</span>', false);
    }
}
