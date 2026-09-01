<?php

namespace Tests\Feature\Guides;

use App\Models\User;
use App\Support\MarketplaceGuideCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceGuideFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_guides_are_public_structured_and_linked_from_the_homepage(): void
    {
        $this->get(route('guides.index'))
            ->assertOk()
            ->assertSee('Le guide de la communauté')
            ->assertSee('Parcours client')
            ->assertSee('Parcours prestataire')
            ->assertSee('Publier une demande qui reçoit des réponses utiles')
            ->assertSee('Construire un profil prestataire rassurant')
            ->assertDontSee('des milliers de clients');

        $this->get(route('homepage'))
            ->assertOk()
            ->assertSee(route('guides.index'), false)
            ->assertSee('Conseils pratiques');

        $this->get(route('guides.show', 'publier-une-demande-utile'))
            ->assertOk()
            ->assertSee('Décrivez le résultat attendu')
            ->assertSee('N’affichez jamais de pièce d’identité')
            ->assertSee(route('demand.create'), false);

        $this->get(route('guides.show', 'guide-inexistant'))->assertNotFound();
    }

    public function test_feed_exposes_only_three_guides_adapted_to_the_authenticated_role(): void
    {
        $client = User::factory()->create([
            'user_type' => 'particulier',
            'account_type' => 'particulier',
            'is_service_provider' => false,
        ]);

        $clientResponse = $this->withoutMiddleware()->actingAs($client)->get(route('feed'));

        $clientResponse->assertOk()
            ->assertSee('Conseils pour avancer sereinement')
            ->assertSee('Publier une demande qui reçoit des réponses utiles')
            ->assertSee('Comparer les prestataires au-delà du prix')
            ->assertSee('Les bons réflexes de la communauté')
            ->assertDontSee('Construire un profil prestataire rassurant');
        $this->assertCount(3, $clientResponse->viewData('pkGuides'));

        $provider = User::factory()->create([
            'user_type' => 'professionnel',
            'account_type' => 'professionnel',
            'is_service_provider' => true,
        ]);

        $providerResponse = $this->withoutMiddleware()->actingAs($provider)->get(route('feed'));

        $providerResponse->assertOk()
            ->assertSee('Construire un profil prestataire rassurant')
            ->assertSee('Envoyer une proposition claire et comparable')
            ->assertSee('Organiser la mission jusqu’au paiement')
            ->assertDontSee('Publier une demande qui reçoit des réponses utiles');
        $this->assertCount(3, $providerResponse->viewData('pkGuides'));
    }

    public function test_every_catalog_guide_has_a_working_page_and_sitemap_entry(): void
    {
        $sitemap = $this->get(route('sitemap'))->assertOk();

        foreach (MarketplaceGuideCatalog::all() as $guide) {
            $url = route('guides.show', $guide['slug']);

            $this->get($url)
                ->assertOk()
                ->assertSee($guide['title'])
                ->assertSee($guide['cta_label']);

            $sitemap->assertSee($url, false);
        }

        $css = file_get_contents(public_path('css/guides.css'));
        $this->assertStringContainsString('@media (max-width: 640px)', $css);
        $this->assertStringContainsString('grid-template-columns: minmax(0, 1fr)', $css);
        $this->assertStringContainsString('overflow-wrap: anywhere', $css);
    }
}
