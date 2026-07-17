<?php

namespace Tests\Feature\Pro;

use App\Models\ProClient;
use App\Models\ProInvoice;
use App\Models\ProQuote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ProCommercialDocumentsFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_particular_provider_cannot_issue_professional_documents(): void
    {
        $user = User::factory()->create([
            'account_type' => 'particulier',
            'user_type' => 'particulier',
            'is_service_provider' => true,
        ]);

        $this->actingAs($user)->get(route('pro.quotes.create'))
            ->assertRedirect(route('pro.account-status'));
    }

    public function test_professional_can_prepare_draft_before_registration_is_complete(): void
    {
        $user = $this->professional(['siret' => null, 'identity_verified' => false]);

        $this->actingAs($user)->post(route('pro.quotes.store'), $this->quotePayload())
            ->assertRedirect(route('pro.quotes'));

        $quote = ProQuote::sole();
        $this->assertSame('draft', $quote->status);
        $this->assertStringStartsWith('BROUILLON-DEV-', $quote->quote_number);
        $this->assertNotNull($quote->pro_client_id);
        $this->assertDatabaseHas('pro_clients', [
            'id' => $quote->pro_client_id,
            'provider_id' => $user->id,
            'email' => 'client@example.test',
        ]);

        $this->actingAs($user)->post(route('pro.quotes.sendEmail', $quote), [
            'email' => 'client@example.test',
        ])->assertRedirect(route('pro.compliance'));

        $this->assertSame('draft', $quote->fresh()->status);
    }

    public function test_client_selection_is_scoped_to_the_authenticated_professional(): void
    {
        $owner = $this->professional();
        $attacker = $this->professional();
        $foreignClient = ProClient::create([
            'provider_id' => $owner->id,
            'name' => 'Client privé',
            'email' => 'private@example.test',
        ]);

        $payload = $this->quotePayload() + ['client_id' => $foreignClient->id];

        $this->actingAs($attacker)->post(route('pro.quotes.store'), $payload)
            ->assertSessionHasErrors('client_id');
        $this->assertDatabaseCount('pro_quotes', 0);
    }

    public function test_invoice_gets_final_number_on_send_and_is_then_immutable(): void
    {
        Mail::fake();
        $user = $this->professional([
            'identity_verified' => true,
            'pro_terms_accepted_at' => now(),
            'pro_terms_version' => config('legal.pro_terms_version'),
        ]);

        $this->actingAs($user)->post(route('pro.invoices.store'), $this->invoicePayload())
            ->assertRedirect(route('pro.invoices'));

        $invoice = ProInvoice::sole();
        $this->assertStringStartsWith('BROUILLON-FAC-', $invoice->invoice_number);

        $this->post(route('pro.invoices.sendEmail', $invoice), [
            'email' => 'client@example.test',
            'message' => 'Votre facture.',
        ])->assertRedirect();

        $invoice->refresh();
        $this->assertSame('sent', $invoice->status);
        $this->assertStringStartsWith('FAC-'.now()->year.'-'.str_pad((string) $user->id, 6, '0', STR_PAD_LEFT).'-', $invoice->invoice_number);
        $this->assertNotNull($invoice->finalized_at);
        $this->assertNotNull($invoice->sent_at);
        $this->assertSame($user->company_name, $invoice->seller_snapshot['company_name']);

        $this->get(route('pro.invoices.edit', $invoice))
            ->assertRedirect(route('pro.invoices.show', $invoice));
        $this->delete(route('pro.invoices.destroy', $invoice))
            ->assertSessionHas('error');
        $this->assertDatabaseHas('pro_invoices', ['id' => $invoice->id]);
    }

    public function test_marking_invoice_paid_updates_client_revenue_only_once(): void
    {
        $user = $this->professional([
            'identity_verified' => true,
            'pro_terms_accepted_at' => now(),
            'pro_terms_version' => config('legal.pro_terms_version'),
        ]);
        $client = ProClient::create(['provider_id' => $user->id, 'name' => 'Client A']);
        $invoice = ProInvoice::create([
            'user_id' => $user->id,
            'pro_client_id' => $client->id,
            'invoice_number' => 'FAC-'.now()->year.'-000001-0001',
            'client_name' => 'Client A',
            'subject' => 'Mission',
            'items' => [['description' => 'Service', 'quantity' => 1, 'unit_price' => 100, 'total' => 100]],
            'subtotal' => 100,
            'tax_rate' => 20,
            'tax_amount' => 20,
            'total' => 120,
            'status' => 'sent',
            'finalized_at' => now(),
        ]);

        $this->actingAs($user)->put(route('pro.invoices.status', $invoice), [
            'status' => 'paid',
            'payment_method' => 'virement',
        ])->assertRedirect(route('pro.invoices'));

        $this->put(route('pro.invoices.status', $invoice), [
            'status' => 'paid',
            'payment_method' => 'virement',
        ])->assertSessionHas('error');

        $client->refresh();
        $this->assertSame('120.00', $client->total_revenue);
        $this->assertSame(1, $client->total_projects);
    }

    private function professional(array $overrides = []): User
    {
        return User::factory()->completeForVerification()->create(array_merge([
            'account_type' => 'professionnel',
            'user_type' => 'professionnel',
            'is_service_provider' => true,
            'identity_verified' => true,
            'company_name' => 'Entreprise Test',
            'siret' => '12345678900012',
            'business_type' => 'entreprise',
        ], $overrides));
    }

    /** @return array<string, mixed> */
    private function quotePayload(): array
    {
        return [
            'client_name' => 'Client Exemple',
            'client_email' => 'client@example.test',
            'client_address' => '1 rue du Client',
            'subject' => 'Travaux de peinture',
            'operation_type' => 'services',
            'execution_location' => 'Paris',
            'items' => [['description' => 'Peinture', 'quantity' => 2, 'unit_price' => 50]],
            'tax_rate' => 20,
            'valid_until' => now()->addDays(30)->format('Y-m-d'),
            'is_free' => 1,
            'deposit_percentage' => 30,
        ];
    }

    /** @return array<string, mixed> */
    private function invoicePayload(): array
    {
        return [
            'client_name' => 'Client Exemple',
            'client_email' => 'client@example.test',
            'client_address' => '1 rue du Client',
            'client_type' => 'business',
            'client_registration_number' => '987654321',
            'subject' => 'Travaux de peinture',
            'operation_type' => 'services',
            'service_date' => now()->format('Y-m-d'),
            'items' => [['description' => 'Peinture', 'quantity' => 2, 'unit_price' => 50]],
            'tax_rate' => 20,
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'payment_method' => 'virement',
            'payment_terms' => 'Paiement à 30 jours.',
            'early_payment_discount' => 'Néant',
            'late_penalty_rate' => 12,
        ];
    }
}
