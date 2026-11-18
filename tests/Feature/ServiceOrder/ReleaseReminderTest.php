<?php

namespace Tests\Feature\ServiceOrder;

use App\Models\Ad;
use App\Models\ServiceOrder;
use App\Models\User;
use App\Notifications\ServiceOrderStatusNotification;
use App\Services\ReleaseReminderService;
use App\Services\StripeConnectService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Mockery;
use Tests\TestCase;

/**
 * Un client qui a payé mais oublie de confirmer la fin de la prestation est
 * relancé : sans sa confirmation, le prestataire n'est pas payé. Le rappel
 * n'insiste pas sans fin et ne verse jamais rien de lui-même.
 */
class ReleaseReminderTest extends TestCase
{
    use RefreshDatabase;

    private function paidOrder(array $attributes = [], ?User $buyer = null, ?User $seller = null, string $adType = 'offre'): ServiceOrder
    {
        $buyer ??= User::factory()->create(['name' => 'Assia Cliente']);
        $seller ??= User::factory()->create(['name' => 'Karim Services', 'stripe_connect_account_id' => 'acct_test', 'stripe_connect_payouts_enabled' => true]);
        $ad = Ad::create([
            'title' => 'Dépannage électrique', 'description' => 'Dépannage électrique à domicile.',
            'category' => 'Électricien', 'location' => 'Mamoudzou', 'price' => 20,
            'service_type' => $adType, 'status' => 'active', 'user_id' => $seller->id,
        ]);

        return ServiceOrder::create($attributes + [
            'order_number' => 'CMD-TEST-'.fake()->unique()->numerify('#####'),
            'ad_id' => $ad->id, 'buyer_id' => $buyer->id, 'seller_id' => $seller->id,
            'amount' => 20, 'commission_amount' => 2, 'seller_amount' => 18,
            'status' => ServiceOrder::STATUS_FUNDED, 'payment_status' => ServiceOrder::PAYMENT_PAID,
            'stripe_payment_intent_id' => 'pi_paid', 'paid_at' => now(),
        ]);
    }

    private function remind(): array
    {
        return app(ReleaseReminderService::class)->run();
    }

    public function test_the_client_is_reminded_after_a_few_days_then_at_intervals_and_never_more_than_three_times(): void
    {
        Notification::fake();
        $this->travelTo('2026-11-01 10:00:00');
        $order = $this->paidOrder();

        // Trop tôt : la prestation vient d'être payée.
        $this->travelTo('2026-11-03 10:00:00');
        $this->assertSame(['sent' => 0, 'failed' => 0], $this->remind());

        // Trois jours après le paiement : premier rappel, une seule fois.
        $this->travelTo('2026-11-04 10:00:00');
        $this->assertSame(['sent' => 1, 'failed' => 0], $this->remind());
        $this->assertSame(['sent' => 0, 'failed' => 0], $this->remind());
        $this->assertSame(1, $order->fresh()->release_reminders_sent);
        $this->assertSame('04/11/2026', $order->fresh()->release_reminder_last_at->format('d/m/Y'));

        // Puis tous les trois jours.
        $this->travelTo('2026-11-06 10:00:00');
        $this->assertSame(0, $this->remind()['sent']);
        $this->travelTo('2026-11-07 10:00:00');
        $this->assertSame(1, $this->remind()['sent']);
        $this->travelTo('2026-11-10 10:00:00');
        $this->assertSame(1, $this->remind()['sent']);

        // Trois rappels au plus : ensuite, plus aucun message.
        $this->travelTo('2026-12-25 10:00:00');
        $this->assertSame(0, $this->remind()['sent']);
        $this->assertSame(3, $order->fresh()->release_reminders_sent);

        Notification::assertSentToTimes($order->buyer, ServiceOrderStatusNotification::class, 3);
        Notification::assertNotSentTo($order->seller, ServiceOrderStatusNotification::class);
        // Le rappel ne verse rien : la commande attend toujours la confirmation du client.
        $this->assertSame(ServiceOrder::STATUS_FUNDED, $order->fresh()->status);
        $this->assertNull($order->fresh()->stripe_transfer_id);
    }

    public function test_the_reminder_waits_for_the_date_the_client_asked_for(): void
    {
        Notification::fake();
        $this->travelTo('2026-11-01 10:00:00');
        $this->paidOrder(['scheduled_for' => '2026-11-20 00:00:00']);

        $this->travelTo('2026-11-22 10:00:00');
        $this->assertSame(0, $this->remind()['sent']);

        $this->travelTo('2026-11-23 10:00:00');
        $this->assertSame(1, $this->remind()['sent']);
    }

