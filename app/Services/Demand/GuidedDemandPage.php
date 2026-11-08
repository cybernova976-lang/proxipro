<?php

namespace App\Services\Demand;

use App\Support\ServiceSituationCatalog;
use App\Support\Territory;
use Illuminate\Http\Request;

/**
 * Données de la vue demands.guided : un écran par question, puis photo,
 * lieu, moment, prix et vérification. Le contrôleur reste court.
 */
class GuidedDemandPage
{
    /** Écrans fixes : situation, photo, lieu, quand, prix, vérifier. */
    public const FIXED_STEPS = 6;

    public const MAX_STEPS = 9;

    /** Au-delà, les communes sont proposées dans un champ avec suggestions. */
    public const MAX_COMMUNE_TILES = 20;

    public const STORAGE_KEY = 'prokejem.guided.v1';

    public function __construct(private ServiceSituationCatalog $catalog) {}

    /** @return array<string, mixed> */
    public function build(Request $request, array $situation, ?Territory $territory): array
    {
        $user = $request->user();
        $questions = array_slice(
            $this->catalog->questionsFor($situation),
            0,
            self::MAX_STEPS - self::FIXED_STEPS,
            true
        );

        $country = trim((string) ($user?->country ?? ''));
        if ($country === '' && $territory !== null) {
            $country = $territory->isOverseas() ? $territory->label : 'France';
        }

        $communes = $territory?->isOverseas() ? $territory->communes : [];
        if ($communes === [] && $country !== '') {
            $communes = (array) config('locations.cities.'.$country, []);
        }
        $communes = array_values(array_unique(array_filter(array_map('strval', $communes))));

        $city = trim((string) ($user?->city ?? ''));
        $loginUrl = route('login', ['continue' => 'demand', 'situation' => $situation['key']]);

        return [
            'pkSituation' => $situation,
            'pkQuestions' => $questions,
            'pkStepTotal' => count($questions) + self::FIXED_STEPS,
            'pkCommunes' => $communes,
            'pkCommuneTiles' => $communes !== [] && count($communes) <= self::MAX_COMMUNE_TILES,
            'pkPrefill' => ['city' => $city !== '' ? $city : null, 'country' => $country !== '' ? $country : null],
            'pkCountries' => array_slice(array_keys((array) config('locations.countries', [])), 0, 6),
            'pkIsGuest' => $user === null,
            'pkLoginUrl' => $loginUrl,
            'pkAssist' => $this->assist((string) $situation['label']),
            'pkGuidedConfig' => [
                'situationKey' => $situation['key'],
                'catalog' => $this->catalog->composerCatalog([$situation['key']]),
                'isGuest' => $user === null,
                'loginUrl' => $loginUrl,
                'resume' => $request->boolean('resume'),
                'storageKey' => self::STORAGE_KEY,
            ],
        ];
    }

    /**
     * Aide WhatsApp (G8) : seulement si le drapeau est actif, avec un numéro
     * valide et des horaires. Aucune donnée personnelle dans le lien : le
     * message ne contient que le libellé de la situation.
     *
     * @return array{url: string, hours: string}|null
     */
    public function assist(string $label): ?array
    {
        $settings = (array) config('demand.assist_whatsapp', []);
        $number = trim((string) ($settings['number'] ?? ''));
        $hours = trim((string) ($settings['hours'] ?? ''));

        if (! ($settings['enabled'] ?? false) || preg_match('/^\d{8,15}$/', $number) !== 1 || $hours === '') {
            return null;
        }

        $text = 'Bonjour, j’ai besoin d’aide pour publier une demande : '.$label;

        return [
            'url' => 'https://wa.me/'.$number.'?text='.rawurlencode($text),
            'hours' => $hours,
        ];
    }
}
