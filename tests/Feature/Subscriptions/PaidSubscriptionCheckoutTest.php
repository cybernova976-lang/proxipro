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

    #[DataProvider('plans')]
    public function test_each_plan_opens_a_recurring_stripe_checkout_without_granting_access_before_payment(string $plan, int $cents, string $interval): void
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
        $user = User::factory()->create(['stripe_id' => 'cus_existing', 'is_service_provider' => true,
            'available_points' => 10000, 'account_type' => 'particulier']);
        $this->actingAs($user)->get(route('pro.subscription'))->assertOk()
            ->assertSee('Mensuel')->assertSee('Annuel')->assertSee('9,99')->assertSee('85')
            ->assertDontSee('Acheter des points')->assertDontSee('points disponibles');

        $http = Mockery::mock(ClientInterface::class);
        $http->shouldReceive('request')->once()->andReturnUsing(function ($method, $url, $headers, $params, $hasFile) use ($plan, $cents, $interval, $user) {
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
}
