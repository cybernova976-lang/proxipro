<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\User;
use App\Models\UserService;
use App\Services\GeocodingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class GuidedDemandPublicationFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_guided_form_exposes_five_short_steps_and_local_draft_controls(): void
    {
        $response = $this->get(route('demand.create'));

        $response->assertOk()
            ->assertSee('Étape 1 sur 5')
            ->assertSee('Étape 5 sur 5')
            ->assertSee('name="desired_date"', false)
            ->assertSee('name="time_window"', false)
            ->assertSee('name="price_type"', false)
            ->assertSee('name="publication_confirmed"', false)
            ->assertSee('prokejem-demand-draft-v2-', false)
            ->assertSee('Les photos ne sont jamais enregistrées dans le brouillon local');
    }

    public function test_authenticated_client_can_publish_a_complete_guided_demand(): void
    {
        Notification::fake();
        $this->mock(GeocodingService::class, function ($mock): void {
            $mock->shouldReceive('geocode')->once()->andReturn(null);
        });

        $client = User::factory()->create();
        $desiredDate = today()->addDays(3)->toDateString();

        $response = $this->actingAs($client)->post(route('demand.store'), [
            'main_category' => 'Bricolage & Travaux',
            'category' => 'Plombier',
            'country' => 'Mayotte',
            'city' => 'Mamoudzou',
            'location' => 'Mamoudzou',
            'desired_date' => $desiredDate,
            'time_window' => 'morning',
            'title' => 'Réparer une fuite sous mon évier',
            'description' => 'Une fuite légère apparaît sous l’évier lorsque le robinet est ouvert.',
            'price_type' => 'negotiable',
            'urgency' => 'urgent',
            'publication_confirmed' => '1',
        ]);

        $ad = $client->ads()->sole();

        $response->assertRedirect(route('demand.matching', $ad));
        $this->assertSame('Bricolage & Travaux', $ad->main_category);
        $this->assertSame('services', $ad->publication_domain);
        $this->assertSame('negotiable', $ad->price_type);
        $this->assertNull($ad->price);
        $this->assertSame($desiredDate, $ad->ad_details['desired_date']);
        $this->assertSame('morning', $ad->ad_details['time_window']);
        $this->assertTrue($ad->is_urgent);
        $this->assertNotNull($ad->publication_terms_accepted_at);
        $this->assertTrue($ad->expires_at->isAfter(now()->addDays(29)));
    }

    public function test_fixed_budget_and_publication_confirmation_are_validated(): void
    {
        $client = User::factory()->create();

        $this->actingAs($client)
            ->from(route('demand.create'))
            ->post(route('demand.store'), [
                'main_category' => 'Bricolage & Travaux',
                'category' => 'Plombier',
                'country' => 'Mayotte',
                'city' => 'Mamoudzou',
                'desired_date' => today()->addDay()->toDateString(),
                'time_window' => 'flexible',
                'title' => 'Réparer une fuite sous mon évier',
                'description' => 'Une fuite légère apparaît sous l’évier lorsque le robinet est ouvert.',
                'price_type' => 'fixed',
            ])
            ->assertRedirect(route('demand.create'))
            ->assertSessionHasErrors(['price', 'publication_confirmed']);

        $this->assertDatabaseCount('ads', 0);
    }

    public function test_matching_page_explains_the_no_provider_fallback_without_false_notification_claim(): void
    {
        $client = User::factory()->create();
        $ad = $this->demand($client);

        $response = $this->actingAs($client)->get(route('demand.matching', $ad));

        $response->assertOk()
            ->assertSee('Aucun prestataire compatible immédiatement')
            ->assertSee('Préciser ma demande')
            ->assertSee('Partager la demande')
            ->assertSee('Suivre depuis le feed')
            ->assertDontSee('Les professionnels correspondants ont été notifiés');
    }

    public function test_matching_page_displays_nearby_alternatives_when_no_exact_provider_exists(): void
    {
        $client = User::factory()->create();
        $provider = User::factory()->create(['name' => 'Électricité Mahoraise']);
        UserService::create([
            'user_id' => $provider->id,
            'main_category' => 'Bricolage & Travaux',
            'subcategory' => 'Électricien',
            'is_active' => true,
        ]);

        $response = $this->actingAs($client)->get(route('demand.matching', $this->demand($client)));

        $response->assertOk()
            ->assertSee('Alternatives dans « Bricolage &amp; Travaux »', false)
            ->assertSee('Électricité Mahoraise')
            ->assertDontSee('Aucun prestataire compatible immédiatement');
    }

    private function demand(User $client): Ad
    {
        return Ad::create([
            'title' => 'Réparer une fuite sous mon évier',
            'description' => 'Une fuite légère apparaît sous l’évier lorsque le robinet est ouvert.',
            'main_category' => 'Bricolage & Travaux',
            'category' => 'Plombier',
            'publication_domain' => 'services',
            'ad_details' => [
                'desired_date' => today()->addDays(3)->toDateString(),
                'time_window' => 'morning',
            ],
            'location' => 'Mamoudzou',
            'city' => 'Mamoudzou',
            'country' => 'Mayotte',
            'price_type' => 'negotiable',
            'service_type' => 'demande',
            'status' => 'active',
            'user_id' => $client->id,
        ]);
    }
}
