<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Transaction;
use App\Models\PointTransaction;
use Barryvdh\DomPDF\Facade\Pdf;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     */
    public function index()
    {
        $user = Auth::user();
        $ads = $user->ads()->latest()->get();
        
        // Fetch financial transactions (Stripe payments, subscriptions, etc.)
        $transactions = Transaction::where('user_id', $user->id)
            ->latest()
            ->take(20)
            ->get();
        
        // Fetch point transactions
        $pointTransactions = PointTransaction::where('user_id', $user->id)
            ->latest()
            ->take(20)
            ->get();
        
        // Active boosts and urgent publications
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
        
        // Pro subscription info
        $proSubscription = null;
        if ($user->plan && $user->plan !== 'free') {
            $proSubscription = [
                'plan' => $user->plan,
                'started_at' => $user->subscription_start ?? $user->created_at,
                'ends_at' => $user->subscription_end,
                'is_active' => !$user->subscription_end || $user->subscription_end->isFuture(),
            ];
        }
        
        return view('home', compact(
            'ads', 'transactions', 'pointTransactions',
            'activeBoostedAds', 'activeUrgentAds', 'proSubscription'
        ));
    }

    /**
     * Export transaction history as PDF invoice.
     */
    public function exportTransactionsPdf()
    {
        $user = Auth::user();
        
        $transactions = Transaction::where('user_id', $user->id)
            ->latest()
            ->get();
        
        $pointTransactions = PointTransaction::where('user_id', $user->id)
            ->latest()
            ->get();
        
        $pdf = Pdf::loadView('pdf.transactions', [
            'user' => $user,
            'transactions' => $transactions,
            'pointTransactions' => $pointTransactions,
            'generatedAt' => now(),
        ]);

        return $pdf->download('Lunamars_Historique_Transactions_' . date('Y-m-d') . '.pdf');
    }
}
