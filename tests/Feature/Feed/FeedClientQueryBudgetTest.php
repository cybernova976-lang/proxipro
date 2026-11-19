<?php

namespace Tests\Feature\Feed;

use App\Models\Ad;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Budget de requêtes de l'accueil client (lot 3). Les seuils sont MESURÉS
 * sur ce jeu de données fixe, pas estimés :
 *   - avant l'allègement : 96 requêtes (8 novembre 2026, commit 2125aa96) ;
 *   - après l'allègement : 30 requêtes (zones invisibles non calculées en mode
 *     client, réglages mémorisés pour la requête : 42 lectures de « settings »
 *     tombaient à chaque fois sur la base) ;
 *   - avec la zone « À faire » du rail : 32 requêtes (abonnement push et
 *     alerte enregistrée, une requête chacune) ;
 *   - avec la version des CGU en vigueur, lue à chaque page depuis le
 *     18 novembre 2026 : 33 requêtes.
 *
 * Le compte dépend du jour : les prestataires affichés tournent chaque jour
 * (HomeProviderDiscoveryService::rotatingSelection) et la rotation coûte une
 * requête de plus quand elle revient au début de la liste, soit trois jours
 * sur quatre avec ce jeu de données. Le budget est donc vérifié sur un cycle
 * complet de quatre jours, à date fixée : il ne passe plus « les bons jours »
 * seulement, comme le 18 novembre où la requête des CGU est passée inaperçue.
 */
class FeedClientQueryBudgetTest extends TestCase
{
    use RefreshDatabase;

    private const MESURE_AVANT = 96;

    private const MESURE_APRES = 33;

    /** Un des quatre jours du cycle évite la seconde requête de rotation. */
    private const MESURE_JOUR_FAVORABLE = 32;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
    }

    private function seedFeed(): User
    {
        $reader = User::factory()->create(['city' => 'Mamoudzou', 'country' => 'Mayotte', 'postal_code' => '97600',
            'user_type' => 'particulier', 'is_service_provider' => false]);
        for ($i = 0; $i < 4; $i++) {
            $provider = User::factory()->create(['user_type' => 'professionnel', 'is_service_provider' => true,
                'city' => 'Mamoudzou', 'country' => 'Mayotte', 'postal_code' => '97600']);
            for ($j = 0; $j < 2; $j++) {
                Ad::create(['user_id' => $provider->id, 'title' => "Offre $i-$j", 'description' => 'Dépannage à domicile.',
                    'category' => 'Plombier', 'city' => 'Mamoudzou', 'country' => 'Mayotte', 'location' => 'Mamoudzou',
                    'service_type' => 'offre', 'status' => 'active', 'visibility' => 'public',
                    'is_boosted' => $i === 0 && $j === 0, 'boost_end' => $i === 0 && $j === 0 ? now()->addDays(3) : null]);
            }
        }
        Ad::create(['user_id' => $reader->id, 'title' => 'Cherche plombier', 'description' => 'Fuite sous l’évier.',
            'category' => 'Plombier', 'city' => 'Mamoudzou', 'country' => 'Mayotte', 'location' => 'Mamoudzou',
            'service_type' => 'demande', 'status' => 'active', 'visibility' => 'public']);

        return $reader;
    }

    /**
     * Quatre jours consécutifs : un cycle complet de la rotation. Chaque jour a
     * son plafond mesuré, pour qu'une requête ajoutée ne se cache pas dans la
     * marge du jour favorable.
     *
     * @return array<string, array{0: string, 1: int}>
     */
    public static function rotationCycle(): array
    {
        return [
            '18 novembre (jour favorable)' => ['2026-11-18 12:00:00', self::MESURE_JOUR_FAVORABLE],
            '19 novembre' => ['2026-11-19 12:00:00', self::MESURE_APRES],
            '20 novembre' => ['2026-11-20 12:00:00', self::MESURE_APRES],
            '21 novembre' => ['2026-11-21 12:00:00', self::MESURE_APRES],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('rotationCycle')]
    public function test_client_feed_stays_within_its_measured_query_budget(string $day, int $ceiling): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse($day, 'UTC'));
        $count = $this->feedQueryCount();

        $this->assertLessThan(self::MESURE_AVANT, $count);
        $this->assertLessThanOrEqual($ceiling, $count, "/feed (client) fait {$count} requêtes le {$day}.");
    }

    private function feedQueryCount(): int
    {
        $this->actingAs($this->seedFeed());

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get(route('feed'))->assertOk();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }

    public function test_visible_zones_keep_their_data(): void
    {
        $reader = $this->seedFeed();

        $this->actingAs($reader)->get(route('feed'))->assertOk()
            ->assertViewHas('pkFeedAds', fn ($ads) => $ads->count() === 6)
            ->assertViewHas('pkTerritory', fn ($territory) => $territory?->code === '976')
            ->assertViewHas('homeProfessionalProfiles', fn ($profiles) => $profiles->count() > 0)
            ->assertViewHas('pkRankingDisclosure', fn ($line) => str_contains($line['text'], 'Mayotte'));
    }
}
