<?php

namespace App\Console\Commands;

use App\Models\IdentityVerification;
use Illuminate\Console\Command;

/**
 * Efface les pièces des demandes de vérification déjà tranchées (validées ou
 * refusées) avant que l'effacement après décision n'existe. Les décisions prises
 * depuis le 26/11/2026 effacent leurs pièces d'elles-mêmes.
 *
 * Sans --force, la commande n'efface rien : elle compte seulement les demandes concernées.
 */
class DeleteDecidedVerificationDocuments extends Command
{
    protected $signature = 'verifications:delete-decided-documents {--force : Effacer réellement les pièces}';

    protected $description = 'Efface les pièces des demandes de vérification déjà validées ou refusées';

    public function handle(): int
    {
        $query = IdentityVerification::query()
            ->whereIn('status', ['approved', 'rejected'])
            ->whereNull('documents_deleted_at');

        $count = (clone $query)->count();

        if (! $this->option('force')) {
            $this->info($count.' demande(s) tranchée(s) gardent encore leurs pièces. Relancez avec --force pour les effacer.');

            return self::SUCCESS;
        }

        $deleted = 0;
        $query->orderBy('id')->chunkById(50, function ($verifications) use (&$deleted): void {
            foreach ($verifications as $verification) {
                $verification->deleteDocumentsAfterDecision();
                $deleted++;
            }
        });

        $this->info($deleted.' demande(s) : pièces effacées.');

        return self::SUCCESS;
    }
}
