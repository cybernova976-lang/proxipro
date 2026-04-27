<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('stripe_connect_account_id')->nullable()->after('stripe_id');
            $table->timestamp('stripe_connect_onboarding_completed_at')->nullable()->after('stripe_connect_account_id');
            $table->boolean('stripe_connect_payouts_enabled')->default(false)->after('stripe_connect_onboarding_completed_at');
            $table->boolean('stripe_connect_charges_enabled')->default(false)->after('stripe_connect_payouts_enabled');

            $table->index('stripe_connect_account_id');
        });

        Schema::table('service_orders', function (Blueprint $table) {
            $table->timestamp('refunded_at')->nullable()->after('released_at');
            $table->string('admin_resolution', 40)->nullable()->after('dispute_reason');
            $table->text('admin_resolution_note')->nullable()->after('admin_resolution');
            $table->timestamp('admin_resolved_at')->nullable()->after('admin_resolution_note');
            $table->unsignedBigInteger('admin_resolved_by')->nullable()->after('admin_resolved_at');
            $table->string('stripe_transfer_id')->nullable()->after('stripe_payment_intent_id');
            $table->string('stripe_refund_id')->nullable()->after('stripe_transfer_id');

            $table->index('admin_resolution');
            $table->index('stripe_transfer_id');
            $table->index('stripe_refund_id');
        });
    }

    public function down(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            $table->dropIndex(['admin_resolution']);
            $table->dropIndex(['stripe_transfer_id']);
            $table->dropIndex(['stripe_refund_id']);
            $table->dropColumn([
                'refunded_at',
                'admin_resolution',
                'admin_resolution_note',
                'admin_resolved_at',
                'admin_resolved_by',
                'stripe_transfer_id',
                'stripe_refund_id',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['stripe_connect_account_id']);
            $table->dropColumn([
                'stripe_connect_account_id',
                'stripe_connect_onboarding_completed_at',
                'stripe_connect_payouts_enabled',
                'stripe_connect_charges_enabled',
            ]);
        });
    }
};