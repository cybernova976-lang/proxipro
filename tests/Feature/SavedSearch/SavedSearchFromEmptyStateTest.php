<?php

namespace Tests\Feature\SavedSearch;

use App\Models\Ad;
use App\Models\SavedSearch;
use App\Models\User;
use App\Notifications\SavedSearchMatchNotification;
use App\Services\SavedSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use Tests\TestCase;

/**
 * Piste B2 et B3 : « Me prévenir » de l'état vide du flux client crée une
 * alerte qui porte sur tout le territoire, et la publication d'une offre de
 * ce territoire la déclenche (en base, par e-mail si activé, et sur
 * l'appareil quand un abonnement push existe).
 */
class SavedSearchFromEmptyStateTest extends TestCase
{
    use RefreshDatabase;

    private const ENDPOINT = 'https://push.example.test/subscriptions/device-1';

    protected function setUp(): void
    {
        parent::setUp();
        // Aucun appel réseau (géocodage, géolocalisation IP) pendant les tests.
        Http::fake();
    }

    public function test_alert_created_from_the_empty_state_covers_the_whole_territory(): void
    {
        $reader = $this->mayotteReader();

        $html = $this->actingAs($reader)->get(route('feed'))->assertOk()
            ->assertSee('Me prévenir')
            ->getContent();
        [$action, $fields] = $this->emptyStateAlertForm($html);
        $this->assertSame(route('saved-searches.store'), $action);
        $this->assertSame(['type' => 'offres', 'territory' => '976', 'country' => 'Mayotte'],
            array_diff_key($fields, ['_token' => true]));

        // Le message de confirmation s'affiche bien sur le flux.
        $this->from(route('feed'))->followingRedirects()->post($action, $fields)->assertOk()
            ->assertSee('Alerte enregistrée : vous recevrez une notification dès qu’une offre sera publiée à Mayotte.');

        $alert = SavedSearch::where('user_id', $reader->id)->sole();
        $this->assertSame('976', $alert->territory_code);
        $this->assertSame('Offres à Mayotte', $alert->name);
        $this->assertSame('offres', $alert->service_type);
        $this->assertSame('Mayotte', $alert->country);
        // Ni la ville, ni la position, ni le rayon du compte : tout le territoire.
        $this->assertNull($alert->city);
        $this->assertNull($alert->latitude);
        $this->assertNull($alert->longitude);
        $this->assertNull($alert->radius_km);
        $this->assertTrue($alert->is_active);

        // Un second clic ne crée pas de doublon et le dit.
        $this->from(route('feed'))->post($action, $fields)
            ->assertRedirect(route('feed'))
            ->assertSessionHas('info', 'Votre alerte « Offres à Mayotte » est déjà active.');
        $this->assertSame(1, SavedSearch::count());
    }

    public function test_publishing_an_offer_of_the_territory_triggers_the_alert_with_push(): void
    {
        Notification::fake();
        $this->enableWebPush();

        $reader = $this->mayotteReader(['pro_notifications_email' => true]);
        $reader->updatePushSubscription(self::ENDPOINT, 'public-key', 'auth-token', 'aes128gcm');
        $this->actingAs($reader)->from(route('feed'))
            ->post(route('saved-searches.store'), ['type' => 'offres', 'territory' => '976', 'country' => 'Mayotte'])
            ->assertRedirect(route('feed'));
        $alert = SavedSearch::sole();

        // Offre publiée par le vrai formulaire, dans une autre commune que celle
        // du lecteur, avec le pays « France » : l'ancienne règle (pays identique,
        // ville du compte, rayon) ne l'aurait pas retenue.
        $provider = User::factory()->create(['user_type' => 'professionnel', 'city' => 'Dembeni', 'country' => 'France']);
        $this->actingAs($provider)->post(route('ads.store'), [
            'title' => 'Plombier disponible à Dembeni',
            'description' => 'Installation, dépannage et entretien de vos sanitaires, devis gratuit.',
            'category' => 'Plombier',
            'country' => 'France',
            'city' => 'Dembeni',
            'location' => 'Dembeni',
            'price_type' => 'negotiable',
            'service_type' => 'offre',
            'visibility' => 'public',
            'reply_restriction' => 'everyone',
            'accept_conditions' => '1',
        ])->assertRedirect();
        $offer = $provider->ads()->sole();

        Notification::assertSentTo($reader, SavedSearchMatchNotification::class,
            fn ($notification, array $channels) => $channels === ['database', 'mail', WebPushChannel::class]);
        $this->assertDatabaseHas('saved_search_matches', ['saved_search_id' => $alert->id, 'ad_id' => $offer->id]);
        $this->assertNotNull($alert->fresh()->last_matched_at);
    }

