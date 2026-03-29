<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Ad;
use App\Models\Transaction;
use App\Models\PointTransaction;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Section: Tableau de bord (overview)
     */
    public function overview()
    {
        $user = Auth::user();
        $ads = $user->ads()->latest()->get();

        $transactions = Transaction::where('user_id', $user->id)
            ->latest()
            ->take(20)
            ->get();

        $pointTransactions = PointTransaction::where('user_id', $user->id)
            ->latest()
            ->take(20)
            ->get();

        $activeBoostedAds = $user->ads()
            ->where('status', 'active')
            ->where('is_boosted', true)
            ->where('boost_end', '>', now())
            ->orderBy('boost_end', 'asc')
            ->get();

        $activeUrgentAds = $user->ads()
            ->where('status', 'active')
            ->where('is_urgent', true)
            ->where(function($q) {
                $q->whereNull('urgent_until')
                  ->orWhere('urgent_until', '>', now());
            })
            ->orderBy('urgent_until', 'asc')
            ->get();

        $proSubscription = null;
        if ($user->plan && $user->plan !== 'free') {
            $proSubscription = [
                'plan' => $user->plan,
                'started_at' => $user->subscription_start ?? $user->created_at,
                'ends_at' => $user->subscription_end,
                'is_active' => !$user->subscription_end || $user->subscription_end->isFuture(),
            ];
        }

        return view('dashboard.partials.overview', compact(
            'ads', 'transactions', 'pointTransactions',
            'activeBoostedAds', 'activeUrgentAds', 'proSubscription'
        ));
    }

    /**
     * Section: Profil
     */
    public function profile()
    {
        $user = \App\Models\User::with('services')->where('id', Auth::id())->firstOrFail();
        $user->refresh();

        $ads = Ad::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->take(6)
            ->get();

        $stats = [
            'total_ads' => Ad::where('user_id', $user->id)->count(),
            'active_ads' => Ad::where('user_id', $user->id)->where('status', 'active')->count(),
            'total_views' => Ad::where('user_id', $user->id)->sum('views'),
        ];

        $verification = \App\Models\IdentityVerification::where('user_id', $user->id)->latest()->first();

        return view('dashboard.partials.profile', compact('user', 'ads', 'stats', 'verification'));
    }

    /**
     * Section: Mon profil - édition
     */
    public function profileEdit()
    {
        $user = Auth::user();
        return view('dashboard.partials.profile-edit', compact('user'));
    }

    /**
     * Section: Paramètres
     */
    public function settings()
    {
        $user = Auth::user();
        return view('dashboard.partials.settings', compact('user'));
    }

    /**
     * Section: Points
     */
    public function points()
    {
        $user = Auth::user();
        $transactions = $user->pointTransactions()->latest()->take(10)->get();
        $badges = $user->badges;

        return view('dashboard.partials.points', compact('user', 'transactions', 'badges'));
    }

    /**
     * Section: Mes annonces
     */
    public function myAds()
    {
        $user = Auth::user();
        $ads = $user->ads()->latest()->get();

        return view('dashboard.partials.my-ads', compact('ads'));
    }

    /**
     * Section: Messages
     */
    public function messages()
    {
        $user = Auth::user();
        $conversations = \App\Models\Conversation::with(['user1', 'user2', 'lastMessage.sender'])
            ->where('user1_id', $user->id)
            ->orWhere('user2_id', $user->id)
            ->orderBy('last_message_at', 'desc')
            ->take(20)
            ->get();

        return view('dashboard.partials.messages', compact('conversations'));
    }

    /**
     * Section: Transactions
     */
    public function transactions()
    {
        $user = Auth::user();

        $transactions = Transaction::where('user_id', $user->id)
            ->latest()
            ->take(20)
            ->get();

        $pointTransactions = PointTransaction::where('user_id', $user->id)
            ->latest()
            ->take(20)
            ->get();

        return view('dashboard.partials.transactions', compact('transactions', 'pointTransactions'));
    }

    /**
     * Section: Publier une annonce
     */
    public function createAd()
    {
        $categories = array_merge(
            array_keys(config('categories.services')),
            array_keys(config('categories.marketplace'))
        );

        $categoriesData = [];
        foreach (config('categories.services') as $name => $data) {
            $categoriesData[$name] = ['icon' => $data['icon'], 'subcategories' => $data['subcategories']];
        }
        foreach (config('categories.marketplace') as $name => $data) {
            $categoriesData[$name] = ['icon' => $data['icon'], 'subcategories' => $data['subcategories']];
        }

        return view('dashboard.partials.create-ad', compact('categories', 'categoriesData'));
    }
}
