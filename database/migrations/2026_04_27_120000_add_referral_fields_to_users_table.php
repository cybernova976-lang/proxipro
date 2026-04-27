<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('referral_code', 20)->nullable()->unique()->after('email');
            $table->foreignId('referred_by_user_id')->nullable()->after('referral_code')->constrained('users')->nullOnDelete();
            $table->timestamp('referral_bonus_granted_at')->nullable()->after('referred_by_user_id');
            $table->timestamp('first_qualifying_purchase_at')->nullable()->after('referral_bonus_granted_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('referred_by_user_id');
            $table->dropColumn([
                'referral_code',
                'referral_bonus_granted_at',
                'first_qualifying_purchase_at',
            ]);
        });
    }
};