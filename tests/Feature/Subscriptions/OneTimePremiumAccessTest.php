<?php

namespace Tests\Feature\Subscriptions;

use App\Models\Ad;
use App\Models\ProSubscription;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\AccountStatusNotification;
use App\Notifications\SubscriptionInvoiceNotification;
use App\Services\ProviderSubscriptionService;
use App\Services\StripeWebhookService;
use App\Support\PlatformFeatures;
use App\Support\ProviderSubscriptionPlans;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Mockery;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Stripe\ApiRequestor;
use Stripe\HttpClient\ClientInterface;
use Tests\TestCase;

/**
 * Accès Premium à paiement unique (demande du fondateur du 25/11/2026) : « un paiement
 * unique sans abonnement pour ceux qui ne voudraient pas être prélevés tous les mois ».
 *
 * 27 € pour 3 mois, 49 € pour 6 mois (configuration, modifiable dans l'administration).
 * Un paiement Stripe Checkout en mode « payment » : aucun abonnement, aucun prélèvement
 * ensuite, l'accès s'arrête seul à sa date de fin. Aucun appel réel à Stripe.
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class OneTimePremiumAccessTest extends TestCase
{
    use RefreshDatabase;

    private const LEAD = 'Un seul paiement par carte. Aucun prélèvement ensuite : l’accès Premium s’arrête seul à la fin de la période.';

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->travelTo(Carbon::parse('2026-11-25 10:00:00'));
        $this->enableSubscriptions();
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

    private function member(array $attributes = []): User
    {
        return User::factory()->create(array_merge(['stripe_id' => 'cus_once', 'is_service_provider' => true], $attributes));
    }

    /** Simule Stripe : $respond(méthode, chemin, paramètres) rend le corps JSON, ou [corps, code]. */
    private function withStripe(callable $respond, callable $run): void
    {
        $http = Mockery::mock(ClientInterface::class);
        $http->shouldReceive('request')->andReturnUsing(function ($method, $url, $headers, $params) use ($respond) {
            $answer = $respond(strtoupper($method), parse_url($url, PHP_URL_PATH), $params ?? [], $headers);

            return isset($answer['__status']) ? [json_encode($answer['body']), $answer['__status'], []] : [json_encode($answer), 200, []];
        });
        $original = ApiRequestor::httpClient();
        ApiRequestor::setHttpClient($http);
        try {
            $run();
        } finally {
            ApiRequestor::setHttpClient($original);
        }
    }

    /** Aucun appel à Stripe n'est permis pendant $run (relevés, puis vérifiés : un appel raté peut être rattrapé par le code). */
    private function withoutStripe(callable $run): void
    {
        $calls = [];
        $this->withStripe(function ($method, $path) use (&$calls) {
            $calls[] = $method.' '.$path;

            return ['__status' => 500, 'body' => ['error' => ['type' => 'api_error', 'message' => 'Appel inattendu']]];
        }, $run);
        $this->assertSame([], $calls, 'Appel à Stripe inattendu.');
    }

    private function emptyList(): array
    {
        return ['object' => 'list', 'has_more' => false, 'data' => []];
    }

    private function paidSession(User $user, array $overrides = [], array $metadata = []): array
    {
        return array_merge([
            'id' => 'cs_paid_1', 'object' => 'checkout.session', 'mode' => 'payment', 'status' => 'complete',
            'payment_status' => 'paid', 'amount_total' => 2700, 'currency' => 'eur',
            'client_reference_id' => (string) $user->id, 'customer' => 'cus_once', 'payment_intent' => 'pi_once',
            'invoice' => null,
            'metadata' => array_merge([
                'user_id' => (string) $user->id, 'type' => ProviderSubscriptionService::ONE_TIME_CHECKOUT_TYPE,
                'plan' => 'pass_3m', 'months' => '3', 'expected_amount_cents' => '2700', 'expected_currency' => 'eur',
                'purchase_ref' => 'ref-once-1',
            ], $metadata),
        ], $overrides);
    }

    private function webhook(array $session, string $eventId = 'evt_once_1'): string
    {
        return app(StripeWebhookService::class)->process(json_decode(json_encode([
            'id' => $eventId, 'type' => 'checkout.session.completed', 'data' => ['object' => $session],
        ])));
    }

    /** Retour du membre après paiement : Stripe rend la session demandée. */
    private function returnFrom(User $user, array $session)
    {
        $response = null;
        $this->withStripe(function ($method, $path) use ($session) {
            $this->assertSame('GET', $method);
            $this->assertSame('/v1/checkout/sessions/'.$session['id'], $path);

            return $session;
        }, function () use ($user, $session, &$response) {
            $response = $this->actingAs($user)->get(route('billing.one-time.return', ['session_id' => $session['id']]));
        });

        return $response;
    }

    private function access(User $user, array $attributes = []): ProSubscription
    {
        return ProSubscription::create(array_merge(['user_id' => $user->id, 'plan' => 'pass_3m', 'amount' => 27,
            'status' => 'active', 'auto_renew' => false, 'starts_at' => now()->subMonths(2), 'ends_at' => now()->addDays(20),
            'stripe_checkout_session_id' => 'cs_previous', 'last_payment_at' => now()->subMonths(2)], $attributes));
    }

    private function assertStatusSent(User $user, string $event, int $times = 1): void
    {
        $sent = Notification::sent($user, AccountStatusNotification::class)
            ->filter(fn (AccountStatusNotification $notification) => $notification->toArray($user)['type'] === 'account_'.$event);
        $this->assertCount($times, $sent, "Notification « {$event} » envoyée ".$sent->count().' fois.');
    }

    public function test_the_catalogue_sells_two_one_time_accesses_beside_the_unchanged_monthly_plan(): void
    {
        $this->assertSame(['monthly'], array_keys(ProviderSubscriptionPlans::active()));
        $this->assertSame(['pass_3m', 'pass_6m'], array_keys(ProviderSubscriptionPlans::activeOneTime()));
        $this->assertTrue(ProviderSubscriptionPlans::isOneTime('pass_3m'));
        $this->assertFalse(ProviderSubscriptionPlans::isOneTime('monthly'));
        $this->assertSame(27.0, ProviderSubscriptionPlans::amount('pass_3m'));
        $this->assertSame(49.0, ProviderSubscriptionPlans::amount('pass_6m'));
        $this->assertSame(6, ProviderSubscriptionPlans::months('pass_6m'));
        $this->assertSame('27,00 € pour 3 mois ou 49,00 € pour 6 mois', ProviderSubscriptionPlans::oneTimeSentence());
        // Mêmes avantages que la formule mensuelle.
        $this->assertSame(ProviderSubscriptionPlans::get('monthly')['features'], ProviderSubscriptionPlans::get('pass_3m')['features']);
        $this->assertSame('Paiement unique 3 mois (27,00 €)', (new ProSubscription(['plan' => 'pass_3m', 'amount' => 27]))->getPlanLabel());
    }

    public function test_the_payment_page_is_a_one_time_card_payment_with_complete_metadata_and_is_reused(): void
    {
        $user = $this->member();
        $open = [];
        $created = [];

        $this->withStripe(function ($method, $path, $params) use (&$open, &$created, $user) {
            if ($method === 'GET') {
                $this->assertSame('cus_once', $params['customer']);

                return $path === '/v1/checkout/sessions'
                    ? ['object' => 'list', 'has_more' => false, 'data' => array_values($open)]
                    : $this->emptyList();
            }
            $this->assertSame('/v1/checkout/sessions', $path);
            $this->assertSame('payment', $params['mode']);
            $this->assertSame(['card'], $params['payment_method_types']);
            $this->assertSame(2700, $params['line_items'][0]['price_data']['unit_amount']);
            $this->assertSame('eur', $params['line_items'][0]['price_data']['currency']);
            $this->assertArrayNotHasKey('recurring', $params['line_items'][0]['price_data']);
            $this->assertArrayNotHasKey('subscription_data', $params);
            $this->assertSame((string) $user->id, $params['client_reference_id']);
            $metadata = $params['metadata'];
            $this->assertSame(ProviderSubscriptionService::ONE_TIME_CHECKOUT_TYPE, $metadata['type']);
            $this->assertSame((string) $user->id, $metadata['user_id']);
            $this->assertSame('pass_3m', $metadata['plan']);
            $this->assertSame('3', $metadata['months']);
            $this->assertSame('2700', $metadata['expected_amount_cents']);
            $this->assertSame('eur', $metadata['expected_currency']);
            $this->assertNotEmpty($metadata['purchase_ref']);
            $this->assertSame('2026-11-25T10:00:00+00:00', $metadata['immediate_start_requested_at']);
            $this->assertSame($metadata, $params['payment_intent_data']['metadata']);
            $this->assertStringStartsWith('https://marketplace.example/facturation/acces-unique/retour?session_id=', $params['success_url']);
            $this->assertStringContainsString('{CHECKOUT_SESSION_ID}', $params['success_url']);
            $created[] = 'cs_open_1';
            $open['cs_open_1'] = ['id' => 'cs_open_1', 'object' => 'checkout.session', 'mode' => 'payment', 'status' => 'open',
                'url' => 'https://checkout.example.test/once', 'metadata' => $metadata];

            return $open['cs_open_1'];
        }, function () use ($user) {
            $this->actingAs($user)->post(route('billing.one-time'), ['plan' => 'pass_3m', 'immediate_start' => '1'])
                ->assertRedirect('https://checkout.example.test/once');
            // Deuxième clic : la même page de paiement, aucune nouvelle session.
            $this->post(route('billing.one-time'), ['plan' => 'pass_3m', 'immediate_start' => '1'])
                ->assertRedirect('https://checkout.example.test/once');
        });

        $this->assertSame(['cs_open_1'], $created);
        // Rien n'est ouvert avant le paiement.
        $this->assertDatabaseCount('pro_subscriptions', 0);
        $this->assertFalse($user->fresh()->isPremium());
    }

    public function test_a_customer_unknown_to_the_live_keys_is_replaced_once(): void
    {
        $user = $this->member(['stripe_id' => 'cus_from_test_keys']);
        $customers = 0;

        $this->withStripe(function ($method, $path, $params) use (&$customers) {
            if ($method === 'GET') {
                if (($params['customer'] ?? null) === 'cus_from_test_keys') {
                    return ['__status' => 404, 'body' => ['error' => ['type' => 'invalid_request_error', 'code' => 'resource_missing',
                        'param' => 'customer', 'message' => "No such customer: 'cus_from_test_keys'"]]];
                }

                return $this->emptyList();
            }
            if ($path === '/v1/customers') {
                $customers++;

                return ['id' => 'cus_live_new', 'object' => 'customer'];
            }
            $this->assertSame('cus_live_new', $params['customer']);

            return ['id' => 'cs_new', 'object' => 'checkout.session', 'url' => 'https://checkout.example.test/new'];
        }, function () use ($user) {
            $this->actingAs($user)->post(route('billing.one-time'), ['plan' => 'pass_6m', 'immediate_start' => '1'])
                ->assertRedirect('https://checkout.example.test/new');
        });

        $this->assertSame(1, $customers);
        $this->assertSame('cus_live_new', $user->fresh()->stripe_id);
    }

    public function test_the_return_opens_three_months_once_and_the_webhook_does_not_double_it(): void
    {
        $user = $this->member(['pro_status' => 'inactive']);
        $session = $this->paidSession($user, ['invoice' => ['id' => 'in_once', 'object' => 'invoice', 'status' => 'paid',
            'number' => 'PKJ-0100', 'invoice_pdf' => 'https://pay.stripe.com/invoice/acct_1/in_once/pdf', 'customer' => 'cus_once']]);

        $this->returnFrom($user, $session)
            ->assertRedirect(route('billing.index'))
            ->assertSessionHas('success', 'Premium est actif jusqu’au 25/02/2027. Aucun prélèvement ne suivra.');

        $access = ProSubscription::sole();
        $this->assertSame('pass_3m', $access->plan);
        $this->assertSame('active', $access->status);
        $this->assertFalse($access->auto_renew);
        $this->assertNull($access->stripe_subscription_id);
        $this->assertSame('cs_paid_1', $access->stripe_checkout_session_id);
        $this->assertTrue($access->starts_at->equalTo(now()));
        $this->assertTrue($access->ends_at->equalTo(now()->addMonthsNoOverflow(3)));
        $this->assertSame('27.00', $access->amount);
        $this->assertTrue($user->fresh()->isPremium());
        $this->assertSame('active', $user->fresh()->pro_status);

        // Le paiement rejoint « Mes paiements », avec son justificatif Stripe.
        $payment = Transaction::sole();
        $this->assertSame('SUBSCRIPTION', $payment->type);
        $this->assertSame('cs_paid_1', $payment->stripe_session_id);
        $this->assertSame('in_once', $payment->metadata['stripe_invoice_id']);
        $this->assertSame('PKJ-0100', $payment->metadata['invoice_number']);

        // L'événement Stripe arrive ensuite, puis est rejoué : rien ne change.
        $this->withoutStripe(function () use ($session) {
            $this->assertSame('processed', $this->webhook(array_merge($session, ['invoice' => 'in_once']), 'evt_once_1'));
            $this->assertSame('duplicate', $this->webhook(array_merge($session, ['invoice' => 'in_once']), 'evt_once_1'));
            $this->assertSame('processed', $this->webhook(array_merge($session, ['invoice' => 'in_once']), 'evt_once_2'));
        });

        $this->assertDatabaseCount('pro_subscriptions', 1);
        $this->assertDatabaseCount('transactions', 1);
        $this->assertTrue(ProSubscription::sole()->ends_at->equalTo(now()->addMonthsNoOverflow(3)));
        $this->assertStatusSent($user, 'premium_pass_activated', 1);
        Notification::assertSentToTimes($user, SubscriptionInvoiceNotification::class, 1);
    }

    public function test_the_webhook_opens_the_access_and_a_later_return_finds_it(): void
    {
        $user = $this->member();
        $session = $this->paidSession($user, ['id' => 'cs_paid_6', 'amount_total' => 4900],
            ['plan' => 'pass_6m', 'months' => '6', 'expected_amount_cents' => '4900']);

        // Une session pas encore payée n'ouvre rien.
        $this->assertSame('ignored', $this->webhook(array_merge($session, ['payment_status' => 'unpaid']), 'evt_unpaid'));
        $this->assertDatabaseCount('pro_subscriptions', 0);

        $this->withoutStripe(fn () => $this->assertSame('processed', $this->webhook($session, 'evt_paid_6')));
        $access = ProSubscription::sole();
        $this->assertSame('pass_6m', $access->plan);
        $this->assertTrue($access->ends_at->equalTo(now()->addMonthsNoOverflow(6)));
        $this->assertTrue($user->fresh()->isPremium());

        $this->returnFrom($user, $session)->assertRedirect(route('billing.index'))
            ->assertSessionHas('success', 'Premium est actif jusqu’au 25/05/2027. Aucun prélèvement ne suivra.');

        $this->assertDatabaseCount('pro_subscriptions', 1);
        $this->assertDatabaseCount('transactions', 1);
        $this->assertStatusSent($user, 'premium_pass_activated', 1);
    }

    public function test_a_new_purchase_during_an_access_extends_it_from_its_end(): void
    {
        $user = $this->member(['pro_status' => 'active']);
        $current = $this->access($user, ['ends_at' => now()->addDays(20)]);

        $session = $this->paidSession($user, ['id' => 'cs_paid_more', 'amount_total' => 4900],
            ['plan' => 'pass_6m', 'months' => '6', 'expected_amount_cents' => '4900']);
        $end = $current->ends_at->copy()->addMonthsNoOverflow(6);

        $this->returnFrom($user, $session)->assertRedirect(route('billing.index'))
            ->assertSessionHas('success', 'Premium est actif jusqu’au '.$end->format('d/m/Y').'. Aucun prélèvement ne suivra.');

        $next = ProSubscription::where('stripe_checkout_session_id', 'cs_paid_more')->sole();
        $this->assertTrue($next->starts_at->equalTo($current->ends_at));
        $this->assertTrue($next->ends_at->equalTo($end));
        $this->assertTrue($current->fresh()->ends_at->equalTo(now()->addDays(20)));

        // La page Facturation montre les deux périodes, sans résiliation ni moyen de paiement.
        $this->withoutStripe(function () use ($user, $current, $end) {
            $this->actingAs($user)->get(route('billing.index'))->assertOk()
                ->assertSee('Jusqu’au '.$current->ends_at->format('d/m/Y').' — aucun renouvellement')
                ->assertSee('Du '.$current->ends_at->format('d/m/Y').' au '.$end->format('d/m/Y').' — aucun renouvellement')
                ->assertSee('À venir')
                ->assertSee('Prolonger sans abonnement : paiement unique')
                ->assertDontSee('Résilier l’abonnement')
                ->assertDontSee('Changer de moyen de paiement');
        });
    }

    public function test_a_running_or_billing_monthly_subscription_refuses_a_one_time_access(): void
    {
        foreach (['active' => now()->addDays(20), 'past_due' => now()->subDay()] as $stripeStatus => $endsAt) {
            $user = $this->member(['stripe_id' => 'cus_monthly_'.$stripeStatus]);
            ProSubscription::create(['user_id' => $user->id, 'plan' => 'monthly', 'amount' => 9.99,
                'status' => $stripeStatus === 'active' ? 'active' : 'pending', 'stripe_status' => $stripeStatus,
                'stripe_subscription_id' => 'sub_'.$stripeStatus, 'auto_renew' => true,
                'starts_at' => now()->subDays(10), 'ends_at' => $endsAt]);

            $this->withoutStripe(function () use ($user) {
                $this->actingAs($user)->post(route('billing.one-time'), ['plan' => 'pass_3m', 'immediate_start' => '1'])
                    ->assertRedirect(route('billing.index'))
                    ->assertSessionHas('error', fn (string $message) => str_contains($message, 'abonnement Premium en cours'));
            });
            // Rien n'est proposé à ce membre (la page lit seulement le moyen de paiement de son abonnement).
            $this->withStripe(fn () => $this->emptyList(), function () {
                $this->get(route('billing.index'))->assertOk()->assertDontSee('Sans abonnement : paiement unique');
            });
            $this->assertSame(0, ProSubscription::where('user_id', $user->id)->where('plan', 'like', 'pass_%')->count());
        }
    }

    public function test_the_monthly_subscription_is_refused_during_a_one_time_access(): void
    {
        $user = $this->member(['pro_status' => 'active']);
        $this->access($user);

        $this->withoutStripe(function () use ($user) {
            $this->actingAs($user)->post(route('pro.onboarding.subscribe'), ['plan' => 'monthly'])
                ->assertRedirect(route('pro.subscription'))->assertSessionHas('error');
            // Un accès à paiement unique ne se vend jamais en abonnement.
            $this->post(route('pro.onboarding.subscribe'), ['plan' => 'pass_3m'])->assertSessionHasErrors('plan');
        });
        $this->assertDatabaseCount('pro_subscriptions', 1);
    }

    public function test_another_members_session_a_forged_amount_or_currency_or_an_unpaid_session_open_nothing(): void
    {
        $user = $this->member();
        $other = $this->member(['stripe_id' => 'cus_other']);

        $cases = [
            'autre membre' => $this->paidSession($other, ['id' => 'cs_other']),
            'montant' => $this->paidSession($user, ['id' => 'cs_cheap', 'amount_total' => 100]),
            'devise' => $this->paidSession($user, ['id' => 'cs_usd', 'currency' => 'usd']),
            'impayé' => $this->paidSession($user, ['id' => 'cs_unpaid', 'payment_status' => 'unpaid']),
            'abonnement' => $this->paidSession($user, ['id' => 'cs_sub', 'mode' => 'subscription']),
        ];
        foreach ($cases as $label => $session) {
            $this->returnFrom($user, $session)->assertRedirect(route('billing.index'))->assertSessionHas('error');
            $this->assertDatabaseCount('pro_subscriptions', 0);
        }

        // Par webhook aussi, un montant qui ne correspond pas est refusé.
        $this->expectException(\DomainException::class);
        try {
            $this->webhook($cases['montant'], 'evt_cheap');
        } finally {
            $this->assertDatabaseCount('pro_subscriptions', 0);
            $this->assertFalse($user->fresh()->isPremium());
        }
    }

    public function test_the_access_ends_by_itself_and_an_extension_keeps_premium_running(): void
    {
        $user = $this->member(['pro_status' => 'active', 'pro_subscription_plan' => 'pass_3m']);
        $first = $this->access($user, ['ends_at' => now()->addDays(2)]);
        $second = $this->access($user, ['plan' => 'pass_6m', 'amount' => 49, 'stripe_checkout_session_id' => 'cs_second',
            'starts_at' => $first->ends_at, 'ends_at' => $first->ends_at->copy()->addMonthsNoOverflow(6)]);

        $this->travelTo(now()->addDays(3));
        $this->artisan('subscriptions:expire-pro')->assertSuccessful();
        $this->assertSame('expired', $first->fresh()->status);
        $this->assertSame('active', $second->fresh()->status);
        $this->assertTrue($user->fresh()->isPremium());
        $this->assertStatusSent($user, 'premium_expired', 0);

        $this->travelTo($second->ends_at->copy()->addMinute());
        $this->artisan('subscriptions:expire-pro')->assertSuccessful();
        $this->assertSame('expired', $second->fresh()->status);
        $this->assertFalse($user->fresh()->isPremium());
        $this->assertSame('inactive', $user->fresh()->pro_status);
        $this->assertNull($user->fresh()->pro_subscription_plan);
        $this->assertStatusSent($user, 'premium_expired', 1);
    }

    public function test_one_reminder_is_sent_a_few_days_before_the_end_by_notification_and_email(): void
    {
        $user = $this->member(['email_notifications' => true]);
        $ending = $this->access($user, ['ends_at' => now()->addDays(5)]);
        $quiet = $this->member(['stripe_id' => 'cus_quiet', 'email_notifications' => false]);
        $this->access($quiet, ['ends_at' => now()->addDays(4), 'stripe_checkout_session_id' => 'cs_quiet']);
        $later = $this->member(['stripe_id' => 'cus_later']);
        $this->access($later, ['ends_at' => now()->addDays(20), 'stripe_checkout_session_id' => 'cs_later']);
        $extended = $this->member(['stripe_id' => 'cus_extended']);
        $soon = $this->access($extended, ['ends_at' => now()->addDays(3), 'stripe_checkout_session_id' => 'cs_ext_1']);
        $this->access($extended, ['plan' => 'pass_6m', 'stripe_checkout_session_id' => 'cs_ext_2',
            'starts_at' => $soon->ends_at, 'ends_at' => $soon->ends_at->copy()->addMonthsNoOverflow(6)]);

        $this->artisan('subscriptions:remind-one-time-ending')->assertSuccessful();
        $this->artisan('subscriptions:remind-one-time-ending')->assertSuccessful();

        $this->assertStatusSent($user, 'premium_pass_ending', 1);
        $this->assertNotNull($ending->fresh()->expiry_reminder_sent_at);
        Notification::assertSentTo($user, AccountStatusNotification::class, function (AccountStatusNotification $notification, array $channels) use ($user) {
            $mail = $notification->toMail($user);

            return in_array('mail', $channels, true) && in_array('database', $channels, true)
                && $mail->actionText === 'Reprendre un accès'
                && str_contains($notification->toArray($user)['message'], 'se termine le 30/11/2026');
        });
        // Sans e-mails de notification : le rappel reste dans les notifications du site.
        $this->assertStatusSent($quiet, 'premium_pass_ending', 1);
        Notification::assertSentTo($quiet, AccountStatusNotification::class,
            fn ($notification, array $channels) => ! in_array('mail', $channels, true) && in_array('database', $channels, true));
        // Trop tôt, ou déjà prolongé : aucun rappel.
        $this->assertStatusSent($later, 'premium_pass_ending', 0);
        $this->assertStatusSent($extended, 'premium_pass_ending', 0);

        $this->artisan('schedule:list')->expectsOutputToContain('subscriptions:remind-one-time-ending')->assertSuccessful();
    }

    public function test_the_pages_offer_the_one_time_access_under_the_monthly_plan(): void
    {
        $user = $this->member();

        $this->withoutStripe(function () use ($user) {
            foreach ([route('billing.index') => 'pk-billing-btn--primary', route('pro.subscription') => 'pk-sub-btn--primary'] as $url => $primary) {
                $html = $this->actingAs($user)->get($url)->assertOk()
                    ->assertSee('Sans abonnement : paiement unique')
                    ->assertSee(self::LEAD)
                    ->assertSee('27,00 €')->assertSee('soit 9,00 € par mois')
                    ->assertSee('49,00 €')->assertSee('soit 8,17 € par mois')
                    ->assertSee('Prendre 3 mois')->assertSee('Prendre 6 mois')
                    ->getContent();
                // Un seul bouton plein, celui de la formule mensuelle, placé avant le paiement unique.
                $this->assertSame(1, substr_count($html, $primary), $url);
                $this->assertSame(2, substr_count($html, 'class="pk-once__btn"'), $url);
                $this->assertLessThan(strpos($html, 'Sans abonnement : paiement unique'), strpos($html, $primary), $url);
            }
        });
    }

    public function test_the_pages_show_a_running_access_without_renewal_or_cancellation(): void
    {
        $user = $this->member();
        $this->withoutStripe(fn () => $this->webhook($this->paidSession($user)));

        $this->withoutStripe(function () use ($user) {
            $this->actingAs($user)->get(route('billing.index'))->assertOk()
                ->assertSee('Premium · paiement unique 3 mois')
                ->assertSee('Jusqu’au 25/02/2027 — aucun renouvellement')
                ->assertSee('Aucun prélèvement ne suivra')
                ->assertDontSee('Résilier l’abonnement')->assertDontSee('Changer de moyen de paiement')
                // Le paiement figure dans « Mes paiements », avec son justificatif.
                ->assertSee('Mes paiements')->assertSee('Paiement unique Premium 3 mois')->assertSee('Télécharger')
                ->assertSee('Prolonger sans abonnement : paiement unique')
                ->assertSee('La nouvelle période commencera le 25/02/2027');

            $this->get(route('pro.subscription'))->assertOk()
                ->assertSee('Votre accès Premium est actif')
                ->assertSee('Paiement unique 3 mois (27,00 €)')
                ->assertSee('Jusqu’au 25/02/2027 — aucun renouvellement')
                ->assertSee('Voir mes paiements et mes factures')
                ->assertSee('Aucun renouvellement : l’accès s’arrête seul le 25/02/2027.')
                ->assertSee('Le badge Premium indique un accès Premium en cours')
                ->assertDontSee('Vous pouvez résilier à tout moment')
                ->assertDontSee('Le badge Premium indique un abonnement')
                ->assertDontSee('Gérer mon abonnement')
                ->assertDontSee('Résilier');

            $this->get(route('service-provider.mes-services'))->assertOk()
                ->assertSee('Premium, paiement unique 3 mois')
                ->assertSee('Premium actif · paiement unique 3 mois')
                ->assertDontSee('Abonnement Mensuel');

            // Les actions propres à l'abonnement ne s'appliquent pas à cet accès.
            $this->post(route('pro.subscription.cancel-renewal'))->assertSessionHas('error');
        });
        $this->assertSame('active', ProSubscription::sole()->status);

        // Paiement abandonné : rien n'est débité, rien n'est ouvert.
        $this->get(route('billing.one-time.return', ['from' => 'subscription']))
            ->assertRedirect(route('pro.subscription'))->assertSessionHas('info');
    }

    public function test_a_member_who_extended_the_access_is_still_shown_as_premium(): void
    {
        $user = $this->member(['pro_status' => 'auto-entrepreneur']);
        $running = $this->access($user);
        $ahead = $this->access($user, ['plan' => 'pass_6m', 'amount' => 49, 'starts_at' => $running->ends_at,
            'ends_at' => $running->ends_at->copy()->addMonthsNoOverflow(6), 'stripe_checkout_session_id' => 'cs_ahead']);

        // L'accès déjà payé (id plus grand, commence plus tard) ne masque pas l'accès en cours.
        $this->assertSame($running->id, $user->fresh()->proSubscription?->id);
        $this->assertSame($running->id, $user->fresh()->subscriptionForManagement()?->id);

        $this->withoutStripe(function () use ($user, $ahead) {
            $this->actingAs($user)->get(route('pro.subscription'))->assertOk()
                ->assertSee('Votre accès Premium est actif')
                ->assertSee('Aucun renouvellement : l’accès s’arrête seul le '.$ahead->ends_at->format('d/m/Y').'.')
                ->assertDontSee('Vous pouvez résilier à tout moment');
        });

        // La commande planifiée ne réécrit pas le statut juridique choisi par le membre.
        $this->artisan('subscriptions:expire-pro')->assertSuccessful();
        $this->assertSame('auto-entrepreneur', $user->fresh()->pro_status);

        // La règle de rétractation reste publiée même quand les accès ne sont plus en vente.
        $this->get(route('legal.pro-terms'))->assertOk()->assertSee('id="retractation-acces-unique"', false);
    }

    public function test_a_disabled_access_is_neither_offered_nor_sold_and_the_admin_keeps_its_nature(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $monthlyBefore = ProviderSubscriptionPlans::get('monthly');
        $monthly = ProviderSubscriptionPlans::all()['monthly'];

        $this->actingAs($admin)->put(route('admin.subscriptions.provider-plans.update'), ['plans' => [
            'monthly' => ['enabled' => '1', 'label' => $monthly['label'], 'price' => $monthly['price'], 'amount' => $monthly['amount'],
                'period' => $monthly['period'], 'original_price' => '', 'badge' => '', 'subtitle' => '',
                'description' => $monthly['description'], 'features' => implode("\n", $monthly['features'])],
            'pass_3m' => ['enabled' => '1', 'amount' => '29', 'description' => 'Trois mois de Premium, payés une fois.',
                // Champs absents du formulaire : jamais pris en compte.
                'billing' => 'recurring', 'months' => '12', 'label' => 'Faux'],
            'pass_6m' => ['amount' => '49', 'description' => 'Six mois de Premium.'],
        ]])->assertRedirect()->assertSessionHas('success');

        $this->assertSame($monthlyBefore, ProviderSubscriptionPlans::get('monthly'));
        $pass = ProviderSubscriptionPlans::get('pass_3m');
        $this->assertSame('one_time', $pass['billing']);
        $this->assertSame(3, $pass['months']);
        $this->assertSame('3 mois', $pass['label']);
        $this->assertSame(29.0, ProviderSubscriptionPlans::amount('pass_3m'));
        $this->assertSame('one_time', ProviderSubscriptionPlans::get('pass_6m')['billing']);
        $this->assertFalse(ProviderSubscriptionPlans::get('pass_6m')['enabled']);
        $this->assertSame(['pass_3m'], array_keys(ProviderSubscriptionPlans::activeOneTime()));
        $this->assertSame(['monthly'], array_keys(ProviderSubscriptionPlans::active()));

        $user = $this->member();
        $this->withoutStripe(function () use ($user) {
            $this->actingAs($user)->get(route('billing.index'))->assertOk()
                ->assertSee('29,00 €')->assertSee('soit 9,67 € par mois')
                ->assertDontSee('Prendre 6 mois')->assertDontSee('49,00 €');
            $this->post(route('billing.one-time'), ['plan' => 'pass_6m', 'immediate_start' => '1'])
                ->assertRedirect(route('billing.index'))->assertSessionHas('error');
            $this->get(route('legal.pro-terms'))->assertOk()->assertSee('29,00 € pour 3 mois')->assertDontSee('pour 6 mois');
        });
        $this->assertDatabaseCount('pro_subscriptions', 0);
    }

    public function test_the_admin_sees_a_one_time_access_as_such_without_stripe_actions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = $this->member(['name' => 'Membre Unique']);
        $this->access($user);

        $this->withoutStripe(function () use ($admin) {
            $this->actingAs($admin)->get(route('admin.subscriptions'))->assertOk()
                ->assertSee('Paiement unique')->assertSee('Carte, sans prélèvement')
                ->assertSee('Accès à paiement unique (sans abonnement)')
                ->assertDontSee('Résilier à l’échéance');
        });
    }

    /** Défaut 1 : un paiement unique réglé chez Stripe mais pas encore enregistré bloque l'abonnement mensuel. */
    public function test_a_paid_one_time_access_not_yet_recorded_refuses_the_monthly_subscription(): void
    {
        $user = $this->member(['pro_status' => 'inactive']);
        $calls = [];

        $this->withStripe(function ($method, $path, $params) use (&$calls, $user) {
            $calls[] = $method.' '.$path;
            $this->assertSame('GET', $method);
            $this->assertSame('/v1/checkout/sessions', $path);
            $this->assertSame('cus_once', $params['customer']);
            $this->assertSame('complete', $params['status']);
            $this->assertSame(now()->subDay()->getTimestamp(), (int) $params['created']['gte']);

            return ['object' => 'list', 'has_more' => false, 'data' => [
                // Abonnement d'un autre parcours et paiement d'un autre membre : ignorés.
                $this->paidSession($user, ['id' => 'cs_sub_done', 'mode' => 'subscription'], ['type' => 'pro_subscription']),
                $this->paidSession($user, ['id' => 'cs_late_once']),
            ]];
        }, function () use ($user) {
            $this->actingAs($user)->post(route('pro.onboarding.subscribe'), ['plan' => 'monthly'])
                ->assertRedirect(route('pro.subscription'))
                ->assertSessionHas('error', fn (string $message) => str_contains($message, 'accès Premium à paiement unique est en cours jusqu’au 25/02/2027'));
        });

        // Une seule lecture chez Stripe, aucune page d'abonnement créée.
        $this->assertSame(['GET /v1/checkout/sessions'], $calls);
        // Le paiement retrouvé est enregistré : l'accès est ouvert, une seule fois.
        $access = ProSubscription::sole();
        $this->assertSame('cs_late_once', $access->stripe_checkout_session_id);
        $this->assertTrue($access->ends_at->equalTo(now()->addMonthsNoOverflow(3)));
        $this->assertTrue($user->fresh()->isPremium());
        $this->assertSame(1, Transaction::where('stripe_session_id', 'cs_late_once')->count());
        $this->assertStatusSent($user, 'premium_pass_activated', 1);

        // L'événement Stripe arrive ensuite : rien n'est doublé.
        $this->withoutStripe(fn () => $this->webhook($this->paidSession($user, ['id' => 'cs_late_once']), 'evt_late'));
        $this->assertDatabaseCount('pro_subscriptions', 1);
    }

    /** Défaut 1 (filet) : un paiement unique enregistré pendant un abonnement prélevé est signalé. */
    public function test_a_one_time_payment_recorded_during_a_billed_subscription_is_logged(): void
    {
        Log::spy();
        $user = $this->member();
        ProSubscription::create(['user_id' => $user->id, 'plan' => 'monthly', 'amount' => 9.99, 'status' => 'active',
            'stripe_status' => 'active', 'stripe_subscription_id' => 'sub_running', 'auto_renew' => true,
            'starts_at' => now()->subDays(10), 'ends_at' => now()->addDays(20)]);

        $this->withoutStripe(fn () => $this->assertSame('processed', $this->webhook($this->paidSession($user, ['id' => 'cs_double']), 'evt_double')));

        $this->assertSame(1, ProSubscription::where('stripe_checkout_session_id', 'cs_double')->count());
        Log::shouldHaveReceived('warning')->withArgs(fn ($message, $context = []) => str_contains($message, 'double paiement')
            && ($context['user_id'] ?? null) === $user->id && ($context['stripe_checkout_session_id'] ?? null) === 'cs_double')->once();
    }

    /** Défaut 2 : un accès prolongé couvre les packs de mise en avant jusqu'au bout de la chaîne. */
    public function test_the_boost_page_counts_the_paid_ahead_access_after_the_running_one(): void
    {
        Http::fake();
        config(['feed.sponsored.effective_at' => now()->subDay()->format('Y-m-d')]);
        $user = $this->member(['pro_status' => 'active']);
        $current = $this->access($user, ['ends_at' => now()->addDays(20)]);
        $next = $this->access($user, ['plan' => 'pass_6m', 'amount' => 49, 'stripe_checkout_session_id' => 'cs_next',
            'starts_at' => $current->ends_at, 'ends_at' => $current->ends_at->copy()->addMonthsNoOverflow(6)]);
        $offer = Ad::create(['user_id' => $user->id, 'title' => 'Service de plomberie',
            'description' => 'Installation et dépannage de sanitaires.', 'category' => 'Plombier',
            'city' => 'Mamoudzou', 'country' => 'Mayotte', 'location' => 'Mamoudzou', 'postal_code' => '97600',
            'service_type' => 'offre', 'status' => 'active', 'visibility' => 'public']);

        $this->withoutStripe(function () use ($user, $offer, $current, $next) {
            $html = $this->actingAs($user)->get(route('boost.show', $offer))->assertOk()
                ->assertSee('jusqu’au '.$next->ends_at->format('d/m/Y'))
                ->assertDontSee('à la une jusqu’au '.$current->ends_at->format('d/m/Y'))
                ->getContent();
            // Les deux packs « À la une » (7 et 30 jours) et la remontée hebdomadaire sont couverts :
            // seules les deux remontées quotidiennes restent en vente.
            $this->assertSame(3, substr_count($html, 'Déjà incluse dans votre abonnement Premium'));
            $this->assertSame(2, substr_count($html, 'type="radio" name="package"'));

            $this->from(route('boost.show', $offer))
                ->post(route('boost.purchase.stripe', $offer), ['package' => 'boost_30'])
                ->assertSessionHas('error', fn (string $message) => str_contains($message, 'jusqu’au '.$next->ends_at->format('d/m/Y')));
        });
    }

    /** Défaut 3 : sans demande d'ouverture immédiate, le paiement ne commence pas. */
    public function test_the_one_time_purchase_requires_the_immediate_start_request(): void
    {
        $user = $this->member();

        $this->withoutStripe(function () use ($user) {
            foreach ([route('billing.index'), route('pro.subscription')] as $url) {
                $html = $this->actingAs($user)->get($url)->assertOk()
                    ->assertSee('Je demande l’ouverture immédiate de mon accès Premium. Je sais que je dispose d’un délai de rétractation de 14 jours et que, si je me rétracte après l’ouverture, la part déjà utilisée reste due.')
                    ->getContent();
                $this->assertSame(1, substr_count($html, 'name="immediate_start"'), $url);
                $this->assertMatchesRegularExpression('/name="immediate_start"[^>]*required/', $html, $url);
            }

            $this->from(route('billing.index'))->post(route('billing.one-time'), ['plan' => 'pass_3m'])
                ->assertRedirect(route('billing.index'))->assertSessionHasErrors('immediate_start');
            $this->from(route('billing.index'))->post(route('billing.one-time'), ['plan' => 'pass_3m', 'immediate_start' => '0'])
                ->assertSessionHasErrors('immediate_start');
            // Le message s'affiche à côté de la case.
            $this->get(route('billing.index'))->assertOk()->assertSee('Cochez la case pour demander l’ouverture immédiate');

            $this->get(route('legal.pro-terms'))->assertOk()
                ->assertSee('délai de rétractation de quatorze jours')
                ->assertSee('article L221-25');
        });
        $this->assertDatabaseCount('pro_subscriptions', 0);

        // La demande horodatée accompagne le paiement jusque dans « Mes paiements ».
        $this->withoutStripe(fn () => $this->webhook($this->paidSession($user, [], ['immediate_start_requested_at' => '2026-11-25T09:58:12+00:00'])));
        $this->assertSame('2026-11-25T09:58:12+00:00', Transaction::sole()->metadata['immediate_start_requested_at']);
    }

    /** Défaut 4 : une prolongation se compte depuis le début de la chaîne, sans perdre de jours. */
    public function test_an_extension_from_an_access_ending_on_the_28th_keeps_every_day(): void
    {
        $this->travelTo(Carbon::parse('2026-11-30 10:00:00'));
        $user = $this->member();

        $this->withoutStripe(function () use ($user) {
            $this->webhook($this->paidSession($user, ['id' => 'cs_first'], ['purchase_ref' => 'ref-first']), 'evt_first');
            $this->webhook($this->paidSession($user, ['id' => 'cs_second'], ['purchase_ref' => 'ref-second']), 'evt_second');
        });

        $first = ProSubscription::where('stripe_checkout_session_id', 'cs_first')->sole();
        $second = ProSubscription::where('stripe_checkout_session_id', 'cs_second')->sole();
        $this->assertSame('2027-02-28 10:00:00', $first->ends_at->format('Y-m-d H:i:s'));
        $this->assertTrue($second->starts_at->equalTo($first->ends_at));
        // 30/11 + 3 mois + 3 mois = 30/05, et non 28/05.
        $this->assertSame('2027-05-30 10:00:00', $second->ends_at->format('Y-m-d H:i:s'));
    }

    /** Défaut 5 : annuler l'accès en cours coupe Premium tout de suite ; l'accès en file démarre à sa date. */
    public function test_cancelling_the_running_access_stops_premium_and_the_paid_ahead_one_starts_on_its_date(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = $this->member(['pro_status' => 'active', 'pro_subscription_plan' => 'pass_3m']);
        $current = $this->access($user, ['ends_at' => now()->addDays(20)]);
        $next = $this->access($user, ['plan' => 'pass_6m', 'amount' => 49, 'stripe_checkout_session_id' => 'cs_next',
            'starts_at' => $current->ends_at, 'ends_at' => $current->ends_at->copy()->addMonthsNoOverflow(6)]);

        // L'accès en file n'ouvre rien avant sa date.
        $this->assertFalse($next->isActive());
        $this->assertTrue($next->isPaidAhead());
        $this->assertSame([$current->id], ProSubscription::currentlyActive()->pluck('id')->all());

        $this->withoutStripe(function () use ($admin, $current) {
            // La confirmation prévient qu'un accès déjà payé attend en file.
            $this->actingAs($admin)->get(route('admin.subscriptions'))->assertOk()->assertSee('existe en file');
            $this->post(route('admin.subscriptions.provider.cancel', $current))->assertRedirect();
        });

        $this->assertSame('cancelled', $current->fresh()->status);
        $this->assertFalse($user->fresh()->isPremium());
        $this->assertSame('inactive', $user->fresh()->pro_status);
        $this->assertSame('active', $next->fresh()->status);

        // À sa date de début, l'accès déjà payé s'ouvre de lui-même, et le compte suit.
        $this->travelTo($next->starts_at->copy()->addMinutes(5));
        $this->artisan('subscriptions:expire-pro')->assertSuccessful();
        $this->assertTrue($user->fresh()->isPremium());
        $this->assertSame('active', $user->fresh()->pro_status);
        $this->assertSame('pass_6m', $user->fresh()->pro_subscription_plan);
        $this->assertStatusSent($user, 'premium_expired', 0);
    }

    /** Défaut 5 : un abonnement mensuel garde son comportement, quelle que soit sa date de début. */
    public function test_the_paid_ahead_rule_does_not_touch_monthly_subscriptions(): void
    {
        $user = $this->member();
        $monthly = ProSubscription::create(['user_id' => $user->id, 'plan' => 'monthly', 'amount' => 9.99, 'status' => 'active',
            'stripe_status' => 'active', 'stripe_subscription_id' => 'sub_future_start', 'auto_renew' => true,
            'starts_at' => now()->addDay(), 'ends_at' => now()->addMonth()]);

        $this->assertTrue($monthly->isActive());
        $this->assertFalse($monthly->isPaidAhead());
        $this->assertTrue($user->fresh()->isPremium());
    }
}
