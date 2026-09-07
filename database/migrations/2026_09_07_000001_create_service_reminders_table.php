<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_order_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('frequency', 20)->default('once');
            $table->timestamp('next_reminder_at');
            $table->timestamp('last_sent_at')->nullable();
            $table->string('last_email_status', 20)->nullable();
            $table->unsignedInteger('reminders_sent_count')->default(0);
            $table->boolean('send_email')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'next_reminder_at']);
            $table->index(['user_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_reminders');
    }
};
