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
        $response = $this->actingAs(User::factory()->create())->get(route('feed'))->assertOk();
        foreach ([
            ['Bricolage & Travaux', 'Plombier'],
            ['Nettoyage & Entretien', 'Femme/Homme de ménage'],
            ['Jardinage & Extérieur', 'Jardinier'],
            ['Informatique & Tech', 'Technicien informatique'],
        ] as [$category, $subcategory]) {
            $url = route('demand.create', compact('category', 'subcategory'));
            $response->assertSee(e($url), false);
            $this->get($url)->assertOk()->assertViewHas('preCategory', $category)->assertViewHas('preSubcategory', $subcategory);
        }
        $response->assertSee('https://candidat.francetravail.fr/rechercheoffre/emploi', false)
            ->assertSee('La recherche et la candidature se poursuivent sur le site France Travail.')
            ->assertDontSee('notre partenaire');
    }

    public function test_disabled_services_are_not_suggested_as_guided_needs(): void
    {
        MarketplaceCategoryRegistry::storeEnabledIds(['jardinage-exterieur']);
        $this->actingAs(User::factory()->create())->get(route('feed'))->assertOk()
            ->assertSee('Entretenir mon jardin')->assertDontSee('Réparer une fuite')
            ->assertDontSee('Faire le ménage')->assertDontSee('Réparer mon ordinateur');
    }

    public function test_provider_home_keeps_its_own_workflow(): void
    {
        $provider = User::factory()->create(['is_service_provider' => true, 'user_type' => 'professionnel']);
        $this->actingAs($provider)->get(route('feed'))->assertOk()
            ->assertSee('Votre activité prestataire')->assertDontSee('pk-client-welcome', false)
            ->assertDontSee('id="pkNeedsTitle"', false)->assertDontSee('id="pkEmploymentTitle"', false);
    }
}
