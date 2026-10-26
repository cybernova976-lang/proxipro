<?php

namespace Tests\Feature\Auth;

use App\Http\Controllers\Auth\EmailVerificationCodeController;
use App\Mail\EmailVerificationCode;
use App\Models\Setting;
use App\Models\User;
use App\Support\DemandPublicationContinuation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class ProviderRegistrationContinuationFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_provider_intention_survives_switching_from_registration_to_login_and_is_consumed_once(): void
    {
        $user = User::factory()->create(['password' => Hash::make('password'), 'is_service_provider' => false]);

        $this->get(route('register', ['continue' => 'provider']))->assertOk()
            ->assertSessionHas(DemandPublicationContinuation::PROVIDER_SESSION_KEY, true)
            ->assertSessionMissing(DemandPublicationContinuation::SESSION_KEY);
        $this->get(route('login'))->assertOk()
            ->assertSessionHas(DemandPublicationContinuation::PROVIDER_SESSION_KEY, true);

        $this->post(route('login.attempt'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('service-provider.start'))
            ->assertSessionMissing(DemandPublicationContinuation::PROVIDER_SESSION_KEY)
            ->assertSessionMissing(DemandPublicationContinuation::SESSION_KEY);
        $this->assertAuthenticatedAs($user);
        $this->assertFalse((bool) $user->fresh()->is_service_provider);
        $this->get(route('register'))->assertRedirect(route('feed'));
    }

    public function test_failed_login_preserves_provider_intention(): void
    {
        $user = User::factory()->create(['password' => Hash::make('password')]);
        $this->get(route('login', ['continue' => 'provider']))->assertOk();

        $this->post(route('login.attempt'), ['email' => $user->email, 'password' => 'incorrect'])
            ->assertSessionHasErrors('email')
            ->assertSessionHas(DemandPublicationContinuation::PROVIDER_SESSION_KEY, true);
        $this->assertGuest();
    }

    public function test_registration_continues_to_onboarding_without_enabling_provider_status(): void
    {
        Mail::fake();
        Setting::set('email_verification_enabled', '0');
        $this->get(route('register', ['continue' => 'provider']))->assertOk();

        $this->post(route('register'), [
            'account_type' => 'particulier', 'firstname' => 'Amina', 'lastname' => 'Martin',
            'email' => 'provider.continuation@example.test', 'country' => 'France', 'city' => 'Paris',
            'password' => 'mot-de-passe-solide-2026', 'password_confirmation' => 'mot-de-passe-solide-2026',
            'terms' => '1', 'website_url' => '',
        ])->assertRedirect(route('service-provider.start'))
            ->assertSessionMissing(DemandPublicationContinuation::PROVIDER_SESSION_KEY);

        $this->assertAuthenticated();
        $user = User::where('email', 'provider.continuation@example.test')->firstOrFail();
        $this->assertSame('particulier', $user->account_type);
        $this->assertFalse((bool) $user->is_service_provider);
        $this->assertDatabaseCount('user_services', 0);
    }

    public function test_authenticated_registration_link_uses_fixed_onboarding_destination_and_preserves_account(): void
    {
        $user = User::factory()->create(['account_type' => 'particulier', 'user_type' => 'particulier', 'is_service_provider' => false]);
        $before = $user->fresh()->only(['account_type', 'user_type', 'is_service_provider', 'identity_verified', 'plan']);

        $this->actingAs($user)->get(route('register', [
            'continue' => 'provider', 'return_url' => 'https://example.org', 'redirect' => 'https://example.org',
        ]))->assertRedirect(route('service-provider.start'))
            ->assertSessionMissing(DemandPublicationContinuation::PROVIDER_SESSION_KEY)
            ->assertSessionMissing(DemandPublicationContinuation::SESSION_KEY);

        $this->assertSame($before, $user->fresh()->only(array_keys($before)));
        $this->assertDatabaseCount('user_services', 0);
        $this->assertDatabaseCount('ads', 0);
    }

    public function test_demand_intention_replaces_provider_and_keeps_existing_demand_destination(): void
    {
        $user = User::factory()->create(['password' => Hash::make('password')]);
        $this->get(route('register', ['continue' => 'provider']))->assertOk();
        $this->get(route('login', ['continue' => 'demand']))->assertOk()
            ->assertSessionHas(DemandPublicationContinuation::SESSION_KEY, true)
            ->assertSessionMissing(DemandPublicationContinuation::PROVIDER_SESSION_KEY);

        $this->post(route('login.attempt'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('demand.create', ['resume' => 1]))
            ->assertSessionMissing(DemandPublicationContinuation::PROVIDER_SESSION_KEY)
            ->assertSessionMissing(DemandPublicationContinuation::SESSION_KEY);
    }

    public function test_provider_intention_replaces_demand(): void
    {
        $user = User::factory()->create(['password' => Hash::make('password')]);
        $this->get(route('register', ['continue' => 'demand']))->assertOk();
        $this->get(route('login', ['continue' => 'provider']))->assertOk()
            ->assertSessionHas(DemandPublicationContinuation::PROVIDER_SESSION_KEY, true)
            ->assertSessionMissing(DemandPublicationContinuation::SESSION_KEY);

        $this->post(route('login.attempt'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('service-provider.start'))
            ->assertSessionMissing(DemandPublicationContinuation::PROVIDER_SESSION_KEY)
            ->assertSessionMissing(DemandPublicationContinuation::SESSION_KEY);
    }

    public function test_external_continuation_and_return_urls_never_control_the_destination(): void
    {
        $user = User::factory()->create(['password' => Hash::make('password')]);
        $this->withSession(['url.intended' => 'https://example.org'])
            ->get(route('login', ['continue' => 'https://example.org', 'return_url' => '//example.org']))
            ->assertOk()
            ->assertSessionMissing(DemandPublicationContinuation::PROVIDER_SESSION_KEY)
            ->assertSessionMissing(DemandPublicationContinuation::SESSION_KEY);

        $this->post(route('login.attempt'), ['email' => $user->email, 'password' => 'password', 'redirect' => 'https://example.org'])
            ->assertRedirect(route('feed'));
    }

    public function test_email_verification_preserves_provider_intention_without_bypassing_verification(): void
    {
        Mail::fake();
        Setting::set('email_verification_enabled', '1');
        $user = User::factory()->unverified()->create(['password' => Hash::make('password'), 'is_service_provider' => false]);
        $this->get(route('login', ['continue' => 'provider']))->assertOk();

        $this->post(route('login.attempt'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('verification.code.show', ['email' => $user->email]))
            ->assertSessionHas(DemandPublicationContinuation::PROVIDER_SESSION_KEY, true)
            ->assertSessionMissing(DemandPublicationContinuation::SESSION_KEY)
            ->assertSessionHas(EmailVerificationCodeController::PENDING_USER_SESSION_KEY, $user->id);
        $this->assertGuest();
        $this->assertNull($user->fresh()->email_verified_at);

        $this->post(route('verification.code.verify'), ['email' => $user->email, 'code' => 'xxxxxx'])
            ->assertSessionHas('error')
            ->assertSessionHas(DemandPublicationContinuation::PROVIDER_SESSION_KEY, true);
        $this->assertGuest();

        $code = Mail::sent(EmailVerificationCode::class)->first()->code;
        $this->post(route('verification.code.verify'), ['email' => $user->email, 'code' => $code])
            ->assertRedirect(route('login'))
            ->assertSessionHas(DemandPublicationContinuation::PROVIDER_SESSION_KEY, true)
            ->assertSessionMissing(EmailVerificationCodeController::PENDING_USER_SESSION_KEY);
        $this->assertGuest();

        $this->post(route('login.attempt'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('service-provider.start'))
            ->assertSessionMissing(DemandPublicationContinuation::PROVIDER_SESSION_KEY);
        $this->assertFalse((bool) $user->fresh()->is_service_provider);
    }

    public function test_social_login_uses_the_same_provider_continuation(): void
    {
        $socialUser = Mockery::mock();
        $socialUser->shouldReceive('getEmail')->once()->andReturn('social.provider.continuation@example.test');
        $socialUser->shouldReceive('getId')->once()->andReturn('social-provider-continuation');
        $socialUser->shouldReceive('getName')->once()->andReturn('Compte social de test');
        $socialUser->shouldReceive('getAvatar')->once()->andReturn(null);
        $driver = Mockery::mock();
        $driver->shouldReceive('user')->once()->andReturn($socialUser);
        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($driver);

        $this->get(route('register', ['continue' => 'provider']))->assertOk();
        $this->get(route('social.callback', ['provider' => 'google']))
            ->assertRedirect(route('service-provider.start'))
            ->assertSessionMissing(DemandPublicationContinuation::PROVIDER_SESSION_KEY)
            ->assertSessionMissing(DemandPublicationContinuation::SESSION_KEY);
        $this->assertAuthenticated();
        $this->assertFalse((bool) User::where('email', 'social.provider.continuation@example.test')->firstOrFail()->is_service_provider);
    }
}
