<?php

namespace App\Notifications;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewMessageNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Message $message,
        protected Conversation $conversation,
        protected User $sender
    ) {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable->email_notifications !== false && filled($notifiable->email)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function viaConnections(): array
    {
        return [
            'database' => 'sync',
            'mail' => config('queue.default', 'database'),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $preview = \Illuminate\Support\Str::limit($this->message->content, 100);
        $supportEmail = config('mail.reply_to.address')
            ?: config('mail.admin_email')
            ?: config('site.support_email')
            ?: config('mail.from.address');

        return (new MailMessage)
            ->subject("💬 Nouveau message de {$this->sender->name}")
            ->view('emails.notifications.new-message', [
                'appName' => config('app.name', 'Lunamars'),
                'supportEmail' => $supportEmail,
                'recipientName' => $notifiable->name,
                'senderName' => $this->sender->name,
                'preview' => $preview,
                'conversationUrl' => url(route('messages.show', $this->conversation->id)),
            ]);
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
