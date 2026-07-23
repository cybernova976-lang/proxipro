<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('group')->default('general');
            $table->timestamps();
        });

        // Insérer les paramètres par défaut
        $defaults = [
            ['key' => 'site_name', 'value' => 'Lunamars', 'group' => 'general'],
            ['key' => 'contact_email', 'value' => 'hello@example.com', 'group' => 'general'],
            ['key' => 'maintenance_mode', 'value' => '0', 'group' => 'general'],
            ['key' => 'free_ads_limit', 'value' => '3', 'group' => 'ads'],
            ['key' => 'ad_validity_days', 'value' => '30', 'group' => 'ads'],
            ['key' => 'auto_moderation', 'value' => '1', 'group' => 'ads'],
            ['key' => 'signup_points', 'value' => '5', 'group' => 'points'],
            ['key' => 'daily_login_points', 'value' => '5', 'group' => 'points'],
            ['key' => 'share_points', 'value' => '5', 'group' => 'points'],
            ['key' => 'message_cost', 'value' => '0', 'group' => 'points'],
            ['key' => 'mail_driver', 'value' => 'smtp', 'group' => 'email'],
            ['key' => 'email_new_user', 'value' => '1', 'group' => 'email'],
            ['key' => 'email_new_ad', 'value' => '1', 'group' => 'email'],
            ['key' => 'email_new_message', 'value' => '1', 'group' => 'email'],
        ];

        foreach ($defaults as $setting) {
            \DB::table('settings')->insert([
                'key' => $setting['key'],
                'value' => $setting['value'],
                'group' => $setting['group'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
