<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\EmailVerificationCode;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/feed';

    /**
     * Determine the redirect path after login.
     * All users go to feed. Professionals see onboarding modal on the feed page if needed.
     */
    protected function redirectTo()
    {
        return '/feed';
    }

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    /**
     * Validate the user login request (with reCAPTCHA).
     */
    protected function validateLogin(Request $request)
    {
        $rules = [
            $this->username() => 'required|string',
            'password' => 'required|string',
        ];

        $request->validate($rules);
    }

    /**
     * After authentication, check if email is verified.
     * If not: logout, generate a new code, send it, and redirect to verification page.
     */
    protected function authenticated(Request $request, $user)
    {
        try {
            // Check if email verification is enabled in admin settings
            $verificationEnabled = false; // Default to false for safety
            try {
                $verificationEnabled = Setting::get('email_verification_enabled', '1') === '1';
            } catch (\Throwable $e) {
                Log::warning('Could not read email_verification_enabled setting on login', [
                    'error' => $e->getMessage(),
                ]);
            }

            if ($verificationEnabled && is_null($user->email_verified_at)) {
                try {
                    $this->guard()->logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                } catch (\Throwable $e) {
                    Log::warning('Session invalidation failed on login verification', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                    ]);
                }

                // Generate a fresh verification code
                $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $user->email_verification_code = Hash::make($code);
                $user->email_verification_code_expires_at = now()->addMinutes(15);
                $user->save();

                $emailSent = false;
                try {
                    Log::info('Sending verification code email on login', [
                        'user_id' => $user->id,
                        'email' => $user->email,
                        'mail_driver' => config('mail.default'),
                    ]);
                    Mail::to($user->email)->send(new EmailVerificationCode($code, $user->name));
                    $emailSent = true;
                    Log::info('Verification code email sent on login', ['email' => $user->email]);
                } catch (\Throwable $e) {
                    Log::error('Verification code email failed on login', [
                        'user_id' => $user->id,
                        'email' => $user->email,
                        'exception_class' => get_class($e),
                        'exception_message' => $e->getMessage(),
                        'exception_trace' => $e->getTraceAsString(),
                    ]);
                }

                if ($emailSent) {
                    return redirect()->route('verification.code.show', ['email' => $user->email])
                        ->with('error', 'Veuillez d\'abord vérifier votre adresse e-mail. Un nouveau code vous a été envoyé.');
                }

                // Email failed: auto-verify to not block the user
                Log::warning('Email send failed on login, auto-verifying user', [
                    'user_id' => $user->id,
                ]);
                try {
                    $user->email_verified_at = now();
                    $user->email_verification_code = null;
                    $user->email_verification_code_expires_at = null;
                    $user->save();

                    // Re-login and continue
                    $this->guard()->login($user);
                } catch (\Throwable $e) {
                    Log::error('Auto-verify fallback failed on login', [
                        'user_id' => $user->id,
                        'exception_class' => get_class($e),
                        'exception_message' => $e->getMessage(),
                        'exception_trace' => $e->getTraceAsString(),
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::error('Login post-authentication failed, redirecting to feed anyway', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);
        }

        return redirect()->intended($this->redirectPath());
    }
}
