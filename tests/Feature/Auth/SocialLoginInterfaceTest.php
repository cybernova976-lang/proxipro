<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

class SocialLoginInterfaceTest extends TestCase
{
    public function test_facebook_buttons_are_hidden_until_the_provider_is_configured(): void
    {
        config([
            'services.facebook.client_id' => null,
            'services.facebook.client_secret' => null,
        ]);

        $this->get(route('login'))
            ->assertOk()
            ->assertDontSee('Continuer avec Facebook');

        $this->get(route('register'))
            ->assertOk()
            ->assertDontSee("S'inscrire avec Facebook");
    }

    public function test_facebook_buttons_are_displayed_when_the_provider_is_configured(): void
    {
        config([
            'services.facebook.client_id' => 'facebook-client-id',
            'services.facebook.client_secret' => 'facebook-client-secret',
            'services.facebook.redirect' => 'https://www.lunamars.fr/auth/facebook/callback',
        ]);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee(route('social.redirect', ['provider' => 'facebook']), false)
            ->assertSee('Continuer avec Facebook');

        $this->get(route('register'))
            ->assertOk()
            ->assertSee(route('social.redirect', ['provider' => 'facebook']), false)
            ->assertSee("S'inscrire avec Facebook", false);
    }

    public function test_unconfigured_facebook_redirect_fails_gracefully_in_french(): void
    {
        config([
            'services.facebook.client_id' => null,
            'services.facebook.client_secret' => null,
        ]);

        $this->get(route('social.redirect', ['provider' => 'facebook']))
            ->assertRedirect(route('login'))
            ->assertSessionHas('error', 'La connexion avec Facebook est temporairement indisponible.');
    }
}
