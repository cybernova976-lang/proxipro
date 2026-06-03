<?php

namespace App\Support;

use App\Models\Setting;

class ProviderSubscriptionPlans
{
    private const SETTING_KEY = 'provider_subscription_plans';

    public static function all(): array
    {
        $stored = Setting::get(self::SETTING_KEY);
        $decoded = is_string($stored) && $stored !== ''
            ? json_decode($stored, true)
            : (is_array($stored) ? $stored : null);

        if (!is_array($decoded)) {
            return self::defaults();
        }

        return self::mergeWithDefaults($decoded);
    }

    public static function active(): array
    {
        return array_filter(self::all(), fn(array $plan) => (bool) ($plan['enabled'] ?? true));
    }

    public static function get(string $plan): ?array
    {
        return self::all()[$plan] ?? null;
    }

    public static function amount(string $plan): float
    {
        $config = self::get($plan);

        return $config ? (float) ($config['amount'] ?? 0) : 0.0;
    }

    public static function stripeLabel(string $plan): string
    {
        $config = self::get($plan);
        $label = $config['label'] ?? ucfirst($plan);

        return 'Abonnement ProxiPro ' . $label;
    }

    public static function summaryLabel(?string $plan): string
    {
        if (!$plan) {
            return '';
        }

        $config = self::get($plan);
        if (!$config) {
            return ucfirst($plan);
        }

        return trim(($config['label'] ?? ucfirst($plan)) . ' (' . ($config['price'] ?? '') . ($config['period'] ?? '') . ')');
    }

    public static function description(string $plan): string
    {
        return (string) (self::get($plan)['description'] ?? 'Accès complet aux outils professionnels.');
    }

    public static function saveFromAdminInput(array $input): void
    {
        $defaults = self::defaults();
        $normalized = [];

        foreach ($defaults as $key => $defaultPlan) {
            $planInput = $input[$key] ?? [];
            $features = preg_split('/\r\n|\r|\n/', (string) ($planInput['features'] ?? ''));
            $features = array_values(array_filter(array_map('trim', $features)));

            $amount = str_replace(',', '.', (string) ($planInput['amount'] ?? $defaultPlan['amount']));

            $normalized[$key] = [
                'enabled' => !empty($planInput['enabled']),
                'recommended' => !empty($planInput['recommended']),
                'label' => trim((string) ($planInput['label'] ?? $defaultPlan['label'])),
                'price' => trim((string) ($planInput['price'] ?? $defaultPlan['price'])),
                'amount' => is_numeric($amount) ? round((float) $amount, 2) : (float) $defaultPlan['amount'],
                'period' => trim((string) ($planInput['period'] ?? $defaultPlan['period'])),
                'original_price' => trim((string) ($planInput['original_price'] ?? '')),
                'badge' => trim((string) ($planInput['badge'] ?? '')),
                'subtitle' => trim((string) ($planInput['subtitle'] ?? '')),
                'description' => trim((string) ($planInput['description'] ?? $defaultPlan['description'])),
                'features' => $features ?: $defaultPlan['features'],
            ];
        }

        Setting::set(self::SETTING_KEY, json_encode($normalized, JSON_UNESCAPED_UNICODE), 'subscriptions');
    }

    private static function defaults(): array
    {
        return config('provider_subscriptions.plans', []);
    }

    private static function mergeWithDefaults(array $stored): array
    {
        $defaults = self::defaults();

        foreach ($defaults as $key => $defaultPlan) {
            $stored[$key] = array_replace($defaultPlan, $stored[$key] ?? []);
            $stored[$key]['features'] = array_values(array_filter(
                (array) ($stored[$key]['features'] ?? $defaultPlan['features'])
            ));
        }

        return array_intersect_key($stored, $defaults);
    }
}
