<?php

namespace Tests\Feature\Pro;

use App\Models\ProInvoice;
use App\Models\ProQuote;
use App\Models\User;
use App\Models\UserService;
use App\Support\PlatformFeatures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProSpaceRedesignFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_menu_is_grouped_without_duplicates_and_locks_documents_for_a_private_provider(): void
    {
        $provider = User::factory()->create([
            'is_service_provider' => true, 'account_type' => 'particulier', 'user_type' => 'particulier',
        ]);

        $html = $this->actingAs($provider)->get(route('pro.clients'))->assertOk()->getContent();
        $menu = $this->between($html, 'id="proSidebar"', '</aside>');

        foreach (['Trouver des clients', 'Gérer mon activité', 'Suivre mes résultats', 'Mon entreprise'] as $group) {
            $this->assertStringContainsString($group, $menu);
        }
        // Les raccourcis déjà présents dans la barre du haut ne sont plus répétés dans le menu.
        foreach (['Liens rapides', 'Messagerie', 'Retour au feed', 'Publier une annonce', "S'abonner"] as $duplicate) {
            $this->assertStringNotContainsString($duplicate, $menu);
        }
        $this->assertSame(1, substr_count($menu, 'aria-current="page"'));
        $this->assertStringContainsString('data-pro-nav="clients"', $menu);
        // Devis et factures mènent au changement de statut, avec l'explication.
        $this->assertMatchesRegularExpression('#href="'.preg_quote(route('pro.account-status'), '#').'"[^>]*data-pro-nav="quotes"#', $menu);
        $this->assertStringContainsString('Statut professionnel requis', $menu);
        $this->assertStringContainsString('Passer pro', $menu);
        // La cloche sans action a disparu de la barre du haut.
        $this->assertStringNotContainsString('aria-label="Notifications"', $html);
    }

    public function test_professional_dashboard_lists_measured_next_steps_and_a_single_primary_button(): void
    {
        $pro = $this->professional();
        UserService::create(['user_id' => $pro->id, 'main_category' => 'Bricolage & Travaux', 'subcategory' => 'Plombier', 'is_active' => true]);
        $this->invoice($pro, 'sent', 120);
        $this->invoice($pro, 'paid', 80, now());
        $this->quote($pro, 'draft');

        $response = $this->actingAs($pro)->get(route('pro.dashboard'))->assertOk();
        $html = $response->getContent();
        $main = $this->between($html, 'id="proMain"', '</main>');

        $steps = collect($response->viewData('nextSteps'))->pluck('key')->all();
        $this->assertSame(['compliance', 'invoices', 'drafts'], $steps);
        $this->assertStringContainsString('1 facture envoyée non réglée', $main);
        $this->assertStringContainsString('1 devis en brouillon', $main);
        $this->assertSame(1, substr_count($main, 'btn-pro-primary'), 'Un seul bouton plein sur l’accueil Pro.');

        $this->assertStringContainsString('Encaissé ce mois-ci', $main);
        $this->assertStringContainsString('80 €', $main);
        $this->assertStringContainsString('Derniers devis et factures', $main);
        // Souscriptions fermées dans les tests : aucune promotion de Premium.
        $this->assertFalse(PlatformFeatures::proSubscriptionsEnabled());
        $this->assertStringNotContainsString('Découvrir Premium', $main);
        $this->assertStringNotContainsString('Note moyenne (0 avis)', $main);
        // Le compteur du menu ne s'affiche que pour une action à mener : la facture impayée.
        $this->assertMatchesRegularExpression('#data-pro-nav="invoices".*?pro-nav-badge--alert">1<#s', $html);
    }

    public function test_dashboard_says_when_nothing_is_waiting(): void
    {
        $pro = $this->professional([
            'pro_terms_accepted_at' => now(), 'pro_terms_version' => config('legal.pro_terms_version'),
        ]);
        UserService::create(['user_id' => $pro->id, 'main_category' => 'Bricolage & Travaux', 'subcategory' => 'Plombier', 'is_active' => true]);
        $this->assertTrue($pro->fresh()->canIssueCommercialDocuments());

        $this->actingAs($pro)->get(route('pro.dashboard'))->assertOk()
            ->assertViewHas('nextSteps', [])
            ->assertSee('Rien d’urgent : tout est à jour.')
            ->assertSee('Aucun devis ni facture pour l’instant.');
    }

    public function test_member_without_services_sees_the_real_steps_to_become_a_provider(): void
    {
        $member = User::factory()->completeForVerification()->create([
            'is_service_provider' => false, 'account_type' => 'particulier', 'user_type' => 'particulier',
            'identity_verified' => false, 'is_verified' => false,
        ]);

        $html = $this->actingAs($member)->get(route('pro.dashboard'))->assertOk()
            ->assertSee('Proposez vos services sur Prokejem')
            ->assertSee('Faire vérifier mon profil')
            ->assertSee(route('service-provider.start'), false)
            ->assertDontSee('Jean Dupont')
            ->getContent();

        // Pas de menu vers des outils qui lui sont encore fermés.
        $this->assertStringNotContainsString('id="proSidebar"', $html);
        $this->assertStringNotContainsString('id="sidebarToggle"', $html);
        $this->assertStringContainsString('pk-join__step--done" data-join-step="1"', $html);
        $this->assertStringContainsString('pk-join__step--current" data-join-step="2"', $html);
        $this->assertSame(1, substr_count($this->between($html, 'id="proMain"', '</main>'), 'btn-pro-primary'));
    }

    public function test_feed_header_opens_the_pro_space_for_providers_and_the_join_page_for_members(): void
    {
        $member = User::factory()->create(['is_service_provider' => false, 'account_type' => 'particulier', 'user_type' => 'particulier']);
        $provider = User::factory()->create(['is_service_provider' => true, 'account_type' => 'particulier', 'user_type' => 'particulier']);

        $memberHeader = $this->actingAs($member)->get(route('feed'))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('#href="'.preg_quote(route('pro.dashboard'), '#').'"[^>]*data-header-pro>\s*<i[^>]*></i><span>Devenir pro</span>#', $memberHeader);
        $this->assertStringContainsString('data-header-pro-mobile', $memberHeader);

        $providerHeader = $this->actingAs($provider)->get(route('feed'))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('#data-header-pro>\s*<i[^>]*></i><span>Espace Pro</span>#', $providerHeader);
        $this->assertStringNotContainsString('Nouvelle facture', $providerHeader);
    }

    public function test_switching_to_a_professional_status_continues_on_the_remaining_steps(): void
    {
        $provider = User::factory()->completeForVerification()->create([
            'is_service_provider' => true, 'account_type' => 'particulier', 'user_type' => 'particulier', 'identity_verified' => true,
        ]);

        $this->actingAs($provider)->get(route('pro.account-status'))->assertOk()
            ->assertSee('Quelle est votre situation ?')
            ->assertSee('Conditions pour émettre devis et factures');

        $this->put(route('pro.account-status.update'), [
            'pro_status' => 'auto-entrepreneur', 'company_name' => 'Atelier du Lagon', 'siret' => '123 456 789 00012',
        ])->assertRedirect(route('pro.compliance'))
            ->assertSessionHas('success', fn (string $message) => str_contains($message, '« Auto-entrepreneur »')
                && str_contains($message, 'pour émettre vos devis et factures'));

        $this->assertTrue($provider->fresh()->isAutoEntrepreneur());

        // Un professionnel qui modifie seulement ses informations reste sur le formulaire.
        $this->put(route('pro.account-status.update'), ['pro_status' => 'auto-entrepreneur', 'company_name' => 'Atelier du Lagon 2'])
            ->assertRedirect(route('pro.account-status'));
    }

    /** Constaté en production : « Installer Prokejem » recouvrait « Enregistrer mon statut ». */
    public function test_install_button_stays_out_of_the_pro_space_but_remains_on_the_feed(): void
    {
        $pro = $this->professional();

        foreach (['pro.dashboard', 'pro.account-status', 'pro.quotes'] as $route) {
            $this->actingAs($pro)->get(route($route))->assertOk()
                ->assertSee('const installSurfaceAllowed = false;', false);
        }

        $this->actingAs($pro)->get(route('feed'))->assertOk()
            ->assertSee('const installSurfaceAllowed = true;', false);
    }

    /** Constaté en production : un modèle de devis d'un autre compte renvoyait une page « Not Found » en anglais. */
    public function test_missing_page_is_explained_in_french(): void
    {
        $pro = $this->professional();

        $this->actingAs($pro)->get(route('pro.quotes.create', ['modele' => 999999]))
            ->assertNotFound()
            ->assertSee('Page introuvable')
            ->assertDontSee('Not Found');
    }

    public function test_pro_views_stay_short_and_keep_styles_out_of_the_layout(): void
    {
        $files = ['layout', 'layout/topbar', 'layout/sidebar', 'layout/flash', 'dashboard', 'dashboard/next-steps',
            'dashboard/figures', 'dashboard/documents', 'dashboard/join', 'account-status', 'partials/status-tabs'];

        foreach ($files as $file) {
            $content = file_get_contents(resource_path("views/pro/{$file}.blade.php"));
            $this->assertLessThanOrEqual(200, substr_count($content, "\n"), "pro/{$file} dépasse 200 lignes.");
            $this->assertDoesNotMatchRegularExpression('/<style>\s*[.:#a-z]/i', $content, "pro/{$file} contient du CSS en ligne.");
            $this->assertStringNotContainsString('<script>', $content, "pro/{$file} contient du JavaScript en ligne.");
        }
    }

    private function professional(array $overrides = []): User
    {
        return User::factory()->completeForVerification()->create(array_merge([
            'account_type' => 'professionnel', 'user_type' => 'professionnel', 'business_type' => 'auto_entrepreneur',
            'is_service_provider' => true, 'identity_verified' => true, 'company_name' => 'Atelier du Lagon', 'siret' => '12345678900012',
        ], $overrides));
    }

    private function invoice(User $owner, string $status, float $amount, $paidAt = null): void
    {
        ProInvoice::create([
            'user_id' => $owner->id, 'invoice_number' => 'FAC-TEST-'.ProInvoice::count(), 'client_name' => 'Client Test',
            'subject' => 'Intervention', 'items' => [['description' => 'Intervention', 'quantity' => 1, 'unit_price' => $amount, 'total' => $amount]],
            'subtotal' => $amount, 'tax_rate' => 0, 'tax_amount' => 0, 'total' => $amount, 'status' => $status, 'paid_at' => $paidAt,
        ]);
    }

    private function quote(User $owner, string $status): void
    {
        ProQuote::create([
            'user_id' => $owner->id, 'quote_number' => 'DEV-TEST-'.ProQuote::count(), 'client_name' => 'Client Test', 'subject' => 'Peinture',
            'items' => [['description' => 'Peinture', 'quantity' => 1, 'unit_price' => 100, 'total' => 100]],
            'subtotal' => 100, 'tax_rate' => 0, 'tax_amount' => 0, 'total' => 100, 'status' => $status,
        ]);
    }

    private function between(string $html, string $start, string $end): string
    {
        $from = strpos($html, $start);
        $this->assertNotFalse($from, "« {$start} » introuvable.");

        return substr($html, $from, strpos($html, $end, $from) - $from);
    }
}
