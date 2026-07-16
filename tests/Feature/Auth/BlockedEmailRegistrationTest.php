<?php

namespace Tests\Feature\Auth;

use App\Http\Controllers\Auth\EmailVerificationCodeController;
use App\Models\BlockedEmail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class BlockedEmailRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_blocked_deleted_email_cannot_register_again(): void
    {
        Mail::fake();

        $deletedUser = User::factory()->create(['email' => 'utilisateur.bloque@gmail.com']);
        $deletedUser->delete();

        BlockedEmail::create([
            'email' => 'utilisateur.bloque@gmail.com',
            'reason' => 'Compte supprimé pour fraude.',
        ]);

        $response = $this->from(route('register'))->post(route('register'), [
            'account_type' => 'particulier',
            'firstname' => 'Nouvelle',
            'lastname' => 'Inscription',
            'email' => 'Utilisateur.Bloque@Gmail.com',
            'country' => 'France',
            'city' => 'Paris',
            'password' => 'mot-de-passe-solide-2026',
            'password_confirmation' => 'mot-de-passe-solide-2026',
            'terms' => '1',
            'website_url' => '',
        ]);

        $response
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors('email');

        $this->assertSame(1, User::withTrashed()->where('email', 'utilisateur.bloque@gmail.com')->count());
        $this->assertTrue(User::withTrashed()->findOrFail($deletedUser->id)->trashed());
        Mail::assertNothingSent();
    }

    public function test_pending_user_cannot_replace_their_email_with_a_blocked_address(): void
    {
        Mail::fake();

        $pendingUser = User::factory()->unverified()->create([
            'email' => 'adresse.erronee@example.com',
        ]);

        BlockedEmail::create([
            'email' => 'adresse.interdite@example.com',
            'reason' => 'Ancien compte bloqué.',
        ]);

        $this->withSession([
            EmailVerificationCodeController::PENDING_USER_SESSION_KEY => $pendingUser->id,
        ])->from(route('verification.code.show', ['email' => $pendingUser->email]))
            ->post(route('verification.code.email.update'), [
                'email' => 'Adresse.Interdite@Example.com',
            ])
            ->assertRedirect(route('verification.code.show', ['email' => $pendingUser->email]))
            ->assertSessionHasErrors('email');

        $this->assertSame('adresse.erronee@example.com', $pendingUser->fresh()->email);
        Mail::assertNothingSent();
    }

    public function test_blocked_email_cannot_create_an_account_through_social_login(): void
    {
        BlockedEmail::create([
            'email' => 'social.interdit@example.com',
            'reason' => 'Compte social précédemment supprimé.',
        ]);

        $socialUser = Mockery::mock();
        $socialUser->shouldReceive('getEmail')->once()->andReturn('Social.Interdit@Example.com');
        $socialUser->shouldReceive('getId')->once()->andReturn('google-user-123');

        $provider = Mockery::mock();
        $provider->shouldReceive('user')->once()->andReturn($socialUser);

        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

        $this->get(route('social.callback', ['provider' => 'google']))
            ->assertRedirect(route('login'))
            ->assertSessionHas(
                'error',
                'Cette adresse e-mail n’est pas autorisée à créer ou réactiver un compte. Contactez le support si vous pensez qu’il s’agit d’une erreur.'
            );

        $this->assertDatabaseMissing('users', ['email' => 'social.interdit@example.com']);
    }
}
