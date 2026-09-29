<?php

namespace Tests\Feature\Feed;

use App\Models\Ad;
use App\Models\ServiceOrder;
use App\Models\ServiceProposal;
use App\Models\User;
use App\Services\ClientActivityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedClientActivityFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_one_priority_three_other_items_and_the_exact_total_without_counting_an_assigned_ad_twice(): void
    {
        $client = User::factory()->create();
        $seller = User::factory()->create();
        $order = $this->order($client, $seller, $this->ad($client, 'Électricien à confirmer'));
        foreach (range(1, 5) as $number) {
            $this->ad($client, 'Besoin '.$number);
        }
        $this->ad($client, 'Offre à exclure', ['service_type' => 'offre']);
        $this->ad($client, 'Demande expirée', ['expires_at' => now()->subDay()]);
        $response = $this->actingAs($client)->get(route('feed'))->assertOk();
        $summary = $response->viewData('pkClientActivity');
        $this->assertSame(6, $summary['total']);
        $this->assertSame(5, $summary['request_count']);
        $this->assertSame(1, $summary['order_count']);
        $this->assertSame('order-'.$order->id, $summary['primary']['key']);
        $this->assertCount(3, $summary['others']);
        $response->assertSee('Votre prochaine action')->assertDontSee('Également en cours')
            ->assertSee(route('home'), false)
            ->assertSee(route('service-orders.index').'#order-'.$order->id, false);
        $this->assertSame(1, substr_count($response->getContent(), 'data-activity-key="order-'.$order->id.'"'));
    }

    public function test_payment_demotes_the_mission_completion_removes_it_and_the_next_item_takes_over(): void
    {
        $client = User::factory()->create();
        $seller = User::factory()->create();
        $order = $this->order($client, $seller, $this->ad($client, 'Mission à payer'));
        $answered = $this->ad($client, 'Demande avec proposition');
        ServiceProposal::create(['ad_id' => $answered->id, 'provider_id' => $seller->id,
            'status' => ServiceProposal::STATUS_PENDING, 'amount' => 80, 'message' => 'Disponible demain']);
        $activity = app(ClientActivityService::class);
        $initial = $activity->summary($client);
        $this->assertSame('order-'.$order->id, $initial['primary']['key']);
        $order->update(['status' => ServiceOrder::STATUS_FUNDED, 'payment_status' => ServiceOrder::PAYMENT_PAID]);
        $funded = $activity->summary($client);
        $this->assertSame('request-'.$answered->id, $funded['primary']['key']);
        $this->assertSame('order-'.$order->id, $funded['others']->first()['key']);
        $this->assertNotSame($initial['revision'], $funded['revision']);
        $order->update(['status' => ServiceOrder::STATUS_COMPLETED, 'payment_status' => ServiceOrder::PAYMENT_RELEASED]);
        $completed = $activity->summary($client);
        $this->assertSame(1, $completed['total']);
        $this->assertEmpty($completed['others']);
        $answered->update(['status' => 'archived']);
        $this->assertSame(0, $activity->summary($client)['total']);
    }

    public function test_reserved_offers_and_pending_confirmation_are_included_even_if_the_ad_is_archived(): void
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create();
        $offer = $this->ad($seller, 'Prestation réservée', ['service_type' => 'offre', 'status' => 'archived']);
        $order = $this->order($buyer, $seller, $offer, ServiceOrder::STATUS_PENDING_ACCEPTANCE);
        $this->order($buyer, $seller, $offer, ServiceOrder::STATUS_REFUNDED);
        $this->order(User::factory()->create(), $seller, $offer);
        $summary = app(ClientActivityService::class)->summary($buyer);
        $this->assertSame(1, $summary['total']);
        $this->assertSame('order-'.$order->id, $summary['primary']['key']);
        $this->actingAs($buyer)->get(route('client-activity.index'))->assertRedirect(route('home'));
        $this->get(route('home'))->assertOk()
            ->assertSee('Prestation réservée')->assertSee('En attente du prestataire');
    }

    public function test_full_view_is_owned_paginated_and_keeps_all_remaining_requests_accessible(): void
    {
        $client = User::factory()->create();
        foreach (range(1, 12) as $number) {
            $this->ad($client, 'Demande personnelle '.$number);
        }
        $this->ad(User::factory()->create(), 'Titre privé du voisin');
        $this->actingAs($client)->get(route('client-activity.index'))->assertRedirect(route('home'));
        $page = $this->get(route('home'))->assertOk()->assertDontSee('Titre privé du voisin');
        $this->assertSame(12, $page->viewData('activityItems')->total());
        $this->assertSame(12, $page->viewData('activityCounts')['waiting']);
        $this->assertCount(10, $page->viewData('activityItems'));
        $page2 = $this->get(route('home', ['page' => 2]))->assertOk()->assertDontSee('Titre privé du voisin');
        $this->assertCount(2, $page2->viewData('activityItems'));
        $this->assertSame(2, $page2->viewData('activityItems')->currentPage());
    }

    public function test_a_refunded_mission_does_not_resurface_as_a_new_unanswered_request(): void
    {
        $client = User::factory()->create();
        $this->order($client, User::factory()->create(), $this->ad($client, 'Mission remboursée'), ServiceOrder::STATUS_REFUNDED);
        $this->assertSame(0, app(ClientActivityService::class)->summary($client)['total']);
    }

    public function test_refresh_is_private_detects_completion_and_exposes_only_the_signed_in_users_activity(): void
    {
        $this->getJson(route('client-activity.refresh'))->assertUnauthorized();
        $this->get(route('client-activity.index'))->assertRedirect(route('login'));
        $client = User::factory()->create();
        $ad = $this->ad($client, 'Ma demande personnelle');
        $this->ad(User::factory()->create(), 'Demande confidentielle du voisin');
        $summary = app(ClientActivityService::class)->summary($client);
        $this->actingAs($client)->getJson(route('client-activity.refresh', ['revision' => $summary['revision']]))
            ->assertOk()->assertJson(['changed' => false, 'html' => null]);
        $ad->update(['status' => 'archived']);
        $response = $this->getJson(route('client-activity.refresh', ['revision' => $summary['revision']]))
            ->assertOk()->assertJson(['changed' => true]);
        $this->assertSame('', trim($response->json('html')));
        $this->assertStringNotContainsString('Demande confidentielle du voisin', $response->json('html'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_a_focused_demand_reaches_its_tracking_page_and_cannot_select_another_owners_card(): void
    {
        $client = User::factory()->create();
        $older = $this->ad($client, 'Demande ancienne à retrouver');
        foreach (range(1, 12) as $number) {
            $this->ad($client, 'Demande plus récente '.$number);
        }
        $this->actingAs($client)->get(route('demands.tracking', ['demande' => $older->id]))
            ->assertRedirect(route('home', ['demande' => $older->id]));
        $focused = $this->get(route('home', ['demande' => $older->id]))
            ->assertOk()->assertSee('id="request-'.$older->id.'"', false);
        $this->assertSame(1, $focused->viewData('activityItems')->currentPage());
        $this->assertSame('request-'.$older->id, $focused->viewData('activityItems')->first()['key']);
        $this->assertSame('waiting', $focused->viewData('activityFilter'));
        $other = $this->ad(User::factory()->create(), 'Privée');
        $this->get(route('home', ['demande' => $other->id]))->assertOk()->assertDontSee('Privée')
            ->assertViewHas('activityFilter', 'active');
    }

    private function ad(User $user, string $title, array $attributes = []): Ad
    {
        return Ad::create(array_merge(['user_id' => $user->id, 'title' => $title, 'description' => 'Description de test.',
            'category' => 'Plombier', 'service_type' => 'demande', 'status' => 'active',
            'visibility' => 'public', 'location' => 'Mamoudzou', 'expires_at' => now()->addDays(30)], $attributes));
    }

    private function order(User $buyer, User $seller, Ad $ad, string $status = ServiceOrder::STATUS_AWAITING_PAYMENT): ServiceOrder
    {
        return ServiceOrder::create(['buyer_id' => $buyer->id, 'seller_id' => $seller->id, 'ad_id' => $ad->id,
            'order_number' => 'CMD-'.uniqid(), 'amount' => 80, 'commission_amount' => 8, 'seller_amount' => 72,
            'status' => $status, 'payment_status' => ServiceOrder::PAYMENT_AWAITING]);
    }
}
