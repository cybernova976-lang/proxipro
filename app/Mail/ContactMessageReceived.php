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

        // Cette alerte arrive dans la boîte contact@ : elle ne doit pas en partir.
        // Un message « de soi à soi » fait ignorer l'adresse de réponse à
        // certaines messageries (Gmail notamment), et « Répondre » écrirait alors
        // à contact@ au lieu du visiteur. Elle part donc de l'adresse générale,
        // au nom du visiteur ; le visiteur, lui, ne voit que contact@ (la boîte
        // d'où part la réponse).
        $generalFrom = (string) config('mail.from.address');
        if (filter_var($generalFrom, FILTER_VALIDATE_EMAIL)) {
            $displayName = trim(preg_replace('/[\r\n"<>]+/', ' ', Str::limit($visitorName, 60, '')));
            $this->from($generalFrom, trim($displayName.' via '.config('app.name', 'Prokejem')));
        }

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
