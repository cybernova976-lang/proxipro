<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Les anciennes listes par défaut ont été enregistrées en base par
        // l'administration et masquent les avantages corrigés du catalogue.
        // Ne modifier que ces listes exactes, jamais les prix ou l'activation.
        $legacy = [
            'monthly' => [
                'Badge Premium visible',
                'Priorité dans les suggestions de prestataires',
                'Gestion de clientèle et Espace Pro',
                'Historique de facturation Stripe',
                "Jusqu'à 4 photos par annonce",
                'Alertes dans la plateforme',
                'Support prioritaire',
            ],
            'annual' => [
                'Tout le plan mensuel inclus',
                'Priorité renforcée dans les résultats',
                'Mise en avant pendant 12 mois',
                'Badge « Premium annuel »',
                "Jusqu'à 4 photos par annonce",
                'Export comptable',
                'Assistance via formulaire de contact',
            ],
        ];

        DB::transaction(function () use ($legacy) {
            $setting = Setting::where('key', 'provider_subscription_plans')->lockForUpdate()->first();
            $plans = $setting ? json_decode($setting->value, true) : null;
            if (! is_array($plans)) {
                return;
            }

            $changed = false;
            foreach ($legacy as $key => $features) {
                if (($plans[$key]['features'] ?? null) === $features) {
                    $plans[$key]['features'] = config("provider_subscriptions.plans.{$key}.features");
                    $changed = true;
                }
            }
            if ($changed) {
                // Le modèle invalide aussi le cache des réglages.
                $setting->update(['value' => json_encode($plans, JSON_UNESCAPED_UNICODE)]);
            }
        });
    }

    public function down(): void
    {
        // Ne pas rétablir des avantages qui ne sont pas implémentés.
    }
};
