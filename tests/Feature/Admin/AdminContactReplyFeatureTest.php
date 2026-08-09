<?php

namespace Tests\Feature\Admin;

use App\Mail\ContactMessageReply;
use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Mailer;
use Illuminate\Support\Facades\Mail;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class AdminContactReplyFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_reply_sends_an_email_before_marking_the_message_as_replied(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['role' => 'admin']);
        $contactMessage = ContactMessage::create([
            'name' => 'Visiteur Test',
            'email' => 'visiteur@example.com',
            'subject' => 'Question sur Prokejem',
            'message' => 'Je souhaite obtenir davantage d’informations.',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.contact-messages.reply', $contactMessage), [
                'admin_reply' => 'Bonjour, voici notre réponse.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        Mail::assertSent(ContactMessageReply::class, function (ContactMessageReply $mail): bool {
            return $mail->hasTo('visiteur@example.com')
                && $mail->reply === 'Bonjour, voici notre réponse.';
        });

        $contactMessage->refresh();

        $this->assertSame('replied', $contactMessage->status);
        $this->assertSame('Bonjour, voici notre réponse.', $contactMessage->admin_reply);
        $this->assertNotNull($contactMessage->replied_at);
    }

    public function test_invalid_recipient_is_not_marked_as_replied(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['role' => 'admin']);
        $contactMessage = ContactMessage::create([
            'name' => 'Visiteur Test',
            'email' => 'adresse-invalide',
            'subject' => 'Question',
            'message' => 'Message de test.',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.contact-messages.reply', $contactMessage), [
                'admin_reply' => 'Réponse qui ne doit pas partir.',
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        Mail::assertNothingSent();

        $contactMessage->refresh();

        $this->assertSame('pending', $contactMessage->status);
        $this->assertNull($contactMessage->admin_reply);
        $this->assertNull($contactMessage->replied_at);
    }

    public function test_smtp_failure_keeps_the_message_pending_for_a_retry(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $contactMessage = ContactMessage::create([
            'name' => 'Visiteur Test',
            'email' => 'visiteur@example.com',
            'subject' => 'Question',
            'message' => 'Message de test.',
            'status' => 'pending',
        ]);

        $contactMailer = Mockery::mock(Mailer::class);
        $contactMailer
            ->shouldReceive('to')
            ->once()
            ->with('visiteur@example.com', 'Visiteur Test')
            ->andThrow(new RuntimeException('Serveur SMTP indisponible'));

        Mail::shouldReceive('mailer')
            ->once()
            ->with('contact')
            ->andReturn($contactMailer);

        $this->actingAs($admin)
            ->from(route('admin.contact-messages.show', $contactMessage))
            ->post(route('admin.contact-messages.reply', $contactMessage), [
                'admin_reply' => 'Réponse à réessayer.',
            ])
            ->assertRedirect(route('admin.contact-messages.show', $contactMessage))
            ->assertSessionHas('error');

        $contactMessage->refresh();

        $this->assertSame('pending', $contactMessage->status);
        $this->assertNull($contactMessage->admin_reply);
        $this->assertNull($contactMessage->replied_at);
    }
}
