<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WelcomeMail extends Mailable
{
    use SerializesModels;

    public User $user;
    public string $appName;
    public string $supportEmail;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->appName = config('app.name', 'Lunamars');
        $this->supportEmail = config('mail.reply_to.address')
            ?: config('mail.admin_email')
            ?: config('mail.from.address')
            ?: 'hello@example.com';
    }

    public function build()
    {
        return $this->subject('Bienvenue sur Lunamars')
            ->view('emails.welcome');
    }
}
