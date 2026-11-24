<?php

namespace App\Services;

use App\Models\DemandDraft;
use App\Models\User;
use App\Support\ClientSpaceNavigation;
use Illuminate\Http\Request;

/**
 * Page « Mes annonces » : toutes les publications du membre, offres et demandes,
 * en ligne ou non, avec le brouillon de demande en cours.
 *
 * Vocabulaire (règle du 24/11/2026) : une demande = « je cherche un prestataire »,
 * une offre = « je présente mon activité », une annonce = l'une ou l'autre.
 */
class MemberPublicationsService
{
    public function data(User $user, Request $request): array
    {
        $state = in_array($request->query('state'), ['active', 'pending', 'closed', 'drafts'], true)
            ? $request->query('state') : 'all';
        $canOffer = $user->isServiceProvider() || $user->isProfessionnel();
        // Le filtre par type ne sert qu'à qui peut publier les deux.
        $type = $canOffer && in_array($request->query('type'), ['offre', 'demande'], true)
            ? $request->query('type') : 'all';

        $base = $user->ads();
        if ($type === 'offre') {
            $base->where('service_type', '!=', 'demande');
        } elseif ($type === 'demande') {
            $base->where('service_type', 'demande');
        }
        $active = (clone $base)->marketplaceActive();
        $pending = (clone $base)->where('status', 'pending');
        $counts = [
            'all' => (clone $base)->count(),
            'active' => (clone $active)->count(),
            'pending' => (clone $pending)->count(),
        ];
        // Le brouillon est toujours une demande : il n'a pas sa place parmi les offres.
        $draft = $type === 'offre' ? null : DemandDraft::where('user_id', $user->id)->first();
        $counts['closed'] = $counts['all'] - $counts['active'] - $counts['pending'];
        $counts['drafts'] = $draft ? 1 : 0;
        $counts['all'] += $counts['drafts'];
        $query = match ($state) {
            'active' => $active,
            'pending' => $pending,
            'closed' => $base->where('status', '!=', 'pending')->whereNotIn('id', $active->select('id')),
            'drafts' => $base->whereRaw('1 = 0'),
            default => $base,
        };

        $role = ClientSpaceNavigation::role($request, $user);

        return ['user' => $user, 'ads' => $query->latest()->paginate(12)->withQueryString(),
            'draft' => $draft, 'publicationCounts' => $counts, 'publicationState' => $state,
            'publicationType' => $type, 'publicationCanOffer' => $canOffer,
            'publicationPublish' => ClientSpaceNavigation::publishAction($type === 'demande' ? 'client' : ($type === 'offre' ? 'provider' : $role)),
            'publicationOnline' => $user->ads()->marketplaceActive()->count(),
            'publicationLimit' => $user->getMaxActiveAds()];
    }
}
