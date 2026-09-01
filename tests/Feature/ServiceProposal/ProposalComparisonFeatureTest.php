<?php

namespace Tests\Feature\ServiceProposal;

use App\Models\Ad;
use App\Models\Review;
use App\Models\ServiceOrder;
use App\Models\ServiceProposal;
use App\Models\User;
use App\Notifications\ServiceProposalReceivedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProposalComparisonFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_compares_proposals_with_factual_information_and_without_automatic_score(): void
    {
        $client = User::factory()->create(['name' => 'Client Prokejem']);
        $providerWithReviews = User::factory()->create([
            'name' => 'Amina Plomberie',
            'user_type' => 'professionnel',
            'is_service_provider' => true,
            'is_verified' => true,
            'profession' => 'Plombière',
            'city' => 'Mamoudzou',
            'years_experience' => 8,
        ]);
        $providerWithLowestPrice = User::factory()->create([
            'name' => 'Karim Services',
            'user_type' => 'professionnel',
            'is_service_provider' => true,
            'profession' => 'Artisan multiservices',
            'city' => 'Dzaoudzi',
            'years_experience' => 3,
        ]);
        $demand = $this->createAd($client, 'demande');

        $earliestProposal = ServiceProposal::create([
            'ad_id' => $demand->id,
            'provider_id' => $providerWithReviews->id,
            'amount' => 120,
            'message' => 'Je peux diagnostiquer la fuite et fournir les pièces nécessaires.',
            'scheduled_for' => now()->addDay()->startOfDay(),
            'status' => ServiceProposal::STATUS_PENDING,
        ]);
        $lowestProposal = ServiceProposal::create([
            'ad_id' => $demand->id,
            'provider_id' => $providerWithLowestPrice->id,
            'amount' => 90,
            'message' => 'Je suis disponible dans deux jours avec mon matériel.',
            'scheduled_for' => now()->addDays(2)->startOfDay(),
            'status' => ServiceProposal::STATUS_PENDING,
        ]);

        $completedOrder = ServiceOrder::create([
            'order_number' => 'CMD-COMPARE-001',
            'ad_id' => $demand->id,
            'buyer_id' => $client->id,
            'seller_id' => $providerWithReviews->id,
            'amount' => 120,
            'commission_amount' => 12,
            'seller_amount' => 108,
            'status' => ServiceOrder::STATUS_COMPLETED,
            'payment_status' => ServiceOrder::PAYMENT_RELEASED,
        ]);
        Review::create([
            'reviewer_id' => $client->id,
            'reviewed_user_id' => $providerWithReviews->id,
            'ad_id' => $demand->id,
            'service_order_id' => $completedOrder->id,
            'rating' => 5,
            'comment' => 'Intervention sérieuse et ponctuelle.',
        ]);

        $response = $this->actingAs($client)
            ->get(route('proposals.compare', $demand))
            ->assertOk()
            ->assertSeeText('Amina Plomberie')
            ->assertSeeText('Karim Services')
            ->assertSeeText('120,00 €')
            ->assertSeeText('90,00 €')
            ->assertSeeText('Prix le plus bas')
            ->assertSeeText('Créneau le plus proche')
            ->assertSeeText('5,0 (1 avis vérifié)')
            ->assertSeeText('Prokejem ne choisit pas à votre place')
            ->assertSeeText('aucun score automatique')
            ->assertSee(route('proposals.accept', $earliestProposal), false)
            ->assertSee(route('proposals.accept', $lowestProposal), false);

        $html = $response->getContent();
        $this->assertStringContainsString('@media(max-width:760px)', $html);
        $this->assertStringContainsString('.compare-grid{grid-template-columns:1fr}', $html);
        $this->assertStringNotContainsString('score de compatibilité', mb_strtolower($html));
    }

    public function test_only_the_demand_owner_can_open_the_comparator(): void
    {
        $owner = User::factory()->create();
        $outsider = User::factory()->create();

        $this->actingAs($outsider)
            ->get(route('proposals.compare', $this->createAd($owner, 'demande')))
            ->assertForbidden();
    }

    public function test_an_offer_cannot_be_opened_in_the_proposal_comparator(): void
    {
        $owner = User::factory()->create();

        $this->actingAs($owner)
            ->get(route('proposals.compare', $this->createAd($owner, 'offre')))
            ->assertNotFound();
    }

    public function test_new_proposal_notification_opens_the_direct_comparator(): void
    {
        $client = User::factory()->create();
        $provider = User::factory()->create([
            'name' => 'Prestataire notifié',
            'user_type' => 'professionnel',
            'is_service_provider' => true,
        ]);
        $demand = $this->createAd($client, 'demande');
        $proposal = ServiceProposal::create([
            'ad_id' => $demand->id,
            'provider_id' => $provider->id,
            'amount' => 110,
            'message' => 'Une proposition suffisamment détaillée pour la notification.',
            'status' => ServiceProposal::STATUS_PENDING,
        ])->load(['ad', 'provider']);
        $notification = new ServiceProposalReceivedNotification($proposal);

        $this->assertSame(
            route('proposals.compare', $demand),
            $notification->toArray($client)['action_url']
        );
        $this->assertSame(
            route('proposals.compare', $demand),
            $notification->toMail($client)->actionUrl
        );
    }

    public function test_demand_owner_sees_the_comparator_from_the_ad_page(): void
    {
        $client = User::factory()->create();
        $provider = User::factory()->create([
            'user_type' => 'professionnel',
            'is_service_provider' => true,
        ]);
        $demand = $this->createAd($client, 'demande');
        ServiceProposal::create([
            'ad_id' => $demand->id,
            'provider_id' => $provider->id,
            'amount' => 100,
            'message' => 'Je peux intervenir rapidement sur cette demande.',
            'status' => ServiceProposal::STATUS_PENDING,
        ]);

        $this->actingAs($client)
            ->get(route('ads.show', $demand))
            ->assertOk()
            ->assertSeeText('Comparer 1 proposition')
            ->assertSee(route('proposals.compare', $demand), false);
    }

    private function createAd(User $owner, string $serviceType): Ad
    {
        return Ad::create([
            'title' => $serviceType === 'demande' ? 'Réparer une fuite urgente' : 'Service de plomberie',
            'description' => 'Une description suffisamment précise pour comparer les propositions.',
            'main_category' => 'Bricolage & Travaux',
            'category' => 'Plombier',
            'location' => 'Mamoudzou',
            'price' => 100,
            'service_type' => $serviceType,
            'status' => 'active',
            'visibility' => 'public',
            'user_id' => $owner->id,
        ]);
    }
}
