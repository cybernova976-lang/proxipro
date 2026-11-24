<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catégories de notifications que le membre a retirées de la cloche de l'en-tête.
 * Vide ou nul : tout est affiché (réglage par défaut voulu par le fondateur).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('muted_bell_categories')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('muted_bell_categories');
        });
    }
};
