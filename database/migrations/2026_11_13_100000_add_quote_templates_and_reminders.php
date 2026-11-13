<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Services Premium des devis :
 *  - modèles de devis réutilisables (lignes, TVA, acompte, conditions) ;
 *  - relance automatique unique d'un devis resté sans réponse.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pro_quote_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('subject');
            $table->string('operation_type', 20)->default('services');
            $table->json('items');
            $table->decimal('tax_rate', 5, 2)->default(20);
            $table->decimal('deposit_percentage', 5, 2)->nullable();
            $table->boolean('is_free')->default(true);
            $table->text('notes')->nullable();
            $table->text('conditions')->nullable();
            $table->timestamps();
        });

        Schema::table('pro_quotes', function (Blueprint $table) {
            // Adresse à laquelle le devis a réellement été envoyé (peut différer de la fiche client).
            $table->string('sent_to_email')->nullable();
            $table->boolean('reminder_enabled')->default(false);
            $table->timestamp('reminder_sent_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('pro_quotes', function (Blueprint $table) {
            $table->dropColumn(['sent_to_email', 'reminder_enabled', 'reminder_sent_at']);
        });
        Schema::dropIfExists('pro_quote_templates');
    }
};
