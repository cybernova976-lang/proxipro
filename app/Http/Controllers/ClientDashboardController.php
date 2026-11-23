<?php

namespace App\Http\Controllers;

use App\Models\IdentityVerification;
use App\Models\User;
use App\Services\ClientActivityService;
use App\Services\SavedSearchService;
use App\Support\ClientSpaceNavigation;
use App\Support\ProviderOfferLink;
use App\Support\TerritoryResolver;
use Illuminate\Http\Request;

class ClientDashboardController extends Controller
{
    public function __invoke(Request $request, ClientActivityService $activity, SavedSearchService $alerts, TerritoryResolver $territories)
    {
        $user = $request->user();

        if (ClientSpaceNavigation::role($request, $user) !== 'client') {
            return redirect()->route('pro.dashboard');
        }

        $section = $request->query('rubrique');
        $section = in_array($section, ['alertes', 'compte'], true) ? $section : 'activite';
        $data = ['section' => $section];

        if ($section === 'activite') {
            $summary = $activity->summary($user);
            $data += [
                'requests' => collect([$summary['primary']])->concat($summary['others'])->filter()->take(3)->values(),
                'requestsTotal' => $summary['total'],
                'unreadMessages' => $user->unreadMessagesCount(),
                'favoritesCount' => $user->savedAds()->count(),
            ];
        } else {
            $territory = $territories->forUser($user, (array) ($request->attributes->get('user_geo') ?? []));
            if ($section === 'alertes') {
                $data += [
                    'territory' => $territory,
                    'territoryAlert' => $territory && ! $alerts->hasActiveTerritoryOfferAlert($user, $territory)
                        ? $alerts->territoryOfferAlertFields($territory) : null,
                    'savedSearches' => $user->savedSearches()->orderByDesc('is_active')->latest()->orderByDesc('id')->limit(3)->get(),
                    'activeAlertCount' => $user->savedSearches()->where('is_active', true)->count(),
                    'unreadNotifications' => $user->unreadNotifications()->count(),
                ];
            } else {
                $employmentUrl = $territory ? route('employment.territory', ['territoire' => $territory->slug]) : route('employment.index');
                $data['groups'] = $this->accountLinks($user, $employmentUrl);
            }
        }

        return view('client-dashboard.index', $data);
    }

    private function accountLinks(User $user, string $employmentUrl): array
    {
        $status = IdentityVerification::where('user_id', $user->id)->latest()->first()?->status;
        $identityLabel = match ($status) {
            'returned' => 'Corriger ma vérification d’identité',
            'rejected' => 'Refaire ma vérification d’identité',
            'pending' => 'Suivre ma vérification d’identité',
            default => $user->hasVerifiedProfileBadge() ? 'Vérification d’identité' : 'Vérifier mon identité',
        };
        $account = [
            ['Mon profil', 'far fa-user', route('profile.show')],
            [$identityLabel, 'fas fa-shield-halved', route('verification.index')],
        ];
        if (blank($user->city) && blank($user->postal_code)) {
            $account[] = ['Indiquer ma commune', 'fas fa-location-dot', route('profile.edit').'#city'];
        }
        $account[] = ['Abonnement et factures', 'far fa-credit-card', route('billing.index')];
        $account[] = ['Paramètres', 'fas fa-gear', route('settings.index')];

        return [
            'Mon compte' => $account,
            'Services et emploi' => [
                ['Offres d’emploi', 'fas fa-briefcase', $employmentUrl],
                ['Proposer mes services', 'fas fa-screwdriver-wrench', ProviderOfferLink::url($user)],
            ],
            'Aide' => [
                ['Conseils pratiques', 'far fa-compass', route('guides.index')],
                ['Comment les offres sont classées', 'fas fa-arrow-down-wide-short', route('legal.platform-rules').'#classement'],
                ['Contacter l’assistance', 'fas fa-headset', route('contact.index')],
            ],
        ];
    }
}
