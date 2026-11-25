<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Catalogue des formules Premium.
 *
 * Deux natures de formule (clé « billing » de la configuration) :
 *  - à prélèvement (recurring) : l'abonnement mensuel, renouvelé par Stripe ;
 *  - à paiement unique (one_time) : un accès de plusieurs mois réglé une seule
 *    fois par carte, sans prélèvement ni reconduction.
 *
 * all() et active() ne rendent que les formules à prélèvement : ce sont elles
 * que les parcours d'activation proposent en abonnement. Les accès à paiement
 * unique se lisent avec oneTime() et activeOneTime(), et le catalogue entier
 * avec catalog().
 */
class ProviderSubscriptionPlans
{
    private const SETTING_KEY = 'provider_subscription_plans';

    public const BILLING_RECURRING = 'recurring';

    public const BILLING_ONE_TIME = 'one_time';

    /** Formule dont les accès à paiement unique reprennent les avantages. */
    public const REFERENCE_PLAN = 'monthly';

    /** Préfixe réservé aux accès à paiement unique : il reste lisible dans l'historique. */
    public const ONE_TIME_PREFIX = 'pass_';

    /** Montant minimal qu'un paiement en euros peut porter chez Stripe. */
    public const MINIMUM_AMOUNT = 0.5;

    /** Formules à prélèvement (abonnements), fusionnées avec le réglage de l'administration. */
    public static function all(): array
    {
        return array_filter(self::catalog(), fn (array $plan): bool => $plan['billing'] !== self::BILLING_ONE_TIME);
    }

    /** Toutes les formules, à prélèvement et à paiement unique. */
    public static function catalog(): array
    {
        $plans = self::withWeeklyBump(self::stored());

        // Un accès à paiement unique ouvre exactement les avantages de l'abonnement.
        $shared = $plans[self::REFERENCE_PLAN]['features'] ?? [];
        foreach ($plans as $key => $plan) {
            if ($plan['billing'] === self::BILLING_ONE_TIME) {
                $plans[$key]['features'] = $shared;
            }
        }

        return $plans;
    }

    /** Avantage inclus dans l'abonnement à partir des nouvelles règles de mise en avant. */
    public const WEEKLY_BUMP_FEATURE = 'Vos offres remontent automatiquement chaque semaine, sans achat';

    /**
     * La remontée hebdomadaire des offres des abonnés (App\Services\AdBumpService)
     * ne s'applique qu'avec les nouvelles règles : elle n'est annoncée qu'à ce moment-là.
     */
    private static function withWeeklyBump(array $plans): array
    {
        if (! AdPromotionCatalog::newRulesActive()) {
            return $plans;
        }

        foreach ($plans as $key => $plan) {
            if ($plan['billing'] === self::BILLING_ONE_TIME) {
                continue;
            }
            $features = array_values((array) ($plan['features'] ?? []));
            if (! in_array(self::WEEKLY_BUMP_FEATURE, $features, true)) {
                array_splice($features, min(2, count($features)), 0, [self::WEEKLY_BUMP_FEATURE]);
            }
            $plans[$key]['features'] = $features;
        }

        return $plans;
    }

    /** Formules à prélèvement en vente. */
    public static function active(): array
    {
        return array_filter(self::all(), fn (array $plan) => (bool) ($plan['enabled'] ?? true));
    }

    /** Accès à paiement unique, en vente ou non. */
    public static function oneTime(): array
    {
        return array_filter(self::catalog(), fn (array $plan): bool => $plan['billing'] === self::BILLING_ONE_TIME);
    }

    /** Accès à paiement unique en vente : activés, et d'un montant que Stripe accepte. */
    public static function activeOneTime(): array
    {
        return array_filter(self::oneTime(), fn (array $plan): bool => ! empty($plan['enabled'])
            && (float) ($plan['amount'] ?? 0) >= self::MINIMUM_AMOUNT);
    }

    /** Une formule, quelle que soit sa nature. */
    public static function get(string $plan): ?array
    {
        return self::catalog()[$plan] ?? null;
    }

