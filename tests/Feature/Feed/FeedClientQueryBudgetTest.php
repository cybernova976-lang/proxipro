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
 *     alerte enregistrée, une requête chacune).
 */
class FeedClientQueryBudgetTest extends TestCase
{
    use RefreshDatabase;

    private const MESURE_AVANT = 96;

    private const MESURE_APRES = 32;

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

    public function test_client_feed_stays_within_its_measured_query_budget(): void
    {
        $reader = $this->seedFeed();
        $this->actingAs($reader);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get(route('feed'))->assertOk();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThan(self::MESURE_AVANT, $count);
        $this->assertLessThanOrEqual(self::MESURE_APRES, $count, "/feed (client) fait {$count} requêtes.");
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
