<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Échec de paiement d'un abonnement : le membre garde ses avantages pendant un
 * délai de régularisation, puis son compte repasse en gratuit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pro_subscriptions', function (Blueprint $table) {
            // Fin du délai de régularisation ouvert par le premier échec de paiement.
            $table->timestamp('grace_ends_at')->nullable()->after('payment_failed_at');
            // Nombre de rappels déjà envoyés pendant ce délai.
            $table->unsignedTinyInteger('payment_reminders_sent')->default(0)->after('grace_ends_at');
            // Dernière session Stripe de changement de moyen de paiement déjà traitée.
            $table->string('payment_method_session_id')->nullable()->after('payment_reminders_sent');
        });
    }

    public function down(): void
    {
        Schema::table('pro_subscriptions', function (Blueprint $table) {
            $table->dropColumn(['grace_ends_at', 'payment_reminders_sent', 'payment_method_session_id']);
        });
    }
};
