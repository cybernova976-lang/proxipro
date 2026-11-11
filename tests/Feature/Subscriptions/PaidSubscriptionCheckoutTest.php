<?php

namespace Tests\Feature\Subscriptions;

use App\Models\Setting;
use App\Models\User;
use App\Support\PlatformFeatures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Stripe\ApiRequestor;
use Stripe\HttpClient\ClientInterface;
use Tests\TestCase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class PaidSubscriptionCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public static function plans(): array
    {
        return ['monthly' => ['monthly', 999, 'month'], 'annual' => ['annual', 8500, 'year']];
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

    #[DataProvider('plans')]
    public function test_each_plan_opens_a_recurring_stripe_checkout_without_granting_access_before_payment(string $plan, int $cents, string $interval): void
    {
        $this->enableSubscriptions();
        $user = User::factory()->create(['stripe_id' => 'cus_existing', 'is_service_provider' => true,
            'available_points' => 10000, 'account_type' => 'particulier']);
        $this->actingAs($user)->get(route('pro.subscription'))->assertOk()
            ->assertSee('Mensuel')->assertSee('Annuel')->assertSee('9,99')->assertSee('85')
            ->assertDontSee('Acheter des points')->assertDontSee('points disponibles');

        $http = Mockery::mock(ClientInterface::class);
        $http->shouldReceive('request')->times(3)->andReturnUsing(function ($method, $url, $headers, $params, $hasFile) use ($plan, $cents, $interval, $user) {
            if (strtolower($method) === 'get') {
                $this->assertSame($user->stripe_id, $params['customer']);
                return [json_encode(['object' => 'list', 'has_more' => false, 'data' => []]), 200, []];
            }
            $this->assertSame('post', strtolower($method));
            $this->assertStringEndsWith('/v1/checkout/sessions', $url);
            $this->assertSame('subscription', $params['mode']);
            $this->assertSame($cents, $params['line_items'][0]['price_data']['unit_amount']);
            $this->assertSame($interval, $params['line_items'][0]['price_data']['recurring']['interval']);
            $this->assertSame('eur', $params['line_items'][0]['price_data']['currency']);
            $this->assertSame($plan, $params['metadata']['plan']);
            $this->assertSame((string) $user->id, $params['metadata']['user_id']);
            $this->assertStringStartsWith('https://marketplace.example/', $params['success_url']);
            return [json_encode(['id' => 'cs_simulated', 'object' => 'checkout.session',
                'url' => 'https://checkout.example.test/subscription']), 200, []];
        });
        $original = ApiRequestor::httpClient();
        ApiRequestor::setHttpClient($http);
        try {
            $this->post(route('pro.onboarding.subscribe'), ['plan' => $plan])
                ->assertRedirect('https://checkout.example.test/subscription');
        } finally {
            ApiRequestor::setHttpClient($original);
        }
        $this->assertFalse($user->fresh()->isPremium());
        $this->assertSame('particulier', $user->fresh()->account_type);
        $this->assertDatabaseCount('pro_subscriptions', 0);
        $this->assertDatabaseCount('point_transactions', 0);
    }

    public function test_repeated_checkouts_reuse_the_url_switch_plans_safely_and_wait_for_a_paid_webhook(): void
    {
        $this->enableSubscriptions();
        $user = User::factory()->create(['stripe_id' => 'cus_repeat', 'is_service_provider' => true]);
        $this->actingAs($user);
        $open = [];
        $created = [];
        $expired = [];
        $paid = false;
        $http = Mockery::mock(ClientInterface::class);
        $http->shouldReceive('request')->andReturnUsing(function ($method, $url, $headers, $params) use (&$open, &$created, &$expired, &$paid) {
            $path = parse_url($url, PHP_URL_PATH);
            if (strtolower($method) === 'get') {
                $this->assertSame('cus_repeat', $params['customer']);
                if ($path === '/v1/subscriptions') {
                    $data = $paid ? [['id' => 'sub_confirming', 'object' => 'subscription', 'status' => 'active',
                        'metadata' => ['type' => 'pro_subscription']]] : [];
                } else {
                    $this->assertSame('/v1/checkout/sessions', $path);
                    $this->assertSame('open', $params['status']);
                    $data = array_values($open);
                }
                return [json_encode(['object' => 'list', 'has_more' => false, 'data' => $data]), 200, []];
            }
            if (str_ends_with($path, '/expire')) {
                $id = basename(dirname($path));
                $this->assertArrayHasKey($id, $open);
                unset($open[$id]);
                $expired[] = $id;
                return [json_encode(['id' => $id, 'object' => 'checkout.session', 'status' => 'expired']), 200, []];
            }
            $this->assertSame('/v1/checkout/sessions', $path);
            $id = 'cs_'.$params['metadata']['plan'];
            $created[] = $id;
            $open[$id] = ['id' => $id, 'object' => 'checkout.session', 'mode' => 'subscription', 'status' => 'open',
                'url' => 'https://checkout.example.test/'.$id, 'metadata' => $params['metadata']];
            return [json_encode($open[$id]), 200, []];
        });
        $original = ApiRequestor::httpClient();
        ApiRequestor::setHttpClient($http);
        try {
            foreach (['monthly', 'monthly', 'annual'] as $plan) {
                $this->post(route('pro.onboarding.subscribe'), ['plan' => $plan])
                    ->assertRedirect('https://checkout.example.test/cs_'.$plan);
            }
            $this->assertSame(['cs_monthly'], $expired);
            $this->assertSame(['cs_annual'], array_keys($open));
            $paid = true;
            $open = [];
            $this->post(route('pro.onboarding.subscribe'), ['plan' => 'monthly'])
                ->assertRedirect(route('pro.subscription'))->assertSessionHas('error');
            $this->postJson(route('pro.onboarding.subscribe'), ['plan' => 'monthly'])
                ->assertStatus(422)->assertJsonPath('success', false);
            $this->assertSame(['cs_monthly', 'cs_annual'], $created);
            $this->assertDatabaseCount('pro_subscriptions', 0);
        } finally {
            ApiRequestor::setHttpClient($original);
        }
    }
}
