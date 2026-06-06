<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('identity_verifications')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE identity_verifications DROP CONSTRAINT IF EXISTS identity_verifications_status_check');
            DB::statement('ALTER TABLE identity_verifications ALTER COLUMN status TYPE VARCHAR(20)');
            DB::statement("ALTER TABLE identity_verifications ALTER COLUMN status SET DEFAULT 'pending'");
        } elseif ($driver === 'mysql') {
            DB::statement("ALTER TABLE identity_verifications MODIFY COLUMN status VARCHAR(20) NOT NULL DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        // Keep the VARCHAR column: existing awaiting_payment rows cannot fit the old enum safely.
    }
};
