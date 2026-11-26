<?php

namespace Tests\Feature\Admin;

use App\Models\ProSubscription;
use App\Models\User;
use App\Notifications\AccountStatusNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Mockery;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Stripe\ApiRequestor;
use Stripe\HttpClient\ClientInterface;
use Tests\TestCase;

/**
 * Suivi des paiements d'abonnement en retard depuis l'administration :
 * l'administrateur les voit, peut prolonger le délai, relancer le prélèvement,
 * résilier à l'échéance ou annuler sans laisser de facture impayée.
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class AdminSubscriptionLatePaymentTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $member;

    private ProSubscription $subscription;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.stripe.secret' => 'sk_test_simulated']);
        Notification::fake();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->member = User::factory()->create(['name' => 'Membre En Retard', 'stripe_id' => 'cus_late', 'pro_status' => 'active']);
        $this->member->forceFill(['is_service_provider' => true])->save();
        $this->subscription = ProSubscription::create(['user_id' => $this->member->id, 'plan' => 'monthly', 'amount' => 9.99,
            'status' => 'active', 'stripe_status' => 'past_due', 'stripe_subscription_id' => 'sub_late', 'auto_renew' => true,
            'starts_at' => now()->subMonth(), 'ends_at' => now()->addMonth(),
            'payment_failed_at' => now()->subDays(5), 'grace_ends_at' => now()->addDays(10), 'payment_reminders_sent' => 0]);
    }

    /**
     * @param  callable  $respond  renvoie le corps de la réponse, ou [corps, code HTTP]
     * @return array<int, string> les appels faits à Stripe, « MÉTHODE /chemin »
     */
    private function withStripe(callable $respond, callable $run): array
    {
        $calls = [];
        $http = Mockery::mock(ClientInterface::class);
        $http->shouldReceive('request')->andReturnUsing(function ($method, $url, $headers, $params) use (&$calls, $respond) {
            $path = parse_url($url, PHP_URL_PATH);
            $calls[] = strtoupper($method).' '.$path;
            $answer = $respond(strtoupper($method), $path, $params ?? []);
            [$body, $code] = array_is_list($answer) ? $answer : [$answer, 200];

            return [json_encode($body), $code, []];
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
            'latest_invoice' => 'in_late',
            'metadata' => ['type' => 'pro_subscription', 'user_id' => (string) $this->member->id, 'plan' => 'monthly'],
            'items' => ['object' => 'list', 'data' => [['id' => 'si_1', 'price' => ['unit_amount' => 999]]]]], $extra);
    }

    private function openInvoice(): array
    {
        return ['id' => 'in_late', 'object' => 'invoice', 'status' => 'open'];
    }

    private function notified(string $event): int
    {
        return Notification::sent($this->member, AccountStatusNotification::class,
            fn (AccountStatusNotification $notification) => $notification->toArray($this->member)['type'] === 'account_'.$event)->count();
    }

    public function test_the_admin_sees_late_payments_in_the_overview_and_on_the_member_profile(): void
    {
        $onTime = User::factory()->create(['name' => 'Membre À Jour']);
        ProSubscription::create(['user_id' => $onTime->id, 'plan' => 'monthly', 'amount' => 9.99, 'status' => 'active',
            'stripe_status' => 'active', 'stripe_subscription_id' => 'sub_ok', 'auto_renew' => true,
            'starts_at' => now()->subDays(3), 'ends_at' => now()->addDays(27)]);

        $overview = $this->actingAs($this->admin)->get(route('admin.subscriptions'))->assertOk();
        $overview->assertSee('data-provider-counter="late">1<', false)
            ->assertSee('data-provider-counter="active">2<', false)
            ->assertSee('Paiement en retard')
            ->assertSee('Refusé le '.now()->subDays(5)->format('d/m/Y'))
            ->assertSee('Avantages gardés jusqu’au '.now()->addDays(10)->format('d/m/Y').' (10 jours restants)')
            ->assertSee('Rappels envoyés : 0 sur 2')
            ->assertSee(route('admin.subscriptions.provider.extend-grace', $this->subscription), false)
            ->assertSee(route('admin.subscriptions.provider.retry-payment', $this->subscription), false)
            ->assertSee('Membre À Jour')
            // L'ancien système n'a plus d'effet : plus aucun bouton pour attribuer un ancien plan.
            ->assertDontSee('Accorder Premium')
            ->assertDontSee('Attribuer un ancien plan')
            ->assertSee('sans effet sur le site depuis le 26/11/2026');

        // Le filtre ne garde que les retards ; la recherche retrouve un membre par son nom.
        $this->actingAs($this->admin)->get(route('admin.subscriptions', ['provider_state' => 'late']))->assertOk()
            ->assertSee(route('admin.subscriptions.provider.cancel', $this->subscription), false)
            ->assertDontSee(route('admin.subscriptions.provider.suspend', ProSubscription::where('stripe_subscription_id', 'sub_ok')->first()), false);
        $this->actingAs($this->admin)->get(route('admin.subscriptions', ['provider_search' => 'À Jour']))->assertOk()
            ->assertDontSee(route('admin.subscriptions.provider.cancel', $this->subscription), false)
            ->assertSee(route('admin.subscriptions.provider.end-at-period-end', ProSubscription::where('stripe_subscription_id', 'sub_ok')->first()), false);
        $this->actingAs($this->admin)->get(route('admin.subscriptions', ['provider_state' => 'ended']))->assertOk()
            ->assertSee('Aucun abonnement ne correspond à ce filtre.');

        $this->actingAs($this->admin)->get(route('admin.users.show', $this->member->id))->assertOk()
            ->assertSee('Paiement en retard')
            ->assertSee('Prolonger le délai')
            ->assertSee('Relancer le prélèvement')
            ->assertSee('Annuler immédiatement')
            // Un abonnement en retard ne se suspend pas et ne se résilie pas « à l'échéance ».
            ->assertDontSee(route('admin.subscriptions.provider.suspend', $this->subscription), false)
            ->assertDontSee(route('admin.subscriptions.provider.end-at-period-end', $this->subscription), false);
    }

    public function test_the_admin_can_extend_the_delay_and_the_member_is_told(): void
    {
        $this->freezeSecond();
        $expected = $this->subscription->grace_ends_at->copy()->addDays(7);

        $calls = $this->withStripe(fn () => [], function () {
            $this->actingAs($this->admin)->from(route('admin.subscriptions'))
                ->post(route('admin.subscriptions.provider.extend-grace', $this->subscription), ['days' => 7])
                ->assertRedirect(route('admin.subscriptions'))->assertSessionHas('success');
        });

        $this->assertSame([], $calls, 'Prolonger un délai en cours ne demande rien à Stripe.');
        $extended = $this->subscription->fresh();
        $this->assertTrue($expected->equalTo($extended->grace_ends_at));
        $this->assertTrue($extended->inPaymentGrace());
        Notification::assertSentTo($this->member, AccountStatusNotification::class, function (AccountStatusNotification $notification, array $channels) use ($expected) {
            $data = $notification->toArray($this->member);

            return $data['type'] === 'account_payment_grace_extended' && in_array('mail', $channels, true)
                && str_contains($data['message'], $expected->format('d/m/Y'));
        });

        // Bornes : entre 1 et 30 jours, et seulement sur un abonnement en retard.
        $this->actingAs($this->admin)->post(route('admin.subscriptions.provider.extend-grace', $this->subscription), ['days' => 31])
            ->assertSessionHasErrors('days');
        $this->subscription->update(['payment_failed_at' => null, 'grace_ends_at' => null, 'stripe_status' => 'active']);
        $this->actingAs($this->admin)->post(route('admin.subscriptions.provider.extend-grace', $this->subscription), ['days' => 7])
            ->assertSessionHas('error');
        $this->assertNull($this->subscription->fresh()->grace_ends_at);
        $this->assertSame(1, $this->notified('payment_grace_extended'));
    }

    public function test_extending_an_elapsed_delay_reopens_the_advantages(): void
    {
        $this->freezeSecond();
        // Délai écoulé et déjà constaté : l'abonnement attend sa fin automatique.
        $this->subscription->update(['status' => 'pending', 'grace_ends_at' => now()->subDay()]);
        $this->member->update(['pro_status' => 'inactive']);
        $this->assertFalse($this->member->fresh()->hasActiveProSubscription());

        $calls = $this->withStripe(fn () => $this->remote('past_due'), function () {
            $this->actingAs($this->admin)->post(route('admin.subscriptions.provider.extend-grace', $this->subscription), ['days' => 5])
                ->assertSessionHas('success');
        });

        $this->assertSame(['GET /v1/subscriptions/sub_late'], $calls);
        $reopened = $this->subscription->fresh();
        $this->assertSame('active', $reopened->status);
        $this->assertTrue(now()->addDays(5)->equalTo($reopened->grace_ends_at));
        $this->assertTrue($this->member->fresh()->hasActiveProSubscription());
        $this->assertSame(1, $this->notified('payment_grace_extended'));
        $this->assertSame(0, $this->notified('premium_activated'));
    }

    public function test_the_admin_can_retry_the_payment(): void
    {
        // Nouveau refus : rien ne change, l'administrateur en est informé.
        $calls = $this->withStripe(function (string $method, string $path) {
            return match (true) {
                $method === 'POST' && $path === '/v1/invoices/in_late/pay' => [['error' => ['type' => 'card_error', 'code' => 'card_declined', 'message' => 'Your card was declined.']], 402],
                default => $this->remote('past_due', ['latest_invoice' => $this->openInvoice()]),
            };
        }, function () {
            $this->actingAs($this->admin)->post(route('admin.subscriptions.provider.retry-payment', $this->subscription))
                ->assertSessionHas('error');
        });
        $this->assertSame(['GET /v1/subscriptions/sub_late', 'POST /v1/invoices/in_late/pay'], $calls);
        $this->assertTrue($this->subscription->fresh()->isPaymentLate());
        $this->assertTrue(now()->addDays(10)->isSameDay($this->subscription->fresh()->grace_ends_at));

        // Paiement accepté : l'incident est clos et le membre est prévenu.
        $paid = false;
        $calls = $this->withStripe(function (string $method, string $path) use (&$paid) {
            if ($method === 'POST' && $path === '/v1/invoices/in_late/pay') {
                $paid = true;

                return ['id' => 'in_late', 'object' => 'invoice', 'status' => 'paid'];
            }

            return $paid ? $this->remote('active') : $this->remote('past_due', ['latest_invoice' => $this->openInvoice()]);
        }, function () {
            $this->actingAs($this->admin)->post(route('admin.subscriptions.provider.retry-payment', $this->subscription))
                ->assertSessionHas('success');
        });
        $this->assertSame(['GET /v1/subscriptions/sub_late', 'POST /v1/invoices/in_late/pay', 'GET /v1/subscriptions/sub_late'], $calls);
        $settled = $this->subscription->fresh();
        $this->assertFalse($settled->isPaymentLate());
        $this->assertNull($settled->grace_ends_at);
        $this->assertTrue($settled->isActive());
        $this->assertSame(1, $this->notified('payment_recovered'));

        // Plus de retard : la relance est refusée sans appeler Stripe.
        $this->assertSame([], $this->withStripe(fn () => [], function () {
            $this->actingAs($this->admin)->post(route('admin.subscriptions.provider.retry-payment', $this->subscription))
                ->assertSessionHas('error');
        }));
    }

    public function test_cancelling_a_late_subscription_voids_the_unpaid_invoice(): void
    {
        $this->freezeSecond();
        $calls = $this->withStripe(function (string $method, string $path) {
            return match (true) {
                $method === 'DELETE' => $this->remote('canceled'),
                $method === 'GET' && $path === '/v1/invoices/in_late' => $this->openInvoice(),
                default => ['id' => 'in_late', 'object' => 'invoice', 'status' => 'void'],
            };
        }, function () {
            $this->actingAs($this->admin)->post(route('admin.subscriptions.provider.cancel', $this->subscription))
                ->assertSessionHas('success');
        });

        $this->assertSame(['DELETE /v1/subscriptions/sub_late', 'GET /v1/invoices/in_late', 'POST /v1/invoices/in_late/void'], $calls);
        $cancelled = $this->subscription->fresh();
        $this->assertSame('cancelled', $cancelled->status);
        $this->assertFalse($cancelled->isPaymentLate());
        $this->assertTrue(now()->equalTo($cancelled->ends_at));
        $this->assertFalse($this->member->fresh()->hasActiveProSubscription());
        $this->assertSame(1, $this->notified('premium_cancelled'));
        $this->assertSame(0, $this->notified('premium_unpaid_ended'));
    }

    public function test_the_admin_can_end_a_paid_subscription_at_period_end_and_restore_its_renewal(): void
    {
        // En retard : la résiliation « à l'échéance » n'a pas de sens, elle est refusée.
        $this->assertSame([], $this->withStripe(fn () => [], function () {
            $this->actingAs($this->admin)->post(route('admin.subscriptions.provider.end-at-period-end', $this->subscription))
                ->assertSessionHas('error');
        }));

        $this->subscription->update(['stripe_status' => 'active', 'payment_failed_at' => null, 'grace_ends_at' => null]);
        $calls = $this->withStripe(fn (string $method, string $path, array $params) => $this->remote('active', [
            'cancel_at_period_end' => filter_var($params['cancel_at_period_end'] ?? false, FILTER_VALIDATE_BOOLEAN),
        ]), function () {
            $this->actingAs($this->admin)->post(route('admin.subscriptions.provider.end-at-period-end', $this->subscription))
                ->assertSessionHas('success');
            $this->assertFalse($this->subscription->fresh()->auto_renew);
            $this->assertTrue($this->member->fresh()->hasActiveProSubscription(), 'Le membre garde la période déjà payée.');

            $this->actingAs($this->admin)->get(route('admin.users.show', $this->member->id))->assertOk()
                ->assertSee('Rétablir le renouvellement')
                ->assertSee('Fin à l’échéance');

            $this->actingAs($this->admin)->post(route('admin.subscriptions.provider.resume-renewal', $this->subscription))
                ->assertSessionHas('success');
        });

        $this->assertSame(['POST /v1/subscriptions/sub_late', 'POST /v1/subscriptions/sub_late'], $calls);
        $this->assertTrue($this->subscription->fresh()->auto_renew);
        $this->assertSame(1, $this->notified('premium_end_scheduled'));
        $this->assertSame(1, $this->notified('premium_renewal_resumed'));
    }
}
