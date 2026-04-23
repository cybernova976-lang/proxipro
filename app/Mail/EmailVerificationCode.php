<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmailVerificationCode extends Mailable
{
    use SerializesModels;

    public string $code;
    public string $userName;
    public string $appName;
    public string $supportEmail;

    public function __construct(string $code, string $userName)
    {
        $this->code = $code;
        $this->userName = $userName;
        $this->appName = config('app.name', 'ProxiPro');
        $this->supportEmail = config('mail.reply_to.address')
            ?: config('mail.admin_email')
            ?: config('mail.from.address')
            ?: 'support@proxipro.fr';
    }

    public function build()
    {
        return $this->subject('Votre code de vérification ProxiPro')
            ->view('emails.verification-code');
    }
}
