<?php

namespace Tests\Feature\Subscriptions;

use App\Models\ProSubscription;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use App\Support\PlatformFeatures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Mockery;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Stripe\ApiRequestor;
use Stripe\HttpClient\ClientInterface;
use Tests\TestCase;

/**
 * Page « Facturation » : abonnements du membre, formules disponibles,
 * changement de moyen de paiement (carte ou prélèvement), paiement en retard.
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class BillingPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Aucun appel réel à Stripe : les tests qui en ont besoin simulent ses réponses.
        config(['services.stripe.secret' => null]);
        Notification::fake();
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

    private function subscriber(array $subscription = []): array
    {
        $user = User::factory()->create(['stripe_id' => 'cus_bill', 'pro_status' => 'active']);
        $local = ProSubscription::create(array_merge(['user_id' => $user->id, 'plan' => 'monthly', 'amount' => 9.99,
            'status' => 'active', 'stripe_status' => 'active', 'stripe_subscription_id' => 'sub_bill', 'auto_renew' => true,
            'starts_at' => now()->subDays(10), 'ends_at' => now()->addDays(20), 'last_payment_at' => now()->subDays(10)], $subscription));

        return [$user, $local];
    }

    /** @return array<int, array{0: string, 1: array}> les appels faits à Stripe */
    private function withStripe(callable $respond, callable $run): array
    {
        $calls = [];
        $http = Mockery::mock(ClientInterface::class);
        $http->shouldReceive('request')->andReturnUsing(function ($method, $url, $headers, $params) use (&$calls, $respond) {
            $path = parse_url($url, PHP_URL_PATH);
            $calls[] = [strtoupper($method).' '.$path, $params ?? []];

            return [json_encode($respond(strtoupper($method), $path, $params ?? [])), 200, []];
        });
        $original = ApiRequestor::httpClient();
        ApiRequestor::setHttpClient($http);
        try {
            $run();
        } finally {
            ApiRequestor::setHttpClient($original);
        }

        return $calls;
    }

    private function primaryButtons(string $html): int
    {
        return substr_count($html, 'pk-billing-btn--primary');
    }

    public function test_guests_are_sent_to_sign_in(): void
    {
        $this->get(route('billing.index'))->assertRedirect(route('login'));
        $this->post(route('billing.payment-method'))->assertRedirect(route('login'));
    }

    public function test_without_subscription_the_page_lists_what_is_really_on_sale(): void
    {
        $user = User::factory()->create();

        // Souscription fermée : aucune formule, aucun bouton de paiement.
        $html = $this->actingAs($user)->get(route('billing.index'))->assertOk()
            ->assertSee('Facturation')->assertSee('votre compte est gratuit')
            ->assertSee('n’est pas ouvert à la souscription')
            ->assertDontSee('Choisir cette formule')->assertDontSee('Mes paiements')->getContent();
        $this->assertSame(0, $this->primaryButtons($html));

        // Souscription ouverte. Sans abonnement, la page n'interroge pas Stripe.
        $this->enableSubscriptions();
        $html = $this->get(route('billing.index'))->assertOk()
            ->assertSee('Abonnement disponible')->assertSee('Premium · Mensuel')->assertSee('9,99€ /mois')
            ->assertSee('Choisir cette formule')->assertSee(route('pro.onboarding.subscribe'), false)
            ->assertDontSee('Annuel')->assertDontSee('Résilier l’abonnement')->getContent();
        $this->assertSame(1, $this->primaryButtons($html), 'Un seul bouton plein sur la page.');
    }

    public function test_an_active_subscription_shows_its_renewal_and_its_management_actions(): void
    {
        [$user, $subscription] = $this->subscriber();
        Transaction::create(['user_id' => $user->id, 'amount' => 9.99, 'type' => 'SUBSCRIPTION', 'status' => 'completed',
            'description' => 'Paiement Premium mensuel', 'stripe_session_id' => 'in_paid']);
        ProSubscription::create(['user_id' => $user->id, 'plan' => 'monthly', 'amount' => 9.99, 'status' => 'cancelled',
            'stripe_status' => 'canceled', 'stripe_subscription_id' => 'sub_old', 'starts_at' => now()->subYear(), 'ends_at' => now()->subMonths(6)]);

        $html = $this->actingAs($user)->get(route('billing.index'))->assertOk()
            ->assertSee('Mes abonnements')->assertSee('Premium · Mensuel')->assertSee('9,99 € par mois')->assertSee('Actif')
            ->assertSee('Prochain prélèvement')->assertSee($subscription->ends_at->format('d/m/Y'))
            ->assertSee('Changer de moyen de paiement')->assertSee('Résilier l’abonnement')
            ->assertSee('Le paiement se fait par carte bancaire.')
            ->assertSee('Mes paiements')->assertSee('Paiement Premium mensuel')->assertSee('Télécharger mes factures')
            ->assertSee('Abonnements terminés')->assertSee('Résilié')
            ->assertDontSee('Choisir cette formule')->assertDontSee('Votre dernier paiement a échoué')
            ->assertDontSee('pk-pay-alert', false)->getContent();
        $this->assertSame(0, $this->primaryButtons($html), 'Rien d’urgent : aucun bouton plein.');

        // Résiliation programmée : on peut reprendre, on ne peut plus « résilier ».
        $subscription->update(['auto_renew' => false]);
        $this->get(route('billing.index'))->assertOk()->assertSee('Résiliation programmée')->assertSee('Fin de vos avantages')
            ->assertSee('Reprendre l’abonnement')->assertDontSee('Résilier l’abonnement')->assertDontSee('Prochain prélèvement');

        // Accès offert par l'administration : aucun prélèvement, aucune action de paiement.
        $subscription->update(['stripe_subscription_id' => null, 'stripe_status' => null]);
        $this->get(route('billing.index'))->assertOk()->assertSee('Accès offert')->assertSee('aucun prélèvement')
            ->assertDontSee('Changer de moyen de paiement')->assertDontSee('Résilier l’abonnement');
    }

    public function test_a_late_payment_is_announced_everywhere_with_its_deadline_and_one_main_button(): void
    {
        [$user, $subscription] = $this->subscriber(['stripe_status' => 'past_due',
            'payment_failed_at' => now()->subDays(5), 'grace_ends_at' => now()->addDays(10)]);
        $deadline = $subscription->grace_ends_at->format('d/m/Y');

        $html = $this->actingAs($user)->get(route('billing.index'))->assertOk()
            ->assertSee('Votre dernier paiement a échoué')->assertSee($deadline)->assertSee('il vous reste 10 jours')
            ->assertSee('votre compte repassera en gratuit')->assertSee('Mettre à jour mon moyen de paiement')
            ->assertSee('Paiement en retard')->assertSee('À régler avant le')->assertSee('Résilier l’abonnement')
            ->assertDontSee('Changer de moyen de paiement')->assertDontSee('pk-pay-alert', false)->getContent();
        $this->assertSame(1, $this->primaryButtons($html), 'Un seul bouton plein : régler le paiement.');

        // Sur les autres pages, un bandeau rappelle l'échéance et mène à la Facturation.
        foreach ([route('settings.index'), route('pro.subscription')] as $url) {
            $this->get($url)->assertOk()->assertSee('class="pk-pay-alert"', false)
                ->assertSee('Le paiement de votre abonnement a échoué')->assertSee($deadline)->assertSee(route('billing.index'), false);
        }
        $this->get(route('pro.subscription'))->assertSee('Votre paiement est en retard')->assertSee('Régler mon paiement');

        // Délai écoulé : plus d'avantages, plus de bandeau ; la page dit que le paiement reste à confirmer.
        $subscription->update(['payment_failed_at' => now()->subDays(16), 'grace_ends_at' => now()->subDay()]);
        $this->assertFalse($user->fresh()->hasActiveProSubscription());
        $this->get(route('settings.index'))->assertOk()->assertDontSee('pk-pay-alert', false);
        $this->get(route('billing.index'))->assertOk()->assertSee('Paiement à confirmer')->assertDontSee('Votre dernier paiement a échoué');
    }

    public function test_changing_the_payment_method_offers_direct_debit_when_stripe_has_it_and_settles_the_late_payment(): void
    {
        [$user, $subscription] = $this->subscriber(['stripe_status' => 'past_due',
            'payment_failed_at' => now()->subDays(2), 'grace_ends_at' => now()->addDays(13)]);
        config(['services.stripe.secret' => 'sk_test_simulated', 'app.url' => 'https://marketplace.example']);
        Setting::set('platform_public_url', 'https://marketplace.example', 'legal');
        $metadata = ['type' => 'subscription_payment_method', 'user_id' => (string) $user->id,
            'pro_subscription_id' => (string) $subscription->id, 'stripe_subscription_id' => 'sub_bill'];
        $remote = fn (string $status) => ['id' => 'sub_bill', 'object' => 'subscription', 'customer' => 'cus_bill', 'status' => $status,
            'cancel_at_period_end' => false, 'current_period_start' => now()->subDays(2)->timestamp, 'current_period_end' => now()->addDays(28)->timestamp,
            'metadata' => ['type' => 'pro_subscription', 'plan' => 'monthly'], 'latest_invoice' => ['id' => 'in_due', 'object' => 'invoice', 'status' => 'open'],
            'default_payment_method' => ['id' => 'pm_sepa', 'object' => 'payment_method', 'type' => 'sepa_debit', 'sepa_debit' => ['last4' => '3000']]];

        $respond = function (string $method, string $path, array $params) use ($metadata, $remote) {
            return match (true) {
                $path === '/v1/payment_method_configurations' => ['object' => 'list', 'has_more' => false, 'data' => [
                    ['id' => 'pmc_1', 'object' => 'payment_method_configuration', 'is_default' => true, 'active' => true, 'parent' => null,
                        'sepa_debit' => ['available' => true]]]],
                $method === 'POST' && $path === '/v1/checkout/sessions' => ['id' => 'cs_setup', 'object' => 'checkout.session',
                    'url' => 'https://checkout.example.test/setup'],
                $method === 'GET' && $path === '/v1/checkout/sessions/cs_setup' => ['id' => 'cs_setup', 'object' => 'checkout.session', 'mode' => 'setup',
                    'status' => 'complete', 'customer' => 'cus_bill', 'metadata' => $metadata,
                    'setup_intent' => ['id' => 'seti_1', 'object' => 'setup_intent', 'status' => 'succeeded', 'payment_method' => 'pm_sepa']],
                $path === '/v1/customers/cus_bill' => ['id' => 'cus_bill', 'object' => 'customer'],
                // Un prélèvement n'est pas encaissé sur-le-champ : la facture reste ouverte.
                $path === '/v1/invoices/in_due/pay' => ['id' => 'in_due', 'object' => 'invoice', 'status' => 'open'],
                default => $remote('past_due'),
            };
        };

        $calls = $this->withStripe($respond, function () use ($user) {
            $this->actingAs($user)->post(route('billing.payment-method'))->assertRedirect('https://checkout.example.test/setup');
            $this->get(route('billing.payment-method.return', ['session_id' => 'cs_setup']))
                ->assertRedirect(route('billing.index'))
                ->assertSessionHas('success', fn (string $message) => str_contains($message, 'prélèvement prend quelques jours ouvrés'));
            // Recharger l'adresse de retour ne refait rien chez Stripe.
            $this->get(route('billing.payment-method.return', ['session_id' => 'cs_setup']))->assertRedirect(route('billing.index'));
            $this->get(route('billing.index'))->assertOk()
                ->assertSee('Carte bancaire ou prélèvement SEPA.')
                ->assertSee('Prélèvement SEPA sur le compte se terminant par 3000');
        });

        $session = collect($calls)->firstWhere(0, 'POST /v1/checkout/sessions')[1];
        $this->assertSame('setup', $session['mode']);
        $this->assertSame(['card', 'sepa_debit'], $session['payment_method_types']);
        $this->assertSame('cus_bill', $session['customer']);
        $this->assertSame($metadata, $session['metadata']);
        $this->assertStringStartsWith('https://marketplace.example/facturation/moyen-de-paiement/retour', $session['success_url']);

        $paths = array_column($calls, 0);
        $this->assertSame(1, count(array_keys($paths, 'POST /v1/customers/cus_bill')));
        $this->assertSame(1, count(array_keys($paths, 'POST /v1/subscriptions/sub_bill')));
        $this->assertSame(1, count(array_keys($paths, 'POST /v1/invoices/in_due/pay')), 'Le paiement en retard est présenté une seule fois.');
        $update = collect($calls)->firstWhere(0, 'POST /v1/subscriptions/sub_bill')[1];
        $this->assertSame('pm_sepa', $update['default_payment_method']);
        // Le délai court toujours tant que le prélèvement n'est pas encaissé.
        $this->assertTrue($subscription->fresh()->inPaymentGrace());
        $this->assertSame('cs_setup', $subscription->fresh()->payment_method_session_id);
    }

    public function test_a_payment_method_session_cannot_be_replayed_on_another_account_and_card_stays_the_only_choice_without_direct_debit(): void
    {
        [$user, $subscription] = $this->subscriber();
        $stranger = User::factory()->create(['stripe_id' => 'cus_other']);
        config(['services.stripe.secret' => 'sk_test_simulated']);

        $calls = $this->withStripe(function (string $method, string $path) use ($user, $subscription) {
            return match (true) {
                $path === '/v1/payment_method_configurations' => ['object' => 'list', 'has_more' => false, 'data' => [
                    ['id' => 'pmc_1', 'object' => 'payment_method_configuration', 'is_default' => true, 'active' => true, 'parent' => null,
                        'sepa_debit' => ['available' => false]]]],
                $method === 'POST' => ['id' => 'cs_card', 'object' => 'checkout.session', 'url' => 'https://checkout.example.test/card'],
                default => ['id' => 'cs_card', 'object' => 'checkout.session', 'mode' => 'setup', 'status' => 'complete', 'customer' => 'cus_bill',
                    'metadata' => ['type' => 'subscription_payment_method', 'user_id' => (string) $user->id,
                        'pro_subscription_id' => (string) $subscription->id, 'stripe_subscription_id' => 'sub_bill'],
                    'setup_intent' => ['id' => 'seti_1', 'object' => 'setup_intent', 'status' => 'succeeded', 'payment_method' => 'pm_card']],
            };
        }, function () use ($user, $stranger) {
            $this->actingAs($user)->post(route('billing.payment-method'))->assertRedirect('https://checkout.example.test/card');
            $this->actingAs($stranger)->get(route('billing.payment-method.return', ['session_id' => 'cs_card']))
                ->assertRedirect(route('billing.index'))->assertSessionHas('error');
            // Sans abonnement prélevé, il n'y a rien à modifier.
            $this->post(route('billing.payment-method'))->assertRedirect(route('billing.index'))->assertSessionHas('error');
        });

        $this->assertSame(['card'], collect($calls)->firstWhere(0, 'POST /v1/checkout/sessions')[1]['payment_method_types']);
        $this->assertNotContains('POST /v1/subscriptions/sub_bill', array_column($calls, 0));
        $this->assertNull($subscription->fresh()->payment_method_session_id);
    }

    public function test_the_page_follows_the_house_rules(): void
    {
        foreach (glob(resource_path('views/billing').'/{,partials/}*.blade.php', GLOB_BRACE) as $view) {
            $source = file_get_contents($view);
            $this->assertLessThanOrEqual(200, substr_count($source, "\n") + 1, basename($view).' dépasse 200 lignes.');
            $this->assertStringNotContainsString('<style', $source, basename($view).' contient du CSS.');
            $this->assertStringNotContainsString('style="', $source, basename($view).' contient du CSS en ligne.');
            $this->assertDoesNotMatchRegularExpression('/\son[a-z]+="/', $source, basename($view).' contient du JS en ligne.');
        }

        $css = file_get_contents(public_path('css/billing.css'));
        foreach (['#3a86ff', '#e76f51'] as $banned) {
            $this->assertStringNotContainsStringIgnoringCase($banned, $css);
        }
        preg_match_all('/@media[^{]*\((?:min|max)-width:\s*(\d+)px\)/', $css, $matches);
        $this->assertNotEmpty($matches[1]);
        foreach ($matches[1] as $width) {
            $this->assertContains((int) $width, [640, 1024, 1180], "billing.css : point de rupture {$width} px.");
        }
        preg_match_all('/\.([a-z][a-z0-9_-]*)/i', preg_replace('/\/\*.*?\*\//s', '', $css), $classes);
        foreach (array_unique($classes[1]) as $class) {
            $this->assertStringStartsWith('pk-', $class, "billing.css : .{$class} n’est pas préfixée.");
        }
    }
}
