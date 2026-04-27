<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referral_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('referee_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('source_transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->string('reward_type', 50);
            $table->unsignedInteger('points');
            $table->timestamp('granted_at');
            $table->timestamps();

            $table->unique(['referee_user_id', 'reward_type']);
            $table->index(['referrer_user_id', 'reward_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_rewards');
    }
};