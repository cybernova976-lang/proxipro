<?php

namespace Tests\Feature\Pro;

use App\Models\ProInvoice;
use App\Models\ProQuote;
use App\Models\ProSubscription;
use App\Models\User;
use App\Support\DocumentTotals;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Remise et TVA par ligne : le total d'une ligne est calculé remise déduite, la
 * TVA est détaillée par taux, et les documents dérivés (facture, acompte,
 * avoir) suivent les taux du document d'origine.
 */
class LineDiscountAndVatTest extends TestCase
{
    use RefreshDatabase;

    public function test_totals_are_unchanged_without_options_and_detailed_by_rate_with_them(): void
    {
        // Sans remise ni taux par ligne : exactement le calcul d'avant.
        $plain = DocumentTotals::compute([
            ['description' => 'A', 'quantity' => 3, 'unit_price' => 33.33],
            ['description' => 'B', 'quantity' => 1, 'unit_price' => 0.01],
        ], 20);
        $this->assertSame([100.0, 20.0, 120.0], [$plain['subtotal'], $plain['tax_amount'], $plain['total']]);
        $this->assertSame(['description' => 'A', 'quantity' => 3, 'unit_price' => 33.33, 'total' => 99.99], $plain['items'][0]);
        $this->assertCount(1, $plain['breakdown']);

        $mixed = DocumentTotals::compute([
            ['description' => 'A', 'quantity' => 2, 'unit_price' => 100, 'discount_percent' => 10, 'tax_rate' => 20],
            ['description' => 'B', 'quantity' => 1, 'unit_price' => 200, 'tax_rate' => '5.5'],
            ['description' => 'C', 'quantity' => 1, 'unit_price' => 50, 'discount_percent' => '', 'tax_rate' => ''],
        ], 20);
        $this->assertSame(180.0, $mixed['items'][0]['total']);
        $this->assertSame(10.0, $mixed['items'][0]['discount_percent']);
        // Un taux égal à celui du document n'est pas enregistré comme taux propre.
        $this->assertArrayNotHasKey('tax_rate', $mixed['items'][0]);
        $this->assertSame(5.5, $mixed['items'][1]['tax_rate']);
        $this->assertArrayNotHasKey('discount_percent', $mixed['items'][2]);
        $this->assertSame([
            ['rate' => 5.5, 'base' => 200.0, 'tax' => 11.0],
            ['rate' => 20.0, 'base' => 230.0, 'tax' => 46.0],
        ], $mixed['breakdown']);
        $this->assertSame([430.0, 57.0, 487.0], [$mixed['subtotal'], $mixed['tax_amount'], $mixed['total']]);

        // Les lignes de TVA affichées font toujours le montant enregistré.
        $lines = DocumentTotals::vatLines($mixed['items'], 20, 57.01);
        $this->assertSame(57.01, round(array_sum(array_column($lines, 'tax')), 2));
        $this->assertSame(11.0, $lines[0]['tax']);
        $this->assertTrue(DocumentTotals::hasSeveralRates($mixed['items'], 20));
        $this->assertFalse(DocumentTotals::hasZeroRate($mixed['items'], 20));
        $this->assertTrue(DocumentTotals::hasZeroRate([['quantity' => 1, 'unit_price' => 10, 'tax_rate' => 0, 'total' => 10]], 20));
        $this->assertSame('5,5', DocumentTotals::formatRate(5.5));
    }

