<?php

namespace App\Http\Controllers;

use App\Services\SessionInactivity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SessionActivityController extends Controller
{
    public function __invoke(Request $request, SessionInactivity $activity): JsonResponse
    {
        if ($request->isMethod('post')) {
            $data = $request->validate(['age_ms' => ['required', 'integer', 'min:0', 'max:3599999']]);
            $activity->touch($request, (int) $data['age_ms']);
        }

        return response()->json([
            'remaining_seconds' => max(0, ($activity->lastInteraction($request) ?? 0) + SessionInactivity::TIMEOUT_SECONDS - now()->timestamp),
        ])->header('Cache-Control', 'private, no-store');
    }
}
