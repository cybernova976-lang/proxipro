<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('managed_emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source_key')->unique();
            $table->string('type')->index();
            $table->string('recipient_email');
            $table->string('recipient_name')->nullable();
            $table->string('subject');
            $table->string('eyebrow')->nullable();
            $table->string('headline');
            $table->text('body');
            $table->string('cta_label')->nullable();
            $table->text('cta_url')->nullable();
            $table->json('image_paths')->nullable();
            $table->json('metadata')->nullable();
            $table->string('status')->default('pending')->index();
            $table->timestamp('scheduled_for')->nullable()->index();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->text('failure_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('managed_emails');
    }
};
