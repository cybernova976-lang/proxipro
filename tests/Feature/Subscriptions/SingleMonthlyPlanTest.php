<?php

namespace Tests\Feature\Subscriptions;

use App\Models\Setting;
use App\Models\User;
use App\Support\PlatformFeatures;
use App\Support\ProviderSubscriptionPlans;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Lot 1 de la refonte de l'offre payante (19/11/2026).
 *
 * - La formule annuelle est retirée : elle n'apportait aucun avantage réel.
 * - Les parcours d'activation lisent les formules dans la configuration : ils
 *   promettaient en dur « Support prioritaire », « Statistiques avancées »,
 *   « Export comptable », « Badge Pro Premium », qui n'existent pas.
 * - Ce que tout prestataire a sans abonnement n'est plus présenté comme payant.
 */
class SingleMonthlyPlanTest extends TestCase
{
    use RefreshDatabase;

    private const UNKEPT_PROMISES = [
        'Support prioritaire', 'Statistiques avancées', 'Export comptable', 'Pro Premium',
        'Position prioritaire', 'Devis & factures illimités', 'Devis &amp; factures illimités',
        // Chiffre jamais mesuré et avantages inexistants du bloc « profil vérifié ».
        '3x supérieur', 'clients premium',
    ];

    private const NOT_EXCLUSIVE = [
        'Gestion de clientèle et Espace Pro', 'Historique de facturation Stripe', 'Alertes dans la plateforme',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
    }

    public function test_the_catalogue_sells_one_monthly_plan_with_real_advantages_only(): void
    {
        $this->assertSame(['monthly'], array_keys(ProviderSubscriptionPlans::active()));
        $this->assertNull(ProviderSubscriptionPlans::get('annual'));

        $features = ProviderSubscriptionPlans::get('monthly')['features'];
        foreach ([...self::NOT_EXCLUSIVE, ...self::UNKEPT_PROMISES] as $text) {
            $this->assertNotContains($text, $features);
        }
        $this->assertSame(['monthly' => ['summary' => 'Mensuel (9,99€/mois)', 'total' => '9,99 €']], ProviderSubscriptionPlans::summaries());

        // Une seule formule à prélèvement, strictement inchangée : 9,99 € par mois.
        $this->assertSame(['monthly'], array_keys(ProviderSubscriptionPlans::all()));
        $monthly = ProviderSubscriptionPlans::get('monthly');
        $this->assertSame('recurring', $monthly['billing']);
        $this->assertSame(1, $monthly['months']);
        $this->assertSame(9.99, ProviderSubscriptionPlans::amount('monthly'));
        $this->assertSame(['Mensuel', '9,99€', '/mois'], [$monthly['label'], $monthly['price'], $monthly['period']]);
        $this->assertSame('Abonnement Prokejem Mensuel', ProviderSubscriptionPlans::stripeLabel('monthly'));
        // Les accès à paiement unique (25/11/2026) sont à part : jamais vendus comme abonnement.
        $this->assertSame(['monthly', 'pass_3m', 'pass_6m'], array_keys(ProviderSubscriptionPlans::catalog()));
        $this->assertSame(['pass_3m', 'pass_6m'], array_keys(ProviderSubscriptionPlans::oneTime()));
    }

    public function test_a_stored_setting_never_turns_a_one_time_access_into_a_subscription(): void
    {
        Setting::set('provider_subscription_plans', json_encode([
            'pass_3m' => ['billing' => 'recurring', 'months' => 12, 'amount' => 30, 'enabled' => true],
        ]), 'subscriptions');

        $this->assertTrue(ProviderSubscriptionPlans::isOneTime('pass_3m'));
        $this->assertSame('one_time', ProviderSubscriptionPlans::get('pass_3m')['billing']);
        $this->assertSame(3, ProviderSubscriptionPlans::months('pass_3m'));
        $this->assertSame(['monthly'], array_keys(ProviderSubscriptionPlans::active()));
        // Le prix, lui, se règle depuis l'administration.
        $this->assertSame(30.0, ProviderSubscriptionPlans::amount('pass_3m'));
    }

    public function test_stored_plans_lose_the_annual_plan_and_the_advantages_that_are_free(): void
    {
        Setting::set('provider_subscription_plans', json_encode([
            'monthly' => ['amount' => 12.34, 'enabled' => true, 'label' => 'Mon tarif', 'features' => [
                'Badge « Premium » sur votre profil public', 'Gestion de clientèle et Espace Pro',
                'Historique de facturation Stripe', 'Avantage personnalisé', 'Alertes dans la plateforme',
            ]],
            'annual' => ['amount' => 85, 'enabled' => true, 'features' => ['Tout le plan mensuel inclus']],
        ], JSON_UNESCAPED_UNICODE), 'subscriptions');

        $migration = require database_path('migrations/2026_11_19_100000_clean_stored_premium_plans.php');
        $migration->up();

        $stored = json_decode(Setting::get('provider_subscription_plans'), true);
        $this->assertSame(['monthly'], array_keys($stored));
        $this->assertSame(['Badge « Premium » sur votre profil public', 'Avantage personnalisé'], $stored['monthly']['features']);
        // Ni le prix, ni le libellé, ni l'activation ne changent.
        $this->assertSame(12.34, $stored['monthly']['amount']);
        $this->assertSame('Mon tarif', $stored['monthly']['label']);
        $this->assertTrue($stored['monthly']['enabled']);

        // Rejouée, la migration ne change plus rien.
        $before = Setting::get('provider_subscription_plans');
        $migration->up();
        $this->assertSame($before, Setting::get('provider_subscription_plans'));
    }

