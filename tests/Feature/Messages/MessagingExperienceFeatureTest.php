<?php

namespace Tests\Feature\Messages;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Notifications\NewMessageNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class MessagingExperienceFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function pair(): array
    {
        $buyer = User::factory()->create(['is_active' => true]);
        $seller = User::factory()->create(['name' => 'Camille Prestataire', 'is_active' => true]);

        return [$buyer, $seller, Conversation::getOrCreate($buyer->id, $seller->id, 'Réparation de la cuisine')];
    }

    public function test_latest_fifty_messages_open_in_chronological_order_and_older_messages_are_available(): void
    {
        [$buyer, $seller, $conversation] = $this->pair();
        $rows = collect(range(1, 75))->map(fn ($i) => Message::create(['conversation_id' => $conversation->id, 'sender_id' => $seller->id, 'content' => 'Message numéro '.$i]));
        $this->actingAs($buyer)->get(route('messages.show', $conversation))
            ->assertOk()->assertSee('Afficher les messages précédents')->assertSeeInOrder(['Message numéro 26', 'Message numéro 75'])
            ->assertDontSee('Message numéro 1</div>', false)->assertDontSee('En ligne')->assertDontSee('<main class=" main-content-with-sidebar', false);
        $this->assertDatabaseHas('messages', ['id' => $rows->first()->id, 'is_read' => true, 'edited_at' => null]);
        $this->getJson(route('messages.poll', [$conversation, 'before_id' => $rows[25]->id]))->assertOk()
            ->assertJsonCount(25, 'messages')->assertJsonPath('messages.0.content', 'Message numéro 1')->assertJsonPath('has_more', false);
    }

    public function test_message_send_is_idempotent_and_does_not_expose_sender_personal_data(): void
    {
        Notification::fake();
        [$buyer, $seller, $conversation] = $this->pair();
        $body = ['conversation_id' => $conversation->id, 'content' => '  Bonjour, disponible demain ?  ', 'client_token' => (string) Str::uuid()];
        $first = $this->actingAs($buyer)->postJson(route('messages.store'), $body)->assertOk()->assertJsonPath('message.content', 'Bonjour, disponible demain ?');
        $this->postJson(route('messages.store'), $body)->assertOk()->assertJsonPath('message.id', $first->json('message.id'));
        $this->assertDatabaseCount('messages', 1);
        Notification::assertSentToTimes($seller, NewMessageNotification::class, 1);
        $this->assertArrayNotHasKey('sender', $first->json('message'));
        $this->assertArrayNotHasKey('email', $first->json('message'));
    }

    public function test_failed_notification_does_not_make_a_saved_message_look_unsent(): void
    {
        [$buyer, $seller, $conversation] = $this->pair();
        Notification::shouldReceive('send')->once()->andThrow(new \RuntimeException('Transport unavailable'));
        $this->actingAs($buyer)->postJson(route('messages.store'), ['conversation_id' => $conversation->id, 'content' => 'Bonjour'])->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseCount('messages', 1);
    }

    public function test_empty_or_oversized_messages_are_rejected(): void
    {
        [$buyer, $seller, $conversation] = $this->pair();
        foreach (['   ', str_repeat('a', 3001)] as $content) {
            $this->actingAs($buyer)->postJson(route('messages.store'), ['conversation_id' => $conversation->id, 'content' => $content])->assertUnprocessable()->assertJsonValidationErrors('content');
        }
        $this->assertDatabaseCount('messages', 0);
    }

    public function test_block_stops_both_participants_and_only_the_blocker_can_unblock(): void
    {
        Notification::fake();
        [$buyer, $seller, $conversation] = $this->pair();
        $this->actingAs($buyer)->postJson(route('messages.block', $conversation))->assertOk();
        foreach ([$buyer, $seller] as $participant) {
            $this->actingAs($participant)->postJson(route('messages.store'), ['conversation_id' => $conversation->id, 'content' => 'Bonjour'])->assertForbidden();
            $this->post(route('messages.create.conversation'), ['recipient_id' => $participant->id === $buyer->id ? $seller->id : $buyer->id, 'message' => 'Bonjour'])->assertForbidden();
        }
        $this->actingAs($seller)->postJson(route('messages.unblock', $conversation))->assertForbidden();
        $this->postJson(route('messages.block', $conversation))->assertForbidden();
        $this->assertSame($buyer->id, $conversation->fresh()->blocked_by);
        $this->actingAs($buyer)->postJson(route('messages.unblock', $conversation))->assertOk();
        $this->actingAs($seller)->postJson(route('messages.store'), ['conversation_id' => $conversation->id, 'content' => 'Bonjour'])->assertOk();
    }

    public function test_non_participant_cannot_read_poll_send_block_or_delete(): void
    {
        [$buyer, $seller, $conversation] = $this->pair();
        $stranger = User::factory()->create();
        $this->actingAs($stranger)->get(route('messages.show', $conversation))->assertForbidden();
        $this->getJson(route('messages.poll', $conversation))->assertForbidden();
        $this->postJson(route('messages.store'), ['conversation_id' => $conversation->id, 'content' => 'Intrusion'])->assertForbidden();
        $this->postJson(route('messages.block', $conversation))->assertForbidden();
        $this->deleteJson(route('messages.destroy', $conversation))->assertForbidden();
    }

    public function test_search_and_unread_filter_are_applied_to_all_pages_and_only_owned_conversations(): void
    {
        [$buyer, $seller, $conversation] = $this->pair();
        Message::create(['conversation_id' => $conversation->id, 'sender_id' => $seller->id, 'content' => 'À lire']);
        $stranger = User::factory()->create(['name' => 'Secret Participant']);
        Conversation::getOrCreate($seller->id, $stranger->id);
        $this->actingAs($buyer)->get(route('messages.index', ['q' => 'camille', 'filter' => 'unread']))
            ->assertOk()->assertSee('Camille Prestataire')
            ->assertViewHas('conversations', fn ($items) => $items->count() === 1 && $items->first()->id === $conversation->id);
        $this->get(route('messages.index', ['q' => 'inconnu']))->assertSee('Aucune conversation correspondante');
        $this->get(route('messages.show', $conversation))->assertOk();
        $this->get(route('messages.index', ['filter' => 'unread']))->assertDontSee('À lire');
    }

    public function test_poll_synchronizes_read_receipts_edits_and_deleted_message_ids_only_in_this_conversation(): void
    {
        [$buyer, $seller, $conversation] = $this->pair();
        $own = Message::create(['conversation_id' => $conversation->id, 'sender_id' => $buyer->id, 'content' => 'Mon message']);
        $incoming = Message::create(['conversation_id' => $conversation->id, 'sender_id' => $seller->id, 'content' => 'Sa réponse']);
        $this->actingAs($seller)->get(route('messages.show', $conversation))->assertOk();
        $this->actingAs($buyer)->putJson(route('messages.update', $own), ['content' => 'Mon message corrigé'])->assertOk();
        $poll = $this->getJson(route('messages.poll', [$conversation, 'last_id' => $own->id, 'visible_ids' => [$own->id, 9999]]))->assertOk()
            ->assertJsonPath('messages.0.content', 'Sa réponse')->assertJsonPath('visible_messages.0.is_read', true)
            ->assertJsonPath('visible_messages.0.content', 'Mon message corrigé')->assertJsonCount(1, 'visible_ids');
        $this->assertNotNull($poll->json('visible_messages.0.edited_at'));
        $this->assertDatabaseHas('messages', ['id' => $incoming->id, 'is_read' => true, 'edited_at' => null]);
        $this->deleteJson(route('messages.delete', $own))->assertOk();
        $this->getJson(route('messages.poll', [$conversation, 'visible_ids' => [$own->id]]))->assertJsonCount(0, 'visible_ids');
    }

    public function test_edit_and_delete_require_message_ownership_and_five_minute_limit(): void
    {
        [$buyer, $seller, $conversation] = $this->pair();
        $message = Message::create(['conversation_id' => $conversation->id, 'sender_id' => $buyer->id, 'content' => 'Bonjour']);
        $this->actingAs($seller)->putJson(route('messages.update', $message), ['content' => 'Intrusion'])->assertForbidden();
        $this->deleteJson(route('messages.delete', $message))->assertForbidden();
        $this->travel(6)->minutes();
        $this->actingAs($buyer)->putJson(route('messages.update', $message), ['content' => 'Trop tard'])->assertForbidden();
        $this->deleteJson(route('messages.delete', $message))->assertForbidden();
    }

    public function test_mark_all_read_redirects_a_normal_form_and_does_not_touch_other_inboxes(): void
    {
        [$buyer, $seller, $conversation] = $this->pair();
        $stranger = User::factory()->create();
        $other = Conversation::getOrCreate($seller->id, $stranger->id);
        $message = Message::create(['conversation_id' => $conversation->id, 'sender_id' => $seller->id, 'content' => 'Bonjour']);
        $private = Message::create(['conversation_id' => $other->id, 'sender_id' => $seller->id, 'content' => 'Autre']);
        $this->actingAs($buyer)->from(route('messages.index'))->post(route('messages.markAllRead'))->assertRedirect(route('messages.index'));
        $this->assertTrue($message->fresh()->is_read);
        $this->assertFalse($private->fresh()->is_read);
    }

    public function test_guest_routes_require_authentication(): void
    {
        $this->get(route('messages.index'))->assertRedirect(route('login'));
        $this->postJson(route('messages.store'), ['content' => 'Bonjour'])->assertUnauthorized();
    }

    public function test_search_finds_a_conversation_beyond_the_first_page(): void
    {
        [$buyer, $seller, $target] = $this->pair();
        $target->update(['last_message_at' => now()->subDays(2)]);
        foreach (range(1, 21) as $n) {
            $other = User::factory()->create();
            Conversation::getOrCreate($buyer->id, $other->id)->update(['last_message_at' => now()]);
        }
        $this->actingAs($buyer)->get(route('messages.index', ['q' => 'camille']))->assertOk()
            ->assertViewHas('conversations', fn ($items) => $items->count() === 1 && $items->first()->id === $target->id);
        $this->getJson(route('messages.index', ['q' => ['bad']]))->assertUnprocessable();
    }

    public function test_inactive_recipient_cannot_receive_messages(): void
    {
        [$buyer, $seller, $conversation] = $this->pair();
        $seller->forceFill(['is_active' => false])->save();
        $this->actingAs($buyer)->postJson(route('messages.store'), ['conversation_id' => $conversation->id, 'content' => 'Bonjour'])->assertForbidden();
        $this->get(route('messages.show', $conversation))->assertOk()->assertSee('Ce compte n’est plus disponible.');
    }
}
