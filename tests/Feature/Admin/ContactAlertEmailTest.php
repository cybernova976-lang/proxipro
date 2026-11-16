<?php

namespace Tests\Feature\Admin;

use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Un message du formulaire de contact prévient l'équipe par e-mail, avec une
 * copie facultative vers une seconde adresse réglée dans l'administration.
 */
class ContactAlertEmailTest extends TestCase
{
    use RefreshDatabase;

    private array $form = [
        'name' => 'Assiata Abdou', 'email' => 'assiata@example.test',
        'subject' => 'Question sur une annonce', 'message' => 'Bonjour, je souhaite un renseignement sur une annonce.',
    ];

    public function test_contact_message_alerts_the_admin_address_with_a_copy(): void
    {
        Mail::fake();
        Setting::set('mail_admin_address', 'contact@prokejem.test', 'email');
        Setting::set('mail_admin_copy_address', 'fondateur@example.test', 'email');
        config(['mail.admin_email' => 'contact@prokejem.test']);

        $this->post(route('contact.store'), $this->form)->assertRedirect(route('contact.index'))->assertSessionHas('success');

        $this->assertDatabaseHas('contact_messages', ['email' => 'assiata@example.test', 'status' => 'pending']);
        Mail::assertSent(ContactMessageReceived::class, function (ContactMessageReceived $mail) {
            $mail->build();

            return $mail->hasTo('contact@prokejem.test')
                && $mail->hasCc('fondateur@example.test')
                // Répondre depuis la boîte mail écrit au visiteur, pas à Prokejem.
                && $mail->hasReplyTo('assiata@example.test')
                && str_contains($mail->subject, 'Question sur une annonce');
        });
    }

    public function test_no_copy_without_a_copy_address_and_no_alert_when_switched_off(): void
    {
        Mail::fake();
        config(['mail.admin_email' => 'contact@prokejem.test']);

        $this->post(route('contact.store'), $this->form);
        Mail::assertSent(ContactMessageReceived::class, fn (ContactMessageReceived $mail) => $mail->hasTo('contact@prokejem.test') && $mail->cc === []);

        Mail::fake();
        Setting::set('email_new_message', '0', 'email');
        $this->post(route('contact.store'), $this->form);
        Mail::assertNothingSent();
        $this->assertSame(2, ContactMessage::count());
    }

    public function test_a_failing_alert_never_blocks_the_visitor(): void
    {
        config(['mail.admin_email' => 'contact@prokejem.test']);
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('Serveur d’e-mail indisponible'));

        $this->post(route('contact.store'), $this->form)->assertRedirect(route('contact.index'))->assertSessionHas('success');
        $this->assertSame(1, ContactMessage::count());
    }

    public function test_alert_email_shows_the_message_and_links_to_the_admin(): void
    {
        $message = ContactMessage::create($this->form + ['status' => 'pending']);

        $html = (new ContactMessageReceived($message))->render();

        $this->assertStringContainsString('Bonjour, je souhaite un renseignement', $html);
        $this->assertStringContainsString('assiata@example.test', $html);
        $this->assertStringContainsString(route('admin.contact-messages.show', $message->id), $html);
        $this->assertStringContainsString('Répondre depuis l’administration', $html);
    }

    /** Envoi réel (sans simulation) : une seule adresse de réponse, celle du visiteur. */
    public function test_reply_goes_to_the_visitor_only_even_with_a_global_reply_to(): void
    {
        config(['mail.admin_email' => 'contact@prokejem.test', 'mail.reply_to' => ['address' => 'contact@prokejem.test', 'name' => 'Prokejem'],
            // L'adresse générale (info@) ne doit pas servir pour le formulaire de contact.
            'mail.from' => ['address' => 'info@prokejem.test', 'name' => 'Prokejem'],
            'mail.contact_from' => ['address' => 'contact@prokejem.test', 'name' => 'Prokejem']]);
        app()->forgetInstance('mail.manager');
        Setting::set('mail_admin_copy_address', 'fondateur@example.test', 'email');

        $this->post(route('contact.store'), $this->form)->assertSessionHas('success');

        $sent = collect(app('mailer')->getSymfonyTransport()->messages())->map(fn ($message) => $message->getOriginalMessage())->last();
        $this->assertNotNull($sent, 'Aucun e-mail envoyé.');
        $this->assertSame(['assiata@example.test'], array_map(fn ($address) => $address->getAddress(), $sent->getReplyTo()));
        $this->assertSame(['contact@prokejem.test'], array_map(fn ($address) => $address->getAddress(), $sent->getFrom()));
        $this->assertSame(['contact@prokejem.test'], array_map(fn ($address) => $address->getAddress(), $sent->getTo()));
        $this->assertSame(['fondateur@example.test'], array_map(fn ($address) => $address->getAddress(), $sent->getCc()));
    }

    /** Newsletters, rappels et messages rédigés dans l'administration partent de l'adresse générale. */
    public function test_newsletters_leave_from_the_general_address_not_the_contact_one(): void
    {
        config(['mail.from' => ['address' => 'info@prokejem.test', 'name' => 'Prokejem'],
            'mail.contact_from' => ['address' => 'contact@prokejem.test', 'name' => 'Prokejem']]);
        app()->forgetInstance('mail.manager');
        $user = User::factory()->create();
        $email = \App\Models\ManagedEmail::create([
            'user_id' => $user->id, 'recipient_email' => $user->email, 'recipient_name' => $user->name,
            'source_key' => 'test:'.$user->id, 'type' => 'newsletter', 'status' => \App\Models\ManagedEmail::STATUS_PENDING,
            'subject' => 'Nouveautés', 'headline' => 'Les nouveautés du mois', 'body' => 'Bonjour à tous.', 'image_paths' => [],
        ]);

        Mail::to($user->email)->send(new \App\Mail\ManagedEmailMail($email));

        $sent = collect(app('mailer')->getSymfonyTransport()->messages())->map(fn ($message) => $message->getOriginalMessage())->last();
        $this->assertSame(['info@prokejem.test'], array_map(fn ($address) => $address->getAddress(), $sent->getFrom()));
    }

    public function test_admin_can_save_and_clear_the_copy_address(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $base = ['mail_driver' => 'smtp', 'mail_from_address' => 'contact@prokejem.test', 'mail_from_name' => 'Prokejem',
            'mail_admin_address' => 'contact@prokejem.test', 'email_new_message' => 'on'];

        $this->actingAs($admin)->post(route('admin.settings.email'), $base + ['mail_admin_copy_address' => 'fondateur@example.test'])
            ->assertSessionHas('success');
        $this->assertSame('fondateur@example.test', Setting::get('mail_admin_copy_address'));

        $this->get(route('admin.settings'))->assertOk()
            ->assertSee('name="mail_admin_copy_address" value="fondateur@example.test"', false);

        $this->post(route('admin.settings.email'), $base + ['mail_admin_copy_address' => ''])->assertSessionHas('success');
        $this->assertSame('', (string) Setting::get('mail_admin_copy_address', ''));

        $this->post(route('admin.settings.email'), $base + ['mail_admin_copy_address' => 'pas-une-adresse'])
            ->assertSessionHasErrors('mail_admin_copy_address');
    }
}
