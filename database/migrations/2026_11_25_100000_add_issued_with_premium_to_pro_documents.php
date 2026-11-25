<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Table du document => colonne qui date son émission (numéro définitif attribué). */
    private const TABLES = ['pro_quotes' => 'issued_at', 'pro_invoices' => 'finalized_at'];

    /**
     * Filigrane « version gratuite » des devis et factures (décision du fondateur
     * du 25/11/2026). Un document émis pendant une période Premium reste sans
     * filigrane pour toujours, même après la fin de l'abonnement : la colonne
     * issued_with_premium le retient, à l'attribution du numéro définitif.
     *
     * Reprise de l'existant : un document déjà émis est marqué « émis sous
     * Premium » si un abonnement de son auteur couvrait sa date d'émission
     * (dates enregistrées, rien d'estimé). Les autres gardent la valeur par défaut.
     *
     * Rejouable : une table qui a déjà la colonne n'est pas touchée.
     */
    public function up(): void
    {
        foreach (self::TABLES as $table => $issuedColumn) {
            if (! Schema::hasTable($table) || Schema::hasColumn($table, 'issued_with_premium')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->boolean('issued_with_premium')->default(false);
            });

            if (! Schema::hasTable('pro_subscriptions')) {
                continue;
            }

            DB::table($table)
                ->whereNotNull($issuedColumn)
                ->where('status', '!=', 'draft')
                ->whereExists(function ($query) use ($table, $issuedColumn) {
                    $query->select(DB::raw(1))->from('pro_subscriptions')
                        ->whereColumn('pro_subscriptions.user_id', $table.'.user_id')
                        // Un abonnement resté « en attente » n'a jamais été payé.
                        ->whereIn('pro_subscriptions.status', ['active', 'cancelled', 'expired'])
                        // Souscription jamais réglée (Stripe « incomplete_expired ») : pas une période payée.
                        ->where(function ($paid) {
                            $paid->whereNull('pro_subscriptions.stripe_status')
                                ->orWhere('pro_subscriptions.stripe_status', '!=', 'incomplete_expired');
                        })
                        ->whereNotNull('pro_subscriptions.starts_at')
                        // Un abonnement renouvelé ne garde que sa période en cours dans starts_at ;
                        // created_at date sa première synchronisation : la plus ancienne des deux fait foi.
                        ->where(function ($start) use ($table, $issuedColumn) {
                            $start->whereColumn('pro_subscriptions.starts_at', '<=', $table.'.'.$issuedColumn)
                                ->orWhereColumn('pro_subscriptions.created_at', '<=', $table.'.'.$issuedColumn);
                        })
                        ->where(function ($period) use ($table, $issuedColumn) {
                            $period->whereNull('pro_subscriptions.ends_at')
                                ->orWhereColumn('pro_subscriptions.ends_at', '>=', $table.'.'.$issuedColumn);
                        });
                })
                ->update(['issued_with_premium' => true]);
        }
    }

    public function down(): void
    {
        foreach (array_keys(self::TABLES) as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'issued_with_premium')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->dropColumn('issued_with_premium');
                });
            }
        }
    }
};
