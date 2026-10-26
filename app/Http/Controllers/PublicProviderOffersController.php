<?php

namespace App\Http\Controllers;

use App\Models\IdentityVerification;
use App\Support\PlatformFeatures;
use App\Support\PointPackCatalog;
use App\Support\ProviderSubscriptionPlans;
use Illuminate\Contracts\View\View;

class PublicProviderOffersController extends Controller
{
    public function index(): View
    {
        $subscriptionsEnabled = PlatformFeatures::proSubscriptionsEnabled();

        return view('pages.provider-offers', [
            'verificationPrice' => IdentityVerification::getVerificationPrice('profile_verification'),
            'commissionPercent' => max(0, min(100, (float) config('marketplace.commission_percent', 10))),
            'pointPacks' => PointPackCatalog::all(),
            'subscriptionPlans' => $subscriptionsEnabled
                ? array_filter(ProviderSubscriptionPlans::active(), fn (array $plan): bool => (float) ($plan['amount'] ?? 0) >= 0.5)
                : [],
        ]);
    }
}
