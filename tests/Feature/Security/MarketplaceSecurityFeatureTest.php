<?php

namespace Tests\Feature\Security;

use App\Models\Ad;
use App\Models\ServiceOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceSecurityFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_ad_mutation_routes_require_authentication(): void
    {
        $this->get(route('ads.create'))->assertRedirect(route('login'));
        $this->post(route('ads.store'), [])->assertRedirect(route('login'));
    }

    public function test_public_contact_and_demand_forms_are_accessible(): void
    {
        $this->get(route('contact.index'))->assertOk();
        $this->get(route('demand.create'))->assertOk();
    }

    public function test_user_search_never_exposes_email_addresses(): void
    {
        $viewer = User::factory()->create();
        $professional = User::factory()->create([
            'name' => 'Marie Plomberie',
            'email' => 'private-search@example.test',
            'profession' => 'Plombiere',
        ]);

        $response = $this->actingAs($viewer)->getJson(route('users.search', ['q' => 'Marie']));

        $response->assertOk()->assertJsonFragment([
            'id' => $professional->id,
            'name' => 'Marie Plomberie',
        ]);
        $this->assertStringNotContainsString('private-search@example.test', $response->getContent());
    }

    public function test_review_requires_a_completed_paid_service_order_between_both_users(): void
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create();
        $ad = Ad::create([
            'title' => 'Mission a evaluer',
            'description' => 'Une mission reelle terminee',
            'category' => 'Plomberie',
            'location' => 'Mamoudzou',
            'price' => 100,
            'service_type' => 'offre',
            'status' => 'active',
            'user_id' => $seller->id,
        ]);

        $order = ServiceOrder::create([
            'order_number' => 'CMD-REVIEW-1',
            'ad_id' => $ad->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'amount' => 100,
            'commission_amount' => 10,
            'seller_amount' => 90,
            'status' => ServiceOrder::STATUS_FUNDED,
            'payment_status' => ServiceOrder::PAYMENT_PAID,
        ]);

        $this->actingAs($buyer)
            ->post(route('reviews.store', $seller), [
                'service_order_id' => $order->id,
                'rating' => 5,
                'comment' => 'Travail soigne et ponctuel.',
            ])
            ->assertNotFound();

        $order->update([
            'status' => ServiceOrder::STATUS_COMPLETED,
            'payment_status' => ServiceOrder::PAYMENT_RELEASED,
            'released_at' => now(),
        ]);

        $this->actingAs($buyer)
            ->post(route('reviews.store', $seller), [
                'service_order_id' => $order->id,
                'rating' => 5,
                'comment' => 'Travail soigne et ponctuel.',
            ])
            ->assertRedirect(route('profile.public', $seller));

        $this->assertDatabaseHas('reviews', [
            'service_order_id' => $order->id,
            'reviewer_id' => $buyer->id,
            'reviewed_user_id' => $seller->id,
            'rating' => 5,
        ]);
    }

    public function test_stripe_webhook_is_not_blocked_by_csrf_and_requires_a_signature(): void
    {
        config(['services.stripe.webhook_secret' => 'whsec_test']);

        $this->postJson(route('stripe.webhook'), [])->assertStatus(400);
    }
}
