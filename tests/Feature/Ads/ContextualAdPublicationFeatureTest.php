<?php

namespace Tests\Feature\Ads;

use App\Models\User;
use App\Support\MarketplaceCategoryRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ContextualAdPublicationFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake(['nominatim.openstreetmap.org/*' => Http::response([], 200)]);
        MarketplaceCategoryRegistry::storeEnabledIds(array_keys(MarketplaceCategoryRegistry::definitions()));
    }

    public function test_create_form_contains_contextual_schemas_and_accessible_mobile_fields(): void
    {
        $response = $this->actingAs(User::factory()->create())->get(route('ads.create'));

        $response->assertOk()
            ->assertSee('Informations —', false)
            ->assertSee('Lieu de départ')
            ->assertSee('Mode de travail')
            ->assertSee('État du bien')
            ->assertSee('Unité de location')
            ->assertSee('grid-template-columns: minmax(0, 1fr);', false)
            ->assertSee('applyPublicationDomain(mainCat);', false);
    }

    public function test_employment_requires_essential_job_information_and_stores_it(): void
    {
        $user = User::factory()->create();
        $payload = $this->payload([
            'main_category' => 'Emploi',
            'category' => 'CDI',
            'title' => 'Recherche poste développeur en CDI',
            'price_type' => 'fixed',
        ]);

        $this->actingAs($user)
            ->from(route('ads.create'))
            ->post(route('ads.store'), $payload)
            ->assertSessionHasErrors([
                'ad_details.work_mode',
                'ad_details.work_time',
                'ad_details.experience_level',
                'ad_details.compensation_period',
            ]);

        $payload['ad_details'] = [
            'work_mode' => 'hybrid',
            'work_time' => 'full_time',
            'experience_level' => 'confirmed',
            'compensation_period' => 'month',
            'start_date' => now()->addWeek()->toDateString(),
        ];

        $this->actingAs($user)->post(route('ads.store'), $payload)->assertRedirect();

        $ad = $user->ads()->sole();
        $this->assertSame('Emploi', $ad->main_category);
        $this->assertSame('employment', $ad->publication_domain);
        $this->assertSame('hybrid', $ad->ad_details['work_mode']);
        $this->assertSame('3 200 €/mois', $ad->formatted_price);
    }

    public function test_ridesharing_validates_the_route_and_displays_practical_details(): void
    {
        $user = User::factory()->create();
        $payload = $this->payload([
            'main_category' => 'Covoiturage',
            'category' => 'Longue distance',
            'title' => 'Trajet Paris vers Lille vendredi soir',
            'price' => 28,
            'price_type' => 'fixed',
            'ad_details' => [
                'departure' => 'Paris Gare du Nord',
                'destination' => 'Lille Europe',
                'departure_at' => now()->addDays(2)->format('Y-m-d\TH:i'),
                'available_seats' => 3,
                'trip_type' => 'one_way',
                'luggage' => 'small',
            ],
        ]);

        $this->actingAs($user)->post(route('ads.store'), $payload)->assertRedirect();

        $ad = $user->ads()->sole();
        $this->assertSame('ridesharing', $ad->publication_domain);
        $this->assertSame('Paris Gare du Nord', $ad->ad_details['departure']);
        $this->assertSame('28 €/place', $ad->formatted_price);

        $this->get(route('ads.show', $ad))
            ->assertOk()
            ->assertSee('Covoiturage')
            ->assertSee('Paris Gare du Nord')
            ->assertSee('Lille Europe')
            ->assertSee('Petit bagage');
    }

    public function test_round_trip_requires_a_return_after_departure(): void
    {
        $user = User::factory()->create();
        $departure = now()->addDays(3);

        $response = $this->actingAs($user)
            ->from(route('ads.create'))
            ->post(route('ads.store'), $this->payload([
                'main_category' => 'Covoiturage',
                'category' => 'Aéroport',
                'title' => 'Aller retour vers aéroport ce week-end',
                'price_type' => 'fixed',
                'ad_details' => [
                    'departure' => 'Lyon',
                    'destination' => 'Aéroport Lyon Saint-Exupéry',
                    'departure_at' => $departure->format('Y-m-d\TH:i'),
                    'available_seats' => 2,
                    'trip_type' => 'round_trip',
                    'return_at' => $departure->copy()->subHour()->format('Y-m-d\TH:i'),
                ],
            ]));

        $response->assertSessionHasErrors('ad_details.return_at');
        $this->assertDatabaseCount('ads', 0);
    }

    public function test_sale_rejects_hourly_pricing_and_stores_condition_and_delivery(): void
    {
        $user = User::factory()->create();
        $payload = $this->payload([
            'main_category' => 'Vente',
            'category' => 'High-tech',
            'title' => 'Ordinateur portable récent à vendre',
            'price_type' => 'hourly',
            'ad_details' => [
                'condition' => 'like_new',
                'delivery_method' => 'both',
                'brand' => 'Lenovo',
                'model' => 'ThinkPad T14',
                'quantity' => 1,
            ],
        ]);

        $this->actingAs($user)
            ->from(route('ads.create'))
            ->post(route('ads.store'), $payload)
            ->assertSessionHasErrors('price_type');

        unset($payload['price_type']);
        $this->actingAs($user)->post(route('ads.store'), $payload)->assertRedirect();

        $ad = $user->ads()->sole();
        $this->assertSame('sale', $ad->publication_domain);
        $this->assertSame('fixed', $ad->price_type);
        $this->assertSame('like_new', $ad->ad_details['condition']);
        $this->assertSame('both', $ad->ad_details['delivery_method']);
    }

    public function test_rental_requires_availability_and_keeps_structured_values_when_edited(): void
    {
        $user = User::factory()->create();
        $payload = $this->payload([
            'main_category' => 'Location',
            'category' => 'Utilitaires',
            'title' => 'Utilitaire douze mètres cubes à louer',
            'price' => 75,
            'price_type' => 'fixed',
            'ad_details' => [
                'rental_period' => 'day',
                'availability_from' => now()->addDay()->toDateString(),
                'availability_to' => now()->addMonth()->toDateString(),
                'minimum_duration' => 2,
                'deposit' => 600,
            ],
        ]);

        $this->actingAs($user)->post(route('ads.store'), $payload)->assertRedirect();
        $ad = $user->ads()->sole();

        $this->assertSame('75 €/jour', $ad->formatted_price);
        $this->actingAs($user)->get(route('ads.edit', $ad))
            ->assertOk()
            ->assertSee('value="600"', false)
            ->assertSee('Utilitaire douze mètres cubes à louer');

        $payload['title'] = 'Utilitaire douze mètres cubes disponible';
        $payload['ad_details']['deposit'] = 500;
        $this->actingAs($user)->put(route('ads.update', $ad), $payload)->assertRedirect(route('ads.show', $ad));

        $this->assertSame('500', (string) $ad->fresh()->ad_details['deposit']);
    }

    public function test_lost_item_report_rejects_a_future_incident_date(): void
    {
        $user = User::factory()->create();
        $payload = $this->payload([
            'main_category' => 'Perdu/disparu',
            'category' => 'Clés',
            'title' => 'Trousseau de clés perdu près de la gare',
            'price_type' => 'negotiable',
            'price' => null,
            'ad_details' => [
                'incident_type' => 'lost',
                'incident_date' => now()->addDay()->toDateString(),
                'distinctive_signs' => 'Porte-clés rond de couleur bleue',
            ],
        ]);

        $this->actingAs($user)
            ->from(route('ads.create'))
            ->post(route('ads.store'), $payload)
            ->assertSessionHasErrors('ad_details.incident_date');

        $payload['ad_details']['incident_date'] = now()->toDateString();
        $this->actingAs($user)->post(route('ads.store'), $payload)->assertRedirect();

        $ad = $user->ads()->sole();
        $this->assertSame('lost_found', $ad->publication_domain);
        $this->assertSame('lost', $ad->ad_details['incident_type']);
    }

    public function test_server_derives_domain_from_the_real_category_instead_of_hidden_input(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('ads.store'), $this->payload([
            'main_category' => 'Emploi',
            'category' => 'Meubles',
            'publication_domain' => 'employment',
            'title' => 'Table en bois massif très bon état',
            'price_type' => 'fixed',
            'ad_details' => [
                'condition' => 'good',
                'delivery_method' => 'pickup',
            ],
        ]))->assertRedirect();

        $ad = $user->ads()->sole();
        $this->assertSame('Vente', $ad->main_category);
        $this->assertSame('sale', $ad->publication_domain);
        $this->assertArrayNotHasKey('work_mode', $ad->ad_details);
    }

    private function payload(array $overrides = []): array
    {
        return array_replace_recursive([
            'title' => 'Annonce contextuelle complète',
            'description' => 'Description suffisamment détaillée pour valider la publication de cette annonce contextuelle.',
            'main_category' => 'Maison & entretien',
            'category' => 'Plombier',
            'country' => 'France',
            'city' => 'Paris',
            'location' => 'Paris',
            'price' => 3200,
            'price_type' => 'fixed',
            'service_type' => 'demande',
            'visibility' => 'public',
            'reply_restriction' => 'everyone',
            'accept_conditions' => '1',
        ], $overrides);
    }
}
