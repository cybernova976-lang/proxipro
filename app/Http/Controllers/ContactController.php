<?php

namespace App\Http\Controllers;

use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    /**
     * Show contact form
     */
    public function index()
    {
        return view('contact.index');
    }

    /**
     * Store contact message
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|min:10|max:5000',
            'website' => 'nullable|max:0',
        ]);

        $contactMessage = ContactMessage::create([
            'user_id' => Auth::id(),
            'name' => $request->name,
            'email' => $request->email,
            'subject' => $request->subject,
            'message' => $request->message,
            'status' => 'pending',
        ]);

        $this->alertTeam($contactMessage);

        return redirect()->route('contact.index')->with('success', 'Votre message a bien été enregistré.');
    }

    /**
     * Prévient l'équipe par e-mail : adresse admin, plus l'adresse de copie si
     * elle est renseignée dans les paramètres. Le message est déjà enregistré :
     * un envoi en échec est journalisé et ne gêne jamais le visiteur.
     */
    private function alertTeam(ContactMessage $contactMessage): void
    {
        if (Setting::get('email_new_message', '1') !== '1') {
            return;
        }

        $recipient = (string) config('mail.admin_email');
        if (! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $copy = trim((string) Setting::get('mail_admin_copy_address', ''));

        try {
            $mail = Mail::to($recipient);
            if (filter_var($copy, FILTER_VALIDATE_EMAIL) && strcasecmp($copy, $recipient) !== 0) {
                $mail->cc($copy);
            }
            $mail->send(new ContactMessageReceived($contactMessage));
        } catch (\Throwable $error) {
            Log::error('Alerte de message de contact non envoyée', [
                'contact_message_id' => $contactMessage->id,
                'exception' => $error->getMessage(),
            ]);
        }
    }

    /**
     * Show user's messages (for authenticated users)
     */
    public function myMessages()
    {
        $messages = ContactMessage::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('contact.my-messages', compact('messages'));
    }
}
