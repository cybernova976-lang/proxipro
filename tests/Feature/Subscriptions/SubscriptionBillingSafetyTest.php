<?php

namespace Tests\Feature\Subscriptions;

use App\Models\ProSubscription;
use App\Models\User;
use App\Services\ProviderSubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Stripe\ApiRequestor;
use Stripe\HttpClient\ClientInterface;
use Tests\TestCase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class SubscriptionBillingSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_deletion_cancels_remote_subscriptions_even_before_webhook_and_is_retryable(): void
    {
        config(['services.stripe.secret' => 'sk_test_simulated']);
        $user = User::factory()->create(['stripe_id' => 'cus_account']);
        $local = ProSubscription::create(['user_id' => $user->id, 'plan' => 'monthly', 'amount' => 9.99,
            'status' => 'pending', 'stripe_status' => 'past_due', 'stripe_subscription_id' => 'sub_local']);
        $requests = [];
        $cancelled = [];
        $checkoutExpired = false;
        $http = Mockery::mock(ClientInterface::class);
        $http->shouldReceive('request')->andReturnUsing(function ($method, $url, $headers, $params) use (&$requests, &$cancelled, &$checkoutExpired, $user) {
            $requests[] = [$method, $url, $params];
            if (strtolower($method) === 'get') {
                $this->assertSame('cus_account', $params['customer']);
                if (str_ends_with($url, '/checkout/sessions')) {
                    $this->assertSame('open', $params['status']);
                    $data = [['id' => 'cs_order', 'object' => 'checkout.session', 'mode' => 'payment']];
                    if (! $checkoutExpired) {
                        $data[] = ['id' => 'cs_open', 'object' => 'checkout.session', 'mode' => 'subscription',
                            'metadata' => ['type' => 'pro_subscription', 'user_id' => (string) $user->id]];
                    }
                    return [json_encode(['object' => 'list', 'has_more' => false, 'data' => $data]), 200, []];
                }
                return [json_encode(['object' => 'list', 'has_more' => false, 'data' => array_map(fn ($id) => [
                    'id' => $id, 'object' => 'subscription', 'status' => in_array($id, $cancelled) ? 'canceled' : 'active',
                    'metadata' => ['type' => $id === 'sub_unrelated' ? 'other' : 'pro_subscription'],
                ], ['sub_local', 'sub_webhook_pending', 'sub_unrelated'])]), 200, []];
            }
            if (strtolower($method) === 'post') {
                $this->assertStringEndsWith('/checkout/sessions/cs_open/expire', $url);
                $checkoutExpired = true;
                return [json_encode(['id' => 'cs_open', 'object' => 'checkout.session', 'status' => 'expired']), 200, []];
            }
            $this->assertSame('delete', strtolower($method));
            $this->assertSame('false', $params['invoice_now']);
            $this->assertSame('false', $params['prorate']);
            $id = basename(parse_url($url, PHP_URL_PATH));
            $cancelled[] = $id;
            return [json_encode(['id' => $id, 'object' => 'subscription', 'status' => 'canceled']), 200, []];
        });
        $original = ApiRequestor::httpClient();
        ApiRequestor::setHttpClient($http);
        try {
            $service = app(ProviderSubscriptionService::class);
            $service->cancelForAccountDeletion($user);
            $service->cancelForAccountDeletion($user);
        } finally {
            ApiRequestor::setHttpClient($original);
        }
        $this->assertSame(['sub_local', 'sub_webhook_pending'], $cancelled);
        $this->assertTrue($checkoutExpired);
        $this->assertCount(7, $requests);
        $this->assertSame('cancelled', $local->fresh()->status);
        $this->assertFalse($local->fresh()->auto_renew);
        $this->assertNotNull(User::find($user->id));
    }

    public function test_active_cancellation_keeps_paid_access_and_prevents_further_renewal(): void
    {
        config(['services.stripe.secret' => 'sk_test_simulated']);
        $user = User::factory()->create();
        $end = now()->addDays(12)->startOfSecond();
        $local = ProSubscription::create(['user_id' => $user->id, 'plan' => 'monthly', 'amount' => 13,
            'status' => 'active', 'stripe_status' => 'active', 'stripe_subscription_id' => 'sub_paid',
            'auto_renew' => true, 'ends_at' => $end]);
        $http = Mockery::mock(ClientInterface::class);
        $http->shouldReceive('request')->once()->andReturnUsing(function ($method, $url, $headers, $params) use ($end) {
            $this->assertSame('true', $params['cancel_at_period_end']);
            return [json_encode(['id' => 'sub_paid', 'object' => 'subscription', 'status' => 'active', 'cancel_at_period_end' => true,
                'current_period_start' => now()->subDays(18)->timestamp, 'current_period_end' => $end->timestamp]), 200, []];
        });
        $original = ApiRequestor::httpClient();
        ApiRequestor::setHttpClient($http);
        try {
            $result = app(ProviderSubscriptionService::class)->cancelAtPeriodEnd($local);
        } finally {
            ApiRequestor::setHttpClient($original);
        }
        $this->assertTrue($result->isActive());
        $this->assertFalse($result->auto_renew);
        $this->assertTrue($end->equalTo($result->ends_at));
        $this->assertTrue($user->fresh()->hasActiveProSubscription());
        $this->assertStringContainsString('13,00', $result->getPlanLabel());
    }
}
