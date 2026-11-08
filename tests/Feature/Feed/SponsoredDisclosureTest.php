<?php

namespace Tests\Feature\Feed;

use App\Models\Ad;
use App\Models\User;
use App\Services\Feed\RankingDisclosure;
use App\Support\AdDisclosure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Piste C du lot 1 : mention « Sponsorisé » + « Pourquoi ? » sur chaque carte
 * promue du flux client, plus de « Premium » ni de « Boosté » côté client,
 * statut déclaré de l'annonceur, « Urgent · option payante » seulement
 * quand urgent_until est renseigné, ligne de critère présente.
 */
class SponsoredDisclosureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
    }

    public function test_every_promoted_card_in_the_grid_is_labelled_sponsored_with_a_why_link(): void
    {
        $viewer = $this->mayotteReader();
        $author = User::factory()->create();
        $subscriber = User::factory()->create();
        $subscriber->proSubscriptions()->create(['plan' => 'monthly', 'amount' => 9.99, 'status' => 'active',
            'starts_at' => now()->subDay(), 'ends_at' => now()->addMonth()]);

        $boosted = $this->offer($author, ['title' => 'Offre boostée', 'is_boosted' => true,
            'boost_end' => now()->addDays(3), 'boost_type' => 'boost_3']);
        $premium = $this->offer($subscriber, ['title' => 'Offre d’un abonné']);
        $ordinary = [];
        foreach (['Offre ordinaire A', 'Offre ordinaire B', 'Offre ordinaire C'] as $title) {
            $ordinary[] = $this->offer($author, ['title' => $title]);
        }

        $response = $this->actingAs($viewer)->get(route('feed'))->assertOk();
        $cards = $this->cards($response, '<article class="pk-ad');

        $this->assertCount(5, $cards, 'Cinq offres : la grille est utilisée.');
        foreach ([$boosted, $premium] as $ad) {
            $card = $cards[$ad->title];
            $this->assertStringContainsString('pk-tag--sponsored', $card);
            $this->assertStringContainsString('Sponsorisé', $card);
            $this->assertStringContainsString('le prestataire a payé pour être affiché ici', $card);
            $this->assertStringContainsString('href="'.route('legal.platform-rules').'#classement"', $card);
            $this->assertStringContainsString('Pourquoi ?', $card);
        }
        foreach ($ordinary as $ad) {
            $this->assertStringNotContainsString('Sponsorisé', $cards[$ad->title]);
        }

        $list = $this->feedList($response);
        $this->assertSame(2, substr_count($list, 'pk-tag--sponsored'));
        $this->assertStringNotContainsString('Premium', $list);
        $this->assertStringNotContainsString('Boosté', $list);
        $this->assertStringNotContainsString('pk-tag--boost', $list);
    }

    public function test_promoted_offer_shown_as_a_list_row_is_labelled_sponsored(): void
    {
        $viewer = $this->mayotteReader();
        $author = User::factory()->create();
        $boosted = $this->offer($author, ['title' => 'Offre boostée seule', 'is_boosted' => true,
            'boost_end' => now()->addDays(7), 'boost_type' => 'boost_7']);
        $ordinary = $this->offer($author, ['title' => 'Offre ordinaire seule']);

        $response = $this->actingAs($viewer)->get(route('feed'))->assertOk();
        $rows = $this->cards($response, '<article class="pk-ad-row');

        $this->assertCount(2, $rows, 'Deux offres : les lignes de liste sont utilisées.');
        $this->assertStringContainsString('Sponsorisé', $rows[$boosted->title]);
        $this->assertStringContainsString('href="'.route('legal.platform-rules').'#classement"', $rows[$boosted->title]);
        $this->assertStringNotContainsString('Sponsorisé', $rows[$ordinary->title]);
        $this->assertStringNotContainsString('Boosté', $this->feedList($response));
    }

    public function test_ranking_line_is_present_above_the_client_feed(): void
    {
        $viewer = $this->mayotteReader();
        $this->offer(User::factory()->create(), ['is_boosted' => true, 'boost_end' => now()->addDay()]);

        $this->actingAs($viewer)->get(route('feed'))->assertOk()
            ->assertSee('id="pkRankingDisclosure"', false)
            ->assertSee("Classement : offres de Mayotte. Les offres sponsorisées (payantes) passent en premier, puis les plus récentes.")
            ->assertSee(route('legal.platform-rules').'#classement', false);
    }

    public function test_declared_status_of_the_advertiser_is_shown_on_cards(): void
    {
        $viewer = $this->mayotteReader();
        $professional = User::factory()->create(['account_type' => 'professionnel', 'user_type' => 'professionnel',
            'business_type' => 'auto_entrepreneur']);
        $private = User::factory()->create(['account_type' => 'particulier', 'user_type' => 'particulier']);
        $pro = $this->offer($professional, ['title' => 'Offre d’un professionnel']);
        $individual = $this->offer($private, ['title' => 'Offre d’un particulier']);

        $rows = $this->cards($this->actingAs($viewer)->get(route('feed'))->assertOk(), '<article class="pk-ad-row');
        $this->assertStringContainsString('>Professionnel</span>', $rows[$pro->title]);
        $this->assertStringNotContainsString('>Particulier</span>', $rows[$pro->title]);
        $this->assertStringContainsString('>Particulier</span>', $rows[$individual->title]);

        $this->assertSame('Professionnel', AdDisclosure::advertiserStatus($professional));
        $this->assertSame('Particulier', AdDisclosure::advertiserStatus($private));
        $this->assertSame('Professionnel', AdDisclosure::advertiserStatus(
            (new User)->forceFill(['account_type' => null, 'user_type' => 'professionnel'])
        ));
        // Statut absent ou inconnu : aucune mention, rien n'est deviné.
        $this->assertNull(AdDisclosure::advertiserStatus((new User)->forceFill(['account_type' => null, 'user_type' => null])));
        $this->assertNull(AdDisclosure::advertiserStatus((new User)->forceFill(['account_type' => 'admin', 'user_type' => ''])));
        $this->assertNull(AdDisclosure::advertiserStatus(null));
    }

    public function test_paid_urgency_is_disclosed_and_free_urgency_stays_urgent(): void
    {
        $provider = User::factory()->create(['user_type' => 'professionnel', 'account_type' => 'professionnel',
            'is_service_provider' => true, 'service_category' => 'Plombier']);
        $client = User::factory()->create();
        $paid = $this->offer($client, ['title' => 'Demande urgente payée', 'service_type' => 'demande',
            'is_urgent' => true, 'urgent_until' => now()->addDays(5)]);
        $free = $this->offer($client, ['title' => 'Demande urgente gratuite', 'service_type' => 'demande',
            'is_urgent' => true, 'urgent_until' => null]);
        $expired = $this->offer($client, ['title' => 'Demande urgence expirée', 'service_type' => 'demande',
            'is_urgent' => true, 'urgent_until' => now()->subDay()]);

        $cards = $this->cards($this->actingAs($provider)->get(route('feed'))->assertOk(), '<article class="pk-ad');
        $this->assertStringContainsString('Urgent · option payante', $cards[$paid->title]);
        $this->assertStringContainsString('Urgent', $cards[$free->title]);
        $this->assertStringNotContainsString('option payante', $cards[$free->title]);
        $this->assertStringNotContainsString('pk-tag--urgent', $cards[$expired->title]);

        $this->assertSame(AdDisclosure::PAID_URGENT_LABEL, AdDisclosure::urgentLabel($paid->fresh()));
        $this->assertSame(AdDisclosure::FREE_URGENT_LABEL, AdDisclosure::urgentLabel($free->fresh()));
        $this->assertNull(AdDisclosure::urgentLabel($expired->fresh()));
    }

    public function test_sponsored_effective_date_comes_from_configuration_and_is_never_invented(): void
    {
        $disclosure = app(RankingDisclosure::class);

        config(['feed.sponsored.effective_at' => null]);
        $this->assertNull($disclosure->sponsoredEffectiveDate());
        $this->assertSame('à une date annoncée au moins 15 jours à l’avance', $disclosure->sponsoredEffectiveLabel());

        config(['feed.sponsored.effective_at' => 'pas une date']);
        $this->assertSame('à une date annoncée au moins 15 jours à l’avance', $disclosure->sponsoredEffectiveLabel());

        config(['feed.sponsored.effective_at' => '2026-11-25']);
        $this->assertSame('à compter du 25/11/2026', $disclosure->sponsoredEffectiveLabel());

        // Format français JJ/MM/AAAA : jamais lu comme MM/JJ.
        config(['feed.sponsored.effective_at' => '05/12/2026']);
        $this->assertSame('à compter du 05/12/2026', $disclosure->sponsoredEffectiveLabel());
        config(['feed.sponsored.effective_at' => '23/11/2026']);
        $this->assertSame('à compter du 23/11/2026', $disclosure->sponsoredEffectiveLabel());

        // Date impossible ou relative : aucune date n'est inventée.
        foreach (['2026-02-30', 'tomorrow', '+1 week', '2026-1-5'] as $invalid) {
            config(['feed.sponsored.effective_at' => $invalid]);
            $this->assertSame('à une date annoncée au moins 15 jours à l’avance', $disclosure->sponsoredEffectiveLabel(), $invalid);
        }
    }

    public function test_promotions_granted_by_the_administration_are_not_presented_as_paid(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $author = User::factory()->create();
        $offer = $this->offer($author, ['title' => 'Offre boostée par l’administration']);
        $demand = $this->offer($author, ['title' => 'Demande rendue urgente par l’administration', 'service_type' => 'demande']);

        $this->actingAs($admin)->post(route('admin.ads.grant-boost', $offer->id), ['boost_type' => 'boost_3']);
        $this->actingAs($admin)->post(route('admin.ads.grant-urgent', $demand->id), ['duration_days' => 3]);

        $offer->refresh();
        $demand->refresh();
        $this->assertTrue($offer->isCurrentlyBoosted());
        $this->assertSame('admin', $offer->boost_source);
        $this->assertFalse(AdDisclosure::isSponsored($offer));
        $this->assertTrue($demand->isCurrentlyUrgent());
        $this->assertSame(AdDisclosure::FREE_URGENT_LABEL, AdDisclosure::urgentLabel($demand));

        // Côté flux client, l'offre n'est ni marquée « Sponsorisé » ni classée parmi les offres sponsorisées.
        $this->actingAs($this->mayotteReader())->get(route('feed'))->assertOk()
            ->assertViewHas('pkFeedAds', fn ($ads) => $ads->pluck('id')->all() === [$offer->id]
                && ! (bool) $ads->first()->feed_is_promoted)
            ->assertDontSee(AdDisclosure::SPONSORED_SCREEN_READER);

        // Un boost acheté ensuite redevient sponsorisé.
        $offer->forceFill(['boost_source' => null])->save();
        $this->assertTrue(AdDisclosure::isSponsored($offer->fresh()));
    }

    public function test_platform_rules_announce_hidden_internal_accounts_only_when_configured(): void
    {
        config(['feed.hidden_author_ids' => []]);
        $this->get(route('legal.platform-rules'))->assertOk()
            ->assertDontSee('Les annonces des comptes internes ou de test de la plateforme ne sont pas affichées.')
            ->assertSee('à défaut, d’après sa position détectée automatiquement');

        config(['feed.hidden_author_ids' => [123]]);
        $this->get(route('legal.platform-rules'))->assertOk()
            ->assertSee('Les annonces des comptes internes ou de test de la plateforme ne sont pas affichées.');
    }

    public function test_platform_rules_describe_the_current_order_and_the_announced_mechanism(): void
    {
        config(['feed.sponsored.effective_at' => null]);
        $this->get(route('legal.platform-rules'))->assertOk()
            ->assertSee('id="classement"', false)
            ->assertSee('les offres sponsorisées passent en premier')
            ->assertSee('deux offres au plus par prestataire passent avant ses offres suivantes')
            ->assertSee('les offres les plus récentes d’abord')
            ->assertSee('toutes les offres actives du prestataire abonné')
            ->assertSee('Aucune position n’est garantie.')
            ->assertSee('la remontée payante d’une annonce n’est plus proposée pour le moment')
            ->assertSee('2 places au plus seront sponsorisées')
            ->assertSee('tirage au sort quotidien')
            ->assertSee('une seule carte par prestataire et par page')
            ->assertSee('Un abonnement Premium comptera pour une seule annonce du prestataire.')
            ->assertSee('à une date annoncée au moins 15 jours à l’avance, un nouveau mécanisme', false)
            ->assertDontSee('« Prioritaire »');

        config(['feed.sponsored.effective_at' => '2026-11-25']);
        $this->get(route('legal.platform-rules'))->assertOk()
            ->assertSee('à compter du 25/11/2026, un nouveau mécanisme', false);
    }

    /** @return array<string, string> titre de l'annonce => HTML de sa carte */
    private function cards(TestResponse $response, string $marker): array
    {
        $cards = [];
        foreach (array_slice(explode($marker, $this->feedList($response)), 1) as $chunk) {
            if (preg_match('#<h3><a [^>]*>([^<]+)</a></h3>#u', $chunk, $match)) {
                $cards[html_entity_decode(trim($match[1]), ENT_QUOTES)] = $chunk;
            }
        }

        return $cards;
    }

    private function feedList(TestResponse $response): string
    {
        $html = $response->getContent();
        $start = strpos($html, 'id="pkFeedList"');
        $this->assertNotFalse($start, 'La liste du flux est rendue.');
        $end = strpos($html, '</section>', $start);

        return substr($html, $start, $end === false ? null : $end - $start);
    }

    private function mayotteReader(): User
    {
        return User::factory()->create(['city' => 'Mamoudzou', 'country' => 'Mayotte', 'postal_code' => '97600']);
    }

    private function offer(User $author, array $attributes = []): Ad
    {
        return Ad::create(array_merge(['user_id' => $author->id, 'title' => 'Service de plomberie',
            'description' => 'Installation et dépannage de sanitaires.', 'category' => 'Plombier',
            'city' => 'Mamoudzou', 'country' => 'Mayotte', 'location' => 'Mamoudzou', 'postal_code' => '97600',
            'service_type' => 'offre', 'status' => 'active', 'visibility' => 'public'], $attributes));
    }
}
