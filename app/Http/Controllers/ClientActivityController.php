<?php

namespace App\Http\Controllers;

use App\Services\ClientActivityService;
use Illuminate\Http\Request;

class ClientActivityController extends Controller
{
    public function index(Request $request, ClientActivityService $activity)
    {
        return redirect()->route('home', $request->only(['demande', 'etat', 'page']));
    }

    public function refresh(Request $request, ClientActivityService $activity)
    {
        $pkClientActivity = $activity->summary($request->user());
        $changed = $request->query('revision') !== $pkClientActivity['revision'];

        return response()->json([
            'revision' => $pkClientActivity['revision'], 'changed' => $changed,
            'html' => $changed ? view('feed.partials.client-activity', compact('pkClientActivity'))->render() : null,
        ])->header('Cache-Control', 'private, no-store');
    }
}
