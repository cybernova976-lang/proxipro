<?php

namespace App\Http\Controllers;

use App\Models\SavedSearch;
use App\Models\User;
use App\Services\SavedSearchService;
use App\Support\TerritoryResolver;
use Illuminate\Http\JsonResponse;
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

    /**
     * Crée une alerte. Envoyé par un script (boutons d'alerte du flux et des
     * fiches), le formulaire reçoit une réponse JSON : le bouton change d'état
     * sur place et un message confirme l'action, sans recharger la page.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $result = match (true) {
            $request->filled('provider') => $this->storeProviderAlert($request),
            $request->filled('territory') => $this->storeTerritoryAlert($request),
            default => $this->storeSearchAlert($request),
        };

        if ($request->expectsJson()) {
            $alert = $result['alert'] ?? null;

            return response()->json([
                'ok' => $result['ok'],
                'message' => $result['message'],
                'active' => (bool) $alert?->is_active,
                'count' => Auth::user()->savedSearches()->where('is_active', true)->count(),
                'stop_url' => $alert ? route('saved-searches.destroy', $alert) : null,
            ], $result['ok'] ? 200 : 422);
        }

        $response = back()->with($result['flash'], $result['message']);

        return empty($result['errors']) ? $response : $response->withErrors($result['errors']);
    }

    /**
     * Alerte sur une recherche enregistrée (filtres de la liste des annonces).
     *
     * @return array{ok: bool, flash: string, message: string, alert?: ?SavedSearch, errors?: array<string, string>}
     */
    private function storeSearchAlert(Request $request): array
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
            return ['ok' => true, 'flash' => 'success', 'alert' => $existing, 'message' => 'Cette alerte existe déjà dans votre espace.'];
        }

        $alert = $this->savedSearchService->saveFromSnapshot(Auth::user(), $snapshot);

        return ['ok' => true, 'flash' => 'success', 'alert' => $alert,
            'message' => 'Alerte enregistrée. Vous serez prévenu des nouvelles annonces correspondantes.'];
    }

    /**
     * Alerte « Me prévenir » de l'état vide du flux client : nouvelles offres
     * publiées dans le territoire. Le message passe par « info », affiché
     * sur toutes les pages par layouts/app (le flux n'affiche pas « success »).
     */
    private function storeTerritoryAlert(Request $request): array
    {
        $territory = $this->savedSearchService->territoryFromInput($request->all());

        if ($territory === null) {
            $message = 'Ce territoire n’est pas reconnu : l’alerte n’a pas été créée. Indiquez votre commune dans votre profil.';

            return ['ok' => false, 'flash' => 'info', 'message' => $message, 'errors' => ['territory' => $message]];
        }

        $user = Auth::user();
        $snapshot = $this->savedSearchService->buildSnapshot($request->all(), $user);
        $existing = $this->savedSearchService->findExistingSearch($user, $snapshot);

        if ($existing?->is_active) {
            return ['ok' => true, 'flash' => 'info', 'alert' => $existing, 'message' => 'Votre alerte « '.$existing->name.' » est déjà active.'];
        }

        $alert = $this->savedSearchService->saveFromSnapshot($user, $snapshot);

        $subject = match ($snapshot['service_type']) {
            'demandes' => 'une demande',
            'all' => 'une annonce',
            default => 'une offre',
        };

        return ['ok' => true, 'flash' => 'info', 'alert' => $alert,
            'message' => 'Alerte enregistrée : vous recevrez une notification dès qu’'.$subject.' sera publiée '.$territory->withPreposition().'.'];
    }

    /**
     * Alerte sur un prestataire choisi (cartes d'offres, fiche d'une offre) :
     * le membre est prévenu de chacune de ses nouvelles offres.
     */
    private function storeProviderAlert(Request $request): array
    {
        $user = Auth::user();
        $provider = is_numeric($request->input('provider')) ? User::find((int) $request->input('provider')) : null;

        if ($provider === null || ! $this->savedSearchService->canFollowProvider($user, $provider)) {
            return ['ok' => false, 'flash' => 'error', 'message' => 'Cette alerte ne peut pas être créée pour ce compte.'];
        }

        $existing = $this->savedSearchService->findProviderAlert($user, $provider);
        if ($existing?->is_active) {
            return ['ok' => true, 'flash' => 'info', 'alert' => $existing,
                'message' => 'Vous êtes déjà prévenu des nouvelles offres de '.$provider->name.'.'];
        }

        $alert = $this->savedSearchService->saveProviderAlert($user, $provider);

        return ['ok' => true, 'flash' => 'info', 'alert' => $alert,
            'message' => 'Alerte enregistrée : vous recevrez une notification dès que '.$provider->name.' publiera une nouvelle offre.'];
    }

    public function destroy(SavedSearch $savedSearch): RedirectResponse
    {
        abort_if($savedSearch->user_id !== Auth::id(), 403);

        $savedSearch->delete();

        return back()->with('success', 'Alerte supprimée.');
    }
}