<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Accès Premium à paiement unique (25/11/2026).
 *
 * 1. La colonne « plan » était une énumération limitée à monthly / annual : elle
 *    devient un texte libre, pour recevoir les accès à paiement unique (pass_3m,
 *    pass_6m). Les valeurs existantes ne changent pas.
 * 2. Une colonne garde la date du rappel envoyé avant la fin d'un accès à paiement
 *    unique : il part une seule fois par accès.
 *
 * Rejouable : chaque étape vérifie d'abord l'état de la table.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pro_subscriptions')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            // Une énumération Laravel est un texte assorti d'une contrainte de valeurs.
            DB::statement('ALTER TABLE pro_subscriptions DROP CONSTRAINT IF EXISTS pro_subscriptions_plan_check');
            DB::statement('ALTER TABLE pro_subscriptions ALTER COLUMN plan TYPE VARCHAR(40)');
            DB::statement("ALTER TABLE pro_subscriptions ALTER COLUMN plan SET DEFAULT 'monthly'");
        } elseif (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE pro_subscriptions MODIFY COLUMN plan VARCHAR(40) NOT NULL DEFAULT 'monthly'");
        } else {
            // SQLite (développement et tests) : la table est reconstruite sans la contrainte.
            Schema::table('pro_subscriptions', function (Blueprint $table) {
                $table->string('plan', 40)->default('monthly')->change();
            });
        }

        if (! Schema::hasColumn('pro_subscriptions', 'expiry_reminder_sent_at')) {
            Schema::table('pro_subscriptions', function (Blueprint $table) {
                $table->timestamp('expiry_reminder_sent_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        // La colonne « plan » reste un texte : un accès à paiement unique déjà vendu
        // ne tiendrait plus dans l'ancienne énumération.
        if (Schema::hasTable('pro_subscriptions') && Schema::hasColumn('pro_subscriptions', 'expiry_reminder_sent_at')) {
            Schema::table('pro_subscriptions', function (Blueprint $table) {
                $table->dropColumn('expiry_reminder_sent_at');
            });
        }
    }
};
