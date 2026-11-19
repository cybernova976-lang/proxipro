<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Remontée automatique d'une annonce.
 *
 *  - bumped_at : date de la dernière remontée. Le classement « plus récentes
 *    d'abord » utilise cette date quand elle existe, sinon la date de
 *    publication. La date de publication affichée ne change jamais.
 *  - bump_every_days / bump_until : remontée achetée (tous les 1 ou 7 jours,
 *    jusqu'à l'échéance).
 *
 * Colonnes additives et nullables : l'ancien code les ignore.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            if (! Schema::hasColumn('ads', 'bumped_at')) {
                $table->timestamp('bumped_at')->nullable();
            }
            if (! Schema::hasColumn('ads', 'bump_every_days')) {
                $table->unsignedTinyInteger('bump_every_days')->nullable();
            }
            if (! Schema::hasColumn('ads', 'bump_until')) {
                $table->timestamp('bump_until')->nullable()->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            foreach (['bump_until', 'bump_every_days', 'bumped_at'] as $column) {
                if (Schema::hasColumn('ads', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
