<?php

namespace Tests\Feature\Site;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Chaque limite de fréquence a son propre compteur.
 *
 * Sans préfixe, Laravel range toutes les limites « throttle:N,M » d'un membre
 * sous la même clé (son identifiant) : les appels automatiques du site
 * (présence, mesure d'audience, synchronisation des notifications) épuisaient
 * alors les petites limites. Constaté en production : « Too Many Attempts » au
 * premier clic sur « Tester » les notifications.
 */
class ThrottleIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_background_calls_do_not_exhaust_the_push_test_limit(): void
    {
        $user = User::factory()->create();

        // Douze appels automatiques dans la même minute, comme en navigation réelle.
        for ($i = 0; $i < 12; $i++) {
            $this->actingAs($user)->postJson(route('presence.heartbeat'));
        }

        // Le test des notifications répond normalement (422 : aucun appareil abonné), pas « trop de tentatives ».
        $this->actingAs($user)->postJson(route('push-subscriptions.test'))->assertStatus(422);
    }

    public function test_a_visitor_can_still_open_registration_after_browsing(): void
    {
        for ($i = 0; $i < 8; $i++) {
            $this->postJson(route('usage.store'), []);
        }

        $this->get(route('register'))->assertOk();
    }

    public function test_every_throttle_declares_its_own_counter(): void
    {
        $files = [base_path('routes/web.php'), base_path('routes/api.php'),
            app_path('Http/Controllers/Auth/RegisterController.php'),
            app_path('Http/Controllers/Auth/VerificationController.php')];
        $prefixes = [];

        foreach ($files as $file) {
            $source = file_get_contents($file);
            $this->assertDoesNotMatchRegularExpression("/throttle:\\d+,\\d+'/", $source, basename($file).' : limite sans compteur propre.');
            preg_match_all("/throttle:\\d+,\\d+,([a-z0-9.:-]+)'/", $source, $matches);
            $prefixes = array_merge($prefixes, $matches[1]);
        }

        $this->assertNotEmpty($prefixes);
        $this->assertSame(count($prefixes), count(array_unique($prefixes)), 'Deux limites partagent le même compteur.');
    }

    /** Message affiché quand une limite est atteinte : en français. */
    public function test_too_many_attempts_message_is_french(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($user)->postJson(route('push-subscriptions.test'));
        }

        $this->actingAs($user)->postJson(route('push-subscriptions.test'))
            ->assertStatus(429)
            ->assertJsonPath('message', 'Trop de tentatives. Patientez une minute avant de réessayer.');
    }
}
