<?php

namespace Tests\Feature\ServiceOrder;

use App\Models\Ad;
use App\Models\ServiceOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Signalement du fondateur du 24/11/2026 : une demande refusait d'être supprimée
 * (« elle porte une commande en cours ») sans qu'aucun bouton ne permette de traiter
 * cette commande. C'était une réservation faite par erreur sur la demande, dont les
 * boutons n'existent que dans « Mes commandes ».
 */
class MisdirectedReservationFromDemandsPageTest extends TestCase
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

    private function order(Ad $ad, User $buyer, User $seller, string $status = ServiceOrder::STATUS_PENDING_ACCEPTANCE): ServiceOrder
    {
        return ServiceOrder::create([
            'order_number' => 'CMD-TEST-'.fake()->unique()->numerify('#####'),
            'ad_id' => $ad->id, 'buyer_id' => $buyer->id, 'seller_id' => $seller->id,
            'amount' => 120, 'commission_amount' => 12, 'seller_amount' => 108,
            'status' => $status, 'payment_status' => ServiceOrder::PAYMENT_AWAITING,
        ]);
    }

    public function test_the_demands_page_leads_to_the_reservation_to_refuse_instead_of_a_dead_delete_button(): void
    {
        Notification::fake();
        $author = User::factory()->create(['user_type' => 'particulier', 'is_service_provider' => false]);
        $responder = User::factory()->create(['name' => 'Fatima Répondante']);
        $demand = $this->ad($author, 'demande', 'Cherche informaticien');
        $order = $this->order($demand, $responder, $author);
        $orderUrl = route('service-orders.index').'#order-'.$order->id;

        $this->actingAs($author)->get(route('home'))->assertOk()
            ->assertSee('Réservation à refuser')
            ->assertSee('Fatima Répondante a réservé cette demande par erreur, comme si c’était une offre. Refusez cette réservation')
            ->assertSee('href="'.$orderUrl.'" class="pk-dash-primary"', false)
            ->assertSee('Refuser la réservation')
            ->assertDontSee('action="'.route('ads.destroy', $demand).'"', false);

        // La réservation est rangée parmi ce qui est « à traiter ».
        $this->get(route('home', ['etat' => 'action']))->assertOk()->assertSee('Réservation à refuser');

        // « Mes commandes » ne demande pas d'activer les paiements pour une réservation à refuser.
        $this->get(route('service-orders.index'))->assertOk()
            ->assertSee('id="order-'.$order->id.'"', false)
            ->assertDontSee('Activer mes paiements');

        // Une suppression tentée quand même mène à la réservation, avec la marche à suivre.
        $this->delete(route('ads.destroy', $demand))
            ->assertRedirect($orderUrl)
            ->assertSessionHas('info', 'Cette demande a été réservée par erreur, comme si c’était une offre. Refusez cette réservation ci-dessous : vous pourrez ensuite supprimer la demande.');
        $this->assertModelExists($demand);

        // Une fois la réservation refusée, la demande redevient une demande ordinaire et se supprime.
        $this->post(route('service-orders.refuse', $order))->assertRedirect(route('service-orders.index'));
        $this->get(route('home'))->assertOk()
            ->assertDontSee('Réservation à refuser')
            ->assertSee('action="'.route('ads.destroy', $demand).'"', false);
        $this->delete(route('ads.destroy', $demand))->assertSessionHas('success');
        $this->assertModelMissing($demand);
    }

    public function test_an_accepted_reservation_on_a_demand_is_to_cancel(): void
    {
        $author = User::factory()->create(['user_type' => 'particulier', 'is_service_provider' => false]);
        $demand = $this->ad($author, 'demande', 'Cherche informaticien');
        $order = $this->order($demand, User::factory()->create(), $author, ServiceOrder::STATUS_AWAITING_PAYMENT);

        $this->actingAs($author)->get(route('home'))->assertOk()
            ->assertSee('Réservation à annuler')->assertSee('Annuler la réservation');
        $this->delete(route('ads.destroy', $demand))
            ->assertRedirect(route('service-orders.index').'#order-'.$order->id)
            ->assertSessionHas('info', 'Cette demande a été réservée par erreur, comme si c’était une offre. Annulez cette réservation ci-dessous : vous pourrez ensuite supprimer la demande.');
    }

    public function test_an_offer_with_a_real_order_in_progress_also_leads_to_that_order(): void
    {
        $provider = User::factory()->create(['user_type' => 'professionnel', 'is_service_provider' => true]);
        $offer = $this->ad($provider, 'offre', 'Dépannage informatique');
        $order = $this->order($offer, User::factory()->create(), $provider, ServiceOrder::STATUS_FUNDED);

        $this->actingAs($provider)->delete(route('ads.destroy', $offer))
            ->assertRedirect(route('service-orders.index').'#order-'.$order->id)
            ->assertSessionHas('info', 'Cette annonce porte une commande en cours. Terminez-la ou annulez-la ci-dessous : vous pourrez ensuite supprimer l’annonce.');
        $this->assertModelExists($offer);
    }
}