    public function test_only_service_offers_of_the_territory_trigger_the_alert(): void
    {
        Notification::fake();

        $reader = $this->mayotteReader();
        $this->actingAs($reader)->from(route('feed'))
            ->post(route('saved-searches.store'), ['type' => 'offres', 'territory' => '976', 'country' => 'Mayotte']);
        $service = app(SavedSearchService::class);
        $author = User::factory()->create();

        // Ne déclenchent pas l'alerte :
        $this->assertSame(0, $service->processNewAd($this->offer($author, ['postal_code' => '75011', 'city' => 'Paris',
            'country' => 'France', 'location' => 'Paris'])), 'offre de Paris');
        $this->assertSame(0, $service->processNewAd($this->offer($author, ['postal_code' => '75011', 'city' => 'Paris',
            'country' => 'France', 'location' => 'Paris', 'ad_details' => ['intervention_mode' => 'remote']])), 'offre à distance publiée ailleurs');
        $this->assertSame(0, $service->processNewAd($this->offer($author, ['service_type' => 'demande'])), 'demande');
        $this->assertSame(0, $service->processNewAd($this->offer($author, ['publication_domain' => 'employment'])), 'offre d’emploi');
        $this->assertSame(0, $service->processNewAd($this->offer($reader)), 'offre du lecteur lui-même');
        $internal = User::factory()->create();
        config(['feed.hidden_author_ids' => [$internal->id]]);
        $this->assertSame(0, $service->processNewAd($this->offer($internal)), 'compte interne masqué');

        // Déclenchent l'alerte : code postal 976, pays « Mayotte », commune connue.
        $this->assertSame(1, $service->processNewAd($this->offer($author, ['postal_code' => '97660', 'city' => 'Bandrélé',
            'country' => 'France', 'location' => 'Bandrélé'])));
        $this->assertSame(1, $service->processNewAd($this->offer($author, ['city' => 'Sada', 'location' => 'Sada'])));
        $this->assertSame(1, $service->processNewAd($this->offer($author, ['city' => 'Koungou', 'country' => 'France',
            'location' => 'Koungou'])));
        Notification::assertSentTimes(SavedSearchMatchNotification::class, 3);
    }

    public function test_department_alert_follows_the_department(): void
    {
        Notification::fake();

        $reader = User::factory()->create(['postal_code' => '75011', 'city' => 'Paris', 'country' => 'France']);
        $this->actingAs($reader)->from(route('feed'))
            ->post(route('saved-searches.store'), ['type' => 'offres', 'territory' => '75'])
            ->assertSessionHas('info', 'Alerte enregistrée : vous recevrez une notification dès qu’une offre sera publiée dans le département 75.');
        $alert = SavedSearch::sole();
        $this->assertSame('75', $alert->territory_code);
        $this->assertSame('Offres dans le département 75', $alert->name);
        $this->assertSame('France', $alert->country);

        $service = app(SavedSearchService::class);
        $author = User::factory()->create();
        $this->assertSame(1, $service->processNewAd($this->offer($author, ['postal_code' => '75015', 'city' => 'Paris',
            'country' => 'France', 'location' => 'Paris'])));
        $this->assertSame(0, $service->processNewAd($this->offer($author, ['postal_code' => '13001', 'city' => 'Marseille',
            'country' => 'France', 'location' => 'Marseille'])));
        $this->assertSame(0, $service->processNewAd($this->offer($author)));
    }

