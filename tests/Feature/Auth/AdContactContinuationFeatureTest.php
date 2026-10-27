<?php

namespace Tests\Feature\Auth;

use App\Mail\EmailVerificationCode;
use App\Models\Ad;
use App\Models\Setting;
use App\Models\User;
use App\Support\DemandPublicationContinuation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class AdContactContinuationFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function publication(array $overrides = []): Ad
    {
        return Ad::create(array_merge([
            'user_id' => User::factory()->create(['is_active' => true])->id,
            'title' => 'Installation de luminaires à domicile',
            'description' => 'Je propose mes services pour installer vos luminaires et vérifier leur branchement.',
            'category' => 'Électricien', 'location' => 'Mamoudzou',
            'service_type' => 'offre', 'visibility' => 'public', 'status' => 'active',
        ], $overrides));
    }

    public function test_login_and_registration_keep_the_selected_publication_without_sending_a_message(): void
    {
        $ad = $this->publication();
        $user = User::factory()->create(['password' => Hash::make('password')]);
        $this->get(route('login', ['continue' => 'ad', 'ad' => $ad->id]))->assertOk()
            ->assertSee($ad->title)->assertSessionHas(DemandPublicationContinuation::AD_SESSION_KEY, $ad->id);
        $this->get(route('register'))->assertOk()->assertSee($ad->title);
        $this->post(route('login.attempt'), ['email' => $user->email, 'password' => 'incorrect'])
            ->assertSessionHasErrors('email')->assertSessionHas(DemandPublicationContinuation::AD_SESSION_KEY, $ad->id);
        $this->post(route('login.attempt'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('ads.show', $ad).'#ad-contact-author')
            ->assertSessionMissing(DemandPublicationContinuation::AD_SESSION_KEY);
        $this->assertDatabaseCount('messages', 0);
        $this->assertDatabaseCount('conversations', 0);
    }

    public function test_registration_returns_to_the_selected_publication(): void
    {
        Mail::fake();
        Setting::set('email_verification_enabled', '0');
        $ad = $this->publication();
        $this->get(route('register', ['continue' => 'ad', 'ad' => $ad->id]))->assertOk();
        $this->post(route('register'), [
            'account_type' => 'particulier', 'firstname' => 'Amina', 'lastname' => 'Martin',
            'email' => 'ad.contact@example.test', 'country' => 'France', 'city' => 'Paris',
            'password' => 'mot-de-passe-solide-2026', 'password_confirmation' => 'mot-de-passe-solide-2026',
            'terms' => '1', 'website_url' => '',
        ])->assertRedirect(route('ads.show', $ad).'#ad-contact-author')
            ->assertSessionMissing(DemandPublicationContinuation::AD_SESSION_KEY);
        $this->assertAuthenticated();
        $this->assertDatabaseCount('messages', 0);
    }

    public function test_private_expired_inactive_and_malformed_targets_are_never_kept(): void
    {
        $private = $this->publication(['visibility' => 'private']);
        $expired = $this->publication(['expires_at' => now()->subMinute()]);
        $inactive = $this->publication(['status' => 'inactive']);
        $ownerInactive = $this->publication();
        $ownerInactive->user->forceFill(['is_active' => false])->save();
        foreach ([$private->id, $expired->id, $inactive->id, $ownerInactive->id, 999999, 'https://example.org', -1] as $target) {
            $this->get(route('login', ['continue' => 'ad', 'ad' => $target]))->assertOk()
                ->assertSessionMissing(DemandPublicationContinuation::AD_SESSION_KEY);
        }
    }

    public function test_new_intention_replaces_the_old_one_and_expired_publication_falls_back_to_feed(): void
    {
        $ad = $this->publication();
        $user = User::factory()->create(['password' => Hash::make('password')]);
        $this->get(route('login', ['continue' => 'provider']))->assertOk();
        $this->get(route('login', ['continue' => 'ad', 'ad' => $ad->id]))->assertOk()
            ->assertSessionMissing(DemandPublicationContinuation::PROVIDER_SESSION_KEY);
        $this->get(route('login', ['continue' => 'demand']))->assertOk()
            ->assertSessionMissing(DemandPublicationContinuation::AD_SESSION_KEY);
        $this->get(route('login', ['continue' => 'ad', 'ad' => $ad->id]))->assertOk()
            ->assertSessionMissing(DemandPublicationContinuation::SESSION_KEY);
        $ad->update(['status' => 'inactive']);
        $this->withSession(['url.intended' => 'https://example.org'])
            ->post(route('login.attempt'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('feed'));
    }

    public function test_email_verification_preserves_the_publication_and_still_requires_verification(): void
    {
        Mail::fake();
        Setting::set('email_verification_enabled', '1');
        $ad = $this->publication();
        $user = User::factory()->unverified()->create(['password' => Hash::make('password')]);
        $this->get(route('login', ['continue' => 'ad', 'ad' => $ad->id]))->assertOk();
        $this->post(route('login.attempt'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('verification.code.show', ['email' => $user->email]))
            ->assertSessionHas(DemandPublicationContinuation::AD_SESSION_KEY, $ad->id);
        $this->assertGuest();
        $code = Mail::sent(EmailVerificationCode::class)->first()->code;
        $this->post(route('verification.code.verify'), ['email' => $user->email, 'code' => $code])
            ->assertRedirect(route('login'));
        $this->post(route('login.attempt'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('ads.show', $ad).'#ad-contact-author');
        $this->assertDatabaseCount('messages', 0);
    }

    public function test_social_login_returns_to_the_same_publication(): void
    {
        $ad = $this->publication();
        $socialUser = Mockery::mock();
        $socialUser->shouldReceive('getEmail')->once()->andReturn('social.ad.contact@example.test');
        $socialUser->shouldReceive('getId')->once()->andReturn('social-ad-contact');
        $socialUser->shouldReceive('getName')->once()->andReturn('Compte social de test');
        $socialUser->shouldReceive('getAvatar')->once()->andReturn(null);
        $driver = Mockery::mock();
        $driver->shouldReceive('user')->once()->andReturn($socialUser);
        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($driver);
        $this->get(route('login', ['continue' => 'ad', 'ad' => $ad->id]))->assertOk();
        $this->get(route('social.callback', ['provider' => 'google']))
            ->assertRedirect(route('ads.show', $ad).'#ad-contact-author');
        $this->assertDatabaseCount('messages', 0);
    }
}