    /**
     * Accès à paiement unique ? La nature se lit dans la configuration, jamais dans
     * le réglage enregistré ; une clé retirée du catalogue reste reconnue à son préfixe.
     */
    public static function isOneTime(?string $plan): bool
    {
        if ($plan === null || $plan === '') {
            return false;
        }

        $config = self::defaults()[$plan] ?? null;

        return is_array($config)
            ? ($config['billing'] ?? self::BILLING_RECURRING) === self::BILLING_ONE_TIME
            : str_starts_with($plan, self::ONE_TIME_PREFIX);
    }

    /** Durée couverte par un paiement, en mois. */
    public static function months(string $plan): int
    {
        return max(1, (int) (self::defaults()[$plan]['months'] ?? 1));
    }

    /** « 3 mois ». */
    public static function durationLabel(string $plan): string
    {
        return self::months($plan).' mois';
    }

    public static function amount(string $plan): float
    {
        $config = self::get($plan);

        return $config ? (float) ($config['amount'] ?? 0) : 0.0;
    }

    /** Prix par mois d'un accès à paiement unique, calculé sur le prix en vigueur. */
    public static function monthlyEquivalent(string $plan): float
    {
        return round(self::amount($plan) / self::months($plan), 2);
    }

    /**
     * Accès à paiement unique en vente, prêts à afficher : durée, prix et équivalent
     * par mois, tous lus ou calculés à partir du prix en vigueur.
     *
     * @return array<string, array{months: int, duration: string, amount: float, price: string, per_month: string}>
     */
    public static function oneTimeOffers(): array
    {
        $offers = [];
        foreach (array_keys(self::activeOneTime()) as $plan) {
            $offers[$plan] = [
                'months' => self::months($plan),
                'duration' => self::durationLabel($plan),
                'amount' => self::amount($plan),
                'price' => self::euros(self::amount($plan)),
                'per_month' => self::euros(self::monthlyEquivalent($plan)),
            ];
        }

        return $offers;
    }

    /**
     * « 27,00 € pour 3 mois ou 49,00 € pour 6 mois » : les accès à paiement unique en
     * vente, pour les textes de vente et les conditions. Null si aucun n'est en vente.
     */
    public static function oneTimeSentence(): ?string
    {
        $offers = array_map(
            fn (array $offer): string => $offer['price'].' pour '.$offer['duration'],
            array_values(self::oneTimeOffers())
        );

        return $offers === [] ? null : implode(' ou ', $offers);
    }

    /** Nom de l'article sur la page de paiement Stripe et sur son justificatif. */
    public static function stripeLabel(string $plan): string
    {
        if (self::isOneTime($plan)) {
            return 'Prokejem Premium, accès '.self::durationLabel($plan).' (paiement unique)';
        }

        $config = self::get($plan);
        $label = $config['label'] ?? ucfirst($plan);

        return 'Abonnement Prokejem '.$label;
    }

    /** « paiement unique 3 mois », à lire après « Premium · ». */
    public static function oneTimeLabel(string $plan): string
    {
        return 'paiement unique '.self::durationLabel($plan);
    }

    public static function summaryLabel(?string $plan): string
    {
        if (! $plan) {
            return '';
        }

        if (self::isOneTime($plan)) {
            return 'Premium, '.self::oneTimeLabel($plan);
        }

        $config = self::get($plan);
        if (! $config) {
            return ucfirst($plan);
        }

        return trim(($config['label'] ?? ucfirst($plan)).' ('.($config['price'] ?? '').($config['period'] ?? '').')');
    }

    /**
     * Libellé et montant des formules à prélèvement en vente, pour les
     * récapitulatifs des parcours d'activation : « Mensuel (9,99€/mois) », « 9,99 € ».
     *
     * @return array<string, array{summary: string, total: string}>
     */
    public static function summaries(): array
    {
        $summaries = [];
        foreach (array_keys(self::active()) as $plan) {
            $summaries[$plan] = [
                'summary' => self::summaryLabel($plan),
                'total' => self::euros(self::amount($plan)),
            ];
        }

        return $summaries;
    }

