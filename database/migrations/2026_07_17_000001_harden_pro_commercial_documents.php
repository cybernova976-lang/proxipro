<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('siret', 64)->nullable()->change();
            $table->string('tva_number', 32)->nullable();
            $table->timestamp('pro_terms_accepted_at')->nullable();
            $table->string('pro_terms_version', 32)->nullable();
            $table->string('pro_terms_ip', 45)->nullable();
        });

        Schema::table('pro_quotes', function (Blueprint $table) {
            // Keep the legacy client_id column intact: it incorrectly references users.
            $table->foreignId('pro_client_id')->nullable()->constrained('pro_clients')->nullOnDelete();
            $table->string('client_company')->nullable();
            $table->string('client_registration_number', 64)->nullable();
            $table->string('client_vat_number', 64)->nullable();
            $table->string('operation_type', 24)->default('services');
            $table->string('execution_location')->nullable();
            $table->string('currency', 3)->default('EUR');
            $table->boolean('is_free')->default(true);
            $table->decimal('deposit_percentage', 5, 2)->nullable();
            $table->json('seller_snapshot')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('refused_at')->nullable();
        });

        Schema::table('pro_invoices', function (Blueprint $table) {
            // Keep the legacy client_id column intact: it incorrectly references users.
            $table->foreignId('pro_client_id')->nullable()->constrained('pro_clients')->nullOnDelete();
            $table->string('client_company')->nullable();
            $table->string('client_registration_number', 64)->nullable();
            $table->string('client_vat_number', 64)->nullable();
            $table->string('client_type', 24)->default('individual');
            $table->string('operation_type', 24)->default('services');
            $table->date('service_date')->nullable();
            $table->string('purchase_order_number', 100)->nullable();
            $table->string('delivery_address')->nullable();
            $table->string('currency', 3)->default('EUR');
            $table->string('vat_exemption_reason')->nullable();
            $table->string('early_payment_discount')->nullable();
            $table->decimal('late_penalty_rate', 5, 2)->nullable();
            $table->json('seller_snapshot')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamp('sent_at')->nullable();
        });

        Schema::create('pro_document_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('document_type', 16);
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();
            $table->unique(['user_id', 'document_type', 'year'], 'pro_document_sequence_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pro_document_sequences');

        Schema::table('pro_invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pro_client_id');
            $table->dropColumn([
                'client_company', 'client_registration_number', 'client_vat_number',
                'client_type', 'operation_type', 'service_date', 'purchase_order_number',
                'delivery_address', 'currency', 'vat_exemption_reason',
                'early_payment_discount', 'late_penalty_rate', 'seller_snapshot',
                'finalized_at', 'sent_at',
            ]);
        });

        Schema::table('pro_quotes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pro_client_id');
            $table->dropColumn([
                'client_company', 'client_registration_number', 'client_vat_number',
                'operation_type', 'execution_location', 'currency', 'is_free',
                'deposit_percentage', 'seller_snapshot', 'issued_at', 'sent_at',
                'accepted_at', 'refused_at',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'tva_number', 'pro_terms_accepted_at', 'pro_terms_version', 'pro_terms_ip',
            ]);
            $table->string('siret', 14)->nullable()->change();
        });
    }
};
