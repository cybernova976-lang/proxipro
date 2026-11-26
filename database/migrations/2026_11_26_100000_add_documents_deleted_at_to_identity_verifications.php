<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Date à laquelle les pièces d'une demande de vérification ont été effacées,
 * après la décision finale (validation ou refus), comme l'annonce la politique
 * de confidentialité. Le résultat du contrôle, lui, est conservé.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('identity_verifications', 'documents_deleted_at')) {
            Schema::table('identity_verifications', function (Blueprint $table) {
                $table->timestamp('documents_deleted_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('identity_verifications', 'documents_deleted_at')) {
            Schema::table('identity_verifications', function (Blueprint $table) {
                $table->dropColumn('documents_deleted_at');
            });
        }
    }
};
