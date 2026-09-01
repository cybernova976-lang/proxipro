<?php

namespace App\Http\Controllers;

use App\Support\MarketplaceGuideCatalog;

class MarketplaceGuideController extends Controller
{
    public function index()
    {
        $guides = MarketplaceGuideCatalog::all();

        return view('guides.index', [
            'clientGuides' => $guides
                ->filter(fn (array $guide): bool => in_array('client', $guide['audience'], true))
                ->values(),
            'providerGuides' => $guides
                ->filter(fn (array $guide): bool => in_array('provider', $guide['audience'], true))
                ->values(),
        ]);
    }

    public function show(string $slug)
    {
        $guide = MarketplaceGuideCatalog::find($slug);
        abort_if($guide === null, 404);

        $relatedGuides = MarketplaceGuideCatalog::all()
            ->reject(fn (array $candidate): bool => $candidate['slug'] === $slug)
            ->filter(fn (array $candidate): bool => array_intersect($candidate['audience'], $guide['audience']) !== [])
            ->take(3)
            ->values();

        return view('guides.show', compact('guide', 'relatedGuides'));
    }
}
