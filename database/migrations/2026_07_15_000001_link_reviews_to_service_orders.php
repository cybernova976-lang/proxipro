<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->foreignId('service_order_id')
                ->nullable()
                ->after('ad_id')
                ->constrained('service_orders')
                ->nullOnDelete();

            $table->unique(['service_order_id', 'reviewer_id'], 'reviews_order_reviewer_unique');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropUnique('reviews_order_reviewer_unique');
            $table->dropConstrainedForeignId('service_order_id');
        });
    }
};
