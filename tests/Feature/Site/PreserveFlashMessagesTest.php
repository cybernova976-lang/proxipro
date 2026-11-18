<?php

namespace Tests\Feature\Site;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Un message de retour ne doit pas disparaître parce qu'un signal
 * d'arrière-plan (présence, activité de session) est passé entre l'action et
 * le rechargement de la page. Constaté en local le 18/11/2026 : l'alerte était
 * créée, mais « Alerte enregistrée… » ne s'affichait pas.
 */
class PreserveFlashMessagesTest extends TestCase
{
    use RefreshDatabase;

    private const MESSAGE = 'Alerte enregistrée : vous recevrez une notification dès qu’une offre sera publiée à Mayotte.';

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
    }

    public function test_background_signals_do_not_consume_the_confirmation_message(): void
    {
        $this->actingAs($this->member());
        $this->createAlert();

        // Trois signaux d'arrière-plan arrivent avant le rechargement de la page.
        $this->postJson(route('presence.heartbeat'), ['tab_id' => (string) Str::uuid(), 'sequence' => 1, 'active' => true])->assertOk();
        $this->postJson(route('auth.session-activity'), ['age_ms' => 0])->assertOk();
        $this->postJson(route('presence.heartbeat'), ['tab_id' => (string) Str::uuid(), 'sequence' => 1, 'active' => true])->assertOk();

        $this->get(route('feed'))->assertOk()->assertSee(self::MESSAGE);
    }

    public function test_the_message_is_still_shown_only_once(): void
    {
        $this->actingAs($this->member());
        $this->createAlert();

        $this->get(route('feed'))->assertOk()->assertSee(self::MESSAGE);
        // Un signal d'arrière-plan ne le fait pas revenir, la page suivante non plus.
        $this->postJson(route('auth.session-activity'), ['age_ms' => 0])->assertOk();
        $this->get(route('feed'))->assertOk()->assertDontSee(self::MESSAGE);
    }

    private function createAlert(): void
    {
        $this->from(route('feed'))
            ->post(route('saved-searches.store'), ['type' => 'offres', 'territory' => '976', 'country' => 'Mayotte'])
            ->assertRedirect(route('feed'));
    }

    private function member(): User
    {
        return User::factory()->create(['city' => 'Mamoudzou', 'country' => 'Mayotte', 'postal_code' => '97600', 'is_active' => true]);
    }
}
