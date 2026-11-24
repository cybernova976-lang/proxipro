<?php

namespace Tests\Feature\Subscriptions;

use App\Models\Ad;
use App\Models\ServiceOrder;
use App\Models\Setting;
use App\Models\User;
use App\Support\PlatformFeatures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Stripe\ApiRequestor;
use Stripe\HttpClient\ClientInterface;
use Tests\TestCase;

/**
 * Un membre dont le client Stripe a été créé avec les clés de test (avant le passage
 * en mode réel) doit pouvoir s'abonner et payer une commande : avec les clés réelles,
 * Stripe répond « No such customer » ; on crée alors un nouveau client, une seule fois.
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class StaleStripeCustomerTest extends TestCase
{
    use RefreshDatabase;

    private const STALE = 'cus_from_test_keys';

    private function unknownCustomer(): array
    {
        return [json_encode(['error' => ['type' => 'invalid_request_error', 'code' => 'resource_missing',
            'param' => 'customer', 'message' => "No such customer: '".self::STALE."'"]]), 404, []];
    }

    private function withStripe(callable $responder, callable $run): void
    {
        $http = Mockery::mock(ClientInterface::class);
        $http->shouldReceive('request')->andReturnUsing($responder);
        $original = ApiRequestor::httpClient();
        ApiRequestor::setHttpClient($http);
        try {
            $run();
        } finally {
            ApiRequestor::setHttpClient($original);
        }
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

    public function test_subscribing_replaces_a_customer_unknown_to_the_live_keys(): void
    {
        $this->enableSubscriptions();
        $user = User::factory()->create(['stripe_id' => self::STALE, 'is_service_provider' => true]);
        $customersCreated = [];
        $sessionCustomer = null;

        $this->withStripe(function ($method, $url, $headers, $params) use (&$customersCreated, &$sessionCustomer) {
            $path = parse_url($url, PHP_URL_PATH);
            if (strtolower($method) === 'get') {
                if (($params['customer'] ?? null) === self::STALE) {
                    return $this->unknownCustomer();
                }
                $this->assertSame('cus_live_new', $params['customer']);

                return [json_encode(['object' => 'list', 'has_more' => false, 'data' => []]), 200, []];
            }
            if ($path === '/v1/customers') {
                $customersCreated[] = $headers;

                return [json_encode(['id' => 'cus_live_new', 'object' => 'customer']), 200, []];
            }
            $this->assertSame('/v1/checkout/sessions', $path);
            $sessionCustomer = $params['customer'];

            return [json_encode(['id' => 'cs_new', 'object' => 'checkout.session',
                'url' => 'https://checkout.example.test/subscription']), 200, []];
        }, function () use ($user) {
            $this->actingAs($user)->post(route('pro.onboarding.subscribe'), ['plan' => 'monthly'])
                ->assertRedirect('https://checkout.example.test/subscription');
        });

        $this->assertCount(1, $customersCreated);
        $this->assertSame('cus_live_new', $sessionCustomer);
        $this->assertSame('cus_live_new', $user->fresh()->stripe_id);
    }

    public function test_subscribing_keeps_the_customer_when_stripe_is_merely_unavailable(): void
    {
        $this->enableSubscriptions();
        $user = User::factory()->create(['stripe_id' => 'cus_real', 'is_service_provider' => true]);
        $posts = 0;

        $this->withStripe(function ($method) use (&$posts) {
            if (strtolower($method) !== 'get') {
                $posts++;
            }

            return [json_encode(['error' => ['type' => 'api_error', 'message' => 'Stripe indisponible']]), 500, []];
        }, function () use ($user) {
            $this->actingAs($user)->post(route('pro.onboarding.subscribe'), ['plan' => 'monthly'])
                ->assertRedirect();
        });

        $this->assertSame(0, $posts);
        $this->assertSame('cus_real', $user->fresh()->stripe_id);
    }

    public function test_paying_an_order_replaces_a_customer_unknown_to_the_live_keys(): void
    {
        config(['services.stripe.secret' => 'sk_live_configured']);
        $buyer = User::factory()->create(['stripe_id' => self::STALE]);
        $seller = User::factory()->create(['stripe_connect_account_id' => 'acct_seller', 'stripe_connect_payouts_enabled' => true]);
        $ad = Ad::create(['title' => 'Annonce test', 'description' => 'Annonce pour tester le paiement',
            'category' => 'Electricite', 'location' => 'Mamoudzou', 'price' => 150, 'service_type' => 'offre',
            'status' => 'active', 'user_id' => $seller->id]);
        $order = ServiceOrder::create(['order_number' => 'CMD-STALE-1', 'ad_id' => $ad->id, 'buyer_id' => $buyer->id,
            'seller_id' => $seller->id, 'amount' => 150, 'commission_amount' => 15, 'seller_amount' => 135,
            'status' => ServiceOrder::STATUS_AWAITING_PAYMENT, 'payment_status' => ServiceOrder::PAYMENT_AWAITING]);
        $this->mock(\App\Services\StripeConnectService::class)->shouldReceive('sellerCanBePaid')->andReturnTrue();
        $sessions = [];

        $this->withStripe(function ($method, $url, $headers, $params) use (&$sessions) {
            $path = parse_url($url, PHP_URL_PATH);
            if ($path === '/v1/customers') {
                return [json_encode(['id' => 'cus_live_buyer', 'object' => 'customer']), 200, []];
            }
            $this->assertSame('/v1/checkout/sessions', $path);
            $sessions[] = $params['customer'];
            if ($params['customer'] === self::STALE) {
                return $this->unknownCustomer();
            }

            return [json_encode(['id' => 'cs_order', 'object' => 'checkout.session',
                'url' => 'https://checkout.example.test/order']), 200, []];
        }, function () use ($buyer, $order) {
            $this->actingAs($buyer)->post(route('service-orders.checkout', $order))
                ->assertRedirect('https://checkout.example.test/order');
        });

        $this->assertSame([self::STALE, 'cus_live_buyer'], $sessions);
        $this->assertSame('cus_live_buyer', $buyer->fresh()->stripe_id);
        $this->assertSame('cs_order', $order->fresh()->stripe_checkout_session_id);
    }
}
