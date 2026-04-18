<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ReportController extends Controller
{
    public function store(Request $request, Ad $ad)
    {
        $isAjax = $request->expectsJson() || $request->ajax();

        $validated = $request->validate([
            'reason' => 'required|string|max:255',
            'message' => 'nullable|string|max:1000',
        ]);

        // Empêcher les doublons : un seul signalement par utilisateur par annonce
        $existing = Report::where('reporter_id', Auth::id())
            ->where('ad_id', $ad->id)
            ->first();

        if ($existing) {
            if ($isAjax) {
                return response()->json(['already_reported' => true, 'message' => 'Déjà signalé']);
            }
            return back()->with('info', 'Vous avez déjà signalé cette annonce.');
        }

        try {
            Report::create([
                'reporter_id' => Auth::id(),
                'ad_id' => $ad->id,
                'reason' => $validated['reason'],
                'message' => $validated['message'] ?? null,
                'status' => 'pending',
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur création signalement: ' . $e->getMessage(), [
                'ad_id' => $ad->id,
                'user_id' => Auth::id(),
            ]);

            if ($isAjax) {
                return response()->json(['success' => false, 'message' => 'Erreur lors du signalement'], 500);
            }
            return back()->with('error', 'Erreur lors du signalement. Veuillez réessayer.');
        }

        if ($isAjax) {
            return response()->json(['success' => true, 'message' => 'Signalement enregistré']);
        }

        return back()->with('success', 'Annonce signalée. Merci pour votre retour.');
    }
}
