<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Avoirs et factures d'acompte.
     *
     *  - kind : « invoice » (facture), « deposit » (facture d'acompte) ou
     *    « credit_note » (avoir). Les factures existantes sont des factures.
     *  - parent_invoice_id : pour un avoir, la facture qu'il corrige.
     *  - credited_total : sur une facture, total TTC des avoirs émis sur elle.
     *  - deposit_deducted : sur une facture de solde, total TTC des acomptes
     *    déjà facturés et déduits à l'émission.
     */
    public function up(): void
    {
        Schema::table('pro_invoices', function (Blueprint $table) {
            $table->string('kind', 20)->default('invoice')->index();
            $table->foreignId('parent_invoice_id')->nullable()->constrained('pro_invoices')->nullOnDelete();
            $table->string('credit_reason')->nullable();
            $table->decimal('credited_total', 10, 2)->default(0);
            $table->decimal('deposit_deducted', 10, 2)->default(0);
            $table->decimal('deposit_percentage', 5, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('pro_invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_invoice_id');
            $table->dropIndex(['kind']);
            $table->dropColumn(['kind', 'credit_reason', 'credited_total', 'deposit_deducted', 'deposit_percentage']);
        });
    }
};
