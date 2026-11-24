<?php

namespace Tests\Feature\Tools;

use App\Models\ProInvoice;
use App\Models\ProQuote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * L'ancien outil devis / facture à crédits est retiré : plus de création ni
 * d'achat hors de l'espace Pro, mais les documents déjà créés restent
 * téléchargeables et la page publique présente l'espace Pro sans fausse promesse.
 */
class QuoteToolRetiredTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_public_page_presents_the_pro_space_without_false_promises_or_cookie(): void
    {
        $response = $this->get(route('quote-tool.landing'))->assertOk()
            ->assertSee('Vos devis et vos factures, sans crédits à acheter.')
            ->assertSee('Devis accepté en ligne')->assertSee('Avoirs')
            ->assertSee('css/quote-landing.css', false)
            // Décision du 24/11/2026 : 2 devis et 2 factures offerts à tout membre, sans
            // justificatif. L'inscription n'est plus celle d'un prestataire.
            ->assertSee('2 devis et 2 factures offerts, sans justificatif')
            ->assertSee('Devis et factures sans limite')
            ->assertSee('href="'.route('register').'"', false)
            ->assertDontSee(route('register', ['continue' => 'provider']), false)
            ->assertDontSee('réservés aux comptes professionnels')
            ->assertDontSee('la checklist de conformité de l’espace Pro doit être terminée')
            ->assertSee(route('public.provider-offers'), false)
            ->assertDontSee('<form', false);

        // Ni essai gratuit, ni packs, ni promesse de conformité, ni identifiant de suivi.
        foreach (['4,99', '14,99', '29,99', 'crédits pour', 'devis gratuit', 'gratuitement', 'conformes', 'toutes les mentions obligatoires', 'illimit'] as $text) {
            $response->assertDontSee($text);
        }
        $response->assertCookieMissing('qt_fp');
        $view = (string) file_get_contents(resource_path('views/tools/quote-landing.blade.php'));
        $this->assertStringNotContainsString('<style', $view);
        $this->assertStringNotContainsString('<script', $view);

        // Un membre connecté, prestataire ou non, va directement à ses devis.
        $this->actingAs(User::factory()->create())->get(route('quote-tool.landing'))->assertOk()
            ->assertSee(route('pro.quotes'), false)->assertSee('Créer mes devis et factures');
    }

    public function test_creation_and_credit_purchase_are_closed_and_redirect_to_the_pro_space(): void
    {
        $this->get('/outils/devis/creer')->assertRedirect(route('login'));

        $user = User::factory()->create(['paid_quotes_remaining' => 5]);
        $this->actingAs($user);

        foreach (['/outils/devis/creer', '/outils/facture/creer', '/outils/acheter-credits', '/outils/achat-succes?session_id=cs_x&pack=pack_5', '/outils/achat-annule'] as $url) {
            $this->get($url)->assertRedirect(route('pro.dashboard'))
                ->assertSessionHas('warning', fn (string $message) => str_contains($message, 'n’est plus proposé'));
        }

        // Plus aucune écriture possible par les anciennes adresses.
        $quote = ['client_name' => 'Client', 'subject' => 'Travaux', 'your_name' => 'Moi',
            'items' => [['description' => 'Ligne', 'quantity' => 1, 'unit_price' => 10]]];
        $this->post('/outils/devis/creer', $quote)->assertStatus(405);
        $this->post('/outils/facture/creer', $quote)->assertStatus(405);
        $this->post('/outils/acheter-credits', ['pack' => 'pack_5'])->assertStatus(405);

        $this->assertSame(0, ProQuote::count());
        $this->assertSame(0, ProInvoice::count());
        $this->assertDatabaseCount('transactions', 0);
        $this->assertSame(5, $user->fresh()->paid_quotes_remaining, 'Les compteurs existants ne sont pas touchés.');
    }

    public function test_documents_already_created_stay_downloadable_by_their_author_only(): void
    {
        $user = User::factory()->create(['name' => 'Auteur Exemple']);
        $quote = ProQuote::create(['user_id' => $user->id, 'quote_number' => 'DEV-ANCIEN-1', 'client_name' => 'Client Exemple',
            'subject' => 'Travaux', 'items' => [['description' => 'Ligne', 'quantity' => 1, 'unit_price' => 100, 'total' => 100]],
            'subtotal' => 100, 'tax_rate' => 20, 'tax_amount' => 20, 'total' => 120, 'status' => 'draft', 'valid_until' => now()->addDays(30)]);
        $invoice = ProInvoice::create(['user_id' => $user->id, 'invoice_number' => 'FAC-ANCIEN-1', 'client_name' => 'Client Exemple',
            'subject' => 'Travaux', 'items' => [['description' => 'Ligne', 'quantity' => 1, 'unit_price' => 100, 'total' => 100]],
            'subtotal' => 100, 'tax_rate' => 20, 'tax_amount' => 20, 'total' => 120, 'status' => 'sent']);

        $this->actingAs($user)->get(route('quote-tool.download', ['token' => $quote->id]))->assertOk()
            ->assertSee('DEV-ANCIEN-1')->assertSee('Télécharger le PDF')->assertSee('n’est plus proposé')
            ->assertSee(route('pro.dashboard'), false)
            ->assertDontSee('Acheter des credits')->assertDontSee('Creer un devis')->assertDontSee('credit(s)');
        $this->get(route('quote-tool.download', ['token' => 'inv-'.$invoice->id]))->assertOk()->assertSee('FAC-ANCIEN-1');

        $this->assertStringContainsString('Devis_DEV-ANCIEN-1.pdf',
            (string) $this->get(route('quote-tool.download', ['token' => $quote->id]).'?pdf=1')->assertOk()->headers->get('content-disposition'));
        $this->assertStringContainsString('Facture_FAC-ANCIEN-1.pdf',
            (string) $this->get(route('quote-tool.download', ['token' => 'inv-'.$invoice->id]).'?pdf=1')->assertOk()->headers->get('content-disposition'));

        $this->actingAs(User::factory()->create())->get(route('quote-tool.download', ['token' => $quote->id]))->assertNotFound();
        $this->get(route('quote-tool.download', ['token' => 'inv-'.$invoice->id]))->assertNotFound();
    }

    public function test_nothing_in_the_application_still_sells_document_credits(): void
    {
        foreach (['quote-create', 'invoice-create', 'purchase-credits', 'invoice-pdf'] as $view) {
            $this->assertFileDoesNotExist(resource_path('views/tools/'.$view.'.blade.php'));
        }
        $controller = (string) file_get_contents(app_path('Http/Controllers/QuoteToolController.php'));
        foreach (['Stripe', 'generateNumber', 'ProQuote::create', 'ProInvoice::create', 'paid_quotes_remaining'] as $text) {
            $this->assertStringNotContainsString($text, $controller);
        }
    }
}
