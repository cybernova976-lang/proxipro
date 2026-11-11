<?php

namespace Tests\Feature\Account;

use App\Mail\ManagedEmailMail;
use App\Models\Ad;
use App\Models\Conversation;
use App\Models\DemandDraft;
use App\Models\ManagedEmail;
use App\Models\Message;
use App\Models\ProSubscription;
use App\Models\SavedSearch;
use App\Models\ServiceOrder;
use App\Models\User;
use App\Notifications\AdCandidatureNotification;
use App\Notifications\BoostExpiringNotification;
use App\Notifications\NewAdMatchingNotification;
use App\Notifications\NewMessageNotification;
use App\Notifications\SavedSearchMatchNotification;
use App\Notifications\ServiceOrderRequestedNotification;
use App\Notifications\ServiceOrderStatusNotification;
use App\Services\ProviderSubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AccountTrustFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_both_settings_entries_offer_the_same_controls_and_real_quota(): void
    {
        $user = User::factory()->create();
        foreach (['settings.index', 'dashboard.settings'] as $route) {
            $this->actingAs($user)->get(route($route))->assertOk()
                ->assertSee('Mon compte, mes choix')->assertSee('0 / 5 annonces actives')
                ->assertSee('newsletter_subscribed')->assertSee('draft_reminders_enabled')
                ->assertSee(route('pro.subscription'), false)->assertSee('Supprimer définitivement mon compte');
        }
    }

    public function test_preferences_cancel_only_the_users_disabled_pending_communications(): void
    {
        $user = User::factory()->create(['newsletter_subscribed' => true, 'email_notifications' => true]);
        $newsletter = $this->email($user, 'newsletter');
        $reminder = $this->email($user, 'abandoned_demand');
        $other = $this->email(User::factory()->create(), 'newsletter');
        $this->actingAs($user)->post(route('settings.notifications'), ['email_notifications' => '1'])
            ->assertRedirect(route('settings.index').'#notifications');
        $this->assertTrue($user->fresh()->email_notifications);
        $this->assertFalse($user->fresh()->newsletter_subscribed);
        $this->assertFalse($user->fresh()->draft_reminders_enabled);
        $this->assertSame('cancelled', $newsletter->fresh()->status);
        $this->assertSame('cancelled', $reminder->fresh()->status);
        $this->assertSame('pending', $other->fresh()->status);
    }

    public function test_activity_emails_respect_opt_out_and_queued_notifications_recheck_it(): void
    {
        config(['webpush.vapid.public_key' => null]);
        $user = User::factory()->create(['email_notifications' => false, 'pro_notifications_email' => true]);
        $ad = new Ad();
        $notifications = [new AdCandidatureNotification($ad, $user), new BoostExpiringNotification($ad, 'boost', 12),
            new NewAdMatchingNotification($ad, $user), new SavedSearchMatchNotification(new SavedSearch(), $ad),
            new ServiceOrderRequestedNotification(new ServiceOrder()), new ServiceOrderStatusNotification(new ServiceOrder(), 'accepted'),
            new NewMessageNotification(new Message(), new Conversation(), $user)];
        foreach ($notifications as $notification) {
            $this->assertSame(['database'], $notification->via($user), $notification::class);
        }
        $user->update(['email_notifications' => true]);
        foreach ($notifications as $notification) {
            $this->assertContains('mail', $notification->via($user), $notification::class);
        }
        $queuedRecipient = $user->fresh();
        $user->update(['email_notifications' => false]);
        foreach ($notifications as $notification) {
            $this->assertFalse($notification->shouldSend($queuedRecipient, 'mail'));
            $this->assertTrue($notification->shouldSend($queuedRecipient, 'database'));
        }
    }

    public function test_newsletter_unsubscribe_is_signed_does_not_mutate_on_get_and_needs_no_login(): void
    {
        $user = User::factory()->create(['newsletter_subscribed' => true, 'email_notifications' => true]);
        $email = $this->email($user, 'newsletter');
        $url = URL::signedRoute('newsletter.unsubscribe', ['user' => $user]);
        $this->get($url)->assertOk()->assertSee('Arrêter les newsletters');
        $this->assertTrue($user->fresh()->newsletter_subscribed);
        $this->post(route('newsletter.unsubscribe.store', $user))->assertForbidden();
        $this->assertTrue($user->fresh()->newsletter_subscribed);
        $this->post($url)->assertRedirect($url);
        $this->assertFalse($user->fresh()->newsletter_subscribed);
        $this->assertTrue($user->fresh()->email_notifications);
        $this->assertSame('cancelled', $email->fresh()->status);
        $this->get($url)->assertOk()->assertSee('Vous êtes désinscrit');
        $this->assertStringContainsString('Me désinscrire des newsletters sans me connecter', (new ManagedEmailMail($email))->render());
    }

    public function test_reminder_is_neither_prepared_nor_sent_after_opt_out_or_draft_removal(): void
    {
        Mail::fake();
        $user = User::factory()->create(['email_notifications' => true, 'draft_reminders_enabled' => false, 'is_active' => true]);
        $draft = DemandDraft::create(['user_id' => $user->id, 'payload' => ['title' => 'Brouillon privé'], 'current_step' => 2, 'last_activity_at' => now()->subDays(2)]);
        $this->artisan('demand-drafts:send-reminders')->assertSuccessful();
        $this->assertDatabaseCount('managed_emails', 0);
        $user->update(['draft_reminders_enabled' => true]);
        $this->artisan('demand-drafts:send-reminders')->assertSuccessful();
        $email = ManagedEmail::sole();
        $draft->delete();
        $this->actingAs(User::factory()->create(['role' => 'admin']))->post(route('admin.emails.approve', $email))->assertStatus(409);
        Mail::assertNothingSent();
    }

    public function test_deletion_stops_before_erasing_any_data_when_stripe_fails(): void
    {
        $user = User::factory()->create();
        $this->mock(ProviderSubscriptionService::class)->shouldReceive('cancelForAccountDeletion')
            ->once()->withArgs(fn ($account) => $account->is($user))->andThrow(new \RuntimeException('Stripe unavailable'));
        $this->actingAs($user)->post(route('settings.delete-account'), ['password' => 'password', 'confirm_delete' => '1'])
            ->assertRedirect(route('settings.index').'#delete')->assertSessionHasErrors('delete');
        $this->assertNotNull(User::find($user->id));
        $this->assertDatabaseCount('deleted_accounts', 0);
        $this->assertAuthenticatedAs($user);
    }

    public function test_deletion_validates_password_before_contacting_stripe(): void
    {
        $this->mock(ProviderSubscriptionService::class)->shouldNotReceive('cancelForAccountDeletion');
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('settings.delete-account'), ['password' => 'incorrect', 'confirm_delete' => '1'])->assertSessionHasErrors('password');
        $this->assertNotNull(User::find($user->id));
    }

    public function test_deletion_cancels_billing_before_removing_account_and_pending_emails(): void
    {
        $user = User::factory()->create();
        $email = $this->email($user, 'newsletter');
        $this->mock(ProviderSubscriptionService::class)->shouldReceive('cancelForAccountDeletion')->once()
            ->andReturnUsing(function ($account) use ($user) {
                $this->assertTrue($account->is($user));
                $this->assertNotNull(User::find($user->id));
            });
        $this->actingAs($user)->post(route('settings.delete-account'), ['password' => 'password', 'confirm_delete' => '1'])
            ->assertRedirect(route('homepage'))->assertSessionHasNoErrors();
        $this->assertSoftDeleted('users', ['id' => $user->id]);
        $this->assertSame('cancelled', $email->fresh()->status);
        $this->assertGuest();
    }

    public function test_failed_billing_remains_manageable_without_provider_status_and_uses_actual_price(): void
    {
        $user = User::factory()->create(['stripe_id' => 'cus_test', 'is_service_provider' => false]);
        $subscription = ProSubscription::create(['user_id' => $user->id, 'plan' => 'monthly', 'amount' => 12.49,
            'status' => 'pending', 'stripe_status' => 'past_due', 'stripe_subscription_id' => 'sub_test', 'auto_renew' => false]);
        $this->actingAs($user)->get(route('pro.subscription'))->assertOk()
            ->assertSee('facturation demande une action')->assertSee('12,49 €')->assertSee('Résilier cet abonnement')
            ->assertSee('Factures et carte bancaire')->assertDontSee('Aucun abonnement actif');
        $this->mock(ProviderSubscriptionService::class)->shouldReceive('cancelImmediately')->once()
            ->withArgs(fn ($sub) => $sub->is($subscription))->andReturn($subscription);
        $this->post(route('pro.subscription.cancel-renewal'))->assertSessionHas('success');
    }

    private function email(User $user, string $type): ManagedEmail
    {
        return ManagedEmail::create(['source_key' => (string) \Illuminate\Support\Str::uuid(), 'user_id' => $user->id, 'type' => $type, 'recipient_email' => $user->email,
            'subject' => 'Test', 'headline' => 'Test', 'body' => 'Test', 'status' => 'pending']);
    }
}
