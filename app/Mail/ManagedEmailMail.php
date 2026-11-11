<?php

namespace App\Mail;

use App\Models\ManagedEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class ManagedEmailMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ManagedEmail $managedEmail) {}

    public function build(): self
    {
        return $this->subject($this->managedEmail->subject)
            ->view('emails.managed.standard', [
                'email' => $this->managedEmail,
                'unsubscribeUrl' => $this->managedEmail->type === 'newsletter' && $this->managedEmail->user_id
                    ? URL::signedRoute('newsletter.unsubscribe', ['user' => $this->managedEmail->user_id]) : null,
                'supportEmail' => config('mail.reply_to.address')
                    ?: config('mail.admin_email')
                    ?: config('mail.from.address'),
            ]);
    }
}
