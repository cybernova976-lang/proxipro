<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('session_user_activity', function (Blueprint $table) {
            $table->string('session_key', 64)->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('last_interaction_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_user_activity');
    }
};
