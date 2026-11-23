<?php

namespace Tests\Feature\Feed;

use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Lot 1, piste D2 : carte « Emploi à {territoire} » dans le rail du flux client
 * et onglet Emploi de l'espace particulier.
 */
class FeedEmploymentCardTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_rail_links_to_the_job_offers_of_the_territory(): void
    {
        $reader = User::factory()->create(['city' => 'Mamoudzou', 'country' => 'Mayotte', 'postal_code' => '97600']);

        $html = $this->actingAs($reader)->get(route('feed'))->assertOk()->getContent();
        $card = $this->xpath($html)->query('//section[@aria-labelledby="pkRailEmploymentTitle"]');

        $this->assertSame(1, $card->length, 'Une seule carte emploi dans le rail.');
        $text = preg_replace('/\s+/u', ' ', $card->item(0)->textContent);
        $this->assertStringContainsString('Emploi à Mayotte', $text);
        $this->assertStringContainsString('Voir les offres d’emploi', $text);
        $this->assertStringContainsString('Publiées par les membres de Prokejem', $text);
        $this->assertStringContainsString('Proposer mes services', $text);
        $this->assertStringNotContainsStringIgnoringCase('France Travail', $text);
        $this->assertStringNotContainsStringIgnoringCase('site externe', $text);
        $this->assertDoesNotMatchRegularExpression('/\d/', $text, 'Aucun compteur dans la carte emploi.');

        $links = collect($this->xpath($html)->query('//section[@aria-labelledby="pkRailEmploymentTitle"]//a'))
            ->map(fn ($a) => $a->getAttribute('href'))->all();
        $this->assertCount(2, $links, 'Deux liens : les offres du territoire et la proposition de services.');
        $this->assertContains(route('employment.territory', ['territoire' => 'mayotte']), $links);
        $this->assertStringNotContainsString('francetravail', implode(' ', $links));
        $this->assertContains(route('service-provider.start'), $links);
    }

    public function test_employment_tab_points_to_the_territory_page(): void
    {
        $reader = User::factory()->create(['city' => 'Mamoudzou', 'country' => 'Mayotte', 'postal_code' => '97600']);

        // L'entrée « Offres d'emploi » du panneau latéral mène à la page du territoire.
        $html = $this->actingAs($reader)->get(route('home'))->assertOk()->getContent();
        $tab = $this->xpath($html)->query('//a[@data-space-section="employment"]');

        $this->assertSame(1, $tab->length);
        $this->assertSame(route('employment.territory', ['territoire' => 'mayotte']), $tab->item(0)->getAttribute('href'));
        $this->get(route('client.dashboard'))->assertOk()
            ->assertSee(route('employment.territory', ['territoire' => 'mayotte']), false);
    }

    public function test_unknown_territory_links_to_the_general_job_page(): void
    {
        $reader = User::factory()->create(['city' => 'Bruxelles', 'country' => 'Belgique', 'postal_code' => null]);

        $html = $this->actingAs($reader)->get(route('feed'))->assertOk()->getContent();
        $links = collect($this->xpath($html)->query('//section[@aria-labelledby="pkRailEmploymentTitle"]//a'))
            ->map(fn ($a) => $a->getAttribute('href'))->all();

        $this->assertContains(route('employment.index'), $links);
        $this->assertStringNotContainsString('francetravail', implode(' ', $links));
        $space = $this->get(route('home'))->assertOk()->getContent();
        $this->assertSame(route('employment.index'),
            $this->xpath($space)->query('//a[@data-space-section="employment"]')->item(0)->getAttribute('href'));
    }

    public function test_provider_feed_has_no_employment_card(): void
    {
        $provider = User::factory()->create(['user_type' => 'professionnel', 'is_service_provider' => true]);

        $this->actingAs($provider)->get(route('feed'))->assertOk()
            ->assertDontSee('pkRailEmploymentTitle', false);
    }

    private function xpath(string $html): DOMXPath
    {
        $dom = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return new DOMXPath($dom);
    }
}
