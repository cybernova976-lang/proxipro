<?php

namespace Tests\Feature\Subscriptions;

use App\Models\Setting;
use App\Support\ProviderSubscriptionPlans;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegacySubscriptionFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_features_are_updated_without_changing_prices_or_activation_and_cache_is_refreshed(): void
    {
        $plans = [
            'monthly' => ['amount' => 12.34, 'enabled' => true, 'label' => 'Mon tarif', 'features' => [
                'Badge Premium visible', 'Priorité dans les suggestions de prestataires',
                'Gestion de clientèle et Espace Pro', 'Historique de facturation Stripe',
                "Jusqu'à 4 photos par annonce", 'Alertes dans la plateforme', 'Support prioritaire',
            ]],
            'annual' => ['amount' => 99, 'enabled' => false, 'features' => [
                'Tout le plan mensuel inclus', 'Priorité renforcée dans les résultats',
                'Mise en avant pendant 12 mois', 'Badge « Premium annuel »',
                "Jusqu'à 4 photos par annonce", 'Export comptable', 'Assistance via formulaire de contact',
            ]],
        ];
        Setting::set('provider_subscription_plans', json_encode($plans), 'subscriptions');
        $this->assertContains('Support prioritaire', ProviderSubscriptionPlans::get('monthly')['features']);
        $migration = require database_path('migrations/2026_11_09_113000_align_legacy_provider_subscription_features.php');
        $migration->up();
        foreach (['monthly', 'annual'] as $key) {
            $this->assertSame(config("provider_subscriptions.plans.{$key}.features"), ProviderSubscriptionPlans::get($key)['features']);
            $this->assertSame($plans[$key]['amount'], ProviderSubscriptionPlans::get($key)['amount']);
            $this->assertSame($plans[$key]['enabled'], ProviderSubscriptionPlans::get($key)['enabled']);
        }
        $this->assertSame('Mon tarif', ProviderSubscriptionPlans::get('monthly')['label']);
        $before = Setting::get('provider_subscription_plans');
        $migration->up();
        $this->assertSame($before, Setting::get('provider_subscription_plans'));
    }

    public function test_custom_features_are_preserved(): void
    {
        $custom = ['monthly' => ['amount' => 11, 'features' => ['Catalogue personnalisé']]];
        Setting::set('provider_subscription_plans', json_encode($custom), 'subscriptions');
        $migration = require database_path('migrations/2026_11_09_113000_align_legacy_provider_subscription_features.php');
        $migration->up();
        $this->assertSame($custom, json_decode(Setting::get('provider_subscription_plans'), true));
    }
}
