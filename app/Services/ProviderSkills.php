<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserService;
use App\Support\MarketplaceCategoryRegistry;
use Illuminate\Support\Collection;

/**
 * Compétences d'un prestataire : une seule source de vérité.
 *
 * Dès qu'un membre a au moins une ligne dans user_services (« Mes compétences »),
 * ce sont ces lignes qui décident. Seules les compétences actives servent aux
 * correspondances : « Mes opportunités », notifications de nouvelles demandes,
 * demandes proposées dans le feed.
 *
 * Les colonnes historiques du profil (profession, service_category,
 * service_subcategories, pro_service_categories) n'en sont qu'une copie,
 * recalculée par sync() après chaque changement, pour les écrans qui les
 * lisent encore. Un membre sans aucune ligne (ancien parcours d'inscription)
 * garde ses colonnes comme source, jusqu'à sa première compétence enregistrée :
 * absorbLegacyProfile() reprend alors ses métiers déclarés.
 */
class ProviderSkills
{
    /** users.service_category et users.profession sont des varchar(255). */
    private const STRING_COLUMN_LENGTH = 255;

    public function hasSkillRecords(User $user): bool
    {
        return $user->exists && $user->services()->exists();
    }

    /**
     * Catégories et métiers qui rattachent une demande à ce membre.
     *
     * @return Collection<int, string>
     */
    public function matchingCategories(User $user): Collection
    {
        if (! $this->hasSkillRecords($user)) {
            return $this->clean($this->legacyCategories($user));
        }

        return $this->clean(
            $user->services()
                ->where('is_active', true)
                ->orderBy('id')
                ->get(['main_category', 'subcategory'])
                ->flatMap(fn (UserService $service) => [$service->main_category, $service->subcategory])
        );
    }

    /**
     * Recopie les compétences actives dans les colonnes du profil.
     *
     * Métier principal : conservé s'il reste une compétence active, s'il désigne
     * une compétence en pause (une pause n'est pas un retrait : il est toujours
     * là à la reprise) ou s'il est libre (hors catalogue) ; remplacé par la
     * première compétence restante, ou vidé, s'il désigne une compétence retirée.
     *
     * Une compétence « catégorie entière » (métier = nom de la catégorie) donne
     * sa catégorie au profil, mais n'est pas un métier.
     *
     * @param  list<string>  $removed  compétences que le membre vient de supprimer
     */
    public function sync(User $user, array $removed = []): void
    {
        if (! $this->hasSkillRecords($user)) {
            $this->stripRemovedFromLegacyProfile($user, $removed);

            return;
        }

        $services = $user->services()->orderBy('id')->get(['main_category', 'subcategory', 'is_active']);
        $active = $services->filter(fn (UserService $service) => (bool) $service->is_active);
        $paused = $services->reject(fn (UserService $service) => (bool) $service->is_active);

        $subcategories = $this->clean(
            $active->reject(fn (UserService $service) => $this->isWholeCategory($service))->pluck('subcategory')
        )->all();
        $mainCategories = $this->clean($active->pluck('main_category'))->all();
        $pausedNames = $this->keys($paused->flatMap(fn (UserService $service) => [$service->subcategory, $service->main_category])->all());

        $user->forceFill([
            'service_category' => $this->fitInColumn($mainCategories, self::STRING_COLUMN_LENGTH),
            'service_subcategories' => $subcategories !== [] ? $subcategories : null,
            'pro_service_categories' => $mainCategories !== [] ? $mainCategories : null,
            'profession' => $this->profession($user->profession, $subcategories, $mainCategories, $removed, $pausedNames),
        ]);

        if ($user->isDirty()) {
            $user->save();
        }
    }

    /**
     * Avant la première compétence enregistrée : transforme en lignes les métiers
     * déclarés sur l'ancien profil, pour qu'ils restent dans « Mes compétences »
     * au lieu de disparaître au prochain sync().
     */
    public function absorbLegacyProfile(User $user): void
    {
        if (! $user->exists || $this->hasSkillRecords($user)) {
            return;
        }

        $preferredMains = $this->clean(collect((array) $user->pro_service_categories)
            ->map(fn ($value) => $this->categoryName($value))
            ->merge(preg_split('/[,;|]/u', (string) $user->service_category) ?: []))->all();

        $candidates = $this->clean(collect((array) $user->service_subcategories)->push($user->profession));

        foreach ($candidates as $subcategory) {
            $main = $this->mainCategoryFor($subcategory, $preferredMains);
            if ($main === null) {
                continue;
            }

            // Clé de l'index unique de user_services : (user_id, subcategory).
            UserService::firstOrCreate(
                ['user_id' => $user->id, 'subcategory' => $subcategory],
                ['main_category' => $main, 'experience_years' => 0, 'description' => null, 'is_active' => true]
            );
        }
    }

