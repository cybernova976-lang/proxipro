<?php

namespace Tests\Feature\Feed;

use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientSpaceNavigationFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_open_every_section_with_one_current_tab_and_no_duplicate_navigation(): void
    {
        $this->actingAs(User::factory()->create());

        // L'onglet Emploi reste l'onglet actif sur la page emploi d'un territoire.
        foreach (['feed' => 'discover', 'home' => 'requests', 'messages.index' => 'messages',
            'saved-ads.index' => 'saved', 'employment.territory' => 'employment'] as $route => $key) {
            $response = $this->get(route($route, $route === 'employment.territory' ? ['territoire' => 'departement-75'] : []))->assertOk();
            $xpath = $this->xpath($response->getContent());
            $this->assertSame(5, $xpath->query('//nav[@aria-label="Mon espace particulier"]/a')->length, $route);
            $current = $xpath->query('//a[@data-space-section][@aria-current="page"]');
            $this->assertSame(1, $current->length, $route);
            $this->assertSame($key, $current->item(0)->getAttribute('data-space-section'));
            $this->assertSame(0, $xpath->query('//nav[@class="pk-tabbar"]')->length, $route);
            $this->assertSame(0, $xpath->query('//main[contains(@class,"main-content-with-sidebar")]')->length, $route);
        }
    }

    public function test_provider_only_sees_client_tabs_when_using_the_client_journey(): void
    {
        $provider = User::factory()->create(['user_type' => 'professionnel', 'is_service_provider' => true]);
        $this->actingAs($provider)->get(route('feed'))->assertOk()->assertDontSee('data-client-space', false);
        $this->get(route('feed', ['mode' => 'client']))->assertOk()->assertSee('data-client-space', false);
        $this->get(route('saved-ads.index'))->assertOk()->assertSee('data-client-space', false);
        $this->get(route('feed', ['mode' => 'provider']))->assertOk()->assertDontSee('data-client-space', false);
        $this->get(route('saved-ads.index'))->assertOk()->assertDontSee('data-client-space', false);
        $this->assertTrue($provider->fresh()->is_service_provider);
    }

    // Lot 1 (D1) : la page emploi devient publique, reste « private » sans « no-store »,
    // et /emploi mène un membre au territoire connu vers sa page.
    public function test_employment_section_is_public_and_keeps_external_applications_separate(): void
    {
        $this->get(route('employment.index'))->assertOk()->assertHeader('Cache-Control', 'private');
        $this->get(route('employment.territory', ['territoire' => 'departement-75']))->assertOk()
            ->assertHeader('Cache-Control', 'private');
        // Connecté, EnforceSessionInactivity impose « private, no-store » à toutes les
        // pages (règle de session, hors de la page emploi) : on vérifie seulement « private ».
        // Un membre sans lieu déclaré reçoit la liste des territoires ; un membre à Paris
        // est conduit à la page de son département.
        $this->actingAs(User::factory()->create())->get(route('employment.index'))->assertOk();
        $this->actingAs(User::factory()->create(['country' => 'France', 'city' => 'Paris', 'postal_code' => '75002']))
            ->get(route('employment.index'))
            ->assertRedirect(route('employment.territory', ['territoire' => 'departement-75']));
        $member = $this->get(route('employment.territory', ['territoire' => 'departement-75']))->assertOk();
        $this->assertStringContainsString('private', (string) $member->headers->get('Cache-Control'));
        $member->assertSee('data-space-section="employment"', false)
            ->assertSee(e(route('employment.territory', ['territoire' => 'departement-75'])), false)
            ->assertSee('Site externe · France Travail')->assertSee('nouvel onglet')
            ->assertSee('La recherche et la candidature se poursuivent sur le site France Travail.')
            ->assertDontSee('notre partenaire')->assertDontSee('Commander');
    }

    public function test_public_home_and_focused_publication_form_do_not_gain_client_tabs(): void
    {
        $this->get('/')->assertOk()->assertDontSee('data-client-space', false);
        $this->actingAs(User::factory()->create())->get(route('demand.create'))
            ->assertOk()->assertDontSee('data-client-space', false);
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
