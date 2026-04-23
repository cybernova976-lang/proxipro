<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerificationEmailTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_verify_email_notification_uses_custom_french_template(): void
    {
        $user = new class extends User implements MustVerifyEmail {
        };

        $user->forceFill([
            'id' => 42,
            'name' => 'Claire Martin',
            'email' => 'claire@example.test',
            'email_verified_at' => null,
        ]);

        $notification = new VerifyEmail();
        $mailMessage = $notification->toMail($user);

        $this->assertSame('Vérification de votre adresse e-mail ProxiPro', $mailMessage->subject);
        $this->assertSame('emails.auth.verify-email', $mailMessage->view);
        $this->assertSame('Claire Martin', $mailMessage->viewData['userName']);
        $this->assertSame(config('app.name', 'ProxiPro'), $mailMessage->viewData['appName']);
        $this->assertStringContainsString('/email/verify/', $mailMessage->viewData['verificationUrl']);
        $this->assertNotEmpty($mailMessage->viewData['supportEmail']);
    }
}