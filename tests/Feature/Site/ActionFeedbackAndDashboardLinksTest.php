<?php

namespace Tests\Feature\Site;

use App\Models\User;
use App\Support\FlashMessages;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Audit de novembre 2026 : des boutons ne menaient nulle part et des
 * formulaires ne confirmaient rien.
 *
 * - les liens « Mon compte », « Profil », « Transactions »… appelaient une
 *   fonction définie seulement dans le menu latéral, absent de l'espace
 *   particulier ;
 * - le gabarit principal n'affichait ni les succès ni les erreurs.
 */
class ActionFeedbackAndDashboardLinksTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
    }

    // ----- Messages de retour -------------------------------------------------

    public function test_a_page_that_stays_silent_now_shows_the_error_message(): void
    {
        $response = $this->actingAs($this->member())
            ->withSession(['error' => 'Cette annonce est réservée aux profils vérifiés.'])
            ->get(route('profile.edit'))
            ->assertOk();

        $response->assertSee('pk-flash--error', false);
        $response->assertSee('Cette annonce est réservée aux profils vérifiés.');
    }

    public function test_a_page_that_stays_silent_now_shows_the_success_message(): void
    {
        $response = $this->actingAs($this->member())
            ->withSession(['success' => 'Annonce signalée. Merci pour votre retour.'])
            ->get(route('ads.index'))
            ->assertOk();

        $response->assertSee('pk-flash--success', false);
        $response->assertSee('Annonce signalée. Merci pour votre retour.');
    }

    public function test_a_page_that_already_shows_its_message_does_not_repeat_it(): void
    {
        $message = 'Profil mis à jour avec succès.';

        $html = $this->actingAs($this->member())
            ->withSession(['success' => $message])
            ->get(route('profile.show'))
            ->assertOk()
            ->getContent();

        $this->assertSame(1, substr_count($html, e($message)));
        $this->assertStringNotContainsString('pk-flash--success', $html);
    }

    public function test_no_banner_without_a_message(): void
    {
        $this->actingAs($this->member())->get(route('ads.index'))->assertOk()
            ->assertDontSee('pk-flash--', false);
    }

    public function test_activating_the_provider_profile_explains_what_is_missing(): void
    {
        $member = User::factory()->create(['phone' => null, 'avatar' => null, 'bio' => null]);

        $this->actingAs($member)
            ->followingRedirects()
            ->get(route('service-provider.start'))
            ->assertOk()
            ->assertSee('pk-flash--error', false)
            ->assertSee('Pour activer votre profil prestataire')
            ->assertSee('Numéro de téléphone')
            ->assertSee('Photo de profil');
    }

    public function test_pending_messages_skip_what_the_page_already_wrote(): void
    {
        $rendered = '<p>'.e('L\'annonce est en ligne.').'</p><script>toast("Brouillon enregistré")</script>';

        $pending = FlashMessages::pending(
            ['success' => 'L\'annonce est en ligne.', 'error' => 'Paiement refusé.', 'warning' => 'Brouillon enregistré'],
            [],
            $rendered,
        );

        $this->assertCount(1, $pending);
        $this->assertSame('error', $pending[0]['tone']);
        $this->assertSame(['Paiement refusé.'], $pending[0]['lines']);
    }

    public function test_validation_errors_are_grouped_without_repeating_field_errors(): void
    {
        $pending = FlashMessages::pending(
            ['success' => ['pas', 'un', 'texte']],
            ['Le motif est obligatoire.', 'Le motif est obligatoire.', 'Le nom est obligatoire.', 'La description est trop courte.'],
            '<div class="invalid-feedback">Le nom est obligatoire.</div>',
        );

        $this->assertCount(1, $pending);
        $this->assertSame('error', $pending[0]['tone']);
        $this->assertSame(['Le motif est obligatoire.', 'La description est trop courte.'], $pending[0]['lines']);
    }

    // ----- Liens du tableau de bord -------------------------------------------

    public function test_my_requests_page_loads_the_section_navigation_without_the_side_menu(): void
    {
        $html = $this->actingAs($this->member())->get(route('home'))->assertOk()->getContent();

        // Espace particulier : onglets, pas de menu latéral — le script doit venir du gabarit.
        $this->assertStringNotContainsString('id="sidebarNav"', $html);
        $this->assertStringContainsString('js/dashboard-nav.js', $html);
        $this->assertStringContainsString('href="'.route('home').'#account"', $html);
        $this->assertStringContainsString('href="'.route('ads.myads').'"', $html);
    }

    public function test_side_menu_entries_lead_to_real_pages(): void
    {
        $html = $this->actingAs($this->member())->get(route('contact.index'))->assertOk()->getContent();

        $this->assertStringContainsString('id="sidebarNav"', $html);
        $menu = substr($html, strpos($html, 'id="sidebarNav"'));
        $menu = substr($menu, 0, strpos($menu, '</aside>'));

        $this->assertStringNotContainsString('href="#"', $menu);
        foreach ([route('profile.show'), route('ads.myads'), route('settings.index'), route('home').'#account', route('home').'#transactions'] as $url) {
            $this->assertStringContainsString('href="'.$url.'"', $menu);
        }

        // « Mes demandes » n'est plus surligné sur une page qui n'est pas la sienne.
        $this->assertDoesNotMatchRegularExpression('/class="menu-item active"[^>]*data-dashboard-section="overview"/', $menu);
    }

    public function test_account_and_transactions_sections_answer(): void
    {
        $member = $this->member();

        $this->actingAs($member)->get(route('dashboard.account'))->assertOk()->assertSee('Compte et achats');
        $this->actingAs($member)->get(route('dashboard.transactions'))->assertOk()->assertSee('Historique des transactions');
    }

    public function test_the_section_navigation_is_defined_once_outside_the_side_menu(): void
    {
        $this->assertStringContainsString('window.dashboardNav = dashboardNav', File::get(public_path('js/dashboard-nav.js')));
        $this->assertStringNotContainsString('function dashboardNav', File::get(resource_path('views/partials/sidebar.blade.php')));
    }

    public function test_no_section_link_is_left_without_an_address(): void
    {
        $dead = [];
        foreach (File::allFiles(resource_path('views')) as $file) {
            foreach (explode("\n", $file->getContents()) as $number => $line) {
                if (str_contains($line, 'dashboardNav(') && str_contains($line, 'href="#"')) {
                    $dead[] = $file->getRelativePathname().':'.($number + 1);
                }
            }
        }

        $this->assertSame([], $dead, 'Un lien de rubrique doit garder une vraie adresse, utilisable sans script.');
    }

    private function member(): User
    {
        return User::factory()->create(['city' => 'Mamoudzou', 'country' => 'Mayotte', 'postal_code' => '97600']);
    }
}
