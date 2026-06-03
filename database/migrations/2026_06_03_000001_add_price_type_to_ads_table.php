<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ads') && !Schema::hasColumn('ads', 'price_type')) {
            Schema::table('ads', function (Blueprint $table) {
                $table->string('price_type')->nullable()->after('price');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ads') && Schema::hasColumn('ads', 'price_type')) {
            Schema::table('ads', function (Blueprint $table) {
                $table->dropColumn('price_type');
            });
        }
    }
};
