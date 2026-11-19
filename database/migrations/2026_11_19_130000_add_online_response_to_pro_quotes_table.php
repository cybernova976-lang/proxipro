<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Réponse du client à un devis, en ligne : lien personnel (public_token),
 * nom saisi par la personne qui répond et canal de la réponse (« online »
 * quand elle vient du lien, « manual » quand le prestataire la saisit).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pro_quotes', function (Blueprint $table) {
            $table->string('public_token', 64)->nullable()->unique();
            $table->string('response_name', 150)->nullable();
            $table->string('response_channel', 20)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('pro_quotes', function (Blueprint $table) {
            $table->dropUnique(['public_token']);
            $table->dropColumn(['public_token', 'response_name', 'response_channel']);
        });
    }
};
