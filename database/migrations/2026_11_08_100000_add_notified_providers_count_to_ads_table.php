<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Nombre de prestataires réellement prévenus à la publication d'une annonce
 * (AdController::notifyMatchingProfessionals). NULL = jamais mesuré (annonces
 * publiées avant cette version) : aucun chiffre n'est alors affiché.
 *
 * Colonne additive et nullable : l'ancien code l'ignore, le retour arrière
 * par git checkout reste possible sans toucher à la base.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('ads', 'notified_providers_count')) {
            return;
        }

        Schema::table('ads', function (Blueprint $table) {
            $table->unsignedInteger('notified_providers_count')->nullable();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('ads', 'notified_providers_count')) {
            return;
        }

        Schema::table('ads', function (Blueprint $table) {
            $table->dropColumn('notified_providers_count');
        });
    }
};
