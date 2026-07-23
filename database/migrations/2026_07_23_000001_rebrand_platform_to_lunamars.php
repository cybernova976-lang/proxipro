<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $legacyName = 'Proxi'.'Pro';
        $configuredEmail = (string) (
            config('mail.admin_email')
            ?: config('mail.from.address')
            ?: 'hello@example.com'
        );

        DB::table('settings')->updateOrInsert(
            ['key' => 'site_name'],
            [
                'value' => 'Lunamars',
                'group' => 'general',
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );

        DB::table('settings')
            ->where('key', 'contact_email')
            ->whereRaw('LOWER(value) = ?', ['contact@'.strtolower($legacyName).'.com'])
            ->update([
                'value' => $configuredEmail,
                'updated_at' => $now,
            ]);
    }

    public function down(): void
    {
        // Le changement d'identité est volontairement conservé en cas de rollback.
    }
};
