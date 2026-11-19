<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/*
 * Formules Premium enregistrées depuis l'administration (réglage
 * provider_subscription_plans) : elles masquent le catalogue du code.
 *
 * 1. La formule annuelle est retirée : elle n'apportait aucun avantage réel
 *    par rapport à la mensuelle.
 * 2. Trois lignes sont retirées de la liste des avantages, où qu'elles se
 *    trouvent : elles décrivaient ce que tout prestataire a déjà sans
 *    abonnement. Les présenter comme payantes était trompeur.
 *
 * Les prix, l'activation et les autres avantages ne sont jamais modifiés.
 */
return new class extends Migration
{
    private const NOT_EXCLUSIVE = [
        'Gestion de clientèle et Espace Pro',
        'Historique de facturation Stripe',
        'Alertes dans la plateforme',
    ];

    public function up(): void
    {
        DB::transaction(function () {
            $setting = Setting::where('key', 'provider_subscription_plans')->lockForUpdate()->first();
            $plans = $setting ? json_decode($setting->value, true) : null;
            if (! is_array($plans)) {
                return;
            }

            $cleaned = $plans;
            unset($cleaned['annual']);

            foreach ($cleaned as $key => $plan) {
                if (is_array($plan['features'] ?? null)) {
                    $cleaned[$key]['features'] = array_values(array_filter(
                        $plan['features'],
                        fn ($feature) => ! in_array($feature, self::NOT_EXCLUSIVE, true)
                    ));
                }
            }

            if ($cleaned !== $plans) {
                // Le modèle invalide aussi le cache des réglages.
                $setting->update(['value' => json_encode($cleaned, JSON_UNESCAPED_UNICODE)]);
            }
        });
    }

    public function down(): void
    {
        // Ne pas rétablir une formule sans avantage ni des avantages qui n'en sont pas.
    }
};
