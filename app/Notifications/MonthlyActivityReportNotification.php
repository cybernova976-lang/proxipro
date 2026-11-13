<?php

namespace App\Notifications;

use App\Notifications\Concerns\RespectsEmailPreferences;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Rapport d'activité du mois écoulé, envoyé aux abonnés Premium.
 * Ne contient que des chiffres mesurés et non nuls.
 */
class MonthlyActivityReportNotification extends Notification
{
    use Queueable, RespectsEmailPreferences;

    /** @param array<string, mixed> $report Résultat de ProviderActivityReport::forMonth() */
    public function __construct(protected array $report) {}

    public function via(object $notifiable): array
    {
        return ['database', ...$this->emailChannelsFor($notifiable, (bool) ($notifiable->pro_notifications_email ?? true))];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Votre rapport d’activité Prokejem — '.$this->report['label'])
            ->greeting('Bonjour '.$notifiable->name.',')
            ->line('Voici votre activité mesurée sur Prokejem en '.$this->report['label'].' :');

        foreach ($this->highlights() as $line) {
            $mail->line('• '.$line);
        }

        return $mail
            ->action('Voir le rapport complet', $this->url())
            ->line('Ce rapport fait partie de votre abonnement Premium. Chaque chiffre est compté par la plateforme, aucun n’est estimé.')
            ->line('Vous pouvez arrêter cet e-mail dans Paramètres › Notifications.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'monthly_activity_report',
            'icon' => 'fas fa-chart-column',
            'color' => '#4f46e5',
            'title' => 'Rapport d’activité — '.$this->report['label'],
            'message' => implode(' · ', array_slice($this->highlights(), 0, 3)),
            'action_url' => $this->url(),
            'month' => $this->report['month']->format('Y-m'),
        ];
    }

    /** @return array<int, string> Chiffres mesurés et non nuls, dans l'ordre du rapport. */
    private function highlights(): array
    {
        $lines = [];
        foreach ($this->report['sections'] as $section) {
            foreach ($section['metrics'] as $metric) {
                if ($metric['value'] === null || $metric['value'] <= 0) {
                    continue;
                }
                $value = $metric['unit'] === '€'
                    ? number_format((float) $metric['value'], 2, ',', ' ').' €'
                    : rtrim(rtrim(number_format((float) $metric['value'], 1, ',', ' '), '0'), ',').($metric['unit'] ? ' '.$metric['unit'] : '');
                $lines[] = $metric['label'].' : '.$value;
            }
        }

        return $lines;
    }

    private function url(): string
    {
        return route('pro.report', ['mois' => $this->report['month']->format('Y-m')]);
    }
}
