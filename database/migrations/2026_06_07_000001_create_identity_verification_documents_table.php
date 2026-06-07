<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('identity_verification_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('identity_verification_id')
                ->constrained('identity_verifications')
                ->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('field', 40);
            $table->string('original_name');
            $table->string('mime_type', 120);
            $table->string('extension', 20);
            $table->unsignedBigInteger('size');
            $table->longText('content');
            $table->timestamps();

            $table->unique(['identity_verification_id', 'field']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('identity_verification_documents');
    }
};
