<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use App\Support\MarketplaceCategoryRegistry;
use App\Support\Territory;
use App\Support\TerritoryResolver;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Pages emploi publiques : /emploi et /emploi/{territoire}.
 *
 * Elles affichent uniquement les offres d'emploi publiées par les membres de
 * Prokejem (annonces de la catégorie « Emploi », de type offre). Aucune offre
 * ne vient d'un site extérieur. Tant qu'aucune offre n'est affichée, la page
 * reste hors index (balise noindex dans la vue).
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
    /** Catégorie des annonces d'emploi dans config/categories.php. */
    private const CATEGORY = 'Emploi';

    public function __construct(private readonly TerritoryResolver $territories) {}

    /** Redirige un membre au territoire connu vers sa page, sinon affiche toutes les offres. */
    public function index(Request $request)
    {
        $territory = $this->territories->forUser(Auth::user());

        if ($territory !== null) {
            return redirect()->route('employment.territory', ['territoire' => $territory->slug]);
        }

        return $this->privateView('employment.index', [
            'pkTerritories' => $this->territories->overseasTerritories(),
            'pkOffers' => $this->offers(null),
            'pkCanPublishOffer' => $this->publicationIsOpen(),
        ]);
    }

    public function show(string $territoire)
    {
        $territory = $this->territories->findBySlug($territoire);
        abort_if($territory === null, 404);

        return $this->privateView('employment.territory', [
            'pkTerritory' => $territory,
            'pkOffers' => $this->offers($territory),
            'pkCanPublishOffer' => $this->publicationIsOpen(),
            'pkOtherTerritories' => array_values(array_filter(
                $this->territories->overseasTerritories(),
                fn (Territory $other) => ! $other->is($territory)
            )),
        ]);
    }

    /**
     * Offres d'emploi en ligne publiées par les membres, les plus récentes d'abord.
     * La catégorie « Emploi » doit être ouverte dans l'administration : sinon
     * aucune annonce n'est visible, ici comme ailleurs sur le site.
     */
    private function offers(?Territory $territory): LengthAwarePaginator
    {
        $contracts = (array) config('categories.marketplace.'.self::CATEGORY.'.subcategories', []);

        return Ad::query()
            ->marketplaceActive()
            ->excludingHiddenAuthors()
            ->where('service_type', 'offre')
            ->where(fn ($query) => $query
                ->where('main_category', self::CATEGORY)
                ->orWhereIn('category', [self::CATEGORY, ...$contracts]))
            ->when($territory !== null, fn ($query) => $query->inTerritory($territory))
            ->with('user:id,name')
            ->latest()
            ->paginate(12);
    }

    /** Une offre d'emploi ne peut être publiée que si la catégorie est ouverte. */
    private function publicationIsOpen(): bool
    {
        return MarketplaceCategoryRegistry::isEnabled(self::CATEGORY);
    }

    private function privateView(string $view, array $data)
    {
        return response()->view($view, $data)->header('Cache-Control', 'private');
    }
}
