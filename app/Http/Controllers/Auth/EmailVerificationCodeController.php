<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\EmailVerificationCode;
use App\Mail\WelcomeMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailVerificationCodeController extends Controller
{
    /**
     * Show the verification code form.
     */
    public function show(Request $request)
    {
        $email = $request->query('email');

        if (!$email) {
            return redirect()->route('register');
        }

        return view('auth.verify-code', ['email' => $email]);
    }

    /**
     * Verify the submitted code.
     */
    public function verify(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return back()->with('error', 'Aucun compte trouvé avec cette adresse e-mail.');
        }

        if ($user->email_verified_at) {
            return redirect()->route('login')
                ->with('success', 'Votre e-mail est déjà vérifié. Connectez-vous.');
        }

        // Check expiration
        if (!$user->email_verification_code_expires_at || now()->gt($user->email_verification_code_expires_at)) {
            return back()
                ->withInput()
                ->with('error', 'Le code a expiré. Veuillez en demander un nouveau.');
        }

        // Verify the code
        if (!$user->email_verification_code || !Hash::check($request->code, $user->email_verification_code)) {
            return back()
                ->withInput()
                ->with('error', 'Code incorrect. Veuillez vérifier et réessayer.');
        }

        // Mark email as verified
        $user->email_verified_at = now();
        $user->email_verification_code = null;
        $user->email_verification_code_expires_at = null;
        $user->save();

        // Send welcome email now that the email is verified
        try {
            Mail::to($user->email)->send(new WelcomeMail($user));
        } catch (\Exception $e) {
            Log::warning('Welcome email failed: ' . $e->getMessage(), [
                'user_id' => $user->id,
            ]);
        }

        return redirect()->route('login')
            ->with('success', 'Votre adresse e-mail a été vérifiée avec succès ! Vous pouvez maintenant vous connecter.');
    }

    /**
     * Resend a new verification code.
     */
    public function resend(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return back()->with('error', 'Aucun compte trouvé avec cette adresse e-mail.');
        }

        if ($user->email_verified_at) {
            return redirect()->route('login')
                ->with('success', 'Votre e-mail est déjà vérifié.');
        }

        // Generate new code
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $user->email_verification_code = Hash::make($code);
        $user->email_verification_code_expires_at = now()->addMinutes(15);
        $user->save();

        try {
            Mail::to($user->email)->send(new EmailVerificationCode($code, $user->name));
        } catch (\Exception $e) {
            Log::warning('Verification code resend failed: ' . $e->getMessage(), [
                'user_id' => $user->id,
            ]);
            return back()->with('error', 'Erreur lors de l\'envoi. Veuillez réessayer.');
        }

        return back()->with('success', 'Un nouveau code a été envoyé à votre adresse e-mail.');
    }
}
