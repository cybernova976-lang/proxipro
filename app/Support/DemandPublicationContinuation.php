<?php

namespace App\Support;

use Illuminate\Http\Request;

class DemandPublicationContinuation
{
    public const SESSION_KEY = 'publication.resume_demand';

    public const PROVIDER_SESSION_KEY = 'publication.resume_provider';

    public static function remember(Request $request): void
    {
        if ($request->query('continue') === 'demand') {
            $request->session()->forget(self::PROVIDER_SESSION_KEY);
            $request->session()->put(self::SESSION_KEY, true);
        } elseif ($request->query('continue') === 'provider') {
            $request->session()->forget(self::SESSION_KEY);
            $request->session()->put(self::PROVIDER_SESSION_KEY, true);
        }
    }

    public static function destination(Request $request): string
    {
        $resumeProvider = $request->session()->pull(self::PROVIDER_SESSION_KEY, false);
        $resumeDemand = $request->session()->pull(self::SESSION_KEY, false);

        // Destinations fixes : les paramètres ne peuvent pas fournir une URL de retour.
        if ($resumeProvider) {
            return route('service-provider.start');
        }

        return $resumeDemand
            ? route('demand.create', ['resume' => 1])
            : route('feed');
    }
}
