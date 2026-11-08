<?php

namespace Tests\Feature\Demand;

use App\Models\Ad;
use App\Models\User;
use App\Services\GeocodingService;
use App\Support\DemandPublicationContinuation;
use App\Support\MarketplaceCategoryRegistry;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Parcours guidé « Publier sans écrire » : /demande?situation=<clé>.
 * Le filtre GuidedDemand du contrôle préalable couvre ce fichier.
 */
class GuidedDemandSituationFeatureTest extends TestCase
{
    use RefreshDatabase;

    private const KOUNGOU_DESCRIPTION = 'Bonjour. J’ai une fuite d’eau dans la cuisine. L’eau coule beaucoup. J’ai besoin d’aide aujourd’hui, le matin si possible. Le prix est à discuter.';

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->mock(GeocodingService::class, function ($mock): void {
            $mock->shouldReceive('geocode')->andReturn(null);
        });
    }

    public function test_valid_situation_renders_one_question_per_screen(): void
    {
        $response = $this->actingAs($this->mayotteClient())
            ->get(route('demand.create', ['situation' => 'fuite-eau']));

        $response->assertOk()
            ->assertViewIs('demands.guided')
            ->assertViewHas('pkStepTotal', 9)
            ->assertSee('Où est la fuite ?')
            ->assertSee('Je ne sais pas')
            ->assertSeeText('Étape 1 sur 9')
            ->assertSee('data-pk-listen hidden', false)
            ->assertSee('capture="environment"', false)
            ->assertSee('name="answers[fuite_lieu]"', false)
            ->assertSee('value="unknown"', false)
            ->assertSee(asset('img/pk-situations/cuisine.svg'), false)
            ->assertSee(asset('css/demand-guided.css'), false)
            ->assertSee(asset('js/demand-text-composer.js'), false)
            ->assertSee(asset('js/demand-guided.js'), false)
            ->assertSee('Publier ma demande')
            ->assertSee('Pour écrire sans clavier, appuyez sur le micro de votre clavier.')
            ->assertDontSee('SpeechRecognition', false);

        $this->assertSame(9, substr_count($response->getContent(), 'data-pk-step="'));

        $guided = $this->fragment($response, '//*[@id="pkGuided"]');
        $this->assertSame(0, $guided['xpath']->query('.//style', $guided['node'])->length, 'Aucun CSS dans la vue.');
        $this->assertSame(0, $guided['xpath']->query('.//*[@style]', $guided['node'])->length, 'Aucun attribut style.');
        $this->assertSame(
            0,
            $guided['xpath']->query('.//script[not(@src) and not(@type="application/json")]', $guided['node'])->length,
            'Aucun JS en ligne dans la vue.'
        );
        $this->assertSame(1, $guided['xpath']->query('.//*[contains(concat(" ", normalize-space(@class), " "), " pk-btn ")]', $guided['node'])->length);

        // Mayotte : 17 communes, donc des tuiles ; la commune du compte est proposée.
        $response->assertSee('C’est à Koungou ?')->assertSee('name="city" value="Mamoudzou"', false);
    }

    public function test_guest_sees_the_login_label_and_the_login_continuation(): void
    {
        $this->get(route('demand.create', ['situation' => 'wc-bouche']))
            ->assertOk()
            ->assertViewIs('demands.guided')
            ->assertSee('Me connecter pour publier')
            ->assertSee('Que fait l’eau ?')
            ->assertViewHas('pkGuidedConfig', fn (array $config) => $config['isGuest'] === true
                && $config['loginUrl'] === route('login', ['continue' => 'demand', 'situation' => 'wc-bouche'])
                && array_keys($config['catalog']['situations']) === ['wc-bouche']);
    }

    public function test_unknown_or_disabled_situation_falls_back_to_the_existing_form(): void
    {
        $this->get(route('demand.create', ['situation' => 'piscine']))
            ->assertOk()
            ->assertViewIs('demands.create');

        $this->get(route('demand.create', ['situation' => '../../etc']))
            ->assertOk()
            ->assertViewIs('demands.create');

        MarketplaceCategoryRegistry::storeEnabledIds(['jardinage-exterieur']);

        $this->get(route('demand.create', ['situation' => 'fuite-eau']))
            ->assertOk()
            ->assertViewIs('demands.create');
        $this->get(route('demand.create', ['situation' => 'jardin']))
            ->assertOk()
            ->assertViewIs('demands.guided');
    }

    public function test_client_publishes_with_the_composed_text_only(): void
    {
        $client = $this->mayotteClient();

        $response = $this->actingAs($client)->post(route('demand.store'), $this->koungouPayload());

        $ad = $client->ads()->sole();
        $response->assertRedirect(route('demand.matching', $ad))
            ->assertSessionHas('demand_published', true);

        $this->assertSame('Fuite d’eau dans la cuisine à Koungou', $ad->title);
        $this->assertSame(self::KOUNGOU_DESCRIPTION, $ad->description);
        $this->assertSame($this->fixtureExpected('exemple-fuite-cuisine-koungou')['description'], $ad->description);
        $this->assertSame('fuite-eau', $ad->situation_key);
        $this->assertSame('Plombier', $ad->category);
        $this->assertSame('Bricolage & Travaux', $ad->main_category);
        $this->assertSame('Koungou', $ad->city);
        $this->assertSame('Koungou', $ad->location);
        $this->assertSame('repair', $ad->ad_details['service_details']['work_scope']);
        $this->assertSame('composed', $ad->ad_details['text_origin']);
        $this->assertSame(1, $ad->ad_details['composer_version']);
        $this->assertSame('today', $ad->ad_details['when']);
        $this->assertSame(today()->toDateString(), $ad->ad_details['desired_date']);
        $this->assertSame('morning', $ad->ad_details['time_window']);
        $this->assertNull($ad->ad_details['author_detail']);
        $this->assertSame(['fuite_lieu' => 'cuisine', 'fuite_force' => 'beaucoup', 'logement' => 'unknown'], $ad->ad_details['answers']);

        $this->assertDatabaseHas('usage_daily_metrics', ['event_name' => 'demand_published_guided', 'route_name' => 'demand.store']);
    }

    public function test_unknown_answers_produce_no_sentence_and_store_honest_values(): void
    {
        $client = $this->mayotteClient();

        $this->actingAs($client)->post(route('demand.store'), $this->koungouPayload([
            'answers' => ['fuite_lieu' => 'unknown', 'fuite_force' => 'unknown', 'logement' => 'unknown'],
        ]))->assertSessionHasNoErrors();

        $ad = $client->ads()->sole();
        $this->assertSame('Fuite d’eau à Koungou', $ad->title);
        $this->assertSame('Bonjour. J’ai une fuite d’eau. J’ai besoin d’aide aujourd’hui, le matin si possible. Le prix est à discuter.', $ad->description);
        $this->assertStringNotContainsString('C’est dans', $ad->description);
        $this->assertSame(['work_scope' => 'repair', 'site_type' => 'unknown'], $ad->ad_details['service_details']);

        // Questions laissées sans réponse : traitées comme « Je ne sais pas ».
        $menage = $this->actingAs($client)->post(route('demand.store'), $this->koungouPayload([
            'situation_key' => 'menage',
            'answers' => [],
        ]));
        $menage->assertSessionHasNoErrors();
        $ad = $client->ads()->where('situation_key', 'menage')->sole();
        $this->assertSame(['frequency' => 'unknown', 'duration_band' => 'unknown'], $ad->ad_details['service_details']);
        $this->assertSame('Femme/Homme de ménage', $ad->category);
    }

    public function test_invalid_answer_is_rejected_without_creating_an_ad(): void
    {
        $client = $this->mayotteClient();

        $this->actingAs($client)
            ->from(route('demand.create', ['situation' => 'fuite-eau']))
            ->post(route('demand.store'), $this->koungouPayload(['answers' => ['fuite_lieu' => 'piscine']]))
            ->assertRedirect(route('demand.create', ['situation' => 'fuite-eau']))
            ->assertSessionHasErrors(['answers' => 'Une réponse n’est pas reconnue. Choisissez une réponse proposée.']);

        $this->actingAs($client)
            ->post(route('demand.store'), $this->koungouPayload(['answers' => ['menage_frequence' => 'weekly']]))
            ->assertSessionHasErrors('answers');

        $this->actingAs($client)
            ->postJson(route('demand.store'), $this->koungouPayload(['answers' => ['fuite_lieu' => ['cuisine']]]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('answers');

        $this->actingAs($client)
            ->post(route('demand.store'), $this->koungouPayload(['when' => 'date', 'desired_date_choice' => today()->subDay()->toDateString()]))
            ->assertSessionHasErrors(['desired_date_choice' => 'Choisissez une date à partir d’aujourd’hui.']);

        $this->actingAs($client)
            ->post(route('demand.store'), $this->koungouPayload(['situation_key' => 'piscine']))
            ->assertSessionHasErrors(['situation_key' => 'Ce besoin n’est plus proposé. Choisissez un autre besoin.']);

        $this->assertSame(0, Ad::count());
    }

    public function test_posted_trade_cannot_override_the_situation(): void
    {
        $client = $this->mayotteClient();

        $this->actingAs($client)->post(route('demand.store'), $this->koungouPayload([
            'main_category' => 'Jardinage & Extérieur',
            'category' => 'Jardinier',
            'title' => 'Titre imposé',
            'service_details' => ['area_band' => 'large'],
        ]))->assertSessionHasNoErrors();

        $ad = $client->ads()->sole();
        $this->assertSame('Bricolage & Travaux', $ad->main_category);
        $this->assertSame('Plombier', $ad->category);
        $this->assertSame('Fuite d’eau dans la cuisine à Koungou', $ad->title);
        $this->assertArrayNotHasKey('area_band', $ad->ad_details['service_details']);
    }

    public function test_guided_duplicate_redirects_to_the_existing_demand(): void
    {
        $client = $this->mayotteClient();

        $this->actingAs($client)->post(route('demand.store'), $this->koungouPayload());
        $first = $client->ads()->sole();

        $this->actingAs($client)->post(route('demand.store'), $this->koungouPayload())
            ->assertRedirect(route('demand.matching', $first))
            ->assertSessionHas('demand_duplicate', true)
            ->assertSessionMissing('demand_published');

        $this->assertSame(1, Ad::count());

        $this->actingAs($client)
            ->followingRedirects()
            ->post(route('demand.store'), $this->koungouPayload())
            ->assertOk()
            ->assertSee('Vous avez déjà publié cette demande aujourd’hui.')
            ->assertSee('href="'.route('ads.show', $first).'"', false)
            ->assertDontSee('Booster ma demande');

        $this->assertSame(1, Ad::count());
        $this->assertDatabaseHas('usage_daily_metrics', ['event_name' => 'demand_duplicate_redirect']);
    }

    public function test_this_week_is_shown_as_a_label_not_a_fake_date(): void
    {
        $client = $this->mayotteClient();
        $this->actingAs($client)->post(route('demand.store'), $this->koungouPayload([
            'when' => 'this_week',
            'time_window' => 'flexible',
        ]))->assertSessionHasNoErrors();
        $ad = $client->ads()->sole();

        $this->assertSame('Cette semaine', $ad->desiredWhenLabel());
        $this->assertStringContainsString('J’ai besoin d’aide cette semaine.', $ad->description);

        $todayLabel = today()->locale('fr')->translatedFormat('d F Y');
        $provider = User::factory()->create([
            'user_type' => 'professionnel', 'account_type' => 'professionnel', 'is_service_provider' => true,
            'profession' => 'Plombier', 'service_category' => 'Plombier', 'profile_public' => true, 'is_active' => true,
            'country' => 'Mayotte', 'city' => 'Mamoudzou', 'postal_code' => '97600',
        ]);

        $this->actingAs($provider)->get(route('ads.show', $ad))
            ->assertOk()
            ->assertSee('Cette semaine')
            ->assertDontSee($todayLabel);

        // Carte du flux, telle qu'un prestataire la voit.
        $card = view('feed.partials.ad-card', ['ad' => $ad->fresh('user'), 'pkSaved' => [], 'pkRole' => 'provider'])->render();
        $this->assertStringContainsString('Cette semaine', $card);
        $this->assertStringNotContainsString($todayLabel, $card);

        $this->assertSame('Ce mois-ci · Soir', Ad::whenLabelFromDetails(['when' => 'this_month', 'desired_date' => '2026-11-08', 'time_window' => 'evening']));
        $this->assertSame('03 décembre 2026', Ad::whenLabelFromDetails(['when' => 'date', 'desired_date' => '2026-12-03']));
        $this->assertNull(Ad::whenLabelFromDetails([]));
    }

    public function test_edited_text_is_kept_and_marked_as_edited(): void
    {
        $client = $this->mayotteClient();

        $this->actingAs($client)->post(route('demand.store'), $this->koungouPayload([
            'description_override' => '  Bonjour.   La fuite est sous l’évier, venez vite.  ',
        ]))->assertSessionHasNoErrors();

        $ad = $client->ads()->sole();
        $this->assertSame('Bonjour. La fuite est sous l’évier, venez vite.', $ad->description);
        $this->assertSame('edited', $ad->ad_details['text_origin']);
        $this->assertSame('Fuite d’eau dans la cuisine à Koungou', $ad->title);

        // Texte ouvert puis laissé tel quel : il reste « composé ».
        $ad->delete();
        $this->actingAs($client)->post(route('demand.store'), $this->koungouPayload([
            'description_override' => self::KOUNGOU_DESCRIPTION,
        ]))->assertSessionHasNoErrors();
        $this->assertSame('composed', $client->ads()->sole()->ad_details['text_origin']);
    }

    public function test_author_detail_is_appended_last(): void
    {
        $client = $this->mayotteClient();

        $this->actingAs($client)->post(route('demand.store'), $this->koungouPayload([
            'detail' => '  Le   robinet est très vieux.  ',
        ]))->assertSessionHasNoErrors();

        $ad = $client->ads()->sole();
        $this->assertSame(self::KOUNGOU_DESCRIPTION.' Le robinet est très vieux.', $ad->description);
        $this->assertSame('composed_plus_detail', $ad->ad_details['text_origin']);
        $this->assertSame('Le robinet est très vieux.', $ad->ad_details['author_detail']);
    }

    public function test_guest_is_sent_to_login_with_the_situation_and_comes_back_to_it(): void
    {
        $user = User::factory()->create(['password' => Hash::make('password')]);

        $this->get(route('login', ['continue' => 'demand', 'situation' => 'fuite-eau']))
            ->assertOk()
            ->assertSessionHas(DemandPublicationContinuation::SESSION_KEY, true)
            ->assertSessionHas(DemandPublicationContinuation::SITUATION_SESSION_KEY, 'fuite-eau');

        $this->post(route('login.attempt'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('demand.create', ['resume' => 1, 'situation' => 'fuite-eau']))
            ->assertSessionMissing(DemandPublicationContinuation::SITUATION_SESSION_KEY);
        $this->assertStringEndsWith('/demande?resume=1&situation=fuite-eau', route('demand.create', ['resume' => 1, 'situation' => 'fuite-eau']));

        $this->get(route('demand.create', ['resume' => 1, 'situation' => 'fuite-eau']))
            ->assertOk()
            ->assertViewIs('demands.guided')
            ->assertViewHas('pkGuidedConfig', fn (array $config) => $config['resume'] === true && $config['isGuest'] === false);

        $this->post(route('logout'));

        // Une clé inconnue n'est pas reprise : retour au formulaire habituel.
        $this->get(route('login', ['continue' => 'demand', 'situation' => 'https://example.org']))
            ->assertSessionMissing(DemandPublicationContinuation::SITUATION_SESSION_KEY);
        $this->get(route('login', ['continue' => 'demand', 'situation' => 'piscine']))
            ->assertSessionMissing(DemandPublicationContinuation::SITUATION_SESSION_KEY);
        $this->post(route('login.attempt'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('demand.create', ['resume' => 1]));
    }

    public function test_heic_photo_gets_a_clear_french_message(): void
    {
        $client = $this->mayotteClient();

        $response = $this->actingAs($client)->post(route('demand.store'), $this->koungouPayload([
            'photos' => [UploadedFile::fake()->create('a.heic', 100, 'image/heic')],
        ]));

        $response->assertSessionHasErrors('photos.0');
        $this->assertStringContainsString('HEIC', implode(' ', session('errors')->get('photos.0')));
        $this->assertSame(0, Ad::count());

        // Le message s'affiche en clair dans l'en-tête du parcours.
        $this->actingAs($client)->get(route('demand.create', ['situation' => 'fuite-eau']))
            ->assertOk()
            ->assertSee('les photos HEIC de l’iPhone ne sont pas lues');

        $this->actingAs($client)->post(route('demand.store'), $this->koungouPayload([
            'photos' => [UploadedFile::fake()->create('lourde.jpg', 6000, 'image/jpeg')],
        ]))->assertSessionHasErrors(['photos.0' => 'Une photo dépasse 5 Mo. Choisissez une photo plus légère.']);
    }

    public function test_published_screen_shows_the_notified_count_only_when_measured(): void
    {
        $client = $this->mayotteClient();
        $ad = $this->publishedDemand($client);

        foreach ([null, 0, 3] as $count) {
            $ad->forceFill(['notified_providers_count' => $count])->save();

            $response = $this->actingAs($client)->withSession(['demand_published' => true])->get(route('demand.matching', $ad));
            $response->assertOk()->assertSee('Demande publiée')->assertDontSee('Booster ma demande');

            if ($count === null) {
                $response->assertDontSee('Envoyée à')->assertDontSee('Aucun prestataire de ce métier');
            } elseif ($count === 0) {
                $response->assertSee('Aucun prestataire de ce métier n’est encore inscrit à Mayotte. Partagez votre demande.')
                    ->assertDontSee('Envoyée à');
            } else {
                $response->assertSee('Envoyée à 3 prestataires à Mayotte.')->assertDontSee('Aucun prestataire de ce métier');
            }

            $published = $this->fragment($response, '//*[contains(concat(" ", normalize-space(@class), " "), " pk-published ")]');
            $this->assertSame(1, $published['xpath']->query('.//*[contains(concat(" ", normalize-space(@class), " "), " pk-btn ")]', $published['node'])->length);
            $response->assertSee('data-pk-share', false)->assertSee('data-push-enable', false);
        }
    }

    public function test_whatsapp_help_is_hidden_by_default_and_without_number_or_hours(): void
    {
        $url = route('demand.create', ['situation' => 'fuite-eau']);

        $this->get($url)->assertOk()->assertDontSee('Publier avec de l’aide')->assertDontSee('pk-guided__assist', false);

        config(['demand.assist_whatsapp' => ['enabled' => true, 'number' => null, 'hours' => 'du lundi au vendredi, 8 h – 17 h']]);
        $this->get($url)->assertOk()->assertDontSee('Publier avec de l’aide');

        config(['demand.assist_whatsapp' => ['enabled' => true, 'number' => '262639000000', 'hours' => null]]);
        $this->get($url)->assertOk()->assertDontSee('Publier avec de l’aide');

        config(['demand.assist_whatsapp' => ['enabled' => true, 'number' => '+262 639', 'hours' => 'tous les jours']]);
        $this->get($url)->assertOk()->assertDontSee('Publier avec de l’aide');

        config(['demand.assist_whatsapp' => ['enabled' => true, 'number' => '262639000000', 'hours' => 'du lundi au vendredi, 8 h – 17 h']]);
        $this->get($url)->assertOk()
            ->assertSee('Publier avec de l’aide')
            ->assertSee('https://wa.me/262639000000?text=', false)
            ->assertSee(rawurlencode('Bonjour, j’ai besoin d’aide pour publier une demande : Fuite d’eau'), false)
            ->assertSee('Réponse du lundi au vendredi, 8 h – 17 h');
    }

    public function test_existing_free_form_is_unchanged(): void
    {
        $this->get(route('demand.create'))
            ->assertOk()
            ->assertViewIs('demands.create')
            ->assertSee('Étape 1 sur 5');

        $client = User::factory()->create();
        $this->actingAs($client)->post(route('demand.store'), [
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
            'service_details' => ['work_scope' => 'repair', 'site_type' => 'house'],
            'publication_confirmed' => '1',
            // Champs du parcours guidé envoyés à tort : ignorés sans situation_key.
            'answers' => ['fuite_lieu' => 'cuisine'],
            'when' => 'this_week',
        ])->assertSessionHas('demand_published', true);

        $ad = $client->ads()->sole();
        $this->assertNull($ad->situation_key);
        $this->assertSame('Réparer une fuite sous mon évier', $ad->title);
        $this->assertSame(['desired_date', 'time_window', 'urgency', 'service_details'], array_keys($ad->ad_details));
        $this->assertDatabaseHas('usage_daily_metrics', ['event_name' => 'demand_published_free']);
    }

    private function mayotteClient(): User
    {
        return User::factory()->create([
            'user_type' => 'particulier',
            'is_service_provider' => false,
            'country' => 'Mayotte',
            'city' => 'Koungou',
            'postal_code' => '97690',
        ]);
    }

    private function koungouPayload(array $overrides = []): array
    {
        return array_merge([
            'situation_key' => 'fuite-eau',
            'guided' => '1',
            'answers' => ['fuite_lieu' => 'cuisine', 'fuite_force' => 'beaucoup', 'logement' => 'unknown'],
            'country' => 'Mayotte',
            'city' => 'Koungou',
            'when' => 'today',
            'time_window' => 'morning',
            'price_type' => 'negotiable',
            'publication_confirmed' => '1',
        ], $overrides);
    }

    private function publishedDemand(User $client): Ad
    {
        $this->actingAs($client)->post(route('demand.store'), $this->koungouPayload())->assertSessionHasNoErrors();
        $ad = $client->ads()->sole();
        $ad->forceFill(['postal_code' => '97600'])->save();

        return $ad->fresh();
    }

    private function fixtureExpected(string $name): array
    {
        $fixture = json_decode((string) file_get_contents(base_path('tests/fixtures/demand-text-cases.json')), true);

        foreach ($fixture['cases'] as $case) {
            if ($case['name'] === $name) {
                return $case['expected'];
            }
        }

        $this->fail('Cas de fixture introuvable : '.$name);
    }

    /** @return array{xpath: DOMXPath, node: \DOMNode} */
    private function fragment(TestResponse $response, string $query): array
    {
        $dom = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $xpath = new DOMXPath($dom);
        $nodes = $xpath->query($query);
        $this->assertSame(1, $nodes->length, 'Fragment attendu une seule fois : '.$query);

        return ['xpath' => $xpath, 'node' => $nodes->item(0)];
    }
}
