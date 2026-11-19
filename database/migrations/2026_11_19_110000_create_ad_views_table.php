<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Comptage honnête des vues d'annonce, sur le modèle des vues de profil.
 *
 * La colonne ads.views existait mais n'était incrémentée par aucun code : elle
 * valait toujours zéro. Cette table enregistre une ligne par visiteur, par
 * annonce et par jour : recharger la page ne fait pas monter le compteur.
 *
 * Aucune adresse IP n'est stockée : un visiteur non connecté est identifié par
 * une empreinte non réversible (HMAC de l'IP et du navigateur).
 *
 * La date de début du comptage est enregistrée une fois, pour pouvoir écrire
 * « N vues depuis le … » sans laisser croire que tout l'historique est compté.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ad_views')) {
            Schema::create('ad_views', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ad_id')->constrained('ads')->cascadeOnDelete();
                $table->foreignId('viewer_user_id')->nullable()->constrained('users')->nullOnDelete();
                // « u:123 » pour un membre connecté, « a:<empreinte> » sinon.
                $table->string('viewer_key', 64);
                $table->date('viewed_on');
                $table->timestamp('created_at')->nullable();

                // Une seule vue comptée par visiteur, par annonce et par jour.
                $table->unique(['ad_id', 'viewer_key', 'viewed_on'], 'ad_views_daily_unique');
                $table->index(['ad_id', 'viewed_on'], 'ad_views_ad_date_index');
            });
        }

        if (Setting::where('key', 'ad_views_counted_since')->doesntExist()) {
            Setting::set('ad_views_counted_since', now()->toDateString(), 'statistics');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_views');
    }
};
