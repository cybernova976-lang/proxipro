<?php

namespace Tests\Feature\Feed;

use App\Models\Ad;
use App\Models\ServiceOrder;
use App\Models\ServiceProposal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedActionHierarchyFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_provider_can_remember_client_mode_without_changing_account_permissions(): void
    {
        $provider = $this->provider();
        $this->actingAs($provider)->get(route('feed', ['mode' => 'client']))->assertOk()
            ->assertViewHas('pkRole', 'client')->assertSee('Je cherche un service');
        $this->get(route('feed'))->assertOk()->assertViewHas('pkRole', 'client');
        $this->get(route('demands.tracking'))->assertRedirect(route('home'));
        $this->get(route('home'))->assertOk()->assertSee('Mon suivi');
        $this->assertTrue($provider->fresh()->is_service_provider);
        $this->get(route('feed', ['mode' => 'provider']))->assertOk()->assertViewHas('pkRole', 'provider');
        $this->get(route('feed'))->assertViewHas('pkRole', 'provider');

        $client = User::factory()->create(['is_service_provider' => false, 'user_type' => 'particulier']);
        $this->actingAs($client)->get(route('feed', ['mode' => 'provider']))
            ->assertOk()->assertViewHas('pkRole', 'client')->assertDontSee('Mon activité prestataire');
        $this->actingAs($provider)->get(route('feed', ['mode' => ['provider']]))
            ->assertOk()->assertViewHas('pkRole', 'provider');
    }

    public function test_pending_proposals_take_priority_over_a_newer_unanswered_request(): void
    {
        $client = User::factory()->create();
        $provider = $this->provider();
        $older = $this->demand($client, 'Réparation avec proposition', ['created_at' => now()->subDay()]);
        $newer = $this->demand($client, 'Nouvelle demande', ['created_at' => now()]);
        ServiceProposal::create(['ad_id' => $older->id, 'provider_id' => $provider->id, 'amount' => 80,
            'message' => 'Disponible demain', 'status' => ServiceProposal::STATUS_PENDING]);
        $this->actingAs($client)->get(route('feed'))->assertOk()
            ->assertViewHas('activeClientRequest', fn ($ad) => $ad->id === $older->id)
            ->assertViewHas('pkActiveRequestCount', 2)
            ->assertSee('Comparer')->assertSee('Mon suivi')
            ->assertSee('data-activity-key="request-'.$older->id.'"', false)
            ->assertDontSee('data-activity-key="request-'.$newer->id.'"', false);
    }

    public function test_withdrawn_proposals_are_not_presented_as_actions_to_compare(): void
    {
        $client = User::factory()->create();
        $ad = $this->demand($client, 'Demande sans proposition disponible');
        ServiceProposal::create(['ad_id' => $ad->id, 'provider_id' => $this->provider()->id, 'amount' => 80,
            'message' => 'Proposition retirée', 'status' => ServiceProposal::STATUS_WITHDRAWN]);
        $response = $this->actingAs($client)->get(route('feed'))->assertOk();
        $this->assertSame(0, (int) $response->viewData('activeClientRequest')->pending_proposals_count);
        $response->assertDontSee('Comparer les propositions')->assertSee('Voir mon suivi')
            ->assertDontSee('data-activity-key="request-'.$ad->id.'"', false);
    }

    public function test_compatible_count_covers_all_results_but_only_six_cards_are_rendered(): void
    {
        $provider = $this->provider(['city' => 'Mamoudzou', 'country' => 'Mayotte']);
        $client = User::factory()->create();
        foreach (range(1, 9) as $number) {
            $this->demand($client, 'Plomberie '.$number, ['city' => 'Mamoudzou', 'country' => 'Mayotte']);
        }
        $wrongTrade = $this->demand($client, 'Garde enfants', ['category' => 'Baby-sitter']);
        $wrongCity = $this->demand($client, 'Plomberie ailleurs', ['city' => 'Paris', 'location' => 'Paris', 'country' => 'France']);
        $response = $this->actingAs($provider)->get(route('feed'))->assertOk()
            ->assertViewHas('pkMatchingCount', 9);
        $ads = $response->viewData('pkFeedAds');
        $this->assertCount(6, $ads);
        $this->assertFalse($ads->contains('id', $wrongTrade->id));
        $this->assertFalse($ads->contains('id', $wrongCity->id));
    }

    public function test_profiles_match_the_request_and_exclude_private_inactive_and_unrelated_accounts(): void
    {
        $client = User::factory()->create(['city' => 'Mamoudzou', 'country' => 'Mayotte']);
        $this->demand($client, 'Besoin local', ['city' => 'Mamoudzou', 'country' => 'Mayotte']);
        $free = $this->provider(['name' => 'Amina Profil public', 'city' => 'Mamoudzou', 'country' => 'Mayotte']);
        $paid = $this->provider(['name' => 'Zacharie Profil Pro', 'plan' => 'pro', 'city' => 'Mamoudzou', 'country' => 'Mayotte']);
        $this->provider(['name' => 'Profil privé', 'profile_public' => false, 'city' => 'Mamoudzou', 'country' => 'Mayotte']);
        $this->provider(['name' => 'Profil inactif', 'is_active' => false, 'city' => 'Mamoudzou', 'country' => 'Mayotte']);
        $this->provider(['name' => 'Mauvais métier', 'profession' => 'Baby-sitter', 'service_category' => 'Baby-sitter', 'city' => 'Mamoudzou', 'country' => 'Mayotte']);
        $this->provider(['name' => 'Autre ville', 'city' => 'Paris', 'country' => 'France']);
        User::factory()->create(['name' => 'Client avec ancien onboarding', 'user_type' => 'particulier',
            'account_type' => 'particulier', 'is_service_provider' => false, 'pro_onboarding_completed' => true,
            'profession' => 'Plombier', 'city' => 'Mamoudzou', 'country' => 'Mayotte']);
        $this->actingAs($client)->get(route('feed'))->assertOk()
            ->assertViewHas('homeProfessionalProfiles', fn ($profiles) => $profiles->pluck('id')->all() === [$free->id, $paid->id])
            ->assertSee('Prestataires pour votre demande')->assertSee('sans priorité liée à l’abonnement');
    }

    public function test_provider_opportunities_exclude_own_pending_proposals_and_already_funded_missions(): void
    {
        $provider = $this->provider();
        $client = User::factory()->create();
        $pending = $this->demand($client, 'Proposition déjà envoyée');
        ServiceProposal::create(['ad_id' => $pending->id, 'provider_id' => $provider->id, 'amount' => 80,
            'message' => 'Disponible demain', 'status' => ServiceProposal::STATUS_PENDING]);
        $funded = $this->demand($client, 'Mission attribuée');
        ServiceOrder::create(['buyer_id' => $client->id, 'seller_id' => $provider->id, 'ad_id' => $funded->id,
            'order_number' => 'CMD-FEED-ASSIGNED', 'amount' => 80, 'commission_amount' => 8, 'seller_amount' => 72,
            'status' => ServiceOrder::STATUS_FUNDED, 'payment_status' => ServiceOrder::PAYMENT_PAID]);
        $available = $this->demand($client, 'Nouvelle opportunité');
        $this->actingAs($provider)->get(route('feed'))->assertOk()
            ->assertViewHas('pkMatchingCount', 1)
            ->assertViewHas('pkFeedAds', fn ($ads) => $ads->pluck('id')->all() === [$available->id]);
    }

    public function test_welcome_has_one_publication_entry_and_profile_reminder_follows_the_list(): void
    {
        $response = $this->actingAs(User::factory()->create())->get(route('feed'))->assertOk();
        $html = $response->getContent();
        $this->assertSame(1, substr_count($html, 'id="pkIntentForm"'));
        $this->assertGreaterThan(strpos($html, 'id="pkFeedList"'), strpos($html, '<details class="pk-progress"'));
        $this->assertGreaterThan(strpos($html, 'id="pkFeedList"'), strpos($html, 'id="pkProviderList"'),
            'Une recherche de prestataire sans résultat ne doit pas repousser les services disponibles.');
        $response->assertSee('Aucun profil public ne correspond encore à ces critères');
    }

    public function test_a_funded_mission_has_a_compact_summary_and_remains_accessible_in_tracking(): void
    {
        $client = User::factory()->create();
        $provider = $this->provider();
        $ad = $this->demand($client, 'Mission déjà financée');
        ServiceOrder::create(['buyer_id' => $client->id, 'seller_id' => $provider->id, 'ad_id' => $ad->id,
            'order_number' => 'CMD-FEED-QA', 'amount' => 80, 'commission_amount' => 8, 'seller_amount' => 72,
            'status' => ServiceOrder::STATUS_FUNDED, 'payment_status' => ServiceOrder::PAYMENT_PAID]);
        $this->actingAs($client)->get(route('feed'))->assertOk()
            ->assertViewHas('pkActiveRequestCount', 0)->assertDontSee('Mission déjà financée')
            ->assertSee('1 demande ou mission en cours')->assertSee('Voir mon suivi')
            ->assertSee(route('home'), false);
        $this->get(route('home'))->assertOk()->assertSee('Mission déjà financée')
            ->assertSee('Voir ma commande')->assertViewHas('activityCounts', fn ($counts) => $counts['ongoing'] === 1);
    }

    private function provider(array $attributes = []): User
    {
        return User::factory()->create(array_merge(['user_type' => 'professionnel', 'account_type' => 'professionnel',
            'is_service_provider' => true, 'profession' => 'Plombier', 'service_category' => 'Plombier',
            'profile_public' => true, 'is_active' => true], $attributes));
    }

    private function demand(User $client, string $title, array $attributes = []): Ad
    {
        $createdAt = $attributes['created_at'] ?? now()->subHours(3);
        $ad = Ad::create(array_merge(['user_id' => $client->id, 'title' => $title, 'description' => 'Besoin de réparation de plomberie.',
            'category' => 'Plombier', 'service_type' => 'demande', 'status' => 'active', 'visibility' => 'public',
            'location' => 'Mamoudzou'], $attributes));

        $ad->forceFill(['created_at' => $createdAt])->save();

        return $ad;
    }
}
