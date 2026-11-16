<?php

namespace App\Support;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * Envoi d'une notification qui ne doit jamais faire échouer l'action en cours.
 *
 * Une décision de l'administration (approuver une vérification, par exemple)
 * reste valable même si l'e-mail ou la notification push ne part pas : l'échec
 * est journalisé, l'action continue.
 */
final class SafeNotifier
{
    public static function send(?object $notifiable, Notification $notification): bool
    {
        if (! $notifiable) {
            return false;
        }

        try {
            $notifiable->notify($notification);

            return true;
        } catch (\Throwable $error) {
            Log::error('Notification non distribuée', [
                'notification' => $notification::class,
                'user_id' => $notifiable->id ?? null,
                'exception' => $error->getMessage(),
            ]);

            return false;
        }
    }
}
