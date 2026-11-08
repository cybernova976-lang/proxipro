<?php

namespace Tests\Feature\Notifications;

use App\Http\Controllers\AdController;
use App\Models\Ad;
use App\Models\User;
use App\Models\UserService;
use App\Notifications\NewAdMatchingNotification;
use App\Services\GeocodingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Piste B1 : une demande ne prévient que les prestataires de son territoire ;
 * un prestataire au territoire inconnu reste prévenu ; une demande au
 * territoire inconnu garde l'ancien comportement ; le nombre réellement
 * prévenu est enregistré dans ads.notified_providers_count.
 */
class DemandNotificationTerritoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Aucun appel réseau (géocodage, géolocalisation IP) pendant les tests.
        Http::fake();
    }

    public function test_mayotte_demand_skips_paris_provider_keeps_unlocated_one_and_stores_the_exact_count(): void
    {
        Notification::fake();
        $this->mock(GeocodingService::class, fn ($mock) => $mock->shouldReceive('geocode')->once()->andReturn(null));

        $local = $this->provider(['postal_code' => '97600', 'city' => 'Mamoudzou', 'country' => 'Mayotte']);
        $paris = $this->provider(['postal_code' => '75011', 'city' => 'Paris', 'country' => 'France']);
        $unlocated = $this->provider();
        // Prestataire trouvé par ses services actifs (user_services), en métropole.
        $parisByService = User::factory()->create(['postal_code' => '75015', 'city' => 'Paris', 'country' => 'France']);
        UserService::create(['user_id' => $parisByService->id, 'main_category' => 'Bricolage & Travaux',
            'subcategory' => 'Plombier', 'is_active' => true]);
        // Prestataire de Mayotte trouvé par ses services actifs, sans code postal.
        $localByService = User::factory()->create(['city' => 'Koungou', 'country' => 'France']);
        UserService::create(['user_id' => $localByService->id, 'main_category' => 'Bricolage & Travaux',
            'subcategory' => 'Plombier', 'is_active' => true]);

        $client = User::factory()->create(['city' => 'Mamoudzou', 'country' => 'Mayotte']);
        $this->actingAs($client)->post(route('demand.store'), $this->demandPayload())->assertRedirect();

        $demand = $client->ads()->sole();
        Notification::assertSentTo($local, NewAdMatchingNotification::class);
        Notification::assertSentTo($localByService, NewAdMatchingNotification::class);
        Notification::assertSentTo($unlocated, NewAdMatchingNotification::class);
        Notification::assertNotSentTo($paris, NewAdMatchingNotification::class);
        Notification::assertNotSentTo($parisByService, NewAdMatchingNotification::class);
        Notification::assertSentTimes(NewAdMatchingNotification::class, 3);
        $this->assertSame(3, $demand->fresh()->notified_providers_count);
    }

    public function test_demand_published_from_the_generic_form_is_filtered_too(): void
    {
        Notification::fake();

        $local = $this->provider(['postal_code' => '97600', 'city' => 'Mamoudzou', 'country' => 'Mayotte']);
        $paris = $this->provider(['postal_code' => '75011', 'city' => 'Paris', 'country' => 'France']);

        $client = User::factory()->create(['city' => 'Dembeni', 'country' => 'Mayotte']);
        $this->actingAs($client)->post(route('ads.store'), [
            'title' => 'Recherche plombier pour une fuite',
            'description' => 'Fuite sous l’évier de la cuisine, intervention souhaitée cette semaine.',
            'category' => 'Plombier',
            'country' => 'Mayotte',
            'city' => 'Dembeni',
            'location' => 'Dembeni',
            'price_type' => 'negotiable',
            'service_type' => 'demande',
            'visibility' => 'public',
            'reply_restriction' => 'everyone',
            'accept_conditions' => '1',
        ])->assertRedirect();

        $demand = $client->ads()->sole();
        Notification::assertSentTo($local, NewAdMatchingNotification::class);
        Notification::assertNotSentTo($paris, NewAdMatchingNotification::class);
        $this->assertSame(1, $demand->fresh()->notified_providers_count);
    }

    public function test_paris_demand_skips_mayotte_provider_and_default_position_counts_as_unknown(): void
    {
        Notification::fake();

        $mayotte = $this->provider(['postal_code' => '97600', 'city' => 'Mamoudzou', 'country' => 'Mayotte']);
        $paris = $this->provider(['postal_code' => '75011', 'city' => 'Paris', 'country' => 'France']);
        $suburb = $this->provider(['postal_code' => '93200', 'city' => 'Saint-Denis', 'country' => 'France']);
        // Position « par défaut » du middleware (Mamoudzou) : ce n'est pas une
        // mesure, le territoire du prestataire reste inconnu et il est prévenu.
        $defaultPosition = $this->provider(['detected_city' => 'Mamoudzou', 'detected_country' => 'Mayotte',
            'geo_source' => 'default']);

        $demand = $this->demand(['postal_code' => '75011', 'city' => 'Paris', 'location' => 'Paris 11e',
            'country' => 'France']);
        app(AdController::class)->notifyMatchingProfessionals($demand);

        Notification::assertSentTo($paris, NewAdMatchingNotification::class);
        Notification::assertSentTo($defaultPosition, NewAdMatchingNotification::class);
        Notification::assertNotSentTo($mayotte, NewAdMatchingNotification::class);
        Notification::assertNotSentTo($suburb, NewAdMatchingNotification::class);
        $this->assertSame(2, $demand->fresh()->notified_providers_count);
    }

    public function test_provider_with_legacy_default_position_is_still_notified_of_a_paris_demand(): void
    {
        Notification::fake();

        // Position par défaut posée avant le marquage geo_source = default (geo_source NULL).
        $legacy = $this->provider(['latitude' => -12.8275, 'longitude' => 45.1662,
            'detected_city' => 'Mamoudzou', 'detected_country' => 'Mayotte', 'geo_source' => null]);
        $mayotte = $this->provider(['postal_code' => '97600', 'city' => 'Mamoudzou', 'country' => 'Mayotte']);

        $demand = $this->demand(['postal_code' => '75011', 'city' => 'Paris', 'location' => 'Paris 11e',
            'country' => 'France']);
        app(AdController::class)->notifyMatchingProfessionals($demand);

        Notification::assertSentTo($legacy, NewAdMatchingNotification::class);
        Notification::assertNotSentTo($mayotte, NewAdMatchingNotification::class);
        $this->assertSame(1, $demand->fresh()->notified_providers_count);
    }

    public function test_demand_with_unknown_territory_keeps_notifying_every_matching_provider(): void
    {
        Notification::fake();

        $mayotte = $this->provider(['postal_code' => '97600', 'city' => 'Mamoudzou', 'country' => 'Mayotte']);
        $paris = $this->provider(['postal_code' => '75011', 'city' => 'Paris', 'country' => 'France']);
        $demand = $this->demand(['city' => 'Bruxelles', 'location' => 'Bruxelles', 'country' => 'Belgique']);

        app(AdController::class)->notifyMatchingProfessionals($demand);

        Notification::assertSentTo($mayotte, NewAdMatchingNotification::class);
        Notification::assertSentTo($paris, NewAdMatchingNotification::class);
        $this->assertSame(2, $demand->fresh()->notified_providers_count);
    }

    public function test_zero_is_stored_when_no_provider_of_the_territory_matches(): void
    {
        Notification::fake();

        $this->provider(['postal_code' => '75011', 'city' => 'Paris', 'country' => 'France']);
        $demand = $this->demand();
        $this->assertNull($demand->fresh()->notified_providers_count, 'Non mesuré tant que rien n’a été envoyé.');

        app(AdController::class)->notifyMatchingProfessionals($demand);

        Notification::assertNothingSent();
        $this->assertSame(0, $demand->fresh()->notified_providers_count);
    }

    public function test_offer_notifications_are_not_filtered_by_territory(): void
    {
        Notification::fake();

        $paris = $this->provider(['postal_code' => '75011', 'city' => 'Paris', 'country' => 'France']);
        $offer = $this->demand(['service_type' => 'offre', 'title' => 'Plombier disponible à Mamoudzou']);

        app(AdController::class)->notifyMatchingProfessionals($offer);

        Notification::assertSentTo($paris, NewAdMatchingNotification::class);
        $this->assertSame(1, $offer->fresh()->notified_providers_count);
    }

    private function provider(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'user_type' => 'professionnel',
            'is_service_provider' => true,
            'pro_notifications_realtime' => true,
            'pro_service_categories' => ['Plombier'],
        ], $attributes));
    }

    private function demand(array $attributes = []): Ad
    {
        $client = User::factory()->create();

        return Ad::create(array_merge([
            'user_id' => $client->id,
            'title' => 'Réparer une fuite sous mon évier',
            'description' => 'Une fuite légère apparaît sous l’évier lorsque le robinet est ouvert.',
            'category' => 'Plombier',
            'publication_domain' => 'service',
            'location' => 'Mamoudzou',
            'city' => 'Mamoudzou',
            'country' => 'Mayotte',
            'price_type' => 'negotiable',
            'service_type' => 'demande',
            'status' => 'active',
        ], $attributes));
    }

    private function demandPayload(): array
    {
        return [
            'main_category' => 'Bricolage & Travaux',
            'category' => 'Plombier',
            'country' => 'Mayotte',
            'city' => 'Mamoudzou',
            'location' => 'Mamoudzou',
            'desired_date' => today()->addDays(3)->toDateString(),
            'time_window' => 'morning',
            'title' => 'Réparer une fuite sous mon évier',
            'description' => 'Une fuite légère apparaît sous l’évier lorsque le robinet est ouvert.',
            'price_type' => 'negotiable',
            'urgency' => 'normal',
            'service_details' => ['work_scope' => 'repair', 'site_type' => 'house'],
            'publication_confirmed' => '1',
        ];
    }
}