    public function test_the_activation_page_lists_the_plan_from_the_catalogue(): void
    {
        $this->enableSubscriptions();
        $provider = User::factory()->create(['is_service_provider' => true]);

        $html = $this->actingAs($provider)->get(route('pro.onboarding'))->assertOk()->getContent();

        $this->assertStringContainsString('data-ob-plan="monthly"', $html);
        $this->assertStringContainsString('9,99€', $html);
        $this->assertStringContainsString(e('Réduction de 20 % sur les boosts et l’option Urgent'), $html);
        foreach (['Annuel', '119,88', 'planAnnual', ...self::UNKEPT_PROMISES] as $text) {
            $this->assertStringNotContainsString($text, $html, "« {$text} » ne doit plus apparaître dans le parcours d'activation.");
        }
        // Dans la liste des avantages payants, rien de ce que tout prestataire a déjà.
        $this->assertSame(1, preg_match('#<ul class="ob-plan-features">(.*?)</ul>#s', $html, $list));
        foreach (self::NOT_EXCLUSIVE as $text) {
            $this->assertStringNotContainsString($text, $list[1]);
        }
    }

    public function test_the_social_sign_in_window_lists_the_plan_from_the_catalogue(): void
    {
        $this->enableSubscriptions();
        $this->actingAs(User::factory()->create());

        $html = view('partials.provider-oauth-modal', ['proSubscriptionsEnabled' => PlatformFeatures::proSubscriptionsEnabled()])->render();

        $this->assertStringContainsString('data-plan="monthly"', $html);
        $this->assertStringContainsString('9,99€', $html);
        // Le récapitulatif lit le libellé de la formule dans le catalogue, pas dans un texte en dur.
        $this->assertStringContainsString((string) \Illuminate\Support\Js::encode(['monthly' => 'Mensuel (9,99€/mois)']), $html);
        foreach (['Annuel', '119,88', 'data-plan="annual"', ...self::UNKEPT_PROMISES] as $text) {
            $this->assertStringNotContainsString($text, $html, "« {$text} » ne doit plus apparaître dans la fenêtre.");
        }
        $this->assertSame(1, preg_match('#<ul class="oauth-plan-features">(.*?)</ul>#s', $html, $list));
        foreach (self::NOT_EXCLUSIVE as $text) {
            $this->assertStringNotContainsString($text, $list[1]);
        }
    }

    public function test_no_view_still_sells_the_annual_plan_or_an_unkept_promise(): void
    {
        $found = [];
        foreach (File::allFiles(resource_path('views')) as $file) {
            $path = str_replace('\\', '/', $file->getRelativePathname());
            if (! str_ends_with($path, '.blade.php') || str_starts_with($path, 'admin/')) {
                continue;
            }
            $content = $file->getContents();
            foreach (['Annuel (85', '85€/an', '119,88', 'soit 7,08', "selectPlan('annual')", "selectOAuthPlan('annual')",
                'mensuelle ou annuelle', ...self::UNKEPT_PROMISES] as $text) {
                if (str_contains($content, $text)) {
                    $found[] = $path.' : '.$text;
                }
            }
        }

        $this->assertSame([], $found);
    }

    public function test_the_annual_plan_can_no_longer_be_bought_nor_granted(): void
    {
        $this->enableSubscriptions();
        $provider = User::factory()->create(['is_service_provider' => true]);

        $this->actingAs($provider)->post(route('pro.onboarding.subscribe'), ['plan' => 'annual'])
            ->assertSessionHasErrors('plan');
        $this->assertDatabaseCount('pro_subscriptions', 0);
    }

    public function test_a_one_time_access_can_never_be_bought_as_a_subscription(): void
    {
        $this->enableSubscriptions();
        $provider = User::factory()->create(['is_service_provider' => true, 'stripe_id' => 'cus_never']);

        foreach (['pass_3m', 'pass_6m'] as $plan) {
            $this->actingAs($provider)->post(route('pro.onboarding.subscribe'), ['plan' => $plan])
                ->assertSessionHasErrors('plan');
            try {
                // Même sans le contrôle du formulaire, le service refuse avant tout appel à Stripe.
                app(\App\Services\ProviderSubscriptionService::class)->createCheckoutSession($provider, $plan, 'https://marketplace.example/ok', 'https://marketplace.example/ko');
                $this->fail('Un accès à paiement unique ne doit pas ouvrir un abonnement.');
            } catch (\DomainException $exception) {
                $this->assertSame('Cet abonnement n’est pas disponible pour le moment.', $exception->getMessage());
            }
        }
        $this->assertDatabaseCount('pro_subscriptions', 0);
    }

    private function enableSubscriptions(): void
    {
        config(['services.stripe.key' => 'pk_live_configured', 'services.stripe.secret' => 'sk_live_configured',
            'services.stripe.webhook_secret' => 'whsec_configured', 'app.url' => 'https://marketplace.example']);
        foreach (['legal_entity_name' => 'Marketplace Test', 'legal_registration_number' => 'REG-123',
            'legal_address' => '1 rue du Test', 'legal_publication_director' => 'Direction Test',
            'platform_public_url' => 'https://marketplace.example'] as $key => $value) {
            Setting::set($key, $value, 'legal');
        }
        Setting::set('stripe_billing_portal_configured', '1', 'subscriptions');
        PlatformFeatures::setProSubscriptionsEnabled(true);
    }
}
