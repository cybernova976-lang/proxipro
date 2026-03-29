<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PointController extends Controller
{
    public function dashboard()
    {
        $user = Auth::user();
        $transactions = $user->pointTransactions()->latest()->take(10)->get();
        $badges = $user->badges;
        
        return view('points.dashboard', compact('user', 'transactions', 'badges'));
    }

    public function share(Request $request)
    {
        $request->validate([
            'platform' => 'required|in:facebook,twitter,instagram,linkedin,whatsapp,telegram'
        ]);

        $user = Auth::user();
        $platform = $request->platform;

        // Vérifier si déjà réclamé sur cette plateforme
        $alreadyClaimed = \DB::table('social_shares')
            ->where('user_id', $user->id)
            ->where('platform', $platform)
            ->exists();

        if ($alreadyClaimed) {
            return response()->json([
                'success' => false,
                'message' => 'Vous avez déjà gagné des points pour le partage sur ' . ucfirst($platform) . '. Vous pouvez toujours partager librement !',
                'already_claimed' => true,
            ], 400);
        }

        // Enregistrer le partage (une seule fois par plateforme)
        \DB::table('social_shares')->insert([
            'user_id' => $user->id,
            'platform' => $platform,
            'points_earned' => 5,
            'ip_address' => $request->ip(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Donner les 5 points pour le partage
        $user->addPoints(5, 'social_share', "Partage sur " . ucfirst($platform), $platform);

        return response()->json([
            'success' => true,
            'message' => '+5 points pour le partage sur ' . ucfirst($platform) . ' !',
            'points' => $user->fresh()->available_points,
            'total_points' => $user->fresh()->total_points
        ]);
    }

    public function dailyEngagement(Request $request)
    {
        $request->validate([
            'action' => 'required|in:like,comment,view,rating'
        ]);

        $user = Auth::user();
        $action = $request->action;

        // Limite de points journaliers
        $dailyLimit = $user->hasActiveProSubscription() ? 25 : 15;
        if ($user->daily_points >= $dailyLimit) {
            return response()->json([
                'success' => false,
                'message' => "Limite quotidienne atteinte ($dailyLimit points max par jour)"
            ], 400);
        }

        $points = 2; // 2 points par action
        $user->addPoints($points, 'daily', "Action quotidienne: $action");

        return response()->json([
            'success' => true,
            'message' => "2 points ajoutés pour $action",
            'points' => $user->available_points,
            'daily_points' => $user->daily_points
        ]);
    }

    public function transactions()
    {
        $transactions = Auth::user()->pointTransactions()->latest()->paginate(20);
        return view('points.transactions', compact('transactions'));
    }
}
