<?php

namespace Tests\Feature\ServiceOrder;

use App\Models\Ad;
use App\Models\ServiceOrder;
use App\Models\ServiceReminder;
use App\Models\User;
use App\Notifications\ServiceReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ServiceReminderFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_buyer_can_program_a_reminder_only_after_a_completed_order(): void
    {
        [$order, $buyer, $seller] = $this->completedOrder();

        $this->actingAs($buyer)->post(route('service-reminders.store', $order), [
            'next_reminder_at' => now()->addMonth()->format('Y-m-d H:i:s'),
            'frequency' => ServiceReminder::FREQUENCY_QUARTERLY,
            'send_email' => '1',
        ])->assertRedirect();

        $this->assertDatabaseHas('service_reminders', [
            'service_order_id' => $order->id,
            'user_id' => $buyer->id,
            'frequency' => ServiceReminder::FREQUENCY_QUARTERLY,
            'send_email' => true,
            'is_active' => true,
        ]);

        $this->actingAs($seller)->post(route('service-reminders.store', $order), [
            'next_reminder_at' => now()->addMonth()->format('Y-m-d H:i:s'),
            'frequency' => ServiceReminder::FREQUENCY_ONCE,
        ])->assertForbidden();

        $order->update(['status' => ServiceOrder::STATUS_FUNDED]);
        $this->actingAs($buyer)->post(route('service-reminders.store', $order), [
            'next_reminder_at' => now()->addMonth()->format('Y-m-d H:i:s'),
            'frequency' => ServiceReminder::FREQUENCY_ONCE,
        ])->assertForbidden();
    }

    public function test_owner_can_update_pause_reactivate_and_delete_a_reminder(): void
    {
        [$order, $buyer, $seller] = $this->completedOrder();
        $reminder = ServiceReminder::create([
            'user_id' => $buyer->id,
            'service_order_id' => $order->id,
            'frequency' => ServiceReminder::FREQUENCY_ONCE,
            'next_reminder_at' => now()->addMonth(),
            'is_active' => true,
        ]);

        $this->actingAs($seller)->put(route('service-reminders.update', $reminder), [
            'next_reminder_at' => now()->addMonths(2)->format('Y-m-d H:i:s'),
            'frequency' => ServiceReminder::FREQUENCY_MONTHLY,
        ])->assertForbidden();

        $this->actingAs($buyer)->put(route('service-reminders.update', $reminder), [
            'next_reminder_at' => now()->addMonths(2)->format('Y-m-d H:i:s'),
            'frequency' => ServiceReminder::FREQUENCY_MONTHLY,
        ])->assertRedirect();

        $this->assertDatabaseHas('service_reminders', [
            'id' => $reminder->id,
            'frequency' => ServiceReminder::FREQUENCY_MONTHLY,
            'send_email' => false,
            'is_active' => true,
        ]);

        $this->actingAs($buyer)->post(route('service-reminders.toggle', $reminder))->assertRedirect();
        $this->assertFalse($reminder->fresh()->is_active);

        $this->actingAs($buyer)->post(route('service-reminders.toggle', $reminder))->assertRedirect();
        $this->assertTrue($reminder->fresh()->is_active);

        $this->actingAs($buyer)->delete(route('service-reminders.destroy', $reminder))->assertRedirect();
        $this->assertDatabaseMissing('service_reminders', ['id' => $reminder->id]);
    }

    public function test_send_command_notifies_due_reminders_and_never_creates_an_ad_or_order(): void
    {
        Notification::fake();
        [$order, $buyer] = $this->completedOrder();
        $initialAds = Ad::count();
        $initialOrders = ServiceOrder::count();

        $oneTime = ServiceReminder::create([
            'user_id' => $buyer->id,
            'service_order_id' => $order->id,
            'frequency' => ServiceReminder::FREQUENCY_ONCE,
            'next_reminder_at' => now()->subMinute(),
            'is_active' => true,
        ]);

        $this->artisan('service-reminders:send')->assertSuccessful();

        Notification::assertSentTo($buyer, ServiceReminderNotification::class);
        $oneTime->refresh();
        $this->assertFalse($oneTime->is_active);
        $this->assertSame(1, $oneTime->reminders_sent_count);
        $this->assertNotNull($oneTime->last_sent_at);
        $this->assertSame($initialAds, Ad::count());
        $this->assertSame($initialOrders, ServiceOrder::count());
    }

    public function test_recurring_reminder_advances_and_paused_or_future_reminders_are_ignored(): void
    {
        Notification::fake();
        [$dueOrder, $buyer] = $this->completedOrder('Entretien climatisation');
        [$futureOrder] = $this->completedOrder('Nettoyage toiture', $buyer);
        [$pausedOrder] = $this->completedOrder('Entretien jardin', $buyer);

        $due = ServiceReminder::create([
            'user_id' => $buyer->id,
            'service_order_id' => $dueOrder->id,
            'frequency' => ServiceReminder::FREQUENCY_MONTHLY,
            'next_reminder_at' => now()->subDay(),
            'is_active' => true,
        ]);
        ServiceReminder::create([
            'user_id' => $buyer->id,
            'service_order_id' => $futureOrder->id,
            'frequency' => ServiceReminder::FREQUENCY_MONTHLY,
            'next_reminder_at' => now()->addDay(),
            'is_active' => true,
        ]);
        ServiceReminder::create([
            'user_id' => $buyer->id,
            'service_order_id' => $pausedOrder->id,
            'frequency' => ServiceReminder::FREQUENCY_MONTHLY,
            'next_reminder_at' => now()->subDay(),
            'is_active' => false,
        ]);

        $this->artisan('service-reminders:send')->assertSuccessful();

        Notification::assertSentToTimes($buyer, ServiceReminderNotification::class, 1);
        $due->refresh();
        $this->assertTrue($due->is_active);
        $this->assertSame(1, $due->reminders_sent_count);
        $this->assertTrue($due->next_reminder_at->isFuture());
    }

    public function test_email_channel_requires_both_reminder_and_account_consent(): void
    {
        [$order, $buyer] = $this->completedOrder();
        $reminder = ServiceReminder::create([
            'user_id' => $buyer->id,
            'service_order_id' => $order->id,
            'frequency' => ServiceReminder::FREQUENCY_ONCE,
            'next_reminder_at' => now()->addDay(),
            'send_email' => true,
        ])->load('serviceOrder.ad');
        $notification = new ServiceReminderNotification($reminder);

        $buyer->forceFill(['email_notifications' => false]);
        $this->assertSame(['database'], $notification->via($buyer));

        $buyer->forceFill(['email_notifications' => true]);
        $this->assertSame(['database', 'mail'], $notification->via($buyer));
    }

    public function test_a_late_recurring_reminder_is_advanced_directly_into_the_future(): void
    {
        [$order, $buyer] = $this->completedOrder();
        $reminder = ServiceReminder::create([
            'user_id' => $buyer->id,
            'service_order_id' => $order->id,
            'frequency' => ServiceReminder::FREQUENCY_MONTHLY,
            'next_reminder_at' => now()->subMonths(8),
            'is_active' => true,
        ]);

        $this->assertTrue($reminder->nextOccurrence()->isFuture());
    }

    public function test_database_failure_keeps_reminder_due_without_false_history(): void
    {
        [$order, $buyer] = $this->completedOrder();
        $reminder = $this->dueReminder($order, $buyer);
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Notifications\Events\NotificationSending::class,
            function ($event) {
                if ($event->notification instanceof ServiceReminderNotification && $event->channel === 'database') {
                    throw new \RuntimeException('Simulated storage failure');
                }
            });

        $this->artisan('service-reminders:send')->assertFailed();
        $this->assertTrue($reminder->fresh()->is_active);
        $this->assertNull($reminder->fresh()->last_sent_at);
        $this->assertSame(0, $reminder->fresh()->reminders_sent_count);
        $this->assertSame(0, $buyer->notifications()->count());
    }

    public function test_email_failure_preserves_one_real_notification_and_records_failure(): void
    {
        [$order, $buyer] = $this->completedOrder();
        $buyer->update(['email_notifications' => true]);
        $reminder = $this->dueReminder($order, $buyer);
        $reminder->update(['send_email' => true]);
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Notifications\Events\NotificationSending::class,
            function ($event) {
                if ($event->notification instanceof ServiceReminderNotification && $event->channel === 'mail') {
                    throw new \RuntimeException('Simulated email failure');
                }
            });

        $this->artisan('service-reminders:send')->assertFailed();
        $this->assertSame('failed', $reminder->fresh()->last_email_status);
        $this->assertSame(1, $reminder->fresh()->reminders_sent_count);
        $this->assertSame(1, $buyer->notifications()->count());
        $this->artisan('service-reminders:send')->assertSuccessful();
        $this->assertSame(1, $buyer->notifications()->count());
        $this->actingAs($buyer)->get(route('service-orders.index'))->assertOk()
            ->assertSee('L’envoi du dernier e-mail n’a pas pu être confirmé.');
    }

    public function test_elapsed_reminder_needs_future_date_before_reactivation(): void
    {
        [$order, $buyer] = $this->completedOrder();
        $reminder = $this->dueReminder($order, $buyer);
        $reminder->update(['is_active' => false]);
        $this->actingAs($buyer)->post(route('service-reminders.toggle', $reminder), [
            'service_order_id' => $order->id,
        ])->assertSessionHasErrors('next_reminder_at');
        $this->assertFalse($reminder->fresh()->is_active);
    }

    public function test_ineligible_order_is_skipped_without_history_or_notification(): void
    {
        [$order, $buyer] = $this->completedOrder();
        $reminder = $this->dueReminder($order, $buyer);
        $order->update(['status' => ServiceOrder::STATUS_REFUNDED]);
        $this->artisan('service-reminders:send')->assertSuccessful();
        $this->assertFalse($reminder->fresh()->is_active);
        $this->assertNull($reminder->fresh()->last_sent_at);
        $this->assertSame(0, $buyer->notifications()->count());
    }

    private function dueReminder(ServiceOrder $order, User $buyer): ServiceReminder
    {
        return ServiceReminder::create([
            'user_id' => $buyer->id,
            'service_order_id' => $order->id,
            'frequency' => ServiceReminder::FREQUENCY_ONCE,
            'next_reminder_at' => now()->subMinute(),
            'is_active' => true,
        ]);
    }

    private function completedOrder(string $title = 'Entretien plomberie', ?User $buyer = null): array
    {
        $buyer ??= User::factory()->create();
        $seller = User::factory()->create();
        $ad = Ad::create([
            'title' => $title,
            'description' => 'Prestation terminée utilisée pour le test du rappel.',
            'category' => 'Services',
            'location' => 'Mamoudzou',
            'price' => 80,
            'service_type' => 'offre',
            'status' => 'active',
            'user_id' => $seller->id,
        ]);
        $order = ServiceOrder::create([
            'order_number' => 'CMD-REM-'.fake()->unique()->numerify('######'),
            'ad_id' => $ad->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'amount' => 80,
            'commission_amount' => 8,
            'seller_amount' => 72,
            'status' => ServiceOrder::STATUS_COMPLETED,
            'payment_status' => ServiceOrder::PAYMENT_RELEASED,
            'released_at' => now(),
        ]);

        return [$order, $buyer, $seller];
    }
}
