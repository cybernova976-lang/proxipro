<?php

namespace Tests\Feature\Subscriptions;

use App\Models\Ad;
use App\Models\ProSubscription;
use App\Models\Setting;
use App\Models\User;
use App\Services\ProviderSubscriptionService;
use App\Support\PlatformFeatures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ProviderSubscriptionFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_cannot_open_subscriptions_before_the_readiness_checklist_is_complete(): void
    {
        config([
            'services.stripe.key' => null,
            'services.stripe.secret' => null,
            'services.stripe.webhook_secret' => null,
            'app.url' => 'http://localhost',
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.settings.pro-subscriptions'), ['enabled' => 1])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertFalse(PlatformFeatures::proSubscriptionsRequested());
        $this->assertFalse(PlatformFeatures::proSubscriptionsEnabled());
    }

    public function test_admin_can_open_and_close_subscriptions_once_everything_is_ready(): void
    {
        $this->configureReadyPlatform();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.settings.pro-subscriptions'), ['enabled' => 1])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertTrue(PlatformFeatures::proSubscriptionsEnabled());

        $this->actingAs($admin)
            ->post(route('admin.settings.pro-subscriptions'), ['enabled' => 0])
            ->assertRedirect();

        $this->assertFalse(PlatformFeatures::proSubscriptionsEnabled());
    }

    public function test_stripe_subscription_sync_is_idempotent_and_does_not_change_legal_status(): void
    {
        $user = User::factory()->create([
            'user_type' => 'particulier',
            'account_type' => 'particulier',
            'is_service_provider' => true,
        ]);
        $service = app(ProviderSubscriptionService::class);
        $stripeSubscription = $this->stripeSubscription($user);
        $session = (object) [
            'id' => 'cs_test_provider_1',
            'metadata' => (object) [
                'user_id' => (string) $user->id,
                'type' => 'provider_subscription',
                'plan' => 'monthly',
            ],
            'subscription' => $stripeSubscription,
            'payment_intent' => null,
        ];

        $first = $service->completeCheckoutSession($session, $user);
        $second = $service->completeCheckoutSession($session, $user);

        $this->assertSame($first->id, $second->id);
        $this->assertSame('sub_provider_1', $first->stripe_subscription_id);
        $this->assertSame('cs_test_provider_1', $first->stripe_checkout_session_id);
        $this->assertTrue($first->auto_renew);
        $this->assertDatabaseCount('pro_subscriptions', 1);
        $this->assertSame('particulier', $user->fresh()->user_type);
        $this->assertSame('particulier', $user->fresh()->account_type);
        $this->assertSame('active', $user->fresh()->pro_status);

        $professionalOnlyAd = Ad::create([
            'user_id' => User::factory()->create()->id,
            'title' => 'Mission réservée aux entreprises',
            'description' => 'Une mission réservée aux professionnels déclarés.',
            'category' => 'Plombier',
            'main_category' => 'Bricolage & Travaux',
            'location' => 'Paris',
            'service_type' => 'demande',
            'status' => 'active',
            'reply_restriction' => 'pro_only',
        ]);

        $this->actingAs($user)
            ->post(route('comments.store', $professionalOnlyAd), ['content' => 'Je suis intéressé.'])
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertDatabaseCount('comments', 0);
    }

    public function test_admin_can_grant_and_cancel_a_manual_access_without_stripe(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $provider = User::factory()->create(['is_service_provider' => true]);

        $this->actingAs($admin)
            ->post(route('admin.subscriptions.provider.grant'), [
                'email' => $provider->email,
                'plan' => 'monthly',
                'duration' => 30,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $subscription = ProSubscription::firstOrFail();
        $this->assertNull($subscription->stripe_subscription_id);
        $this->assertTrue($subscription->isActive());

        $this->actingAs($admin)
            ->post(route('admin.subscriptions.provider.suspend', $subscription))
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertSame('pending', $subscription->fresh()->status);

        $this->actingAs($admin)
            ->post(route('admin.subscriptions.provider.resume', $subscription))
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertSame('active', $subscription->fresh()->status);

        $this->actingAs($admin)
            ->post(route('admin.subscriptions.provider.cancel', $subscription))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('cancelled', $subscription->fresh()->status);
        $this->assertSame('inactive', $provider->fresh()->pro_status);
    }

    public function test_expired_manual_access_is_cleaned_without_touching_stripe_subscriptions(): void
    {
        $provider = User::factory()->create([
            'is_service_provider' => true,
            'pro_status' => 'active',
            'pro_subscription_plan' => 'monthly',
        ]);
        $manual = ProSubscription::create([
            'user_id' => $provider->id,
            'plan' => 'monthly',
            'amount' => 0,
            'status' => 'active',
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->subMinute(),
            'auto_renew' => false,
        ]);

        Artisan::call('subscriptions:expire-pro');

        $this->assertSame('expired', $manual->fresh()->status);
        $this->assertSame('inactive', $provider->fresh()->pro_status);
    }

    private function configureReadyPlatform(): void
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
    }

    private function stripeSubscription(User $user): object
    {
        return (object) [
            'id' => 'sub_provider_1',
            'customer' => 'cus_provider_1',
            'status' => 'active',
            'current_period_start' => now()->timestamp,
            'current_period_end' => now()->addMonth()->timestamp,
            'cancel_at_period_end' => false,
            'canceled_at' => null,
            'pause_collection' => null,
            'latest_invoice' => null,
            'metadata' => (object) [
                'user_id' => (string) $user->id,
                'type' => 'provider_subscription',
                'plan' => 'monthly',
            ],
        ];
    }
}
