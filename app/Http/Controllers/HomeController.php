<?php

namespace App\Http\Controllers;

use App\Models\PointTransaction;
use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
    public function index(Request $request, \App\Services\ActivityDashboardService $dashboard)
    {
        return response()->view('home', $dashboard->data($request->user(), $request))->header('Cache-Control', 'private, no-store');
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

        return $pdf->download('Prokejem_Historique_Transactions_'.date('Y-m-d').'.pdf');
    }
}
