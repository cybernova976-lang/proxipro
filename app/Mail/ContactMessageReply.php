<?php

namespace App\Mail;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class ContactMessageReply extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ContactMessage $contactMessage,
        public string $reply,
    ) {
    }

    public function build(): self
    {
        $subject = preg_replace('/[\r\n]+/', ' ', (string) $this->contactMessage->subject) ?: 'Votre message';
        $fromAddress = (string) config('mail.contact_from.address');
        $fromName = (string) config('mail.contact_from.name', config('app.name'));

        return $this
            ->from($fromAddress, $fromName)
            ->replyTo($fromAddress, $fromName)
            ->subject('Re : '.Str::limit($subject, 140, '…'))
            ->view('emails.contact-message-reply');
    }
}
