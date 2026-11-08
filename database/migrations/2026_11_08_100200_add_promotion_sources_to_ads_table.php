<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Origine d'un boost ou d'une urgence en cours : 'admin' quand l'administration
 * l'a accordé gratuitement (AdminController::grantBoost / grantUrgent), NULL
 * pour un achat. Une mise en avant gratuite n'est jamais présentée comme payée
 * (App\Support\AdDisclosure).
 *
 * Colonnes additives et nullables : l'ancien code les ignore, le retour arrière
 * par git checkout reste possible sans toucher à la base.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            if (! Schema::hasColumn('ads', 'boost_source')) {
                $table->string('boost_source', 20)->nullable();
            }
            if (! Schema::hasColumn('ads', 'urgent_source')) {
                $table->string('urgent_source', 20)->nullable();
            }
        });
    }

    public function down(): void
    {
        foreach (['boost_source', 'urgent_source'] as $column) {
            if (Schema::hasColumn('ads', $column)) {
                Schema::table('ads', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
