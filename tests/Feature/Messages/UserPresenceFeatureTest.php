<?php

namespace Tests\Feature\Messages;

use App\Models\Conversation;
use App\Models\User;
use App\Services\UserPresence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class UserPresenceFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function pair(): array
    {
        $viewer = User::factory()->create(['is_active' => true]);
        $other = User::factory()->create(['is_active' => true]);

        return [$viewer, $other, Conversation::getOrCreate($viewer->id, $other->id, 'Réparation')];
    }

    private function state($conversation, $viewer): array
    {
        return app(UserPresence::class)->forConversations(collect([$conversation->fresh()]), $viewer->id)[$conversation->id];
    }

    public function test_authenticated_account_without_a_visible_page_is_not_marked_online(): void
    {
        [$viewer, $other, $conversation] = $this->pair();
        $this->actingAs($viewer)->get(route('messages.show', $conversation))->assertOk()->assertSee('Hors ligne')->assertDontSee('>En ligne<', false);
        $this->getJson(route('messages.poll', $conversation))->assertOk()->assertJsonPath('presence.state', 'offline');
        $this->assertDatabaseCount('user_presence_leases', 0);
    }

    public function test_heartbeat_registers_only_authenticated_user_and_does_not_store_raw_session(): void
    {
        [$viewer, $other, $conversation] = $this->pair();
        $tab = (string) Str::uuid();
        $this->actingAs($other)->postJson(route('presence.heartbeat'), ['tab_id' => $tab, 'sequence' => 1, 'active' => true, 'user_id' => $viewer->id])
            ->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $row = DB::table('user_presence_leases')->first();
        $this->assertSame($other->id, (int) $row->user_id);
        $this->assertSame(64, strlen($row->session_key));
        $this->assertNotSame(session()->getId(), $row->session_key);
        $this->assertSame('online', $this->state($conversation, $viewer)['state']);
        $this->actingAs($viewer)->get(route('messages.show', $conversation))->assertOk()->assertSee('>En ligne<', false)->assertSee('Réparation')
            ->assertViewHas('conversations', fn ($conversations) => $conversations->count() === 1);
        $this->getJson(route('messages.poll', $conversation))->assertOk()->assertJsonPath('presence.state', 'online');
    }

    public function test_a_lost_connection_expires_without_logout_and_background_poll_does_not_renew_it(): void
    {
        $this->freezeTime();
        [$viewer, $other, $conversation] = $this->pair();
        app(UserPresence::class)->heartbeat($other, 'device', (string) Str::uuid(), 1, true);
        $this->travel(74)->seconds();
        $this->assertSame('online', $this->state($conversation, $viewer)['state']);
        $this->actingAs($other)->getJson(route('messages.poll', $conversation))->assertOk();
        $this->travel(2)->seconds();
        $this->assertSame('offline', $this->state($conversation, $viewer)['state']);
        $this->assertTrue($other->fresh()->is_active);
    }

    public function test_closing_one_tab_does_not_disconnect_another_visible_tab(): void
    {
        [$viewer, $other, $conversation] = $this->pair();
        $presence = app(UserPresence::class);
        $first = (string) Str::uuid();
        $second = (string) Str::uuid();
        $presence->heartbeat($other, 'same-session', $first, 1, true);
        $presence->heartbeat($other, 'same-session', $second, 1, true);
        $presence->heartbeat($other, 'same-session', $first, 2, false);
        $this->assertSame('online', $this->state($conversation, $viewer)['state']);
        $presence->heartbeat($other, 'same-session', $second, 2, false);
        $this->assertSame('offline', $this->state($conversation, $viewer)['state']);
        $presence->heartbeat($other, 'same-session', $first, 3, true);
        $this->assertSame('online', $this->state($conversation, $viewer)['state']);
    }

    public function test_delayed_heartbeat_cannot_overwrite_newer_closure_or_extend_a_replayed_lease(): void
    {
        $this->freezeTime();
        [$viewer, $other, $conversation] = $this->pair();
        $presence = app(UserPresence::class);
        $tab = (string) Str::uuid();
        $presence->heartbeat($other, 'device', $tab, 2, false);
        $presence->heartbeat($other, 'device', $tab, 1, true);
        $this->assertSame('offline', $this->state($conversation, $viewer)['state']);
        $presence->heartbeat($other, 'device', $tab, 3, true);
        $this->travel(60)->seconds();
        $presence->heartbeat($other, 'device', $tab, 3, true);
        $this->travel(16)->seconds();
        $this->assertSame('offline', $this->state($conversation, $viewer)['state']);
    }

    public function test_logout_releases_all_tabs_of_this_session_but_not_another_device(): void
    {
        [$viewer, $other, $conversation] = $this->pair();
        $presence = app(UserPresence::class);
        $this->actingAs($other)->postJson(route('presence.heartbeat'), ['tab_id' => (string) Str::uuid(), 'sequence' => 1, 'active' => true])->assertOk();
        $presence->heartbeat($other, 'other-device', (string) Str::uuid(), 1, true);
        $this->withCookie(config('session.cookie'), session()->getId())->post(route('logout'))->assertRedirect();
        $this->assertDatabaseCount('user_presence_leases', 1);
        $this->assertSame('online', $this->state($conversation, $viewer)['state']);
        $presence->forgetSession($other->id, 'other-device');
        $this->assertSame('offline', $this->state($conversation, $viewer)['state']);
    }

    public function test_presence_lookup_is_limited_to_owned_conversations_and_no_personal_data_is_returned(): void
    {
        [$viewer, $other, $conversation] = $this->pair();
        $stranger = User::factory()->create(['is_active' => true]);
        $outside = Conversation::getOrCreate($other->id, $stranger->id);
        app(UserPresence::class)->heartbeat($other, 'device', (string) Str::uuid(), 1, true);
        $response = $this->actingAs($viewer)->postJson(route('presence.heartbeat'), ['tab_id' => (string) Str::uuid(), 'sequence' => 1, 'active' => true, 'conversation_ids' => [$conversation->id, $outside->id, 9999]])
            ->assertOk()->assertJsonPath('presences.'.$conversation->id.'.state', 'online');
        $this->assertSame([(string) $conversation->id], array_map('strval', array_keys($response->json('presences'))));
        foreach (['email', 'session_key', 'user_id', 'expires_at', 'last_seen_at'] as $field) {
            $this->assertStringNotContainsString('"'.$field.'"', $response->getContent());
        }
        $this->assertSame([], app(UserPresence::class)->forConversations(collect([$outside]), $viewer->id));
    }

    public function test_blocked_inactive_and_deleted_users_never_expose_online_presence(): void
    {
        [$viewer, $other, $conversation] = $this->pair();
        app(UserPresence::class)->heartbeat($other, 'device', (string) Str::uuid(), 1, true);
        $this->actingAs($viewer)->postJson(route('messages.block', $conversation))->assertOk();
        $this->getJson(route('messages.poll', $conversation))->assertOk()->assertJsonPath('presence.state', 'hidden');
        $this->postJson(route('messages.unblock', $conversation))->assertOk();
        $other->forceFill(['is_active' => false])->save();
        $this->assertSame('unavailable', $this->state($conversation, $viewer)['state']);
        $other->delete();
        $this->assertSame('unavailable', $this->state($conversation, $viewer)['state']);
    }

    public function test_guests_inactive_accounts_and_invalid_heartbeats_are_rejected(): void
    {
        $body = ['tab_id' => (string) Str::uuid(), 'sequence' => 1, 'active' => true];
        $this->postJson(route('presence.heartbeat'), $body)->assertUnauthorized();
        $user = User::factory()->create(['is_active' => false]);
        $this->actingAs($user)->postJson(route('presence.heartbeat'), $body)->assertForbidden();
        $user->forceFill(['is_active' => true])->save();
        foreach ([['tab_id' => 'invalid'], ['sequence' => 0], ['active' => 'anything'], ['conversation_ids' => range(1, 31)]] as $invalid) {
            $this->actingAs($user->fresh())->postJson(route('presence.heartbeat'), array_replace($body, $invalid))->assertUnprocessable();
        }
        $this->assertDatabaseCount('user_presence_leases', 0);
    }

    public function test_guest_pages_do_not_send_presence_signals_and_inbox_displays_real_offline_state(): void
    {
        $this->get(route('login'))->assertOk()->assertDontSee('data-user-presence', false);
        [$viewer, $other, $conversation] = $this->pair();
        $this->actingAs($viewer)->get(route('messages.index'))->assertOk()->assertSee('data-user-presence', false)->assertSee('Hors ligne')->assertDontSee('>En ligne<', false);
    }
}
