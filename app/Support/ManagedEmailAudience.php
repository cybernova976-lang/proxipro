<?php

namespace App\Support;

use App\Models\ManagedEmail;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Qui peut recevoir un message rédigé dans le studio e-mail.
 *
 *  - « newsletter » : message d'information commerciale. Il ne part qu'aux
 *    comptes qui y ont consenti (inscription à la newsletter, e-mails autorisés).
 *  - « service » : message d'information sur le fonctionnement du service
 *    (ouverture, mode d'emploi, changement de règles). Il part à tous les
 *    comptes actifs, parce qu'il ne contient aucun argument de vente : le
 *    comparatif des offres y est refusé (ManagedEmailController).
 *
 * Dans les deux cas, l'adresse doit avoir été confirmée par son titulaire.
 */
class ManagedEmailAudience
{
    public const NEWSLETTER = 'newsletter';

    public const SERVICE = 'service';

    public const TYPES = [self::NEWSLETTER, self::SERVICE];

    public static function normalize(mixed $type): string
    {
        return in_array($type, self::TYPES, true) ? $type : self::NEWSLETTER;
    }

    public static function query(string $type): Builder
    {
        $query = User::query()->where('is_active', true)->whereNotNull('email_verified_at');

        return $type === self::SERVICE
            ? $query
            : $query->where('newsletter_subscribed', true)->where('email_notifications', true);
    }

    /** Le destinataire peut-il encore recevoir ce message, à cette adresse ? */
    public static function stillReachable(ManagedEmail $email): bool
    {
        return self::query(self::normalize($email->type))
            ->whereKey($email->user_id)->where('email', $email->recipient_email)->exists();
    }

    /** Comptes actifs laissés de côté parce que leur adresse n'a pas été confirmée. */
    public static function unverifiedCount(): int
    {
        return User::query()->where('is_active', true)->whereNull('email_verified_at')->count();
    }
}
