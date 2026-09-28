<?php

namespace Tests\Feature\Messages;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class MessagingContactsFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function exchange(User $first, User $second): Conversation
    {
        $conversation = Conversation::getOrCreate($first->id, $second->id);
        Message::create(['conversation_id' => $conversation->id, 'sender_id' => $first->id, 'content' => 'Bonjour']);

        return $conversation;
    }

    public function test_picker_contains_only_existing_active_unblocked_contacts_in_both_directions(): void
    {
        $before = User::factory()->create(['name' => 'Contact ancien', 'is_active' => true]);
        $viewer = User::factory()->create(['is_active' => true]);
        $after = User::factory()->create(['name' => 'Contact récent', 'is_active' => true]);
        $stranger = User::factory()->create(['name' => 'Membre jamais contacté', 'is_active' => true]);
        $this->exchange($before, $viewer);
        $this->exchange($viewer, $after);
        $blocked = User::factory()->create(['is_active' => true]);
        $this->exchange($viewer, $blocked)->update(['is_blocked' => true, 'blocked_by' => $viewer->id]);
        $inactive = User::factory()->create(['is_active' => false]);
        $this->exchange($viewer, $inactive);
        $empty = User::factory()->create(['is_active' => true]);
        Conversation::getOrCreate($viewer->id, $empty->id);
        $this->exchange($after, $stranger); // Les contacts d'autrui ne deviennent pas les siens.

        $this->actingAs($viewer)->get(route('messages.index'))->assertOk()
            ->assertDontSee('Membre jamais contacté')->assertSee('Écrire à un contact')
            ->assertSee('Uniquement les personnes avec qui vous avez déjà échangé.')
            ->assertViewHas('recipients', fn ($contacts) => $contacts->pluck('id')->sort()->values()->all() === collect([$before->id, $after->id])->sort()->values()->all());
    }

    public function test_contacts_are_not_limited_by_the_inbox_page_or_search_and_empty_inbox_has_no_directory(): void
    {
        $viewer = User::factory()->create(['is_active' => true]);
        User::factory()->create(['name' => 'Profil confidentiel jamais contacté', 'is_active' => true]);
        $this->actingAs($viewer)->get(route('messages.index'))->assertOk()
            ->assertDontSee('Profil confidentiel jamais contacté')->assertDontSee('id="newConversationModal"', false)
            ->assertSee('Explorer les annonces')->assertViewHas('recipients', fn ($contacts) => $contacts->isEmpty());
        foreach (range(1, 22) as $n) {
            $contact = User::factory()->create(['name' => 'Mon contact '.$n, 'is_active' => true]);
            $this->exchange($viewer, $contact);
        }
        $this->get(route('messages.index', ['q' => 'aucun résultat']))->assertOk()
            ->assertViewHas('conversations', fn ($conversations) => $conversations->isEmpty())
            ->assertViewHas('recipients', fn ($contacts) => $contacts->count() === 22);
    }

    public function test_contact_form_rejects_unrelated_recipient_and_first_contact_from_profile_is_still_allowed(): void
    {
        Notification::fake();
        $viewer = User::factory()->create(['is_active' => true]);
        $stranger = User::factory()->create(['is_active' => true]);
        $body = ['recipient_id' => $stranger->id, 'message' => 'Bonjour, êtes-vous disponible ?'];
        $this->actingAs($viewer)->post(route('messages.create.conversation'), $body + ['existing_contact' => 1])->assertForbidden();
        $this->assertDatabaseCount('messages', 0);
        $this->post(route('messages.create.conversation'), $body)->assertRedirect()
            ->assertSessionMissing('success');
        $this->post(route('messages.create.conversation'), $body + ['existing_contact' => 1])->assertRedirect()
            ->assertSessionMissing('success');
        $this->assertDatabaseCount('messages', 2);
        $stranger->delete();
        $this->get(route('messages.index'))->assertOk()->assertViewHas('recipients', fn ($contacts) => $contacts->isEmpty());
    }
}
