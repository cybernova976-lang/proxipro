<?php

namespace Tests\Unit;

use App\Models\Ad;
use App\Models\User;
use App\Support\Territory;
use App\Support\TerritoryResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TerritoryResolverTest extends TestCase
{
    use RefreshDatabase;

    private TerritoryResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = app(TerritoryResolver::class);
    }

    public function test_overseas_postal_codes_resolve_to_their_territory(): void
    {
        $this->assertSame('976', $this->resolver->fromParts('97600', null, null)?->code);
        $this->assertSame('973', $this->resolver->fromParts('97300', null, null)?->code);
        $this->assertSame('974', $this->resolver->fromParts(' 97400 ', 'France', 'Paris')?->code);
        // Le code postal prime sur un pays contradictoire.
        $this->assertSame('971', $this->resolver->fromParts('97110', 'Martinique', null)?->code);
    }

    public function test_overseas_country_labels_resolve_even_without_postal_code(): void
    {
        $mayotte = $this->resolver->fromParts(null, 'Mayotte', null);
        $this->assertSame('976', $mayotte?->code);
        $this->assertSame('Mayotte', $mayotte->label);
        $this->assertSame('mayotte', $mayotte->slug);
        $this->assertSame('Indian/Mayotte', $mayotte->timezone);
        $this->assertSame('976D', $mayotte->ftLieux);
        $this->assertContains('Mamoudzou', $mayotte->communes);
        $this->assertTrue($mayotte->isOverseas());
        $this->assertSame('974', $this->resolver->fromParts(null, 'la reunion', null)?->code);
        $this->assertSame('974', $this->resolver->fromParts(null, 'Réunion', null)?->code);
        $this->assertSame('973', $this->resolver->fromParts(null, ' GUYANE ', null)?->code);
    }

    public function test_known_overseas_commune_with_mainland_or_empty_country_resolves(): void
    {
        $this->assertSame('976', $this->resolver->fromParts(null, 'France', 'Mamoudzou')?->code);
        $this->assertSame('976', $this->resolver->fromParts('', '', 'mamoudzou')?->code);
        $this->assertSame('976', $this->resolver->fromParts(null, null, null, 'Boueni')?->code);
        $this->assertSame('973', $this->resolver->fromParts(null, 'France', 'Cayenne')?->code);
        // Commune homonyme de l'Hexagone ou d'un autre DROM : aucune conclusion.
        $this->assertNull($this->resolver->fromParts(null, 'France', 'Saint-Denis'));
        $this->assertNull($this->resolver->fromParts(null, null, 'Sainte-Marie'));
        // Un pays étranger ne se rattache jamais à une commune d'outre-mer.
        $this->assertNull($this->resolver->fromParts(null, 'Comores', 'Mamoudzou'));
    }

    public function test_mainland_departments_come_from_the_postal_code(): void
    {
        $paris = $this->resolver->fromParts('75011', 'France', 'Paris');
        $this->assertSame('75', $paris?->code);
        $this->assertSame('Département 75', $paris->label);
        $this->assertSame('75D', $paris->ftLieux);
        $this->assertTrue($paris->isDepartment());
        $this->assertSame('dans le département 75', $paris->withPreposition());
        $this->assertSame('2A', $this->resolver->fromParts('20090', null, 'Ajaccio')?->code);
        $this->assertSame('2B', $this->resolver->fromParts('20200', 'France', 'Bastia')?->code);
        $this->assertSame('2AD', $this->resolver->fromParts('20000', null, null)?->ftLieux);
        $this->assertSame('01', $this->resolver->fromParts('01000', null, null)?->code);
        // Un code postal valide prime sur un nom de commune d'outre-mer homonyme.
        $this->assertSame('68', $this->resolver->fromParts('68300', 'France', 'Saint-Louis')?->code);
    }

    public function test_unknown_or_foreign_locations_resolve_to_null(): void
    {
        $this->assertNull($this->resolver->fromParts(null, null, null));
        $this->assertNull($this->resolver->fromParts('', '', ''));
        $this->assertNull($this->resolver->fromParts(null, 'France', 'Paris'));
        $this->assertNull($this->resolver->fromParts('97500', null, null));
        $this->assertNull($this->resolver->fromParts('98000', 'Monaco', 'Monaco'));
        $this->assertNull($this->resolver->fromParts('10115', 'Allemagne', 'Berlin'));
        $this->assertNull($this->resolver->fromParts('7501', 'France', null));
        $this->assertNull($this->resolver->fromParts(null, 'Belgique', 'Bruxelles'));
    }

    public function test_users_prefer_declared_location_then_detected_location_and_ignore_default_positions(): void
    {
        $declared = new User(['postal_code' => '75011', 'city' => 'Paris', 'country' => 'France',
            'detected_city' => 'Mamoudzou', 'detected_country' => 'Mayotte']);
        $this->assertSame('75', $this->resolver->forUser($declared)?->code);

        $detectedOnly = new User(['detected_city' => 'Cayenne', 'detected_country' => 'Guyane française']);
        $this->assertSame('973', $this->resolver->forUser($detectedOnly)?->code);

        $foreign = new User(['city' => 'Bruxelles', 'country' => 'Belgique', 'detected_country' => 'Mayotte']);
        $this->assertNull($this->resolver->forUser($foreign));

        $empty = new User;
        $this->assertNull($this->resolver->forUser($empty));
        $this->assertNull($this->resolver->forUser($empty, ['city' => 'Mamoudzou', 'country' => 'Mayotte', 'source' => 'default']));
        $this->assertSame('976', $this->resolver->forUser($empty, ['city' => 'Mamoudzou', 'country' => 'Mayotte', 'source' => 'ip_detection'])?->code);
        $this->assertNull($this->resolver->forUser(null));
    }

    public function test_legacy_default_position_without_geo_source_is_unknown(): void
    {
        // Position par défaut posée par l'ancien IpGeolocationService, sans marquage geo_source.
        $legacy = new User(['latitude' => -12.8275, 'longitude' => 45.1662,
            'detected_city' => 'Mamoudzou', 'detected_country' => 'Mayotte']);
        $this->assertNull($legacy->geo_source);
        $this->assertNull($this->resolver->forUser($legacy));
        $this->assertTrue($this->resolver->hasDefaultPosition($legacy));

        // La géolocalisation du navigateur est une vraie mesure, même à Mamoudzou.
        $browser = new User(['latitude' => -12.8275, 'longitude' => 45.1662, 'geo_source' => 'browser',
            'detected_city' => 'Mamoudzou', 'detected_country' => 'Mayotte']);
        $this->assertSame('976', $this->resolver->forUser($browser)?->code);

        // Des coordonnées réelles à Mamoudzou, sans marquage, restent retenues.
        $measured = new User(['latitude' => -12.7806, 'longitude' => 45.2278,
            'detected_city' => 'Mamoudzou', 'detected_country' => 'Mayotte']);
        $this->assertSame('976', $this->resolver->forUser($measured)?->code);
    }

    public function test_ads_use_postal_code_country_city_and_location(): void
    {
        $this->assertSame('976', $this->resolver->forAd(new Ad(['country' => 'France', 'city' => 'Mamoudzou']))?->code);
        $this->assertSame('976', $this->resolver->forAd(new Ad(['country' => 'France', 'location' => 'Koungou']))?->code);
        $this->assertSame('75', $this->resolver->forAd(new Ad(['postal_code' => '75011', 'country' => 'France', 'city' => 'Paris']))?->code);
        $this->assertNull($this->resolver->forAd(new Ad(['country' => 'France', 'city' => 'Paris'])));
    }

    public function test_slugs_and_codes_find_territories(): void
    {
        $this->assertSame('974', $this->resolver->findBySlug('la-reunion')?->code);
        $this->assertSame('2B', $this->resolver->findBySlug('departement-2b')?->code);
        $this->assertNull($this->resolver->findBySlug('atlantide'));
        $this->assertSame('972', $this->resolver->find('972')?->code);
        $this->assertSame('2A', $this->resolver->find('2a')?->code);
        $this->assertNull($this->resolver->find('20'));
        $this->assertNull($this->resolver->find('99'));
        $this->assertSame(['976', '973', '974', '971', '972'], array_map(
            fn (Territory $territory) => $territory->code,
            $this->resolver->overseasTerritories()
        ));
        $this->assertSame('en Guyane', $this->resolver->find('973')->withPreposition());
        $this->assertSame('de La Réunion', $this->resolver->find('974')->withDe());
    }

    /**
     * La condition SQL et la résolution PHP doivent donner le même rattachement,
     * pour chaque territoire et chaque cas limite.
     */
    public function test_sql_scope_matches_php_resolution_for_every_case(): void
    {
        $author = User::factory()->create();
        $cases = [
            ['postal_code' => '97600', 'country' => 'France', 'city' => 'Mamoudzou'],
            ['postal_code' => null, 'country' => 'Mayotte', 'city' => 'Dzaoudzi'],
            ['postal_code' => null, 'country' => 'France', 'city' => 'Koungou'],
            ['postal_code' => null, 'country' => 'France', 'city' => null, 'location' => 'Bandrélé'],
            ['postal_code' => '', 'country' => 'France', 'city' => 'mamoudzou '],
            ['postal_code' => '97300', 'country' => 'Mayotte', 'city' => null],
            ['postal_code' => '97400', 'country' => 'France', 'city' => 'Saint-Denis'],
            ['postal_code' => null, 'country' => 'France', 'city' => 'Saint-Denis'],
            ['postal_code' => null, 'country' => 'Guyane', 'city' => 'Cayenne'],
            ['postal_code' => '75011', 'country' => 'France', 'city' => 'Paris'],
            ['postal_code' => '75011', 'country' => 'Mayotte', 'city' => 'Paris'],
            ['postal_code' => '20090', 'country' => 'France', 'city' => 'Ajaccio'],
            ['postal_code' => '20200', 'country' => 'France', 'city' => 'Bastia'],
            ['postal_code' => '68300', 'country' => 'France', 'city' => 'Saint-Louis'],
            ['postal_code' => '10115', 'country' => 'Allemagne', 'city' => 'Berlin'],
            ['postal_code' => null, 'country' => 'Comores', 'city' => 'Mamoudzou'],
            ['postal_code' => null, 'country' => 'France', 'city' => 'Paris'],
            ['postal_code' => null, 'country' => 'France', 'city' => 'Paris', 'location' => 'Mamoudzou'],
        ];
        $ads = collect($cases)->map(fn (array $case) => Ad::create(array_merge([
            'user_id' => $author->id, 'title' => 'Annonce de test', 'description' => 'Description de test.',
            'category' => 'Plombier', 'location' => $case['city'] ?? '', 'service_type' => 'offre', 'status' => 'active',
        ], $case)));

        $territories = [...$this->resolver->overseasTerritories(), $this->resolver->find('75'),
            $this->resolver->find('2A'), $this->resolver->find('2B'), $this->resolver->find('68')];
        foreach ($territories as $territory) {
            $expected = $ads->filter(fn (Ad $ad) => $territory->is($this->resolver->forAd($ad->fresh())))
                ->pluck('id')->sort()->values()->all();
            $inSql = Ad::query()->inTerritory($territory)->pluck('id')->sort()->values()->all();
            $this->assertSame($expected, $inSql, 'Écart SQL/PHP pour le territoire '.$territory->code);
            $outside = Ad::query()->whereNot(fn ($query) => $query->inTerritory($territory))->count();
            $this->assertSame($ads->count() - count($expected), $outside, 'La négation perd des lignes pour '.$territory->code);
        }
    }
}
