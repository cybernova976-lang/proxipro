<?php

namespace Tests\Feature\Demand;

use App\Support\MarketplaceCategoryRegistry;
use App\Support\ServiceSituationCatalog;
use App\Support\Territory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ServiceSituationCatalogTest extends TestCase
{
    use RefreshDatabase;

    private const PICTO_COLORS = ['#4338ca', '#e0e7ff', '#eef2ff', '#7c3aed'];

    private ServiceSituationCatalog $catalog;

    protected function setUp(): void
    {
        parent::setUp();
        $this->catalog = app(ServiceSituationCatalog::class);
    }

    public function test_the_eight_founder_situations_are_configured_in_order(): void
    {
        $this->assertSame(
            ['fuite-eau', 'wc-bouche', 'panne-electrique', 'clim', 'menage', 'jardin', 'demenager', 'telephone-casse'],
            array_keys(config('service_situations.situations'))
        );
        $this->assertCount(7, $this->catalog->tiles(null));
        $this->assertNotContains('telephone-casse', array_column($this->catalog->tiles(null), 'key'));
        $this->assertNotNull($this->catalog->find('telephone-casse'));
    }

    public function test_each_situation_targets_an_existing_trade(): void
    {
        foreach (config('service_situations.situations') as $key => $situation) {
            $subcategories = config('categories.services.'.$situation['main_category'].'.subcategories');

            $this->assertIsArray($subcategories, "Univers inconnu pour {$key}.");
            $this->assertContains($situation['subcategory'], $subcategories, "Métier inconnu pour {$key}.");
        }
    }

    public function test_situation_keys_are_unique_slugs_of_64_chars_max(): void
    {
        $keys = array_keys(config('service_situations.situations'));

        $this->assertSame($keys, array_values(array_unique($keys)));
        foreach ($keys as $key) {
            $this->assertMatchesRegularExpression(ServiceSituationCatalog::KEY_PATTERN, $key);
        }

        $this->assertNull($this->catalog->find('Fuite-Eau'));
        $this->assertNull($this->catalog->find('fuite_eau'));
        $this->assertNull($this->catalog->find(str_repeat('a', 65)));
        $this->assertNull($this->catalog->find('inconnue'));
        $this->assertNull($this->catalog->find(''));
    }

    public function test_labels_have_three_words_max(): void
    {
        foreach (config('service_situations.situations') as $key => $situation) {
            $this->assertLessThanOrEqual(3, $this->wordCount($situation['label']), "Libellé trop long pour {$key}.");
        }
    }

    public function test_disabled_category_hides_its_situations(): void
    {
        MarketplaceCategoryRegistry::storeEnabledIds(['jardinage-exterieur']);

        $this->assertSame(['jardin'], array_column($this->catalog->tiles(null), 'key'));
        $this->assertSame(['jardin'], array_column($this->catalog->searchIndex(null), 'key'));
        $this->assertNull($this->catalog->find('fuite-eau'));
        $this->assertNotNull($this->catalog->find('jardin'));
    }

    public function test_situation_restricted_to_a_territory_is_hidden_elsewhere(): void
    {
        config(['service_situations.situations.menage.territories' => ['976']]);

        $mayotte = new Territory('976', 'Mayotte', 'mayotte', 'Indian/Mayotte');
        $guyane = new Territory('973', 'Guyane', 'guyane', 'America/Cayenne');

        $this->assertNotNull($this->catalog->find('menage', $mayotte));
        $this->assertNull($this->catalog->find('menage', $guyane));
        $this->assertNull($this->catalog->find('menage'));
        $this->assertNotNull($this->catalog->find('jardin', $guyane));
    }

    public function test_every_required_intake_field_is_fixed_or_asked_with_an_honest_unknown(): void
    {
        $questions = config('demand_questions.questions');

        foreach (config('service_situations.situations') as $key => $situation) {
            $profile = config('service_demand_intake.category_profiles.'.$situation['main_category']);
            $fields = config('service_demand_intake.profiles.'.$profile.'.fields');
            $this->assertIsArray($fields, "Profil introuvable pour {$key}.");

            foreach ($fields as $field => $definition) {
                if (! ($definition['required'] ?? false)) {
                    continue;
                }

                $allowed = array_keys($definition['options']);
                $fixed = $situation['service_details'][$field] ?? null;

                if ($fixed !== null) {
                    $this->assertContains($fixed, $allowed, "{$key} fixe {$field} à une valeur inconnue.");

                    continue;
                }

                $asking = array_values(array_filter(
                    $situation['questions'],
                    fn (string $question): bool => ($questions[$question]['maps_to'] ?? null) === $field
                ));
                $this->assertNotEmpty($asking, "{$key} ne renseigne pas le champ obligatoire {$field}.");

                foreach ($asking as $question) {
                    $this->assertContains($questions[$question]['unknown_value'], $allowed, "« Je ne sais pas » de {$question} n’est pas une valeur de {$field}.");
                    foreach (array_keys($questions[$question]['options']) as $option) {
                        $this->assertContains($option, $allowed, "{$question}.{$option} n’est pas une valeur de {$field}.");
                    }
                }
            }

            // Une surcharge d'option doit elle aussi viser une valeur existante.
            foreach ($situation['questions'] as $question) {
                foreach ($questions[$question]['options'] as $optionKey => $option) {
                    foreach ($option['service_details'] ?? [] as $field => $value) {
                        $this->assertContains($value, array_keys($fields[$field]['options'] ?? []), "{$question}.{$optionKey} surcharge {$field} à tort.");
                    }
                }
            }
        }
    }

    public function test_every_question_offers_unknown_and_each_option_has_label_and_picto(): void
    {
        $questions = config('demand_questions.questions');
        $unknown = config('demand_questions.unknown');

        $this->assertSame('Je ne sais pas', $unknown['label']);
        $this->assertSame('je-ne-sais-pas', $unknown['picto']);

        foreach (config('service_situations.situations') as $key => $situation) {
            $this->assertNotEmpty($situation['questions']);
            $this->assertLessThanOrEqual(3, count($situation['questions']), "{$key} : 9 étapes au plus en tout.");

            if ($situation['title_question'] !== null) {
                $this->assertContains($situation['title_question'], $situation['questions']);
            }

            foreach ($this->catalog->questionsFor($situation) as $questionKey => $question) {
                $this->assertArrayHasKey($questionKey, $questions);
                $this->assertNotSame('', $question['label']);
                $this->assertSame('unknown', $question['unknown']['value']);
                $this->assertSame('Je ne sais pas', $question['unknown']['label']);
                $this->assertArrayNotHasKey('unknown', $question['options'], 'La clé unknown est réservée à « Je ne sais pas ».');
                $this->assertGreaterThanOrEqual(2, count($question['options']));

                foreach ($question['options'] as $optionKey => $option) {
                    $this->assertNotSame('', $option['label'] ?? '', "{$questionKey}.{$optionKey} sans libellé.");
                    $this->assertFileExists(public_path('img/pk-situations/'.$option['picto'].'.svg'));
                    $this->assertStringEndsWith('/img/pk-situations/'.$option['picto'].'.svg', $option['picto_url']);
                }
            }

            $this->assertCount(count($situation['questions']), $this->catalog->questionsFor($situation), "{$key} cite une question inconnue.");
        }
    }

    public function test_config_sentences_have_twelve_words_max(): void
    {
        $sentences = [];

        foreach (config('service_situations.situations') as $situation) {
            $sentences[] = str_replace('{fragment}', '', $situation['opening']);
            $fragments = $situation['title_question'] !== null
                ? array_column(config('demand_questions.questions.'.$situation['title_question'].'.options'), 'fragment')
                : [];
            foreach ($fragments as $fragment) {
                $sentences[] = str_replace('{fragment}', $fragment, $situation['opening']);
            }
        }

        foreach (config('demand_questions.questions') as $question) {
            foreach ($question['options'] as $option) {
                if (isset($option['sentence'])) {
                    $sentences[] = $option['sentence'];
                }
            }
        }

        $this->assertNotEmpty($sentences);
        foreach ($sentences as $sentence) {
            $this->assertLessThanOrEqual(12, $this->wordCount($sentence), "Phrase trop longue : {$sentence}");
            $this->assertStringNotContainsString("'", $sentence, "Apostrophe droite dans : {$sentence}");
        }
    }

    public function test_each_picto_follows_the_single_style(): void
    {
        $pictos = ['autre-besoin', 'je-ne-sais-pas', 'photo', 'lieu', 'quand', 'prix', 'verifier'];
        foreach (config('service_situations.situations') as $situation) {
            $pictos[] = $situation['picto'];
        }
        foreach (config('demand_questions.questions') as $question) {
            foreach ($question['options'] as $option) {
                $pictos[] = $option['picto'];
            }
        }
        $pictos = array_unique($pictos);

        $this->assertCount(62, $pictos);
        $this->assertCount(62, glob(public_path('img/pk-situations/*.svg')), 'Aucun pictogramme orphelin.');

        foreach ($pictos as $picto) {
            $path = public_path('img/pk-situations/'.$picto.'.svg');
            $this->assertFileExists($path);
            $svg = file_get_contents($path);

            $this->assertLessThan(3072, strlen($svg), "{$picto} dépasse 3 Ko.");
            $this->assertMatchesRegularExpression('/^<svg [^>]*viewBox="0 0 48 48"[^>]*>/', $svg, $picto);
            $this->assertMatchesRegularExpression('/^<svg [^>]*width="48" height="48" fill="none"/', $svg, $picto);
            $this->assertStringContainsString('stroke="#4338ca"', $svg, $picto);
            $this->assertStringContainsString('stroke-width="2"', $svg, $picto);
            $this->assertStringContainsString('stroke-linecap="round"', $svg, $picto);
            $this->assertStringContainsString('stroke-linejoin="round"', $svg, $picto);
            $this->assertSame(1, substr_count($svg, 'stroke-width='), "{$picto} : une seule épaisseur de trait.");

            preg_match_all('/#[0-9a-fA-F]{3,8}\b/', $svg, $colors);
            $this->assertEmpty(array_diff(array_map('strtolower', $colors[0]), self::PICTO_COLORS), "{$picto} sort de la palette.");

            foreach (['<script', '<image', 'href=', 'style=', '<text', '<foreignObject', 'url('] as $forbidden) {
                $this->assertStringNotContainsStringIgnoringCase($forbidden, $svg, "{$picto} contient {$forbidden}.");
            }

            preg_match_all('/<([a-zA-Z]+)[\s>\/]/', $svg, $tags);
            $this->assertEmpty(
                array_diff(array_unique($tags[1]), ['svg', 'path', 'circle', 'rect', 'line', 'polyline']),
                "{$picto} utilise une forme non prévue."
            );

            $xml = simplexml_load_string($svg);
            $this->assertNotFalse($xml, "{$picto} n’est pas un SVG valide.");
        }
    }

    public function test_search_index_contains_normalized_labels_and_synonyms(): void
    {
        $index = collect($this->catalog->searchIndex(null))->keyBy('key');

        $this->assertCount(8, $index);
        $this->assertContains('fuite d\'eau', $index['fuite-eau']['search']);
        $this->assertContains('menage', $index['menage']['search']);
        $this->assertContains('femme de menage', $index['menage']['search']);
        $this->assertContains('telephone casse', $index['telephone-casse']['search']);
        $this->assertContains('portable', $index['telephone-casse']['search']);
        $this->assertContains('toilettes', $index['wc-bouche']['search']);

        foreach ($index as $key => $entry) {
            $this->assertSame(route('demand.create', ['situation' => $key]), $entry['url']);
            $this->assertStringEndsWith('/img/pk-situations/'.$key.'.svg', $entry['picto_url']);

            foreach ($entry['search'] as $term) {
                $this->assertSame(ServiceSituationCatalog::normalize($term), $term);
                $this->assertMatchesRegularExpression('/^[a-z0-9 \'\-]+$/', $term, "Terme non normalisé : {$term}");
            }
        }
    }

    public function test_ads_table_has_an_indexed_situation_key(): void
    {
        $this->assertTrue(Schema::hasColumn('ads', 'situation_key'));

        $indexed = collect(Schema::getIndexes('ads'))
            ->contains(fn (array $index): bool => $index['columns'] === ['situation_key']);

        $this->assertTrue($indexed, 'La colonne ads.situation_key doit être indexée.');
    }

    private function wordCount(string $text): int
    {
        return count(preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY));
    }
}
