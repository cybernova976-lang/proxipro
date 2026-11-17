<?php

namespace Tests\Feature\Feed;

use App\Models\User;
use App\Support\MarketplaceCategoryRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedClientExperienceFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_gets_guided_service_entries_and_separate_external_employment_search(): void
    {
        // Lecteur à Paris : la fabrique par défaut ne pose aucun lieu, donc aucun territoire.
        $reader = User::factory()->create(['country' => 'France', 'city' => 'Paris', 'postal_code' => '75002']);
        $response = $this->actingAs($reader)->get(route('feed'))->assertOk();
        // Lot 2 : les entrées guidées sont les tuiles des situations (parcours /demande?situation=…).
        foreach (['fuite-eau', 'menage', 'jardin', 'demenager'] as $situation) {
            $url = route('demand.create', ['situation' => $situation]);
            $response->assertSee(e($url), false);
            $this->get($url)->assertOk();
        }
        // La carte « Emploi » du rail renvoie à la page emploi publique du territoire
        // (Paris : département 75), qui n'affiche que les offres des membres de Prokejem.
        $territoryPage = route('employment.territory', ['territoire' => 'departement-75']);
        $response->assertSee($territoryPage, false)
            ->assertDontSee('francetravail', false);
        $this->get(route('employment.index'))->assertRedirect($territoryPage);
        $this->get($territoryPage)->assertOk()
            ->assertSee('Les offres d’emploi publiées par les membres de Prokejem')
            ->assertDontSee('francetravail', false)->assertDontSee('France Travail')
            ->assertDontSee('notre partenaire');
    }

    public function test_disabled_services_are_not_suggested_as_guided_needs(): void
    {
        MarketplaceCategoryRegistry::storeEnabledIds(['jardinage-exterieur']);
        $this->actingAs(User::factory()->create())->get(route('feed'))->assertOk()
            ->assertSee('data-pk-situation="jardin"', false)
            ->assertDontSee('data-pk-situation="fuite-eau"', false)
            ->assertDontSee('data-pk-situation="menage"', false)
            ->assertDontSee('Fuite d’eau');
    }

    public function test_provider_home_keeps_its_own_workflow(): void
    {
        $provider = User::factory()->create(['is_service_provider' => true, 'user_type' => 'professionnel']);
        $this->actingAs($provider)->get(route('feed'))->assertOk()
            ->assertSee('Votre activité prestataire')->assertDontSee('pk-client-welcome', false)
            ->assertDontSee('id="pkProblemTitle"', false)->assertDontSee('id="pkEmploymentTitle"', false);
    }
}
