<?php

namespace App\Http\Controllers;

use App\Models\SavedSearch;
use App\Services\SavedSearchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SavedSearchController extends Controller
{
    public function __construct(private SavedSearchService $savedSearchService)
    {
    }

    public function index()
    {
        $savedSearches = Auth::user()
            ->savedSearches()
            ->withCount('matches')
            ->latest()
            ->paginate(12);

        return view('saved-searches.index', compact('savedSearches'));
    }

    public function store(Request $request): RedirectResponse
    {
        $snapshot = $this->savedSearchService->buildSnapshot($request->all(), Auth::user(), [
            'city' => $request->input('geo_city'),
            'country' => $request->input('geo_country'),
            'latitude' => $request->input('geo_latitude'),
            'longitude' => $request->input('geo_longitude'),
            'radius' => $request->input('radius'),
        ]);

        $existing = $this->savedSearchService->findExistingSearch(Auth::user(), $snapshot);

        if ($existing) {
            return back()->with('success', 'Cette alerte existe deja dans votre espace.');
        }

        $this->savedSearchService->saveFromSnapshot(Auth::user(), $snapshot);

        return back()->with('success', 'Alerte enregistree. Vous serez prevenu des nouvelles annonces correspondantes.');
    }

    public function destroy(SavedSearch $savedSearch): RedirectResponse
    {
        abort_if($savedSearch->user_id !== Auth::id(), 403);

        $savedSearch->delete();

        return back()->with('success', 'Alerte supprimee.');
    }
}