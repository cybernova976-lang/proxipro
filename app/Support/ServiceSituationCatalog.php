<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Catalogue des situations de départ du parcours guidé (config/service_situations.php)
 * et de leurs questions (config/demand_questions.php).
 *
 * Une situation n'est proposée que si l'univers de son métier est actif
 * (MarketplaceCategoryRegistry), si le métier existe encore dans cet univers
 * et si le territoire fait partie de ses territoires (null = tous).
 *
 * Pas de cache : l'ordre est éditorial (aucune mesure n'existe encore) et
 * enabledServices() lit déjà le réglage d'administration.
 */
class ServiceSituationCatalog
{
    public const KEY_PATTERN = '/^[a-z0-9-]{1,64}$/';

    /**
     * Toutes les situations configurées, enrichies, sans filtrage.
     *
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        $situations = [];

        foreach ((array) config('service_situations.situations', []) as $key => $situation) {
            if (! is_string($key) || ! is_array($situation)) {
                continue;
            }

            $situations[$key] = $this->enrich($key, $situation);
        }

        return $situations;
    }

    /** Situation disponible pour ce territoire, ou null (clé invalide, inconnue ou univers désactivé). */
    public function find(string $key, ?Territory $territory = null): ?array
    {
        if (preg_match(self::KEY_PATTERN, $key) !== 1) {
            return null;
        }

        return $this->available($territory)[$key] ?? null;
    }

    /**
     * Situations affichées en tuiles, dans l'ordre de la configuration.
     *
     * @return list<array<string, mixed>>
     */
    public function tiles(?Territory $territory): array
    {
        return array_values(array_filter(
            $this->available($territory),
            fn (array $situation): bool => (bool) ($situation['tile'] ?? false)
        ));
    }

    /**
     * Index de recherche : toutes les situations disponibles, tuiles ou non.
     *
     * @return list<array{key: string, label: string, url: string, picto_url: string, search: list<string>}>
     */
    public function searchIndex(?Territory $territory): array
    {
        return array_values(array_map(
            fn (array $situation): array => [
                'key' => $situation['key'],
                'label' => $situation['label'],
                'url' => $situation['url'],
                'picto_url' => $situation['picto_url'],
                'search' => $situation['search'],
            ],
            $this->available($territory)
        ));
    }

    /**
     * Questions d'une situation, dans l'ordre des écrans, avec « Je ne sais pas ».
     *
     * @return array<string, array<string, mixed>>
     */
    public function questionsFor(array $situation): array
    {
        $bank = (array) config('demand_questions.questions', []);
        $unknown = (array) config('demand_questions.unknown', []);
        $questions = [];

        foreach ((array) ($situation['questions'] ?? []) as $questionKey) {
            $question = $bank[$questionKey] ?? null;

            if (! is_array($question)) {
                continue;
            }

            $options = [];
            foreach ((array) ($question['options'] ?? []) as $optionKey => $option) {
                $options[$optionKey] = $option + ['picto_url' => $this->pictoUrl((string) ($option['picto'] ?? ''))];
            }

            $questions[$questionKey] = [
                'key' => $questionKey,
                'label' => $question['label'] ?? '',
                'maps_to' => $question['maps_to'] ?? null,
                'unknown_value' => $question['unknown_value'] ?? null,
                'options' => $options,
                'unknown' => [
                    'value' => 'unknown',
                    'label' => $unknown['label'] ?? 'Je ne sais pas',
                    'picto' => $unknown['picto'] ?? 'je-ne-sais-pas',
                    'picto_url' => $this->pictoUrl((string) ($unknown['picto'] ?? 'je-ne-sais-pas')),
                ],
            ];
        }

        return $questions;
    }

    /**
     * Données nécessaires au compositeur de texte (PHP et JS), et rien d'autre.
     * Les clés inconnues sont ignorées. Aucun filtrage par univers : l'appelant
     * passe des clés déjà retenues par find().
     *
     * @param  array<int, string>  $keys
     * @return array{version: int, situations: array<string, array<string, mixed>>, questions: array<string, array<string, mixed>>}
     */
    public function composerCatalog(array $keys): array
    {
        $configured = (array) config('service_situations.situations', []);
        $bank = (array) config('demand_questions.questions', []);
        $situations = [];
        $questions = [];

        foreach ($keys as $key) {
            $situation = is_string($key) ? ($configured[$key] ?? null) : null;

            if (! is_array($situation)) {
                continue;
            }

            $questionKeys = array_values(array_filter(
                (array) ($situation['questions'] ?? []),
                fn ($questionKey): bool => is_string($questionKey) && is_array($bank[$questionKey] ?? null)
            ));

            $situations[$key] = [
                'title' => (string) ($situation['title'] ?? ''),
                'title_question' => $situation['title_question'] ?? null,
                'opening' => (string) ($situation['opening'] ?? ''),
                'questions' => $questionKeys,
            ];

            foreach ($questionKeys as $questionKey) {
                if (isset($questions[$questionKey])) {
                    continue;
                }

                $options = [];
                foreach ((array) ($bank[$questionKey]['options'] ?? []) as $optionKey => $option) {
                    $options[(string) $optionKey] = array_filter([
                        'title_part' => $option['title_part'] ?? null,
                        'fragment' => $option['fragment'] ?? null,
                        'sentence' => $option['sentence'] ?? null,
                    ], fn ($value): bool => is_string($value) && $value !== '');
                }

                $questions[$questionKey] = ['options' => $options];
            }
        }

        ksort($questions);

        return [
            'version' => 1,
            'situations' => $situations,
            'questions' => $questions,
        ];
    }

    /** Normalisation de recherche : minuscules, sans accents, espaces simples. */
    public static function normalize(string $text): string
    {
        $text = Str::lower(Str::ascii($text));

        return trim((string) preg_replace('/\s+/', ' ', $text));
    }

    /** @return array<string, array<string, mixed>> */
    private function available(?Territory $territory): array
    {
        $enabled = MarketplaceCategoryRegistry::enabledServices();

        return array_filter($this->all(), function (array $situation) use ($enabled, $territory): bool {
            $universe = $enabled[$situation['main_category'] ?? ''] ?? null;

            if (! is_array($universe) || ! in_array($situation['subcategory'] ?? null, (array) ($universe['subcategories'] ?? []), true)) {
                return false;
            }

            $territories = $situation['territories'] ?? null;

            return $territories === null
                || ($territory !== null && in_array($territory->code, (array) $territories, true));
        });
    }

    private function enrich(string $key, array $situation): array
    {
        $terms = [self::normalize((string) ($situation['label'] ?? ''))];
        foreach ((array) ($situation['synonyms'] ?? []) as $synonym) {
            $terms[] = self::normalize((string) $synonym);
        }

        return array_merge($situation, [
            'key' => $key,
            'url' => route('demand.create', ['situation' => $key]),
            'picto_url' => $this->pictoUrl((string) ($situation['picto'] ?? $key)),
            'search' => array_values(array_unique(array_filter($terms, fn (string $term): bool => $term !== ''))),
        ]);
    }

    private function pictoUrl(string $picto): string
    {
        return asset('img/pk-situations/'.$picto.'.svg');
    }
}
