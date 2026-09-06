<?php

namespace App\Support;

use Illuminate\Http\Request;

class DemandPublicationContinuation
{
    public const SESSION_KEY = 'publication.resume_demand';

    public static function remember(Request $request): void
    {
        if ($request->query('continue') === 'demand') {
            $request->session()->put(self::SESSION_KEY, true);
        }
    }

    public static function destination(Request $request): string
    {
        // Destination fixe : ne jamais accepter une URL de retour arbitraire.
        return $request->session()->pull(self::SESSION_KEY, false)
            ? route('demand.create', ['resume' => 1])
            : route('feed');
    }
}
