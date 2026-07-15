<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $columns = [
            'email_notifications' => true,
            'profile_public' => true,
            'show_email' => false,
            'show_phone' => false,
        ];

        foreach ($columns as $column => $default) {
            if (! Schema::hasColumn('users', $column)) {
                Schema::table('users', function (Blueprint $table) use ($column, $default) {
                    $table->boolean($column)->default($default);
                });
            }
        }
    }

    public function down(): void
    {
        $columns = collect(['email_notifications', 'profile_public', 'show_email', 'show_phone'])
            ->filter(fn (string $column) => Schema::hasColumn('users', $column))
            ->all();

        if ($columns !== []) {
            Schema::table('users', fn (Blueprint $table) => $table->dropColumn($columns));
        }
    }
};
