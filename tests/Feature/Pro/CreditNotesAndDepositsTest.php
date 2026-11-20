<?php

namespace Tests\Feature\Pro;

use App\Models\ProClient;
use App\Models\ProInvoice;
use App\Models\ProQuote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Avoirs et factures d'acompte : une facture émise se corrige par un avoir, un
 * acompte se facture à part, et aucun montant n'est compté deux fois.
 */
class CreditNotesAndDepositsTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_total_credit_note_cancels_an_unpaid_invoice(): void
    {
        $pro = $this->professional();
        $invoice = $this->invoice($pro, ['reminder_enabled' => true, 'sent_to_email' => 'client@example.test']);
        $url = route('pro.invoices.credit-note', $invoice);

        $this->actingAs($pro)->get(route('pro.invoices.show', $invoice))->assertOk()
            ->assertSee('Corriger ou annuler cette facture par un avoir')->assertSee('Il reste 120,00 € TTC');

        // Motif et confirmation exigés ; un montant est exigé pour un avoir partiel.
        $this->post($url, ['scope' => 'total'])->assertSessionHasErrors(['reason', 'confirm']);
        $this->post($url, ['scope' => 'partial', 'reason' => 'Remise', 'confirm' => '1'])->assertSessionHasErrors('amount_ht');
        $this->assertSame(0, ProInvoice::where('kind', 'credit_note')->count());

        // Le compte d'un autre prestataire n'émet pas d'avoir sur cette facture.
        $this->actingAs($this->professional())->post($url, ['scope' => 'total', 'reason' => 'x', 'confirm' => '1'])->assertNotFound();

        $this->actingAs($pro)->post($url, ['scope' => 'total', 'reason' => 'Prestation annulée', 'confirm' => '1'])
            ->assertSessionHas('success');
        $credit = ProInvoice::where('kind', 'credit_note')->sole();

        $this->assertStringStartsWith('AV-'.now()->format('Y').'-', $credit->invoice_number);
        $this->assertStringEndsWith('-0001', $credit->invoice_number);
        $this->assertSame('issued', $credit->status);
        $this->assertSame($invoice->id, $credit->parent_invoice_id);
        $this->assertSame('120.00', $credit->total);
        $this->assertSame('100.00', $credit->subtotal);
        $this->assertEquals($invoice->items, $credit->items);
        $this->assertSame('Prestation annulée', $credit->credit_reason);
        $this->assertNotNull($credit->finalized_at);
        $this->assertFalse($credit->isEditable());

        $fresh = $invoice->fresh();
        $this->assertSame('cancelled', $fresh->status);
        $this->assertSame('120.00', $fresh->credited_total);
        $this->assertSame('Annulée par avoir', $fresh->getStatusLabel());
        $this->assertFalse($fresh->reminder_enabled);
        $this->assertSame('FAC-TEST-1', $fresh->invoice_number, 'La facture d’origine garde son numéro.');

        // Rien à annuler une seconde fois, et un avoir ne change pas de statut.
        $this->post($url, ['scope' => 'total', 'reason' => 'Encore', 'confirm' => '1'])->assertSessionHas('error');
        $this->put(route('pro.invoices.status', $credit), ['status' => 'paid', 'payment_method' => 'virement'])->assertSessionHas('error');
        $this->assertSame(1, ProInvoice::where('kind', 'credit_note')->count());

        // L'avoir se lit, se télécharge et s'envoie comme un avoir.
        $this->get(route('pro.invoices.show', $credit))->assertOk()
            ->assertSee('Avoir '.$credit->invoice_number)->assertSee('AVOIR')
            ->assertSee('Cet avoir corrige la facture')->assertSee('FAC-TEST-1')
            ->assertSee('Motif de l’avoir')->assertSee('Envoyer l’avoir par email')
            ->assertDontSee('Marquer payée')->assertDontSee('Corriger ou annuler cette facture');
        $pdf = view('pro.invoice-pdf', ['user' => $pro, 'invoice' => $credit->fresh()])->render();
        foreach (['AVOIR', 'Avoir sur la facture :', 'FAC-TEST-1 du', 'Total TTC de l’avoir', 'Prestation annulée'] as $text) {
            $this->assertStringContainsString($text, $pdf);
        }
        $this->assertStringNotContainsString('Conditions de paiement', $pdf);
        $this->assertStringContainsString('Avoir_'.$credit->invoice_number.'.pdf',
            (string) $this->get(route('pro.invoices.download', $credit))->assertOk()->headers->get('content-disposition'));

        $this->get(route('pro.invoices'))->assertOk()->assertSee('Annulée par avoir')->assertSee('−120,00€');
    }

    public function test_a_partial_credit_note_lowers_what_is_due_and_the_revenue(): void
    {
        $pro = $this->professional();
        $client = ProClient::create(['provider_id' => $pro->id, 'name' => 'Client Exemple', 'status' => 'active']);
        $invoice = $this->invoice($pro, ['pro_client_id' => $client->id]);
        $url = route('pro.invoices.credit-note', $invoice);
        $this->actingAs($pro);

        $this->post($url, ['scope' => 'partial', 'amount_ht' => '25', 'reason' => 'Remise accordée', 'confirm' => '1'])->assertSessionHas('success');
        $first = ProInvoice::where('kind', 'credit_note')->sole();
        $this->assertSame(['25.00', '5.00', '30.00'], [$first->subtotal, $first->tax_amount, $first->total]);
        $this->assertSame('Avoir partiel sur la facture FAC-TEST-1', $first->items[0]['description']);
        $this->assertSame('sent', $invoice->fresh()->status);
        $this->assertSame(90.0, $invoice->fresh()->netTotal());

        $this->get(route('pro.invoices.show', $invoice))->assertOk()
            ->assertSee('Avoir '.$first->invoice_number)->assertSee('-30,00 €')
            ->assertSee('Net à payer')->assertSee('90,00 €')->assertSee('Il reste 90,00 € TTC');
        $pdf = view('pro.invoice-pdf', ['user' => $pro, 'invoice' => $invoice->fresh()])->render();
        $this->assertStringContainsString('Net à payer', $pdf);
        $this->assertStringContainsString('90,00 €', $pdf);

        // Un avoir ne dépasse jamais ce qui reste de la facture.
        $this->post($url, ['scope' => 'partial', 'amount_ht' => '80', 'reason' => 'Trop', 'confirm' => '1'])
            ->assertSessionHas('error', fn (string $message) => str_contains($message, '75,00 € HT'));
        $this->assertSame(1, ProInvoice::where('kind', 'credit_note')->count());

        $this->get(route('pro.analytics'))->assertOk()
            ->assertViewHas('stats', fn (array $stats) => (float) $stats['pending_amount'] === 90.0 && $stats['total_invoices'] === 1);

        // Une facture émise ne s'annule plus par un simple changement de statut.
        $this->put(route('pro.invoices.status', $invoice), ['status' => 'cancelled'])->assertSessionHas('error');
        $this->assertSame('sent', $invoice->fresh()->status);

        // Payée : le client et le chiffre d'affaires portent le net, pas le total.
        $this->put(route('pro.invoices.status', $invoice), ['status' => 'paid', 'payment_method' => 'virement'])->assertSessionHas('success');
        $this->assertSame('90.00', $client->fresh()->total_revenue);
        $this->get(route('pro.analytics'))->assertOk()
            ->assertViewHas('stats', fn (array $stats) => (float) $stats['total_revenue'] === 90.0 && (float) $stats['pending_amount'] === 0.0);

        // Avoir total sur une facture payée : elle reste payée, tout est à rembourser.
        $this->post($url, ['scope' => 'total', 'reason' => 'Annulation', 'confirm' => '1'])->assertSessionHas('success');
        $second = ProInvoice::where('kind', 'credit_note')->latest('id')->first();
        $this->assertSame('90.00', $second->total);
        $this->assertSame('75.00', $second->subtotal);
        $this->assertSame('Solde de la facture FAC-TEST-1', $second->items[0]['description']);
        $this->assertStringEndsWith('-0002', $second->invoice_number);
        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame(0.0, $invoice->fresh()->netTotal());
        $this->assertSame('0.00', $client->fresh()->total_revenue);
        $this->get(route('pro.invoices.show', $invoice))->assertOk()->assertSee('Net réglé')->assertDontSee('Corriger ou annuler cette facture');
        $this->get(route('pro.analytics'))->assertOk()
            ->assertViewHas('stats', fn (array $stats) => (float) $stats['total_revenue'] === 0.0);
    }

    public function test_a_deposit_is_invoiced_apart_and_deducted_from_the_final_invoice(): void
    {
        $pro = $this->professional();
        $quote = $this->quote($pro);
        $this->actingAs($pro);

        $this->get(route('pro.quotes.show', $quote))->assertOk()->assertSee('Facturer l’acompte');
        $this->post(route('pro.quotes.deposit-invoice', $quote))->assertSessionHas('success');
        $deposit = ProInvoice::where('kind', 'deposit')->sole();
        $this->assertSame('draft', $deposit->status);
        $this->assertSame(['300.00', '60.00', '360.00'], [$deposit->subtotal, $deposit->tax_amount, $deposit->total]);
        $this->assertSame($quote->id, $deposit->quote_id);
        $this->assertStringStartsWith('Acompte de 30 % sur le devis DEV-ACOMPTE-1 du ', $deposit->items[0]['description']);
        $this->assertSame('Acompte payable à la commande.', $deposit->payment_terms);

        // Un seul acompte par devis ; le brouillon se relit sur l'écran de modification.
        $this->post(route('pro.quotes.deposit-invoice', $quote))->assertSessionHas('error');
        $this->get(route('pro.quotes.show', $quote))->assertOk()->assertDontSee('Facturer l’acompte');
        $this->get(route('pro.invoices.edit', $deposit))->assertOk()->assertSee('Acompte de 30 % sur le devis');

        $this->put(route('pro.invoices.status', $deposit), ['status' => 'sent'])->assertSessionHas('success');
        $deposit->refresh();
        $this->assertStringStartsWith('FAC-', $deposit->invoice_number);
        $this->get(route('pro.invoices.show', $deposit))->assertOk()->assertSee('FACTURE D’ACOMPTE');
        $this->put(route('pro.invoices.status', $deposit), ['status' => 'paid', 'payment_method' => 'virement'])->assertSessionHas('success');

        // La facture du devis reste possible, et annonce la déduction dès le brouillon.
        $this->get(route('pro.invoices.create', ['quoteId' => $quote->id]))->assertOk();
        $this->post(route('pro.invoices.store'), ['quote_id' => $quote->id, 'client_name' => 'Client Exemple', 'client_type' => 'individual',
            'subject' => 'Travaux de peinture', 'operation_type' => 'services', 'service_date' => now()->format('Y-m-d'),
            'items' => [['description' => 'Peinture', 'quantity' => 1, 'unit_price' => 1000]], 'tax_rate' => 20,
            'payment_terms' => 'Paiement à réception.', 'early_payment_discount' => 'Néant'])->assertSessionHasNoErrors();
        $final = ProInvoice::where('kind', 'invoice')->sole();
        $this->get(route('pro.invoices.show', $final))->assertOk()
            ->assertSee('Acompte déjà facturé ('.$deposit->invoice_number)->assertSee('-360,00 €')
            ->assertSee('Net à payer')->assertSee('840,00 €');
        $this->assertSame('0.00', $final->deposit_deducted, 'Rien n’est figé avant l’émission.');

        $this->put(route('pro.invoices.status', $final), ['status' => 'sent'])->assertSessionHas('success');
        $final->refresh();
        $this->assertSame('360.00', $final->deposit_deducted);
        $this->assertSame(840.0, $final->netTotal());
        $pdf = view('pro.invoice-pdf', ['user' => $pro, 'invoice' => $final])->render();
        foreach (['Total TTC', '1 200,00 €', 'Acompte déjà facturé', 'Net à payer', '840,00 €'] as $text) {
            $this->assertStringContainsString($text, $pdf);
        }

        // Aucun montant compté deux fois : 360 encaissés, 840 en attente, puis 1 200 au total.
        $this->get(route('pro.analytics'))->assertOk()->assertViewHas('stats', fn (array $stats) => (float) $stats['total_revenue'] === 360.0
            && (float) $stats['pending_amount'] === 840.0 && $stats['total_invoices'] === 2);
        $this->put(route('pro.invoices.status', $final), ['status' => 'paid', 'payment_method' => 'virement'])->assertSessionHas('success');
        $this->get(route('pro.analytics'))->assertOk()->assertViewHas('stats', fn (array $stats) => (float) $stats['total_revenue'] === 1200.0);

        // L'acompte déjà déduit ne se corrige plus à part : l'avoir se fait sur la facture.
        $this->post(route('pro.invoices.credit-note', $deposit), ['scope' => 'total', 'reason' => 'Erreur', 'confirm' => '1'])
            ->assertSessionHas('error', fn (string $message) => str_contains($message, $final->invoice_number));
        $this->assertSame(0, ProInvoice::where('kind', 'credit_note')->count());

        // Pas d'acompte après la facture, ni sans pourcentage prévu au devis.
        $this->post(route('pro.quotes.deposit-invoice', $this->quote($pro, ['deposit_percentage' => null])))->assertSessionHas('error');
        $this->post(route('pro.quotes.deposit-invoice', $this->quote($pro, ['status' => 'sent', 'accepted_at' => null])))->assertSessionHas('error');
        $this->assertSame(1, ProInvoice::where('kind', 'deposit')->count());
    }

    public function test_a_credited_invoice_frees_its_quote_and_a_credited_deposit_is_not_deducted(): void
    {
        $pro = $this->professional();
        $quote = $this->quote($pro);
        $this->actingAs($pro);

        // Acompte émis puis annulé par un avoir avant toute facture : il n'est plus déduit.
        $this->post(route('pro.quotes.deposit-invoice', $quote));
        $deposit = ProInvoice::where('kind', 'deposit')->sole();
        $this->put(route('pro.invoices.status', $deposit), ['status' => 'sent']);
        $this->post(route('pro.invoices.credit-note', $deposit), ['scope' => 'total', 'reason' => 'Acompte annulé', 'confirm' => '1'])->assertSessionHas('success');
        $this->assertSame('cancelled', $deposit->fresh()->status);
        $this->get(route('pro.quotes.show', $quote))->assertOk()->assertSee('Facturer l’acompte');

        $final = ProInvoice::create(['user_id' => $pro->id, 'quote_id' => $quote->id, 'invoice_number' => 'BROUILLON-FAC-X',
            'client_name' => 'Client Exemple', 'client_type' => 'individual', 'subject' => 'Travaux', 'operation_type' => 'services',
            'service_date' => now(), 'items' => [['description' => 'Peinture', 'quantity' => 1, 'unit_price' => 1000, 'total' => 1000]],
            'subtotal' => 1000, 'tax_rate' => 20, 'tax_amount' => 200, 'total' => 1200, 'status' => 'draft',
            'payment_terms' => 'Paiement à réception.', 'early_payment_discount' => 'Néant']);
        $this->put(route('pro.invoices.status', $final), ['status' => 'sent'])->assertSessionHas('success');
        $this->assertSame('0.00', $final->fresh()->deposit_deducted);

        // Facture du devis annulée par un avoir : le devis peut être facturé de nouveau.
        $this->get(route('pro.invoices.create', ['quoteId' => $quote->id]))->assertRedirect(route('pro.invoices.show', $final));
        $this->post(route('pro.invoices.credit-note', $final), ['scope' => 'total', 'reason' => 'Erreur de prix', 'confirm' => '1'])->assertSessionHas('success');
        $this->get(route('pro.invoices.create', ['quoteId' => $quote->id]))->assertOk();
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

    private function quote(User $user, array $overrides = []): ProQuote
    {
        static $number = 0;
        $number++;

        return ProQuote::create(array_merge(['user_id' => $user->id, 'quote_number' => 'DEV-ACOMPTE-'.$number,
            'client_name' => 'Client Exemple', 'client_email' => 'client@example.test',
            'subject' => 'Travaux de peinture', 'operation_type' => 'services', 'currency' => 'EUR', 'is_free' => true,
            'deposit_percentage' => 30,
            'items' => [['description' => 'Peinture', 'quantity' => 1, 'unit_price' => 1000, 'total' => 1000]],
            'subtotal' => 1000, 'tax_rate' => 20, 'tax_amount' => 200, 'total' => 1200, 'status' => 'accepted',
            'sent_at' => now(), 'issued_at' => now(), 'accepted_at' => now(), 'valid_until' => now()->addDays(30)], $overrides));
    }
}
