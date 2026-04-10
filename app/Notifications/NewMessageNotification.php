<?php

namespace App\Notifications;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewMessageNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Message $message,
        protected Conversation $conversation,
        protected User $sender
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $preview = \Illuminate\Support\Str::limit($this->message->content, 100);

        return (new MailMessage)
            ->subject("💬 Nouveau message de {$this->sender->name}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("**{$this->sender->name}** vous a envoyé un message :")
            ->line("\"{$preview}\"")
            ->action('Lire la conversation', url(route('messages.show', $this->conversation->id)))
            ->line('Vous pouvez répondre directement depuis la messagerie de la plateforme.')
            ->salutation('À bientôt sur ProxiPro !');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_message',
            'title' => '💬 Nouveau message',
            'message' => "{$this->sender->name} vous a envoyé un message",
            'preview' => \Illuminate\Support\Str::limit($this->message->content, 80),
            'icon' => 'fas fa-envelope',
            'color' => '#25d366',
            'action_url' => route('messages.show', $this->conversation->id),
            'conversation_id' => $this->conversation->id,
            'message_id' => $this->message->id,
            'sender_id' => $this->sender->id,
            'sender_name' => $this->sender->name,
            'sender_avatar' => $this->sender->avatar,
        ];
    }
}
