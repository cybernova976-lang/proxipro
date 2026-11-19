<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** Avantages ajoutés le 19/11/2026 (lot 3) : ils sont appliqués par le code. */
    private const ADDED = [
        'Votre logo sur vos devis et vos factures (comptes professionnels)',
        'Rappel automatique de vos factures impayées (comptes professionnels)',
    ];

    public function up(): void
    {
        // La liste d'avantages enregistrée en base masque celle du catalogue. Si
        // elle est restée identique à la liste par défaut précédente, on lui
        // ajoute les deux nouveaux avantages. Une liste personnalisée depuis
        // l'administration n'est jamais touchée, pas plus que les prix.
        $previous = [
            'Badge « Premium » sur votre profil public',
            'Vos offres signalées « Sponsorisé » et affichées avant les offres non sponsorisées sur l’accueil des clients, sans place fixe',
            'Profil présenté avant celui des prestataires non abonnés dans la liste proposée après une demande',
            'Jusqu\'à 4 photos par annonce',
            'Réduction de 20 % sur les boosts et l’option Urgent',
            'Rapport d’activité mensuel : vues de profil, contacts, missions et montants mesurés, avec récapitulatif par e-mail',
            'Modèles de devis et relance automatique des devis sans réponse (comptes professionnels)',
        ];

        DB::transaction(function () use ($previous) {
            $setting = Setting::where('key', 'provider_subscription_plans')->lockForUpdate()->first();
            $plans = $setting ? json_decode($setting->value, true) : null;
            if (! is_array($plans) || ($plans['monthly']['features'] ?? null) !== $previous) {
                return;
            }

            $plans['monthly']['features'] = [...$previous, ...self::ADDED];
            // Le modèle invalide aussi le cache des réglages.
            $setting->update(['value' => json_encode($plans, JSON_UNESCAPED_UNICODE)]);
        });
    }

    public function down(): void
    {
        // Les avantages ajoutés sont implémentés : rien à retirer.
    }
};
