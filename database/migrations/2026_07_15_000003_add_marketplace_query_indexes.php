<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            $table->index(['status', 'category', 'created_at'], 'ads_status_category_created_idx');
            $table->index(['status', 'service_type', 'created_at'], 'ads_status_type_created_idx');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->index(['reviewed_user_id', 'created_at'], 'reviews_recipient_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            $table->dropIndex('ads_status_category_created_idx');
            $table->dropIndex('ads_status_type_created_idx');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex('reviews_recipient_created_idx');
        });
    }
};
