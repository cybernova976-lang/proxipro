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
            ->get(route('demands.tracking'))
            ->assertOk();

        $items = $response->viewData('demands')->getCollection()->keyBy(fn ($item) => $item['demand']->id);
        $this->assertSame('published', $items[$fresh->id]['key']);
        $this->assertSame('attention', $items[$old->id]['key']);
        $this->assertSame('proposals', $items[$answered->id]['key']);
        $this->assertSame('funded', $items[$funded->id]['key']);

        $response
            ->assertSee('Suivi de mes demandes')
            ->assertSee('Recherche de prestataires en cours')
            ->assertSee('Toujours aucune proposition')
            ->assertSee('1 proposition à comparer')
            ->assertSee('Mission en cours · fonds protégés')
            ->assertSee(route('ads.edit', $old), false)
            ->assertSee(route('proposals.compare', $answered), false)
            ->assertSee(route('service-orders.index').'#order-'.$order->id, false)
            ->assertDontSee('Demande privée d’un autre client')
            ->assertDontSee('Offre du client à exclure');

        $this->assertSame(4, $response->viewData('summary')['total']);
        $this->assertSame(2, $response->viewData('summary')['awaiting']);
        $this->assertSame(1, $response->viewData('summary')['responses']);
        $this->assertSame(1, $response->viewData('summary')['active']);
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
            ->get(route('demands.tracking'))
            ->assertOk();

        $this->assertSame('completed', $response->viewData('demands')->first()['key']);
        $this->assertSame(5, $response->viewData('demands')->first()['step']);

        $response
            ->assertSee('Mission terminée')
            ->assertSee('aria-current="step"', false)
            ->assertSee(route('service-orders.index').'#order-'.$order->id, false)
            ->assertSee('Suivi', false);

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
