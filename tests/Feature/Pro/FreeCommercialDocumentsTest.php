<?php

namespace Tests\Feature\Pro;

use App\Models\ProInvoice;
use App\Models\ProQuote;
use App\Models\ProSubscription;
use App\Models\User;
use App\Services\CommercialDocumentAllowance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Décision du fondateur du 24/11/2026 : tout membre, particulier ou professionnel,
 * crée 2 devis et 2 factures gratuits, sans justificatif ni immatriculation. Au-delà,
 * la création est refusée avec un lien vers l'abonnement Premium, qui lève la limite.
 * Les compteurs sont mesurés sur les documents réellement enregistrés.
 */
class FreeCommercialDocumentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_private_member_without_any_proof_creates_two_quotes_and_the_third_is_refused(): void
    {
        $member = $this->privateMember();

        $this->actingAs($member)->get(route('pro.quotes'))->assertOk()
            ->assertSee('Il vous reste 2 devis gratuits sur 2.')
            ->assertSee(route('pro.quotes.create'), false);
        $this->get(route('pro.quotes.create'))->assertOk()
            ->assertSee('Aucun SIRET n’est renseigné : aucun numéro d’immatriculation n’y figurera.');

        $this->post(route('pro.quotes.store'), $this->quotePayload())->assertRedirect(route('pro.quotes'));
        $this->get(route('pro.quotes'))->assertSee('Il vous reste 1 devis gratuit sur 2.');
        $this->post(route('pro.quotes.store'), $this->quotePayload())->assertRedirect(route('pro.quotes'));
        $this->assertSame(2, ProQuote::where('user_id', $member->id)->count());

        // Le troisième est refusé, à la page comme à l'enregistrement, sans rien créer.
        // L'abonnement n'est pas ouvert (réglage par défaut) : aucun renvoi vers Premium.
        $this->get(route('pro.quotes.create'))->assertRedirect(route('pro.quotes'))
            ->assertSessionHas('warning', 'Vous avez utilisé vos 2 devis gratuits. L’abonnement n’est pas encore ouvert.');
        $this->post(route('pro.quotes.store'), $this->quotePayload())->assertRedirect(route('pro.quotes'))
            ->assertSessionHas('warning');
        $this->post(route('pro.quotes.duplicate', ProQuote::first()))->assertRedirect(route('pro.quotes'));
        $this->assertSame(2, ProQuote::where('user_id', $member->id)->count());

        $html = $this->get(route('pro.quotes'))->assertOk()
            ->assertSee('Vous avez utilisé vos 2 devis gratuits. L’abonnement n’est pas encore ouvert.')
            ->assertDontSee('Premium lève la limite')
            ->getContent();
        $this->assertStringNotContainsString('data-doc-allowance-premium', $html);
        $this->assertStringNotContainsString('href="'.route('pro.quotes.create').'"', $html);
    }

    public function test_a_private_member_creates_two_invoices_and_the_third_is_refused(): void
    {
        $member = $this->privateMember();

        $this->actingAs($member)->get(route('pro.invoices'))->assertOk()->assertSee('Il vous reste 2 factures gratuites sur 2.');
        $this->get(route('pro.invoices.create'))->assertOk();
        $this->post(route('pro.invoices.store'), $this->invoicePayload())->assertRedirect(route('pro.invoices'));
        $this->get(route('pro.invoices'))->assertSee('Il vous reste 1 facture gratuite sur 2.');
        $this->post(route('pro.invoices.store'), $this->invoicePayload())->assertRedirect(route('pro.invoices'));

        $this->get(route('pro.invoices.create'))->assertRedirect(route('pro.invoices'))
            ->assertSessionHas('warning', 'Vous avez utilisé vos 2 factures gratuites. L’abonnement n’est pas encore ouvert.');
        $this->post(route('pro.invoices.store'), $this->invoicePayload())->assertRedirect(route('pro.invoices'));
        $this->assertSame(2, ProInvoice::where('user_id', $member->id)->count());

        $this->get(route('pro.invoices'))->assertOk()
            ->assertSee('Vous avez utilisé vos 2 factures gratuites. L’abonnement n’est pas encore ouvert.')
            ->assertDontSee('data-doc-allowance-premium', false);
    }

    public function test_free_documents_are_issued_and_downloaded_without_inventing_a_registration_number(): void
    {
        Mail::fake();
        $member = $this->privateMember();
        $this->actingAs($member)->post(route('pro.quotes.store'), $this->quotePayload());
        $quote = ProQuote::sole();

        $this->put(route('pro.quotes.status', $quote), ['status' => 'sent'])->assertRedirect(route('pro.quotes'));
        $quote->refresh();
        $this->assertSame('sent', $quote->status);
        $this->assertStringStartsWith('DEV-', $quote->quote_number);
        $this->assertNull($quote->seller_snapshot['siret']);

        $pdf = $this->get(route('pro.quotes.download', $quote))->assertOk();
        $this->assertStringContainsString('Devis_'.$quote->quote_number.'.pdf', (string) $pdf->headers->get('content-disposition'));
        $html = view('pro.quote-pdf', ['user' => $member, 'quote' => $quote])->render();
        // Le nom est échappé dans le HTML (un nom tiré au hasard peut contenir une apostrophe).
        $this->assertStringContainsString(e($member->name), $html);
        $this->assertStringNotContainsString('SIRET', $html);

        $this->post(route('pro.invoices.store'), $this->invoicePayload());
        $invoice = ProInvoice::sole();
        $this->post(route('pro.invoices.sendEmail', $invoice), ['email' => 'client@example.test'])->assertRedirect();
        $invoice->refresh();
        $this->assertSame('sent', $invoice->status);
        $this->assertStringStartsWith('FAC-', $invoice->invoice_number);
        $this->assertStringNotContainsString('SIRET', view('pro.invoice-pdf', ['user' => $member, 'invoice' => $invoice])->render());
        $this->get(route('pro.invoices.download', $invoice))->assertOk();
    }

    public function test_a_premium_subscriber_has_no_limit(): void
    {
        $member = $this->privateMember();
        ProSubscription::create(['user_id' => $member->id, 'plan' => 'monthly', 'amount' => 9.99, 'status' => 'active',
            'starts_at' => now()->subDay(), 'ends_at' => now()->addMonth()]);

        $this->actingAs($member);
        foreach (range(1, 3) as $i) {
            $this->post(route('pro.quotes.store'), $this->quotePayload())->assertRedirect(route('pro.quotes'))->assertSessionHas('success');
            $this->post(route('pro.invoices.store'), $this->invoicePayload())->assertRedirect(route('pro.invoices'))->assertSessionHas('success');
        }
        $this->assertSame(3, ProQuote::count());
        $this->assertSame(3, ProInvoice::count());

        $third = ProQuote::latest('id')->first();
        $this->put(route('pro.quotes.status', $third), ['status' => 'sent'])->assertRedirect(route('pro.quotes'));
        $this->assertSame('sent', $third->fresh()->status);

        $this->get(route('pro.quotes'))->assertOk()->assertSee('Premium actif : devis sans limite.')
            ->assertSee(route('pro.quotes.create'), false)->assertDontSee('devis gratuits sont utilisés');
    }

    /**
     * Changement de règle : un prestataire conforme créait jusqu'ici sans limite. Il garde ce
     * droit seulement s'il est abonné ; sinon la règle des 2 offerts s'applique, y compris à un
     * brouillon préparé avant la règle au-delà de ses 2 premiers documents.
     */
    public function test_a_compliant_professional_without_subscription_follows_the_two_plus_two_rule(): void
    {
        $pro = User::factory()->completeForVerification()->create([
            'account_type' => 'professionnel', 'user_type' => 'professionnel', 'business_type' => 'entreprise',
            'is_service_provider' => true, 'identity_verified' => true, 'company_name' => 'Entreprise Test', 'siret' => '12345678900012',
            'pro_terms_accepted_at' => now(), 'pro_terms_version' => config('legal.pro_terms_version'),
        ]);
        $this->assertTrue($pro->canIssueCommercialDocuments());
        $drafts = collect(range(1, 3))->map(fn () => $this->draftQuote($pro));

        $this->actingAs($pro)->get(route('pro.quotes.create'))->assertRedirect(route('pro.quotes'));

        // Ses 2 premiers devis restent émissibles ; le 3e attend l'abonnement.
        $this->put(route('pro.quotes.status', $drafts[0]), ['status' => 'sent'])->assertRedirect(route('pro.quotes'));
        $this->assertSame('sent', $drafts[0]->fresh()->status);
        $this->assertStringContainsString('12345678900012', view('pro.quote-pdf', ['user' => $pro, 'quote' => $drafts[0]->fresh()])->render());

        // Abonnement fermé : retour à la liste, sans promesse de Premium.
        $this->put(route('pro.quotes.status', $drafts[2]), ['status' => 'sent'])->assertRedirect(route('pro.quotes'))
            ->assertSessionHas('warning', 'Ce devis dépasse vos 2 devis gratuits : il reste en brouillon. L’abonnement n’est pas encore ouvert.');
        $this->assertSame('draft', $drafts[2]->fresh()->status);
        $this->assertStringStartsWith('BROUILLON-DEV-', $drafts[2]->fresh()->quote_number);
    }

    public function test_credit_notes_are_not_counted_and_deposit_invoices_are(): void
    {
        $member = $this->privateMember();
        $allowance = app(CommercialDocumentAllowance::class);
        ProInvoice::create($this->invoiceRow($member, ['kind' => ProInvoice::KIND_CREDIT_NOTE, 'status' => ProInvoice::STATUS_ISSUED]));
        $this->assertSame(0, $allowance->used($member, CommercialDocumentAllowance::INVOICE));

        ProInvoice::create($this->invoiceRow($member, ['kind' => ProInvoice::KIND_DEPOSIT]));
        ProInvoice::create($this->invoiceRow($member));
        $this->assertSame(2, $allowance->used($member, CommercialDocumentAllowance::INVOICE));
        $this->assertFalse($allowance->canCreate($member, CommercialDocumentAllowance::INVOICE));

        // Une facture d'acompte au-delà des offertes est refusée, sans rien créer.
        $quote = $this->draftQuote($member, ['status' => 'accepted', 'deposit_percentage' => 30]);
        $this->actingAs($member)->post(route('pro.quotes.deposit-invoice', $quote))->assertRedirect(route('pro.invoices'))
            ->assertSessionHas('warning');
        $this->assertSame(3, ProInvoice::count());
    }

    public function test_the_menu_opens_quotes_and_invoices_without_a_lock_to_every_member(): void
    {
        $member = $this->privateMember();
        $privateProvider = User::factory()->create(['is_service_provider' => true, 'account_type' => 'particulier', 'user_type' => 'particulier']);

        foreach ([$member, $privateProvider] as $user) {
            $html = $this->actingAs($user)->get(route('pro.quotes'))->assertOk()->getContent();
            $start = strpos($html, 'id="proSidebar"');
            $this->assertNotFalse($start, 'Le menu de l’espace Pro est affiché.');
            $menu = substr($html, $start, strpos($html, '</aside>', $start) - $start);

            $this->assertMatchesRegularExpression('#href="'.preg_quote(route('pro.quotes'), '#').'"[^>]*data-pro-nav="quotes"#', $menu);
            $this->assertMatchesRegularExpression('#href="'.preg_quote(route('pro.invoices'), '#').'"[^>]*data-pro-nav="invoices"#', $menu);
            $this->assertStringNotContainsString('fa-lock', $menu);
            $this->assertStringNotContainsString('Statut professionnel requis', $menu);
        }

        // Un membre qui n'est pas prestataire ne voit aucun outil de prestataire.
        $this->actingAs($member)->get(route('pro.invoices'))->assertOk()
            ->assertDontSee('data-pro-nav="opportunities"', false)
            ->assertDontSee('data-pro-nav="clients"', false);
    }

    private function privateMember(): User
    {
        return User::factory()->create([
            'account_type' => 'particulier', 'user_type' => 'particulier', 'is_service_provider' => false,
            'identity_verified' => false, 'siret' => null, 'company_name' => null,
        ]);
    }

    private function draftQuote(User $user, array $overrides = []): ProQuote
    {
        return ProQuote::create(array_merge([
            'user_id' => $user->id, 'quote_number' => 'BROUILLON-DEV-'.uniqid(), 'client_name' => 'Client Test', 'subject' => 'Peinture',
            'items' => [['description' => 'Peinture', 'quantity' => 1, 'unit_price' => 100, 'total' => 100]],
            'subtotal' => 100, 'tax_rate' => 20, 'tax_amount' => 20, 'total' => 120, 'status' => 'draft',
        ], $overrides));
    }

    private function invoiceRow(User $user, array $overrides = []): array
    {
        return array_merge([
            'user_id' => $user->id, 'invoice_number' => 'FAC-TEST-'.uniqid(), 'client_name' => 'Client Test', 'subject' => 'Intervention',
            'items' => [['description' => 'Intervention', 'quantity' => 1, 'unit_price' => 100, 'total' => 100]],
            'subtotal' => 100, 'tax_rate' => 20, 'tax_amount' => 20, 'total' => 120, 'status' => 'draft',
        ], $overrides);
    }

    /** @return array<string, mixed> */
    private function quotePayload(): array
    {
        return [
            'client_name' => 'Client Exemple', 'client_email' => 'client@example.test', 'client_address' => '1 rue du Client',
            'subject' => 'Travaux de peinture', 'operation_type' => 'services',
            'items' => [['description' => 'Peinture', 'quantity' => 2, 'unit_price' => 50]],
            'tax_rate' => 20, 'valid_until' => now()->addDays(30)->format('Y-m-d'), 'is_free' => 1,
        ];
    }

    /** @return array<string, mixed> */
    private function invoicePayload(): array
    {
        return [
            'client_name' => 'Client Exemple', 'client_email' => 'client@example.test', 'client_type' => 'individual',
            'subject' => 'Travaux de peinture', 'operation_type' => 'services', 'service_date' => now()->format('Y-m-d'),
            'items' => [['description' => 'Peinture', 'quantity' => 2, 'unit_price' => 50]],
            'tax_rate' => 20, 'due_date' => now()->addDays(30)->format('Y-m-d'),
            'payment_terms' => 'Paiement à 30 jours.', 'early_payment_discount' => 'Néant',
        ];
    }
}
