<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class PointController extends Controller
{
    public function dashboard()
    {
        return view('points.retired');
    }

    public function transactions()
    {
        $transactions = Auth::user()->pointTransactions()->latest()->paginate(20);

        return view('points.transactions', compact('transactions'));
    }
}