    public function test_a_quote_carries_discounts_and_rates_from_the_form_to_every_document(): void
    {
        $pro = $this->professional();
        $this->actingAs($pro);

        // Le formulaire propose les deux champs, masqués tant qu'on ne les demande pas.
        $this->get(route('pro.quotes.create'))->assertOk()
            ->assertSee('name="items[0][discount_percent]"', false)->assertSee('name="items[0][tax_rate]"', false)
            ->assertSee('Remise ou TVA différente sur certaines lignes')
            ->assertSee('js/pro-document-lines.js', false)
            ->assertDontSee('function recalculate()', false);

        $payload = ['client_name' => 'Client Exemple', 'client_email' => 'client@example.test', 'subject' => 'Travaux',
            'operation_type' => 'services', 'is_free' => '1', 'tax_rate' => 20, 'items' => [
                ['description' => 'Peinture', 'quantity' => 2, 'unit_price' => 100, 'discount_percent' => 10, 'tax_rate' => ''],
                ['description' => 'Fournitures', 'quantity' => 1, 'unit_price' => 200, 'discount_percent' => '', 'tax_rate' => 5.5],
            ]];

        $this->post(route('pro.quotes.store'), ['items' => [['description' => 'X', 'quantity' => 1, 'unit_price' => 1, 'discount_percent' => 120]]] + $payload)
            ->assertSessionHasErrors('items.0.discount_percent');
        $this->post(route('pro.quotes.store'), $payload)->assertSessionHasNoErrors();

        $quote = ProQuote::sole();
        $this->assertSame(['380.00', '47.00', '427.00'], [$quote->subtotal, $quote->tax_amount, $quote->total]);
        $this->assertEquals(180, $quote->items[0]['total']);
        $this->assertEquals(10, $quote->items[0]['discount_percent']);
        $this->assertEquals(5.5, $quote->items[1]['tax_rate']);

        // Fiche, PDF et page de réponse du client : remise, taux de la ligne, TVA par taux.
        $quote->update(['status' => 'sent', 'sent_at' => now(), 'issued_at' => now(), 'quote_number' => 'DEV-LIGNES-1']);
        $views = [
            $this->get(route('pro.quotes.show', $quote))->assertOk()->getContent(),
            view('pro.quote-pdf', ['user' => $pro, 'quote' => $quote->fresh()])->render(),
            $this->get($quote->fresh()->responseUrl())->assertOk()->getContent(),
        ];
        foreach ($views as $html) {
            foreach (['Remise de 10 %', 'TVA 5,5 %', 'TVA 20 %', 'sur 200,00 €', 'sur 180,00 €'] as $text) {
                $this->assertStringContainsString($text, $html);
            }
        }

        // L'écran de modification rouvre les options, avec les valeurs saisies.
        $draft = ProQuote::create(['user_id' => $pro->id, 'quote_number' => 'BROUILLON-DEV-L', 'client_name' => 'Client Exemple',
            'subject' => 'Travaux', 'operation_type' => 'services', 'is_free' => true, 'items' => $quote->items,
            'subtotal' => 380, 'tax_rate' => 20, 'tax_amount' => 47, 'total' => 427, 'status' => 'draft']);
        $this->get(route('pro.quotes.edit', $draft))->assertOk()
            ->assertSee('name="items[0][discount_percent]" class="form-control pk-line__input item-discount" value="10"', false)
            ->assertSee('name="items[1][tax_rate]" class="form-control pk-line__input item-tax" value="5.5"', false)
            ->assertSee('data-pk-lines-advanced checked', false);

        // Retirer la remise à la modification recalcule le devis.
        $this->put(route('pro.quotes.update', $draft), ['client_name' => 'Client Exemple', 'subject' => 'Travaux', 'tax_rate' => 20,
            'items' => [['description' => 'Peinture', 'quantity' => 2, 'unit_price' => 100, 'discount_percent' => '', 'tax_rate' => '']]])
            ->assertSessionHasNoErrors();
        $this->assertSame('240.00', $draft->fresh()->total);
        $this->assertArrayNotHasKey('discount_percent', $draft->fresh()->items[0]);

        // Un modèle de devis (Premium) garde la remise et le taux des lignes.
        ProSubscription::create(['user_id' => $pro->id, 'plan' => 'monthly', 'amount' => 9.99, 'status' => 'active',
            'starts_at' => now()->subDay(), 'ends_at' => now()->addMonth()]);
        $this->post(route('pro.quotes.template.store', $quote), ['name' => 'Chantier type'])->assertSessionHas('success');
        $template = $pro->proQuoteTemplates()->sole()->formPayload();
        $this->assertEquals(10, $template['items'][0]['discount_percent']);
        $this->assertArrayNotHasKey('tax_rate', $template['items'][0]);
        $this->assertEquals(5.5, $template['items'][1]['tax_rate']);
    }

