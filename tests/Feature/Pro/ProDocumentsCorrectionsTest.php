<?php

namespace Tests\Feature\Pro;

use App\Models\ProInvoice;
use App\Models\ProQuote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Lot 3, corrections de l'espace Pro : duplication d'un devis, TVA proposée
 * selon le statut, compteurs de l'agenda et statistiques sans brouillons.
 */
class ProDocumentsCorrectionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_issued_quote_can_be_duplicated_into_a_clean_draft(): void
    {
        $pro = $this->professional();
        $source = $this->quote($pro, ['status' => 'accepted', 'accepted_at' => now(), 'response_name' => 'Jeanne Martin',
            'response_channel' => 'online', 'public_token' => str_repeat('a', 40), 'sent_to_email' => 'client@example.test',
            'reminder_enabled' => true, 'valid_until' => now()->subDays(3), 'conditions' => 'Acompte de 30 %.']);

        // Le message d'un devis émis renvoie vers une action qui existe.
        $this->actingAs($pro)->get(route('pro.quotes.edit', $source))->assertRedirect(route('pro.quotes.show', $source))
            ->assertSessionHas('error', fn (string $message) => str_contains($message, 'Dupliquez-le'));
        $this->get(route('pro.quotes.show', $source))->assertOk()->assertSee(route('pro.quotes.duplicate', $source), false);

        $this->post(route('pro.quotes.duplicate', $source))->assertSessionHas('success');
        $copy = ProQuote::where('id', '!=', $source->id)->sole();

        $this->assertSame('draft', $copy->status);
        $this->assertStringStartsWith('BROUILLON-DEV-', $copy->quote_number);
        $this->assertSame('Travaux de peinture', $copy->subject);
        $this->assertSame('Acompte de 30 %.', $copy->conditions);
        $this->assertEquals($source->items, $copy->items);
        $this->assertSame('120.00', $copy->total);
        foreach (['issued_at', 'sent_at', 'accepted_at', 'public_token', 'response_name', 'response_channel', 'sent_to_email', 'valid_until'] as $field) {
            $this->assertNull($copy->{$field}, $field);
        }
        $this->assertFalse($copy->reminder_enabled);
        $this->assertTrue($copy->isEditable());
        $this->assertSame('accepted', $source->fresh()->status, 'Le devis d’origine garde son historique.');

        // Le devis d'un autre prestataire ne se duplique pas.
        $this->actingAs($this->professional())->post(route('pro.quotes.duplicate', $source))->assertNotFound();
    }

    public function test_the_default_vat_rate_follows_the_declared_status(): void
    {
        $company = $this->professional();
        $micro = $this->professional(['business_type' => 'auto_entrepreneur', 'tva_number' => null]);
        $microWithVat = $this->professional(['business_type' => 'auto_entrepreneur', 'tva_number' => 'FR12345678901']);

        $this->assertSame(20.0, $company->defaultVatRate());
        $this->assertSame(0.0, $micro->defaultVatRate());
        $this->assertSame(20.0, $microWithVat->defaultVatRate());

        $this->actingAs($micro)->get(route('pro.quotes.create'))->assertOk()
            ->assertSee('name="tax_rate" id="taxRate" class="form-control" value="0"', false);
        $this->actingAs($company)->get(route('pro.quotes.create'))->assertOk()
            ->assertSee('name="tax_rate" id="taxRate" class="form-control" value="20"', false);

        // Une facture tirée d'un devis reprend le taux du devis, pas 20 % d'office.
        $quote = $this->quote($company, ['status' => 'accepted', 'accepted_at' => now(), 'tax_rate' => 8.5, 'tax_amount' => 8.5, 'total' => 108.5]);
        $this->get(route('pro.invoices.create', ['quoteId' => $quote->id]))->assertOk()
            ->assertSee('name="tax_rate" id="taxRate" class="form-control" value="8.5"', false);
    }

    public function test_agenda_and_statistics_count_real_statuses_and_ignore_drafts(): void
    {
        $pro = $this->professional();
        $this->quote($pro, ['status' => 'draft', 'issued_at' => null, 'sent_at' => null]);
        $this->quote($pro, ['status' => 'sent']);
        $this->quote($pro, ['status' => 'sent']);
        $this->quote($pro, ['status' => 'accepted', 'accepted_at' => now()]);
        $this->invoice($pro, ['status' => 'draft', 'finalized_at' => null]);
        $this->invoice($pro, ['status' => 'sent', 'due_date' => now()->addDays(3)]);
        $this->invoice($pro, ['status' => 'overdue', 'due_date' => now()->subDays(5)]);
        $this->invoice($pro, ['status' => 'paid', 'paid_at' => now(), 'due_date' => now()->subDays(5)]);
        $this->invoice($pro, ['status' => 'cancelled']);

        $this->actingAs($pro)->get(route('pro.agenda'))->assertOk()
            ->assertViewHas('agendaStats', fn (array $stats) => $stats['active_quotes'] === 2
                && $stats['overdue_invoices'] === 1 && $stats['upcoming_deadlines'] === 1);

        \Illuminate\Support\Facades\DB::enableQueryLog();
        $this->get(route('pro.analytics'))->assertOk()
            ->assertViewHas('stats', fn (array $stats) => $stats['total_quotes'] === 3 && $stats['accepted_quotes'] === 1
                && (float) $stats['conversion_rate'] === 33.3
                && $stats['total_invoices'] === 3 && $stats['paid_invoices'] === 1
                && (float) $stats['pending_amount'] === 240.0);

        // PostgreSQL refuse un tri sur un alias qui porte le nom d'une colonne de
        // la table lue (erreur 500 en production le 20/11/2026). SQLite le tolère :
        // on vérifie donc la requête elle-même.
        $columns = \Illuminate\Support\Facades\Schema::getColumnListing('pro_clients');
        foreach (\Illuminate\Support\Facades\DB::getQueryLog() as $entry) {
            $sql = $entry['query'];
            if (! str_contains($sql, '"pro_clients".*') || ! preg_match_all('/\) as "(\w+)"/', $sql, $aliases)) {
                continue;
            }
            $this->assertSame([], array_values(array_intersect($aliases[1], $columns)), 'Alias en conflit avec une colonne : '.$sql);
        }
    }

    private function professional(array $overrides = []): User
    {
        return User::factory()->completeForVerification()->create(array_merge([
            'account_type' => 'professionnel', 'user_type' => 'professionnel', 'is_service_provider' => true,
            'identity_verified' => true, 'company_name' => 'Entreprise Test', 'siret' => '12345678900012',
            'business_type' => 'entreprise', 'is_active' => true,
            'pro_terms_accepted_at' => now(), 'pro_terms_version' => config('legal.pro_terms_version'),
        ], $overrides));
    }

    private function quote(User $user, array $overrides = []): ProQuote
    {
        static $number = 0;
        $number++;

        return ProQuote::create(array_merge(['user_id' => $user->id, 'quote_number' => 'DEV-COR-'.$number,
            'client_name' => 'Client Exemple', 'client_email' => 'client@example.test', 'client_address' => '1 rue du Client',
            'subject' => 'Travaux de peinture', 'operation_type' => 'services', 'currency' => 'EUR', 'is_free' => true,
            'items' => [['description' => 'Peinture', 'quantity' => 2, 'unit_price' => 50, 'total' => 100]],
            'subtotal' => 100, 'tax_rate' => 20, 'tax_amount' => 20, 'total' => 120, 'status' => 'sent',
            'sent_at' => now(), 'issued_at' => now(), 'valid_until' => now()->addDays(30)], $overrides));
    }

    private function invoice(User $user, array $overrides = []): ProInvoice
    {
        static $number = 0;
        $number++;

        return ProInvoice::create(array_merge(['user_id' => $user->id, 'invoice_number' => 'FAC-COR-'.$number,
            'client_name' => 'Client Exemple', 'subject' => 'Travaux de peinture',
            'items' => [['description' => 'Peinture', 'quantity' => 2, 'unit_price' => 50, 'total' => 100]],
            'subtotal' => 100, 'tax_rate' => 20, 'tax_amount' => 20, 'total' => 120, 'status' => 'sent',
            'finalized_at' => now(), 'sent_at' => now(), 'due_date' => now()->addDays(30)], $overrides));
    }
}
