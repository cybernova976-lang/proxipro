<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usage_daily_metrics', function (Blueprint $table) {
            $table->id();
            $table->date('metric_date');
            $table->string('event_name', 40);
            $table->string('route_name', 100)->default('');
            $table->string('device_type', 20)->default('desktop');
            $table->string('app_mode', 20)->default('browser');
            $table->unsignedBigInteger('count')->default(0);
            $table->timestamps();

            $table->unique(
                ['metric_date', 'event_name', 'route_name', 'device_type', 'app_mode'],
                'usage_daily_metric_dimensions_unique'
            );
            $table->index(['metric_date', 'event_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usage_daily_metrics');
    }
};