    public function test_an_invoice_with_a_line_without_vat_requires_the_exemption_mention(): void
    {
        $pro = $this->professional();
        $this->actingAs($pro);
        $payload = ['client_name' => 'Client Exemple', 'client_type' => 'individual', 'subject' => 'Travaux',
            'operation_type' => 'mixed', 'service_date' => now()->format('Y-m-d'), 'tax_rate' => 20,
            'payment_terms' => 'Paiement à réception.', 'early_payment_discount' => 'Néant', 'items' => [
                ['description' => 'Pose', 'quantity' => 1, 'unit_price' => 100],
                ['description' => 'Débours', 'quantity' => 1, 'unit_price' => 40, 'tax_rate' => 0],
            ]];

        $this->post(route('pro.invoices.store'), $payload)->assertSessionHasErrors('vat_exemption_reason');
        $this->assertSame(0, ProInvoice::count());

        $this->post(route('pro.invoices.store'), $payload + ['vat_exemption_reason' => 'Débours, art. 267 II du CGI'])->assertSessionHasNoErrors();
        $invoice = ProInvoice::sole();
        $this->assertSame(['140.00', '20.00', '160.00'], [$invoice->subtotal, $invoice->tax_amount, $invoice->total]);

        // Texte du PDF sans balises : la base d'un taux est insécable (balise de mise en forme).
        $pdf = preg_replace('/\s+/u', ' ', strip_tags(view('pro.invoice-pdf', ['user' => $pro, 'invoice' => $invoice])->render()));
        foreach (['TVA (0,00%) sur 40,00 €', 'TVA (20,00%) sur 100,00 €', 'Débours, art. 267 II du CGI', 'TVA 0 %'] as $text) {
            $this->assertStringContainsString($text, $pdf);
        }
        $this->get(route('pro.invoices.show', $invoice))->assertOk()->assertSeeText('TVA (20,00%) sur 100,00 €')->assertSee('Débours, art. 267 II du CGI');

        // Même exigence à la modification, sans effacer la mention déjà enregistrée.
        $update = ['client_name' => 'Client Exemple', 'subject' => 'Travaux', 'tax_rate' => 20, 'items' => $payload['items']];
        $this->put(route('pro.invoices.update', $invoice), $update)->assertSessionHasNoErrors();
        $this->put(route('pro.invoices.update', $invoice), $update + ['vat_exemption_reason' => ''])->assertSessionHasErrors('vat_exemption_reason');
        $this->assertSame('Débours, art. 267 II du CGI', $invoice->fresh()->vat_exemption_reason);

        // Une facture ordinaire à un seul taux s'affiche comme avant.
        $plain = view('pro.invoice-pdf', ['user' => $pro, 'invoice' => ProInvoice::create(['user_id' => $pro->id,
            'invoice_number' => 'FAC-SIMPLE', 'client_name' => 'Client', 'subject' => 'Travaux',
            'items' => [['description' => 'Pose', 'quantity' => 1, 'unit_price' => 100, 'total' => 100]],
            'subtotal' => 100, 'tax_rate' => 20, 'tax_amount' => 20, 'total' => 120, 'status' => 'sent', 'finalized_at' => now()])])->render();
        $this->assertStringContainsString('TVA (20,00%)</span>', $plain);
        $this->assertStringNotContainsString('Remise de', $plain);
        $this->assertStringNotContainsString('TVA 20 %', $plain);
    }

