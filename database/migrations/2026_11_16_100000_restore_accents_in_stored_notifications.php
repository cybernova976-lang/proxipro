<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Les notifications de commandes et de propositions ont été enregistrées avec
 * des textes sans accents (« Paiement securise recu », « Proposition acceptee »).
 * Le code est corrigé ; cette migration corrige les notifications déjà en base.
 * Les transactions ne sont pas touchées : leur libellé sert de clé pour ne pas
 * enregistrer deux fois le même versement.
 *
 * Seuls le titre et le message sont réécrits, mot entier par mot entier.
 */
return new class extends Migration
{
    private const TYPES = [
        'App\\Notifications\\ServiceOrderStatusNotification',
        'App\\Notifications\\ServiceOrderRequestedNotification',
        'App\\Notifications\\ServiceProposalReceivedNotification',
        'App\\Notifications\\ServiceProposalStatusNotification',
    ];

    private const WORDS = [
        '/\bn a pas ete retenue\b/u' => 'n’a pas été retenue',
        '/\ba ete confirme\b/u' => 'a été confirmé',
        '/\ba ete\b/u' => 'a été',
        '/\bont ete\b/u' => 'ont été',
        '/\bsecurisee\b/u' => 'sécurisée',
        '/\bsecurise\b/u' => 'sécurisé',
        '/\bacceptee\b/u' => 'acceptée',
        '/\brefusee\b/u' => 'refusée',
        '/\bremboursee\b/u' => 'remboursée',
        '/\bliberes\b/u' => 'libérés',
        '/\bliberation\b/u' => 'libération',
        '/\bbloques\b/u' => 'bloqués',
        '/\bsécurisé recu\b/u' => 'sécurisé reçu',
        '/\bmaintenant regler\b/u' => 'maintenant régler',
        '/\bmise a jour\b/u' => 'mise à jour',
        '/\ba propose une commande\b/u' => 'a proposé une commande',
        '/\ba envoye\b/u' => 'a envoyé',
    ];

    public function up(): void
    {
        DB::table('notifications')->whereIn('type', self::TYPES)
            ->chunkById(200, function ($notifications): void {
                foreach ($notifications as $notification) {
                    $data = json_decode((string) $notification->data, true);
                    if (! is_array($data)) {
                        continue;
                    }

                    $original = $data;
                    foreach (['title', 'message'] as $field) {
                        if (is_string($data[$field] ?? null)) {
                            $data[$field] = preg_replace(array_keys(self::WORDS), array_values(self::WORDS), $data[$field]);
                        }
                    }

                    if ($data !== $original) {
                        DB::table('notifications')->where('id', $notification->id)
                            ->update(['data' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
                    }
                }
            });
    }

    public function down(): void
    {
        // Rien à défaire : seule l'orthographe de textes déjà affichés a été corrigée.
    }
};
