<?php

namespace App\Rules;

use App\Models\BlockedEmail;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class EmailNotBlocked implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value) && BlockedEmail::isBlocked($value)) {
            $fail('Cette adresse e-mail n’est pas autorisée. Contactez le support si vous pensez qu’il s’agit d’une erreur.');
        }
    }
}
