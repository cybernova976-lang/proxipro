<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        $now = now();
        $previousBrandName = 'luna'.'mars';
        $previousDomains = [
            'https://'.$previousBrandName.'.fr',
            'https://'.$previousBrandName.'.fr/',
            'https://www.'.$previousBrandName.'.fr',
            'https://www.'.$previousBrandName.'.fr/',
        ];

        DB::table('settings')->updateOrInsert(
            ['key' => 'site_name'],
            [
                'value' => 'Prokejem',
                'group' => 'general',
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );

        DB::table('settings')
            ->whereIn('key', ['mail_from_name', 'mail_reply_to_name'])
            ->whereRaw('LOWER(TRIM(value)) = ?', [$previousBrandName])
            ->update([
                'value' => 'Prokejem',
                'updated_at' => $now,
            ]);

        DB::table('settings')
            ->where('key', 'platform_public_url')
            ->whereIn('value', $previousDomains)
            ->update([
                'value' => 'https://www.prokejem.fr',
                'updated_at' => $now,
            ]);

        foreach (['site_name', 'mail_from_name', 'mail_reply_to_name', 'platform_public_url'] as $key) {
            Cache::forget("setting_{$key}");
        }
    }

    public function down(): void
    {
        // L'identité Prokejem est conservée volontairement en cas de rollback.
    }
};
