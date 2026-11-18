<?php

namespace App\Http\Controllers;

use App\Models\SavedSearch;
use App\Models\User;
use App\Services\SavedSearchService;
use App\Support\TerritoryResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SavedSearchController extends Controller
{
    public function __construct(private SavedSearchService $savedSearchService)
    {
    }

    public function index(Request $request, TerritoryResolver $territories)
    {
        $user = Auth::user();
        $savedSearches = $user
            ->savedSearches()
            ->withCount('matches')
            ->latest()
            ->paginate(12);

        // La page doit permettre de créer une alerte, pas seulement de les lire :
        // on propose celle des nouvelles offres du territoire du membre, tant
        // qu'il ne l'a pas déjà.
        $territory = $territories->forUser($user, (array) ($request->attributes->get('user_geo') ?? []));
        $territoryAlert = $territory !== null && ! $this->savedSearchService->hasActiveTerritoryOfferAlert($user, $territory)
            ? $this->savedSearchService->territoryOfferAlertFields($territory)
            : null;

        return view('saved-searches.index', compact('savedSearches', 'territory', 'territoryAlert'));
    }

    public function store(Request $request): RedirectResponse
    {
        if ($request->filled('provider')) {
            return $this->storeProviderAlert($request);
        }

        if ($request->filled('territory')) {
            return $this->storeTerritoryAlert($request);
        }

        $snapshot = $this->savedSearchService->buildSnapshot($request->all(), Auth::user(), [
            'city' => $request->input('geo_city'),
            'country' => $request->input('geo_country'),
            'latitude' => $request->input('geo_latitude'),
            'longitude' => $request->input('geo_longitude'),
            'radius' => $request->input('radius'),
        ]);

        $existing = $this->savedSearchService->findExistingSearch(Auth::user(), $snapshot);

        if ($existing) {
            return back()->with('success', 'Cette alerte existe déjà dans votre espace.');
        }

        $this->savedSearchService->saveFromSnapshot(Auth::user(), $snapshot);

        return back()->with('success', 'Alerte enregistrée. Vous serez prévenu des nouvelles annonces correspondantes.');
    }

    /**
     * Alerte « Me prévenir » de l'état vide du flux client : nouvelles offres
     * publiées dans le territoire. Le message passe par « info », affiché
     * sur toutes les pages par layouts/app (le flux n'affiche pas « success »).
     */
    private function storeTerritoryAlert(Request $request): RedirectResponse
    {
        $territory = $this->savedSearchService->territoryFromInput($request->all());

        if ($territory === null) {
            $message = 'Ce territoire n’est pas reconnu : l’alerte n’a pas été créée. Indiquez votre commune dans votre profil.';

            return back()->withErrors(['territory' => $message])->with('info', $message);
        }

        $user = Auth::user();
        $snapshot = $this->savedSearchService->buildSnapshot($request->all(), $user);
        $existing = $this->savedSearchService->findExistingSearch($user, $snapshot);

        if ($existing?->is_active) {
            return back()->with('info', 'Votre alerte « '.$existing->name.' » est déjà active.');
        }

        $this->savedSearchService->saveFromSnapshot($user, $snapshot);

        $subject = match ($snapshot['service_type']) {
            'demandes' => 'une demande',
            'all' => 'une annonce',
            default => 'une offre',
        };

        return back()->with('info', 'Alerte enregistrée : vous recevrez une notification dès qu’'.$subject.' sera publiée '.$territory->withPreposition().'.');
    }

    /**
     * Alerte sur un prestataire choisi (cartes d'offres, fiche d'une offre) :
     * le membre est prévenu de chacune de ses nouvelles offres.
     */
    private function storeProviderAlert(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $provider = is_numeric($request->input('provider')) ? User::find((int) $request->input('provider')) : null;

        if ($provider === null || ! $this->savedSearchService->canFollowProvider($user, $provider)) {
            return back()->with('error', 'Cette alerte ne peut pas être créée pour ce compte.');
        }

        if ($this->savedSearchService->findProviderAlert($user, $provider)?->is_active) {
            return back()->with('info', 'Vous êtes déjà prévenu des nouvelles offres de '.$provider->name.'.');
        }

        $this->savedSearchService->saveProviderAlert($user, $provider);

        return back()->with('info', 'Alerte enregistrée : vous recevrez une notification dès que '.$provider->name.' publiera une nouvelle offre.');
    }

    public function destroy(SavedSearch $savedSearch): RedirectResponse
    {
        abort_if($savedSearch->user_id !== Auth::id(), 403);

        $savedSearch->delete();

        return back()->with('success', 'Alerte supprimée.');
    }
}