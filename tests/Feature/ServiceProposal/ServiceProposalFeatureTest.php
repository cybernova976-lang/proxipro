<?php

namespace Tests\Feature\ServiceProposal;

use App\Models\Ad;
use App\Models\ServiceOrder;
use App\Models\ServiceProposal;
use App\Models\User;
use App\Notifications\ServiceProposalReceivedNotification;
use App\Notifications\ServiceProposalStatusNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ServiceProposalFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_provider_can_quote_a_demand_and_client_acceptance_creates_payable_order(): void
    {
        Notification::fake();

        $client = User::factory()->create();
        $provider = User::factory()->create(['user_type' => 'professionnel']);
        $otherProvider = User::factory()->create(['is_service_provider' => true]);
        $demand = $this->createDemand($client);

        $this->actingAs($provider)
            ->post(route('proposals.store', $demand), [
                'amount' => 250,
                'message' => 'Je peux intervenir avec le materiel inclus des la semaine prochaine.',
                'scheduled_for' => now()->addDays(3)->toDateString(),
            ])
            ->assertRedirect(route('proposals.index'));

        $proposal = ServiceProposal::where('provider_id', $provider->id)->firstOrFail();
        Notification::assertSentTo($client, ServiceProposalReceivedNotification::class);

        $otherProposal = ServiceProposal::create([
            'ad_id' => $demand->id,
            'provider_id' => $otherProvider->id,
            'amount' => 275,
            'message' => 'Autre proposition suffisamment detaillee pour le client.',
            'status' => ServiceProposal::STATUS_PENDING,
        ]);

        $this->actingAs($client)
            ->post(route('proposals.accept', $proposal))
            ->assertRedirect(route('service-orders.index'));

        $proposal->refresh();
        $this->assertSame(ServiceProposal::STATUS_ACCEPTED, $proposal->status);
        $this->assertNotNull($proposal->service_order_id);
        $this->assertSame(ServiceProposal::STATUS_REFUSED, $otherProposal->fresh()->status);

        $this->assertDatabaseHas('service_orders', [
            'id' => $proposal->service_order_id,
            'ad_id' => $demand->id,
            'buyer_id' => $client->id,
            'seller_id' => $provider->id,
            'amount' => 250,
            'commission_amount' => 25,
            'seller_amount' => 225,
            'status' => ServiceOrder::STATUS_AWAITING_PAYMENT,
            'payment_status' => ServiceOrder::PAYMENT_AWAITING,
        ]);

        Notification::assertSentTo($provider, ServiceProposalStatusNotification::class);
        Notification::assertSentTo($otherProvider, ServiceProposalStatusNotification::class);
    }

    public function test_non_provider_cannot_send_a_quote(): void
    {
        $client = User::factory()->create();
        $visitor = User::factory()->create([
            'user_type' => 'particulier',
            'is_service_provider' => false,
        ]);

        $this->actingAs($visitor)
            ->post(route('proposals.store', $this->createDemand($client)), [
                'amount' => 100,
                'message' => 'Je tente une proposition sans profil prestataire actif.',
            ])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('service_proposals', 0);
    }

    private function createDemand(User $client): Ad
    {
        return Ad::create([
            'title' => 'Renovation salle de bain',
            'description' => 'Recherche un professionnel disponible et assure.',
            'category' => 'Plomberie',
            'location' => 'Mamoudzou',
            'price' => 250,
            'service_type' => 'demande',
            'status' => 'active',
            'user_id' => $client->id,
        ]);
    }
}
