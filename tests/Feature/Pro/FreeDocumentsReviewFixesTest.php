<?php

namespace Tests\Feature\Pro;

use App\Models\ProInvoice;
use App\Models\ProQuote;
use App\Models\Setting;
use App\Models\User;
use App\Services\ProNextSteps;
use App\Support\PlatformFeatures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Corrections de la relecture du 24/11/2026 sur les devis et factures offerts :
 * TVA par défaut d'un particulier, identité professionnelle présentée comme une
 * recommandation (plus comme un verrou d'émission), article 1 des conditions PRO,
 * Premium promis seulement quand l'abonnement est ouvert, retour après un paiement annulé.
 */
class FreeDocumentsReviewFixesTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_private_member_gets_a_zero_vat_rate_by_default(): void
    {
        $member = $this->privateMember();
        $this->assertSame(0.0, $member->defaultVatRate());
        $this->assertSame(20.0, $this->privateMember(['tva_number' => 'FR12345678901'])->defaultVatRate());
        $this->assertSame(20.0, $this->professional()->defaultVatRate());

        $this->actingAs($member)->get(route('pro.quotes.create'))->assertOk()
            ->assertSee('name="tax_rate" id="taxRate" class="form-control" value="0"', false);
        $this->get(route('pro.invoices.create'))->assertOk()
            ->assertSee('name="tax_rate" id="taxRate" class="form-control" value="0"', false)
            ->assertSee('TVA non applicable, art. 293 B du CGI');
    }

    public function test_premium_is_offered_only_when_the_subscription_is_open(): void
    {
        $this->openSubscriptions();
        $member = $this->privateMember();
        $drafts = collect(range(1, 3))->map(fn () => ProQuote::create($this->quoteRow($member)));
        collect(range(1, 3))->each(fn () => ProInvoice::create($this->invoiceRow($member)));

        $html = $this->actingAs($member)->get(route('pro.quotes'))->assertOk()
            ->assertSee('Vos 2 devis gratuits sont utilisés : Premium lève la limite.')
            ->assertDontSee('L’abonnement n’est pas encore ouvert.')
            ->getContent();
        $this->assertMatchesRegularExpression('#href="'.preg_quote(route('pro.subscription'), '#').'"[^>]*data-doc-allowance-premium#', $html);

        $this->get(route('pro.quotes.create'))->assertRedirect(route('pro.quotes'))
            ->assertSessionHas('warning', 'Vous avez utilisé vos 2 devis gratuits. Passez à Premium pour créer des devis sans limite.');
        $this->put(route('pro.quotes.status', $drafts[2]), ['status' => 'sent'])->assertRedirect(route('pro.subscription'))
            ->assertSessionHas('warning', 'Ce devis dépasse vos 2 devis gratuits : il reste en brouillon. Passez à Premium pour l’émettre.');
        $this->assertSame('draft', $drafts[2]->fresh()->status);

        $third = ProInvoice::latest('id')->first();
        $this->get(route('pro.invoices.show', $third))->assertOk()
            ->assertSee('data-invoice-over-allowance', false)
            ->assertSee('Passer à Premium');

        $this->get(route('quote-tool.landing'))->assertOk()
            ->assertSee('Si l’outil vous plaît, l’abonnement Premium lève la limite.')
            ->assertSee('Ce que Premium ajoute')
            ->assertDontSee('data-pk-premium-closed', false);
    }

    public function test_without_an_open_subscription_the_quota_applies_with_a_neutral_message(): void
    {
        $this->assertFalse(PlatformFeatures::proSubscriptionsEnabled());
        $member = $this->privateMember();
        collect(range(1, 3))->each(fn () => ProInvoice::create($this->invoiceRow($member)));
        $third = ProInvoice::latest('id')->first();

        $html = $this->actingAs($member)->get(route('pro.invoices.show', $third))->assertOk()
            ->assertSee('Cette facture dépasse vos 2 factures gratuites : elle reste en brouillon. L’abonnement n’est pas encore ouvert.')
            ->getContent();
        $start = strpos($html, 'data-invoice-over-allowance');
        $banner = substr($html, $start, strpos($html, '</div>', $start) - $start);
        $this->assertStringNotContainsString('Passer à Premium', $banner);
        $this->assertStringNotContainsString(route('pro.subscription'), $banner);

        // L'émission reste refusée : la facture garde son numéro de brouillon.
        $this->put(route('pro.invoices.status', $third), ['status' => 'sent'])->assertRedirect(route('pro.invoices'))
            ->assertSessionHas('warning', 'Cette facture dépasse vos 2 factures gratuites : elle reste en brouillon. L’abonnement n’est pas encore ouvert.');
        $this->assertSame('draft', $third->fresh()->status);

        $this->get(route('quote-tool.landing'))->assertOk()
            ->assertSee('Au-delà, l’abonnement Premium lèvera la limite : il n’est pas encore ouvert.')
            ->assertSee('Ce que Premium ajoutera')
            ->assertSee('data-pk-premium-closed', false)
            ->assertDontSee('Si l’outil vous plaît');
    }

    public function test_the_compliance_page_presents_the_identity_as_a_recommendation(): void
    {
        $incomplete = $this->professional(['siret' => null]);

        $this->actingAs($incomplete)->get(route('pro.compliance'))->assertOk()
            ->assertSee('Identité professionnelle sur vos documents')
            ->assertSee('data-identity-not-a-lock', false)
            ->assertSee('Recommandé, pas obligatoire pour créer vos documents')
            ->assertSee('Identité à compléter')
            ->assertDontSee('Conditions d’émission')
            ->assertDontSee('Émission autorisée')
            ->assertDontSee('prêt pour l’émission')
            ->assertDontSee('émettez vos documents');

        $ready = $this->professional(['pro_terms_accepted_at' => now(), 'pro_terms_version' => config('legal.pro_terms_version')]);
        $this->actingAs($ready)->get(route('pro.compliance'))->assertOk()
            ->assertSee('Identité complète')
            ->assertSee('Votre identité professionnelle est complète : elle figurera sur vos documents.')
            ->assertDontSee('Émission autorisée');
    }

    public function test_account_status_and_next_steps_no_longer_tie_issuing_to_the_identity(): void
    {
        $provider = User::factory()->completeForVerification()->create([
            'is_service_provider' => true, 'account_type' => 'particulier', 'user_type' => 'particulier', 'identity_verified' => true,
        ]);

        $this->actingAs($provider)->get(route('pro.account-status'))->assertOk()
            ->assertDontSee('conditions d’émission')
            ->assertDontSee('Pas de devis ni de facture à votre nom.')
            ->assertSee('Vos devis et factures portent votre nom, sans numéro SIRET.');

        $this->put(route('pro.account-status.update'), [
            'pro_status' => 'auto-entrepreneur', 'company_name' => 'Atelier du Lagon', 'siret' => '123 456 789 00012',
        ])->assertRedirect(route('pro.compliance'))
            ->assertSessionHas('success', fn (string $message) => str_contains($message, 'à compléter pour l’identité professionnelle de vos devis et factures')
                && ! str_contains($message, 'pour émettre'));

        $steps = collect(app(ProNextSteps::class)->for($provider->fresh(), ['new_opportunities' => 0, 'has_services' => true]));
        $compliance = $steps->firstWhere('key', 'compliance');
        $this->assertNotNull($compliance);
        $this->assertStringContainsString('Elle figurera sur vos devis et factures', $compliance['detail']);
    }

    public function test_the_pro_terms_describe_the_free_documents_offer(): void
    {
        $this->get(route('legal.pro-terms'))->assertOk()
            ->assertSee('Tout membre connecté, particulier ou professionnel, peut créer, émettre et télécharger 2 devis et 2 factures sans frais, sans fournir de justificatif.')
            ->assertSee('nécessitent un Premium actif (abonnement ou accès à paiement unique)')
            ->assertSee('il n’est pas partie aux devis et factures')
            ->assertDontSee('réservée à un compte professionnel')
            ->assertDontSee('brouillons non contractuels');
    }

    public function test_a_cancelled_payment_keeps_its_message_for_a_member_who_is_not_a_provider(): void
    {
        $member = $this->privateMember();

        $this->actingAs($member)->get(route('pro.onboarding.payment.cancel'))
            ->assertRedirect(route('pro.subscription'))
            ->assertSessionHas('info', 'Le paiement a été annulé. Vous pouvez réessayer quand vous le souhaitez.');
        $this->get(route('pro.subscription'))->assertOk()
            ->assertSee('Le paiement a été annulé. Vous pouvez réessayer quand vous le souhaitez.');

        // Un prestataire qui n'a pas terminé sa configuration reprend l'onboarding.
        $provider = $this->professional(['pro_onboarding_completed' => false]);
        $this->actingAs($provider)->get(route('pro.onboarding.payment.cancel'))
            ->assertRedirect(route('pro.onboarding'))
            ->assertSessionHas('info');
    }

    private function openSubscriptions(): void
    {
        config([
            'services.stripe.key' => 'pk_live_configured',
            'services.stripe.secret' => 'sk_live_configured',
            'services.stripe.webhook_secret' => 'whsec_configured',
            'app.url' => 'https://marketplace.example',
        ]);
        Setting::set('legal_entity_name', 'Marketplace Test', 'legal');
        Setting::set('legal_registration_number', 'REG-123', 'legal');
        Setting::set('legal_address', '1 rue du Test', 'legal');
        Setting::set('legal_publication_director', 'Direction Test', 'legal');
        Setting::set('platform_public_url', 'https://marketplace.example', 'legal');
        Setting::set('stripe_billing_portal_configured', '1', 'subscriptions');
        PlatformFeatures::setProSubscriptionsEnabled(true);
        $this->assertTrue(PlatformFeatures::proSubscriptionsEnabled());
    }

    private function privateMember(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'account_type' => 'particulier', 'user_type' => 'particulier', 'is_service_provider' => false,
            'business_type' => null, 'identity_verified' => false, 'siret' => null, 'company_name' => null, 'tva_number' => null,
        ], $overrides));
    }

    private function professional(array $overrides = []): User
    {
        return User::factory()->completeForVerification()->create(array_merge([
            'account_type' => 'professionnel', 'user_type' => 'professionnel', 'pro_status' => 'entreprise',
            'business_type' => 'entreprise', 'is_service_provider' => true, 'identity_verified' => true,
            'company_name' => 'Entreprise Test', 'siret' => '12345678900012', 'tva_number' => null,
        ], $overrides));
    }

    private function quoteRow(User $user): array
    {
        return [
            'user_id' => $user->id, 'quote_number' => 'BROUILLON-DEV-'.uniqid(), 'client_name' => 'Client Test', 'subject' => 'Peinture',
            'items' => [['description' => 'Peinture', 'quantity' => 1, 'unit_price' => 100, 'total' => 100]],
            'subtotal' => 100, 'tax_rate' => 0, 'tax_amount' => 0, 'total' => 100, 'status' => 'draft',
        ];
    }

    private function invoiceRow(User $user): array
    {
        return [
            'user_id' => $user->id, 'invoice_number' => 'BROUILLON-FAC-'.uniqid(), 'client_name' => 'Client Test', 'subject' => 'Intervention',
            'items' => [['description' => 'Intervention', 'quantity' => 1, 'unit_price' => 100, 'total' => 100]],
            'subtotal' => 100, 'tax_rate' => 0, 'tax_amount' => 0, 'total' => 100, 'status' => 'draft',
            'vat_exemption_reason' => 'TVA non applicable, art. 293 B du CGI',
        ];
    }
}
