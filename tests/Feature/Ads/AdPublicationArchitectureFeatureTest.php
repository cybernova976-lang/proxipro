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
        ], $overrides);
    }
}
