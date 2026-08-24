<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Comptage honnête des vues de profil.
 *
 * La colonne users.profile_views existait deja, mais elle etait incrementee a
 * chaque chargement de page : un simple rafraichissement gonflait le compteur.
 * Cette table enregistre une ligne par visiteur et par jour, ce qui permet
 * a la fois de dedoublonner et de compter sur une periode donnee.
 *
 * Aucune adresse IP n'est stockee : les visiteurs non connectes sont
 * identifies par une empreinte non reversible (HMAC de l'IP et du navigateur
 * avec la cle applicative).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profile_views', function (Blueprint $table) {
            $table->id();

            // Profil consulte.
            $table->foreignId('profile_user_id')->constrained('users')->cascadeOnDelete();

            // Visiteur connecte, quand il y en a un (utile pour les statistiques).
            $table->foreignId('viewer_user_id')->nullable()->constrained('users')->nullOnDelete();

            // « u:123 » pour un membre connecte, « a:<empreinte> » sinon.
            $table->string('viewer_key', 64);

            $table->date('viewed_on');
            $table->timestamp('created_at')->nullable();

            // Une seule vue comptee par visiteur, par profil et par jour.
            $table->unique(['profile_user_id', 'viewer_key', 'viewed_on'], 'profile_views_daily_unique');

            // Index de lecture : « combien de vues sur ce profil depuis telle date ».
            $table->index(['profile_user_id', 'viewed_on'], 'profile_views_profile_date_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_views');
    }
};
