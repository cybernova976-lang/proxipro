<?php

namespace Tests\Feature\Notifications;

use App\Models\ContactMessage;
use App\Models\ProSubscription;
use App\Models\User;
use App\Notifications\AccountStatusNotification;
use App\Notifications\AdminReplyNotification;
use App\Notifications\VerificationApproved;
use App\Support\SafeNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Le membre est prévenu, dans son compte et par e-mail, de tout ce qui change
 * sur son compte : Premium, droits d'administration, vérification, activation.
 */
class AccountNotificationsFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function principalAdmin(): User
    {
        $principal = User::factory()->create(['role' => 'admin']);
        config(['admin.principal_admin.email' => $principal->email]);

        return $principal;
    }

    private function provider(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->forceFill(['is_service_provider' => true])->save();

        return $user;
    }

    private function assertNotified(User $user, string $event, ?callable $extra = null): void
    {
        Notification::assertSentTo($user, AccountStatusNotification::class, function ($notification, $channels) use ($user, $event, $extra) {
            $data = $notification->toArray($user);

            return $data['type'] === 'account_'.$event
                && in_array('database', $channels, true)
                && ($extra === null || $extra($notification, $channels, $data));
        });
    }

    public function test_premium_granted_suspended_resumed_and_cancelled_each_notify_the_member(): void
    {
        Notification::fake();
        $principal = $this->principalAdmin();
        $provider = $this->provider();

        $this->actingAs($principal)->post(route('admin.subscriptions.provider.grant'), [
            'email' => $provider->email, 'plan' => 'monthly', 'duration' => '30',
        ])->assertSessionHas('success');
        $this->assertNotified($provider, 'premium_granted', fn ($n, $channels, $data) => in_array('mail', $channels, true)
            && str_contains($data['message'], now()->addDays(30)->format('d/m/Y')));

        $subscription = ProSubscription::where('user_id', $provider->id)->firstOrFail();
        $this->post(route('admin.subscriptions.provider.suspend', $subscription));
        $this->assertNotified($provider, 'premium_suspended');

        $this->post(route('admin.subscriptions.provider.resume', $subscription));
        $this->assertNotified($provider, 'premium_resumed');

        $this->post(route('admin.subscriptions.provider.cancel', $subscription));
        $this->assertNotified($provider, 'premium_cancelled');
    }

    /** Bout en bout, sans simulation : l'action de l'administrateur crée bien la notification et l'e-mail. */
    public function test_granting_premium_really_stores_the_notification_and_sends_the_email(): void
    {
        $principal = $this->principalAdmin();
        $provider = $this->provider(['email_notifications' => true]);

        $this->actingAs($principal)->post(route('admin.subscriptions.provider.grant'), [
            'email' => $provider->email, 'plan' => 'annual', 'duration' => '60',
        ])->assertSessionHas('success');

        $stored = $provider->notifications()->firstOrFail();
        $this->assertSame('account_premium_granted', $stored->data['type']);
        $this->assertNull($stored->read_at);

        $sent = collect(app('mailer')->getSymfonyTransport()->messages())
            ->map(fn ($message) => $message->getOriginalMessage())
            ->first(fn ($email) => collect($email->getTo())->contains(fn ($to) => $to->getAddress() === $provider->email));
        $this->assertNotNull($sent, 'Aucun e-mail envoyé au membre.');
        $this->assertStringContainsString('Premium activé', $sent->getSubject());
        $this->assertStringContainsString('Connectez-vous à votre compte Prokejem', $sent->getHtmlBody());

        // Le membre voit la notification sur sa page et dans la cloche.
        $this->actingAs($provider)->get(route('notifications.index'))->assertOk()->assertSee('Premium activé');
    }

    public function test_admin_role_changes_notify_the_member_even_with_emails_switched_off(): void
    {
        Notification::fake();
        $principal = $this->principalAdmin();
        $member = User::factory()->create(['email_notifications' => false]);

        $this->actingAs($principal)->post(route('admin.admins.promote', $member->id), ['privileges' => ['manage_ads']]);
        $this->assertNotified($member, 'admin_promoted', fn ($n, $channels, $data) => in_array('mail', $channels, true)
            && str_contains($data['message'], config('admin.privileges.manage_ads.label')));

        $this->put(route('admin.admins.privileges', $member->id), ['privileges' => ['manage_ads', 'view_stats']]);
        // Changement de droits : dans le compte, mais pas d'e-mail si le membre les a coupés.
        $this->assertNotified($member, 'admin_privileges_updated', fn ($n, $channels) => ! in_array('mail', $channels, true));

        $this->post(route('admin.admins.revoke', $member->id));
        $this->assertNotified($member, 'admin_revoked', fn ($n, $channels) => in_array('mail', $channels, true));
    }

    public function test_unchanged_privileges_do_not_notify_again(): void
    {
        Notification::fake();
        $principal = $this->principalAdmin();
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->forceFill(['admin_privileges' => ['manage_ads']])->save();

        $this->actingAs($principal)->put(route('admin.admins.privileges', $admin->id), ['privileges' => ['manage_ads']]);

        Notification::assertNothingSentTo($admin);
    }

    public function test_verification_and_activation_changes_notify_the_member(): void
    {
        Notification::fake();
        $principal = $this->principalAdmin();
        $member = User::factory()->create(['is_verified' => false, 'is_active' => true]);
        $base = ['name' => $member->name, 'email' => $member->email];

        $this->actingAs($principal)->put(route('admin.users.update', $member->id), $base + ['is_verified' => '1', 'is_active' => '1']);
        $this->assertNotified($member, 'account_verified');

        $this->put(route('admin.users.update', $member->id), $base + ['is_verified' => '1']);
        $this->assertNotified($member, 'account_deactivated', fn ($n, $channels) => in_array('mail', $channels, true));

        $this->put(route('admin.users.update', $member->id), $base + ['is_active' => '1']);
        $this->assertNotified($member, 'account_reactivated');
        $this->assertNotified($member, 'account_unverified');
    }

    public function test_notification_is_stored_readable_and_email_invites_to_sign_in(): void
    {
        $member = User::factory()->create();

        AccountStatusNotification::dispatch($member, 'premium_granted', ['until' => '16/12/2026']);

        $stored = $member->notifications()->firstOrFail();
        $this->assertSame('Premium activé', $stored->data['title']);
        $this->assertSame(route('pro.subscription'), $stored->data['action_url']);

        $html = (string) (new AccountStatusNotification('premium_granted', ['until' => '16/12/2026']))->toMail($member)->render();
        $this->assertStringContainsString('Connectez-vous à votre compte Prokejem', $html);
        $this->assertStringContainsString('Cordialement,', $html);
        // Le pied des e-mails de notification n'est plus en anglais.
        foreach (['Regards,', 'having trouble clicking', 'All rights reserved'] as $english) {
            $this->assertStringNotContainsString($english, $html);
        }
    }

    public function test_admin_reply_is_also_signalled_in_the_member_account(): void
    {
        Mail::fake();
        Notification::fake();
        $principal = $this->principalAdmin();
        $member = User::factory()->create();
        $message = ContactMessage::create(['user_id' => $member->id, 'name' => $member->name, 'email' => $member->email,
            'subject' => 'Question sur mon annonce', 'message' => 'Bonjour, une question.', 'status' => 'pending']);

        $this->actingAs($principal)->post(route('admin.contact-messages.reply', $message->id), ['admin_reply' => 'Voici notre réponse.'])
            ->assertSessionHas('success');

        Notification::assertSentTo($member, AdminReplyNotification::class, function ($notification, $channels) use ($member) {
            $data = $notification->toArray($member);

            // Pas de second e-mail : la réponse elle-même est déjà envoyée par e-mail.
            return $data['type'] === 'admin_reply' && ! in_array('mail', $channels, true)
                && str_contains($data['message'], 'Question sur mon annonce');
        });
    }

    public function test_verification_decisions_reach_the_member_by_email(): void
    {
        $member = User::factory()->create(['email_notifications' => true]);
        $verification = new \App\Models\IdentityVerification;
        $verification->id = 1;

        $channels = (new VerificationApproved($verification))->via($member);
        $this->assertContains('database', $channels);
        $this->assertContains('mail', $channels);

        $member->forceFill(['email_notifications' => false]);
        $this->assertNotContains('mail', (new VerificationApproved($verification))->via($member));
    }

    public function test_a_failing_notification_never_breaks_the_action_in_progress(): void
    {
        $member = User::factory()->create();
        $failing = new class extends \Illuminate\Notifications\Notification
        {
            public function via($notifiable): array
            {
                throw new \RuntimeException('Serveur d’e-mail indisponible');
            }
        };

        $this->assertFalse(SafeNotifier::send($member, $failing));
        $this->assertFalse(SafeNotifier::send(null, $failing));
    }

    public function test_notifications_page_lists_marks_read_and_only_opens_internal_pages(): void
    {
        $member = User::factory()->create();
        AccountStatusNotification::dispatch($member, 'account_verified');
        $notification = $member->notifications()->firstOrFail();

        $this->actingAs($member)->get(route('notifications.index'))->assertOk()
            ->assertSee('Profil vérifié')
            ->assertSee('1 notification non lue')
            ->assertSee(route('notifications.open', $notification->id), false);

        $this->get(route('notifications.open', $notification->id))->assertRedirect(route('profile.show'));
        $this->assertNotNull($notification->fresh()->read_at);

        // Une adresse extérieure enregistrée dans une notification ne redirige jamais hors de Prokejem.
        $member->notifications()->update(['data' => json_encode(['title' => 'Test', 'message' => 'Test', 'action_url' => 'https://exemple-externe.test/piege'])]);
        $this->get(route('notifications.open', $notification->id))->assertRedirect(route('notifications.index'));

        // Les notifications d'un autre membre ne sont pas accessibles.
        $this->actingAs(User::factory()->create())->get(route('notifications.open', $notification->id))->assertNotFound();

        AccountStatusNotification::dispatch($member, 'premium_expired');
        $this->actingAs($member)->post(route('notifications.read-all'))->assertRedirect(route('notifications.index'));
        $this->assertSame(0, $member->unreadNotifications()->count());
    }

    public function test_bell_links_to_the_notifications_page_everywhere(): void
    {
        $provider = $this->provider();
        AccountStatusNotification::dispatch($provider, 'premium_granted');

        $this->actingAs($provider)->get(route('feed'))->assertOk()
            ->assertSee('Voir toutes mes notifications')
            ->assertSee(route('notifications.index'), false);

        $this->get(route('pro.dashboard'))->assertOk()
            ->assertSee('data-pro-notifications', false)
            ->assertSee('Notifications (1 non lues)', false);
    }
}
