<?php

namespace Tests\Feature\Subscriptions;

use App\Models\ProSubscription;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\AccountStatusNotification;
use App\Services\ProviderSubscriptionService;
use App\Services\StripeWebhookService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Mockery;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Stripe\ApiRequestor;
use Stripe\HttpClient\ClientInterface;
use Tests\TestCase;

/**
 * Paiement d'abonnement refusé : le membre est prévenu, garde ses avantages
 * pendant 15 jours, puis son compte repasse en gratuit s'il n'a pas réglé.
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class SubscriptionPaymentGraceTest extends TestCase
{
    use RefreshDatabase;

    private User $member;

    private ProSubscription $subscription;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.stripe.secret' => 'sk_test_simulated']);
        Notification::fake();
        $this->member = User::factory()->create(['stripe_id' => 'cus_late', 'pro_status' => 'active', 'email_notifications' => false]);
        $this->subscription = ProSubscription::create(['user_id' => $this->member->id, 'plan' => 'monthly', 'amount' => 9.99,
            'status' => 'active', 'stripe_status' => 'active', 'stripe_subscription_id' => 'sub_late', 'auto_renew' => true,
            'starts_at' => now()->subMonth(), 'ends_at' => now()->addMonth()]);
    }

    /** @return array<int, string> les appels faits à Stripe, « MÉTHODE /chemin » */
    private function withStripe(callable $respond, callable $run): array
    {
        $calls = [];
        $http = Mockery::mock(ClientInterface::class);
        $http->shouldReceive('request')->andReturnUsing(function ($method, $url, $headers, $params) use (&$calls, $respond) {
            $path = parse_url($url, PHP_URL_PATH);
            $calls[] = strtoupper($method).' '.$path;

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

    private function remote(string $status, array $extra = []): array
    {
        return array_merge(['id' => 'sub_late', 'object' => 'subscription', 'customer' => 'cus_late', 'status' => $status,
            'cancel_at_period_end' => false, 'canceled_at' => $status === 'canceled' ? now()->timestamp : null, 'pause_collection' => null,
            'current_period_start' => now()->subDay()->timestamp, 'current_period_end' => now()->addMonth()->timestamp,
            'metadata' => ['type' => 'pro_subscription', 'user_id' => (string) $this->member->id, 'plan' => 'monthly'],
            'items' => ['object' => 'list', 'data' => [['id' => 'si_1', 'price' => ['unit_amount' => 999]]]]], $extra);
    }

    private function event(string $id, string $type, array $object): object
    {
        return json_decode(json_encode(['id' => $id, 'type' => $type, 'data' => ['object' => $object]]));
    }

    private function notified(string $event): int
    {
        return Notification::sent($this->member, AccountStatusNotification::class,
            fn (AccountStatusNotification $notification) => $notification->toArray($this->member)['type'] === 'account_'.$event)->count();
    }

    private function refuseRenewal(): void
    {
        $this->withStripe(fn () => $this->remote('past_due'), function () {
            app(StripeWebhookService::class)->process($this->event('evt_failed', 'invoice.payment_failed', ['id' => 'in_late', 'subscription' => 'sub_late']));
        });
    }

    public function test_a_refused_renewal_keeps_the_advantages_for_fifteen_days_and_warns_the_member_once(): void
    {
        $this->freezeSecond();
        $this->refuseRenewal();

        $late = $this->subscription->fresh();
        $this->assertSame('active', $late->status);
        $this->assertSame('past_due', $late->stripe_status);
        $this->assertTrue($late->inPaymentGrace());
        $this->assertSame(15, $late->graceDaysLeft());
        $this->assertTrue(now()->addDays(15)->equalTo($late->grace_ends_at));
        $this->assertTrue($this->member->fresh()->hasActiveProSubscription());
        $this->assertSame('active', $this->member->fresh()->pro_status);

        // L'e-mail part même si le membre a coupé les e-mails : son abonnement payé est en jeu.
        Notification::assertSentTo($this->member, AccountStatusNotification::class, function (AccountStatusNotification $notification, array $channels) {
            $data = $notification->toArray($this->member);

            return $data['type'] === 'account_payment_failed' && in_array('mail', $channels, true)
                && $data['action_url'] === route('billing.index')
                && str_contains($data['message'], now()->addDays(15)->format('d/m/Y')) && str_contains($data['message'], '15 jours');
        });

        // Nouvelles tentatives de Stripe et événement d'abonnement : le délai ne repart pas, un seul message.
        $this->travel(3)->days();
        $this->withStripe(fn () => $this->remote('past_due'), function () {
            $webhooks = app(StripeWebhookService::class);
            $webhooks->process($this->event('evt_retry', 'invoice.payment_failed', ['id' => 'in_late', 'subscription' => 'sub_late']));
            $webhooks->process($this->event('evt_updated', 'customer.subscription.updated', $this->remote('past_due')));
        });
        $this->assertSame(12, $this->subscription->fresh()->graceDaysLeft());
        $this->assertSame(1, $this->notified('payment_failed'));
    }

    public function test_a_received_payment_closes_the_incident(): void
    {
        $this->refuseRenewal();
        $this->travel(4)->days();

        $this->withStripe(fn () => $this->remote('active'), function () {
            app(StripeWebhookService::class)->process($this->event('evt_paid', 'invoice.paid',
                ['id' => 'in_late', 'subscription' => 'sub_late', 'amount_paid' => 999, 'billing_reason' => 'subscription_cycle']));
        });

        $paid = $this->subscription->fresh();
        $this->assertNull($paid->payment_failed_at);
        $this->assertNull($paid->grace_ends_at);
        $this->assertSame(0, $paid->payment_reminders_sent);
        $this->assertTrue($paid->isActive());
        $this->assertFalse($paid->inPaymentGrace());
        $this->assertSame(1, $this->notified('payment_recovered'));
        $this->assertSame(0, $this->notified('premium_activated'));
        $this->assertSame(1, Transaction::where('user_id', $this->member->id)->where('type', 'SUBSCRIPTION')->count());
    }

    public function test_a_late_event_does_not_reopen_a_settled_payment_and_an_unpaid_first_invoice_opens_nothing(): void
    {
        // Événement « en retard » reçu après coup : Stripe, relu, dit que tout est payé.
        $this->withStripe(fn () => $this->remote('active'), function () {
            app(StripeWebhookService::class)->process($this->event('evt_stale', 'customer.subscription.updated', $this->remote('past_due')));
        });
        $this->assertNull($this->subscription->fresh()->payment_failed_at);
        $this->assertSame(0, $this->notified('payment_failed'));

        // Abonnement jamais payé : aucun avantage n'a été ouvert, aucun délai n'est accordé.
        $this->subscription->update(['status' => 'pending', 'stripe_status' => 'incomplete']);
        $this->member->update(['pro_status' => 'inactive']);
        $this->withStripe(fn () => $this->remote('past_due'), function () {
            app(StripeWebhookService::class)->process($this->event('evt_first', 'invoice.payment_failed', ['id' => 'in_first', 'subscription' => 'sub_late']));
        });
        $this->assertSame('pending', $this->subscription->fresh()->status);
        $this->assertNull($this->subscription->fresh()->grace_ends_at);
        $this->assertFalse($this->member->fresh()->hasActiveProSubscription());
        $this->assertSame(0, $this->notified('payment_failed'));
    }

    public function test_reminders_are_sent_once_each_then_the_account_returns_to_free_after_fifteen_days(): void
    {
        $this->refuseRenewal();

        $this->artisan('subscriptions:enforce-payment-grace')->assertSuccessful();
        $this->assertSame(0, $this->notified('payment_reminder'));

        $this->travel(7)->days();
        $this->travel(1)->hours();
        $this->artisan('subscriptions:enforce-payment-grace')->assertSuccessful();
        $this->artisan('subscriptions:enforce-payment-grace')->assertSuccessful();
        $this->assertSame(1, $this->notified('payment_reminder'));
        Notification::assertSentTo($this->member, AccountStatusNotification::class, function (AccountStatusNotification $notification, array $channels) {
            $data = $notification->toArray($this->member);

            return $data['type'] === 'account_payment_reminder' && in_array('mail', $channels, true)
                && str_contains($data['message'], 'Il vous reste 8 jours');
        });

        $this->travel(5)->days();
        $this->artisan('subscriptions:enforce-payment-grace')->assertSuccessful();
        $this->assertSame(2, $this->notified('payment_reminder'));
        $this->assertTrue($this->member->fresh()->hasActiveProSubscription());

        // Délai écoulé : les avantages sont fermés tout de suite, avant même le passage de la commande.
        $this->travel(3)->days();
        $this->assertFalse($this->subscription->fresh()->isActive());
        $this->assertFalse($this->member->fresh()->hasActiveProSubscription());

        $calls = $this->withStripe(function (string $method, string $path, array $params) {
            return match (true) {
                $method === 'GET' && $path === '/v1/subscriptions/sub_late' => $this->remote('past_due', ['latest_invoice' => ['id' => 'in_late', 'object' => 'invoice', 'status' => 'open']]),
                $method === 'GET' && $path === '/v1/invoice_payments' => ['object' => 'list', 'has_more' => false, 'data' => [
                    ['id' => 'inpay_1', 'object' => 'invoice_payment', 'status' => 'open',
                        'payment' => ['type' => 'payment_intent', 'payment_intent' => ['id' => 'pi_1', 'object' => 'payment_intent', 'status' => 'requires_payment_method']]]]],
                $method === 'DELETE' => $this->remote('canceled'),
                default => ['id' => 'in_late', 'object' => 'invoice', 'status' => 'void'],
            };
        }, fn () => $this->artisan('subscriptions:enforce-payment-grace')->assertSuccessful());

        $this->assertSame(['GET /v1/subscriptions/sub_late', 'GET /v1/invoice_payments', 'DELETE /v1/subscriptions/sub_late', 'POST /v1/invoices/in_late/void'], $calls);
        $ended = $this->subscription->fresh();
        $this->assertSame('cancelled', $ended->status);
        $this->assertSame('canceled', $ended->stripe_status);
        $this->assertFalse($ended->auto_renew);
        $this->assertFalse($ended->hasOpenBilling());
        $this->assertTrue($ended->ends_at->isPast() || $ended->ends_at->equalTo(now()));
        $this->assertSame('inactive', $this->member->fresh()->pro_status);
        $this->assertNotNull(User::find($this->member->id), 'Le compte reste ouvert, en gratuit.');
        $this->assertSame(1, $this->notified('premium_unpaid_ended'));
        $this->assertSame(0, $this->notified('premium_expired'));

        // Plus rien à faire au passage suivant : aucun appel à Stripe, aucun message de plus.
        $this->assertSame([], $this->withStripe(fn () => [], fn () => $this->artisan('subscriptions:enforce-payment-grace')->assertSuccessful()));
        $this->assertSame(1, $this->notified('premium_unpaid_ended'));
    }

    public function test_a_direct_debit_in_progress_postpones_the_end_and_a_payment_received_meanwhile_cancels_it(): void
    {
        $this->refuseRenewal();
        $this->travel(16)->days();

        // Prélèvement en cours de traitement : on attend son résultat, rien n'est résilié.
        $calls = $this->withStripe(function (string $method, string $path) {
            return $path === '/v1/invoice_payments'
                ? ['object' => 'list', 'has_more' => false, 'data' => [['id' => 'inpay_1', 'object' => 'invoice_payment', 'status' => 'open',
                    'payment' => ['type' => 'payment_intent', 'payment_intent' => ['id' => 'pi_1', 'object' => 'payment_intent', 'status' => 'processing']]]]]
                : $this->remote('past_due', ['latest_invoice' => ['id' => 'in_late', 'object' => 'invoice', 'status' => 'open']]);
        }, fn () => $this->artisan('subscriptions:enforce-payment-grace')->assertSuccessful());
        $this->assertSame(['GET /v1/subscriptions/sub_late', 'GET /v1/invoice_payments'], $calls);
        $this->assertSame('past_due', $this->subscription->fresh()->stripe_status);
        $this->assertSame(0, $this->notified('premium_unpaid_ended'));

        // Le prélèvement aboutit : Stripe dit « actif », l'abonnement reprend sans résiliation.
        $calls = $this->withStripe(fn () => $this->remote('active'), fn () => $this->artisan('subscriptions:enforce-payment-grace')->assertSuccessful());
        $this->assertSame(['GET /v1/subscriptions/sub_late'], $calls);
        $this->assertTrue($this->subscription->fresh()->isActive());
        $this->assertNull($this->subscription->fresh()->payment_failed_at);
        $this->assertTrue($this->member->fresh()->hasActiveProSubscription());
    }

    public function test_cancelling_while_late_ends_the_subscription_at_once_without_any_debt(): void
    {
        $this->refuseRenewal();

        $calls = $this->withStripe(function (string $method, string $path) {
            return match (true) {
                $method === 'GET' && $path === '/v1/subscriptions/sub_late' => $this->remote('past_due', ['latest_invoice' => ['id' => 'in_late', 'object' => 'invoice', 'status' => 'open']]),
                $method === 'GET' => ['object' => 'list', 'has_more' => false, 'data' => []],
                $method === 'DELETE' => $this->remote('canceled'),
                default => ['id' => 'in_late', 'object' => 'invoice', 'status' => 'void'],
            };
        }, function () {
            $this->actingAs($this->member)->from(route('billing.index'))->post(route('pro.subscription.cancel-renewal'))
                ->assertRedirect(route('billing.index'))->assertSessionHas('success');
        });

        $this->assertContains('POST /v1/invoices/in_late/void', $calls);
        $this->assertSame('cancelled', $this->subscription->fresh()->status);
        $this->assertFalse($this->member->fresh()->hasActiveProSubscription());
        // Le membre vient de le décider lui-même : pas de message « résilié pour impayé ».
        $this->assertSame(0, $this->notified('premium_unpaid_ended'));
    }

    public function test_the_follow_up_runs_every_hour(): void
    {
        $event = collect(app(Schedule::class)->events())
            ->first(fn ($event) => str_contains((string) $event->command, 'subscriptions:enforce-payment-grace'));

        $this->assertNotNull($event);
        $this->assertSame('0 * * * *', $event->expression);
        $this->assertSame(15, ProSubscription::PAYMENT_GRACE_DAYS);
        $this->assertInstanceOf(ProviderSubscriptionService::class, app(ProviderSubscriptionService::class));
    }
}
