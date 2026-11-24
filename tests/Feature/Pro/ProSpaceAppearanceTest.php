<?php

namespace Tests\Feature\Pro;

use App\Models\User;
use App\Models\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Refonte de la présentation de l'espace Pro (24/11/2026) : une seule rampe de couleur,
 * pas de chiffres répétés, des états vides utiles, les styles hors des vues et un tiroir
 * de menu qui se ferme depuis son propre en-tête sur téléphone.
 */
class ProSpaceAppearanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_opportunities_page_has_no_duplicate_counters_and_useful_empty_states(): void
    {
        $provider = $this->provider();

        $html = $this->actingAs($provider)->get(route('pro.opportunities'))->assertOk()->getContent();
        $main = $this->between($html, 'id="proMain"', '</main>');

        // Les compteurs ne sont affichés qu'une fois, dans l'en-tête de chaque colonne.
        $this->assertStringNotContainsString('opportunity-summary', $main);
        $this->assertSame(4, substr_count($main, 'class="pipeline-count"'));
        foreach (['À examiner', 'Propositions envoyées', 'Missions en cours', 'Terminées'] as $column) {
            $this->assertStringContainsString($column, $main);
        }

        // Les métiers pris en compte, avec l'endroit où les changer.
        $this->assertStringContainsString('Demandes reçues pour', $main);
        $this->assertStringContainsString('<li>Plombier</li>', $main);
        $this->assertStringContainsString('href="'.route('service-provider.mes-services').'" class="pk-opps-trades__edit"', $main);

        // Colonne « À examiner » vide : une piste concrète plutôt qu'un cadre en pointillés.
        $this->assertStringContainsString('Aucune nouvelle demande compatible pour le moment.', $main);
        $this->assertStringContainsString('href="'.route('ads.index', ['type' => 'demandes']).'"', $main);
        $this->assertStringContainsString('Parcourir toutes les demandes', $main);

        // Un seul bouton plein : « Toutes mes propositions » reste secondaire.
        $this->assertStringContainsString('btn btn-pro-outline pro-mobile-full', $main);
        $this->assertStringNotContainsString('btn-pro-primary', $main);
    }

    public function test_opportunities_styles_live_in_their_stylesheet_with_a_single_colour_ramp(): void
    {
        $view = file_get_contents(resource_path('views/pro/opportunities.blade.php'));
        $styles = file_get_contents(public_path('css/pro-opportunities.css'));

        $this->assertStringNotContainsString("@section('styles')", $view);
        $this->assertStringNotContainsString('style="', $view);
        $this->assertStringContainsString('css/pro-opportunities.css', $view);
        $this->assertLessThanOrEqual(200, substr_count($view, "\n"));

        // Plus de bleu (#1d4ed8, #bfdbfe, #dbeafe) ni de sarcelle : la rampe indigo seulement.
        foreach (['#1d4ed8', '#bfdbfe', '#dbeafe', '#eff6ff', '#ecfeff', 'dashed'] as $outOfRamp) {
            $this->assertStringNotContainsString($outOfRamp, $styles);
        }
        $this->assertStringNotContainsString('1399px', $styles);
        $this->assertStringNotContainsString('767px', $styles);
    }

    public function test_quotes_list_shows_one_counter_strip_and_an_icon_instead_of_an_emoji(): void
    {
        $member = User::factory()->create(['is_service_provider' => true, 'account_type' => 'particulier', 'user_type' => 'particulier']);

        $html = $this->actingAs($member)->get(route('pro.quotes'))->assertOk()->getContent();
        $main = $this->between($html, 'id="proMain"', '</main>');

        $this->assertStringContainsString('class="pro-kpis" aria-label="Vos devis en chiffres"', $main);
        foreach (['Total devis', 'En attente', 'Acceptés', 'Refusés'] as $label) {
            $this->assertStringContainsString($label, $main);
        }
        $this->assertStringNotContainsString('fs-4 text-warning', $main);
        $this->assertStringNotContainsString('📄', $main);
        $this->assertStringContainsString('<i class="fas fa-file-lines"></i>', $main);
        // Liste vide : le bouton plein reste celui de l'en-tête, l'état vide propose un bouton secondaire.
        $this->assertSame(1, substr_count($main, 'btn-pro-primary'));

        $invoices = $this->between($this->get(route('pro.invoices'))->assertOk()->getContent(), 'id="proMain"', '</main>');
        $this->assertStringContainsString('class="pro-kpis" aria-label="Vos factures en chiffres"', $invoices);
        $this->assertStringNotContainsString('🧾', $invoices);
        $this->assertSame(1, substr_count($invoices, 'btn-pro-primary'));
    }

    public function test_sidebar_drawer_can_be_closed_from_its_own_header(): void
    {
        $html = $this->actingAs($this->provider())->get(route('pro.dashboard'))->assertOk()->getContent();
        $menu = $this->between($html, 'id="proSidebar"', '</aside>');

        $this->assertStringContainsString('data-pro-sidebar-close aria-label="Fermer le menu"', $menu);

        $script = file_get_contents(public_path('js/pro-space.js'));
        $this->assertStringContainsString("querySelector('[data-pro-sidebar-close]')", $script);

        // Le gabarit recharge ses feuilles à chaque modification, sans numéro de version à la main.
        $this->assertStringContainsString("filemtime(public_path('css/pro-space.css'))", file_get_contents(resource_path('views/pro/layout.blade.php')));
    }

    public function test_dashboard_puts_figures_in_a_side_column(): void
    {
        $html = $this->actingAs($this->provider())->get(route('pro.dashboard'))->assertOk()->getContent();
        $main = $this->between($html, 'id="proMain"', '</main>');
        $aside = $this->between($main, 'class="pk-dash__aside"', '</aside>');

        $this->assertStringContainsString('Vos chiffres', $aside);
        $this->assertSame(4, substr_count($aside, 'data-figure="'));
        $this->assertStringNotContainsString('Derniers devis et factures', $aside);
    }

    public function test_analytics_and_agenda_styles_live_in_their_stylesheets_and_charts_have_a_fixed_height(): void
    {
        $provider = $this->provider();

        $analytics = $this->actingAs($provider)->get(route('pro.analytics'))->assertOk()->getContent();
        $this->assertStringContainsString('css/pro-analytics.css', $analytics);
        $this->assertSame(3, substr_count($analytics, 'class="analytics-chart'));
        $this->assertStringNotContainsString('border-left: 4px solid', $analytics);

        $agenda = $this->get(route('pro.agenda'))->assertOk()->getContent();
        $this->assertStringContainsString('css/pro-agenda.css', $agenda);
        $this->assertStringNotContainsString('class="agenda-stat-icon" style=', $agenda);

        foreach (['analytics', 'agenda'] as $page) {
            $this->assertStringNotContainsString('<style', file_get_contents(resource_path("views/pro/$page.blade.php")));
        }
        $this->assertStringContainsString('.analytics-chart:has(canvas:not([hidden])) { height: 220px; }', file_get_contents(public_path('css/pro-analytics.css')));
    }

    public function test_shared_stylesheet_keeps_the_three_breakpoints_only(): void
    {
        $styles = file_get_contents(public_path('css/pro-space.css'));

        preg_match_all('/@media \((?:max|min)-width: ([0-9.]+)px\)/', $styles, $matches);
        $breakpoints = array_unique(array_map(fn ($width) => (int) ceil((float) $width), $matches[1]));
        sort($breakpoints);
        $this->assertSame([640, 1024, 1180], array_values($breakpoints));

        // Les accès de l'en-tête n'ont plus de contour : ils ne concurrencent plus le contenu.
        $this->assertMatchesRegularExpression('/\.pro-topbar-action \{[^}]*border: 0;/s', $styles);
        // Titres de groupe du menu : plus de capitales espacées.
        $this->assertMatchesRegularExpression('/\.pro-nav-label \{[^}]*text-transform: none;/s', $styles);
    }

    private function provider(): User
    {
        $provider = User::factory()->create(['is_service_provider' => true, 'account_type' => 'particulier', 'user_type' => 'particulier']);
        UserService::create([
            'user_id' => $provider->id, 'main_category' => 'Bricolage & Travaux', 'subcategory' => 'Plombier',
            'description' => 'Interventions de plomberie.', 'is_active' => true,
        ]);

        return $provider->fresh();
    }

    private function between(string $html, string $start, string $end): string
    {
        $from = strpos($html, $start);
        $this->assertNotFalse($from, "Repère introuvable : $start");
        $to = strpos($html, $end, $from);

        return substr($html, $from, $to === false ? null : $to - $from);
    }
}
