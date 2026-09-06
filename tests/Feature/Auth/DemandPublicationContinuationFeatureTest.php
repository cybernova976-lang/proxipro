<?php

namespace Tests\Feature\Auth;

use App\Http\Controllers\Auth\EmailVerificationCodeController;
use App\Models\Setting;
use App\Models\User;
use App\Support\DemandPublicationContinuation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class DemandPublicationContinuationFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_login_resumes_only_the_explicit_demand_flow_and_consumes_the_marker(): void
    {
        $user = User::factory()->create(['password' => Hash::make('password')]);
        $this->get(route('login', ['continue' => 'demand']))->assertOk()
            ->assertSessionHas(DemandPublicationContinuation::SESSION_KEY, true);

        $this->post(route('login.attempt'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('demand.create', ['resume' => 1]))
            ->assertSessionMissing(DemandPublicationContinuation::SESSION_KEY);
        $this->assertDatabaseCount('ads', 0);
    }

    public function test_normal_login_still_goes_to_the_feed_and_ignores_arbitrary_return_urls(): void
    {
        $user = User::factory()->create(['password' => Hash::make('password')]);
        $this->get(route('login', ['continue' => 'https://example.org']))->assertOk()
            ->assertSessionMissing(DemandPublicationContinuation::SESSION_KEY);
        $this->post(route('login.attempt'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('feed'));
    }

    public function test_failed_login_keeps_the_pending_demand(): void
    {
        $user = User::factory()->create(['password' => Hash::make('password')]);
        $this->get(route('login', ['continue' => 'demand']));
        $this->post(route('login.attempt'), ['email' => $user->email, 'password' => 'incorrect'])
            ->assertSessionHasErrors('email')
            ->assertSessionHas(DemandPublicationContinuation::SESSION_KEY, true);
        $this->assertGuest();
    }

    public function test_email_verification_keeps_the_pending_demand_without_bypassing_verification(): void
    {
        Mail::fake();
        Setting::set('email_verification_enabled', '1');
        $user = User::factory()->unverified()->create(['password' => Hash::make('password')]);
        $this->get(route('login', ['continue' => 'demand']));
        $this->post(route('login.attempt'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('verification.code.show', ['email' => $user->email]))
            ->assertSessionHas(DemandPublicationContinuation::SESSION_KEY, true)
            ->assertSessionHas(EmailVerificationCodeController::PENDING_USER_SESSION_KEY, $user->id);
        $this->assertGuest();
    }

    public function test_registration_can_resume_the_demand_when_email_verification_is_disabled(): void
    {
        Mail::fake();
        Setting::set('email_verification_enabled', '0');
        $this->get(route('register', ['continue' => 'demand']))->assertOk();
        $this->post(route('register'), [
            'account_type' => 'particulier', 'firstname' => 'Amina', 'lastname' => 'Martin',
            'email' => 'amina.continuation@example.test', 'country' => 'France', 'city' => 'Paris',
            'password' => 'mot-de-passe-solide-2026', 'password_confirmation' => 'mot-de-passe-solide-2026',
            'terms' => '1', 'website_url' => '',
        ])->assertRedirect(route('demand.create', ['resume' => 1]));
        $this->assertAuthenticated();
        $this->assertDatabaseCount('ads', 0);
    }

    public function test_social_login_uses_the_same_safe_demand_continuation(): void
    {
        $socialUser = Mockery::mock();
        $socialUser->shouldReceive('getEmail')->once()->andReturn('social.continuation@example.test');
        $socialUser->shouldReceive('getId')->once()->andReturn('test-social-continuation');
        $socialUser->shouldReceive('getName')->once()->andReturn('Compte social de test');
        $socialUser->shouldReceive('getAvatar')->once()->andReturn(null);
        $driver = Mockery::mock();
        $driver->shouldReceive('user')->once()->andReturn($socialUser);
        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($driver);

        $this->get(route('login', ['continue' => 'demand']));
        $this->get(route('social.callback', ['provider' => 'google']))
            ->assertRedirect(route('demand.create', ['resume' => 1]))
            ->assertSessionMissing(DemandPublicationContinuation::SESSION_KEY);
        $this->assertAuthenticated();
        $this->assertDatabaseCount('ads', 0);
    }
}
