<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\ResetsPasswords;

class ResetPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset requests
    | and uses a simple trait to include this behavior. You're free to
    | explore this trait and override any methods you wish to tweak.
    |
    */

    use ResetsPasswords;

    /**
     * Where to redirect users after resetting their password.
     *
     * @var string
     */
    protected $redirectTo = '/feed';

    protected function rules()
    {
        return [
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', \Illuminate\Validation\Rules\Password::defaults()],
            'password_confirmation' => ['required', 'same:password'],
        ];
    }

    protected function validationErrorMessages()
    {
        return [
            'password_confirmation.required' => 'Veuillez confirmer votre nouveau mot de passe.',
            'password_confirmation.same' => 'La confirmation doit être identique au nouveau mot de passe.',
        ];
    }
}
