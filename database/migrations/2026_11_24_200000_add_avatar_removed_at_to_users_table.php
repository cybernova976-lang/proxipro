<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Date à laquelle le membre a retiré sa photo de profil. Tant qu'elle est
 * renseignée, la connexion par Google ou Facebook ne remet pas la photo du
 * compte social à sa place.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'avatar_removed_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('avatar_removed_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'avatar_removed_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('avatar_removed_at');
            });
        }
    }
};