    public function test_unknown_territory_is_refused_without_creating_a_broad_alert(): void
    {
        $this->actingAs($this->mayotteReader())->from(route('feed'))
            ->post(route('saved-searches.store'), ['type' => 'offres', 'territory' => '999'])
            ->assertRedirect(route('feed'))
            ->assertSessionHasErrors('territory')
            ->assertSessionHas('info');
        $this->assertSame(0, SavedSearch::count());
    }

    public function test_alert_notification_channels_payload_and_colour(): void
    {
        $this->enableWebPush();
        $reader = User::factory()->create(['pro_notifications_email' => false]);
        $alert = SavedSearch::create(['user_id' => $reader->id, 'name' => 'Offres à Mayotte', 'service_type' => 'offres',
            'country' => 'Mayotte', 'territory_code' => '976', 'is_active' => true]);
        $ad = $this->offer(User::factory()->create(), ['title' => 'Plombier disponible à Sada']);
        $notification = new SavedSearchMatchNotification($alert, $ad);

        // Sans appareil enregistré : aucun canal push.
        $this->assertSame(['database'], $notification->via($reader));

        $reader->updatePushSubscription(self::ENDPOINT, 'public-key', 'auth-token', 'aes128gcm');
        $this->assertSame(['database', WebPushChannel::class], $notification->via($reader));

        $payload = $notification->toWebPush($reader, $notification)->toArray();
        $this->assertSame('Nouvelle annonce pour votre alerte', $payload['title']);
        $this->assertStringContainsString('Offres à Mayotte', $payload['body']);
        $this->assertStringContainsString('Plombier disponible à Sada', $payload['body']);
        $this->assertSame(route('ads.show', $ad->id), $payload['data']['url']);
        $this->assertSame('#4f46e5', $notification->toArray($reader)['color']);

        // Sans clés serveur : pas de push, les autres canaux restent.
        config()->set('webpush.vapid.private_key', null);
        $this->assertSame(['database'], $notification->via($reader));
    }

    private function enableWebPush(): void
    {
        config()->set('webpush.vapid.subject', 'https://www.prokejem.fr');
        config()->set('webpush.vapid.public_key', 'test-public-key');
        config()->set('webpush.vapid.private_key', 'test-private-key');
    }

    /** @return array{0: string, 1: array<string, string>} adresse et champs du formulaire « Me prévenir » */
    private function emptyStateAlertForm(string $html): array
    {
        $this->assertSame(1, preg_match('/<form method="POST" action="([^"]+)" class="pk-empty-local__alert">(.*?)<\/form>/s', $html, $form));
        preg_match_all('/<input type="hidden" name="([^"]+)" value="([^"]*)"/', $form[2], $inputs, PREG_SET_ORDER);

        $fields = [];
        foreach ($inputs as [, $name, $value]) {
            $fields[$name] = html_entity_decode($value, ENT_QUOTES);
        }

        return [html_entity_decode($form[1], ENT_QUOTES), $fields];
    }

    private function mayotteReader(array $attributes = []): User
    {
        return User::factory()->create(array_merge(['city' => 'Mamoudzou', 'country' => 'Mayotte', 'postal_code' => '97600',
            'latitude' => -12.7806, 'longitude' => 45.2279, 'geo_radius' => 5], $attributes));
    }

    private function offer(User $author, array $attributes = []): Ad
    {
        return Ad::create(array_merge(['user_id' => $author->id, 'title' => 'Service de plomberie',
            'description' => 'Installation et dépannage de sanitaires.', 'category' => 'Plombier',
            'city' => 'Mamoudzou', 'country' => 'Mayotte', 'location' => 'Mamoudzou',
            'service_type' => 'offre', 'status' => 'active', 'visibility' => 'public'], $attributes));
    }
}
