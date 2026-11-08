<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Situation de départ du parcours guidé (config/service_situations.php) d'où
 * provient une demande : « fuite-eau », « menage »… NULL = demande publiée
 * par l'ancien formulaire, ou avant cette version.
 *
 * Colonne plutôt qu'un chemin JSON dans ad_details : les comptages par
 * situation restent portables entre SQLite (tests) et PostgreSQL (production).
 *
 * Colonne additive et nullable : l'ancien code l'ignore, le retour arrière
 * par git checkout reste possible sans toucher à la base.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('ads', 'situation_key')) {
            return;
        }

        Schema::table('ads', function (Blueprint $table) {
            $table->string('situation_key', 64)->nullable()->index();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('ads', 'situation_key')) {
            return;
        }

        Schema::table('ads', function (Blueprint $table) {
            $table->dropIndex(['situation_key']);
            $table->dropColumn('situation_key');
        });
    }
};
