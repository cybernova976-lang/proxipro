<?php

namespace Tests\Feature\Auth;

use App\Http\Controllers\Auth\EmailVerificationCodeController;
use App\Mail\EmailVerificationCode;
use App\Mail\WelcomeMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailVerificationCodeFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_stores_the_pending_verification_session(): void
    {
        Mail::fake();

        $response = $this->post(route('register'), [
            'account_type' => 'particulier',
            'firstname' => 'Amina',
            'lastname' => 'Martin',
            'email' => 'amina.verification@gmail.com',
            'country' => 'France',
            'city' => 'Paris',
            'password' => 'mot-de-passe-solide-2026',
            'password_confirmation' => 'mot-de-passe-solide-2026',
            'terms' => '1',
            'website_url' => '',
        ]);

        $user = User::where('email', 'amina.verification@gmail.com')->firstOrFail();

        $response
            ->assertRedirect(route('verification.code.show', ['email' => $user->email]))
            ->assertSessionHas(EmailVerificationCodeController::PENDING_USER_SESSION_KEY, $user->id);

        $this->assertNull($user->email_verified_at);
        Mail::assertSent(EmailVerificationCode::class);
    }

    public function test_verification_page_uses_the_account_bound_to_the_session_and_allows_email_correction(): void
    {
        $user = $this->unverifiedUser('adresse-erronee@example.com');

        $response = $this->withSession([
            EmailVerificationCodeController::PENDING_USER_SESSION_KEY => $user->id,
        ])->get(route('verification.code.show', ['email' => 'autre-compte@example.com']));

        $response
            ->assertOk()
            ->assertSee($user->email)
            ->assertDontSee('autre-compte@example.com')
            ->assertSee('Adresse incorrecte ? La modifier')
            ->assertSee(route('verification.code.email.update'), false);
    }

    public function test_pending_user_can_correct_their_email_and_receive_a_new_code(): void
    {
        Mail::fake();

        $user = $this->unverifiedUser('mauvaise-adresse@example.com');
        $oldCodeHash = $user->email_verification_code;

        $response = $this->withSession([
            EmailVerificationCodeController::PENDING_USER_SESSION_KEY => $user->id,
        ])->post(route('verification.code.email.update'), [
            'email' => 'Bonne.Adresse@Example.com',
        ]);

        $response
            ->assertRedirect(route('verification.code.show', ['email' => 'bonne.adresse@example.com']))
            ->assertSessionHas('success');

        $user = $user->fresh();

        $this->assertSame('bonne.adresse@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
        $this->assertNotSame($oldCodeHash, $user->email_verification_code);
        $this->assertTrue($user->email_verification_code_expires_at->isFuture());

        Mail::assertSent(EmailVerificationCode::class, function (EmailVerificationCode $mail) use ($user) {
            return $mail->hasTo($user->email)
                && Hash::check($mail->code, $user->email_verification_code);
        });
    }

    public function test_email_correction_requires_the_pending_verification_session(): void
    {
        Mail::fake();

        $user = $this->unverifiedUser('adresse-erronee@example.com');

        $response = $this->post(route('verification.code.email.update'), [
            'email' => 'pirate@example.com',
        ]);

        $response
            ->assertRedirect(route('register'))
            ->assertSessionHas('error');

        $this->assertSame('adresse-erronee@example.com', $user->fresh()->email);
        Mail::assertNothingSent();
    }

    public function test_email_correction_rejects_an_address_used_by_another_account(): void
    {
        Mail::fake();

        User::factory()->create(['email' => 'adresse-utilisee@example.com']);
        $user = $this->unverifiedUser('adresse-erronee@example.com');

        $response = $this->withSession([
            EmailVerificationCodeController::PENDING_USER_SESSION_KEY => $user->id,
        ])->from(route('verification.code.show', ['email' => $user->email]))
            ->post(route('verification.code.email.update'), [
                'email' => 'adresse-utilisee@example.com',
            ]);

        $response
            ->assertRedirect(route('verification.code.show', ['email' => $user->email]))
            ->assertSessionHasErrors('email');

        $this->assertSame('adresse-erronee@example.com', $user->fresh()->email);
        Mail::assertNothingSent();
    }

    public function test_successful_verification_clears_the_pending_session(): void
    {
        Mail::fake();

        $user = $this->unverifiedUser('adresse-correcte@example.com', '482915');

        $response = $this->withSession([
            EmailVerificationCodeController::PENDING_USER_SESSION_KEY => $user->id,
        ])->post(route('verification.code.verify'), [
            'email' => $user->email,
            'code' => '482915',
        ]);

        $response
            ->assertRedirect(route('login'))
            ->assertSessionMissing(EmailVerificationCodeController::PENDING_USER_SESSION_KEY);

        $this->assertNotNull($user->fresh()->email_verified_at);
        Mail::assertSent(WelcomeMail::class);
    }

    public function test_unverified_login_restores_the_pending_verification_session(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'compte-non-verifie@example.com',
            'email_verified_at' => null,
            'password' => Hash::make('mot-de-passe-solide-2026'),
        ]);

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'mot-de-passe-solide-2026',
        ]);

        $response
            ->assertRedirect(route('verification.code.show', ['email' => $user->email]))
            ->assertSessionHas(EmailVerificationCodeController::PENDING_USER_SESSION_KEY, $user->id);

        $this->assertGuest();
        $this->assertNull($user->fresh()->email_verified_at);
        Mail::assertSent(EmailVerificationCode::class);
    }

    private function unverifiedUser(string $email, string $code = '123456'): User
    {
        return User::factory()->create([
            'email' => $email,
            'email_verified_at' => null,
            'email_verification_code' => Hash::make($code),
            'email_verification_code_expires_at' => now()->addMinutes(15),
        ]);
    }
}
