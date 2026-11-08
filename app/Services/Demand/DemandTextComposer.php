<?php

namespace App\Services\Demand;

/**
 * Compose le titre et la description d'une demande à partir des réponses
 * du parcours guidé. Fonction pure et déterministe, versionnée.
 *
 * public/js/demand-text-composer.js applique exactement les mêmes règles
 * (aperçu dans le navigateur). Les deux sont vérifiés sur la même fixture :
 * tests/fixtures/demand-text-cases.json. Toute modification se fait des deux côtés.
 *
 * Aucune date n'est formatée par Carbon ni par Intl : le tableau des mois
 * ci-dessous garantit la même sortie qu'en JS.
 */
class DemandTextComposer
{
    public const VERSION = 1;

    public const DETAIL_MAX = 500;

    public const TITLE_MAX = 255;

    private const MONTHS = [
        1 => 'janvier', 2 => 'février', 3 => 'mars', 4 => 'avril', 5 => 'mai', 6 => 'juin',
        7 => 'juillet', 8 => 'août', 9 => 'septembre', 10 => 'octobre', 11 => 'novembre', 12 => 'décembre',
    ];

    private const WHEN = [
        'today' => 'J’ai besoin d’aide aujourd’hui',
        'this_week' => 'J’ai besoin d’aide cette semaine',
        'this_month' => 'J’ai besoin d’aide ce mois-ci',
        'no_rush' => 'Ce n’est pas pressé',
    ];

    private const TIME_WINDOW = [
        'morning' => ', le matin si possible',
        'afternoon' => ', l’après-midi si possible',
        'evening' => ', le soir si possible',
        'flexible' => '',
    ];

    // Mêmes caractères que la classe \s de JavaScript, pour une normalisation identique.
    private const SPACES = '/[\t\n\x{0B}\f\r \x{00A0}\x{1680}\x{2000}-\x{200A}\x{2028}\x{2029}\x{202F}\x{205F}\x{3000}\x{FEFF}]+/u';

    /**
     * @param  array{situations?: array<string, array<string, mixed>>, questions?: array<string, array<string, mixed>>}  $catalog
     * @param  array<string, mixed>  $input
     * @return array{title: string, description: string, sentences: list<string>, version: int}
     */
    public function compose(array $catalog, array $input): array
    {
        $situationKey = is_string($input['situation_key'] ?? null) ? $input['situation_key'] : '';
        $situation = $catalog['situations'][$situationKey] ?? null;

        if (! is_array($situation)) {
            return ['title' => '', 'description' => '', 'sentences' => [], 'version' => self::VERSION];
        }

        $answers = is_array($input['answers'] ?? null) ? $input['answers'] : [];
        $questions = is_array($catalog['questions'] ?? null) ? $catalog['questions'] : [];
        $city = self::normalize($input['city'] ?? '');
        $detail = self::normalize($input['detail'] ?? '');
        $detail = rtrim(mb_substr($detail, 0, self::DETAIL_MAX), ' ');

        $titleOption = $this->chosenOption($questions, $situation['title_question'] ?? null, $answers);

        // Titre.
        $title = (string) ($situation['title'] ?? '').(string) ($titleOption['title_part'] ?? '');
        if ($city !== '') {
            $title .= ' à '.$city;
        }
        $title = mb_substr(self::normalize($title), 0, self::TITLE_MAX);

        // Phrases.
        $sentences = [];
        $opening = str_replace('{fragment}', (string) ($titleOption['fragment'] ?? ''), (string) ($situation['opening'] ?? ''));
        if (self::normalize($opening) !== '') {
            $sentences[] = self::normalize($opening);
        }

        foreach ((array) ($situation['questions'] ?? []) as $questionKey) {
            $option = $this->chosenOption($questions, $questionKey, $answers);
            $sentence = self::normalize($option['sentence'] ?? '');
            if ($sentence !== '') {
                $sentences[] = $sentence;
            }
        }

        $whenSentence = $this->whenSentence($input);
        if ($whenSentence !== null) {
            $sentences[] = $whenSentence;
        }

        $priceSentence = $this->priceSentence($input);
        if ($priceSentence !== null) {
            $sentences[] = $priceSentence;
        }

        $parts = array_merge(['Bonjour.'], $sentences);
        if ($detail !== '') {
            $parts[] = $detail;
        }

        return [
            'title' => $title,
            'description' => implode(' ', $parts),
            'sentences' => $sentences,
            'version' => self::VERSION,
        ];
    }

    /** trim et espaces multiples réduits à une seule espace. */
    public static function normalize(mixed $value): string
    {
        if (! is_string($value) && ! is_int($value) && ! is_float($value)) {
            return '';
        }

        return trim((string) preg_replace(self::SPACES, ' ', (string) $value), ' ');
    }

    /** Option choisie pour une question ; null pour « Je ne sais pas », une absence ou une réponse hors des options. */
    private function chosenOption(array $questions, mixed $questionKey, array $answers): ?array
    {
        if (! is_string($questionKey) || $questionKey === '') {
            return null;
        }

        $answer = $answers[$questionKey] ?? null;
        if (! is_string($answer) || $answer === '' || $answer === 'unknown') {
            return null;
        }

        $option = $questions[$questionKey]['options'][$answer] ?? null;

        return is_array($option) ? $option : null;
    }

    private function whenSentence(array $input): ?string
    {
        $when = is_string($input['when'] ?? null) ? $input['when'] : '';

        if ($when === 'date') {
            $date = is_string($input['desired_date'] ?? null) ? $input['desired_date'] : '';
            if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $match) !== 1
                || ! checkdate((int) $match[2], (int) $match[3], (int) $match[1])) {
                return null;
            }
            $base = 'J’ai besoin d’aide le '.(int) $match[3].' '.self::MONTHS[(int) $match[2]];
        } elseif (isset(self::WHEN[$when])) {
            $base = self::WHEN[$when];
        } else {
            return null;
        }

        $window = is_string($input['time_window'] ?? null) ? $input['time_window'] : '';

        return $base.(self::TIME_WINDOW[$window] ?? '').'.';
    }

    private function priceSentence(array $input): ?string
    {
        $type = $input['price_type'] ?? null;

        if ($type === 'negotiable') {
            return 'Le prix est à discuter.';
        }

        if ($type !== 'fixed') {
            return null;
        }

        // Lecture du montant sur sa forme écrite (et non en flottant), pour que PHP
        // et JS produisent le même texte : 2 décimales au plus, point ou virgule.
        $raw = $input['price'] ?? null;
        if (! is_string($raw) && ! is_int($raw) && ! is_float($raw)) {
            return null;
        }

        if (preg_match('/^(\d+)(?:[.,](\d{1,2}))?$/', self::normalize($raw), $match) !== 1) {
            return null;
        }

        $units = ltrim($match[1], '0');
        $units = $units === '' ? '0' : $units;
        $cents = str_pad($match[2] ?? '', 2, '0');

        if ($units === '0' && $cents === '00') {
            return null;
        }

        $formatted = $cents === '00' ? $units : $units.','.$cents;

        return 'Je propose '.$formatted.' €.';
    }
}
