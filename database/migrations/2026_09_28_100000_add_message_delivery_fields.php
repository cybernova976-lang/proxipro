<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->uuid('client_token')->nullable();
            $table->timestamp('edited_at')->nullable();
            $table->unique(['sender_id', 'client_token']);
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropUnique(['sender_id', 'client_token']);
            $table->dropColumn(['client_token', 'edited_at']);
        });
    }
};
