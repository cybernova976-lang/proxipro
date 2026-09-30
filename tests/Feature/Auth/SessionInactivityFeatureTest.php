<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SessionInactivityFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function signIn(): User
    {
        $this->freezeTime();
        $user = User::factory()->create(['is_active' => true]);
        $this->actingAs($user)->getJson(route('auth.session-activity'))->assertOk()
            ->assertJsonPath('remaining_seconds', 3600);
        $this->withCredentials()->withCookie(config('session.cookie'), session()->getId());

        return $user;
    }

    public function test_background_checks_do_not_extend_the_one_hour_deadline(): void
    {
        $this->signIn();
        $this->travel(59)->minutes();
        $this->getJson(route('auth.session-activity'))->assertOk()->assertJsonPath('remaining_seconds', 60);
        $this->travel(1)->minutes();
        $this->getJson(route('auth.session-activity'))->assertUnauthorized()->assertJsonPath('reason', 'session_inactive');
        $this->assertGuest();
    }

    public function test_expired_html_request_redirects_to_login_and_does_not_execute_action(): void
    {
        $this->signIn();
        $this->travel(1)->hours();
        $this->get(route('feed'))->assertRedirect(route('login'))
            ->assertSessionHas('error', 'Vous avez été déconnecté après une heure d’inactivité. Veuillez vous reconnecter.');
        $this->assertGuest();
    }

    public function test_real_interaction_extends_the_shared_deadline_but_delayed_reports_do_not_add_idle_time(): void
    {
        $this->signIn();
        $this->travel(30)->minutes();
        $this->postJson(route('auth.session-activity'), ['age_ms' => 15000])->assertOk()
            ->assertJsonPath('remaining_seconds', 3585);
        $this->getJson(route('auth.session-activity'))->assertOk()->assertJsonPath('remaining_seconds', 3585);
        $this->travel(59)->minutes();
        $this->getJson(route('auth.session-activity'))->assertOk()->assertJsonPath('remaining_seconds', 45);
        $this->travel(45)->seconds();
        $this->getJson(route('auth.session-activity'))->assertUnauthorized();
    }

    public function test_first_click_after_one_hour_cannot_revive_session(): void
    {
        $this->signIn();
        $before = DB::table('session_user_activity')->value('last_interaction_at');
        $this->travel(61)->minutes();
        $this->postJson(route('auth.session-activity'), ['age_ms' => 0])->assertUnauthorized();
        $this->assertSame($before, DB::table('session_user_activity')->value('last_interaction_at'));
    }

    public function test_missing_guard_record_cannot_restart_an_existing_session(): void
    {
        $this->signIn();
        DB::table('session_user_activity')->delete();
        $this->getJson(route('auth.session-activity'))->assertUnauthorized();
    }

    public function test_legacy_remember_cookie_cannot_automatically_reauthenticate(): void
    {
        $user = User::factory()->create(['remember_token' => 'old-persistent-token']);
        $guard = Auth::guard('web');
        $cookieName = $guard->getRecallerName();
        $this->withCredentials()->withCookie($cookieName, $user->id.'|old-persistent-token|'.$guard->hashPasswordForCookie($user->getAuthPassword()))
            ->getJson(route('auth.session-activity'))->assertUnauthorized()->assertJsonPath('reason', 'session_inactive');
        $this->assertGuest();
    }

    public function test_activity_endpoint_requires_authentication_and_valid_report(): void
    {
        $this->getJson(route('auth.session-activity'))->assertUnauthorized();
        $this->signIn();
        $this->postJson(route('auth.session-activity'), ['age_ms' => -1])->assertUnprocessable();
        $this->postJson(route('auth.session-activity'), ['age_ms' => 3600000])->assertUnprocessable();
    }

    public function test_password_login_starts_a_new_deadline_and_ignores_remember_checkbox_from_old_forms(): void
    {
        $this->freezeTime();
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->post(route('login.attempt'), ['email' => $user->email, 'password' => 'password', 'remember' => '1'])
            ->assertRedirect(route('feed'))->assertCookieMissing(Auth::guard('web')->getRecallerName());
        $this->assertAuthenticatedAs($user);
        $this->getJson(route('auth.session-activity'))->assertOk()->assertJsonPath('remaining_seconds', 3600);
        $this->travel(1)->hours();
        $this->getJson(route('auth.session-activity'))->assertUnauthorized();
    }

    public function test_presence_heartbeats_do_not_extend_the_session_deadline(): void
    {
        $this->signIn();
        $tab = (string) \Illuminate\Support\Str::uuid();
        $this->travel(59)->minutes();
        $this->postJson(route('presence.heartbeat'), ['tab_id' => $tab, 'sequence' => 1, 'active' => true])->assertOk();
        $this->travel(1)->minutes();
        $this->postJson(route('presence.heartbeat'), ['tab_id' => $tab, 'sequence' => 2, 'active' => true])->assertUnauthorized();
        $this->assertDatabaseCount('user_presence_leases', 0);
    }

    public function test_opening_another_page_does_not_reset_inactivity_and_includes_guard(): void
    {
        $this->signIn();
        $this->travel(20)->minutes();
        $this->get(route('feed'))->assertOk()->assertSee('data-session-inactivity', false)
            ->assertSee('data-remaining="2400"', false);
        $this->assertStringContainsString('no-store', $this->getJson(route('auth.session-activity'))->headers->get('Cache-Control'));
    }

    public function test_explicit_browser_navigation_counts_but_cannot_revive_expired_session(): void
    {
        $this->signIn();
        $this->travel(20)->minutes();
        $this->withHeaders(['Sec-Fetch-User' => '?1', 'Sec-Fetch-Mode' => 'navigate'])
            ->get(route('feed'))->assertOk()->assertSee('data-remaining="3600"', false);
        $this->travel(1)->hours();
        $this->get(route('feed'))->assertRedirect(route('login'));
    }

    public function test_another_device_has_an_independent_deadline(): void
    {
        $user = $this->signIn();
        $firstId = session()->getId();
        $secondId = \Illuminate\Support\Str::random(40);
        $this->travel(30)->minutes();
        session()->flush();
        $this->withCookie(config('session.cookie'), $secondId)->getJson(route('auth.session-activity'))->assertOk()
            ->assertJsonPath('remaining_seconds', 3600);
        $this->travel(30)->minutes();
        session()->flush();
        $this->withCookie(config('session.cookie'), $firstId)
            ->getJson(route('auth.session-activity'))->assertUnauthorized();
        session()->flush();
        $this->actingAs($user)->withCookie(config('session.cookie'), $secondId)
            ->getJson(route('auth.session-activity'))->assertOk()->assertJsonPath('remaining_seconds', 1800);
    }
}
