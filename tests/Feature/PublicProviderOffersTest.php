<?php

namespace Tests\Feature;

use App\Models\IdentityVerification;
use App\Models\Setting;
use App\Support\PlatformFeatures;
use App\Support\PointPackCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicProviderOffersTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_can_read_real_costs_without_creating_an_account_or_checkout(): void
    {
        config(['marketplace.commission_percent' => 7.5]);
        $initialSettings = Setting::query()->orderBy('key')->get(['key', 'value', 'group'])->toArray();

        $response = $this->get(route('public.provider-offers'))->assertOk()
            ->assertSee('Les coûts, avant de vous lancer.')
            ->assertSee('Gratuit')
            ->assertSee(number_format(IdentityVerification::getVerificationPrice('profile_verification'), 2, ',', ' ').' €')
            ->assertSee('Le contrôle d’identité se règle par carte')
            ->assertSee('Les points ne règlent pas le contrôle d’identité du profil.')
            ->assertSee('7,50 %')
            ->assertSee('Elle est déduite du montant versé au prestataire.')
            ->assertSee(route('register'), false)
            ->assertSee(route('legal.pro-terms'), false)
            ->assertDontSee('<form', false)
            ->assertDontSee('Satisfait ou remboursé');

        foreach (PointPackCatalog::all() as $pack) {
            $response->assertSee($pack['points'].' points')
                ->assertSee(number_format((float) $pack['price'], 2, ',', ' ').' €');
        }

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('pro_subscriptions', 0);
        $this->assertSame($initialSettings, Setting::query()->orderBy('key')->get(['key', 'value', 'group'])->toArray());
    }

    public function test_plans_are_not_advertised_when_commercialization_is_disabled_or_unready(): void
    {
        config(['provider_subscriptions.plans.monthly.label' => 'Plan non commercialisable']);

        $this->get(route('public.provider-offers'))->assertOk()
            ->assertSee('Les abonnements PRO ne sont pas proposés à la souscription actuellement.')
            ->assertDontSee('Plan non commercialisable');

        Setting::set('pro_subscriptions_enabled', '1', 'subscriptions');
        config(['services.stripe.key' => null]);

        $this->get(route('public.provider-offers'))->assertOk()
            ->assertDontSee('Plan non commercialisable')
            ->assertDontSee('STRIPE_KEY')
            ->assertDontSee('webhook');
    }

    public function test_only_enabled_commercializable_plans_show_the_current_admin_amount(): void
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
        Setting::set('provider_subscription_plans', json_encode([
            'monthly' => ['enabled' => true, 'label' => 'Offre mensuelle actualisée', 'amount' => 12.34, 'price' => 'ancien prix'],
            'annual' => ['enabled' => false, 'label' => 'Offre annuelle désactivée', 'amount' => 78],
        ]), 'subscriptions');
        PlatformFeatures::setProSubscriptionsEnabled(true);

        $response = $this->get(route('public.provider-offers'))->assertOk()
            ->assertSee('Offre mensuelle actualisée')
            ->assertSee('12,34 €')
            ->assertDontSee('ancien prix')
            ->assertDontSee('Offre annuelle désactivée')
            ->assertDontSee('pk_live_configured')
            ->assertDontSee('sk_live_configured')
            ->assertDontSee('whsec_configured');

        $this->assertCount(1, $response->viewData('subscriptionPlans'));
        $this->assertDatabaseCount('pro_subscriptions', 0);
    }
}
