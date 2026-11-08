<?php

namespace Tests\Feature\Employment;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Lot 1, piste D1 : pages emploi publiques /emploi et /emploi/{territoire}.
 */
class EmploymentPublicPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_the_mayotte_page_with_the_france_travail_deep_link(): void
    {
        $response = $this->get('/emploi/mayotte')->assertOk();

        $response->assertSee('Emploi à Mayotte')
            ->assertSee('href="https://candidat.francetravail.fr/offres/recherche?lieux=976D"', false)
            ->assertSee('Offres d’emploi à Mayotte sur France Travail (site externe)')
            ->assertSee('La recherche et la candidature se poursuivent sur le site France Travail.')
            ->assertSee('Vous savez faire un métier ?')
            ->assertSee('Proposer mes services')
            ->assertSee('href="'.e(route('register', ['account_type' => 'professionnel'])).'"', false)
            ->assertSee('href="'.route('demand.create').'"', false);

        $this->assertPublicEmploymentPage($response);
    }

    public function test_each_territory_uses_its_own_france_travail_code_and_wording(): void
    {
        $this->get('/emploi/guyane')->assertOk()
            ->assertSee('lieux=973D', false)
            ->assertSee('Offres d’emploi en Guyane sur France Travail (site externe)');
        $this->get('/emploi/la-reunion')->assertOk()->assertSee('lieux=974D', false);
        $this->get('/emploi/departement-2a')->assertOk()
            ->assertSee('lieux=2AD', false)
            ->assertSee('Emploi dans le département 2A');
    }

    public function test_unknown_territory_returns_404(): void
    {
        $this->get('/emploi/atlantide')->assertNotFound();
        $this->get('/emploi/departement-99')->assertNotFound();
    }

    public function test_guest_on_emploi_gets_the_list_of_territories_without_redirect(): void
    {
        $response = $this->get('/emploi')->assertOk();

        foreach (['mayotte', 'guyane', 'la-reunion', 'guadeloupe', 'martinique'] as $slug) {
            $response->assertSee('href="'.route('employment.territory', ['territoire' => $slug]).'"', false);
        }
        $response->assertSee('href="https://candidat.francetravail.fr/offres/recherche"', false);

        $this->assertPublicEmploymentPage($response);
    }

    public function test_member_with_a_known_territory_is_redirected_to_its_page(): void
    {
        $member = User::factory()->create(['postal_code' => null, 'country' => 'Mayotte', 'city' => 'Mamoudzou']);

        $this->actingAs($member)->get('/emploi')->assertRedirect('/emploi/mayotte');
    }

    public function test_member_with_an_unknown_territory_gets_the_list(): void
    {
        $member = User::factory()->create(['postal_code' => null, 'country' => 'Belgique', 'city' => 'Bruxelles']);

        $this->actingAs($member)->get('/emploi')->assertOk()
            ->assertSee('href="'.route('employment.territory', ['territoire' => 'mayotte']).'"', false);
    }

    public function test_provider_is_sent_to_offer_publication_rather_than_signup(): void
    {
        $provider = User::factory()->create(['user_type' => 'professionnel', 'is_service_provider' => true]);

        $this->actingAs($provider)->get('/emploi/mayotte')->assertOk()
            ->assertSee('href="'.route('ads.create').'"', false)
            ->assertDontSee('account_type=professionnel', false);
    }

    public function test_employment_pages_stay_out_of_the_sitemap(): void
    {
        $this->get(route('sitemap'))->assertOk()->assertDontSee('/emploi', false);
    }

    private function assertPublicEmploymentPage(TestResponse $response): void
    {
        $html = $response->getContent();

        $this->assertStringContainsString('<meta name="robots" content="noindex">', $html);
        $this->assertSame('private', $response->headers->get('Cache-Control'));
        $this->assertStringNotContainsStringIgnoringCase('no-store', (string) $response->headers->get('Cache-Control'));
        // Jamais « partenaire » à propos de France Travail : ni dans le contenu de la
        // page emploi, ni accolé à France Travail ailleurs dans le gabarit. (La modale
        // de partage du gabarit visiteur parle de « clients ou partenaires » de
        // l'utilisateur, sans lien avec France Travail.)
        $dom = new \DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $page = (new \DOMXPath($dom))->query('//div[@class="pk-employment-page"]');
        $this->assertSame(1, $page->length, 'Un seul bloc de page emploi.');
        $this->assertStringNotContainsStringIgnoringCase('partenaire', $dom->saveHTML($page->item(0)));
        $this->assertDoesNotMatchRegularExpression('/partenaire[^<]{0,80}France Travail|France Travail[^<]{0,80}partenaire/iu', $html);
        $this->assertStringNotContainsStringIgnoringCase('notre partenaire', $html);
        $this->assertStringContainsString('rel="noopener noreferrer"', $html);
    }
}
