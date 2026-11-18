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
use Tests\TestCase;

/**
 * Demandes du fondateur du 18/11/2026 :
 * 1. un territoire sans offre montre quand même les offres publiées ailleurs ;
 * 2. le lecteur peut créer une alerte sur chaque prestataire de son choix ;
 * 3. le champ de lieu s'appelle « Pays / Département » ;
 * 4. le « Secteur d'activité » de l'inscription, jamais utilisé, est retiré.
 */
class ProviderAlertAndElsewhereOffersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
    }

    // ----- 1. Offres d'ailleurs ------------------------------------------------

    public function test_an_empty_territory_still_shows_offers_published_elsewhere(): void
    {
        $provider = $this->mayotteProvider();
        $this->offer($provider, ['title' => 'Dépannage plomberie à Mamoudzou']);

        $this->actingAs($this->reunionReader())->get(route('feed'))->assertOk()
            // L'état vide reste dit en clair…
            ->assertSee('Aucun prestataire n\'a encore publié d\'offre à La Réunion.', false)
            ->assertSee('Me prévenir')
            // …et les offres d'ailleurs sont montrées, annoncées comme telles.
            ->assertSee('Offres publiées dans d’autres territoires')
            ->assertSee('Dépannage plomberie à Mamoudzou')
            ->assertSee('offres de toutes les zones');
    }

    public function test_no_elsewhere_block_when_no_offer_exists_anywhere(): void
    {
        $this->actingAs($this->reunionReader())->get(route('feed'))->assertOk()
            ->assertSee('Aucun prestataire n\'a encore publié d\'offre à La Réunion.', false)
            ->assertDontSee('Offres publiées dans d’autres territoires')
            ->assertDontSee('pkElsewhere', false);
    }

    public function test_a_territory_with_offers_does_not_mix_in_offers_from_elsewhere(): void
    {
        $reader = $this->mayotteProvider(['name' => 'Lectrice de Mayotte']);
        $this->offer($this->mayotteProvider(), ['title' => 'Offre locale de Mayotte']);
        $this->offer($this->reunionReader(['name' => 'Artisan réunionnais']), ['title' => 'Offre de La Réunion',
            'city' => 'Le Tampon', 'country' => 'La Réunion', 'location' => 'Le Tampon']);

        $this->actingAs($reader)->get(route('feed'))->assertOk()
            ->assertSee('Offre locale de Mayotte')
            ->assertDontSee('Offre de La Réunion')
            ->assertDontSee('pkElsewhere', false);
    }

    // ----- 2. Alerte sur un prestataire ---------------------------------------

    public function test_each_offer_card_lets_the_reader_follow_its_provider(): void
    {
        $reader = $this->reunionReader();
        $provider = $this->mayotteProvider(['name' => 'Atelier Baobab']);
        $this->offer($provider);

        $html = $this->actingAs($reader)->get(route('feed'))->assertOk()
            ->assertSee('Me prévenir des nouvelles offres de Atelier Baobab')
            ->getContent();
        $this->assertMatchesRegularExpression('/class="pk-follow">.*?name="provider" value="'.$provider->id.'"/s', $html);

        $this->from(route('feed'))->followingRedirects()->post(route('saved-searches.store'), ['provider' => $provider->id])
            ->assertOk()
            ->assertSee('Alerte enregistrée : vous recevrez une notification dès que Atelier Baobab publiera une nouvelle offre.')
            // Le bouton change d'état : il sert maintenant à arrêter l'alerte.
            ->assertSee('Ne plus être prévenu des nouvelles offres de Atelier Baobab');

        $alert = SavedSearch::where('user_id', $reader->id)->sole();
        $this->assertSame($provider->id, (int) $alert->provider_id);
        $this->assertSame('Offres de Atelier Baobab', $alert->name);
        $this->assertNull($alert->territory_code);

        // Un second clic ne crée pas de doublon et le dit.
        $this->from(route('feed'))->post(route('saved-searches.store'), ['provider' => $provider->id])
            ->assertSessionHas('info', 'Vous êtes déjà prévenu des nouvelles offres de Atelier Baobab.');
        $this->assertSame(1, SavedSearch::count());
    }

    public function test_the_reader_can_follow_several_providers_and_is_notified_only_for_them(): void
    {
        Notification::fake();
        $reader = $this->reunionReader();
        $first = $this->mayotteProvider(['name' => 'Premier prestataire']);
        $second = $this->mayotteProvider(['name' => 'Second prestataire']);
        $other = $this->mayotteProvider(['name' => 'Prestataire non suivi']);
        $this->offer($first);
        $this->offer($second);

        $this->actingAs($reader)->post(route('saved-searches.store'), ['provider' => $first->id]);
        $this->actingAs($reader)->post(route('saved-searches.store'), ['provider' => $second->id]);
        $this->assertSame(2, SavedSearch::where('user_id', $reader->id)->whereNotNull('provider_id')->count());

        $service = app(SavedSearchService::class);
        // Une offre d'un prestataire suivi, où qu'elle soit publiée : une notification.
        $this->assertSame(1, $service->processNewAd($this->offer($first, ['title' => 'Nouvelle offre du premier'])));
        // Un prestataire non suivi, ou une demande du prestataire suivi : rien.
        $this->assertSame(0, $service->processNewAd($this->offer($other)));
        $this->assertSame(0, $service->processNewAd($this->offer($second, ['service_type' => 'demande'])));

        Notification::assertSentToTimes($reader, SavedSearchMatchNotification::class, 1);
    }

    public function test_an_alert_cannot_target_oneself_nor_a_member_who_offers_nothing(): void
    {
        $reader = $this->reunionReader();
        $plainMember = User::factory()->create(['name' => 'Simple particulier']);

        foreach ([$reader->id, $plainMember->id, 999999, 'abc'] as $target) {
            $this->actingAs($reader)->from(route('feed'))->post(route('saved-searches.store'), ['provider' => $target])
                ->assertRedirect(route('feed'))
                ->assertSessionHas('error', 'Cette alerte ne peut pas être créée pour ce compte.');
        }

        $this->assertSame(0, SavedSearch::count());
    }

    public function test_my_alerts_lists_the_provider_alert_and_lets_the_reader_stop_it(): void
    {
        $reader = $this->reunionReader();
        $provider = $this->mayotteProvider(['name' => 'Atelier Baobab']);
        $this->offer($provider);
        $this->actingAs($reader)->post(route('saved-searches.store'), ['provider' => $provider->id]);
        $alert = SavedSearch::sole();

        $this->get(route('saved-searches.index'))->assertOk()
            ->assertSee('Offres de Atelier Baobab')
            ->assertSee('Voir le profil')
            ->assertSee('href="'.route('profile.public', $provider->id).'"', false);

        $this->from(route('feed'))->followingRedirects()->delete(route('saved-searches.destroy', $alert))->assertOk()
            ->assertSee('Alerte supprimée.')
            ->assertSee('Me prévenir des nouvelles offres de Atelier Baobab');
        $this->assertSame(0, SavedSearch::count());
    }

    public function test_the_offer_page_offers_the_same_alert(): void
    {
        $provider = $this->mayotteProvider(['name' => 'Atelier Baobab']);
        $offer = $this->offer($provider);

        $this->actingAs($this->reunionReader())->get(route('ads.show', $offer))->assertOk()
            ->assertSee('Me prévenir de ses nouvelles offres')
            ->assertSee('name="provider" value="'.$provider->id.'"', false);

        // L'auteur ne se voit pas proposer une alerte sur lui-même.
        $this->actingAs($provider)->get(route('ads.show', $offer))->assertOk()
            ->assertDontSee('Me prévenir de ses nouvelles offres');
    }

    public function test_a_provider_alert_does_not_replace_the_territory_alert(): void
    {
        $reader = $this->mayotteProvider(['name' => 'Lectrice de Mayotte']);
        $provider = $this->mayotteProvider(['name' => 'Atelier Baobab']);
        $this->offer($provider);
        $this->actingAs($reader)->post(route('saved-searches.store'), ['provider' => $provider->id]);

        // La ligne « À faire » du territoire reste proposée.
        $this->get(route('feed'))->assertOk()->assertSee('Me prévenir des nouvelles offres à Mayotte');
    }

    public function test_the_empty_state_says_when_the_territory_alert_is_already_active(): void
    {
        $reader = $this->reunionReader();
        $this->actingAs($reader)->post(route('saved-searches.store'), ['type' => 'offres', 'territory' => '974', 'country' => 'La Réunion']);

        $this->get(route('feed'))->assertOk()
            ->assertSee('Alerte active : vous serez prévenu de la première offre')
            ->assertDontSee('pk-empty-local__alert', false);
    }

    // ----- 3 et 4. Formulaires --------------------------------------------------

    public function test_location_fields_are_labelled_country_or_department(): void
    {
        $this->get(route('register'))->assertOk()->assertSee('Pays / Département *');
        $this->actingAs($this->reunionReader())->get(route('profile.edit'))->assertOk()->assertSee('Pays / Département');
    }

    public function test_professional_registration_no_longer_asks_for_an_unused_sector(): void
    {
        $this->get(route('register', ['account_type' => 'professionnel']))->assertOk()
            ->assertDontSee('Secteur d\'activité', false)
            ->assertDontSee('name="sector"', false);
    }

    private function reunionReader(array $attributes = []): User
    {
        return User::factory()->create(array_merge(['city' => 'Le Tampon', 'country' => 'La Réunion', 'postal_code' => '97430'], $attributes));
    }

    private function mayotteProvider(array $attributes = []): User
    {
        return User::factory()->create(array_merge(['city' => 'Mamoudzou', 'country' => 'Mayotte', 'postal_code' => '97600'], $attributes));
    }

    private function offer(User $author, array $attributes = []): Ad
    {
        return Ad::create(array_merge(['user_id' => $author->id, 'title' => 'Service de plomberie',
            'description' => 'Installation et dépannage de sanitaires.', 'category' => 'Plombier',
            'city' => 'Mamoudzou', 'country' => 'Mayotte', 'location' => 'Mamoudzou',
            'service_type' => 'offre', 'status' => 'active', 'visibility' => 'public'], $attributes));
    }
}
