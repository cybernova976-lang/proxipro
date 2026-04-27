<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Increase email_verification_code from varchar(6) to varchar(255)
     * so it can store a bcrypt hash (60+ characters).
     */
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'email_verification_code')) {
            return;
        }

        if ($this->alreadySupportsHashedCodes()) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('email_verification_code', 255)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email_verification_code', 6)->nullable()->change();
        });
    }

    private function alreadySupportsHashedCodes(): bool
    {
        if (DB::getDriverName() !== 'sqlite') {
            return false;
        }

        $column = collect(DB::select('PRAGMA table_info(users)'))
            ->firstWhere('name', 'email_verification_code');

        $type = strtolower((string) ($column->type ?? ''));

        return str_contains($type, '255') || $type === 'text';
    }
};
