<?php

namespace Tests\Feature\Auth;

use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

/**
 * Connexion sociale : Google est le seul fournisseur proposé.
 * Le retrait de Facebook est couvert par FacebookLoginRemovedTest.
 */
class SocialLoginInterfaceTest extends TestCase
{
    public function test_google_buttons_are_displayed_on_login_and_register(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee(route('social.redirect', ['provider' => 'google']), false)
            ->assertSee('Continuer avec Google');

        $this->get(route('register'))
            ->assertOk()
            ->assertSee(route('social.redirect', ['provider' => 'google']), false)
            ->assertSee("S'inscrire avec Google", false);
    }

    public function test_unconfigured_google_redirect_fails_gracefully_in_french(): void
    {
        config([
            'services.google.client_id' => null,
            'services.google.client_secret' => null,
        ]);

        $this->get(route('social.redirect', ['provider' => 'google']))
            ->assertRedirect(route('login'))
            ->assertSessionHas('error', 'La connexion avec Google est temporairement indisponible.');
    }

    public function test_configured_google_redirect_goes_through_socialite(): void
    {
        config([
            'services.google.client_id' => 'google-client-id',
            'services.google.client_secret' => 'google-client-secret',
            'services.google.redirect' => 'https://www.prokejem.fr/auth/google/callback',
        ]);

        $provider = Mockery::mock();
        $provider->shouldReceive('redirect')->once()->andReturn(redirect('https://google.example/authorize'));

        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

        $this->get(route('social.redirect', ['provider' => 'google']))
            ->assertRedirect('https://google.example/authorize');
    }

    public function test_unknown_providers_are_not_routed(): void
    {
        Socialite::shouldReceive('driver')->never();

        $this->get('/auth/twitter')->assertNotFound();
        $this->get('/auth/twitter/callback')->assertNotFound();
    }
}
