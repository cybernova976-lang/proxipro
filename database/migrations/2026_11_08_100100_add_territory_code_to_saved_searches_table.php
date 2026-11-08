<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Alerte « nouvelles offres du territoire » créée depuis l'état vide du flux
 * client : code du territoire (« 976 », « 75 », « 2A »…) au sens de
 * App\Support\TerritoryResolver. NULL = alerte classique (pays, ville, rayon).
 *
 * Colonne additive et nullable : l'ancien code l'ignore ; une alerte de
 * territoire garde aussi son libellé de pays (« Mayotte ») pour rester
 * lisible après un retour arrière.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('saved_searches', 'territory_code')) {
            return;
        }

        Schema::table('saved_searches', function (Blueprint $table) {
            $table->string('territory_code', 8)->nullable();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('saved_searches', 'territory_code')) {
            return;
        }

        Schema::table('saved_searches', function (Blueprint $table) {
            $table->dropColumn('territory_code');
        });
    }
};
