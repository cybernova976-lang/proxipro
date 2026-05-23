<?php

namespace Tests\Feature\Ads;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdPublicationArchitectureFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_form_shows_publication_architecture_selector(): void
    {
        $user = User::factory()->create([
            'user_type' => 'particulier',
            'is_service_provider' => false,
            'plan' => 'FREE',
        ]);

        $response = $this->actingAs($user)->get(route('ads.create'));

        $response->assertOk();
        $response->assertSee('Demande de particulier');
        $response->assertSee('Offre professionnelle');
        $response->assertSee('Réservé aux comptes professionnels ou prestataires valides.');
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