    /**
     * Ajoute des métiers choisis ailleurs que dans « Mes compétences »
     * (configuration de l'Espace Pro, fenêtre de choix des catégories) à un
     * membre qui a déjà des compétences enregistrées, puis resynchronise.
     * Un membre de l'ancien parcours garde ses colonnes, inchangées.
     *
     * Une catégorie choisie sans aucun métier devient une compétence
     * « catégorie entière » (métier = nom de la catégorie), sauf si le membre a
     * déjà une compétence dans cette catégorie : sinon sync() l'effacerait.
     *
     * @param  list<string>  $categories
     * @param  list<string>  $subcategories
     */
    public function adoptSelections(User $user, array $categories, array $subcategories): void
    {
        if (! $this->hasSkillRecords($user)) {
            return;
        }

        $preferredMains = $this->clean(collect($categories)->map(fn ($value) => $this->categoryName($value)))->all();
        $coveredMains = [];

        foreach ($this->clean(collect($subcategories)) as $subcategory) {
            // Recherche sur la clé de l'index unique (user_id, subcategory) : le
            // registre a pu ranger le métier dans une autre catégorie que la ligne
            // existante (« Maçon » passé de « Bricolage & Travaux » à
            // « Maçonnerie & Gros œuvre ») ; un INSERT violerait l'index.
            $service = $user->services()->where('subcategory', $subcategory)->first();
            $main = $this->mainCategoryFor(
                $subcategory,
                $service ? [...$preferredMains, $service->main_category] : $preferredMains
            );

            if ($service) {
                $service->forceFill(['main_category' => $main ?? $service->main_category, 'is_active' => true])->save();
                $coveredMains[] = $service->main_category;

                continue;
            }

            if ($main === null) {
                continue;
            }

            UserService::create([
                'user_id' => $user->id, 'main_category' => $main, 'subcategory' => $subcategory,
                'experience_years' => 0, 'description' => null, 'is_active' => true,
            ]);
            $coveredMains[] = $main;
        }

        $catalogCategories = collect(MarketplaceCategoryRegistry::definitions())->pluck('name')->all();
        foreach ($preferredMains as $main) {
            if (! in_array($main, $catalogCategories, true) || in_array($main, $coveredMains, true)) {
                continue;
            }

            $alreadyCovered = $user->services()
                ->where(fn ($query) => $query->where('main_category', $main)->orWhere('subcategory', $main))
                ->exists();
            if ($alreadyCovered) {
                continue;
            }

            UserService::create([
                'user_id' => $user->id, 'main_category' => $main, 'subcategory' => $main,
                'experience_years' => 0, 'description' => null, 'is_active' => true,
            ]);
        }

        $this->sync($user);
    }

    /** @return Collection<int, mixed> */
    private function legacyCategories(User $user): Collection
    {
        return collect([$user->service_category, $user->profession])
            ->merge((array) $user->service_subcategories)
            ->merge(collect((array) $user->pro_service_categories)->map(fn ($value) => $this->categoryName($value)))
            ->flatMap(fn ($value) => is_scalar($value) ? (preg_split('/[,;|]/u', (string) $value) ?: []) : []);
    }

    private function stripRemovedFromLegacyProfile(User $user, array $removed): void
    {
        $removedKeys = $this->keys($removed);
        if (! $user->exists || $removedKeys === []) {
            return;
        }

        $keep = fn ($value) => ! in_array(mb_strtolower(trim((string) $value)), $removedKeys, true);
        $mains = $this->clean(collect(preg_split('/[,;|]/u', (string) $user->service_category) ?: [])->filter($keep))->all();
        $subcategories = $this->clean(collect((array) $user->service_subcategories)->filter($keep))->all();
        $proCategories = collect((array) $user->pro_service_categories)
            ->filter(fn ($value) => $keep($this->categoryName($value)))->values()->all();

        $user->forceFill([
            'service_category' => $this->fitInColumn($mains, self::STRING_COLUMN_LENGTH),
            'service_subcategories' => $subcategories !== [] ? $subcategories : null,
            'pro_service_categories' => $proCategories !== [] ? $proCategories : null,
            'profession' => $keep($user->profession) ? $user->profession : ($subcategories[0] ?? null),
        ]);

        if ($user->isDirty()) {
            $user->save();
        }
    }

