<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Support\Str;

/**
 * Les mêmes scénarios de déconnexion après inactivité, joués avec des sessions
 * en base comme en production : depuis le 19/11/2026, un signal d'arrière-plan
 * qui ne change rien ne réécrit plus la session
 * (App\Session\SignalAwareDatabaseSessionHandler). La garde d'inactivité doit
 * continuer à fonctionner à l'identique.
 */
class SessionInactivityDatabaseSessionsTest extends SessionInactivityFeatureTest
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['session.driver' => 'database']);
    }

    /**
     * Même scénario que le test parent, mais chaque appareil reçoit son propre
     * gestionnaire de sessions, comme chaque requête en production. Dans un
     * test, le gestionnaire en base est sinon partagé : il croit que la session
     * du second appareil existe déjà et ne l'enregistre pas.
     */
    public function test_another_device_has_an_independent_deadline(): void
    {
        $this->freezeTime();
        $user = User::factory()->create(['is_active' => true]);
        $this->actingAs($user)->getJson(route('auth.session-activity'))->assertOk()
            ->assertJsonPath('remaining_seconds', 10800);
        $firstId = session()->getId();
        $secondId = Str::random(40);
        $this->withCredentials();

        $this->travel(90)->minutes();
        $this->switchDevice();
        $this->withCookie(config('session.cookie'), $secondId)->getJson(route('auth.session-activity'))->assertOk()
            ->assertJsonPath('remaining_seconds', 10800);

        $this->travel(90)->minutes();
        $this->switchDevice();
        $this->withCookie(config('session.cookie'), $firstId)
            ->getJson(route('auth.session-activity'))->assertUnauthorized();

        $this->switchDevice();
        $this->actingAs($user)->withCookie(config('session.cookie'), $secondId)
            ->getJson(route('auth.session-activity'))->assertOk()->assertJsonPath('remaining_seconds', 5400);
    }

    private function switchDevice(): void
    {
        session()->flush();
        $this->app['session']->forgetDrivers();
    }
}
