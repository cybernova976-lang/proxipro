<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // La liste d'avantages du plan mensuel enregistrée en base masque celle du
        // catalogue. Si elle est restée identique à l'ancienne liste par défaut, on
        // la remplace par la nouvelle (rapport d'activité, modèles et relance des
        // devis). Une liste personnalisée par l'administration n'est jamais touchée,
        // pas plus que les prix ou l'activation.
        $previous = [
            'Badge « Premium » sur votre profil public',
            'Vos offres signalées « Sponsorisé » et affichées avant les offres non sponsorisées sur l’accueil des clients, sans place fixe',
            'Profil présenté avant celui des prestataires non abonnés dans la liste proposée après une demande',
            'Gestion de clientèle et Espace Pro',
            'Historique de facturation Stripe',
            'Jusqu\'à 4 photos par annonce',
            'Réduction de 20 % sur les boosts et l’option Urgent',
            'Alertes dans la plateforme',
        ];

        DB::transaction(function () use ($previous) {
            $setting = Setting::where('key', 'provider_subscription_plans')->lockForUpdate()->first();
            $plans = $setting ? json_decode($setting->value, true) : null;
            if (! is_array($plans) || ($plans['monthly']['features'] ?? null) !== $previous) {
                return;
            }

            $plans['monthly']['features'] = config('provider_subscriptions.plans.monthly.features');
            // Le modèle invalide aussi le cache des réglages.
            $setting->update(['value' => json_encode($plans, JSON_UNESCAPED_UNICODE)]);
        });
    }

    public function down(): void
    {
        // Les avantages ajoutés sont implémentés : rien à retirer.
    }
};