    /**
     * @param  list<string>  $subcategories
     * @param  list<string>  $mainCategories
     * @param  list<string>  $removed
     * @param  list<string>  $pausedNames  noms (en minuscules) des compétences en pause
     */
    private function profession(?string $current, array $subcategories, array $mainCategories, array $removed, array $pausedNames = []): ?string
    {
        $current = trim((string) $current);
        $fallback = $subcategories[0] ?? null;

        if ($current === '') {
            return $fallback;
        }

        $key = mb_strtolower($current);
        if (in_array($key, $this->keys([...$subcategories, ...$mainCategories]), true)) {
            return $current;
        }

        if (in_array($key, $this->keys($removed), true)) {
            return $fallback;
        }

        // Compétence en pause : le métier principal reste, il est toujours
        // d'actualité à la reprise. La pause retire seulement les correspondances.
        if (in_array($key, $pausedNames, true)) {
            return $current;
        }

        // Un nom du catalogue qui n'est plus une compétence active est un reste
        // d'une ancienne compétence ; un métier saisi librement est conservé.
        if ($fallback !== null && in_array($key, $this->catalogKeys(), true)) {
            return $fallback;
        }

        return $current;
    }

    /** Compétence qui couvre sa catégorie entière : sans métier, ou métier = nom de la catégorie. */
    private function isWholeCategory(UserService $service): bool
    {
        $subcategory = mb_strtolower(trim((string) $service->subcategory));

        return $subcategory === '' || $subcategory === mb_strtolower(trim((string) $service->main_category));
    }

    /**
     * Liste « A, B, C » pour une colonne varchar : garde les éléments entiers qui
     * tiennent, dans l'ordre (le premier est la catégorie principale), plutôt
     * qu'une valeur trop longue que PostgreSQL refuserait.
     *
     * @param  list<string>  $values
     */
    private function fitInColumn(array $values, int $maxLength): ?string
    {
        $kept = [];
        foreach ($values as $value) {
            $candidate = implode(', ', [...$kept, $value]);
            if (mb_strlen($candidate) <= $maxLength) {
                $kept[] = $value;
            }
        }

        return $kept !== [] ? implode(', ', $kept) : null;
    }

    /** @param  list<string>  $preferredMains */
    private function mainCategoryFor(string $subcategory, array $preferredMains): ?string
    {
        $definitions = MarketplaceCategoryRegistry::definitions();
        $matches = collect($definitions)
            ->filter(fn (array $definition) => in_array($subcategory, $definition['subcategories'] ?? [], true))
            ->pluck('name');

        if ($matches->isEmpty()) {
            return null;
        }

        return $matches->first(fn ($name) => in_array($name, $preferredMains, true)) ?? $matches->first();
    }

    /** Certaines anciennes fiches stockent l'identifiant (slug) de la catégorie. */
    private function categoryName(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        return MarketplaceCategoryRegistry::definitions()[$value]['name'] ?? $value;
    }

    /** @return list<string> */
    private function catalogKeys(): array
    {
        return collect(MarketplaceCategoryRegistry::definitions())
            ->flatMap(fn (array $definition) => [$definition['name'], ...($definition['subcategories'] ?? [])])
            ->map(fn ($name) => mb_strtolower(trim((string) $name)))
            ->unique()->values()->all();
    }

    /** @return list<string> */
    private function keys(array $values): array
    {
        return collect($values)
            ->filter(fn ($value) => is_scalar($value))
            ->map(fn ($value) => mb_strtolower(trim((string) $value)))
            ->filter()->values()->all();
    }

    /** @return Collection<int, string> */
    private function clean(iterable $values): Collection
    {
        return collect($values)
            ->filter(fn ($value) => is_scalar($value))
            ->map(fn ($value) => trim((string) $value))
            ->filter(fn (string $value) => $value !== '')
            ->unique(fn (string $value) => mb_strtolower($value))
            ->values();
    }
}
