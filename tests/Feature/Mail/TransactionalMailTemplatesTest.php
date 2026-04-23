<?php

namespace Tests\Feature\Mail;

use App\Mail\EmailVerificationCode;
use App\Mail\WelcomeMail;
use App\Models\Ad;
use App\Models\User;
use App\Notifications\NewAdMatchingNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionalMailTemplatesTest extends TestCase
{
    use RefreshDatabase;

    public function test_welcome_mail_renders_support_email_and_app_name(): void
    {
        $user = User::factory()->create([
            'name' => 'Nadia Dupont',
            'email' => 'nadia@example.test',
        ]);

        $html = (new WelcomeMail($user))->render();

        $this->assertStringContainsString(config('app.name', 'ProxiPro'), $html);
        $this->assertStringContainsString($user->email, $html);
        $this->assertStringContainsString((string) config('mail.reply_to.address', config('mail.admin_email', config('mail.from.address'))), $html);
    }

    public function test_email_verification_code_mail_renders_support_email_and_code(): void
    {
        $html = (new EmailVerificationCode('482913', 'Sophie Martin'))->render();

        $this->assertStringContainsString('482913', $html);
        $this->assertStringContainsString('Sophie Martin', $html);
        $this->assertStringContainsString(config('app.name', 'ProxiPro'), $html);
        $this->assertStringContainsString((string) config('mail.reply_to.address', config('mail.admin_email', config('mail.from.address'))), $html);
    }

    public function test_new_ad_matching_notification_uses_custom_template(): void
    {
        $publisher = User::factory()->create(['name' => 'Paul Artisan']);
        $recipient = User::factory()->create(['name' => 'Claire Pro']);
        $ad = new Ad();
        $ad->id = 77;
        $ad->title = 'Réparation fuite cuisine';
        $ad->category = 'Plomberie';
        $ad->location = 'Mamoudzou';
        $ad->price = 120;
        $ad->service_type = 'demande';

        $mailMessage = (new NewAdMatchingNotification($ad, $publisher))->toMail($recipient);

        $this->assertSame('emails.notifications.new-ad-matching', $mailMessage->view);
        $this->assertSame('Claire Pro', $mailMessage->viewData['recipientName']);
        $this->assertSame('Paul Artisan', $mailMessage->viewData['publisherName']);
        $this->assertSame('Plomberie', $mailMessage->viewData['category']);
        $this->assertStringContainsString('/ads/77', $mailMessage->viewData['adUrl']);
    }
}