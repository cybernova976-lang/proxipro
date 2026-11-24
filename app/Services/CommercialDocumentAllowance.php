<?php

namespace App\Services;

use App\Models\ProInvoice;
use App\Models\ProQuote;
use App\Models\User;
use App\Support\PlatformFeatures;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Devis et factures offerts (décision du fondateur du 24/11/2026) : tout membre
 * connecté, particulier ou professionnel, sans justificatif ni immatriculation,
 * crée et télécharge 2 devis et 2 factures gratuits. S'il veut aller plus loin,
 * il s'abonne : un abonnement Premium actif lève la limite.
 *
 * Les compteurs sont mesurés sur les documents réellement enregistrés du membre
 * (aucun chiffre estimé). Un avoir corrige une facture déjà émise : il n'est pas
 * compté et n'est jamais bloqué. Une facture d'acompte est une facture : elle compte.
 */
class CommercialDocumentAllowance
{
    public const QUOTE = 'quote';

    public const INVOICE = 'invoice';

    /** Nombre de documents offerts, par type. */
    public const FREE = [self::QUOTE => 2, self::INVOICE => 2];

    /** Phrase neutre quand l'abonnement Premium n'est pas encore ouvert à la souscription. */
    public const SUBSCRIPTIONS_CLOSED = 'L’abonnement n’est pas encore ouvert.';

    /**
     * État du quota pour l'affichage et les contrôles.
     *
     * @return array{type: string, limit: int, used: int, remaining: int, unlimited: bool, can_create: bool}
     */
    public function for(User $user, string $type): array
    {
        $limit = self::FREE[$type];
        $used = $this->used($user, $type);
        $remaining = max(0, $limit - $used);
        $unlimited = $user->hasActiveProSubscription();

        return [
            'type' => $type,
            'limit' => $limit,
            'used' => $used,
            'remaining' => $remaining,
            'unlimited' => $unlimited,
            'can_create' => $unlimited || $remaining > 0,
        ];
    }

    public function canCreate(User $user, string $type): bool
    {
        return $this->for($user, $type)['can_create'];
    }

    /** Documents du membre qui comptent dans le quota, tels qu'enregistrés en base. */
    public function used(User $user, string $type): int
    {
        return $this->countedDocuments($user, $type)->count();
    }

    /**
     * Un brouillon reçoit son numéro définitif s'il fait partie des documents
     * offerts du membre (ses 2 premiers de ce type), ou si le membre est abonné.
     * Un document préparé avant la règle au-delà des 2 offerts attend donc l'abonnement.
     */
    public function canIssue(User $user, ProQuote|ProInvoice $document): bool
    {
        if ($document instanceof ProInvoice && $document->isCreditNote()) {
            return true;
        }
        if ($user->hasActiveProSubscription()) {
            return true;
        }

        $type = $document instanceof ProQuote ? self::QUOTE : self::INVOICE;
        $rank = $this->countedDocuments($user, $type)->where('id', '<=', $document->id)->count();

        return $rank <= self::FREE[$type];
    }

    /**
     * L'abonnement Premium peut être souscrit. Tant qu'il est fermé, aucun message
     * ne le promet : le quota s'applique de la même façon, sans renvoi vers Premium.
     */
    public function subscriptionsOpen(): bool
    {
        return PlatformFeatures::proSubscriptionsEnabled();
    }

    /** Message affiché quand la création est refusée. */
    public function limitReachedMessage(string $type): string
    {
        $used = $type === self::QUOTE
            ? 'Vous avez utilisé vos '.self::FREE[self::QUOTE].' devis gratuits.'
            : 'Vous avez utilisé vos '.self::FREE[self::INVOICE].' factures gratuites.';

        if (! $this->subscriptionsOpen()) {
            return $used.' '.self::SUBSCRIPTIONS_CLOSED;
        }

        return $used.($type === self::QUOTE
            ? ' Passez à Premium pour créer des devis sans limite.'
            : ' Passez à Premium pour créer des factures sans limite.');
    }

    /** Message affiché quand l'émission d'un document au-delà des offerts est refusée. */
    public function issueRefusedMessage(string $type): string
    {
        $refused = $type === self::QUOTE
            ? 'Ce devis dépasse vos '.self::FREE[self::QUOTE].' devis gratuits : il reste en brouillon.'
            : 'Cette facture dépasse vos '.self::FREE[self::INVOICE].' factures gratuites : elle reste en brouillon.';

        return $refused.' '.($this->subscriptionsOpen() ? 'Passez à Premium pour l’émettre.' : self::SUBSCRIPTIONS_CLOSED);
    }

    private function countedDocuments(User $user, string $type): HasMany|Builder
    {
        return $type === self::QUOTE
            ? $user->proQuotes()
            : $user->proInvoices()->where('kind', '!=', ProInvoice::KIND_CREDIT_NOTE);
    }
}
