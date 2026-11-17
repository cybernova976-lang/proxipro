<?php

namespace Tests\Feature\Auth;

use Laravel\Socialite\Facades\Socialite;
use Tests\TestCase;

/**
 * La connexion et l'inscription via Facebook ont été retirées (accès à l'API
 * de connexion refusé par Facebook). Ces tests prouvent le retrait : plus de
 * route, plus de bouton, plus de configuration.
 *
 * Le partage vers Facebook et les liens vers une page Facebook ne sont pas
 * concernés.
 */
class FacebookLoginRemovedTest extends TestCase
{
    public function test_la_redirection_vers_facebook_repond_404(): void
    {
        Socialite::shouldReceive('driver')->never();

        $this->get('/auth/facebook')->assertNotFound();
    }

    public function test_le_retour_de_facebook_repond_404(): void
    {
        Socialite::shouldReceive('driver')->never();

        $this->get('/auth/facebook/callback')->assertNotFound();
        $this->get('/auth/facebook/callback?code=abc&state=xyz')->assertNotFound();

        $this->assertGuest();
    }

    public function test_la_configuration_facebook_n_existe_plus(): void
    {
        $this->assertNull(config('services.facebook'));
    }

    public function test_la_page_de_connexion_n_affiche_plus_de_bouton_facebook_meme_si_des_identifiants_sont_definis(): void
    {
        $this->definirDesIdentifiantsFacebook();

        $this->get(route('login'))
            ->assertOk()
            ->assertDontSee('Continuer avec Facebook')
            ->assertDontSee("S'inscrire avec Facebook", false)
            ->assertDontSee('auth/facebook', false)
            ->assertSee('Continuer avec Google');
    }

    public function test_la_page_d_inscription_n_affiche_plus_de_bouton_facebook_meme_si_des_identifiants_sont_definis(): void
    {
        $this->definirDesIdentifiantsFacebook();

        $this->get(route('register'))
            ->assertOk()
            ->assertDontSee("S'inscrire avec Facebook", false)
            ->assertDontSee('Continuer avec Facebook')
            ->assertDontSee('auth/facebook', false)
            ->assertSee("S'inscrire avec Google", false);
    }

    /**
     * Simule un environnement où les anciennes variables FACEBOOK_* seraient
     * encore renseignées : elles ne doivent plus rien réactiver.
     */
    private function definirDesIdentifiantsFacebook(): void
    {
        config([
            'services.facebook.client_id' => 'facebook-client-id',
            'services.facebook.client_secret' => 'facebook-client-secret',
            'services.facebook.redirect' => 'https://www.prokejem.fr/auth/facebook/callback',
        ]);
    }
}
