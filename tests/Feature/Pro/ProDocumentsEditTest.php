<?php

namespace Tests\Feature\Pro;

use App\Models\ProClient;
use App\Models\ProInvoice;
use App\Models\ProQuote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Modifier un brouillon de devis ou de facture donne accès aux mêmes champs
 * que la création : rien de ce qui a été saisi n'est figé ni perdu.
 */
class ProDocumentsEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_edit_screens_offer_every_field_of_the_creation_screens(): void
    {
        $pro = $this->professional();
        $quote = $this->quote($pro);
        $invoice = $this->invoice($pro);
        $names = function (string $html): array {
            preg_match_all('/<(?:input|select|textarea)\b[^>]*\bname="([a-z_]+)"/', $html, $found);

            return array_values(array_unique($found[1]));
        };
        $this->actingAs($pro);

        $missing = array_diff(
            $names($this->get(route('pro.quotes.create'))->assertOk()->getContent()),
            $names($this->get(route('pro.quotes.edit', $quote))->assertOk()->getContent())
        );
        $this->assertSame([], array_values($missing), 'Champs du devis absents de la modification.');

        // « quote_id » lie une facture à son devis au moment de la création seulement.
        $missing = array_diff(
            $names($this->get(route('pro.invoices.create'))->assertOk()->getContent()),
            $names($this->get(route('pro.invoices.edit', $invoice))->assertOk()->getContent()),
            ['quote_id']
        );
        $this->assertSame([], array_values($missing), 'Champs de la facture absents de la modification.');
    }

    public function test_a_draft_quote_is_edited_with_all_its_fields(): void
    {
        $pro = $this->professional();
        $client = ProClient::create(['provider_id' => $pro->id, 'name' => 'Client Exemple', 'email' => 'client@example.test', 'status' => 'active']);
        $quote = $this->quote($pro, ['pro_client_id' => $client->id]);

        // Les valeurs saisies à la création sont affichées, et le client lié est présélectionné.
        $this->actingAs($pro)->get(route('pro.quotes.edit', $quote))->assertOk()
            ->assertSee('value="SARL Exemple"', false)->assertSee('value="123456789"', false)
            ->assertSee('value="Chantier, 2 rue du Port"', false)->assertSee('value="30.00"', false)
            ->assertSee('<option value="goods" selected', false)
            ->assertSee('<option value="0" selected', false)
            ->assertSee('id="clientIdHidden" value="'.$client->id.'"', false);

        $payload = ['client_id' => $client->id, 'client_name' => 'Client Exemple', 'client_email' => 'client@example.test',
            'subject' => 'Travaux de peinture', 'tax_rate' => 20, 'valid_until' => now()->addDays(20)->format('Y-m-d'),
            'items' => [['description' => 'Peinture', 'quantity' => 3, 'unit_price' => 50]]];

        // Formulaire ouvert avant la mise à jour (sans les nouveaux champs) : rien n'est effacé.
        $this->put(route('pro.quotes.update', $quote), $payload)->assertSessionHasNoErrors();
        $fresh = $quote->fresh();
        $this->assertSame('SARL Exemple', $fresh->client_company);
        $this->assertSame('goods', $fresh->operation_type);
        $this->assertFalse($fresh->is_free);
        $this->assertSame('30.00', $fresh->deposit_percentage);
        $this->assertSame('180.00', $fresh->total);
        $this->assertSame($client->id, $fresh->pro_client_id);

        $this->put(route('pro.quotes.update', $quote), $payload + ['client_company' => 'SAS Nouvelle', 'client_registration_number' => '',
            'client_vat_number' => 'FR11222333444', 'operation_type' => 'mixed', 'execution_location' => 'À distance',
            'is_free' => '1', 'deposit_percentage' => '40'])->assertSessionHasNoErrors();
        $fresh = $quote->fresh();
        $this->assertSame('SAS Nouvelle', $fresh->client_company);
        $this->assertNull($fresh->client_registration_number);
        $this->assertSame('FR11222333444', $fresh->client_vat_number);
        $this->assertSame('mixed', $fresh->operation_type);
        $this->assertSame('À distance', $fresh->execution_location);
        $this->assertTrue($fresh->is_free);
        $this->assertSame('40.00', $fresh->deposit_percentage);

        $this->put(route('pro.quotes.update', $quote), $payload + ['operation_type' => 'autre'])->assertSessionHasErrors('operation_type');
        $this->assertSame(1, ProClient::count(), 'Aucun client en double.');
    }

    public function test_a_draft_invoice_is_edited_with_its_legal_mentions(): void
    {
        $pro = $this->professional();
        $invoice = $this->invoice($pro);

        $this->actingAs($pro)->get(route('pro.invoices.edit', $invoice))->assertOk()
            ->assertSee('value="SARL Exemple"', false)->assertSee('value="BC-2026-014"', false)
            ->assertSee('<option value="business" selected', false)
            ->assertSee('Paiement à réception.')->assertSee('value="'.now()->subDays(2)->format('Y-m-d').'"', false);

        $payload = ['client_name' => 'Client Exemple', 'client_email' => 'client@example.test', 'subject' => 'Travaux de peinture',
            'tax_rate' => 20, 'payment_method' => 'virement', 'items' => [['description' => 'Peinture', 'quantity' => 2, 'unit_price' => 50]]];

        // Formulaire ouvert avant la mise à jour : les mentions enregistrées sont conservées.
        $this->put(route('pro.invoices.update', $invoice), $payload)->assertSessionHasNoErrors();
        $fresh = $invoice->fresh();
        $this->assertSame('business', $fresh->client_type);
        $this->assertSame('Paiement à réception.', $fresh->payment_terms);
        $this->assertSame('12.00', $fresh->late_penalty_rate);

        $full = $payload + ['client_company' => 'SAS Nouvelle', 'client_type' => 'individual', 'client_registration_number' => '',
            'client_vat_number' => '', 'operation_type' => 'goods', 'service_date' => now()->format('Y-m-d'),
            'purchase_order_number' => 'BC-2026-020', 'delivery_address' => '3 quai Nord', 'payment_terms' => 'Paiement à 15 jours.',
            'early_payment_discount' => 'Néant', 'late_penalty_rate' => '', 'vat_exemption_reason' => ''];
        $this->put(route('pro.invoices.update', $invoice), $full)->assertSessionHasNoErrors();
        $fresh = $invoice->fresh();
        $this->assertSame('SAS Nouvelle', $fresh->client_company);
        $this->assertSame('individual', $fresh->client_type);
        $this->assertSame('goods', $fresh->operation_type);
        $this->assertSame(now()->format('Y-m-d'), $fresh->service_date->format('Y-m-d'));
        $this->assertSame('BC-2026-020', $fresh->purchase_order_number);
        $this->assertSame('3 quai Nord', $fresh->delivery_address);
        // L'adresse de livraison était saisie mais n'apparaissait sur aucun document.
        $this->get(route('pro.invoices.show', $invoice))->assertOk()->assertSee('Adresse de livraison : 3 quai Nord');
        $this->assertStringContainsString('Adresse de livraison : 3 quai Nord',
            view('pro.invoice-pdf', ['user' => $pro, 'invoice' => $fresh])->render());
        $this->assertSame('Paiement à 15 jours.', $fresh->payment_terms);
        $this->assertNull($fresh->late_penalty_rate);

        // Mêmes exigences qu'à la création : mention d'exonération à 0 %, pénalités pour un professionnel.
        $this->put(route('pro.invoices.update', $invoice), ['tax_rate' => 0] + $full)->assertSessionHasErrors('vat_exemption_reason');
        $this->put(route('pro.invoices.update', $invoice), ['client_type' => 'business'] + $full)->assertSessionHasErrors('late_penalty_rate');
        $this->put(route('pro.invoices.update', $invoice), ['tax_rate' => 0, 'vat_exemption_reason' => 'TVA non applicable, art. 293 B du CGI'] + $full)
            ->assertSessionHasNoErrors();
        $this->assertSame('100.00', $invoice->fresh()->total);
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

    private function quote(User $user, array $overrides = []): ProQuote
    {
        return ProQuote::create(array_merge(['user_id' => $user->id, 'quote_number' => 'BROUILLON-DEV-TEST',
            'client_name' => 'Client Exemple', 'client_email' => 'client@example.test', 'client_company' => 'SARL Exemple',
            'client_registration_number' => '123456789', 'client_vat_number' => 'FR00123456789',
            'subject' => 'Travaux de peinture', 'operation_type' => 'goods', 'execution_location' => 'Chantier, 2 rue du Port',
            'currency' => 'EUR', 'is_free' => false, 'deposit_percentage' => 30,
            'items' => [['description' => 'Peinture', 'quantity' => 2, 'unit_price' => 50, 'total' => 100]],
            'subtotal' => 100, 'tax_rate' => 20, 'tax_amount' => 20, 'total' => 120, 'status' => 'draft',
            'valid_until' => now()->addDays(30)], $overrides));
    }

    private function invoice(User $user, array $overrides = []): ProInvoice
    {
        return ProInvoice::create(array_merge(['user_id' => $user->id, 'invoice_number' => 'BROUILLON-FAC-TEST',
            'client_name' => 'Client Exemple', 'client_email' => 'client@example.test', 'client_company' => 'SARL Exemple',
            'client_type' => 'business', 'subject' => 'Travaux de peinture', 'operation_type' => 'services',
            'service_date' => now()->subDays(2), 'purchase_order_number' => 'BC-2026-014', 'currency' => 'EUR',
            'items' => [['description' => 'Peinture', 'quantity' => 2, 'unit_price' => 50, 'total' => 100]],
            'subtotal' => 100, 'tax_rate' => 20, 'tax_amount' => 20, 'total' => 120, 'status' => 'draft',
            'payment_terms' => 'Paiement à réception.', 'early_payment_discount' => 'Néant', 'late_penalty_rate' => 12],
            $overrides));
    }
}
