<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_reset_request_uses_french_status_message(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $response = $this->post(route('password.email'), [
            'email' => $user->email,
        ]);

        $response
            ->assertRedirect()
            ->assertSessionHas('status', __('passwords.sent'));

        Notification::assertSentTo($user, ResetPassword::class);

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
            $mailMessage = $notification->toMail($user);

            $this->assertSame('Réinitialisation de votre mot de passe ProxiPro', $mailMessage->subject);
            $this->assertSame('emails.auth.reset-password', $mailMessage->view);
            $this->assertSame($user->name, $mailMessage->viewData['userName']);
            $this->assertSame(config('app.name', 'ProxiPro'), $mailMessage->viewData['appName']);
            $this->assertNotEmpty($mailMessage->viewData['supportEmail']);
            $this->assertStringContainsString('password/reset', $mailMessage->viewData['resetUrl']);

            return true;
        });
    }

    public function test_user_can_open_reset_link_and_submit_new_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('old-password-123'),
        ]);

        $token = Password::broker()->createToken($user);

        $resetUrl = route('password.reset', [
            'token' => $token,
            'email' => $user->email,
        ]);

        $this->get($resetUrl)
            ->assertOk()
            ->assertSee('Définissez un nouveau mot de passe')
            ->assertSee($user->email);

        $response = $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'nouveau-mot-de-passe-456',
            'password_confirmation' => 'nouveau-mot-de-passe-456',
        ]);

        $response->assertRedirect('/feed');
        $this->assertAuthenticatedAs($user);
        $this->assertTrue(Hash::check('nouveau-mot-de-passe-456', $user->fresh()->password));
    }
}