<?php

namespace Tests\Feature\Feed;

use App\Models\ProfessionalRealization;
use App\Models\User;
use App\Models\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProviderDirectoryExperienceFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_trade_city_and_country_filters_are_combined_without_widening_results(): void
    {
        $viewer = User::factory()->create();
        $local = $this->provider(['name' => 'Amina Martin', 'profession' => 'Électricien']);
        UserService::create(['user_id' => $local->id, 'main_category' => 'Bricolage & Travaux', 'subcategory' => 'Plombier', 'is_active' => true]);
        $this->provider(['name' => 'Autre ville', 'city' => 'Paris']);
        $this->provider(['name' => 'Autre territoire', 'country' => 'France']);
        $inactive = $this->provider(['name' => 'Service inactif', 'profession' => 'Peintre']);
        UserService::create(['user_id' => $inactive->id, 'main_category' => 'Bricolage & Travaux', 'subcategory' => 'Plombier', 'is_active' => false]);

        $this->actingAs($viewer)->get(route('feed.professionals', ['q' => 'plomb', 'city' => ' MAMOUDZOU ', 'country' => 'Mayotte']))
            ->assertOk()->assertSee('Amina Martin')->assertSee('Filtres appliqués :')
            ->assertSee('plomb')->assertSee('MAMOUDZOU')->assertSee('Mayotte')
            ->assertViewHas('professionals', fn ($profiles) => $profiles->total() === 1 && $profiles->first()->id === $local->id);
        $this->get(route('feed.professionals', ['q' => 'plomb', 'city' => 'Ville sans profil']))
            ->assertOk()->assertSee('Aucun profil ne correspond à ces critères')
            ->assertViewHas('professionals', fn ($profiles) => $profiles->isEmpty());
    }

    public function test_search_wildcards_are_literal_and_inputs_are_validated(): void
    {
        $this->actingAs(User::factory()->create());
        $this->provider(['name' => 'Prestataire normal']);
        $this->get(route('feed.professionals', ['q' => '%']))->assertOk()
            ->assertViewHas('professionals', fn ($profiles) => $profiles->isEmpty());
        $this->getJson(route('feed.professionals', ['q' => ['invalid']]))->assertUnprocessable();
        $this->getJson(route('feed.professionals', ['city' => str_repeat('a', 121)]))->assertUnprocessable();
    }

    public function test_pagination_preserves_trade_and_location_filters(): void
    {
        $this->actingAs(User::factory()->create());
        foreach (range(1, 13) as $number) {
            $this->provider(['name' => sprintf('Prestataire %02d', $number)]);
        }
        $response = $this->get(route('feed.professionals', ['q' => 'Plombier', 'city' => 'Mamoudzou', 'country' => 'Mayotte']));
        $response->assertOk()->assertSee('Page 1 sur 2');
        $next = $response->viewData('professionals')->nextPageUrl();
        parse_str(parse_url($next, PHP_URL_QUERY), $query);
        $this->assertSame('Mamoudzou', $query['city']);
        $this->assertSame('Mayotte', $query['country']);
        $this->assertSame('Plombier', $query['q']);
        $this->get($next)->assertOk()->assertSee('Prestataire 13');
    }

    public function test_cards_have_two_actions_full_names_and_only_public_rates(): void
    {
        $viewer = User::factory()->create();
        $provider = $this->provider(['name' => 'Amina Très Long Nom Complet Sans Troncature', 'hourly_rate' => 987, 'show_hourly_rate' => false]);
        $response = $this->actingAs($viewer)->get(route('feed.professionals'))->assertOk()
            ->assertSee($provider->name)->assertSee('Voir le profil')->assertSee('Décrire mon besoin')
            ->assertDontSee('987')->assertDontSee('Identité vérifiée');
        $response->assertSee(route('profile.public', ['id' => $provider->id, 'contact' => 1]).'#profile-contact', false);
        $provider->forceFill(['show_hourly_rate' => true, 'identity_verified' => true])->save();
        $this->get(route('feed.professionals'))->assertOk()->assertSee('987')->assertSee('Identité vérifiée');
    }

    public function test_contact_action_keeps_the_recipient_but_never_sends_a_message_automatically(): void
    {
        $provider = $this->provider();
        $response = $this->actingAs(User::factory()->create())->get(route('profile.public', ['id' => $provider->id, 'contact' => 1]));
        $response->assertOk()->assertSee('window.bootstrap.Modal.getOrCreateInstance(modal).show()', false)
            ->assertSee('name="recipient_id" value="'.$provider->id.'"', false)
            ->assertSee('Aucun message ne sera envoyé avant votre validation.');
        $this->assertDatabaseCount('messages', 0);
        $this->assertDatabaseCount('conversations', 0);
    }

    public function test_feed_card_uses_the_same_contact_link_without_a_hidden_message_form(): void
    {
        $provider = $this->provider();
        $this->actingAs(User::factory()->create());
        $html = view('feed.partials.providers', ['homeProfessionalProfiles' => collect([$provider])])->render();
        $this->assertStringContainsString(route('profile.public', ['id' => $provider->id, 'contact' => 1]).'#profile-contact', $html);
        $this->assertStringNotContainsString('<form', $html);
        $this->assertDatabaseCount('messages', 0);
    }

    public function test_profile_exposes_section_anchors_and_gallery_navigation_without_duplicate_metrics(): void
    {
        $provider = $this->provider(['bio' => 'Rénovations et interventions soignées.']);
        UserService::create(['user_id' => $provider->id, 'main_category' => 'Bricolage & Travaux', 'subcategory' => 'Plombier', 'is_active' => true]);
        ProfessionalRealization::create(['user_id' => $provider->id, 'photo_path' => 'work/example.png', 'position' => 1]);
        $this->get(route('profile.public', $provider))->assertOk()
            ->assertSee('aria-label="Sections du profil"', false)
            ->assertSee('href="#profile-realizations"', false)
            ->assertSee('href="#profile-services"', false)
            ->assertSee('href="#profile-reviews"', false)
            ->assertSee('href="#profile-about"', false)
            ->assertDontSee('aria-label="Indicateurs du profil"', false)
            ->assertSee('Photo déclarée par le prestataire')
            ->assertSee('id="realizationPrevious"', false)
            ->assertSee('id="realizationNext"', false)
            ->assertSee('Ouvrir l’original');
    }

    private function provider(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'user_type' => 'professionnel', 'plan' => 'FREE', 'profile_public' => true,
            'profession' => 'Plombier', 'city' => 'Mamoudzou', 'country' => 'Mayotte',
        ], $attributes));
    }
}
