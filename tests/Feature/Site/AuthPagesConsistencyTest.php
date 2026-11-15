<?php

namespace Tests\Feature\Site;

use Tests\TestCase;

/**
 * Les pages de connexion et d'inscription sont le premier écran après la page
 * d'accueil : elles portent la même couleur de marque (indigo), et rien qui
 * s'affiche mal selon l'appareil.
 */
class AuthPagesConsistencyTest extends TestCase
{
    public function test_login_and_register_use_the_brand_indigo_instead_of_blue(): void
    {
        foreach (['auth/login', 'auth/register', 'auth/passwords/reset'] as $view) {
            $source = file_get_contents(resource_path("views/{$view}.blade.php"));

            $this->assertDoesNotMatchRegularExpression('/\b(blue|sky)-\d{2,3}\b/', $source, "{$view} utilise encore le bleu.");
            $this->assertStringNotContainsString('#3b82f6', $source, "{$view} utilise encore le bleu.");
        }

        $this->get(route('login'))->assertOk()->assertSee('bg-indigo-600', false);
        $this->get(route('register'))->assertOk()->assertSee('bg-indigo-600', false);
    }

    /** Windows n'affiche pas les drapeaux : « 🇾🇹 Mayotte » devenait « YT Mayotte » dans la liste. */
    public function test_register_country_list_has_no_flag_emoji(): void
    {
        $html = $this->get(route('register'))->assertOk()->getContent();

        $this->assertSame(1, preg_match('/<option value="Mayotte"[^>]*>\s*Mayotte\s*<\/option>/u', $html));
        // Indicateurs régionaux Unicode : les deux lettres qui composent un drapeau.
        $this->assertSame(0, preg_match('/[\x{1F1E6}-\x{1F1FF}]/u', $html));
    }
}
