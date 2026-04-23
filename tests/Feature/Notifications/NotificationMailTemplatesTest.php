<?php

namespace Tests\Feature\Notifications;

use App\Models\Ad;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Notifications\AdCandidatureNotification;
use App\Notifications\BoostExpiringNotification;
use App\Notifications\NewMessageNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationMailTemplatesTest extends TestCase
{
    use RefreshDatabase;

    public function test_boost_expiring_notification_uses_custom_template(): void
    {
        $recipient = User::factory()->create(['name' => 'Marie Pro']);
        $ad = new Ad();
        $ad->id = 12;
        $ad->title = 'Dépannage plomberie rapide';

        $mailMessage = (new BoostExpiringNotification($ad, 'boost', 36))->toMail($recipient);

        $this->assertSame('emails.notifications.boost-expiring', $mailMessage->view);
        $this->assertSame('Marie Pro', $mailMessage->viewData['recipientName']);
        $this->assertSame('boost', $mailMessage->viewData['label']);
        $this->assertStringContainsString('/ads/12/boost', $mailMessage->viewData['renewUrl']);
    }

    public function test_new_message_notification_uses_custom_template(): void
    {
        $recipient = User::factory()->create(['name' => 'Nina Client']);
        $sender = User::factory()->create(['name' => 'Omar Prestataire']);
        $conversation = new Conversation();
        $conversation->id = 41;
        $message = new Message();
        $message->content = 'Bonjour, je suis disponible demain matin pour intervenir.';

        $mailMessage = (new NewMessageNotification($message, $conversation, $sender))->toMail($recipient);

        $this->assertSame('emails.notifications.new-message', $mailMessage->view);
        $this->assertSame('Nina Client', $mailMessage->viewData['recipientName']);
        $this->assertSame('Omar Prestataire', $mailMessage->viewData['senderName']);
        $this->assertStringContainsString('disponible demain matin', $mailMessage->viewData['preview']);
        $this->assertStringContainsString('/messages/41', $mailMessage->viewData['conversationUrl']);
    }

    public function test_ad_candidature_notification_uses_custom_template(): void
    {
        $recipient = User::factory()->create(['name' => 'Luc Pro']);
        $candidate = User::factory()->create(['name' => 'Anna Client']);
        $ad = new Ad();
        $ad->id = 88;
        $ad->title = 'Pose de carrelage salle de bain';

        $mailMessage = (new AdCandidatureNotification($ad, $candidate, 'Je suis disponible cette semaine.'))->toMail($recipient);

        $this->assertSame('emails.notifications.ad-candidature', $mailMessage->view);
        $this->assertSame('Luc Pro', $mailMessage->viewData['recipientName']);
        $this->assertSame('Anna Client', $mailMessage->viewData['candidateName']);
        $this->assertSame('Je suis disponible cette semaine.', $mailMessage->viewData['candidateMessage']);
        $this->assertStringContainsString('/ads/88', $mailMessage->viewData['adUrl']);
    }
}