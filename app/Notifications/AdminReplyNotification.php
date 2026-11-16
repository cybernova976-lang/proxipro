<?php

namespace App\Notifications;

use App\Models\ContactMessage;
use App\Notifications\Concerns\SendsWebPushNotifications;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Réponse de l'équipe Prokejem à un message envoyé depuis la page Contact.
 *
 * La réponse elle-même part par e-mail (App\Mail\ContactMessageReply) : cette
 * notification la signale dans le compte du membre et sur ses appareils, sans
 * envoyer un second e-mail.
 */
class AdminReplyNotification extends Notification
{
    use SendsWebPushNotifications;

    public function __construct(private readonly ContactMessage $contactMessage) {}

    /** Envoi qui ne doit jamais faire échouer la réponse de l'administrateur. */
    public static function dispatch(?object $user, ContactMessage $contactMessage): void
    {
        if (! $user) {
            return;
        }

        try {
            $user->notify(new self($contactMessage));
        } catch (\Throwable $error) {
            Log::error('Notification de réponse de l’administration non distribuée', [
                'user_id' => $user->id ?? null, 'contact_message_id' => $contactMessage->id, 'exception' => $error->getMessage(),
            ]);
        }
    }

    public function via(object $notifiable): array
    {
        return array_merge(['database'], $this->webPushChannelsFor($notifiable));
    }

    public function toArray(object $notifiable): array
    {
        $subject = Str::limit((string) $this->contactMessage->subject, 60);

        return [
            'type' => 'admin_reply',
            'title' => 'Prokejem vous a répondu',
            'message' => 'L’équipe Prokejem a répondu à votre message'.($subject !== '' ? ' « '.$subject.' »' : '')
                .'. La réponse vous a été envoyée par e-mail.',
            'icon' => 'fas fa-headset',
            'color' => '#4f46e5',
            'action_url' => route('contact.index'),
            'contact_message_id' => $this->contactMessage->id,
        ];
    }
}
