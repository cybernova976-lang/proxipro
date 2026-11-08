<?php

namespace Tests\Unit\Demand;

use App\Services\Demand\DemandTextComposer;
use App\Support\ServiceSituationCatalog;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Le compositeur PHP et public/js/demand-text-composer.js lisent la même
 * fixture (tests/Browser/demand-text-composer.test.mjs) : la sortie doit être identique.
 */
class DemandTextComposerTest extends TestCase
{
    private const FIXTURE = __DIR__.'/../../fixtures/demand-text-cases.json';

    private static function fixture(): array
    {
        return json_decode(file_get_contents(self::FIXTURE), true, 512, JSON_THROW_ON_ERROR);
    }

    public static function fixtureCases(): array
    {
        $cases = [];
        foreach (self::fixture()['cases'] as $case) {
            $cases[$case['name']] = [$case['input'], $case['expected']];
        }

        return $cases;
    }

    #[DataProvider('fixtureCases')]
    public function test_fixture_cases_produce_the_expected_text(array $input, array $expected): void
    {
        $result = (new DemandTextComposer)->compose(self::fixture()['catalog'], $input);

        $this->assertSame($expected['title'], $result['title']);
        $this->assertSame($expected['description'], $result['description']);
        $this->assertSame(1, $result['version']);
    }

    public function test_fixture_has_at_least_fourteen_cases(): void
    {
        $this->assertGreaterThanOrEqual(14, count(self::fixture()['cases']));
    }

    public function test_reference_example_of_the_specification(): void
    {
        // Exemple écrit à la main dans la spécification, indépendant de la fixture.
        $result = (new DemandTextComposer)->compose($this->catalog(), [
            'situation_key' => 'fuite-eau',
            'answers' => ['fuite_lieu' => 'cuisine', 'fuite_force' => 'beaucoup', 'logement' => 'unknown'],
            'city' => 'Koungou',
            'when' => 'today',
            'time_window' => 'morning',
            'price_type' => 'negotiable',
        ]);

        $this->assertSame('Fuite d’eau dans la cuisine à Koungou', $result['title']);
        $this->assertSame(
            'Bonjour. J’ai une fuite d’eau dans la cuisine. L’eau coule beaucoup. J’ai besoin d’aide aujourd’hui, le matin si possible. Le prix est à discuter.',
            $result['description']
        );
        $this->assertSame([
            'J’ai une fuite d’eau dans la cuisine.',
            'L’eau coule beaucoup.',
            'J’ai besoin d’aide aujourd’hui, le matin si possible.',
            'Le prix est à discuter.',
        ], $result['sentences']);
    }

    public function test_unknown_answers_produce_no_sentence(): void
    {
        $catalog = $this->catalog();
        $composer = new DemandTextComposer;

        foreach ($catalog['situations'] as $key => $situation) {
            $unknown = array_fill_keys($situation['questions'], 'unknown');
            $result = $composer->compose($catalog, ['situation_key' => $key, 'answers' => $unknown]);
            $absent = $composer->compose($catalog, ['situation_key' => $key, 'answers' => []]);

            $this->assertSame([str_replace('{fragment}', '', $situation['opening'])], $result['sentences'], $key);
            $this->assertSame($result, $absent, "{$key} : une réponse absente vaut « Je ne sais pas ».");
            $this->assertSame($situation['title'], $result['title'], "{$key} : pas de complément de titre.");
            $this->assertSame('Bonjour. '.$result['sentences'][0], $result['description']);
        }
    }

    public function test_fixture_catalog_matches_the_current_configuration(): void
    {
        $this->assertSame(self::fixture()['catalog'], $this->catalog());
    }

    public function test_detail_is_trimmed_squished_and_cut_at_500_characters(): void
    {
        $composer = new DemandTextComposer;
        $base = ['situation_key' => 'menage', 'answers' => []];
        $prefix = 'Bonjour. Je cherche une aide pour le ménage. ';

        $squished = $composer->compose($this->catalog(), $base + ['detail' => "  Deux   chambres\n\n et\u{00A0}\u{00A0}un salon.\t "]);
        $this->assertSame($prefix.'Deux chambres et un salon.', $squished['description']);

        $long = $composer->compose($this->catalog(), $base + ['detail' => str_repeat('é', 600)]);
        $this->assertSame($prefix.str_repeat('é', 500), $long['description']);

        $emoji = $composer->compose($this->catalog(), $base + ['detail' => str_repeat('a', 499).'🙂🙂']);
        $this->assertSame($prefix.str_repeat('a', 499).'🙂', $emoji['description']);
        $this->assertTrue(mb_check_encoding($emoji['description'], 'UTF-8'));

        $blank = $composer->compose($this->catalog(), $base + ['detail' => " \n\t "]);
        $this->assertSame('Bonjour. Je cherche une aide pour le ménage.', $blank['description']);
    }

    public function test_price_without_amount_and_invalid_inputs_produce_no_sentence(): void
    {
        $composer = new DemandTextComposer;
        $base = ['situation_key' => 'demenager', 'answers' => [], 'price_type' => 'fixed'];

        foreach (['', '0', 'abc', '12.345', '-5', null] as $price) {
            $result = $composer->compose($this->catalog(), $base + ['price' => $price]);
            $this->assertSame(['Je dois déménager.'], $result['sentences'], var_export($price, true));
        }

        $this->assertContains('Je propose 1200 €.', $composer->compose($this->catalog(), $base + ['price' => '1200'])['sentences']);
        $this->assertContains('Je propose 12,05 €.', $composer->compose($this->catalog(), $base + ['price' => '12.05'])['sentences']);
        $this->assertContains('Je propose 30 €.', $composer->compose($this->catalog(), $base + ['price' => '30,00'])['sentences']);

        $invalidDate = $composer->compose($this->catalog(), ['situation_key' => 'demenager', 'when' => 'date', 'desired_date' => '2026-02-30']);
        $this->assertSame(['Je dois déménager.'], $invalidDate['sentences']);

        $unknownSituation = $composer->compose($this->catalog(), ['situation_key' => 'inconnue']);
        $this->assertSame(['title' => '', 'description' => '', 'sentences' => [], 'version' => 1], $unknownSituation);
    }

    private function catalog(): array
    {
        return app(ServiceSituationCatalog::class)
            ->composerCatalog(array_keys(config('service_situations.situations')));
    }
}
