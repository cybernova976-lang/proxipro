<?php

namespace Tests\Feature\Ads;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdPublicationArchitectureFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_particular_create_form_only_shows_personal_request_option(): void
    {
        $user = User::factory()->create([
            'user_type' => 'particulier',
            'is_service_provider' => false,
            'plan' => 'FREE',
        ]);

        $response = $this->actingAs($user)->get(route('ads.create'));

        $response->assertOk();
        $response->assertSee('Demande de particulier');
        $response->assertDontSee('Offre professionnelle');
        $response->assertDontSee('Vous proposez un service, une location de matériel, une promotion ou un recrutement.');
    }

    public function test_professional_create_form_shows_professional_offer_option(): void
    {
        $user = User::factory()->create([
            'user_type' => 'professionnel',
            'plan' => 'FREE',
        ]);

        $response = $this->actingAs($user)->get(route('ads.create'));

        $response->assertOk();
        $response->assertSee('Demande de particulier');
        $response->assertSee('Offre professionnelle');
    }

    public function test_mobile_category_grid_uses_columns_that_fit_the_viewport(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('ads.create'));

        $response->assertOk();
        $response->assertSee('grid-template-columns: repeat(2, minmax(0, 1fr));', false);
        $response->assertSee('overflow-wrap: anywhere;', false);
    }

    public function test_particular_user_cannot_publish_professional_offer(): void
    {
        $user = User::factory()->create([
            'user_type' => 'particulier',
            'is_service_provider' => false,
            'plan' => 'FREE',
        ]);

        $response = $this
            ->actingAs($user)
            ->from(route('ads.create'))
            ->post(route('ads.store'), $this->payload(['service_type' => 'offre']));

        $response->assertRedirect(route('ads.create'));
        $response->assertSessionHasErrors('service_type');
        $this->assertDatabaseMissing('ads', [
            'title' => 'Publication architecture test',
            'user_id' => $user->id,
        ]);
    }

    public function test_professional_user_can_publish_professional_offer(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([], 200),
        ]);

        $user = User::factory()->create([
            'user_type' => 'professionnel',
            'plan' => 'FREE',
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('ads.store'), $this->payload(['service_type' => 'offre']));

        $response->assertRedirect();
        $this->assertDatabaseHas('ads', [
            'title' => 'Publication architecture test',
            'service_type' => 'offre',
            'user_id' => $user->id,
        ]);
        $ad = $user->ads()->firstOrFail();
        $this->assertNotNull($ad->expires_at);
        $this->assertTrue($ad->expires_at->between(now()->addDays(89), now()->addDays(91)));
        $this->assertNotNull($ad->publication_terms_accepted_at);
        $this->assertSame('2026-07-16', $ad->publication_terms_version);
    }

    public function test_publication_requires_explicit_acceptance_of_the_rules(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->from(route('ads.create'))
            ->post(route('ads.store'), $this->payload(['accept_conditions' => null]));

        $response->assertRedirect(route('ads.create'));
        $response->assertSessionHasErrors('accept_conditions');
        $this->assertDatabaseCount('ads', 0);
    }

    public function test_recent_duplicate_publication_is_rejected(): void
    {
        Http::fake(['nominatim.openstreetmap.org/*' => Http::response([], 200)]);
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('ads.store'), $this->payload())->assertRedirect();
        $this->actingAs($user)
            ->from(route('ads.create'))
            ->post(route('ads.store'), $this->payload())
            ->assertSessionHasErrors('title');

        $this->assertDatabaseCount('ads', 1);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Publication architecture test',
            'description' => 'Description suffisamment detaillee pour le test de publication.',
            'category' => 'Plomberie',
            'country' => 'Mayotte',
            'city' => 'Mamoudzou',
            'location' => 'Mamoudzou',
            'price' => 120,
            'service_type' => 'demande',
            'visibility' => 'public',
            'reply_restriction' => 'everyone',
            'accept_conditions' => '1',
        ], $overrides);
    }
}
