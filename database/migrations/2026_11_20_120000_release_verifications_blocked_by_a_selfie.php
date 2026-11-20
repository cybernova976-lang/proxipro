<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Le selfie avec le document n'est plus demandé (20/11/2026).
     *
     * Une demande en cours ne doit plus rester bloquée par un selfie refusé :
     *  - le refus du selfie est levé sur les demandes non clôturées ;
     *  - une demande renvoyée à son auteur pour ce seul motif repart en examen.
     *
     * Les selfies déjà enregistrés ne sont pas supprimés, et les demandes
     * approuvées ou refusées définitivement ne sont pas touchées.
     */
    public function up(): void
    {
        DB::table('identity_verifications')
            ->whereIn('status', ['pending', 'returned', 'awaiting_payment'])
            ->where('selfie_status', 'rejected')
            ->update(['selfie_status' => 'pending', 'selfie_rejection_reason' => null]);

        DB::table('identity_verifications')
            ->where('status', 'returned')
            // Pièce d'identité déjà validée, aucun autre document refusé : seul le selfie bloquait.
            // (Une demande renvoyée sans document validé attend un renvoi complet : on ne la touche pas.)
            ->where('document_front_status', 'approved')
            ->where(fn ($query) => $query->whereNull('document_back')->orWhere('document_back_status', '!=', 'rejected'))
            ->where(fn ($query) => $query->whereNull('professional_document')->orWhere('professional_document_status', '!=', 'rejected'))
            ->update(['status' => 'pending', 'admin_message' => null]);
    }

    public function down(): void
    {
        // Rien à rétablir : un refus de selfie n'a plus d'effet.
    }
};
