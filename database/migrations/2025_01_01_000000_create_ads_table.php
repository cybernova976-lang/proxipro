<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ads')) {
            return;
        }

        Schema::create('ads', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->string('category'); // bricolage, plomberie, etc.
            $table->string('location'); // ville, département
            $table->decimal('price', 10, 2)->nullable(); // prix si applicable
            $table->string('service_type'); // offre/demande
            $table->string('status')->default('active'); // active, inactive
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps(); // created_at, updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ads');
    }
};
