<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use App\Models\PointTransaction;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Section: Tableau de bord (overview)
     */
    public function overview(\Illuminate\Http\Request $request, \App\Services\ActivityDashboardService $dashboard)
    {
        return response()->view('dashboard.partials.overview', $dashboard->data($request->user(), $request))->header('Cache-Control', 'private, no-store');
    }

    public function account(\App\Services\ActivityDashboardService $dashboard)
    {
        return response()->view('dashboard.partials.account', $dashboard->account(Auth::user()))->header('Cache-Control', 'private, no-store');
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
            array_keys(\App\Support\MarketplaceCategoryRegistry::enabledServices()),
            array_keys(\App\Support\MarketplaceCategoryRegistry::enabledMarketplace())
        );

        $categoriesData = [];
        foreach (\App\Support\MarketplaceCategoryRegistry::enabledServices() as $name => $data) {
            $categoriesData[$name] = ['icon' => $data['icon'], 'subcategories' => $data['subcategories']];
        }
        foreach (\App\Support\MarketplaceCategoryRegistry::enabledMarketplace() as $name => $data) {
            $categoriesData[$name] = ['icon' => $data['icon'], 'subcategories' => $data['subcategories']];
        }

        return view('dashboard.partials.create-ad', compact('categories', 'categoriesData'));
    }
}
