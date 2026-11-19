<?php

namespace Tests\Feature\Offers;

use App\Models\Setting;
use App\Models\User;
use App\Support\OfferComparison;
use App\Support\PlatformFeatures;
use App\Support\ProviderSubscriptionPlans;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Lot 4 : le comparatif « Gratuit / À la carte / Premium » n'affiche que des
 * prix lus dans le catalogue en vigueur, et le même contenu partout.
 */
class OfferComparisonTest extends TestCase
{
    use RefreshDatabase;

    public function test_prices_follow_the_rules_in_force(): void
    {
        $values = fn () => collect(OfferComparison::sections())->flatMap(fn ($section) => $section['rows'])
            ->flatMap(fn ($row) => [$row['label'], $row['free'], $row['unit'], $row['premium']])->all();

        // Règles d'origine : boosts de 3 à 30 jours, Urgent 7 jours, pas de remontée.
        $legacy = $values();
        $this->assertContains('De 4,00 € (3 jours) à 15,00 € (30 jours), par offre', $legacy);
        $this->assertContains('14,00 € pour 7 jours', $legacy);
        $this->assertContains('11,20 € pour 7 jours (réduction de 20 %)', $legacy);
        $this->assertStringNotContainsString('Remontée automatique', implode(' ', $legacy));
        $this->assertStringNotContainsString('liste des annonces', implode(' ', $legacy));

        // Nouvelles règles : À la une, remontée automatique, Urgent pour toute la durée.
        config(['feed.sponsored.effective_at' => now()->subDay()->format('Y-m-d')]);
        $new = $values();
        $this->assertContains('5,90 € (7 jours) ou 14,90 € (30 jours), par offre', $new);
        $this->assertContains('À partir de 2,90 €, par offre', $new);
        $this->assertContains('Toutes vos offres, chaque semaine, sans achat', $new);
        $this->assertContains('2,50 € pour toute la durée de la demande', $new);
        $this->assertContains('2,00 € pour toute la durée de la demande (réduction de 20 %)', $new);
        $this->assertStringContainsString('liste des annonces', implode(' ', $new));

        // Ce qui est gratuit n'est jamais présenté comme un avantage payant.
        foreach (OfferComparison::sections()[0]['rows'] as $row) {
            $this->assertSame([OfferComparison::INCLUDED, OfferComparison::INCLUDED, OfferComparison::INCLUDED],
                [$row['free'], $row['unit'], $row['premium']]);
        }
    }

    public function test_the_cost_argument_is_written_only_when_it_is_true_and_premium_is_on_sale(): void
    {
        // Abonnement non commercialisé : ni prix, ni calcul.
        $this->assertNull(OfferComparison::premiumPrice());
        $this->assertNull(OfferComparison::argument());

        $this->sellSubscriptions();
        $this->assertSame('9,99 € par mois', OfferComparison::premiumPrice());
        $this->assertSame('Mettre une seule offre en avant pendant 30 jours coûte 15,00 € à la carte. '
            .'Premium coûte 9,99 € par mois et met en avant toutes vos offres.', OfferComparison::argument());

        // Un abonnement plus cher que 30 jours de mise en avant : la phrase disparaît.
        Setting::set('provider_subscription_plans', json_encode(['monthly' => ['enabled' => true, 'amount' => 19.90]]), 'subscriptions');
        $this->assertSame('19,90 € par mois', OfferComparison::premiumPrice());
        $this->assertNull(OfferComparison::argument());
    }

    public function test_the_offers_page_and_the_subscription_page_show_the_same_comparison(): void
    {
        $this->sellSubscriptions();
        $partial = (string) file_get_contents(resource_path('views/partials/premium-comparison.blade.php'));
        $this->assertStringNotContainsString('<style', $partial);
        $this->assertStringNotContainsString('style="', $partial);

        $pro = User::factory()->completeForVerification()->create([
            'account_type' => 'professionnel', 'user_type' => 'professionnel', 'is_service_provider' => true,
            'identity_verified' => true, 'is_active' => true,
        ]);

        foreach ([$this->get(route('public.provider-offers')), $this->actingAs($pro)->get(route('pro.subscription'))] as $response) {
            $response->assertOk()
                ->assertSee('css/offer-comparison.css', false)
                ->assertSee('Gratuit, à la carte ou Premium')
                ->assertSee('À la carte')
                ->assertSee('9,99 € par mois')
                ->assertSee('Le calcul.')
                ->assertSee('De 4,00 € (3 jours) à 15,00 € (30 jours), par offre')
                ->assertSee('Votre logo sur vos devis et vos factures')
                ->assertSee('Rappel automatique de vos factures impayées')
                ->assertSee('ne garantit ni demande, ni mission, ni revenu');
        }
    }

    public function test_the_two_new_advantages_are_added_to_an_untouched_stored_plan_only(): void
    {
        $added = ['Votre logo sur vos devis et vos factures (comptes professionnels)',
            'Rappel automatique de vos factures impayées (comptes professionnels)'];
        $current = config('provider_subscriptions.plans.monthly.features');
        $this->assertSame($added, array_slice($current, -2));
        $previous = array_slice($current, 0, -2);
        $migration = require database_path('migrations/2026_11_19_180000_add_document_services_to_stored_subscription_features.php');

        Setting::set('provider_subscription_plans', json_encode(['monthly' => ['amount' => 12.34, 'features' => $previous]], JSON_UNESCAPED_UNICODE), 'subscriptions');
        $migration->up();
        $this->assertSame($current, ProviderSubscriptionPlans::get('monthly')['features']);
        $this->assertSame(12.34, ProviderSubscriptionPlans::get('monthly')['amount']);

        Setting::set('provider_subscription_plans', json_encode(['monthly' => ['features' => ['Catalogue personnalisé']]], JSON_UNESCAPED_UNICODE), 'subscriptions');
        $migration->up();
        $this->assertSame(['Catalogue personnalisé'], ProviderSubscriptionPlans::get('monthly')['features']);
    }

    /** Met l'abonnement réellement en vente, comme en production. */
    private function sellSubscriptions(): void
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
    }
}
