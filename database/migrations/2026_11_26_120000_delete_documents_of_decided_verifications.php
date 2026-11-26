<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Effacement unique, décidé par le fondateur le 26/11/2026 : les pièces des demandes
 * de vérification tranchées (validées ou refusées) avant l'effacement automatique
 * après décision sont supprimées, comme l'annonce la politique de confidentialité.
 * Le résultat du contrôle (statuts, motifs, dates) est conservé.
 *
 * Écrit avec la base seule, sans dépendre du modèle ni de la commande, pour rester
 * exécutable tel quel sur une base neuve. Sans retour possible : down() ne fait rien.
 */
return new class extends Migration
{
    private const FIELDS = ['document_front', 'document_back', 'selfie', 'professional_document'];

    public function up(): void
    {
        if (! Schema::hasTable('identity_verifications') || ! Schema::hasColumn('identity_verifications', 'documents_deleted_at')) {
            return;
        }

        $fields = array_values(array_filter(self::FIELDS, fn (string $field) => Schema::hasColumn('identity_verifications', $field)));
        $now = now();

        DB::table('identity_verifications')
            ->whereIn('status', ['approved', 'rejected'])
            ->whereNull('documents_deleted_at')
            ->orderBy('id')
            ->get(array_merge(['id'], $fields))
            ->each(function ($verification) use ($fields, $now): void {
                foreach ($fields as $field) {
                    $path = $verification->{$field};
                    // Ancien stockage en fichier (les pièces récentes sont en base).
                    if ($path && ! str_starts_with($path, 'verification-documents/')) {
                        try {
                            Storage::disk(config('filesystems.default', 'public'))->delete($path);
                        } catch (\Throwable $error) {
                            report($error);
                        }
                    }
                }

                if (Schema::hasTable('identity_verification_documents')) {
                    DB::table('identity_verification_documents')->where('identity_verification_id', $verification->id)->delete();
                }

                DB::table('identity_verifications')->where('id', $verification->id)
                    ->update(array_fill_keys($fields, null) + ['documents_deleted_at' => $now, 'updated_at' => $now]);
            });
    }

    public function down(): void
    {
        // Des pièces effacées ne se restaurent pas.
    }
};
