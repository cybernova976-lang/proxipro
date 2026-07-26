<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class FacebookSocialLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_new_user_can_create_an_account_with_facebook(): void
    {
        $socialUser = Mockery::mock();
        $socialUser->shouldReceive('getEmail')->once()->andReturn('nouveau.facebook@example.com');
        $socialUser->shouldReceive('getId')->once()->andReturn('facebook-user-123');
        $socialUser->shouldReceive('getName')->once()->andReturn('Utilisateur Facebook');
        $socialUser->shouldReceive('getAvatar')->once()->andReturn(null);

        $provider = Mockery::mock();
        $provider->shouldReceive('user')->once()->andReturn($socialUser);

        Socialite::shouldReceive('driver')->once()->with('facebook')->andReturn($provider);

        $this->get(route('social.callback', ['provider' => 'facebook']))
            ->assertRedirect(route('feed'))
            ->assertSessionHas('success', 'Bienvenue ! Votre compte a été créé via Facebook.');

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'nouveau.facebook@example.com',
            'provider' => 'facebook',
            'provider_id' => 'facebook-user-123',
            'name' => 'Utilisateur Facebook',
        ]);
    }

    public function test_facebook_redirect_requests_the_email_permission(): void
    {
        config([
            'services.facebook.client_id' => 'facebook-client-id',
            'services.facebook.client_secret' => 'facebook-client-secret',
            'services.facebook.redirect' => 'https://www.lunamars.fr/auth/facebook/callback',
        ]);

        $provider = Mockery::mock();
        $provider->shouldReceive('scopes')->once()->with(['email'])->andReturnSelf();
        $provider->shouldReceive('redirect')->once()->andReturn(redirect('https://facebook.example/authorize'));

        Socialite::shouldReceive('driver')->once()->with('facebook')->andReturn($provider);

        $this->get(route('social.redirect', ['provider' => 'facebook']))
            ->assertRedirect('https://facebook.example/authorize');
    }
}
