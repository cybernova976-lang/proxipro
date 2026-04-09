<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\WelcomeMail;
use App\Mail\EmailVerificationCode;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/feed';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
        $this->middleware('throttle:5,1'); // Max 5 registration attempts per minute
    }

    /**
     * Handle a registration request for the application.
     * Overridden to add honeypot & timing checks.
     */
    public function register(Request $request)
    {
        // ── Honeypot check: if the hidden field is filled, it's a bot ──
        if ($request->filled('website_url')) {
            Log::warning('Bot registration blocked (honeypot)', [
                'ip' => $request->ip(),
                'email' => $request->input('email'),
                'user_agent' => $request->userAgent(),
            ]);
            // Return a fake success to confuse bots
            return redirect($this->redirectPath())
                ->with('status', 'Inscription réussie !');
        }

        // ── Timing check: form filled too fast = bot ──
        $formRenderedAt = $request->input('_form_token');
        if ($formRenderedAt) {
            try {
                $renderedTime = (int) decrypt($formRenderedAt);
                $elapsed = time() - $renderedTime;
                if ($elapsed < 3) { // Less than 3 seconds = bot
                    Log::warning('Bot registration blocked (timing)', [
                        'ip' => $request->ip(),
                        'elapsed_seconds' => $elapsed,
                        'email' => $request->input('email'),
                    ]);
                    return redirect($this->redirectPath())
                        ->with('status', 'Inscription réussie !');
                }
            } catch (\Exception $e) {
                // Invalid token - might be tampered
                Log::warning('Invalid form token during registration', [
                    'ip' => $request->ip(),
                ]);
            }
        }

        // Proceed with normal registration (from RegistersUsers trait)
        $this->validator($request->all())->validate();
        
        event(new \Illuminate\Auth\Events\Registered($user = $this->create($request->all())));

        // Check if email verification is enabled in admin settings
        $verificationEnabled = true;
        try {
            $verificationEnabled = Setting::get('email_verification_enabled', '1') === '1';
        } catch (\Exception $e) {
            Log::warning('Could not read email_verification_enabled setting, defaulting to enabled', [
                'error' => $e->getMessage(),
            ]);
        }

        if ($verificationEnabled) {
            // Generate 6-digit verification code
            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $user->email_verification_code = Hash::make($code);
            $user->email_verification_code_expires_at = now()->addMinutes(15);
            $user->save();

            // Send verification code email synchronously (critical — must not be queued)
            $emailSent = false;
            try {
                Log::info('Sending verification code email', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'mail_driver' => config('mail.default'),
                    'mail_host' => config('mail.mailers.smtp.host'),
                    'queue_driver' => config('queue.default'),
                ]);
                Mail::to($user->email)->send(new EmailVerificationCode($code, $user->name));
                $emailSent = true;
                Log::info('Verification code email sent successfully', ['email' => $user->email]);
            } catch (\Exception $e) {
                Log::error('Verification code email FAILED: ' . $e->getMessage(), [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'exception' => get_class($e),
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            if ($emailSent) {
                // Redirect to code verification page
                return redirect()->route('verification.code.show', ['email' => $user->email])
                    ->with('success', 'Un code de vérification a été envoyé à votre adresse e-mail.');
            }

            // Email failed: still redirect to verification page so user can request a resend
            // Do NOT auto-verify — the code stays in DB for when email is resent
            Log::warning('Email send failed, redirecting to verification page for manual resend', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);
            return redirect()->route('verification.code.show', ['email' => $user->email])
                ->with('warning', 'L\'envoi du code a échoué. Veuillez cliquer sur "Renvoyer le code" pour réessayer.');
        } else {
            // Verification disabled: mark as verified immediately
            $user->email_verified_at = now();
            $user->save();
        }

        // Send welcome email
        try {
            Mail::to($user->email)->send(new WelcomeMail($user));
        } catch (\Exception $e) {
            Log::warning('Welcome email failed: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);
        }

        // Auto-login and redirect
        $this->guard()->login($user);

        if ($response = $this->registered($request, $user)) {
            return $response;
        }

        return redirect($this->redirectPath());
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        $rules = [
            'email' => ['required', 'string', 'email:rfc,dns', 'max:255', Rule::unique('users', 'email')->whereNull('deleted_at')],
            'password' => [
                'required',
                'string',
                'min:8',
                'max:40',
                'confirmed',
                function ($attribute, $value, $fail) use ($data) {
                    $normalizedPassword = mb_strtolower(trim((string) $value));

                    $commonPasswords = [
                        '123456', '12345678', '123456789', '1234567890', 'password', 'password123',
                        'qwerty', 'azerty', 'admin', 'admin123', 'welcome', 'letmein', 'iloveyou',
                        '000000', '111111', 'abc123', 'motdepasse', 'motdepasse123', 'proxipro',
                    ];

                    if (in_array($normalizedPassword, $commonPasswords, true)) {
                        $fail('Mot de passe trop courant. Choisissez un mot de passe plus unique.');
                        return;
                    }

                    $email = mb_strtolower(trim((string) ($data['email'] ?? '')));
                    $emailLocalPart = mb_strtolower(trim((string) strtok($email, '@')));

                    if ($normalizedPassword !== '' && ($normalizedPassword === $email || $normalizedPassword === $emailLocalPart)) {
                        $fail('Le mot de passe ne doit pas être identique à votre e-mail.');
                        return;
                    }

                    $nameCandidates = [
                        mb_strtolower(trim((string) ($data['firstname'] ?? ''))),
                        mb_strtolower(trim((string) ($data['lastname'] ?? ''))),
                        mb_strtolower(trim((string) ($data['company_name'] ?? ''))),
                        mb_strtolower(trim((string) (($data['firstname'] ?? '').' '.($data['lastname'] ?? '')))),
                    ];

                    foreach ($nameCandidates as $candidate) {
                        if ($candidate !== '' && $normalizedPassword === $candidate) {
                            $fail('Le mot de passe ne doit pas être identique à votre nom ou à votre entreprise.');
                            return;
                        }
                    }
                },
            ],
            'terms' => ['required', 'accepted'],
            // Honeypot: must be empty
            'website_url' => ['max:0'],
        ];

        // Validation selon le type de compte
        if (isset($data['account_type']) && $data['account_type'] === 'professionnel') {
            $rules['company_name'] = ['required', 'string', 'max:255'];
            $rules['business_type'] = ['required', 'in:entreprise,auto_entrepreneur'];
            $rules['siret'] = ['nullable', 'string', 'size:14'];
            $rules['sector'] = ['nullable', 'string', 'max:255'];
        } else {
            $rules['firstname'] = ['required', 'string', 'max:255'];
            $rules['lastname'] = ['required', 'string', 'max:255'];
        }

        return Validator::make($data, $rules, [
            'firstname.required' => 'Le prénom est obligatoire.',
            'lastname.required' => 'Le nom est obligatoire.',
            'company_name.required' => 'Le nom de l\'entreprise est obligatoire.',
            'business_type.required' => 'Veuillez choisir entre Entreprise ou Auto-entrepreneur.',
            'business_type.in' => 'Type d\'activité invalide.',
            'siret.size' => 'Le SIRET doit contenir exactement 14 chiffres.',

            'email.required' => 'L\'adresse e-mail est obligatoire.',
            'email.email' => 'Veuillez entrer une adresse e-mail valide.',
            'email.unique' => 'Cette adresse e-mail est déjà utilisée.',
            'password.required' => 'Le mot de passe est obligatoire.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.max' => 'Le mot de passe ne peut pas dépasser 40 caractères.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
            'terms.required' => 'Vous devez accepter les conditions d\'utilisation.',
            'terms.accepted' => 'Vous devez accepter les conditions d\'utilisation.',
            'website_url.max' => '',
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\Models\User
     */
    protected function create(array $data)
    {
        $isProfessionnel = isset($data['account_type']) && $data['account_type'] === 'professionnel';
        
        // Construire le nom selon le type de compte
        if ($isProfessionnel) {
            $name = $data['company_name'];
            $accountType = 'professionnel';
            $userType = 'professionnel';
            $businessType = $data['business_type'] ?? 'auto_entrepreneur';
            
            // Définir les limites selon le type de business
            $maxActiveAds = $businessType === 'entreprise' ? 20 : 10;
        } else {
            $name = trim($data['firstname'] . ' ' . $data['lastname']);
            $accountType = 'particulier';
            $userType = 'particulier';
            $businessType = null;
            $maxActiveAds = 5;
        }

        // Supprimer définitivement tout utilisateur soft-deleted avec le même email
        // pour libérer la contrainte UNIQUE avant la création
        $trashedUser = User::withTrashed()
            ->where('email', $data['email'])
            ->whereNotNull('deleted_at')
            ->first();
        
        if ($trashedUser) {
            Log::info('Force-deleting soft-deleted user to allow re-registration', [
                'old_user_id' => $trashedUser->id,
                'email' => $data['email'],
            ]);
            $trashedUser->forceDelete();
        }

        $user = User::create([
            'name' => $name,
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'phone' => $data['phone'] ?? null,
            'user_type' => $userType,
            'account_type' => $accountType,
            'business_type' => $businessType,
            'company_name' => $isProfessionnel ? $data['company_name'] : null,
            'siret' => $isProfessionnel ? ($data['siret'] ?? null) : null,
            'business_sector' => $isProfessionnel ? ($data['sector'] ?? null) : null,
            'service_category' => null,
            'service_subcategories' => null,
            'profession' => null,
            'newsletter_subscribed' => isset($data['newsletter']),
            'is_service_provider' => $isProfessionnel,
            'service_provider_since' => $isProfessionnel ? now() : null,
        ]);

        // Set protected fields explicitly (not mass-assignable for security)
        $user->role = 'user';
        $user->max_active_ads = $maxActiveAds;
        $user->is_active = true;
        $user->save();

        // Ajouter des points de bienvenue (5 points pour tous)
        if (class_exists(\App\Models\PointTransaction::class)) {
            try {
                $welcomePoints = 5; // 5 points gratuits à l'inscription
                
                \App\Models\PointTransaction::create([
                    'user_id' => $user->id,
                    'points' => $welcomePoints,
                    'type' => 'welcome_bonus',
                    'description' => 'Bonus de bienvenue à l\'inscription (5 points gratuits)',
                ]);
                
                // Créditer available_points et total_points (colonnes réelles)
                $user->increment('available_points', $welcomePoints);
                $user->increment('total_points', $welcomePoints);
            } catch (\Exception $e) {
                // Log error but don't fail registration
                \Log::error('Failed to add welcome points: ' . $e->getMessage());
            }
        }

        return $user;
    }
}
