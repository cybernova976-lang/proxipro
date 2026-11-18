<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Alerte sur un prestataire choisi : le membre est prévenu quand ce
 * prestataire publie une nouvelle offre. NULL = alerte classique ou de
 * territoire.
 *
 * Colonne additive et nullable : l'ancien code l'ignore. Si le compte du
 * prestataire est supprimé, ses alertes disparaissent avec lui.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('saved_searches', 'provider_id')) {
            return;
        }

        Schema::table('saved_searches', function (Blueprint $table) {
            $table->foreignId('provider_id')->nullable()->constrained('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('saved_searches', 'provider_id')) {
            return;
        }

        Schema::table('saved_searches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('provider_id');
        });
    }
};
