<?php

namespace App\Services;

use App\Models\DemandDraft;
use App\Models\User;
use Illuminate\Http\Request;

class MemberPublicationsService
{
    public function data(User $user, Request $request): array
    {
        $state = in_array($request->query('state'), ['active', 'pending', 'closed', 'drafts'], true)
            ? $request->query('state') : 'all';
        $base = $user->ads();
        $active = (clone $base)->marketplaceActive();
        $pending = (clone $base)->where('status', 'pending');
        $counts = [
            'all' => (clone $base)->count(),
            'active' => (clone $active)->count(),
            'pending' => (clone $pending)->count(),
        ];
        $draft = DemandDraft::where('user_id', $user->id)->first();
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

        return ['user' => $user, 'ads' => $query->latest()->paginate(12)->withQueryString(),
            'draft' => $draft, 'publicationCounts' => $counts, 'publicationState' => $state,
            'publicationLimit' => $user->getMaxActiveAds()];
    }
}