    public static function description(string $plan): string
    {
        return (string) (self::get($plan)['description'] ?? 'Accès complet aux outils professionnels.');
    }

    /**
     * Enregistre les formules réglées dans l'administration. Une formule absente du
     * formulaire reste telle quelle ; la nature d'une formule (prélèvement ou
     * paiement unique, durée) vient toujours de la configuration.
     */
    public static function saveFromAdminInput(array $input): void
    {
        $current = self::stored();
        $normalized = [];

        foreach (self::defaults() as $key => $defaultPlan) {
            $planInput = $input[$key] ?? null;
            if (! is_array($planInput)) {
                $normalized[$key] = $current[$key];

                continue;
            }

            $oneTime = self::isOneTime($key);
            $features = preg_split('/\r\n|\r|\n/', (string) ($planInput['features'] ?? ''));
            $features = array_values(array_filter(array_map('trim', $features)));

            $amount = str_replace(',', '.', (string) ($planInput['amount'] ?? $defaultPlan['amount']));
            $normalizedAmount = is_numeric($amount) ? round((float) $amount, 2) : (float) $defaultPlan['amount'];
            $description = trim((string) ($planInput['description'] ?? $current[$key]['description'] ?? ''));

            $normalized[$key] = [
                'billing' => $current[$key]['billing'],
                'months' => $current[$key]['months'],
                'enabled' => ! empty($planInput['enabled']),
                'recommended' => ! $oneTime && ! empty($planInput['recommended']),
                // Un accès à paiement unique se nomme par sa durée : son libellé ne se règle pas.
                'label' => $oneTime ? $defaultPlan['label'] : trim((string) ($planInput['label'] ?? $defaultPlan['label'])),
                'price' => number_format($normalizedAmount, 2, ',', ' ').'€',
                'amount' => $normalizedAmount,
                'period' => $oneTime ? $defaultPlan['period'] : trim((string) ($planInput['period'] ?? $defaultPlan['period'])),
                'original_price' => $oneTime ? '' : trim((string) ($planInput['original_price'] ?? '')),
                'badge' => $oneTime ? '' : trim((string) ($planInput['badge'] ?? '')),
                'subtitle' => $oneTime ? '' : trim((string) ($planInput['subtitle'] ?? '')),
                'description' => $description !== '' ? $description : (string) $defaultPlan['description'],
                'features' => $oneTime ? [] : ($features ?: $defaultPlan['features']),
            ];
        }

        Setting::set(self::SETTING_KEY, json_encode($normalized, JSON_UNESCAPED_UNICODE), 'subscriptions');
    }

    /** « 27,00 € ». */
    public static function euros(float $amount): string
    {
        return number_format($amount, 2, ',', ' ').' €';
    }

    private static function defaults(): array
    {
        return config('provider_subscriptions.plans', []);
    }

    /** Catalogue enregistré, fusionné avec la configuration, sans les avantages calculés. */
    private static function stored(): array
    {
        $stored = Setting::get(self::SETTING_KEY);
        $decoded = is_string($stored) && $stored !== ''
            ? json_decode($stored, true)
            : (is_array($stored) ? $stored : null);

        return self::mergeWithDefaults(is_array($decoded) ? $decoded : []);
    }

    private static function mergeWithDefaults(array $stored): array
    {
        $merged = [];

        foreach (self::defaults() as $key => $defaultPlan) {
            $plan = array_replace($defaultPlan, is_array($stored[$key] ?? null) ? $stored[$key] : []);
            // La nature de la formule ne se lit jamais dans le réglage enregistré.
            $plan['billing'] = ($defaultPlan['billing'] ?? self::BILLING_RECURRING) === self::BILLING_ONE_TIME
                ? self::BILLING_ONE_TIME
                : self::BILLING_RECURRING;
            $plan['months'] = max(1, (int) ($defaultPlan['months'] ?? 1));
            $plan['features'] = array_values(array_filter(
                (array) ($plan['features'] ?? $defaultPlan['features'] ?? [])
            ));
            $merged[$key] = $plan;
        }

        return $merged;
    }
}
