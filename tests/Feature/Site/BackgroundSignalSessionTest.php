<?php

namespace Tests\Feature\Site;

use App\Models\User;
use App\Services\UserPresence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

/**
 * Constaté le 19/11/2026 dans le journal des requêtes de production : un
 * signal d'arrière-plan avait lu la session avant qu'une action y inscrive son
 * message de confirmation, puis l'avait réécrite juste après, sans le message.
 *
 * Ces tests utilisent des sessions en base, comme la production (le serveur
 * local et les autres tests ne reproduisent pas cette collision).
 */
class BackgroundSignalSessionTest extends TestCase
{
    use RefreshDatabase;

    private const MESSAGE = 'Commande clôturée : aucun argent n’a bougé.';

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
        config(['session.driver' => 'database']);
    }

    public function test_a_signal_running_during_an_action_does_not_erase_its_confirmation(): void
    {
        $id = $this->openSession();

        // Pendant que le signal de présence est traité, une autre requête (l'action
        // du membre) termine et inscrit son message de confirmation dans la session.
        $presence = Mockery::mock(UserPresence::class);
        $presence->shouldReceive('heartbeat')->once()->andReturnUsing(fn () => $this->anotherRequestWrites($id, 'info', self::MESSAGE));
        $presence->shouldReceive('forConversations')->andReturn([]);
        $this->app->instance(UserPresence::class, $presence);

        $this->postJson(route('presence.heartbeat'), ['tab_id' => (string) Str::uuid(), 'sequence' => 1, 'active' => true])->assertOk();
        $this->app->forgetInstance(UserPresence::class);

        // Le signal n'a pas réécrit la session : le message y est toujours…
        $this->assertSame(self::MESSAGE, $this->storedSession($id)['info'] ?? null);
        // …et la page suivante l'affiche.
        $this->get(route('saved-searches.index'))->assertOk()->assertSee(self::MESSAGE);
    }

    public function test_a_signal_keeps_the_session_alive_without_rewriting_it(): void
    {
        $id = $this->openSession();
        $payload = DB::table('sessions')->where('id', $id)->value('payload');
        DB::table('sessions')->where('id', $id)->update(['last_activity' => now()->subMinutes(30)->timestamp]);

        $this->getJson(route('auth.session-activity'))->assertOk();
        $this->postJson(route('auth.session-activity'), ['age_ms' => 0])->assertOk();
        $this->postJson(route('presence.heartbeat'), ['tab_id' => (string) Str::uuid(), 'sequence' => 1, 'active' => true])->assertOk();

        // Contenu intact, à l'octet près ; session notée comme toujours active.
        $this->assertSame($payload, DB::table('sessions')->where('id', $id)->value('payload'));
        $this->assertGreaterThanOrEqual(now()->subMinute()->timestamp, (int) DB::table('sessions')->where('id', $id)->value('last_activity'));
    }

    public function test_a_signal_that_really_changes_the_session_still_writes_it(): void
    {
        $id = $this->openSession();
        $this->assertArrayNotHasKey('usage_analytics', $this->storedSession($id));

        // Première page vue du jour : le signal de mesure note le jour dans la session.
        $this->withHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/130.0 Safari/537.36')
            ->postJson(route('usage.store'), ['event_name' => 'page_view', 'route_name' => 'feed', 'app_mode' => 'browser'])
            ->assertNoContent();

        $this->assertSame(today()->toDateString(), $this->storedSession($id)['usage_analytics']['last_session_day'] ?? null);
    }

    public function test_an_ordinary_page_still_consumes_the_message_once(): void
    {
        $id = $this->openSession();
        $this->anotherRequestWrites($id, 'info', self::MESSAGE);

        $this->get(route('saved-searches.index'))->assertOk()->assertSee(self::MESSAGE);
        $this->postJson(route('auth.session-activity'), ['age_ms' => 0])->assertOk();
        $this->get(route('saved-searches.index'))->assertOk()->assertDontSee(self::MESSAGE);
    }

    /** Ouvre une session en base pour un membre connecté et renvoie son identifiant. */
    private function openSession(): string
    {
        $member = User::factory()->create(['city' => 'Mamoudzou', 'country' => 'Mayotte', 'postal_code' => '97600', 'is_active' => true]);
        $this->actingAs($member)->get(route('saved-searches.index'))->assertOk();

        $id = session()->getId();
        $this->withCredentials()->withCookie(config('session.cookie'), $id);
        $this->assertNotNull(DB::table('sessions')->where('id', $id)->value('payload'), 'La session doit exister en base.');

        return $id;
    }

    /** @return array<string, mixed> contenu de la session tel qu'il est en base */
    private function storedSession(string $id): array
    {
        return unserialize(base64_decode((string) DB::table('sessions')->where('id', $id)->value('payload')));
    }

    /** Ce qu'une autre requête, simultanée, laisse en base en inscrivant un message de retour. */
    private function anotherRequestWrites(string $id, string $key, string $message): void
    {
        $data = $this->storedSession($id);
        $data[$key] = $message;
        $data['_flash'] = ['old' => [$key], 'new' => []];

        DB::table('sessions')->where('id', $id)->update(['payload' => base64_encode(serialize($data))]);
    }
}
