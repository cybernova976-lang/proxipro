<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset emails and
    | includes a trait which assists in sending these notifications from
    | your application to your users. Feel free to explore this trait.
    |
    */

    use SendsPasswordResetEmails;

    public function sendResetLinkEmail(Request $request)
    {
        $this->validateEmail($request);

        try {
            $response = $this->broker()->sendResetLink(
                $this->credentials($request)
            );
        } catch (\Throwable $exception) {
            Log::error('Password reset email failed to send.', [
                'email' => $request->input('email'),
                'message' => $exception->getMessage(),
            ]);

            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => 'Impossible d\'envoyer l\'e-mail de réinitialisation pour le moment. Réessayez dans quelques instants.',
                ]);
        }

        return $response == Password::RESET_LINK_SENT
            ? $this->sendResetLinkResponse($request, $response)
            : $this->sendResetLinkFailedResponse($request, $response);
    }

    /**
     * Confirmation d'envoi.
     *
     * Laravel ne renvoie que le message de statut. On y ajoute l'adresse
     * saisie afin que la page puisse l'afficher : c'est ce qui permet a
     * l'utilisateur de reperer immediatement une faute de frappe, cause la
     * plus frequente d'un e-mail « jamais recu ».
     *
     * L'adresse affichee est celle que l'utilisateur vient de taper, jamais
     * une donnee tiree de la base : cela ne revele donc pas si un compte
     * existe. Blade l'echappe a l'affichage.
     */
    protected function sendResetLinkResponse(Request $request, $response)
    {
        return back()->with([
            'status' => trans($response),
            'reset_link_email' => $request->input('email'),
        ]);
    }
}
