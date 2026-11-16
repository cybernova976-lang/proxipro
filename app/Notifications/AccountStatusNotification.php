<?php

namespace App\Notifications;

use App\Notifications\Concerns\RespectsEmailPreferences;
use App\Notifications\Concerns\SendsWebPushNotifications;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * Tout changement du statut d'un compte : Premium, droits d'administration,
 * vérification du profil, activation du compte.
 *
 * Le membre le voit dans ses notifications Prokejem, sur ses appareils où il
 * a activé les notifications, et par e-mail. L'e-mail l'invite à se connecter :
 * il ne contient aucune donnée sensible.
 */
class AccountStatusNotification extends Notification
{
    use RespectsEmailPreferences, SendsWebPushNotifications;

    /**
     * Événements qui touchent à l'accès au compte ou à ses droits : l'e-mail part
     * même si le membre a coupé les e-mails de notification (information de sécurité).
     */
    private const ALWAYS_EMAILED = ['account_deactivated', 'account_reactivated', 'admin_promoted', 'admin_revoked'];

    /** @param array{until?: string, plan?: string, privileges?: array<int, string>} $context */
    public function __construct(private readonly string $event, private readonly array $context = []) {}

    /** Envoi qui ne doit jamais faire échouer l'action de l'administrateur ou un paiement. */
    public static function dispatch(?object $user, string $event, array $context = []): void
    {
        if (! $user) {
            return;
        }

        try {
            $user->notify(new self($event, $context));
        } catch (\Throwable $error) {
            Log::error('Notification de statut du compte non distribuée', [
                'user_id' => $user->id ?? null, 'event' => $event, 'exception' => $error->getMessage(),
            ]);
        }
    }

    public function via(object $notifiable): array
    {
        $mail = in_array($this->event, self::ALWAYS_EMAILED, true)
            ? (filled($notifiable->email) ? ['mail'] : [])
            : $this->emailChannelsFor($notifiable);

        return array_merge(['database'], $mail, $this->webPushChannelsFor($notifiable));
    }

    public function toMail(object $notifiable): MailMessage
    {
        $content = $this->content();

        return (new MailMessage)
            ->subject($content['title'].' — '.config('app.name', 'Prokejem'))
            ->greeting('Bonjour '.$notifiable->name.',')
            ->line($content['message'])
            ->line('Connectez-vous à votre compte Prokejem pour consulter le détail.')
            ->action($content['cta'], $content['url'])
            ->line('Si vous pensez qu’il s’agit d’une erreur, répondez à cet e-mail ou écrivez-nous depuis la page Contact.');
    }

    public function toArray(object $notifiable): array
    {
        $content = $this->content();

        return [
            'type' => 'account_'.$this->event,
            'title' => $content['title'],
            'message' => $content['message'],
            'icon' => $content['icon'],
            'color' => $content['color'],
            'action_url' => $content['url'],
        ];
    }

    /** @return array{title: string, message: string, icon: string, color: string, url: string, cta: string} */
    private function content(): array
    {
        $until = filled($this->context['until'] ?? null) ? ' jusqu’au '.$this->context['until'] : '';
        $premium = ['icon' => 'fas fa-crown', 'color' => '#7c3aed', 'url' => route('pro.subscription'), 'cta' => 'Voir mon abonnement'];
        $admin = ['icon' => 'fas fa-user-shield', 'color' => '#4f46e5'];
        $profile = ['icon' => 'fas fa-id-badge', 'color' => '#4f46e5'];

        return match ($this->event) {
            'premium_granted' => $premium + ['title' => 'Premium activé',
                'message' => 'Un administrateur vous a accordé l’accès Premium'.$until.'. Aucun paiement ne vous est demandé.'],
            'premium_activated' => $premium + ['title' => 'Votre abonnement Premium est actif',
                'message' => 'Votre paiement est confirmé : votre abonnement Premium est actif'.$until.'.'],
            'premium_suspended' => $premium + ['title' => 'Premium suspendu',
                'message' => 'Votre accès Premium a été suspendu par un administrateur. Vos avantages Premium sont en pause.'],
            'premium_resumed' => $premium + ['title' => 'Premium réactivé',
                'message' => 'Votre accès Premium a été réactivé. Vous retrouvez tous vos avantages'.$until.'.'],
            'premium_cancelled' => $premium + ['title' => 'Premium arrêté',
                'message' => 'Votre accès Premium a été arrêté. Vous gardez votre espace prestataire et vos annonces.'],
            'premium_expired' => $premium + ['title' => 'Votre accès Premium est terminé',
                'message' => 'Votre accès Premium est arrivé à son terme. Vous pouvez le reprendre à tout moment depuis votre espace.'],
            'admin_promoted' => $admin + ['title' => 'Vous êtes administrateur',
                'message' => 'Vous avez été nommé administrateur de Prokejem.'.$this->privilegesSentence(),
                'url' => route('admin.dashboard'), 'cta' => 'Ouvrir l’administration'],
            'admin_privileges_updated' => $admin + ['title' => 'Vos droits d’administration ont changé',
                'message' => 'Vos droits d’administration ont été mis à jour.'.$this->privilegesSentence(),
                'url' => route('admin.dashboard'), 'cta' => 'Ouvrir l’administration'],
            'admin_revoked' => $admin + ['title' => 'Droits d’administration retirés',
                'message' => 'Vos droits d’administration sur Prokejem ont été retirés. Votre compte reste actif.',
                'url' => route('feed'), 'cta' => 'Ouvrir Prokejem'],
            'account_verified' => $profile + ['title' => 'Profil vérifié',
                'message' => 'Votre profil a été vérifié par un administrateur. Le badge « Profil vérifié » est affiché sur votre profil.',
                'url' => route('profile.show'), 'cta' => 'Voir mon profil'],
            'account_unverified' => $profile + ['title' => 'Vérification du profil retirée',
                'message' => 'La vérification de votre profil a été retirée par un administrateur. Vous pouvez refaire une demande.',
                'url' => route('verification.index'), 'cta' => 'Refaire une demande'],
            'account_deactivated' => ['icon' => 'fas fa-ban', 'color' => '#b91c1c', 'title' => 'Compte désactivé',
                'message' => 'Votre compte Prokejem a été désactivé par un administrateur.',
                'url' => route('contact.index'), 'cta' => 'Contacter Prokejem'],
            'account_reactivated' => ['icon' => 'fas fa-circle-check', 'color' => '#047857', 'title' => 'Compte réactivé',
                'message' => 'Votre compte Prokejem a été réactivé. Vous pouvez de nouveau vous connecter.',
                'url' => route('login'), 'cta' => 'Me connecter'],
            default => ['icon' => 'fas fa-bell', 'color' => '#4f46e5', 'title' => 'Votre compte a changé',
                'message' => 'Une modification a été apportée à votre compte Prokejem.',
                'url' => route('feed'), 'cta' => 'Ouvrir Prokejem'],
        };
    }

    private function privilegesSentence(): string
    {
        $labels = collect($this->context['privileges'] ?? [])
            ->map(fn (string $key) => config('admin.privileges.'.$key.'.label'))
            ->filter()
            ->values();

        return $labels->isEmpty() ? '' : ' Droits accordés : '.$labels->implode(', ').'.';
    }
}
