<?php

namespace App\Http\Controllers;

use App\Support\FranceTravailLinks;
use App\Support\Territory;
use App\Support\TerritoryResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Pages emploi publiques : /emploi et /emploi/{territoire}.
 *
 * Elles ne reprennent aucune offre : elles renvoient vers la recherche
 * France Travail du territoire. Tant qu'aucune offre vérifiée n'est affichée,
 * elles restent hors index (balise noindex dans la vue).
 *
 * Le gabarit varie selon la session (en-tête connecté, onglets de l'espace
 * particulier) : la réponse reste « private » pour que Cloudflare ne la
 * partage pas entre visiteurs, sans « no-store » pour permettre le retour
 * arrière du navigateur. Pour un membre connecté, le middleware
 * EnforceSessionInactivity impose ensuite « private, no-store » à toutes les
 * pages : cette règle de session n'est pas modifiée ici.
 */
class EmploymentController extends Controller
{
    public function __construct(private readonly TerritoryResolver $territories) {}

    /** Redirige un membre au territoire connu vers sa page, sinon liste les territoires. */
    public function index(Request $request)
    {
        $territory = $this->territories->forUser(Auth::user());

        if ($territory !== null) {
            return redirect()->route('employment.territory', ['territoire' => $territory->slug]);
        }

        return $this->privateView('employment.index', [
            'pkTerritories' => $this->territories->overseasTerritories(),
            'pkSearchUrl' => FranceTravailLinks::searchUrl(null),
        ]);
    }

    public function show(string $territoire)
    {
        $territory = $this->territories->findBySlug($territoire);
        abort_if($territory === null, 404);

        return $this->privateView('employment.territory', [
            'pkTerritory' => $territory,
            'pkSearchUrl' => FranceTravailLinks::searchUrl($territory),
            'pkSearchLabel' => FranceTravailLinks::searchLabel($territory),
            'pkOtherTerritories' => array_values(array_filter(
                $this->territories->overseasTerritories(),
                fn (Territory $other) => ! $other->is($territory)
            )),
        ]);
    }

    private function privateView(string $view, array $data)
    {
        return response()->view($view, $data)->header('Cache-Control', 'private');
    }
}
