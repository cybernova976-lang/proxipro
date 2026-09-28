<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_presence_leases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Jamais de cookie ou d'identifiant de session en clair.
            $table->string('session_key', 64);
            $table->uuid('tab_id');
            $table->unsignedBigInteger('sequence')->default(0);
            $table->timestamp('expires_at');
            $table->unique(['session_key', 'tab_id']);
            $table->index(['user_id', 'expires_at']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_presence_leases');
    }
};
