<?php

namespace App\Http\Controllers;

use App\Support\PlatformFeatures;
use App\Support\ProviderSubscriptionPlans;
use Illuminate\Contracts\View\View;

class PublicProviderOffersController extends Controller
{
    public function index(): View
    {
        $subscriptionsEnabled = PlatformFeatures::proSubscriptionsEnabled();

        return view('pages.provider-offers', [
            'commissionPercent' => \App\Support\MarketplaceCommission::percent(),
            'subscriptionPlans' => $subscriptionsEnabled
                ? array_filter(ProviderSubscriptionPlans::active(), fn (array $plan): bool => (float) ($plan['amount'] ?? 0) >= 0.5)
                : [],
        ]);
    }
}
