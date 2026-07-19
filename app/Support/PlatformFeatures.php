<?php

namespace App\Support;

use App\Models\Setting;
use DomainException;

class PlatformFeatures
{
    private const PRO_SUBSCRIPTIONS_KEY = 'pro_subscriptions_enabled';

    public static function proSubscriptionsRequested(): bool
    {
        return Setting::get(self::PRO_SUBSCRIPTIONS_KEY, '0') === '1';
    }

    public static function proSubscriptionsEnabled(): bool
    {
        return self::proSubscriptionsRequested() && self::proSubscriptionReadiness()['ready'];
    }

    public static function setProSubscriptionsEnabled(bool $enabled): void
    {
        if ($enabled) {
            $readiness = self::proSubscriptionReadiness();

            if (! $readiness['ready']) {
                $missing = collect($readiness['checks'])
                    ->where('ready', false)
                    ->pluck('label')
                    ->implode(', ');

                throw new DomainException('Activation impossible. Éléments manquants : '.$missing.'.');
            }
        }

        Setting::set(self::PRO_SUBSCRIPTIONS_KEY, $enabled ? '1' : '0', 'subscriptions');
    }

    public static function proSubscriptionReadiness(): array
    {
        $publicKey = trim((string) config('services.stripe.key'));
        $secretKey = trim((string) config('services.stripe.secret'));
        $webhookSecret = trim((string) config('services.stripe.webhook_secret'));
        $publicUrl = trim((string) Setting::get('platform_public_url', config('app.url')));

        $checks = [
            'stripe_public_key' => [
                'label' => 'Clé publique Stripe',
                'ready' => self::isConfiguredSecret($publicKey, ['pk_test_', 'pk_live_']),
                'help' => 'Configurer STRIPE_KEY sur l’hébergement.',
                'sensitive' => true,
            ],
            'stripe_secret_key' => [
                'label' => 'Clé secrète Stripe',
                'ready' => self::isConfiguredSecret($secretKey, ['sk_test_', 'sk_live_']),
                'help' => 'Configurer STRIPE_SECRET sur l’hébergement.',
                'sensitive' => true,
            ],
            'stripe_webhook_secret' => [
                'label' => 'Secret du webhook Stripe',
                'ready' => self::isConfiguredSecret($webhookSecret, ['whsec_']),
                'help' => 'Déclarer /stripe/webhook dans Stripe puis configurer STRIPE_WEBHOOK_SECRET.',
                'sensitive' => true,
            ],
            'stripe_live_mode' => [
                'label' => 'Compte Stripe en production',
                'ready' => str_starts_with($publicKey, 'pk_live_') && str_starts_with($secretKey, 'sk_live_'),
                'help' => 'Les clés de test permettent le développement mais ne peuvent pas ouvrir la commercialisation.',
                'sensitive' => true,
            ],
            'stripe_billing_portal' => [
                'label' => 'Portail client Stripe',
                'ready' => Setting::get('stripe_billing_portal_configured', '0') === '1',
                'help' => 'Configurer le portail client Stripe (carte, factures et résiliation), puis confirmer ci-dessous.',
            ],
            'legal_entity_name' => [
                'label' => 'Nom de l’exploitant',
                'ready' => filled(self::legalValue('legal_entity_name', 'entity_name')),
                'help' => 'Renseigner la personne ou l’entreprise qui exploite la plateforme.',
            ],
            'legal_registration_number' => [
                'label' => 'Numéro d’immatriculation',
                'ready' => filled(self::legalValue('legal_registration_number', 'registration_number')),
                'help' => 'SIREN/SIRET ou numéro équivalent du registre local.',
            ],
            'legal_address' => [
                'label' => 'Adresse légale',
                'ready' => filled(self::legalValue('legal_address', 'address')),
                'help' => 'Adresse de l’exploitant à faire figurer dans les mentions légales.',
            ],
            'legal_publication_director' => [
                'label' => 'Responsable de publication',
                'ready' => filled(self::legalValue('legal_publication_director', 'publication_director')),
                'help' => 'Nom de la personne responsable de la publication.',
            ],
            'platform_public_url' => [
                'label' => 'URL publique HTTPS',
                'ready' => self::isPublicHttpsUrl($publicUrl),
                'help' => 'Renseigner l’URL publique utilisée dans les retours de paiement et les webhooks.',
            ],
            'provider_plan_catalog' => [
                'label' => 'Au moins un plan commercialisable',
                'ready' => collect(ProviderSubscriptionPlans::active())
                    ->contains(fn (array $plan): bool => (float) ($plan['amount'] ?? 0) >= 0.5),
                'help' => 'Activer au moins un plan dont le montant Stripe est valide.',
            ],
        ];

        return [
            'ready' => collect($checks)->every(fn (array $check): bool => $check['ready']),
            'requested' => self::proSubscriptionsRequested(),
            'enabled' => self::proSubscriptionsRequested() && collect($checks)->every(fn (array $check): bool => $check['ready']),
            'mode' => str_starts_with($secretKey, 'sk_live_') ? 'live' : 'test',
            'checks' => $checks,
            'public_url' => $publicUrl,
        ];
    }

    public static function legalValue(string $settingKey, string $configKey): ?string
    {
        $value = Setting::get($settingKey, config('legal.'.$configKey));
        $value = is_string($value) ? trim($value) : null;

        return $value !== '' ? $value : null;
    }

    public static function proSubscriptionUnavailableMessage(): string
    {
        return 'Les abonnements Pro ne sont pas commercialisés actuellement. Vous pouvez utiliser l’espace prestataire gratuitement pendant cette phase.';
    }

    private static function isConfiguredSecret(string $value, array $prefixes): bool
    {
        if ($value === '' || str_contains(strtolower($value), 'xxxxx')) {
            return false;
        }

        return collect($prefixes)->contains(fn (string $prefix): bool => str_starts_with($value, $prefix));
    }

    private static function isPublicHttpsUrl(string $url): bool
    {
        if (! filter_var($url, FILTER_VALIDATE_URL) || ! str_starts_with(strtolower($url), 'https://')) {
            return false;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        return $host !== '' && ! in_array($host, ['localhost', '127.0.0.1', '::1'], true);
    }
}
