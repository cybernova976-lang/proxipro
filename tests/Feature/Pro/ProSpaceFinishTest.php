<?php

namespace Tests\Feature\Pro;

use App\Models\Ad;
use App\Models\ProInvoice;
use App\Models\ServiceOrder;
use App\Models\User;
use App\Models\UserService;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Finitions de l'Espace Pro (24/11/2026), après la revue du rendu : une compétence
 * supprimée ne ramène plus les demandes de sa catégorie, un avoir n'est pas une
 * recette, une commande à accepter n'est pas une mission en cours, une seule page
 * porte un seul nom, et l'avatar ne montre jamais une image cassée.
 */
class ProSpaceFinishTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_removed_trade_no_longer_brings_the_requests_of_its_category(): void
    {
        $provider = $this->plumberAndElectrician();
        $client = User::factory()->create(['user_type' => 'particulier', 'account_type' => 'particulier']);
        $this->demand($client, 'Bricolage & Travaux', 'Plombier', 'Fuite sous évier à réparer');
        $this->demand($client, 'Bricolage & Travaux', 'Électricien', 'Prise de cuisine à remplacer');
        $this->demand($client, 'Bricolage & Travaux', 'Bricolage & Travaux', 'Petits travaux dans la maison');
        $this->demand($client, 'Bricolage & Travaux', 'Maçon', 'Muret de jardin à monter');

        $plumber = $provider->services()->where('subcategory', 'Plombier')->firstOrFail();
        $this->actingAs($provider)->deleteJson(route('service-provider.delete-service', $plumber->id))->assertOk();

        $response = $this->get(route('pro.opportunities'))->assertOk();
        $trades = $this->between($response->getContent(), 'class="pk-opps-trades__list"', '</ul>');

        // Métiers affichés : le métier restant, ni « Plombier » ni la catégorie entière.
        $this->assertStringContainsString('<li>Électricien</li>', $trades);
        $this->assertStringNotContainsString('Plombier', $trades);
        $this->assertStringNotContainsString('Bricolage &amp; Travaux', $trades);

        // Demandes : celle du métier restant et la demande sans métier précis ; plus de plomberie ni de maçonnerie.
        $response->assertSee('Prise de cuisine à remplacer')->assertSee('Petits travaux dans la maison')
            ->assertDontSee('Fuite sous évier à réparer')->assertDontSee('Muret de jardin à monter');
        $this->assertSame(2, $response->viewData('pipelineCounts')['new']);

        // Le compteur de l'accueil lit la même règle.
        $this->get(route('pro.dashboard'))->assertOk()->assertViewHas('stats', fn ($stats) => $stats['new_opportunities'] === 2);
    }

    public function test_a_skill_covering_a_whole_category_still_brings_all_its_requests(): void
    {
        $provider = User::factory()->create(['is_service_provider' => true, 'user_type' => 'professionnel', 'account_type' => 'professionnel']);
        UserService::create(['user_id' => $provider->id, 'main_category' => 'Bricolage & Travaux', 'subcategory' => 'Bricolage & Travaux',
            'experience_years' => 4, 'is_active' => true]);
        $client = User::factory()->create(['user_type' => 'particulier', 'account_type' => 'particulier']);
        $this->demand($client, 'Bricolage & Travaux', 'Plombier', 'Fuite sous évier à réparer');

        $this->actingAs($provider)->get(route('pro.opportunities'))->assertOk()
            ->assertSee('Fuite sous évier à réparer')
            ->assertSee('<li>Bricolage &amp; Travaux</li>', false);
    }

    public function test_an_order_awaiting_acceptance_is_to_answer_not_an_active_mission(): void
    {
        $provider = $this->plumberAndElectrician();
        $client = User::factory()->create(['name' => 'Cliente Réservation']);
        $ad = $this->demand($client, 'Bricolage & Travaux', 'Électricien', 'Tableau électrique à vérifier');
        $order = ServiceOrder::create(['order_number' => 'CMD-A-ACCEPTER', 'ad_id' => $ad->id, 'buyer_id' => $client->id,
            'seller_id' => $provider->id, 'amount' => 180, 'commission_amount' => 18, 'seller_amount' => 162,
            'status' => ServiceOrder::STATUS_PENDING_ACCEPTANCE, 'payment_status' => ServiceOrder::PAYMENT_AWAITING]);

        $response = $this->actingAs($provider)->get(route('pro.opportunities'))->assertOk();
        $html = $response->getContent();
        $toExamine = $this->between($html, 'data-stage="new"', 'data-stage="proposed"');
        $active = $this->between($html, 'data-stage="active"', 'data-stage="completed"');

        $this->assertStringContainsString('data-order-to-answer', $toExamine);
        $this->assertStringContainsString('CMD-A-ACCEPTER', $toExamine);
        $this->assertStringContainsString('pro-status pro-status-warning', $toExamine);
        $this->assertStringContainsString('action="'.route('service-orders.accept', $order).'"', $toExamine);
        $this->assertStringNotContainsString('CMD-A-ACCEPTER', $active);
        $this->assertSame(0, $response->viewData('pipelineCounts')['active']);

        // Accueil : la commande attend une réponse, elle n'est pas comptée parmi les missions en cours.
        $this->get(route('pro.dashboard'))->assertOk()
            ->assertViewHas('stats', fn ($stats) => $stats['active_missions'] === 0)
            ->assertSee('data-next-step="orders"', false);
    }

    public function test_one_page_has_one_name_and_columns_show_a_single_counter(): void
    {
        $html = $this->actingAs($this->plumberAndElectrician())->get(route('pro.opportunities'))->assertOk()->getContent();
        $menu = $this->between($html, 'id="proSidebar"', '</aside>');
        $topbar = $this->between($html, 'class="pro-topbar-context"', '</div>');
        $main = $this->between($html, 'id="proMain"', '</main>');

        $this->assertStringContainsString('Demandes et missions', $menu);
        $this->assertStringContainsString('Demandes et missions', $topbar);
        $this->assertStringContainsString('<h1>Demandes et missions</h1>', $main);
        foreach (['Mes opportunités', 'Demandes à examiner', 'Transformez les demandes', 'breadcrumb', 'pipeline-stage-step', 'pipeline-status-'] as $gone) {
            $this->assertStringNotContainsString($gone, $main);
        }
        $this->assertSame(4, substr_count($main, 'class="pipeline-count"'));
    }

    public function test_dashboard_shows_a_credit_note_as_a_negative_avoir_and_missions_without_orders_to_accept(): void
    {
        $provider = $this->plumberAndElectrician();
        $invoice = $this->invoice($provider, ['invoice_number' => 'FAC-2026-0001', 'total' => 237.62]);
        $this->invoice($provider, ['invoice_number' => 'AV-2026-0001', 'kind' => ProInvoice::KIND_CREDIT_NOTE,
            'parent_invoice_id' => $invoice->id, 'status' => ProInvoice::STATUS_ISSUED, 'total' => 20.62]);

        $html = $this->actingAs($provider)->get(route('pro.dashboard'))->assertOk()->getContent();
        $documents = $this->between($html, 'class="pk-dash-docs"', '</section>');

        $this->assertMatchesRegularExpression('#pk-dash-docs__type">Avoir</span>\s*<span class="pk-dash-docs__main">\s*<span class="pk-dash-docs__number">AV-2026-0001#', $documents);
        $this->assertStringContainsString('pk-dash-docs__total--credit">−20,62 €', $documents);
        $this->assertStringContainsString('237,62 €', $documents);
    }

    public function test_amounts_have_one_format_and_lists_become_cards_on_phones(): void
    {
        $this->assertSame('1 234,50 €', Money::eur(1234.5));
        $this->assertSame('−20,62 €', Money::eur(-20.62));
        $this->assertSame('80 €', Money::eur(80, 0));

        $provider = $this->plumberAndElectrician();
        $this->invoice($provider, ['invoice_number' => 'FAC-2026-0002', 'total' => 792.05]);

        $main = $this->between($this->actingAs($provider)->get(route('pro.invoices'))->assertOk()->getContent(), 'id="proMain"', '</main>');
        $this->assertStringContainsString('pro-table pro-table--cards', $main);
        $this->assertStringContainsString('<td class="pk-col-amount">792,05 €</td>', $main);
        $this->assertStringNotContainsString('breadcrumb', $main);

        $styles = file_get_contents(public_path('css/pro-space.css'));
        $this->assertMatchesRegularExpression('/@media \(max-width: 639\.98px\).*\.pro-table--cards thead \{ display: none; \}/s', $styles);
        $this->assertStringContainsString('.pro-table .pk-col-amount { text-align: right; white-space: nowrap; font-weight: 600; font-variant-numeric: tabular-nums; }', $styles);
        // Un seul rouge pour les compteurs et les états refusés.
        $this->assertStringNotContainsString('#dc2626', $styles);
        $this->assertStringNotContainsString('#ef4444', $styles);
    }

    public function test_topbar_avatar_falls_back_to_initials_and_the_return_link_speaks_french(): void
    {
        $provider = $this->plumberAndElectrician(['company_name' => 'Atelier du Lagon', 'avatar' => 'avatars/absente.jpg']);

        $html = $this->actingAs($provider)->get(route('pro.dashboard'))->assertOk()->getContent();
        $topbar = $this->between($html, 'class="pro-topbar"', '</nav>');

        $this->assertStringContainsString('<span class="pro-user-avatar__initials">AL</span>', $topbar);
        $this->assertStringContainsString('alt="" data-pro-avatar-photo', $topbar);
        $this->assertStringContainsString('Retour à l’accueil', $topbar);
        $this->assertStringContainsString('title="Retour à l’accueil de Prokejem"', $topbar);
        $this->assertStringNotContainsString('feed</span>', $topbar);

        $script = file_get_contents(public_path('js/pro-space.js'));
        $this->assertStringContainsString("querySelectorAll('[data-pro-avatar-photo]')", $script);
        $this->assertStringContainsString('photo.naturalWidth === 0', $script);
    }

    public function test_quote_templates_have_a_discreet_delete_action(): void
    {
        $partial = file_get_contents(resource_path('views/pro/partials/quote-templates.blade.php'));
        $styles = file_get_contents(public_path('css/pro-premium.css'));

        $this->assertStringContainsString('class="pk-templates__delete pk-templates__delete--icon" aria-label="Supprimer le modèle', $partial);
        $this->assertStringContainsString('fa-trash-can', $partial);
        $this->assertStringContainsString("confirm('Supprimer ce modèle ?", $partial);
        $this->assertMatchesRegularExpression('/\.pk-templates__head h2 \{[^}]*color: #0f172a;/', $styles);
    }

    private function plumberAndElectrician(array $attributes = []): User
    {
        $provider = User::factory()->create(array_merge([
            'user_type' => 'professionnel', 'account_type' => 'professionnel', 'is_service_provider' => true,
            'profession' => 'Plombier', 'service_category' => 'Bricolage & Travaux',
            'service_subcategories' => ['Plombier', 'Électricien'], 'pro_service_categories' => ['Bricolage & Travaux'],
        ], $attributes));

        UserService::create(['user_id' => $provider->id, 'main_category' => 'Bricolage & Travaux',
            'subcategory' => 'Plombier', 'experience_years' => 5, 'is_active' => true]);
        UserService::create(['user_id' => $provider->id, 'main_category' => 'Bricolage & Travaux',
            'subcategory' => 'Électricien', 'experience_years' => 3, 'is_active' => true]);

        return $provider->fresh();
    }

    private function demand(User $client, string $main, string $category, string $title): Ad
    {
        return Ad::create(['user_id' => $client->id, 'title' => $title, 'description' => 'Description de la demande de test.',
            'main_category' => $main, 'category' => $category, 'service_type' => 'demande',
            'location' => 'Paris', 'status' => 'active', 'expires_at' => now()->addDays(10)]);
    }

    private function invoice(User $user, array $overrides = []): ProInvoice
    {
        return ProInvoice::create(array_merge(['user_id' => $user->id, 'invoice_number' => 'FAC-TEST-1',
            'client_name' => 'Client Exemple', 'client_email' => 'client@example.test', 'client_type' => 'individual',
            'subject' => 'Travaux de peinture', 'operation_type' => 'services', 'service_date' => now(),
            'items' => [['description' => 'Peinture', 'quantity' => 2, 'unit_price' => 50, 'total' => 100]],
            'subtotal' => 100, 'tax_rate' => 20, 'tax_amount' => 20, 'total' => 120, 'status' => 'sent',
            'finalized_at' => now(), 'sent_at' => now(), 'due_date' => now()->addDays(30),
            'payment_terms' => 'Paiement à réception.', 'early_payment_discount' => 'Néant'], $overrides));
    }

    private function between(string $html, string $start, string $end): string
    {
        $from = strpos($html, $start);
        $this->assertNotFalse($from, "Repère introuvable : $start");
        $to = strpos($html, $end, $from + strlen($start));

        return substr($html, $from, $to === false ? null : $to - $from);
    }
}
