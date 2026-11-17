<?php

namespace Tests\Feature\Employment;

use App\Models\Ad;
use App\Models\User;
use App\Support\MarketplaceCategoryRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Pages emploi publiques /emploi et /emploi/{territoire} : elles affichent
 * uniquement les offres d'emploi publiées par les membres de Prokejem.
 */
class EmploymentPublicPageTest extends TestCase
{
    use RefreshDatabase;

    private function openEmploymentCategory(): void
    {
        $services = collect(MarketplaceCategoryRegistry::definitions())->where('type', 'services')->keys()->all();
        MarketplaceCategoryRegistry::storeEnabledIds([...$services, 'emploi']);
    }

    private function jobOffer(array $attributes = []): Ad
    {
        return Ad::create($attributes + [
            'user_id' => User::factory()->create(['name' => 'Garage Kawéni'])->id,
            'title' => 'Nous recrutons un mécanicien', 'description' => 'Poste à pourvoir rapidement.',
            'main_category' => 'Emploi', 'category' => 'CDI',
            'city' => 'Mamoudzou', 'country' => 'Mayotte', 'location' => 'Mamoudzou',
            'service_type' => 'offre', 'status' => 'active', 'visibility' => 'public',
            'ad_details' => ['work_mode' => 'on_site', 'work_time' => 'full_time'],
        ]);
    }

    public function test_guest_sees_an_empty_page_without_any_external_job_site(): void
    {
        $response = $this->get('/emploi/mayotte')->assertOk();

        $response->assertSee('Emploi à Mayotte')
            ->assertSee('Les offres d’emploi publiées par les membres de Prokejem à Mayotte.')
            ->assertSee('Aucune offre d’emploi à Mayotte pour le moment')
            ->assertSee('Vous savez faire un métier ?')
            ->assertSee('Proposer mes services')
            ->assertSee('href="'.e(route('register', ['account_type' => 'professionnel'])).'"', false)
            ->assertSee('href="'.route('demand.create').'"', false)
            // La catégorie Emploi est fermée par défaut : on ne propose pas de publier.
            ->assertDontSee('Publier une offre d’emploi');

        $this->assertPublicEmploymentPage($response);
        $this->assertStringContainsString('<meta name="robots" content="noindex">', $response->getContent());
    }

    public function test_job_offers_published_by_members_are_listed_for_their_territory(): void
    {
        $this->openEmploymentCategory();
        $offer = $this->jobOffer();
        $this->jobOffer(['title' => 'Vendeuse en boulangerie', 'category' => 'CDD', 'city' => 'Cayenne', 'country' => 'Guyane', 'location' => 'Cayenne']);
        // Ni les recherches d'emploi, ni les offres de service, ni les annonces hors ligne.
        $this->jobOffer(['title' => 'Je cherche un poste de comptable', 'service_type' => 'demande']);
        $this->jobOffer(['title' => 'Plombier disponible', 'main_category' => null, 'category' => 'Plombier']);
        $this->jobOffer(['title' => 'Offre suspendue', 'status' => 'inactive']);
        $this->jobOffer(['title' => 'Offre expirée', 'expires_at' => now()->subDay()]);

        $response = $this->get('/emploi/mayotte')->assertOk();

        $response->assertSee('1 offre d’emploi à Mayotte')
            ->assertSee('Nous recrutons un mécanicien')
            ->assertSee('CDI · Mamoudzou · Temps plein · Sur site')
            ->assertSee('Publiée par Garage Kawéni')
            ->assertSee('href="'.route('ads.show', $offer).'"', false)
            ->assertSee('Publier une offre d’emploi')
            ->assertDontSee('Vendeuse en boulangerie')
            ->assertDontSee('Je cherche un poste de comptable')
            ->assertDontSee('Plombier disponible')
            ->assertDontSee('Offre suspendue')
            ->assertDontSee('Offre expirée')
            ->assertDontSee('Aucune offre d’emploi');

        $this->assertPublicEmploymentPage($response);
        // Une page qui affiche des offres peut être référencée.
        $this->assertStringNotContainsString('<meta name="robots" content="noindex">', $response->getContent());

        $this->get('/emploi/guyane')->assertOk()
            ->assertSee('1 offre d’emploi en Guyane')->assertSee('Vendeuse en boulangerie')
            ->assertDontSee('Nous recrutons un mécanicien');
    }

    public function test_a_closed_employment_category_hides_every_offer(): void
    {
        $this->jobOffer();

        $this->get('/emploi/mayotte')->assertOk()
            ->assertSee('Aucune offre d’emploi à Mayotte pour le moment')
            ->assertDontSee('Nous recrutons un mécanicien');
    }

    public function test_unknown_territory_returns_404(): void
    {
        $this->get('/emploi/atlantide')->assertNotFound();
        $this->get('/emploi/departement-99')->assertNotFound();
    }

    public function test_guest_on_emploi_gets_every_offer_and_the_territories_without_redirect(): void
    {
        $this->openEmploymentCategory();
        $this->jobOffer();
        $this->jobOffer(['title' => 'Vendeuse en boulangerie', 'city' => 'Cayenne', 'country' => 'Guyane', 'location' => 'Cayenne']);

        $response = $this->get('/emploi')->assertOk();

        $response->assertSee('2 offres d’emploi')
            ->assertSee('Nous recrutons un mécanicien')->assertSee('Vendeuse en boulangerie');
        foreach (['mayotte', 'guyane', 'la-reunion', 'guadeloupe', 'martinique'] as $slug) {
            $response->assertSee('href="'.route('employment.territory', ['territoire' => $slug]).'"', false);
        }

        $this->assertPublicEmploymentPage($response);
    }

    public function test_offers_are_paginated(): void
    {
        $this->openEmploymentCategory();
        foreach (range(1, 13) as $i) {
            $this->jobOffer(['title' => "Poste numéro {$i}"]);
        }

        $this->get('/emploi/mayotte')->assertOk()
            ->assertSee('13 offres d’emploi à Mayotte')
            ->assertSee('Page 1 sur 2')
            ->assertSee('page=2', false);
        $this->get('/emploi/mayotte?page=2')->assertOk()->assertSee('Page 2 sur 2');
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

    /** France Travail a refusé l'accès à ses offres : plus aucun lien ni mention. */
    private function assertPublicEmploymentPage(TestResponse $response): void
    {
        $html = $response->getContent();

        $this->assertSame('private', $response->headers->get('Cache-Control'));
        $this->assertStringNotContainsStringIgnoringCase('no-store', (string) $response->headers->get('Cache-Control'));
        $this->assertStringNotContainsStringIgnoringCase('france travail', $html);
        $this->assertStringNotContainsStringIgnoringCase('francetravail', $html);
        $this->assertStringNotContainsStringIgnoringCase('site externe', $html);

        $dom = new \DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $page = (new \DOMXPath($dom))->query('//div[@class="pk-employment-page"]');
        $this->assertSame(1, $page->length, 'Un seul bloc de page emploi.');
    }
}
