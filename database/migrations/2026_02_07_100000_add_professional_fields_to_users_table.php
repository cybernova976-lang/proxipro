<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Système de profils utilisateurs Lunamars:
     * 
     * account_type: 'particulier' | 'professionnel'
     * - Détermine le type principal du compte
     * 
     * business_type: null | 'entreprise' | 'auto_entrepreneur'  
     * - Applicable uniquement aux professionnels
     * - Entreprise: société avec SIRET, employés potentiels
     * - Auto-entrepreneur: travailleur indépendant
     * 
     * Règles métier basées sur ces champs:
     * - Particulier: peut publier des demandes de service, rechercher des prestataires
     * - Pro Entreprise: peut publier des offres de service, a accès aux fonctionnalités entreprise
     * - Pro Auto-entrepreneur: peut publier des offres, limite sur le nombre d'annonces actives
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Type de compte principal
            $table->enum('account_type', ['particulier', 'professionnel'])->default('particulier')->after('user_type');
            
            // Sous-type pour les professionnels
            $table->enum('business_type', ['entreprise', 'auto_entrepreneur'])->nullable()->after('account_type');
            
            // Informations entreprise/auto-entrepreneur
            $table->string('company_name')->nullable()->after('business_type');
            $table->string('siret', 14)->nullable()->after('company_name');
            $table->string('business_sector')->nullable()->after('siret');
            
            // Statut de vérification professionnelle
            $table->boolean('pro_verified')->default(false)->after('business_sector');
            $table->timestamp('pro_verified_at')->nullable()->after('pro_verified');
            
            // Documents professionnels (pour vérification)
            $table->string('kbis_document')->nullable()->after('pro_verified_at');
            $table->string('id_document')->nullable()->after('kbis_document');
            
            // Limites et permissions basées sur le profil
            $table->integer('max_active_ads')->default(5)->after('id_document');
            $table->boolean('can_boost_ads')->default(false)->after('max_active_ads');
            $table->boolean('newsletter_subscribed')->default(false)->after('can_boost_ads');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'account_type',
                'business_type', 
                'company_name',
                'siret',
                'business_sector',
                'pro_verified',
                'pro_verified_at',
                'kbis_document',
                'id_document',
                'max_active_ads',
                'can_boost_ads',
                'newsletter_subscribed',
            ]);
        });
    }
};
