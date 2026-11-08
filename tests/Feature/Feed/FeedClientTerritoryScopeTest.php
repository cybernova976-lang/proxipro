<?php

namespace Tests\Feature\Feed;

use App\Models\Ad;
use App\Models\SavedSearch;
use App\Models\User;
use App\Services\HomeProviderDiscoveryService;
use App\Support\PublicHomepageSelection;
use App\Support\TerritoryResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FeedClientTerritoryScopeTest extends TestCase
{
    use RefreshDatabase;

    private const RANKING_LINE = "Classement : offres de Mayotte. Les offres sponsorisées (payantes) passent en premier, puis les plus récentes.";

    protected function setUp(): void
    {
        parent::setUp();
        // Aucun appel réseau (géocodage, géolocalisation IP) pendant les tests.
        Http::fake();
    }

    public function test_offer_from_another_territory_is_hidden_for_a_mayotte_reader(): void
    {
        $viewer = $this->mayotteReader();
        $author = User::factory()->create();
        $local = $this->offer($author, ['title' => 'Plomberie à Mamoudzou']);
        $this->offer($author, ['title' => 'Plomberie à Paris', 'postal_code' => '75011', 'city' => 'Paris',
            'country' => 'France', 'location' => 'Paris']);

        $response = $this->actingAs($viewer)->get(route('feed'))->assertOk()
            ->assertViewHas('pkTerritoryScoped', true)
            ->assertViewHas('pkFeedAds', fn ($ads) => $ads->pluck('id')->all() === [$local->id])
            ->assertSee('Services à Mayotte')
            ->assertSee('Offres de Mayotte')
            ->assertSee(e(route('feed', ['scope' => 'all'])), false)
            ->assertSee('Voir les autres territoires')
            ->assertDontSee('Plomberie à Paris')
            ->assertSee(self::RANKING_LINE)
            ->assertSee(route('legal.platform-rules').'#classement', false)
            ->assertSee('En savoir plus');
        $this->assertSame('976', $response->viewData('pkTerritory')->code);
    }

    public function test_remote_offer_from_another_territory_is_included_and_labelled(): void
    {
        $viewer = $this->mayotteReader();
        $author = User::factory()->create();
        $remote = $this->offer($author, ['title' => 'Comptabilité en visioconférence', 'postal_code' => '75011',
            'city' => 'Paris', 'country' => 'France', 'location' => 'Paris',
            'ad_details' => ['intervention_mode' => 'remote']]);
        $this->offer($author, ['title' => 'Dépannage sur place à Paris', 'postal_code' => '75011',
            'city' => 'Paris', 'country' => 'France', 'location' => 'Paris',
            'ad_details' => ['intervention_mode' => 'customer_location']]);

        $this->actingAs($viewer)->get(route('feed'))->assertOk()
            ->assertViewHas('pkFeedAds', fn ($ads) => $ads->pluck('id')->all() === [$remote->id])
            ->assertSee('Comptabilité en visioconférence')
            ->assertSee('pk-tag--remote', false)
            ->assertDontSee('Dépannage sur place à Paris');
    }

    public function test_scope_all_keeps_every_zone_and_offers_a_way_back(): void
    {
        $viewer = $this->mayotteReader();
        $author = User::factory()->create();
        $this->offer($author, ['title' => 'Plomberie à Mamoudzou']);
        $this->offer($author, ['title' => 'Plomberie à Paris', 'postal_code' => '75011', 'city' => 'Paris',
            'country' => 'France', 'location' => 'Paris']);

        $this->actingAs($viewer)->get(route('feed', ['scope' => 'all']))->assertOk()
            ->assertViewHas('pkTerritoryScoped', false)
            ->assertViewHas('pkFeedAds', fn ($ads) => $ads->count() === 2)
            ->assertSee('Plomberie à Paris')
            ->assertSee('Toutes les zones')
            ->assertSee('Revenir aux offres de Mayotte')
            ->assertSee("Classement : offres de toutes les zones. Les offres sponsorisées (payantes) passent en premier");
    }

    public function test_unknown_territory_shows_every_zone_and_says_so(): void
    {
        $author = User::factory()->create();
        $this->offer($author, ['title' => 'Plomberie à Mamoudzou']);
        $this->offer($author, ['title' => 'Plomberie à Paris', 'postal_code' => '75011', 'city' => 'Paris',
            'country' => 'France', 'location' => 'Paris']);

        $foreign = User::factory()->create(['city' => 'Bruxelles', 'country' => 'Belgique']);
        $this->actingAs($foreign)->get(route('feed'))->assertOk()
            ->assertViewHas('pkTerritory', fn ($v) => $v === null)
            ->assertViewHas('pkFeedAds', fn ($ads) => $ads->count() === 2)
            ->assertSee('Vous voyez toutes les zones.')
            ->assertSee('Indiquez votre commune')
            ->assertSee(route('profile.edit').'#city', false);
    }

    public function test_default_middleware_position_is_not_taken_for_the_reader_territory(): void
    {
        $this->offer(User::factory()->create(), ['title' => 'Plomberie à Mamoudzou']);
        // Sans aucun lieu, le middleware « geo » retombe sur Mamoudzou par défaut :
        // ce n'est pas une mesure, le territoire reste inconnu et c'est annoncé.
        $unlocated = User::factory()->create();
        $this->actingAs($unlocated)->get(route('feed'))->assertOk()
            ->assertViewHas('pkTerritory', fn ($v) => $v === null)
            ->assertSee('Vous voyez toutes les zones.');
        $this->assertSame('default', $unlocated->fresh()->geo_source);
        $this->get(route('feed'))->assertOk()->assertViewHas('pkTerritory', fn ($v) => $v === null);
    }

    public function test_legacy_default_position_reader_sees_every_zone(): void
    {
        $this->offer(User::factory()->create(), ['title' => 'Plomberie à Mamoudzou']);
        $this->offer(User::factory()->create(), ['title' => 'Plomberie à Paris', 'postal_code' => '75011',
            'city' => 'Paris', 'country' => 'France', 'location' => 'Paris']);
        // Position par défaut posée avant le marquage geo_source = default.
        $legacy = User::factory()->create(['latitude' => -12.8275, 'longitude' => 45.1662,
            'detected_city' => 'Mamoudzou', 'detected_country' => 'Mayotte', 'geo_source' => null]);

        $this->actingAs($legacy)->get(route('feed'))->assertOk()
            ->assertViewHas('pkTerritory', fn ($v) => $v === null)
            ->assertViewHas('pkFeedAds', fn ($ads) => $ads->count() === 2)
            ->assertSee('Vous voyez toutes les zones.')
            ->assertDontSee('Services à Mayotte');

        // Une session explicitement « default » ne rattache pas davantage à Mayotte.
        $this->withSession(['user_geolocation' => ['latitude' => -12.8275, 'longitude' => 45.1662,
            'city' => 'Mamoudzou', 'country' => 'Mayotte', 'source' => 'default', 'detected_at' => now()->toISOString()]])
            ->get(route('feed'))->assertOk()
            ->assertViewHas('pkTerritory', fn ($v) => $v === null)
            ->assertSee('Vous voyez toutes les zones.');
    }

    public function test_default_position_is_not_presented_as_the_reader_place_in_the_provider_rail(): void
    {
        User::factory()->create(['name' => 'Prestataire de Mamoudzou', 'is_active' => true, 'profile_public' => true,
            'is_service_provider' => true, 'user_type' => 'professionnel', 'profession' => 'Plombier',
            'service_category' => 'Plombier', 'city' => 'Mamoudzou', 'country' => 'Mayotte',
            'avatar' => 'avatars/plombier.jpg', 'bio' => str_repeat('Présentation du prestataire. ', 3)]);
        $unlocated = User::factory()->create();

        $response = $this->actingAs($unlocated)->get(route('feed'))->assertOk()
            ->assertViewHas('pkTerritory', fn ($v) => $v === null)
            ->assertSee('Vous voyez toutes les zones.')
            ->assertDontSee('Prestataires à Mamoudzou');
        $this->assertSame('default', $unlocated->fresh()->geo_source);
        $this->assertNull($response->viewData('geoCity'));
        $this->assertNull($response->viewData('geoCountry'));
    }

    public function test_page_beyond_the_last_redirects_to_the_last_real_page_without_false_empty_state(): void
    {
        $viewer = $this->mayotteReader();
        $author = User::factory()->create();
        $this->offer($author, ['title' => 'Première offre locale']);
        $this->offer($author, ['title' => 'Deuxième offre locale']);

        $response = $this->actingAs($viewer)->get(route('feed', ['offers_page' => 2]));
        $response->assertRedirect();
        $location = (string) $response->headers->get('Location');
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);
        $this->assertSame('1', $query['offers_page'] ?? null);

        $this->get($location)->assertOk()
            ->assertViewHas('pkFeedAds', fn ($ads) => $ads->count() === 2)
            ->assertViewHas('pkEmptyLocal', fn ($v) => $v === null)
            ->assertDontSee("Aucun prestataire n'a encore publié d'offre", false);
    }

    public function test_reader_who_is_the_only_local_author_is_not_told_that_nobody_published(): void
    {
        $viewer = $this->mayotteReader();
        $this->offer($viewer, ['title' => 'Ma propre offre locale']);

        $this->actingAs($viewer)->get(route('feed', ['mode' => 'client']))->assertOk()
            ->assertViewHas('pkFeedAds', fn ($ads) => $ads->isEmpty())
            ->assertViewHas('pkEmptyLocal', fn ($v) => $v === null)
            ->assertDontSee("Aucun prestataire n'a encore publié d'offre", false);
    }

    public function test_nearby_scope_is_described_by_the_ranking_line(): void
    {
        $viewer = User::factory()->create(['city' => 'Mamoudzou', 'country' => 'Mayotte', 'postal_code' => '97600',
            'latitude' => -12.7806, 'longitude' => 45.2278, 'geo_source' => 'browser']);
        $author = User::factory()->create();
        $this->offer($author, ['title' => 'Plomberie proche', 'latitude' => -12.7810, 'longitude' => 45.2280]);
        $this->offer($author, ['title' => 'Plomberie à Paris', 'postal_code' => '75011', 'city' => 'Paris',
            'country' => 'France', 'location' => 'Paris', 'latitude' => 48.8590, 'longitude' => 2.3800]);

        $this->actingAs($viewer)->get(route('feed', ['scope' => 'nearby']))->assertOk()
            ->assertViewHas('pkTerritoryScoped', false)
            ->assertSee('Mamoudzou et alentours')
            ->assertSee("Classement : offres de Mamoudzou et alentours. Les offres sponsorisées (payantes) passent en premier")
            ->assertDontSee('Classement : offres de toutes les zones');
    }

    public function test_department_reader_sees_offers_of_the_department(): void
    {
        $viewer = User::factory()->create(['postal_code' => '75011', 'city' => 'Paris', 'country' => 'France']);
        $author = User::factory()->create();
        $paris = $this->offer($author, ['title' => 'Plomberie à Paris', 'postal_code' => '75015', 'city' => 'Paris',
            'country' => 'France', 'location' => 'Paris']);
        $this->offer($author, ['title' => 'Plomberie à Mamoudzou']);

        $this->actingAs($viewer)->get(route('feed'))->assertOk()
            ->assertViewHas('pkFeedAds', fn ($ads) => $ads->pluck('id')->all() === [$paris->id])
            ->assertSee('Services dans le département 75')
            ->assertSee("Classement : offres du département 75. Les offres sponsorisées (payantes) passent en premier");
    }

    public function test_empty_territory_offers_alert_invitation_and_other_territories_without_false_promise(): void
    {
        $viewer = $this->mayotteReader();
        $this->offer(User::factory()->create(), ['title' => 'Plomberie à Paris', 'postal_code' => '75011',
            'city' => 'Paris', 'country' => 'France', 'location' => 'Paris']);

        $response = $this->actingAs($viewer)->get(route('feed'))->assertOk()
            ->assertViewHas('pkFeedAds', fn ($ads) => $ads->isEmpty())
            ->assertSee("Aucun prestataire n'a encore publié d'offre à Mayotte.", false)
            ->assertSeeText("Décrivez votre besoin : les prestataires de Mayotte qui s'inscrivent le verront.", false)
            ->assertSee('Me prévenir')
            ->assertSee('action="'.route('saved-searches.store').'"', false)
            ->assertSee('name="territory" value="976"', false)
            ->assertSee('name="type" value="offres"', false)
            ->assertSee('name="country" value="Mayotte"', false)
            ->assertSee('Vous connaissez un artisan ? Invitez-le')
            ->assertSee('href="https://wa.me/?text=', false)
            ->assertSee(rawurlencode(route('register', ['account_type' => 'professionnel'])), false)
            ->assertSee('rel="noopener"', false)
            ->assertSee('Voir les autres territoires')
            ->assertDontSee('les prestataires de votre zone la recevront')
            ->assertDontSee('id="pkRankingDisclosure"', false);
        // Aucun compteur dans l'état vide.
        $this->assertDoesNotMatchRegularExpression('/\d+ (prestataires?|artisans?|alertes?)/u', strip_tags(
            (string) str($response->getContent())->between('id="pkEmptyLocal"', 'Voir les autres territoires')
        ));

        $this->post(route('saved-searches.store'), ['type' => 'offres', 'territory' => '976', 'country' => 'Mayotte'])
            ->assertRedirect();
        $this->assertTrue(SavedSearch::where('user_id', $viewer->id)->where('service_type', 'offres')
            ->where('country', 'Mayotte')->exists());
    }

    public function test_one_to_three_offers_are_list_rows_and_four_or_more_use_the_grid(): void
    {
        $viewer = $this->mayotteReader();
        $author = User::factory()->create();
        $this->offer($author, ['title' => 'Première offre locale', 'photos' => ['ads/plomberie.jpg']]);
        $this->offer($author, ['title' => 'Deuxième offre locale']);

        $this->actingAs($viewer)->get(route('feed'))->assertOk()
            ->assertSee('class="pk-feed-rows" id="pkFeedList"', false)
            ->assertSee('class="pk-ad-row', false)
            ->assertSee('width="72" height="72"', false)
            ->assertDontSee('<article class="pk-ad pk-ad', false);

        $this->offer($author, ['title' => 'Troisième offre locale']);
        $this->offer($author, ['title' => 'Quatrième offre locale']);
        $this->get(route('feed'))->assertOk()
            ->assertSee('class="pk-feed-list" id="pkFeedList"', false)
            ->assertSee('<article class="pk-ad pk-ad', false)
            ->assertDontSee('class="pk-ad-row', false);
    }

    public function test_hidden_internal_accounts_are_removed_from_public_listings(): void
    {
        $internal = User::factory()->create(['name' => 'Compte interne', 'is_active' => true, 'profile_public' => true,
            'is_service_provider' => true, 'profession' => 'Plombier', 'city' => 'Mamoudzou', 'country' => 'Mayotte',
            'avatar' => 'avatars/interne.jpg', 'bio' => str_repeat('Présentation du compte interne. ', 3)]);
        $member = User::factory()->create(['is_active' => true]);
        $hidden = $this->offer($internal, ['title' => 'Offre du compte interne',
            'description' => 'Une description suffisamment longue pour la vitrine publique.']);
        $visible = $this->offer($member, ['title' => 'Offre d’un vrai membre',
            'description' => 'Une description suffisamment longue pour la vitrine publique.']);
        $viewer = $this->mayotteReader();
        $territory = app(TerritoryResolver::class)->find('976');
        // Sans réglage, le compte interne serait mis en avant partout.
        $this->assertTrue(PublicHomepageSelection::showcaseAds()->contains('id', $hidden->id));
        $this->assertTrue(PublicHomepageSelection::featuredProviders()->get()->contains('id', $internal->id));
        $this->assertTrue(app(HomeProviderDiscoveryService::class)
            ->discover($viewer, 'Mamoudzou', 'Mayotte', $territory)['profiles']->contains('id', $internal->id));

        config(['feed.hidden_author_ids' => [$internal->id]]);

        $this->actingAs($viewer)->get(route('feed'))->assertOk()
            ->assertViewHas('pkFeedAds', fn ($ads) => $ads->pluck('id')->all() === [$visible->id])
            ->assertDontSee('Offre du compte interne')
            ->assertViewHas('homeProfessionalProfiles', fn ($profiles) => ! $profiles->contains('id', $internal->id));

        $this->get(route('ads.index'))->assertOk()
            ->assertViewHas('ads', fn ($ads) => $ads->pluck('id')->contains($visible->id) && ! $ads->pluck('id')->contains($hidden->id));
        $this->assertFalse(PublicHomepageSelection::showcaseAds()->contains('id', $hidden->id));
        $this->assertTrue(PublicHomepageSelection::showcaseAds()->contains('id', $visible->id));
        $this->assertFalse(PublicHomepageSelection::featuredProviders()->get()->contains('id', $internal->id));

        $discovery = app(HomeProviderDiscoveryService::class)->discover($viewer, 'Mamoudzou', 'Mayotte', $territory);
        $this->assertFalse($discovery['profiles']->contains('id', $internal->id));

        // Liste vide = aucun compte masqué.
        config(['feed.hidden_author_ids' => []]);
        $this->get(route('feed'))->assertOk()
            ->assertViewHas('pkFeedAds', fn ($ads) => $ads->pluck('id')->contains($hidden->id));
    }

    public function test_provider_mode_is_not_scoped_by_the_client_territory_rules(): void
    {
        $provider = User::factory()->create(['user_type' => 'professionnel', 'is_service_provider' => true,
            'service_category' => 'Plombier', 'city' => 'Mamoudzou', 'country' => 'Mayotte', 'postal_code' => '97600']);
        $this->actingAs($provider)->get(route('feed'))->assertOk()
            ->assertViewHas('pkRole', 'provider')
            ->assertViewHas('pkFeedPagination', fn ($v) => $v === null)
            ->assertViewHas('pkRankingDisclosure', fn ($v) => $v === null)
            ->assertViewHas('pkEmptyLocal', fn ($v) => $v === null)
            ->assertDontSee('id="pkFeedScope"', false);
    }

    private function mayotteReader(): User
    {
        return User::factory()->create(['city' => 'Mamoudzou', 'country' => 'Mayotte', 'postal_code' => '97600']);
    }

    private function offer(User $author, array $attributes = []): Ad
    {
        return Ad::create(array_merge(['user_id' => $author->id, 'title' => 'Service de plomberie',
            'description' => 'Installation et dépannage de sanitaires.', 'category' => 'Plombier',
            'city' => 'Mamoudzou', 'country' => 'Mayotte', 'location' => 'Mamoudzou',
            'service_type' => 'offre', 'status' => 'active', 'visibility' => 'public'], $attributes));
    }
}
