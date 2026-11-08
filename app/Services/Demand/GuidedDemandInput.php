<?php

namespace App\Services\Demand;

use App\Support\ServiceSituationCatalog;
use App\Support\TerritoryResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Prépare une demande du parcours guidé (/demande?situation=…) avant la
 * validation habituelle de DemandController::store.
 *
 * - le métier vient de la situation (ce que le navigateur envoie est ignoré) ;
 * - les réponses sont vérifiées contre les questions de la situation ;
 * - les précisions obligatoires sont remplies par la situation, par les
 *   réponses, ou par une valeur honnête pour « Je ne sais pas » ;
 * - le titre et la description sont composés par DemandTextComposer.
 *
 * Les règles existantes de store() s'appliquent ensuite sans changement.
 */
class GuidedDemandInput
{
    public const WHEN = ['today', 'this_week', 'this_month', 'no_rush', 'date'];

    public const TIME_WINDOWS = ['flexible', 'morning', 'afternoon', 'evening'];

    public function __construct(
        private ServiceSituationCatalog $catalog,
        private DemandTextComposer $composer,
        private TerritoryResolver $territories,
    ) {}

    /**
     * @return array{situation: array<string, mixed>, fields: array<string, mixed>, ad_details_extra: array<string, mixed>}
     *
     * @throws ValidationException
     */
    public function prepare(Request $request): array
    {
        $territory = $this->territories->forUser(
            $request->user(),
            (array) ($request->attributes->get('user_geo') ?? [])
        );
        $situation = $this->catalog->find((string) $request->input('situation_key'), $territory);

        if ($situation === null) {
            throw ValidationException::withMessages([
                'situation_key' => 'Ce besoin n’est plus proposé. Choisissez un autre besoin.',
            ]);
        }

        $questions = $this->catalog->questionsFor($situation);
        $validated = $this->validate($request, $questions);
        $answers = $this->answers($questions, $validated['answers'] ?? []);

        $city = DemandTextComposer::normalize($request->input('city'));
        if ($city === '' || $city === '__other__') {
            $city = DemandTextComposer::normalize($request->input('city_typed'));
        }

        $when = $validated['when'];
        $timeWindow = $validated['time_window'] ?? 'flexible';
        $desiredDate = $when === 'date'
            ? (string) $validated['desired_date_choice']
            : today()->toDateString();
        $detail = DemandTextComposer::normalize($validated['detail'] ?? '');

        $composed = $this->composer->compose(
            $this->catalog->composerCatalog([$situation['key']]),
            [
                'situation_key' => $situation['key'],
                'answers' => $answers,
                'city' => $city,
                'when' => $when,
                'desired_date' => $when === 'date' ? $desiredDate : null,
                'time_window' => $timeWindow,
                'price_type' => $request->input('price_type'),
                'price' => $request->input('price'),
                'detail' => $detail,
            ]
        );

        $description = $composed['description'];
        $origin = $detail !== '' ? 'composed_plus_detail' : 'composed';
        $override = DemandTextComposer::normalize($validated['description_override'] ?? '');
        if ($override !== '' && $override !== $composed['description']) {
            $description = $override;
            $origin = 'edited';
        }

        return [
            'situation' => $situation,
            'fields' => [
                'main_category' => $situation['main_category'],
                'category' => $situation['subcategory'],
                'service_details' => $this->serviceDetails($situation, $questions, $answers),
                'desired_date' => $desiredDate,
                'time_window' => $timeWindow,
                'title' => $composed['title'],
                'description' => $description,
                'city' => $city,
                'location' => $city,
            ],
            'ad_details_extra' => [
                'answers' => $answers,
                'when' => $when,
                'composer_version' => DemandTextComposer::VERSION,
                'text_origin' => $origin,
                'author_detail' => $detail !== '' ? $detail : null,
            ],
        ];
    }

    /** @throws ValidationException */
    private function validate(Request $request, array $questions): array
    {
        $invalidAnswer = 'Une réponse n’est pas reconnue. Choisissez une réponse proposée.';

        return Validator::make($request->all(), [
            'answers' => [
                'nullable',
                'array',
                function (string $attribute, mixed $value, \Closure $fail) use ($questions, $invalidAnswer): void {
                    foreach ((array) $value as $questionKey => $answer) {
                        $question = $questions[$questionKey] ?? null;
                        $allowed = $question ? [...array_map('strval', array_keys($question['options'])), 'unknown'] : [];

                        if ($question === null || ! is_string($answer) || ! in_array($answer, $allowed, true)) {
                            $fail($invalidAnswer);

                            return;
                        }
                    }
                },
            ],
            'when' => ['required', 'string', 'in:'.implode(',', self::WHEN)],
            'desired_date_choice' => ['nullable', 'required_if:when,date', 'date_format:Y-m-d', 'after_or_equal:today'],
            'time_window' => ['nullable', 'string', 'in:'.implode(',', self::TIME_WINDOWS)],
            'city_typed' => ['nullable', 'string', 'max:120'],
            'detail' => ['nullable', 'string', 'max:'.DemandTextComposer::DETAIL_MAX],
            'description_override' => ['nullable', 'string', 'max:2000'],
        ], [
            'when.required' => 'Choisissez quand vous avez besoin d’aide.',
            'when.in' => 'Choisissez quand vous avez besoin d’aide.',
            'desired_date_choice.required_if' => 'Choisissez une date à partir d’aujourd’hui.',
            'desired_date_choice.date_format' => 'Choisissez une date à partir d’aujourd’hui.',
            'desired_date_choice.after_or_equal' => 'Choisissez une date à partir d’aujourd’hui.',
            'time_window.in' => 'Choisissez un moment de la journée proposé.',
            'detail.max' => 'Le détail est trop long : 500 caractères au plus.',
            'description_override.max' => 'Le texte est trop long : 2 000 caractères au plus.',
        ])->validate();
    }

    /**
     * Une réponse par question de la situation : la clé choisie, ou « unknown »
     * (« Je ne sais pas » ou question laissée sans réponse).
     *
     * @return array<string, string>
     */
    private function answers(array $questions, mixed $posted): array
    {
        $posted = is_array($posted) ? $posted : [];
        $answers = [];

        foreach ($questions as $questionKey => $question) {
            $answer = $posted[$questionKey] ?? null;
            $answers[$questionKey] = is_string($answer) && array_key_exists($answer, $question['options'])
                ? $answer
                : 'unknown';
        }

        return $answers;
    }

    /**
     * Précisions : valeurs fixées par la situation, puis champs visés par les
     * réponses (« Je ne sais pas » → unknown_value), puis surcharges d'option.
     */
    private function serviceDetails(array $situation, array $questions, array $answers): array
    {
        $details = (array) ($situation['service_details'] ?? []);

        foreach ($questions as $questionKey => $question) {
            $answer = $answers[$questionKey] ?? 'unknown';
            $field = $question['maps_to'] ?? null;

            if (is_string($field) && $field !== '') {
                if ($answer !== 'unknown') {
                    $details[$field] = $answer;
                } elseif (is_string($question['unknown_value'] ?? null)) {
                    $details[$field] = $question['unknown_value'];
                }
            }

            if ($answer !== 'unknown') {
                foreach ((array) ($question['options'][$answer]['service_details'] ?? []) as $field => $value) {
                    $details[$field] = $value;
                }
            }
        }

        return $details;
    }
}
