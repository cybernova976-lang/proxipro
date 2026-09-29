<?php

namespace Tests\Feature\Demand;

use App\Models\Ad;
use App\Models\ServiceOrder;
use App\Models\ServiceProposal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemandTrackingFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('demands.tracking'))
            ->assertRedirect(route('login'));
    }

    public function test_client_sees_only_owned_demands_with_a_real_next_action(): void
    {
        $client = User::factory()->create([
            'user_type' => 'particulier',
            'is_service_provider' => false,
        ]);
        $provider = User::factory()->create([
            'name' => 'Prestataire Exemple',
            'user_type' => 'professionnel',
            'is_service_provider' => true,
        ]);
        $otherClient = User::factory()->create();

        $fresh = $this->demand($client, 'Besoin fraîchement publié');
        $old = $this->demand($client, 'Besoin à améliorer');
        $old->forceFill(['created_at' => now()->subHours(4), 'updated_at' => now()->subHours(4)])->save();

        $answered = $this->demand($client, 'Besoin avec des propositions');
        ServiceProposal::create([
            'ad_id' => $answered->id,
            'provider_id' => $provider->id,
            'amount' => 80,
            'message' => 'Je peux intervenir avec le matériel adapté dès demain.',
            'status' => ServiceProposal::STATUS_PENDING,
        ]);

        $funded = $this->demand($client, 'Mission financée');
        $order = $this->order($funded, $client, $provider, ServiceOrder::STATUS_FUNDED, ServiceOrder::PAYMENT_PAID);

        $this->demand($otherClient, 'Demande privée d’un autre client');
        Ad::create([
            'title' => 'Offre du client à exclure',
            'description' => 'Cette offre ne doit pas apparaître dans le suivi des demandes.',
            'category' => 'Plombier',
            'location' => 'Mamoudzou',
            'service_type' => 'offre',
            'status' => 'active',
            'user_id' => $client->id,
        ]);

        $response = $this->actingAs($client)
            ->get(route('home'))
            ->assertOk();

        $items = $response->viewData('activityItems')->getCollection()->keyBy('key');
        $this->assertSame('waiting', $items['request-'.$fresh->id]['stage']);
        $this->assertSame('waiting', $items['request-'.$old->id]['stage']);
        $this->assertSame('action', $items['request-'.$answered->id]['stage']);
        $this->assertSame('ongoing', $items['order-'.$order->id]['stage']);
        $this->assertFalse($items->has('request-'.$funded->id), 'La demande attribuée ne doit pas dupliquer sa commande.');

        $response
            ->assertSee('Mon suivi')
            ->assertSee('En attente de propositions')
            ->assertDontSee('Toujours aucune proposition')
            ->assertSee('1 proposition à examiner')
            ->assertSee('Mission en cours')
            ->assertSee(route('ads.edit', $old), false)
            ->assertSee(route('proposals.compare', $answered), false)
            ->assertSee(route('service-orders.index').'#order-'.$order->id, false)
            ->assertDontSee('Demande privée d’un autre client')
            ->assertDontSee('Offre du client à exclure');

        $this->assertSame(4, $response->viewData('activityItems')->total());
        $this->assertSame(2, $response->viewData('activityCounts')['waiting']);
        $this->assertSame(1, $response->viewData('activityCounts')['action']);
        $this->assertSame(1, $response->viewData('activityCounts')['ongoing']);
    }

    public function test_completed_demand_reaches_the_last_step_and_navigation_links_to_tracking(): void
    {
        $client = User::factory()->create([
            'user_type' => 'particulier',
            'is_service_provider' => false,
        ]);
        $provider = User::factory()->create([
            'user_type' => 'professionnel',
            'is_service_provider' => true,
        ]);
        $demand = $this->demand($client, 'Mission terminée avec succès');
        $order = $this->order($demand, $client, $provider, ServiceOrder::STATUS_COMPLETED, ServiceOrder::PAYMENT_RELEASED);

        $response = $this->actingAs($client)
            ->get(route('home', ['etat' => 'closed']))
            ->assertOk();

        $this->assertSame('order-'.$order->id, $response->viewData('activityItems')->first()['key']);
        $this->assertSame('closed', $response->viewData('activityItems')->first()['stage']);
        $this->assertSame(1, $response->viewData('activityCounts')['closed']);

        $response
            ->assertSee('Mission terminée')
            ->assertSee('Historique')
            ->assertSee('aria-current="page"', false)
            ->assertSee(route('service-orders.index').'#order-'.$order->id, false)
            ->assertSee('Suivi', false);

        $this->get(route('demands.tracking', ['etat' => 'closed']))
            ->assertRedirect(route('home', ['etat' => 'closed']));

    }

    private function demand(User $client, string $title): Ad
    {
        return Ad::create([
            'title' => $title,
            'description' => 'Une description suffisamment précise pour tester le suivi client.',
            'main_category' => 'Bricolage & Travaux',
            'category' => 'Plombier',
            'city' => 'Mamoudzou',
            'location' => 'Mamoudzou',
            'service_type' => 'demande',
            'status' => 'active',
            'visibility' => 'public',
            'expires_at' => now()->addDays(30),
            'user_id' => $client->id,
        ]);
    }

    private function order(Ad $demand, User $client, User $provider, string $status, string $paymentStatus): ServiceOrder
    {
        return ServiceOrder::create([
            'order_number' => 'CMD-TRACK-'.$demand->id,
            'ad_id' => $demand->id,
            'buyer_id' => $client->id,
            'seller_id' => $provider->id,
            'amount' => 100,
            'commission_amount' => 10,
            'seller_amount' => 90,
            'status' => $status,
            'payment_status' => $paymentStatus,
        ]);
    }
}
