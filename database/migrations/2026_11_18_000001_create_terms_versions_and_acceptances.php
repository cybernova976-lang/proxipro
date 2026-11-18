<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Versions des conditions générales d'utilisation et preuve de leur
 * acceptation par chaque membre. Tant qu'aucune version n'est publiée par
 * l'administrateur, rien n'est demandé à personne.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('terms_versions', function (Blueprint $table) {
            $table->id();
            $table->string('version', 32)->unique();
            // Résumé des changements, montré à chaque membre avant qu'il accepte.
            $table->text('summary');
            // Avant cette date l'acceptation est proposée ; à partir d'elle, elle est obligatoire.
            $table->timestamp('effective_at');
            $table->unsignedBigInteger('published_by')->nullable();
            $table->timestamps();
        });

        Schema::create('terms_acceptances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('terms_version_id')->constrained('terms_versions')->cascadeOnDelete();
            $table->timestamp('accepted_at');
            $table->string('ip', 45)->nullable();
            // inscription, demande à la connexion, ou publication par l'administrateur.
            $table->string('source', 20);
            $table->timestamps();
            $table->unique(['user_id', 'terms_version_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            // Dernière version acceptée : évite une jointure à chaque page.
            $table->unsignedBigInteger('accepted_terms_version_id')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['accepted_terms_version_id']);
            $table->dropColumn('accepted_terms_version_id');
        });
        Schema::dropIfExists('terms_acceptances');
        Schema::dropIfExists('terms_versions');
    }
};
