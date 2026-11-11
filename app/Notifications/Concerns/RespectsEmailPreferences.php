<?php

namespace App\Notifications\Concerns;

trait RespectsEmailPreferences
{
    protected function emailChannelsFor(object $notifiable, bool $categoryEnabled = true): array
    {
        return $categoryEnabled && ($notifiable->email_notifications ?? true) && filled($notifiable->email)
            ? ['mail'] : [];
    }

    /** A queued message must respect a preference changed after it was queued. */
    public function shouldSend(object $notifiable, string $channel): bool
    {
        if ($channel !== 'mail') {
            return true;
        }

        $recipient = method_exists($notifiable, 'fresh') ? $notifiable->fresh() : $notifiable;

        return $recipient && in_array('mail', $this->via($recipient), true);
    }
}
