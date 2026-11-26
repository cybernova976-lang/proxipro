<?php

namespace Tests\Feature\Admin;

use App\Models\ProSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Les chiffres de l'administration et les mises en avant du site reposent sur le
 * Premium réel (abonnement ou accès à paiement unique en cours), jamais sur
 * l'ancienne colonne users.plan (STARTER / PRO / BUSINESS).
 */
class AdminFiguresMatchRealDataTest extends TestCase
{
    use RefreshDatabase;

    private function premium(User $user, string $plan): void
    {
        ProSubscription::create(['user_id' => $user->id, 'plan' => $plan, 'amount' => 9.99, 'status' => 'active',
            'auto_renew' => $plan === 'monthly', 'starts_at' => now()->subDay(), 'ends_at' => now()->addMonth()]);
    }

    public function test_stats_count_real_premium_and_every_verified_badge(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $monthly = User::factory()->create();
        $this->premium($monthly, 'monthly');
        $pass = User::factory()->create();
        $this->premium($pass, 'pass_3m');
        // Ancien plan attribué : ce n'est pas Premium.
        User::factory()->create()->forceFill(['plan' => 'PRO', 'subscription_end' => now()->addYear()])->save();
        // Badge obtenu par le contrôle d'identité seul.
        User::factory()->create()->forceFill(['is_verified' => false, 'identity_verified' => true])->save();

        $response = $this->actingAs($admin)->get(route('admin.stats'))->assertOk()->assertSee('Répartition Premium');

        $stats = $response->viewData('generalStats');
        $this->assertSame(2, $stats['premium_users']);
        $this->assertSame(User::withVerifiedProfileBadge()->count(), $stats['verified_users']);
        $this->assertGreaterThanOrEqual(1, $stats['verified_users']);

        $breakdown = collect($response->viewData('usersByPlan'))->pluck('count', 'plan');
        $this->assertSame($stats['total_users'] - 2, $breakdown['Sans Premium']);
        $this->assertSame(1, $breakdown['Premium Mensuel']);
        $this->assertSame(1, $breakdown['Premium 3 mois']);
        $this->assertFalse($breakdown->has('PRO'));
    }

    public function test_a_legacy_plan_no_longer_puts_a_provider_forward(): void
    {
        $legacy = User::factory()->create(['name' => 'Ancien Plan', 'user_type' => 'professionnel']);
        $legacy->forceFill(['plan' => 'BUSINESS', 'subscription_end' => now()->addYear(), 'is_service_provider' => false])->save();
        $real = User::factory()->create(['name' => 'Vrai Premium', 'user_type' => 'professionnel']);
        $real->forceFill(['is_service_provider' => false])->save();
        $this->premium($real, 'monthly');

        $names = collect($this->actingAs(User::factory()->create())
            ->getJson(route('feed.professionals', ['format' => 'json']))
            ->assertOk()->json('professionals'))->pluck('name');

        $this->assertContains('Vrai Premium', $names);
        $this->assertNotContains('Ancien Plan', $names);
    }

    public function test_settings_page_only_offers_settings_that_have_an_effect(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('admin.settings'))->assertOk()
            ->assertSee('data-ad-rules', false)
            ->assertSee('30 jours pour une demande, 90 jours pour une offre')
            ->assertDontSee('name="free_ads_limit"', false)
            ->assertDontSee('name="ad_validity_days"', false)
            ->assertDontSee('name="auto_moderation"', false)
            ->assertDontSee('name="maintenance_mode"', false)
            ->assertDontSee('name="site_name"', false)
            ->assertDontSee('name="email_new_user"', false)
            ->assertDontSee('name="email_new_ad"', false)
            ->assertSee('name="email_new_message"', false);

        $this->post(route('admin.settings.general'), ['contact_email' => 'support@prokejem.test'])->assertSessionHas('success');
    }

    public function test_public_texts_match_the_rules_applied(): void
    {
        $this->get(route('legal.terms'))->assertOk()->assertSee('Dernière mise à jour des conditions : 18/11/2026');

        $this->get(route('legal.pro-terms'))->assertOk()
            ->assertSee('Version 2026-11-26')
            ->assertSee('Sans Premium actif (abonnement ou accès à paiement unique)')
            ->assertSee('« Abonnement et factures »', false)
            ->assertDontSee('« Abonnement Premium »', false);

        $this->get(route('public.provider-offers'))->assertOk()
            ->assertDontSee('nécessitent une identité professionnelle');
    }
}
