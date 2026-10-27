<?php

namespace App\Support;

use App\Models\Ad;
use Illuminate\Http\Request;

class DemandPublicationContinuation
{
    public const SESSION_KEY = 'publication.resume_demand';

    public const PROVIDER_SESSION_KEY = 'publication.resume_provider';

    public const AD_SESSION_KEY = 'publication.contact_ad';

    public static function remember(Request $request): void
    {
        if (in_array($request->query('continue'), ['demand', 'provider', 'ad'], true)) {
            $request->session()->forget([self::SESSION_KEY, self::PROVIDER_SESSION_KEY, self::AD_SESSION_KEY]);
        }

        if ($request->query('continue') === 'demand') {
            $request->session()->forget(self::PROVIDER_SESSION_KEY);
            $request->session()->put(self::SESSION_KEY, true);
        } elseif ($request->query('continue') === 'provider') {
            $request->session()->forget(self::SESSION_KEY);
            $request->session()->put(self::PROVIDER_SESSION_KEY, true);
        } elseif ($request->query('continue') === 'ad') {
            $id = filter_var($request->query('ad'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($id && self::accessibleAd($id)) {
                $request->session()->put(self::AD_SESSION_KEY, $id);
            }
        }
    }

    public static function pendingAd(Request $request): ?Ad
    {
        $id = $request->session()->get(self::AD_SESSION_KEY);

        return is_int($id) ? self::accessibleAd($id) : null;
    }

    private static function accessibleAd(int $id): ?Ad
    {
        return Ad::marketplaceActive()
            ->where('visibility', 'public')
            ->whereHas('user', fn ($query) => $query->where('is_active', true))
            ->select(['id', 'title'])
            ->find($id);
    }

    public static function destination(Request $request): string
    {
        $resumeProvider = $request->session()->pull(self::PROVIDER_SESSION_KEY, false);
        $resumeDemand = $request->session()->pull(self::SESSION_KEY, false);
        $adId = $request->session()->pull(self::AD_SESSION_KEY);

        // Destinations fixes : les paramètres ne peuvent pas fournir une URL de retour.
        if ($resumeProvider) {
            return route('service-provider.start');
        }

        if ($resumeDemand) {
            return route('demand.create', ['resume' => 1]);
        }

        if (is_int($adId) && self::accessibleAd($adId)) {
            return route('ads.show', $adId).'#ad-contact-author';
        }

        return route('feed');
    }
}
