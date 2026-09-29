<?php

namespace Tests\Feature\Pro;

use App\Models\Ad;
use App\Models\ProInvoice;
use App\Models\ServiceOrder;
use App\Models\ServiceProposal;
use App\Models\User;
use App\Models\UserService;
use App\Support\PlatformFeatures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProviderOpportunityPipelineFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_provider_sees_each_matching_item_in_the_correct_pipeline_stage(): void
    {
        $provider = User::factory()->create([
            'is_service_provider' => true,
            'user_type' => 'particulier',
            'city' => 'Mamoudzou',
        ]);
        $client = User::factory()->create(['name' => 'Client Pipeline']);

        UserService::create([
            'user_id' => $provider->id,
            'main_category' => 'Bricolage & Travaux',
            'subcategory' => 'Plombier',
            'description' => 'Interventions de plomberie.',
            'is_active' => true,
        ]);

        $newDemand = $this->createDemand($client, 'Fuite à réparer rapidement');
        $nonMatchingDemand = $this->createDemand(
            $client,
            'Recherche une garde après l’école',
            'Baby-sitter',
            'Aide à domicile'
        );

        $proposedDemand = $this->createDemand($client, 'Robinet à remplacer');
        ServiceProposal::create([
            'ad_id' => $proposedDemand->id,
            'provider_id' => $provider->id,
            'amount' => 95,
            'message' => 'Je peux remplacer votre robinet avec le matériel nécessaire.',
            'scheduled_for' => now()->addDays(2),
            'status' => ServiceProposal::STATUS_PENDING,
        ]);

        $activeDemand = $this->createDemand($client, 'Canalisation à déboucher');
        $activeOrder = $this->createOrder(
            $activeDemand,
            $client,
            $provider,
            'CMD-PIPELINE-ACTIVE',
            ServiceOrder::STATUS_FUNDED,
            ServiceOrder::PAYMENT_PAID
        );
        ServiceProposal::create([
            'ad_id' => $activeDemand->id,
            'provider_id' => $provider->id,
            'service_order_id' => $activeOrder->id,
            'amount' => 180,
            'message' => 'Intervention de débouchage confirmée et planifiée avec le client.',
            'status' => ServiceProposal::STATUS_ACCEPTED,
        ]);

        $completedDemand = $this->createDemand($client, 'Chauffe-eau remis en service');
        $completedOrder = $this->createOrder(
            $completedDemand,
            $client,
            $provider,
            'CMD-PIPELINE-DONE',
            ServiceOrder::STATUS_COMPLETED,
            ServiceOrder::PAYMENT_RELEASED,
            ['released_at' => now()->subDay()]
        );
        ServiceProposal::create([
            'ad_id' => $completedDemand->id,
            'provider_id' => $provider->id,
            'service_order_id' => $completedOrder->id,
            'amount' => 180,
            'message' => 'Remise en service du chauffe-eau terminée avec succès.',
            'status' => ServiceProposal::STATUS_ACCEPTED,
        ]);

        $response = $this->actingAs($provider)->get(route('pro.opportunities'));

        $response->assertOk()
            ->assertSee('data-stage="new"', false)
            ->assertSee('data-stage="proposed"', false)
            ->assertSee('data-stage="active"', false)
            ->assertSee('data-stage="completed"', false)
            ->assertSee('Fuite à réparer rapidement')
            ->assertSee('Robinet à remplacer')
            ->assertSee('Canalisation à déboucher')
            ->assertSee('Chauffe-eau remis en service')
            ->assertDontSee('Recherche une garde après l’école')
            ->assertSee('Étudier et proposer')
            ->assertSee('Missions en cours');

        $this->assertSame(1, $response->viewData('pipelineCounts')['new']);
        $this->assertSame(1, $response->viewData('pipelineCounts')['proposed']);
        $this->assertSame(1, $response->viewData('pipelineCounts')['active']);
        $this->assertSame(1, $response->viewData('pipelineCounts')['completed']);

        $this->actingAs($provider)
            ->get(route('pro.dashboard'))
            ->assertOk()
            ->assertSee('Vos opportunités et missions')
            ->assertSee('Ouvrir mes opportunités');

        $this->assertSame($newDemand->id, $response->viewData('newOpportunities')->sole()->id);
        $this->assertSame($nonMatchingDemand->id, $nonMatchingDemand->fresh()->id);
    }

    public function test_provider_without_categories_gets_a_configuration_empty_state(): void
    {
        $provider = User::factory()->create([
            'is_service_provider' => true,
            'user_type' => 'particulier',
        ]);

        $this->actingAs($provider)
            ->get(route('pro.opportunities'))
            ->assertOk()
            ->assertSee('Indiquez vos métiers pour recevoir les bonnes demandes')
            ->assertSee(route('pro.profile.edit'), false);
    }

    public function test_client_cannot_open_the_provider_pipeline(): void
    {
        $client = User::factory()->create([
            'is_service_provider' => false,
            'user_type' => 'particulier',
            'account_type' => 'particulier',
        ]);

        $this->actingAs($client)
            ->get(route('pro.opportunities'))
            ->assertRedirect(route('pro.dashboard'))
            ->assertSessionHas('warning');
    }

    public function test_dashboard_revenue_counts_only_owned_paid_invoices_in_the_correct_month_and_year(): void
    {
        $this->travelTo(now()->setDate(2026, 9, 15)->startOfDay());
        $provider = User::factory()->create(['is_service_provider' => true]);
        $otherProvider = User::factory()->create(['is_service_provider' => true]);

        $invoice = function (User $owner, float $amount, string $status, $paidAt): void {
            ProInvoice::create([
                'user_id' => $owner->id,
                'invoice_number' => 'FACT-REVENUE-'.ProInvoice::count(),
                'client_name' => 'Client Exemple',
                'subject' => 'Intervention de plomberie',
                'items' => [['description' => 'Intervention', 'quantity' => 1, 'unit_price' => $amount, 'total' => $amount]],
                'subtotal' => $amount,
                'tax_rate' => 0,
                'tax_amount' => 0,
                'total' => $amount,
                'status' => $status,
                'paid_at' => $paidAt,
            ]);
        };

        $invoice($provider, 100, 'paid', now());
        $invoice($provider, 250, 'paid', now()->subYear());
        $invoice($provider, 50, 'paid', now()->subMonth());
        $invoice($provider, 1000, 'sent', null);
        $invoice($provider, 777, 'cancelled', null);
        $invoice($otherProvider, 99, 'paid', now());

        $response = $this->actingAs($provider)->get(route('pro.dashboard'))->assertOk();
        $this->assertSame(100.0, (float) $response->viewData('stats')['monthly_revenue']);
        $this->assertSame(400.0, (float) $response->viewData('stats')['total_revenue']);
    }

    public function test_a_provider_with_only_a_profession_sees_public_and_correctly_targeted_opportunities(): void
    {
        PlatformFeatures::setProSubscriptionsEnabled(false);
        $provider = User::factory()->create([
            'is_service_provider' => true,
            'user_type' => 'particulier',
            'profession' => 'Plombier',
            'service_category' => null,
            'service_subcategories' => null,
            'pro_service_categories' => null,
        ]);
        $client = User::factory()->create();
        $public = $this->createDemand($client, 'Intervention publique');
        $public->update(['visibility' => 'public']);
        $targeted = $this->createDemand($client, 'Intervention ciblée plomberie');
        $targeted->update(['visibility' => 'pro_targeted', 'target_categories' => ['Plombier']]);
        $otherTarget = $this->createDemand($client, 'Intervention réservée à un autre métier');
        $otherTarget->update(['visibility' => 'pro_targeted', 'target_categories' => ['Baby-sitter']]);

        $response = $this->actingAs($provider)->get(route('pro.opportunities'))->assertOk()
            ->assertSee('Intervention publique')->assertSee('Intervention ciblée plomberie')
            ->assertDontSee('Intervention réservée à un autre métier');
        $this->assertEqualsCanonicalizing([$public->id, $targeted->id], $response->viewData('newOpportunities')->pluck('id')->all());
        $this->assertSame(2, $response->viewData('pipelineCounts')['new']);
        $this->get(route('pro.dashboard'))->assertOk()->assertDontSee('Activez votre abonnement Pro')
            ->assertViewHas('stats', fn ($stats) => $stats['new_opportunities'] === 2);
    }

    public function test_assigned_demands_and_proposals_accepted_by_another_provider_are_not_new_opportunities(): void
    {
        $provider = User::factory()->create(['is_service_provider' => true, 'profession' => 'Plombier']);
        $otherProvider = User::factory()->create(['is_service_provider' => true]);
        $client = User::factory()->create();
        $available = $this->createDemand($client, 'Demande encore disponible');
        ServiceProposal::create(['ad_id' => $available->id, 'provider_id' => $otherProvider->id,
            'amount' => 80, 'message' => 'Proposition pas encore acceptée', 'status' => ServiceProposal::STATUS_PENDING]);
        $accepted = $this->createDemand($client, 'Demande attribuée par proposition');
        ServiceProposal::create(['ad_id' => $accepted->id, 'provider_id' => $otherProvider->id,
            'amount' => 80, 'message' => 'Proposition acceptée', 'status' => ServiceProposal::STATUS_ACCEPTED]);

        foreach ([ServiceOrder::STATUS_AWAITING_PAYMENT, ServiceOrder::STATUS_FUNDED,
            ServiceOrder::STATUS_COMPLETED, ServiceOrder::STATUS_DISPUTED] as $status) {
            $assigned = $this->createDemand($client, 'Demande attribuée '.$status);
            $this->createOrder($assigned, $client, $otherProvider, 'CMD-ASSIGNED-'.$status, $status, ServiceOrder::PAYMENT_PAID);
            $this->assertSame('active', $assigned->fresh()->status);
        }

        $response = $this->actingAs($provider)->get(route('pro.opportunities'))->assertOk();
        $this->assertSame([$available->id], $response->viewData('newOpportunities')->pluck('id')->all());
        $this->assertSame(1, $response->viewData('pipelineCounts')['new']);
        $response->assertSee('Demande encore disponible')->assertDontSee('Demande attribuée');
        $this->get(route('pro.dashboard'))->assertOk()
            ->assertViewHas('stats', fn ($stats) => $stats['new_opportunities'] === 1);
    }

    private function createDemand(
        User $client,
        string $title,
        string $category = 'Plombier',
        string $mainCategory = 'Bricolage & Travaux'
    ): Ad {
        return Ad::create([
            'title' => $title,
            'description' => 'Description détaillée de la demande pour le pipeline prestataire.',
            'category' => $category,
            'main_category' => $mainCategory,
            'publication_domain' => 'service',
            'location' => 'Mamoudzou',
            'city' => 'Mamoudzou',
            'country' => 'France',
            'price' => 180,
            'price_type' => 'fixed',
            'service_type' => 'demande',
            'status' => 'active',
            'expires_at' => now()->addMonth(),
            'user_id' => $client->id,
        ]);
    }

    private function createOrder(
        Ad $ad,
        User $buyer,
        User $seller,
        string $number,
        string $status,
        string $paymentStatus,
        array $extra = []
    ): ServiceOrder {
        return ServiceOrder::create(array_merge([
            'order_number' => $number,
            'ad_id' => $ad->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'amount' => 180,
            'commission_amount' => 18,
            'seller_amount' => 162,
            'status' => $status,
            'payment_status' => $paymentStatus,
            'scheduled_for' => now()->addDays(2),
        ], $extra));
    }
}
