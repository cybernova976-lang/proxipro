<?php

namespace App\Mail;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

/**
 * Alerte envoyée à l'équipe quand un visiteur écrit par le formulaire de contact.
 *
 * L'adresse de réponse est celle du visiteur : répondre depuis la boîte mail
 * lui écrit directement. Répondre depuis l'administration de Prokejem reste
 * préférable, car la réponse y est conservée avec le message.
 */
class ContactMessageReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ContactMessage $contactMessage) {}

    public function build(): self
    {
        $subject = preg_replace('/[\r\n]+/', ' ', (string) $this->contactMessage->subject) ?: 'Sans objet';

        $visitorEmail = (string) $this->contactMessage->email;
        $visitorName = (string) $this->contactMessage->name;

        return $this
            ->replyTo($visitorEmail, $visitorName)
            // L'adresse de réponse globale (contact@…) s'ajouterait à celle du visiteur :
            // on la remplace, pour que « Répondre » écrive au visiteur et à lui seul.
            ->withSymfonyMessage(fn (\Symfony\Component\Mime\Email $email) => $email->replyTo(
                new \Symfony\Component\Mime\Address($visitorEmail, $visitorName)
            ))
            ->subject('Nouveau message de contact : '.Str::limit($subject, 120, '…'))
            ->view('emails.contact-message-received', [
                'adminUrl' => route('admin.contact-messages.show', $this->contactMessage->id),
            ]);
    }
}
