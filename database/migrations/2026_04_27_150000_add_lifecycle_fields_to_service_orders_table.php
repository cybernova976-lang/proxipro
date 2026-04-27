<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            $table->timestamp('paid_at')->nullable()->after('accepted_at');
            $table->timestamp('refused_at')->nullable()->after('released_at');
            $table->text('refused_reason')->nullable()->after('refused_at');
            $table->timestamp('disputed_at')->nullable()->after('refused_reason');
            $table->text('dispute_reason')->nullable()->after('disputed_at');
            $table->string('stripe_checkout_session_id')->nullable()->after('dispute_reason');
            $table->string('stripe_payment_intent_id')->nullable()->after('stripe_checkout_session_id');

            $table->index('stripe_checkout_session_id');
            $table->index('stripe_payment_intent_id');
        });
    }

    public function down(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            $table->dropIndex(['stripe_checkout_session_id']);
            $table->dropIndex(['stripe_payment_intent_id']);
            $table->dropColumn([
                'paid_at',
                'refused_at',
                'refused_reason',
                'disputed_at',
                'dispute_reason',
                'stripe_checkout_session_id',
                'stripe_payment_intent_id',
            ]);
        });
    }
};