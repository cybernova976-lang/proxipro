<?php

namespace Tests\Feature\Site;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * Piste C du lot 1 : un lien « Règles de classement » (article D111-6 I du
 * code de la consommation) et les liens légaux sur toutes les pages des
 * gabarits communs, inclus une seule fois.
 */
class LegalFooterLinksTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
    }

    public function test_feed_shows_the_legal_footer_once(): void
    {
        $this->assertLegalFooter($this->actingAs($this->member())->get(route('feed'))->assertOk());
    }

    public function test_ads_list_shows_the_legal_footer_once(): void
    {
        $this->assertLegalFooter($this->actingAs($this->member())->get(route('ads.index'))->assertOk());
    }

    public function test_demand_page_shows_the_legal_footer_once(): void
    {
        $this->assertLegalFooter($this->actingAs($this->member())->get(route('demand.create'))->assertOk());
    }

    public function test_messages_page_shows_the_legal_footer_once(): void
    {
        $this->assertLegalFooter($this->actingAs($this->member())->get(route('messages.index'))->assertOk());
    }

    public function test_employment_page_shows_the_legal_footer_once(): void
    {
        // /emploi existe déjà ; la piste D la fait rediriger vers /emploi/{territoire}.
        $this->assertLegalFooter($this->actingAs($this->member())->followingRedirects()->get('/emploi')->assertOk());
    }

    public function test_public_employment_page_for_mayotte_shows_the_legal_footer_once(): void
    {
        try {
            $this->app['router']->getRoutes()->match(Request::create('/emploi/mayotte'));
        } catch (HttpException) {
            $this->markTestSkipped('La route publique /emploi/{territoire} (piste D) n’est pas encore enregistrée.');
        }

        $this->assertLegalFooter($this->get('/emploi/mayotte')->assertOk());
    }

    public function test_authentication_layout_shows_the_legal_footer_once(): void
    {
        $this->assertLegalFooter($this->get(route('login'))->assertOk());
    }

    private function assertLegalFooter(TestResponse $response): void
    {
        $html = $response->getContent();

        $this->assertSame(1, substr_count($html, '<footer class="pk-legal-footer">'), 'Le pied de page légal est inclus une seule fois.');
        $this->assertStringContainsString('css/legal-footer.css', $html);

        $footer = substr($html, strpos($html, '<footer class="pk-legal-footer">'));
        $footer = substr($footer, 0, strpos($footer, '</footer>'));
        $this->assertStringContainsString('href="'.route('legal.platform-rules').'#classement">Règles de classement</a>', $footer);
        $this->assertStringContainsString('href="'.route('legal.terms').'">CGU</a>', $footer);
        $this->assertStringContainsString('href="'.route('legal.privacy').'">Confidentialité</a>', $footer);
        $this->assertStringContainsString('href="'.route('legal.cookies').'">Cookies</a>', $footer);
        $this->assertStringContainsString('href="'.route('legal.mentions').'">Mentions légales</a>', $footer);
        $this->assertStringNotContainsString('style=', $footer, 'Aucun style en ligne dans le pied de page.');
    }

    private function member(): User
    {
        return User::factory()->create(['city' => 'Mamoudzou', 'country' => 'Mayotte', 'postal_code' => '97600']);
    }
}
