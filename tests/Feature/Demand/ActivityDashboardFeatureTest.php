<?php

namespace Tests\Feature\Demand;

use App\Models\Ad;
use App\Models\ProSubscription;
use App\Models\ServiceOrder;
use App\Models\ServiceProposal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityDashboardFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutExceptionHandling();
    }

    public function test_shared_dashboard_is_owned_deduplicated_and_filters_terminal_orders_into_history(): void
    {
        $client = User::factory()->create();
        $seller = User::factory()->create();
        $waiting = $this->demand($client, 'Besoin en attente');
        $answered = $this->demand($client, 'Besoin avec proposition');
        ServiceProposal::create(['ad_id' => $answered->id, 'provider_id' => $seller->id, 'amount' => 60,
            'message' => 'Disponible demain', 'status' => ServiceProposal::STATUS_PENDING]);
        $pay = $this->booking($client, $seller, $this->demand($client, 'Prestation à payer'), ServiceOrder::STATUS_AWAITING_PAYMENT);
        $funded = $this->booking($client, $seller, $this->demand($client, 'Prestation financée'), ServiceOrder::STATUS_FUNDED);
        $done = $this->booking($client, $seller, $this->demand($client, 'Prestation terminée'), ServiceOrder::STATUS_COMPLETED);
        $this->demand($seller, 'Activité privée du voisin');
        $this->booking($seller, $client, $this->demand($seller, 'Commande privée du voisin'), ServiceOrder::STATUS_FUNDED);

        $response = $this->actingAs($client)->get(route('home'))->assertOk()->assertDontSee('Activité privée du voisin')->assertDontSee('Commande privée du voisin');
        $this->assertSame(['action' => 2, 'waiting' => 1, 'ongoing' => 1, 'closed' => 1], $response->viewData('activityCounts'));
        $this->assertCount(4, $response->viewData('activityItems'));
        $this->assertSame(1, substr_count($response->getContent(), 'id="order-'.$pay->id.'"'));
        $response->assertDontSee('id="request-'.$pay->ad_id.'"', false)->assertDontSee('Prestation terminée');
        $action = $this->get(route('home', ['etat' => 'action']))->assertOk();
        $this->assertCount(2, $action->viewData('activityItems'));
        $action->assertSee(route('proposals.compare', $answered), false)->assertDontSee('Besoin en attente');
        $history = $this->get(route('home', ['etat' => 'closed']))->assertOk()->assertSee('Mission terminée')->assertSee('Prestation terminée');
        $this->assertSame('order-'.$done->id, $history->viewData('activityItems')->first()['key']);
        $this->get(route('dashboard.overview', ['etat' => 'ongoing']))->assertOk()->assertSee('Prestation financée')->assertDontSee('Besoin en attente');
    }

    public function test_pagination_focus_and_invalid_filter_do_not_disclose_other_members(): void
    {
        $client = User::factory()->create();
        $old = $this->demand($client, 'Mon besoin ancien');
        $old->forceFill(['created_at' => now()->subDays(10)])->save();
        foreach (range(1, 13) as $number) {
            $this->demand($client, 'Mon besoin '.$number);
        }
        $private = $this->demand(User::factory()->create(), 'Besoin secret');
        $page = $this->actingAs($client)->get(route('home', ['etat' => 'invalid']))->assertOk();
        $this->assertCount(10, $page->viewData('activityItems'));
        $this->assertSame('active', $page->viewData('activityFilter'));
        $this->assertCount(4, $this->get(route('home', ['page' => 2]))->viewData('activityItems'));
        $focused = $this->get(route('home', ['demande' => $old->id]))->assertOk()->assertSee('Mon besoin ancien');
        $this->assertSame('request-'.$old->id, $focused->viewData('activityItems')->first()['key']);
        $this->get(route('home', ['demande' => $private->id]))->assertOk()->assertDontSee('Besoin secret');
    }

    public function test_declared_urgency_and_free_plan_are_not_paid_purchases(): void
    {
        $client = User::factory()->create(['plan' => 'FREE']);
        $urgent = $this->demand($client, 'Urgence déclarée sans achat');
        $urgent->update(['is_urgent' => true, 'urgent_until' => null]);
        $response = $this->actingAs($client)->get(route('dashboard.account'))->assertOk()
            ->assertSee('Gratuit')->assertDontSee('Renouveler')->assertDontSee('Urgence déclarée sans achat')->assertDontSee('Options de visibilité actives');
        $this->assertNull($response->viewData('subscription'));
        $urgent->update(['urgent_until' => now()->addDays(3)]);
        $this->get(route('dashboard.account'))->assertOk()->assertSee('Options de visibilité actives')->assertSee('Urgence déclarée sans achat')->assertDontSee('Mes abonnements & achats actifs');
        $this->get(route('home'))->assertOk()->assertDontSee('Points disponibles')->assertDontSee('Acheter');
    }

    public function test_subscription_uses_the_real_active_record_and_expiry(): void
    {
        $client = User::factory()->create(['plan' => 'FREE']);
        $subscription = ProSubscription::create(['user_id' => $client->id, 'plan' => 'monthly', 'amount' => 19,
            'status' => 'active', 'starts_at' => now(), 'ends_at' => now()->addMonth()]);
        $this->actingAs($client)->get(route('dashboard.account'))->assertOk()
            ->assertSee($subscription->getPlanLabel())->assertSee($subscription->ends_at->format('d/m/Y'))->assertSee('Gérer mon abonnement');
        $subscription->update(['status' => 'cancelled']);
        $this->get(route('dashboard.account'))->assertOk()->assertDontSee('Gérer mon abonnement')->assertSee('Gratuit');
    }

    public function test_waiting_is_neutral_even_after_two_hours_and_completion_changes_the_compact_reminder(): void
    {
        $client = User::factory()->create();
        $seller = User::factory()->create();
        $old = $this->demand($client, 'Besoin déjà bien décrit');
        $old->forceFill(['created_at' => now()->subDays(2)])->save();
        $summary = app(\App\Services\ClientActivityService::class)->summary($client);
        $html = view('feed.partials.client-activity', ['pkClientActivity' => $summary])->render();
        $this->assertStringContainsString('Voir mon suivi', $html);
        $this->assertStringNotContainsString('Besoin déjà bien décrit', $html);
        $this->assertStringNotContainsString('Toujours aucune', $html);
        $order = $this->booking($client, $seller, $this->demand($client, 'Mission à régler'), ServiceOrder::STATUS_AWAITING_PAYMENT);
        $initial = app(\App\Services\ClientActivityService::class)->summary($client);
        $this->assertStringContainsString('Mission à régler', view('feed.partials.client-activity', ['pkClientActivity' => $initial])->render());
        $order->update(['status' => ServiceOrder::STATUS_COMPLETED]);
        $refresh = $this->actingAs($client)->getJson(route('client-activity.refresh', ['revision' => $initial['revision']]))->assertOk()->assertJson(['changed' => true]);
        $this->assertStringNotContainsString('Mission à régler', $refresh->json('html'));
        $this->assertSame(1, app(\App\Services\ClientActivityService::class)->summary($client)['total']);
    }

    public function test_inactive_ads_move_to_history_and_old_demand_link_finds_its_assigned_order(): void
    {
        $client = User::factory()->create();
        $seller = User::factory()->create();
        $inactive = $this->demand($client, 'Demande désactivée');
        $inactive->update(['status' => 'inactive']);
        $ad = $this->demand($client, 'Ancien besoin attribué');
        $order = $this->booking($client, $seller, $ad, ServiceOrder::STATUS_FUNDED);
        $order->forceFill(['updated_at' => now()->subDays(10)])->save();
        foreach (range(1, 12) as $number) {
            $this->booking($client, $seller, $this->demand($client, 'Mission récente '.$number), ServiceOrder::STATUS_FUNDED);
        }
        $this->actingAs($client)->get(route('home'))->assertOk()->assertDontSee('Demande désactivée');
        $this->get(route('home', ['etat' => 'closed']))->assertOk()->assertSee('Demande archivée')->assertSee('Demande désactivée');
        $focused = $this->get(route('home', ['demande' => $ad->id]))->assertOk()->assertSee('Ancien besoin attribué');
        $this->assertSame('order-'.$order->id, $focused->viewData('activityItems')->first()['key']);
    }

    private function demand(User $user, string $title): Ad
    {
        return Ad::create(['user_id' => $user->id, 'title' => $title, 'description' => 'Un besoin précis avec les informations nécessaires.',
            'category' => 'Plombier', 'service_type' => 'demande', 'status' => 'active', 'visibility' => 'public',
            'location' => 'Mamoudzou', 'expires_at' => now()->addDays(30)]);
    }

    private function booking(User $buyer, User $seller, Ad $ad, string $status): ServiceOrder
    {
        return ServiceOrder::create(['buyer_id' => $buyer->id, 'seller_id' => $seller->id, 'ad_id' => $ad->id,
            'order_number' => 'CMD-'.uniqid(), 'amount' => 60, 'commission_amount' => 6, 'seller_amount' => 54,
            'status' => $status, 'payment_status' => ServiceOrder::PAYMENT_AWAITING]);
    }
}