    public function test_no_reminder_when_there_is_nothing_to_confirm(): void
    {
        Notification::fake();
        $this->travelTo('2026-11-01 10:00:00');

        $this->paidOrder(['status' => ServiceOrder::STATUS_COMPLETED, 'payment_status' => ServiceOrder::PAYMENT_RELEASED, 'released_at' => now()]);
        $this->paidOrder(['status' => ServiceOrder::STATUS_DISPUTED, 'payment_status' => ServiceOrder::PAYMENT_DISPUTED]);
        $this->paidOrder(['status' => ServiceOrder::STATUS_AWAITING_PAYMENT, 'payment_status' => ServiceOrder::PAYMENT_AWAITING, 'paid_at' => null]);
        $this->paidOrder(['status' => ServiceOrder::STATUS_REFUNDED, 'payment_status' => ServiceOrder::PAYMENT_REFUNDED]);
        // Réservation faite à l'envers sur une demande : on ne relance pas.
        $this->paidOrder(adType: 'demande');
        // Compte client supprimé : personne à prévenir.
        $gone = User::factory()->create();
        $this->paidOrder(buyer: $gone);
        $gone->delete();

        $this->travelTo('2026-11-15 10:00:00');
        $this->assertSame(['sent' => 0, 'failed' => 0], $this->remind());
        Notification::assertNothingSent();
    }

    public function test_the_delays_come_from_the_configuration(): void
    {
        Notification::fake();
        config(['marketplace.release_reminders' => ['first_after_days' => 7, 'every_days' => 2, 'max' => 1]]);
        $this->travelTo('2026-11-01 10:00:00');
        $order = $this->paidOrder();

        $this->travelTo('2026-11-07 10:00:00');
        $this->assertSame(0, $this->remind()['sent']);
        $this->travelTo('2026-11-08 10:00:00');
        $this->assertSame(1, $this->remind()['sent']);
        $this->travelTo('2026-11-30 10:00:00');
        $this->assertSame(0, $this->remind()['sent']);
        $this->assertSame(1, $order->fresh()->release_reminders_sent);
    }

