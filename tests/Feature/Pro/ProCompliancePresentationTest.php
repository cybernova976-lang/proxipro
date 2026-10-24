<?php

namespace Tests\Feature\Pro;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProCompliancePresentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_particular_provider_is_directed_to_declare_professional_status(): void
    {
        $user = $this->provider(['account_type' => 'particulier', 'user_type' => 'particulier']);

        $this->actingAs($user)->get(route('pro.compliance'))
            ->assertOk()
            ->assertSee('Statut professionnel à déclarer')
            ->assertSee('Prochaine étape : Statut professionnel déclaré')
            ->assertSee(route('pro.account-status'))
            ->assertDontSee('Brouillons uniquement');

        $this->assertNull($user->fresh()->pro_terms_accepted_at);
        $this->assertFalse($user->fresh()->canIssueCommercialDocuments());
    }

    public function test_incomplete_professional_sees_own_identity_and_next_missing_step(): void
    {
        $user = $this->provider(['company_name' => 'Atelier du Lagon', 'siret' => null]);

        $this->actingAs($user)->get(route('pro.compliance'))
            ->assertOk()
            ->assertSee('Brouillons uniquement')
            ->assertSee('Votre identité d’émetteur')
            ->assertSee('Atelier du Lagon')
            ->assertSee('Prochaine étape : Numéro d’immatriculation renseigné au format attendu')
            ->assertSee('5 / 7 étapes complétées')
            ->assertDontSee('13177594200017');

        $this->assertNull($user->fresh()->siret);
        $this->assertNull($user->fresh()->pro_terms_accepted_at);
    }

    public function test_ready_professional_has_no_remaining_step(): void
    {
        $user = $this->provider([
            'pro_terms_accepted_at' => now(),
            'pro_terms_version' => config('legal.pro_terms_version'),
        ]);

        $this->actingAs($user)->get(route('pro.compliance'))
            ->assertOk()
            ->assertSee('Émission autorisée')
            ->assertSee('7 / 7 étapes complétées')
            ->assertDontSee('Prochaine étape :')
            ->assertDontSee('id="acceptProTerms"', false);

        $this->assertTrue($user->fresh()->canIssueCommercialDocuments());
    }

    public function test_terms_require_both_explicit_confirmations_and_do_not_bypass_registration(): void
    {
        $user = $this->provider(['siret' => null]);

        $this->actingAs($user)->post(route('pro.compliance.accept'), [])
            ->assertSessionHasErrors(['accept_pro_terms', 'certify_information']);
        $this->assertNull($user->fresh()->pro_terms_accepted_at);

        $this->post(route('pro.compliance.accept'), [
            'accept_pro_terms' => '1',
            'certify_information' => '1',
        ])->assertRedirect(route('pro.compliance'));

        $this->assertTrue($user->fresh()->hasAcceptedCurrentProTerms());
        $this->assertFalse($user->fresh()->canIssueCommercialDocuments());
    }

    public function test_account_status_explains_issuers_own_company_details(): void
    {
        $user = $this->provider();

        $this->actingAs($user)->get(route('pro.account-status'))
            ->assertOk()
            ->assertSee('SASU')
            ->assertDontSee('Vous avez accès à toutes les fonctionnalités professionnelles');
    }

    public function test_account_status_uses_canonical_account_type_for_legacy_accounts(): void
    {
        $user = $this->provider(['pro_status' => null]);

        $this->actingAs($user)->get(route('pro.account-status'))
            ->assertOk()
            ->assertSee('value="entreprise" checked', false)
            ->assertSee('class="visually-hidden status-radio"', false)
            ->assertDontSee('class="d-none status-radio"', false);

        $this->assertNull($user->fresh()->pro_status);
    }

    public function test_account_status_keeps_choice_after_validation_error(): void
    {
        $user = $this->provider();

        $this->actingAs($user)->withSession(['_old_input' => ['pro_status' => 'auto-entrepreneur']])
            ->get(route('pro.account-status'))
            ->assertOk()
            ->assertSee('value="auto-entrepreneur" checked', false)
            ->assertDontSee('value="entreprise" checked', false);
    }

    private function provider(array $overrides = []): User
    {
        return User::factory()->completeForVerification()->create(array_merge([
            'account_type' => 'professionnel',
            'user_type' => 'professionnel',
            'pro_status' => 'entreprise',
            'business_type' => 'entreprise',
            'is_service_provider' => true,
            'identity_verified' => true,
            'company_name' => 'Entreprise Test',
            'siret' => '12345678900012',
        ], $overrides));
    }
}
