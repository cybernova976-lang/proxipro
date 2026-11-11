<?php

namespace Tests\Feature\Account;

use App\Models\Ad;
use App\Models\DemandDraft;
use App\Models\User;
use App\Support\MarketplaceCategoryRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberPublicationsFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_publications_are_sorted_by_state_paginated_and_scoped_to_the_owner(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        foreach (range(1, 13) as $index) {
            $this->ad($owner, 'Annonce active '.$index);
        }
        $this->ad($owner, 'Annonce archivée', ['status' => 'inactive']);
        $this->ad($owner, 'Annonce expirée', ['expires_at' => now()->subDay()]);
        $this->ad($owner, 'Annonce en validation', ['status' => 'pending']);
        $this->ad($other, 'Annonce privée autre compte', ['status' => 'pending']);
        DemandDraft::create(['user_id' => $owner->id, 'payload' => ['title' => 'Demande à terminer'], 'current_step' => 2, 'last_activity_at' => now()]);
        $response = $this->actingAs($owner)->get(route('ads.myads'));
        $response->assertOk()->assertSee('Demande à terminer')->assertSee('13 / 5 annonces actives')->assertDontSee('Annonce privée autre compte');
        $this->assertCount(12, $response->viewData('ads'));
        $this->assertSame(16, $response->viewData('ads')->total());
        $this->get(route('ads.myads', ['state' => 'pending', 'user' => $other->id]))->assertOk()->assertSee('Annonce en validation')->assertDontSee('Annonce privée autre compte')->assertDontSee('Annonce archivée');
        $this->get(route('ads.myads', ['state' => 'closed']))->assertOk()->assertSee('Annonce archivée')->assertSee('Annonce expirée')->assertDontSee('Annonce active 1');
        $this->get(route('ads.myads', ['state' => 'drafts']))->assertOk()->assertSee('Demande à terminer')->assertDontSee('Annonce active 1');
        $this->get(route('dashboard.my-ads'))->assertOk()->assertSee('Reprendre ma demande');
        MarketplaceCategoryRegistry::storeEnabledIds([]);
        $this->get(route('ads.myads', ['state' => 'closed']))->assertOk()->assertSee('Catégorie suspendue');
        $this->get(route('ads.myads', ['state' => 'active']))->assertOk()->assertSee('Aucune publication dans cette rubrique');
    }

    public function test_the_draft_and_management_pages_are_not_public(): void
    {
        $this->get(route('ads.myads'))->assertRedirect(route('login'));
        $this->get(route('dashboard.my-ads'))->assertRedirect(route('login'));
    }

    public function test_the_demand_flow_enforces_the_same_existing_quota_without_losing_the_draft(): void
    {
        $user = User::factory()->create();
        foreach (range(1, 5) as $index) {
            $this->ad($user, 'Annonce existante '.$index);
        }
        $draft = DemandDraft::create(['user_id' => $user->id, 'payload' => ['title' => 'Nouvelle demande'], 'current_step' => 5, 'last_activity_at' => now()]);
        $this->actingAs($user)->post(route('demand.store'), [
            'title' => 'Une nouvelle demande', 'description' => 'Une fuite à réparer dans la salle de bain.',
            'main_category' => 'Bricolage & Travaux', 'category' => 'Plombier', 'country' => 'France', 'city' => 'Paris',
            'desired_date' => now()->addDay()->toDateString(), 'time_window' => 'morning', 'price_type' => 'negotiable',
            'publication_confirmed' => '1', 'service_details' => ['work_scope' => 'repair', 'site_type' => 'house'],
        ])->assertSessionHasErrors('publication');
        $this->assertDatabaseCount('ads', 5);
        $this->assertNotNull($draft->fresh());
    }

    private function ad(User $user, string $title, array $attributes = []): Ad
    {
        return Ad::create(array_merge(['user_id' => $user->id, 'title' => $title, 'description' => 'Description de test.',
            'main_category' => 'Bricolage & Travaux', 'category' => 'Plombier', 'service_type' => 'offre',
            'location' => 'Paris', 'status' => 'active', 'expires_at' => now()->addDays(10)], $attributes));
    }
}
