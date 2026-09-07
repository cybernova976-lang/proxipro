<?php

namespace Tests\Feature\Admin;

use App\Models\Ad;
use App\Models\IdentityVerification;
use App\Models\Report;
use App\Models\ServiceOrder;
use App\Models\StripeWebhookEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationsQueueFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_one_prioritized_queue_with_direct_next_actions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $client = User::factory()->create(['name' => 'Cliente File']);
        $provider = User::factory()->create(['name' => 'Prestataire File']);

        $demand = $this->demand($client, 'Demande ancienne sans proposition');
        $demand->forceFill(['created_at' => now()->subHours(4), 'updated_at' => now()->subHours(4)])->saveQuietly();

        $reportedAd = $this->demand($client, 'Annonce signalée à examiner');
        $report = Report::create([
            'reporter_id' => $provider->id,
            'ad_id' => $reportedAd->id,
            'reason' => 'contenu_inapproprie',
            'status' => 'pending',
        ]);

        $verification = IdentityVerification::create([
            'user_id' => $provider->id,
            'document_type' => 'id_card',
            'status' => 'pending',
            'submitted_at' => now()->subDay(),
        ]);

        $order = ServiceOrder::create([
            'order_number' => 'CMD-QUEUE-001',
            'ad_id' => $reportedAd->id,
            'buyer_id' => $client->id,
            'seller_id' => $provider->id,
            'amount' => 100,
            'commission_amount' => 10,
            'seller_amount' => 90,
            'status' => ServiceOrder::STATUS_DISPUTED,
            'payment_status' => ServiceOrder::PAYMENT_DISPUTED,
            'disputed_at' => now()->subHours(3),
        ]);

        $event = StripeWebhookEvent::create([
            'event_id' => 'evt_queue_failed',
            'event_type' => 'checkout.session.completed',
            'status' => StripeWebhookEvent::STATUS_FAILED,
            'attempts' => 2,
            'error_message' => 'Erreur de test contrôlée',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.operations'));

        $response->assertOk()
            ->assertSee('À traiter')
            ->assertSee('Cette page ne relance, ne modifie et ne clôture aucun dossier automatiquement.')
            ->assertSeeInOrder([
                'Incident de paiement',
                'Litige',
                'Signalement',
                'Vérification d’identité',
                'Demande sans réponse',
            ])
            ->assertSee(route('admin.payments.index').'#event-'.$event->id, false)
            ->assertSee(route('admin.service-orders.index', ['status' => ServiceOrder::STATUS_DISPUTED]).'#order-'.$order->id, false)
            ->assertSee(route('admin.reports.show', $report->id), false)
            ->assertSee(route('admin.verifications.show', $verification->id), false)
            ->assertSee(route('admin.ads.show', $demand->id), false);

        $items = $response->viewData('items');
        $this->assertSame(['payment', 'dispute', 'report', 'verification', 'unanswered'], $items->pluck('type')->all());
        $this->assertSame(5, $response->viewData('counts')->get('total'));
    }

    public function test_queue_excludes_non_actionable_and_fresh_records(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $client = User::factory()->create();
        $freshDemand = $this->demand($client, 'Demande encore récente');

        StripeWebhookEvent::create([
            'event_id' => 'evt_queue_processed',
            'event_type' => 'payment_intent.succeeded',
            'status' => StripeWebhookEvent::STATUS_PROCESSED,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.operations'));

        $response->assertOk()
            ->assertSee('Aucun dossier prioritaire')
            ->assertDontSee($freshDemand->title);
        $this->assertSame(0, $response->viewData('counts')->get('total'));
    }

    public function test_regular_user_cannot_open_operations_queue(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)
            ->get(route('admin.operations'))
            ->assertForbidden();
    }

    private function demand(User $client, string $title): Ad
    {
        return Ad::create([
            'title' => $title,
            'description' => 'Description suffisamment longue pour le test de la file administrative.',
            'category' => 'Plombier',
            'location' => 'Mamoudzou',
            'service_type' => 'demande',
            'status' => 'active',
            'expires_at' => now()->addMonth(),
            'user_id' => $client->id,
        ]);
    }
}
