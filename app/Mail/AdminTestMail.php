<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminTestMail extends Mailable
{
    use SerializesModels;

    public array $details;

    public function __construct(array $details)
    {
        $this->details = $details;
    }

    public function build()
    {
        return $this->subject('Test e-mail ProxiPro')
            ->replyTo(
                $this->details['reply_to_address'] ?? config('mail.reply_to.address'),
                $this->details['reply_to_name'] ?? config('mail.reply_to.name')
            )
            ->view('emails.admin.test-mail');
    }
}