    public function test_the_reminder_says_what_to_do_and_the_provider_sees_it_was_sent(): void
    {
        $this->travelTo('2026-11-01 10:00:00');
        $order = $this->paidOrder()->load(['ad', 'buyer', 'seller']);

        $notification = new ServiceOrderStatusNotification($order, 'release_reminder');
        $data = $notification->toArray($order->buyer);
        $this->assertSame('service_order_release_reminder', $data['type']);
        $this->assertSame('Votre prestation est-elle terminée ?', $data['title']);
        $this->assertStringContainsString('Karim Services ne sera payé qu’à ce moment-là', $data['message']);
        $this->assertStringContainsString('En cas de problème, signalez-le', $data['message']);
        $this->assertSame(route('service-orders.index'), $data['action_url']);
        $this->assertSame('Votre prestation est-elle terminée ?', $notification->toMail($order->buyer)->subject);

        // Avant tout rappel, le prestataire ne voit aucune mention.
        $this->actingAs($order->seller)->get(route('service-orders.index'))->assertOk()
            ->assertDontSee('data-order-reminded', false);

        $this->travelTo('2026-11-04 10:00:00');
        $this->assertSame(1, $this->remind()['sent']);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $order->buyer_id, 'type' => ServiceOrderStatusNotification::class]);

        // Trois jours ont passé : la session précédente a expiré (déconnexion après une heure).
        $this->flushSession();
        $this->actingAs($order->seller)->get(route('service-orders.index'))->assertOk()
            ->assertSee('Nous avons rappelé au client de confirmer la fin de la prestation le 04/11/2026.');
    }

    public function test_once_all_reminders_are_sent_the_administrator_decides_and_nothing_is_paid_automatically(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['role' => 'admin']);
        $this->travelTo('2026-11-01 10:00:00');
        $order = $this->paidOrder();

        foreach (['2026-11-04', '2026-11-07', '2026-11-10'] as $day) {
            $this->travelTo($day.' 10:00:00');
            $this->remind();
        }
        // Le dernier rappel vient de partir : le client a encore le délai habituel pour répondre.
        $this->assertFalse($order->fresh()->isAwaitingAdminDecision());
        $this->flushSession();
        $this->actingAs($admin)->post(route('admin.service-orders.release', $order))->assertStatus(422);
        Notification::assertNotSentTo($admin, ServiceOrderStatusNotification::class);

        // Trois jours plus tard, toujours rien : l'administrateur est prévenu, une seule fois.
        $this->travelTo('2026-11-13 10:00:01');
        $this->assertSame(['sent' => 0, 'failed' => 0], $this->remind());
        $this->remind();
        $this->assertTrue($order->fresh()->isAwaitingAdminDecision());
        Notification::assertSentToTimes($admin, ServiceOrderStatusNotification::class, 1);
        Notification::assertSentTo($admin, ServiceOrderStatusNotification::class, function ($notification) use ($admin) {
            $data = $notification->toArray($admin);

            return $data['type'] === 'service_order_admin_decision_needed'
                && $data['action_url'] === route('admin.service-orders.index', ['status' => 'unconfirmed']);
        });
        // Rien n'a été versé tout seul.
        $this->assertSame(ServiceOrder::STATUS_FUNDED, $order->fresh()->status);
        $this->assertNull($order->fresh()->stripe_transfer_id);

        $this->flushSession();
        $this->actingAs($admin)->get(route('admin.service-orders.index'))->assertOk()
            ->assertSee('1 commande à trancher.')
            ->assertSee('data-order-unconfirmed', false)
            ->assertSee('Client sans réponse après 3 rappels (dernier le 10/11/2026).')
            ->assertSee('Payer le prestataire')->assertSee('Rembourser le client');
        $this->actingAs($admin)->get(route('admin.service-orders.index', ['status' => 'unconfirmed']))->assertOk()
            ->assertSee($order->order_number);

        // Chaque partie voit que la décision appartient désormais à la plateforme.
        $this->flushSession();
        $this->actingAs($order->seller)->get(route('service-orders.index'))->assertOk()
            ->assertSee('Prokejem examine la situation et vous tiendra informé de sa décision');
        $this->flushSession();
        $this->actingAs($order->buyer)->get(route('service-orders.index'))->assertOk()
            ->assertSee('Prokejem examine maintenant la situation')
            ->assertSee('Prestation terminée : payer le prestataire');
    }

    public function test_the_administrator_can_pay_the_provider_or_refund_the_client_of_an_unconfirmed_order(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['role' => 'admin']);
        $silent = ['metadata' => ['release_reminders_sent' => 3, 'release_reminder_last_at' => now()->subDays(4)->toIso8601String()]];
        $toPay = $this->paidOrder($silent);
        $toRefund = $this->paidOrder($silent);

        $connect = Mockery::mock(StripeConnectService::class);
        $connect->shouldReceive('transferToSeller')->once()->andReturn('tr_admin');
        $connect->shouldReceive('refundOrder')->once()->andReturn('re_admin');
        $this->app->instance(StripeConnectService::class, $connect);

        // Un membre ordinaire ne peut rien décider.
        $this->actingAs($toPay->seller)->post(route('admin.service-orders.release', $toPay));
        $this->assertSame(ServiceOrder::STATUS_FUNDED, $toPay->fresh()->status);

        $this->actingAs($admin)->post(route('admin.service-orders.release', $toPay), ['resolution_note' => 'Travail confirmé par photos.'])
            ->assertRedirect(route('admin.service-orders.index'))
            ->assertSessionHas('success', 'Décision enregistrée : le prestataire est payé. Les deux parties sont prévenues.');
        $toPay->refresh();
        $this->assertSame([ServiceOrder::STATUS_COMPLETED, ServiceOrder::PAYMENT_RELEASED, 'tr_admin', 'released', $admin->id],
            [$toPay->status, $toPay->payment_status, $toPay->stripe_transfer_id, $toPay->admin_resolution, (int) $toPay->admin_resolved_by]);

        $this->actingAs($admin)->post(route('admin.service-orders.refund', $toRefund), ['resolution_note' => 'Prestation non réalisée selon les deux parties.'])
            ->assertRedirect(route('admin.service-orders.index'))
            ->assertSessionHas('success', 'Décision enregistrée : le client est remboursé. Les deux parties sont prévenues.');
        $toRefund->refresh();
        $this->assertSame([ServiceOrder::STATUS_REFUNDED, 're_admin', 'refunded'], [$toRefund->status, $toRefund->stripe_refund_id, $toRefund->admin_resolution]);

        Notification::assertSentTo($toPay->buyer, ServiceOrderStatusNotification::class);
        Notification::assertSentTo($toRefund->seller, ServiceOrderStatusNotification::class);
    }

    public function test_the_command_runs_and_is_scheduled_by_day_only(): void
    {
        $this->artisan('service-orders:remind-release')
            ->expectsOutputToContain('0 rappel(s) de confirmation envoyé(s), 0 échec(s).')
            ->assertSuccessful();

        $event = collect(app(Schedule::class)->events())
            ->first(fn ($event) => str_contains((string) $event->command, 'service-orders:remind-release'));
        $this->assertNotNull($event, 'Le rappel de confirmation n’est pas planifié.');
        $this->assertSame('0 * * * *', $event->expression);
        $this->assertSame('Indian/Mayotte', (string) $event->timezone);
    }
}
