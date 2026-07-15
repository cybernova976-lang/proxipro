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
use Illuminate\Validation\Rule;

class EmailVerificationCodeController extends Controller
{
    public const PENDING_USER_SESSION_KEY = 'pending_email_verification_user_id';

    /**
     * Show the verification code form.
     */
    public function show(Request $request)
    {
        $pendingUser = $this->pendingUser($request);
        $email = $pendingUser?->email ?? trim((string) $request->query('email'));

        if ($email === '') {
            return redirect()->route('register');
        }

        return view('auth.verify-code', [
            'email' => $email,
            'canChangeEmail' => $pendingUser !== null,
        ]);
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

        if (! $user) {
            return back()->with('error', 'Aucun compte trouvé avec cette adresse e-mail.');
        }

        if ($user->email_verified_at) {
            return redirect()->route('login')
                ->with('success', 'Votre e-mail est déjà vérifié. Connectez-vous.');
        }

        // Check expiration
        if (! $user->email_verification_code_expires_at || now()->gt($user->email_verification_code_expires_at)) {
            return back()
                ->withInput()
                ->with('error', 'Le code a expiré. Veuillez en demander un nouveau.');
        }

        // Verify the code
        if (! $user->email_verification_code || ! Hash::check($request->code, $user->email_verification_code)) {
            return back()
                ->withInput()
                ->with('error', 'Code incorrect. Veuillez vérifier et réessayer.');
        }

        // Mark email as verified
        $user->email_verified_at = now();
        $user->email_verification_code = null;
        $user->email_verification_code_expires_at = null;
        $user->save();
        $request->session()->forget(self::PENDING_USER_SESSION_KEY);

        // Send welcome email now that the email is verified
        try {
            Mail::to($user->email)->send(new WelcomeMail($user));
        } catch (\Exception $e) {
            Log::warning('Welcome email failed: '.$e->getMessage(), [
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

        if (! $user) {
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
            Log::warning('Verification code resend failed: '.$e->getMessage(), [
                'user_id' => $user->id,
            ]);

            return back()->with('error', 'Erreur lors de l\'envoi. Veuillez réessayer.');
        }

        return back()->with('success', 'Un nouveau code a été envoyé à votre adresse e-mail.');
    }

    /**
     * Correct the email address for the unverified account bound to this session.
     */
    public function updateEmail(Request $request)
    {
        $user = $this->pendingUser($request);

        if (! $user) {
            return redirect()->route('register')
                ->with('error', 'Votre session de vérification a expiré. Reconnectez-vous pour modifier votre adresse e-mail.');
        }

        $request->merge([
            'email' => mb_strtolower(trim((string) $request->input('email'))),
        ]);

        $validated = $request->validate([
            'email' => [
                'required',
                'string',
                'email:rfc',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
        ], [
            'email.required' => 'Saisissez votre nouvelle adresse e-mail.',
            'email.email' => 'Saisissez une adresse e-mail valide.',
            'email.unique' => 'Cette adresse e-mail est déjà utilisée par un autre compte.',
        ]);

        $newEmail = $validated['email'];

        if (strcasecmp($newEmail, $user->email) === 0) {
            return back()
                ->withErrors(['email' => 'La nouvelle adresse doit être différente de l’adresse actuelle.'])
                ->withInput();
        }

        $previousEmail = $user->email;
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->forceFill([
            'email' => $newEmail,
            'email_verified_at' => null,
            'email_verification_code' => Hash::make($code),
            'email_verification_code_expires_at' => now()->addMinutes(15),
        ])->save();

        try {
            Mail::to($newEmail)->send(new EmailVerificationCode($code, $user->name));
        } catch (\Throwable $e) {
            Log::error('Verification email failed after address correction', [
                'user_id' => $user->id,
                'previous_email' => $previousEmail,
                'new_email' => $newEmail,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('verification.code.show', ['email' => $newEmail])
                ->with('error', 'L’adresse a bien été corrigée, mais l’envoi a échoué. Utilisez « Renvoyer le code » pour réessayer.');
        }

        Log::info('Unverified user corrected their email address', [
            'user_id' => $user->id,
            'previous_email' => $previousEmail,
            'new_email' => $newEmail,
        ]);

        return redirect()->route('verification.code.show', ['email' => $newEmail])
            ->with('success', 'Adresse e-mail corrigée. Un nouveau code de vérification vient de vous être envoyé.');
    }

    private function pendingUser(Request $request): ?User
    {
        $userId = $request->session()->get(self::PENDING_USER_SESSION_KEY);

        if (! $userId) {
            return null;
        }

        $user = User::find($userId);

        if (! $user || $user->email_verified_at) {
            $request->session()->forget(self::PENDING_USER_SESSION_KEY);

            return null;
        }

        return $user;
    }
}
