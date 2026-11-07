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

        foreach (['feed' => 'discover', 'home' => 'requests', 'messages.index' => 'messages',
            'saved-ads.index' => 'saved', 'employment.index' => 'employment'] as $route => $key) {
            $response = $this->get(route($route))->assertOk();
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

    public function test_new_employment_section_requires_login_and_keeps_external_applications_separate(): void
    {
        $this->get(route('employment.index'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get(route('employment.index'))->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
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
