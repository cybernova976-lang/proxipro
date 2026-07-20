<?php

namespace App\Http\Controllers;

use App\Models\ReferralReward;
use Illuminate\Support\Facades\Auth;

class PointController extends Controller
{
    public function dashboard()
    {
        $user = Auth::user();
        $transactions = $user->pointTransactions()->latest()->take(10)->get();
        $badges = $user->badges;
        $referralHistory = ReferralReward::with(['referee', 'referrer'])
            ->where(function ($query) use ($user) {
                $query->where('referrer_user_id', $user->id)
                    ->orWhere('referee_user_id', $user->id);
            })
            ->latest('granted_at')
            ->take(10)
            ->get();
        $referralStats = [
            'code' => $user->referral_code,
            'link' => route('register', ['ref' => $user->referral_code]),
            'referred_count' => $user->referredUsers()->count(),
            'successful_referrals' => $user->referralRewardsEarned()
                ->where('reward_type', 'first_purchase_referrer')
                ->count(),
            'points_earned' => (int) $user->referralRewardsEarned()->sum('points'),
        ];

        return view('points.dashboard', compact('user', 'transactions', 'badges', 'referralHistory', 'referralStats'));
    }

    public function transactions()
    {
        $transactions = Auth::user()->pointTransactions()->latest()->paginate(20);

        return view('points.transactions', compact('transactions'));
    }
}
