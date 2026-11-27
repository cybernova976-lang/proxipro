<?php

namespace Tests\Feature\Admin;

use App\Models\IdentityVerification;
use App\Models\TermsVersion;
use App\Models\User;
use App\Support\AdminUserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

/**
 * Fiche d'un membre dans l'administration : entreprise, profil prestataire,
 * localisation et compte. Seules les valeurs enregistrées apparaissent, et les
 * données techniques ou sensibles ne sont jamais montrées.
 */
class AdminUserProfileFactsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_the_admin_sees_the_company_provider_and_account_details(): void
    {
        $referrer = User::factory()->create(['name' => 'Marraine Test']);
        $member = User::factory()->create([
            'name' => 'Atelier Fiche', 'email_verified_at' => now()->subDays(3), 'phone' => '+262 639 00 00 00',
            'account_type' => 'professionnel', 'business_type' => 'entreprise', 'company_name' => 'Atelier du Récif',
            'siret' => '73282932000074', 'tva_number' => 'FR44732829320', 'insurance_number' => 'RC-2026-889',
            'profession' => 'Électricien', 'city' => 'Mamoudzou', 'postal_code' => '97600', 'country' => 'Mayotte',
            'website_url' => 'https://atelier-recif.example', 'social_links' => ['facebook' => 'https://facebook.com/atelier', 'tiktok' => 'javascript:alert(1)'],
            'hourly_rate' => 45, 'show_hourly_rate' => false, 'years_experience' => 12, 'pro_intervention_radius' => 25,
            'specialties' => ['Tableaux électriques', 'Climatisation'],
            'stripe_id' => 'cus_fiche', 'stripe_connect_account_id' => 'acct_fiche', 'stripe_connect_payouts_enabled' => false,
            'referral_code' => 'RECIF26', 'referred_by_user_id' => $referrer->id,
            'pro_terms_version' => '2026-07-19', 'pro_terms_accepted_at' => now()->subDays(2),
            'pro_terms_ip' => '203.0.113.77', 'email_verification_code' => '493817',
        ]);
        $member->forceFill(['is_service_provider' => true, 'service_provider_since' => now()->subMonth()])->save();
        $version = TermsVersion::create(['version' => '2026-11', 'summary' => 'Mise à jour.', 'effective_at' => now()->subWeek()]);
        $member->acceptTerms($version, '198.51.100.23', 'test');
        $verification = IdentityVerification::create(['user_id' => $member->id, 'document_type' => 'id_card', 'status' => 'pending', 'submitted_at' => now()]);

        // Une version des conditions en vigueur s'impose aussi à l'administrateur qui consulte la fiche.
        $admin = $this->admin();
        $admin->acceptTerms($version, null, 'test');

        $html = $this->actingAs($admin)->get(route('admin.users.show', $member->id))->assertOk()->getContent();

        foreach ([
            'Atelier du Récif', 'Entreprise (société)', '732 829 320 00074', 'https://annuaire-entreprises.data.gouv.fr/etablissement/73282932000074',
            'FR44732829320', 'RC-2026-889', 'Électricien',
            'Tableaux électriques, Climatisation', '12 ans', '45,00 €', 'Masqué sur le profil public.', '25 km', 'https://atelier-recif.example',
            'https://facebook.com/atelier', 'Mamoudzou', '97600', 'Mayotte',
            'Confirmée le '.now()->subDays(3)->format('d/m/Y'), 'Version 2026-11 acceptée le '.now()->format('d/m/Y'),
            'Version 2026-07-19 acceptée le', 'En cours d’examen', route('admin.verifications.show', $verification->id),
            'acct_fiche', 'Versements non activés', 'cus_fiche', 'RECIF26', 'Marraine Test', route('admin.users.show', $referrer->id),
        ] as $expected) {
            $this->assertStringContainsString(e($expected), $html, 'Absent de la fiche : '.$expected);
        }

        // Un SIRET valide n'est pas signalé ; un lien qui n'est pas une adresse web n'est jamais cliquable.
        $this->assertStringNotContainsString('La clé de contrôle de ce numéro ne correspond pas', $html);
        $this->assertStringNotContainsString('href="javascript:', $html);

        // Jamais montrés : adresses IP, code de confirmation, mot de passe chiffré.
        foreach (['203.0.113.77', '198.51.100.23', '493817', $member->password] as $hidden) {
            $this->assertStringNotContainsString($hidden, $html);
        }
    }

    public function test_an_empty_profile_shows_no_invented_value_and_a_wrong_siret_is_flagged(): void
    {
        $member = User::factory()->create(['email_verified_at' => null, 'account_type' => 'particulier']);

        $this->actingAs($this->admin())->get(route('admin.users.show', $member->id))->assertOk()
            ->assertSee('Aucune information d’entreprise renseignée.')
            ->assertSee('Aucun profil prestataire renseigné.')
            ->assertSee('Aucune localisation renseignée.')
            ->assertSee('Non confirmée')
            ->assertSee('Aucune acceptation enregistrée')
            ->assertSee('Aucune connexion enregistrée pour ce compte.')
            ->assertDontSee('Stripe Connect');

        $wrong = AdminUserProfile::registrationNumber('12345678900012');
        $this->assertSame('SIRET', $wrong['label']);
        $this->assertTrue($wrong['warning']);
        $this->assertStringContainsString('clé de contrôle', $wrong['note']);

        $siren = AdminUserProfile::registrationNumber('732829320');
        $this->assertSame('SIREN', $siren['label']);
        $this->assertSame('https://annuaire-entreprises.data.gouv.fr/entreprise/732829320', $siren['href']);
        $this->assertArrayNotHasKey('warning', $siren);

        // Établissement de La Poste : règle de clé particulière.
        $this->assertArrayNotHasKey('warning', AdminUserProfile::registrationNumber('35600000049837'));

        $odd = AdminUserProfile::registrationNumber('MYT-2024-B-118');
        $this->assertTrue($odd['warning']);
        $this->assertArrayNotHasKey('href', $odd);
    }

    public function test_the_principal_admin_profile_also_shows_its_details(): void
    {
        $principal = User::factory()->create(['role' => 'admin', 'company_name' => 'Société Principale']);
        config(['admin.principal_admin.email' => $principal->email]);

        $this->actingAs($principal)->get(route('admin.users.show', $principal->id))->assertOk()
            ->assertSee('Société Principale')
            ->assertSee('data-profile-section="account"', false);
    }

    public function test_only_the_three_most_recent_successful_logins_appear_on_the_members_admin_profile(): void
    {
        $member = User::factory()->create();
        $otherMember = User::factory()->create();
        $start = Carbon::parse('2026-11-27 08:07:15', 'UTC');
        $profileUrl = route('admin.users.show', $member->id);

        $this->travelTo($start->copy()->subHour());
        $this->post(route('login.attempt'), ['email' => $otherMember->email, 'password' => 'password'])
            ->assertRedirect(route('feed'));
        $this->post(route('logout'));

        foreach ([0, 1, 2] as $hours) {
            $this->travelTo($start->copy()->addHours($hours));
            $this->post(route('login.attempt'), ['email' => $member->email, 'password' => 'password'])
                ->assertRedirect(route('feed'));
            $this->post(route('logout'));
        }

        $socialUser = Mockery::mock();
        $socialUser->shouldReceive('getEmail')->once()->andReturn($member->email);
        $socialUser->shouldReceive('getId')->once()->andReturn('member-google');
        $socialUser->shouldReceive('getAvatar')->once()->andReturn(null);
        $driver = Mockery::mock();
        $driver->shouldReceive('user')->once()->andReturn($socialUser);
        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($driver);

        $this->travelTo($start->copy()->addHours(3));
        $this->get(route('social.callback', ['provider' => 'google']))->assertRedirect(route('feed'));
        $this->get($profileUrl)->assertForbidden();
        $this->post(route('logout'));

        $this->travelTo($start->copy()->addHours(4));
        $this->post(route('login.attempt'), ['email' => $member->email, 'password' => 'incorrect'])
            ->assertSessionHasErrors('email');

        $this->assertDatabaseCount('user_logins', 4);
        $this->assertDatabaseHas('user_logins', ['user_id' => $member->id, 'method' => 'google']);
        $this->actingAs($this->admin())->get($profileUrl)->assertOk()
            ->assertSee('Dernières connexions')
            ->assertSeeInOrder(['27/11/2026 à 11:07:15 UTC', '27/11/2026 à 10:07:15 UTC', '27/11/2026 à 09:07:15 UTC'])
            ->assertDontSee('27/11/2026 à 08:07:15 UTC')
            ->assertDontSee('27/11/2026 à 07:07:15 UTC')
            ->assertDontSee('27/11/2026 à 12:07:15 UTC');
    }
}