    public function test_deposits_and_credit_notes_follow_the_rates_of_the_document(): void
    {
        $pro = $this->professional();
        $this->actingAs($pro);
        $items = [
            ['description' => 'Pose', 'quantity' => 1, 'unit_price' => 1000, 'total' => 1000],
            ['description' => 'Fournitures', 'quantity' => 1, 'unit_price' => 500, 'tax_rate' => 10, 'total' => 500],
        ];
        $quote = ProQuote::create(['user_id' => $pro->id, 'quote_number' => 'DEV-TAUX-1', 'client_name' => 'Client Exemple',
            'subject' => 'Travaux', 'operation_type' => 'mixed', 'is_free' => true, 'deposit_percentage' => 30, 'items' => $items,
            'subtotal' => 1500, 'tax_rate' => 20, 'tax_amount' => 250, 'total' => 1750, 'status' => 'accepted',
            'issued_at' => now(), 'sent_at' => now(), 'accepted_at' => now()]);

        // Acompte : une ligne par taux, la TVA de chacun.
        $this->post(route('pro.quotes.deposit-invoice', $quote))->assertSessionHas('success');
        $deposit = ProInvoice::where('kind', 'deposit')->sole();
        $this->assertSame(['450.00', '75.00', '525.00'], [$deposit->subtotal, $deposit->tax_amount, $deposit->total]);
        $this->assertCount(2, $deposit->items);
        $this->assertStringEndsWith('— part à TVA 10 %', $deposit->items[0]['description']);
        $this->assertEquals(150, $deposit->items[0]['total']);
        $this->assertEquals(10, $deposit->items[0]['tax_rate']);
        $this->assertEquals(300, $deposit->items[1]['total']);
        $this->assertArrayNotHasKey('tax_rate', $deposit->items[1]);

        // Facture à deux taux, puis avoir partiel réparti au prorata.
        $invoice = ProInvoice::create(['user_id' => $pro->id, 'invoice_number' => 'FAC-TAUX-1', 'client_name' => 'Client Exemple',
            'client_type' => 'individual', 'subject' => 'Travaux', 'operation_type' => 'mixed', 'service_date' => now(), 'items' => $items,
            'subtotal' => 1500, 'tax_rate' => 20, 'tax_amount' => 250, 'total' => 1750, 'status' => 'sent', 'finalized_at' => now(),
            'payment_terms' => 'Paiement à réception.', 'early_payment_discount' => 'Néant']);
        $url = route('pro.invoices.credit-note', $invoice);
        $this->get(route('pro.invoices.show', $invoice))->assertOk()->assertSee('réparti entre les taux de TVA de la facture');

        $this->post($url, ['scope' => 'partial', 'amount_ht' => '150', 'reason' => 'Remise', 'confirm' => '1'])->assertSessionHas('success');
        $partial = ProInvoice::where('kind', 'credit_note')->sole();
        $this->assertSame(['150.00', '25.00', '175.00'], [$partial->subtotal, $partial->tax_amount, $partial->total]);
        $this->assertEquals([50, 100], array_column($partial->items, 'total'));
        $this->assertEquals(10, $partial->items[0]['tax_rate']);

        // Au-delà du reste hors taxes : refusé.
        $this->post($url, ['scope' => 'partial', 'amount_ht' => '1400', 'reason' => 'Trop', 'confirm' => '1'])
            ->assertSessionHas('error', fn (string $message) => str_contains($message, '1 350,00 € HT'));

        // Avoir total sur le reste : chaque taux garde sa base, le total solde la facture au centime.
        $this->post($url, ['scope' => 'total', 'reason' => 'Annulation', 'confirm' => '1'])->assertSessionHas('success');
        $rest = ProInvoice::where('kind', 'credit_note')->latest('id')->first();
        $this->assertSame(['1350.00', '225.00', '1575.00'], [$rest->subtotal, $rest->tax_amount, $rest->total]);
        $this->assertEquals([450, 900], array_column($rest->items, 'total'));
        $this->assertSame('cancelled', $invoice->fresh()->status);
        $this->assertSame(0.0, $invoice->fresh()->netTotal());

        $pdf = preg_replace('/\s+/u', ' ', strip_tags(view('pro.invoice-pdf', ['user' => $pro, 'invoice' => $rest])->render()));
        foreach (['TVA (10,00%) sur 450,00 €', 'TVA (20,00%) sur 900,00 €', '1 575,00 €'] as $text) {
            $this->assertStringContainsString($text, $pdf);
        }
    }

    private function professional(): User
    {
        return User::factory()->completeForVerification()->create([
            'account_type' => 'professionnel', 'user_type' => 'professionnel', 'is_service_provider' => true,
            'identity_verified' => true, 'company_name' => 'Entreprise Test', 'siret' => '12345678900012',
            'business_type' => 'entreprise', 'is_active' => true,
            'pro_terms_accepted_at' => now(), 'pro_terms_version' => config('legal.pro_terms_version'),
        ]);
    }
}
