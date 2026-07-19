<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pro_subscriptions', function (Blueprint $table) {
            $table->string('stripe_checkout_session_id')->nullable()->unique()->after('stripe_subscription_id');
            $table->string('stripe_status')->nullable()->after('status');
            $table->timestamp('last_payment_at')->nullable()->after('cancelled_at');
            $table->timestamp('payment_failed_at')->nullable()->after('last_payment_at');
            $table->unique('stripe_subscription_id', 'pro_subscriptions_stripe_subscription_unique');
        });
    }

    public function down(): void
    {
        Schema::table('pro_subscriptions', function (Blueprint $table) {
            $table->dropUnique(['stripe_checkout_session_id']);
            $table->dropUnique('pro_subscriptions_stripe_subscription_unique');
            $table->dropColumn([
                'stripe_checkout_session_id',
                'stripe_status',
                'last_payment_at',
                'payment_failed_at',
            ]);
        });
    }
};
