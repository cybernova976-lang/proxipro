<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rappel unique d'une facture impayée (App\Services\InvoiceFollowUpService) :
 * adresse où la facture a été envoyée, demande du prestataire, date d'envoi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pro_invoices', function (Blueprint $table) {
            $table->string('sent_to_email')->nullable();
            $table->boolean('reminder_enabled')->default(false);
            $table->timestamp('reminder_sent_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('pro_invoices', function (Blueprint $table) {
            $table->dropColumn(['sent_to_email', 'reminder_enabled', 'reminder_sent_at']);
        });
    }
};
