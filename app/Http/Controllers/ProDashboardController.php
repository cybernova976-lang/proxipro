<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use App\Models\ProClient;
use App\Models\ProDocument;
use App\Models\ProInvoice;
use App\Models\ProQuote;
use App\Models\ServiceOrder;
use App\Models\ServiceProposal;
use App\Models\User;
use App\Services\ProviderSubscriptionService;
use App\Support\MarketplaceCategoryRegistry;
use App\Support\PlatformFeatures;
use App\Support\ProviderSubscriptionPlans;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProDashboardController extends Controller
{
    public function __construct(private ProviderSubscriptionService $providerSubscriptionService)
    {
        $this->middleware('auth');
    }

    /**
     * Vérifie que l'utilisateur est bien un professionnel.
     * Redirige vers le dashboard (teaser) au lieu d'un abort 403.
     */
    private function ensurePro()
    {
        $user = Auth::user();
        if (! $user->isProfessionnel() && ! $user->isServiceProvider()) {
            throw new \Illuminate\Http\Exceptions\HttpResponseException(
                redirect()->route('pro.dashboard')
                    ->with('warning', 'Cette fonctionnalité est réservée aux prestataires enregistrés. Vous devez d\'abord vous inscrire en tant que prestataire pour utiliser les outils de l\'Espace Pro.')
            );
        }

        return $user;
    }

    /**
     * Devis et factures : ouverts à tout membre connecté, particulier ou
     * professionnel, sans profil prestataire ni justificatif (décision du
     * fondateur du 24/11/2026). Le quota offert est appliqué à la création et à
     * l'émission (App\Services\CommercialDocumentAllowance).
     */
    private function documentMember(): User
    {
        return Auth::user();
    }

    private function allowance(): \App\Services\CommercialDocumentAllowance
    {
        return app(\App\Services\CommercialDocumentAllowance::class);
    }

    /** Refuse proprement la création au-delà des documents offerts, sans rien enregistrer. */
    private function ensureCanCreateDocument(User $user, string $type): void
    {
        if (! $this->allowance()->canCreate($user, $type)) {
            throw new \Illuminate\Http\Exceptions\HttpResponseException(
                redirect()->route($type === \App\Services\CommercialDocumentAllowance::QUOTE ? 'pro.quotes' : 'pro.invoices')
                    ->with('warning', $this->allowance()->limitReachedMessage($type))
            );
        }
    }

    /**
     * Seul un document compris dans les offerts (ou un abonné Premium) reçoit un
     * numéro définitif. Le SIRET n'est plus exigé : le document reprend ce que le
     * membre a renseigné, sans rien inventer.
     */
    private function ensureCanIssueCommercialDocuments($user, ProQuote|ProInvoice $document): void
    {
        if (! $this->allowance()->canIssue($user, $document)) {
            $isQuote = $document instanceof ProQuote;
            // Tant que l'abonnement n'est pas ouvert, on ne renvoie pas vers une page
            // où rien ne peut être souscrit : le membre revient à sa liste de documents.
            $target = $this->allowance()->subscriptionsOpen()
                ? 'pro.subscription'
                : ($isQuote ? 'pro.quotes' : 'pro.invoices');

            throw new \Illuminate\Http\Exceptions\HttpResponseException(
                redirect()->route($target)->with(
                    'warning',
                    $this->allowance()->issueRefusedMessage($isQuote
                        ? \App\Services\CommercialDocumentAllowance::QUOTE
                        : \App\Services\CommercialDocumentAllowance::INVOICE)
                )
            );
        }
    }

    /** Une facture dont une ligne est sans TVA doit porter la mention d'exonération. */
    private function requireVatExemptionReason(array $items, float $taxRate, ?string $reason): void
    {
        if (\App\Support\DocumentTotals::hasZeroRate($items, $taxRate) && blank($reason)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'vat_exemption_reason' => 'Indiquez la mention d’exonération de TVA : une partie de cette facture est sans TVA.',
            ]);
        }
    }

    private function proClientRule(int $userId)
    {
        return Rule::exists('pro_clients', 'id')->where(
            fn ($query) => $query->where('provider_id', $userId)
        );
    }

    private function resolveProClient($user, array $validated): ?ProClient
    {
        if (! empty($validated['client_id'])) {
            return $user->proClients()->findOrFail($validated['client_id']);
        }

        $query = $user->proClients();
        $existing = filled($validated['client_email'] ?? null)
            ? $query->where('email', $validated['client_email'])->first()
            : $query->where('name', $validated['client_name'])->first();

        if ($existing) {
            return $existing;
        }

        return $user->proClients()->create([
            'name' => $validated['client_name'],
            'email' => $validated['client_email'] ?? null,
            'phone' => $validated['client_phone'] ?? null,
            'address' => $validated['client_address'] ?? null,
            'company' => $validated['client_company'] ?? null,
            'status' => 'active',
            'source' => 'commercial_document',
            'last_interaction_at' => now(),
        ]);
    }

    private function issueQuote(ProQuote $quote, $user): void
    {
        if ($quote->status === 'draft' && $quote->issued_at === null) {
            $this->ensureCanIssueCommercialDocuments($user, $quote);
        }

        if ($quote->status === 'draft' && $quote->issued_at === null) {
            $quote->update([
                'quote_number' => ProQuote::generateNumber($user->id),
                'seller_snapshot' => $user->commercialIdentitySnapshot(),
                'issued_at' => now(),
                // Émis sous Premium : le PDF reste sans filigrane « version gratuite » pour toujours.
            ] + $this->allowance()->issueStamp($user));
        } elseif ($quote->status === 'draft') {
            // Reprise après un envoi échoué : le numéro est gardé, la marque Premium suit le moment réel de l'envoi.
            $quote->update($this->allowance()->issueStamp($user));
        }
    }

    private function finalizeInvoice(ProInvoice $invoice, $user): void
    {
        if ($invoice->status === 'draft' && $invoice->finalized_at === null) {
            $this->ensureCanIssueCommercialDocuments($user, $invoice);
        }

        if ($invoice->status === 'draft' && $invoice->finalized_at === null) {
            $invoice->update([
                'invoice_number' => ProInvoice::generateNumber($user->id),
                'seller_snapshot' => $user->commercialIdentitySnapshot(),
                'finalized_at' => now(),
                // Acomptes déjà facturés sur le même devis : déduits une fois pour toutes.
                'deposit_deducted' => app(\App\Services\InvoiceAdjustments::class)->deductibleDeposits($invoice),
                // Émise sous Premium : le PDF reste sans filigrane « version gratuite » pour toujours.
            ] + $this->allowance()->issueStamp($user));
        } elseif ($invoice->status === 'draft') {
            // Reprise après un envoi échoué : le numéro est gardé, la marque Premium suit le moment réel de l'envoi.
            $invoice->update($this->allowance()->issueStamp($user));
        }
    }

    // =========================================
    // TABLEAU DE BORD
    // =========================================

    public function dashboard()
    {
        $user = Auth::user();
        $isPro = $user->isProfessionnel() || $user->isServiceProvider();

        // Accessible à tous : un membre qui ne propose pas encore de services
        // voit le parcours pour devenir prestataire, avec ses étapes réelles.
        if (! $isPro) {
            return view('pro.dashboard', [
                'user' => $user,
                'isPro' => false,
                'joinSteps' => [
                    ['done' => $user->verificationProfileMissingFields() === [], 'label' => 'Compléter votre profil',
                        'detail' => 'Nom, téléphone, adresse et photo : ce que les clients voient avant de vous contacter.'],
                    ['done' => $user->hasVerifiedProfileBadge(), 'label' => 'Obtenir le badge « Profil vérifié »',
                        'detail' => 'La vérification rassure les clients et protège la plateforme.'],
                    ['done' => false, 'label' => 'Choisir votre métier et votre statut',
                        'detail' => 'Particulier, auto-entrepreneur ou société : vous pourrez changer plus tard.'],
                ],
            ]);
        }

        $isProfessional = $user->isProfessionnel();
        $providerCategories = $this->providerServiceCategories($user);
        $reviewsCount = $user->reviewsReceived()->count();

        // Quatre chiffres seulement, tous mesurés. Le montant encaissé
        // n'apparaît que pour un professionnel, seul autorisé à émettre des factures.
        $stats = [
            'new_opportunities' => $providerCategories->isEmpty()
                ? 0
                : $this->matchingOpportunityQuery($user, $providerCategories)->count(),
            'pending_proposals' => ServiceProposal::query()
                ->where('provider_id', $user->id)
                ->where('status', ServiceProposal::STATUS_PENDING)
                ->count(),
            // Une commande en attente d'acceptation n'est pas encore une mission :
            // elle figure dans « À faire maintenant » et dans « À examiner ».
            'active_missions' => ServiceOrder::query()
                ->where('seller_id', $user->id)
                ->whereIn('status', [
                    ServiceOrder::STATUS_AWAITING_PAYMENT,
                    ServiceOrder::STATUS_FUNDED,
                    ServiceOrder::STATUS_DISPUTED,
                ])
                ->count(),
            'monthly_revenue' => (float) $user->proInvoices()->where('status', 'paid')
                ->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])->sum(DB::raw(ProInvoice::NET_TOTAL_SQL)),
            'reviews_count' => $reviewsCount,
            'average_rating' => $reviewsCount > 0 ? round((float) $user->reviewsReceived()->avg('rating'), 1) : null,
        ];

        $nextSteps = app(\App\Services\ProNextSteps::class)->for($user, [
            'new_opportunities' => $stats['new_opportunities'],
            'has_services' => $providerCategories->isNotEmpty(),
        ]);

        // Devis et factures récents dans une seule liste, du plus récent au plus ancien.
        // Ouverts à tous depuis le 24/11/2026 : la liste ne dépend plus du statut.
        $recentDocuments = $user->proQuotes()->latest()->take(5)->get()->map(fn (ProQuote $quote) => [
            'type' => 'Devis', 'number' => $quote->quote_number, 'client' => $quote->client_name,
            'total' => (float) $quote->total, 'status' => $quote->getStatusLabel(), 'tone' => $quote->getStatusColor(),
            'url' => route('pro.quotes.show', $quote->id), 'date' => $quote->created_at,
        ])->concat($user->proInvoices()->latest()->take(5)->get()->map(fn (ProInvoice $invoice) => [
            // Un avoir est affiché comme tel, en négatif : ce n'est pas une recette.
            'type' => $invoice->isCreditNote() ? 'Avoir' : ($invoice->isDeposit() ? 'Acompte' : 'Facture'),
            'number' => $invoice->invoice_number, 'client' => $invoice->client_name,
            'total' => $invoice->isCreditNote() ? -abs((float) $invoice->total) : (float) $invoice->total,
            'status' => $invoice->getStatusLabel(), 'tone' => $invoice->getStatusColor(),
            'url' => route('pro.invoices.show', $invoice->id), 'date' => $invoice->created_at,
        ]))->sortByDesc('date')->take(5)->values();

        return view('pro.dashboard', [
            'user' => $user,
            'isPro' => true,
            'isProfessional' => $isProfessional,
            'stats' => $stats,
            'nextSteps' => $nextSteps,
            'recentDocuments' => $recentDocuments,
            'subscription' => $user->proSubscription,
        ]);
    }

    /**
     * Tableau de prospection du prestataire, depuis la demande compatible
     * jusqu'a la mission terminee.
     */
    public function opportunities()
    {
        $user = $this->ensurePro();
        $providerCategories = $this->providerServiceCategories($user);

        $newOpportunities = $providerCategories->isEmpty()
            ? collect()
            : $this->matchingOpportunityQuery($user, $providerCategories)
                ->with(['user'])
                ->when(filled($user->city), function ($query) use ($user) {
                    $query->orderByRaw('CASE WHEN city = ? OR location = ? THEN 0 ELSE 1 END', [
                        $user->city,
                        $user->city,
                    ]);
                })
                ->orderByDesc('is_urgent')
                ->latest()
                ->take(18)
                ->get();

        $sentProposals = ServiceProposal::query()
            ->with(['ad.user', 'serviceOrder'])
            ->where('provider_id', $user->id)
            ->where('status', ServiceProposal::STATUS_PENDING)
            ->latest()
            ->take(18)
            ->get();

        // Commandes réservées par un client : une réponse est attendue, elles
        // ouvrent la colonne « À examiner » au lieu de gonfler les missions en cours.
        $ordersToAnswer = ServiceOrder::query()
            ->with(['ad', 'buyer'])
            ->where('seller_id', $user->id)
            ->where('status', ServiceOrder::STATUS_PENDING_ACCEPTANCE)
            ->latest()
            ->take(18)
            ->get();

        $activeMissions = ServiceOrder::query()
            ->with(['ad', 'buyer'])
            ->where('seller_id', $user->id)
            ->whereIn('status', [
                ServiceOrder::STATUS_AWAITING_PAYMENT,
                ServiceOrder::STATUS_FUNDED,
                ServiceOrder::STATUS_DISPUTED,
            ])
            ->latest()
            ->take(18)
            ->get();

        $completedMissions = ServiceOrder::query()
            ->with(['ad', 'buyer'])
            ->where('seller_id', $user->id)
            ->where('status', ServiceOrder::STATUS_COMPLETED)
            ->where('payment_status', ServiceOrder::PAYMENT_RELEASED)
            ->latest('released_at')
            ->take(12)
            ->get();

        $pipelineCounts = [
            'new' => $ordersToAnswer->count() + $newOpportunities->count(),
            'proposed' => $sentProposals->count(),
            'active' => $activeMissions->count(),
            'completed' => $completedMissions->count(),
        ];

        // Métiers affichés : les compétences actives, par leur nom de métier
        // (la catégorie seule quand la compétence couvre toute la catégorie).
        $providerTrades = $this->providerTrades($user, $providerCategories);

        return view('pro.opportunities', compact(
            'user',
            'providerCategories',
            'providerTrades',
            'ordersToAnswer',
            'newOpportunities',
            'sentProposals',
            'activeMissions',
            'completedMissions',
            'pipelineCounts'
        ));
    }

    /**
     * Compétences actives de « Mes compétences » ; les colonnes du profil ne
     * servent qu'aux comptes qui n'en ont encore aucune (voir ProviderSkills).
     */
    private function providerServiceCategories(User $user)
    {
        return app(\App\Services\ProviderSkills::class)->matchingCategories($user);
    }

    /**
     * Métiers choisis pendant la configuration de l'Espace Pro : pour un membre
     * qui a déjà des compétences enregistrées, ils deviennent des compétences
     * (sinon ils seraient ignorés), et le profil est recalculé.
     */
    private function adoptOnboardingSkills(User $user, array $updateData): void
    {
        if (! array_key_exists('pro_service_categories', $updateData) && ! array_key_exists('service_subcategories', $updateData)) {
            return;
        }

        app(\App\Services\ProviderSkills::class)->adoptSelections(
            $user,
            array_values((array) ($updateData['pro_service_categories'] ?? [])),
            array_values((array) ($updateData['service_subcategories'] ?? [])),
        );
    }

    /**
     * Métiers à afficher dans « Demandes reçues pour » : une compétence s'affiche
     * par son métier (« Électricien »), la catégorie n'apparaît que pour une
     * compétence qui la couvre entière. Compte de l'ancien parcours : ses catégories.
     */
    private function providerTrades(User $user, $providerCategories)
    {
        if (! app(\App\Services\ProviderSkills::class)->hasSkillRecords($user)) {
            return $providerCategories->values();
        }

        return $user->services()->where('is_active', true)->orderBy('id')->get(['main_category', 'subcategory'])
            ->map(fn ($service) => trim((string) ($service->subcategory ?: $service->main_category)))
            ->filter()->unique(fn ($trade) => mb_strtolower($trade))->values();
    }

    /**
     * Catégories entières couvertes par le prestataire. Une compétence précise
     * (« Électricien ») ne rattache pas les autres métiers de sa catégorie :
     * après la suppression de « Plombier », une demande de plomberie de
     * « Bricolage & Travaux » ne doit plus être proposée. Compte de l'ancien
     * parcours, sans compétence enregistrée : ses catégories de profil font foi.
     */
    private function wholeProviderCategories(User $user, $providerCategories)
    {
        if (! app(\App\Services\ProviderSkills::class)->hasSkillRecords($user)) {
            return $providerCategories->values();
        }

        // Compétence « catégorie entière » : sans métier, ou dont le métier porte le nom de la catégorie.
        return $user->services()->where('is_active', true)
            ->where(fn ($query) => $query->whereNull('subcategory')->orWhere('subcategory', '')
                ->orWhereColumn('subcategory', 'main_category'))
            ->pluck('main_category')->filter()->unique()->values();
    }

    private function matchingOpportunityQuery(User $user, $providerCategories)
    {
        $wholeCategories = $this->wholeProviderCategories($user, $providerCategories);

        return Ad::query()
            ->marketplaceActive()
            ->where('service_type', 'demande')
            ->where('user_id', '!=', $user->id)
            ->where(function ($query) use ($providerCategories, $wholeCategories) {
                // Le métier de la demande est une compétence active…
                $query->whereIn('category', $providerCategories)
                    // … ou la demande ne précise aucun métier dans une catégorie couverte…
                    ->orWhere(function ($generic) use ($providerCategories) {
                        $generic->whereIn('main_category', $providerCategories)
                            ->where(fn ($noTrade) => $noTrade->whereNull('category')->orWhere('category', '')
                                ->orWhereColumn('category', 'main_category'));
                    })
                    // … ou le prestataire couvre la catégorie entière.
                    ->when($wholeCategories->isNotEmpty(), fn ($whole) => $whole->orWhereIn('main_category', $wholeCategories));
            })
            ->where(function ($query) use ($providerCategories) {
                $query->where('visibility', 'public')->orWhereNull('visibility')
                    ->orWhere(function ($targeted) use ($providerCategories) {
                        $targeted->where('visibility', 'pro_targeted')->where(function ($targets) use ($providerCategories) {
                            $targets->whereNull('target_categories')->orWhereJsonLength('target_categories', 0);
                            foreach ($providerCategories as $category) {
                                $targets->orWhereJsonContains('target_categories', $category);
                            }
                        });
                    });
            })
            ->whereDoesntHave('serviceProposals', fn ($query) => $query->where('provider_id', $user->id))
            ->whereDoesntHave('serviceProposals', fn ($query) => $query->where('status', ServiceProposal::STATUS_ACCEPTED))
            ->whereDoesntHave('serviceOrders', fn ($query) => $query->whereIn('status', [
                ServiceOrder::STATUS_AWAITING_PAYMENT, ServiceOrder::STATUS_FUNDED,
                ServiceOrder::STATUS_COMPLETED, ServiceOrder::STATUS_DISPUTED,
            ]));
    }

    // =========================================
    // PROFIL PROFESSIONNEL
    // =========================================

    public function profile()
    {
        $user = $this->ensurePro();
        $reviews = $user->reviewsReceived()->with('reviewer')->latest()->paginate(10);
        $services = $user->services()->where('is_active', true)->get();

        return view('pro.profile', compact('user', 'reviews', 'services'));
    }

    public function editProfile()
    {
        $user = $this->ensurePro();
        $services = $user->services;
        $categories = $this->getServiceCategories();

        return view('pro.profile-edit', compact('user', 'services', 'categories'));
    }

    public function updateProfile(Request $request)
    {
        $user = $this->ensurePro();

        // Convert comma-separated specialties string to array
        if ($request->has('specialties') && is_string($request->input('specialties'))) {
            $specialties = array_filter(array_map('trim', explode(',', $request->input('specialties'))));
            $request->merge(['specialties' => ! empty($specialties) ? array_values($specialties) : null]);
        }

        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'bio' => 'nullable|string|max:1000',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'website_url' => 'nullable|url|max:255',
            'hourly_rate' => 'nullable|numeric|min:0|max:9999',
            'years_experience' => 'nullable|integer|min:0|max:99',
            'insurance_number' => 'nullable|string|max:100',
            'specialties' => 'nullable|array',
            'social_links' => 'nullable|array',
            'avatar' => 'nullable|image|max:2048',
            'company_logo' => 'nullable|file|mimes:jpeg,jpg,png|max:'.\App\Support\DocumentLogo::MAX_KILOBYTES,
        ], [
            'company_logo.mimes' => 'Le logo doit être une image PNG ou JPG.',
            'company_logo.max' => 'Le logo ne doit pas dépasser '.(int) (\App\Support\DocumentLogo::MAX_KILOBYTES / 1024).' Mo.',
        ]);
        unset($validated['company_logo']);

        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', config('filesystems.default', 'public'));
            $validated['avatar'] = $path;
        }

        // Logo des devis et factures : avantage Premium. Le retrait reste toujours possible.
        if ($request->boolean('remove_company_logo')) {
            \App\Support\DocumentLogo::remove($user);
        } elseif ($request->hasFile('company_logo')) {
            if (! $user->hasActiveProSubscription()) {
                return back()->withInput()->withErrors(['company_logo' => 'Le logo sur les devis et factures est un avantage Premium.']);
            }
            if (! \App\Support\DocumentLogo::store($user, $request->file('company_logo'))) {
                return back()->withInput()->withErrors(['company_logo' => 'Ce fichier n’a pas pu être lu. Envoyez une image PNG ou JPG.']);
            }
        }

        $user->update($validated);

        return redirect()->route('pro.profile')->with('success', 'Profil mis à jour avec succès.');
    }

    // =========================================
    // CLIENTS
    // =========================================

    public function clients()
    {
        $user = $this->ensurePro();
        $clients = $user->proClients()->latest('last_interaction_at')->paginate(15);

        return view('pro.clients', compact('user', 'clients'));
    }

    public function storeClient(Request $request)
    {
        $user = $this->ensurePro();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'company' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        $validated['provider_id'] = $user->id;
        $validated['status'] = 'active';
        $validated['source'] = 'manual';
        $validated['last_interaction_at'] = now();

        ProClient::create($validated);

        return redirect()->route('pro.clients')->with('success', 'Client ajouté avec succès.');
    }

    public function updateClient(Request $request, $id)
    {
        $user = $this->ensurePro();
        $client = $user->proClients()->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'company' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'status' => 'in:active,inactive,prospect',
        ]);

        $client->update($validated);

        return redirect()->route('pro.clients')->with('success', 'Client mis à jour.');
    }

    public function deleteClient($id)
    {
        $user = $this->ensurePro();
        $client = $user->proClients()->findOrFail($id);
        $client->delete();

        return redirect()->route('pro.clients')->with('success', 'Client supprimé.');
    }

    // =========================================
    // DEVIS
    // =========================================

    public function quotes()
    {
        $user = $this->documentMember();
        $quotes = $user->proQuotes()->with('client')->latest()->paginate(15);
        $clients = $user->proClients()->orderBy('name')->get();
        $quoteStats = [
            'waiting' => $user->proQuotes()->whereIn('status', ['draft', 'sent'])->count(),
            'accepted' => $user->proQuotes()->where('status', 'accepted')->count(),
            'refused' => $user->proQuotes()->where('status', 'refused')->count(),
        ];

        $quoteTemplates = $user->proQuoteTemplates()->latest()->get();
        $allowance = $this->allowance()->for($user, \App\Services\CommercialDocumentAllowance::QUOTE);

        return view('pro.quotes', compact('user', 'quotes', 'clients', 'quoteStats', 'quoteTemplates', 'allowance'));
    }

    public function createQuote(Request $request)
    {
        $user = $this->documentMember();
        $this->ensureCanCreateDocument($user, \App\Services\CommercialDocumentAllowance::QUOTE);
        $clients = $user->proClients()->orderBy('name')->get();
        $quoteTemplates = $user->proQuoteTemplates()->latest()->get();

        // Partir d'un modèle (Premium) : le formulaire est prérempli, rien n'est enregistré.
        $template = null;
        if ($request->filled('modele')) {
            $template = $quoteTemplates->firstWhere('id', (int) $request->query('modele'));
            abort_if($template === null, 404);
            if (! $user->hasActiveProSubscription()) {
                return redirect()->route('pro.subscription')
                    ->with('warning', 'Les modèles de devis font partie de Premium. Vos modèles enregistrés sont conservés.');
            }
        }

        return view('pro.quote-create', compact('user', 'clients', 'quoteTemplates', 'template'));
    }

    public function storeQuote(Request $request)
    {
        $user = $this->documentMember();
        $this->ensureCanCreateDocument($user, \App\Services\CommercialDocumentAllowance::QUOTE);

        $validated = $request->validate([
            'client_id' => ['nullable', $this->proClientRule($user->id)],
            'client_name' => 'required|string|max:255',
            'client_email' => 'nullable|email|max:255',
            'client_phone' => 'nullable|string|max:20',
            'client_address' => 'nullable|string|max:500',
            'client_company' => 'nullable|string|max:255',
            'client_registration_number' => 'nullable|string|max:64',
            'client_vat_number' => 'nullable|string|max:64',
            'subject' => 'required|string|max:255',
            'operation_type' => 'required|in:services,goods,mixed',
            'execution_location' => 'nullable|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:500',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'valid_until' => 'nullable|date|after:today',
            'is_free' => 'required|boolean',
            'deposit_percentage' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string|max:2000',
            'conditions' => 'nullable|string|max:2000',
        ]);

        // Totaux : remise et TVA par ligne (App\Support\DocumentTotals).
        $taxRate = (float) ($validated['tax_rate'] ?? $user->defaultVatRate());
        $totals = \App\Support\DocumentTotals::compute($validated['items'], $taxRate);
        $items = collect($totals['items']);
        $subtotal = $totals['subtotal'];
        $tax = $totals['tax_amount'];
        $total = $totals['total'];

        $client = $this->resolveProClient($user, $validated);

        ProQuote::create([
            'user_id' => $user->id,
            'pro_client_id' => $client?->id,
            'quote_number' => 'BROUILLON-DEV-'.strtoupper(Str::random(10)),
            'client_name' => $validated['client_name'],
            'client_email' => $validated['client_email'] ?? null,
            'client_phone' => $validated['client_phone'] ?? null,
            'client_address' => $validated['client_address'] ?? null,
            'client_company' => $validated['client_company'] ?? null,
            'client_registration_number' => $validated['client_registration_number'] ?? null,
            'client_vat_number' => $validated['client_vat_number'] ?? null,
            'subject' => $validated['subject'],
            'operation_type' => $validated['operation_type'],
            'execution_location' => $validated['execution_location'] ?? null,
            'currency' => 'EUR',
            'is_free' => (bool) $validated['is_free'],
            'deposit_percentage' => $validated['deposit_percentage'] ?? null,
            'items' => $items->toArray(),
            'subtotal' => $subtotal,
            'tax_rate' => $taxRate,
            'tax_amount' => $tax,
            'total' => $total,
            'status' => 'draft',
            'valid_until' => $validated['valid_until'] ?? now()->addDays(30),
            'notes' => $validated['notes'] ?? null,
            'conditions' => $validated['conditions'] ?? null,
        ]);

        return redirect()->route('pro.quotes')->with('success', 'Brouillon de devis créé. Vérifiez-le avant l’envoi au client.');
    }

    public function showQuote($id)
    {
        $user = $this->documentMember();
        $quote = $user->proQuotes()->with('client')->findOrFail($id);

        return view('pro.quote-show', compact('user', 'quote'));
    }

    public function updateQuoteStatus(Request $request, $id)
    {
        $user = $this->documentMember();
        $quote = $user->proQuotes()->findOrFail($id);

        $request->validate(['status' => 'required|in:sent,accepted,refused,expired']);
        $target = $request->string('status')->toString();
        $allowedTransitions = [
            'draft' => ['sent'],
            'sent' => ['accepted', 'refused', 'expired'],
        ];

        if (! in_array($target, $allowedTransitions[$quote->status] ?? [], true)) {
            return back()->with('error', 'Cette transition est impossible : un document émis conserve son historique.');
        }

        if ($target === 'sent') {
            $this->issueQuote($quote, $user);
        }

        $timestamps = match ($target) {
            'sent' => ['sent_at' => now()],
            'accepted' => ['accepted_at' => now(), 'response_channel' => 'manual'],
            'refused' => ['refused_at' => now(), 'response_channel' => 'manual'],
            default => [],
        };
        $quote->update(['status' => $target] + $timestamps);

        // Si le devis est accepté, on peut auto-générer une facture
        if ($target === 'accepted') {
            return redirect()->route('pro.quotes')->with('success', 'Devis accepté ! Vous pouvez maintenant créer une facture associée.');
        }

        return redirect()->route('pro.quotes')->with('success', 'Statut du devis mis à jour.');
    }

    public function editQuote($id)
    {
        $user = $this->documentMember();
        $quote = $user->proQuotes()->findOrFail($id);
        if (! $quote->isEditable()) {
            return redirect()->route('pro.quotes.show', $quote)->with('error', 'Un devis émis ne peut plus être modifié. Dupliquez-le pour créer une nouvelle version.');
        }
        $clients = $user->proClients()->orderBy('name')->get();

        return view('pro.quote-edit', compact('user', 'quote', 'clients'));
    }

    public function updateQuote(Request $request, $id)
    {
        $user = $this->documentMember();
        $quote = $user->proQuotes()->findOrFail($id);
        if (! $quote->isEditable()) {
            return redirect()->route('pro.quotes.show', $quote)->with('error', 'Un devis émis ne peut plus être modifié.');
        }

        $validated = $request->validate([
            'client_id' => ['nullable', $this->proClientRule($user->id)],
            'client_name' => 'required|string|max:255',
            'client_email' => 'nullable|email|max:255',
            'client_phone' => 'nullable|string|max:20',
            'client_address' => 'nullable|string|max:500',
            'client_company' => 'sometimes|nullable|string|max:255',
            'client_registration_number' => 'sometimes|nullable|string|max:64',
            'client_vat_number' => 'sometimes|nullable|string|max:64',
            'subject' => 'required|string|max:255',
            'operation_type' => 'sometimes|required|in:services,goods,mixed',
            'execution_location' => 'sometimes|nullable|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:500',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'valid_until' => 'nullable|date',
            'is_free' => 'sometimes|required|boolean',
            'deposit_percentage' => 'sometimes|nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string|max:2000',
            'conditions' => 'nullable|string|max:2000',
        ]);

        // Totaux : remise et TVA par ligne (App\Support\DocumentTotals).
        $taxRate = (float) ($validated['tax_rate'] ?? $user->defaultVatRate());
        $totals = \App\Support\DocumentTotals::compute($validated['items'], $taxRate);
        $items = collect($totals['items']);
        $subtotal = $totals['subtotal'];
        $tax = $totals['tax_amount'];
        $total = $totals['total'];

        // Champs repris de la création : enregistrés seulement s'ils ont été envoyés.
        $extra = collect($validated)->only([
            'client_company', 'client_registration_number', 'client_vat_number',
            'operation_type', 'execution_location', 'deposit_percentage',
        ])->all();
        if (array_key_exists('is_free', $validated)) {
            $extra['is_free'] = (bool) $validated['is_free'];
        }

        $quote->update($extra + [
            'pro_client_id' => $this->resolveProClient($user, $validated)?->id,
            'client_name' => $validated['client_name'],
            'client_email' => $validated['client_email'] ?? null,
            'client_phone' => $validated['client_phone'] ?? null,
            'client_address' => $validated['client_address'] ?? null,
            'subject' => $validated['subject'],
            'items' => $items->toArray(),
            'subtotal' => $subtotal,
            'tax_rate' => $taxRate,
            'tax_amount' => $tax,
            'total' => $total,
            'valid_until' => $validated['valid_until'] ?? $quote->valid_until,
            'notes' => $validated['notes'] ?? null,
            'conditions' => $validated['conditions'] ?? null,
        ]);

        return redirect()->route('pro.quotes.show', $quote->id)->with('success', 'Devis mis à jour avec succès.');
    }

    /** Copie d'un devis en nouveau brouillon : lignes, client et conditions repris, rien de son historique. */
    public function duplicateQuote($id)
    {
        $user = $this->documentMember();
        $source = $user->proQuotes()->findOrFail($id);
        // Une copie est un nouveau devis : elle compte dans les devis offerts.
        $this->ensureCanCreateDocument($user, \App\Services\CommercialDocumentAllowance::QUOTE);

        $copy = $source->replicate([
            'quote_number', 'status', 'issued_at', 'sent_at', 'accepted_at', 'refused_at', 'seller_snapshot',
            'sent_to_email', 'reminder_enabled', 'reminder_sent_at', 'public_token', 'response_name', 'response_channel',
            // La copie sera émise à son tour : elle ne reprend pas l'émission sous Premium de l'original.
            'issued_with_premium',
        ]);
        $copy->fill([
            'quote_number' => 'BROUILLON-DEV-'.strtoupper(Str::random(10)),
            'status' => 'draft',
            'reminder_enabled' => false,
            // Une validité déjà dépassée n'est pas reprise.
            'valid_until' => $source->valid_until && $source->valid_until->isFuture() ? $source->valid_until : null,
        ])->save();

        return redirect()->route('pro.quotes.edit', $copy->id)
            ->with('success', 'Copie créée en brouillon. Modifiez-la, puis envoyez-la : elle recevra son propre numéro.');
    }

    public function deleteQuote($id)
    {
        $user = $this->documentMember();
        $quote = $user->proQuotes()->findOrFail($id);
        if (! $quote->isEditable()) {
            return back()->with('error', 'Seul un brouillon peut être supprimé. Les devis émis sont conservés pour garantir la traçabilité.');
        }
        $quote->delete();

        return redirect()->route('pro.quotes')->with('success', 'Devis supprimé avec succès.');
    }

    public function downloadQuote($id)
    {
        $user = $this->documentMember();
        $quote = $user->proQuotes()->with('client')->findOrFail($id);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pro.quote-pdf', compact('user', 'quote'));
        $pdf->setPaper('a4');

        return $pdf->download('Devis_'.$quote->quote_number.'.pdf');
    }

    public function sendQuoteEmail(Request $request, $id)
    {
        $user = $this->documentMember();
        $quote = $user->proQuotes()->with('client')->findOrFail($id);

        $request->validate([
            'email' => 'required|email',
            'message' => 'nullable|string|max:2000',
        ]);

        $this->issueQuote($quote, $user);
        $quote->refresh();
        $wasDraft = $quote->status === 'draft';
        if ($wasDraft) {
            $quote->update(['status' => 'sent', 'sent_at' => now()]);
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pro.quote-pdf', compact('user', 'quote'));
        $pdf->setPaper('a4');

        try {
            Mail::send('emails.quote', [
                'quote' => $quote,
                'user' => $user,
                'customMessage' => $request->message,
                'responseUrl' => $quote->isAwaitingResponse() ? $quote->responseUrl() : null,
            ], function ($mail) use ($request, $quote, $user, $pdf) {
                $mail->to($request->email)
                    ->subject('Devis '.$quote->quote_number.' - '.($user->company_name ?? $user->name))
                    ->attachData($pdf->output(), 'Devis_'.$quote->quote_number.'.pdf', [
                        'mime' => 'application/pdf',
                    ]);
            });
        } catch (\Throwable $exception) {
            // L'e-mail n'est pas parti : le devis émis ne passe pas pour envoyé.
            if ($wasDraft) {
                $quote->update(['status' => 'draft', 'sent_at' => null]);
            }
            throw $exception;
        }

        // Relance automatique unique (Premium), à l'adresse réellement utilisée.
        $reminder = $request->boolean('auto_reminder') && $user->hasActiveProSubscription()
            && $quote->status === 'sent' && $quote->reminder_sent_at === null;
        $quote->update(['sent_to_email' => $request->email, 'reminder_enabled' => $reminder]);

        return redirect()->route('pro.quotes.show', $id)->with('success', 'Devis envoyé par email à '.$request->email
            .($reminder ? '. Une relance partira dans '.\App\Services\QuoteReminderService::DELAY_DAYS.' jours s’il reste sans réponse.' : ''));
    }

    public function sendQuoteMessage(Request $request, $id)
    {
        $user = $this->documentMember();
        $quote = $user->proQuotes()->with('client')->findOrFail($id);

        $request->validate([
            'recipient_id' => 'required|exists:users,id',
            'message' => 'nullable|string|max:2000',
        ]);

        $recipient = User::findOrFail($request->recipient_id);
        abort_if($recipient->id === $user->id, 422, 'Vous ne pouvez pas vous envoyer un devis à vous-même.');

        $this->issueQuote($quote, $user);
        $quote->refresh();

        // Get or create conversation
        $conversation = \App\Models\Conversation::getOrCreate($user->id, $recipient->id, 'Devis '.$quote->quote_number);

        // Build quote summary message
        $content = '📄 *Devis '.$quote->quote_number."*\n\n"
            .'📋 Objet : '.$quote->subject."\n"
            .'💰 Montant TTC : '.number_format($quote->total, 2, ',', ' ')."€\n"
            .'📅 Date : '.$quote->created_at->format('d/m/Y')."\n";
        if ($quote->valid_until) {
            $content .= "⏳ Valide jusqu'au : ".$quote->valid_until->format('d/m/Y')."\n";
        }
        $downloadUrl = URL::temporarySignedRoute(
            'pro.quotes.shared-download',
            now()->addDays(30),
            ['id' => $quote->id, 'recipient' => $recipient->id]
        );
        $content .= "\n🔗 Télécharger le devis (lien personnel valable 30 jours) : ".$downloadUrl;
        if ($quote->status === 'draft') {
            $quote->update(['status' => 'sent', 'sent_at' => now()]);
        }
        if ($quote->isAwaitingResponse()) {
            $content .= "\n✅ Répondre au devis en ligne (accepter ou refuser) : ".$quote->responseUrl();
        }

        if ($request->message) {
            $content .= "\n\n💬 Message :\n".$request->message;
        }

        $message = \App\Models\Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'content' => $content,
        ]);

        \App\Support\SafeNotifier::send($recipient, new \App\Notifications\NewMessageNotification($message, $conversation, $user));

        // Mark as sent
        if ($quote->status === 'draft') {
            $quote->update(['status' => 'sent', 'sent_at' => now()]);
        }

        return redirect()->route('pro.quotes.show', $id)->with('success', 'Devis envoyé via la messagerie à '.$recipient->name);
    }

    public function downloadSharedQuote(Request $request, $id)
    {
        abort_unless((int) $request->query('recipient') === (int) Auth::id(), 403);
        $quote = ProQuote::with('user', 'client')->findOrFail($id);
        $user = $quote->user;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pro.quote-pdf', compact('user', 'quote'));
        $pdf->setPaper('a4');

        return $pdf->download('Devis_'.$quote->quote_number.'.pdf');
    }

    // =========================================
    // FACTURES
    // =========================================

    public function invoices()
    {
        $user = $this->documentMember();
        $invoices = $user->proInvoices()->with('client')->latest()->paginate(15);
        $clients = $user->proClients()->orderBy('name')->get();
        $invoiceStats = [
            'sent' => $user->proInvoices()->where('status', 'sent')->count(),
            'paid' => $user->proInvoices()->where('status', 'paid')->count(),
            'overdue' => $user->proInvoices()->where('status', 'overdue')->count(),
        ];
        $allowance = $this->allowance()->for($user, \App\Services\CommercialDocumentAllowance::INVOICE);

        return view('pro.invoices', compact('user', 'invoices', 'clients', 'invoiceStats', 'allowance'));
    }

    public function createInvoice($quoteId = null)
    {
        $user = $this->documentMember();
        $this->ensureCanCreateDocument($user, \App\Services\CommercialDocumentAllowance::INVOICE);
        $clients = $user->proClients()->orderBy('name')->get();
        $quote = $quoteId ? $user->proQuotes()->find($quoteId) : null;

        if ($quote && $quote->status !== 'accepted') {
            return redirect()->route('pro.quotes.show', $quote)->with('error', 'La facture ne peut être créée qu’à partir d’un devis accepté.');
        }
        if ($quote && $quote->invoice()->exists()) {
            return redirect()->route('pro.invoices.show', $quote->invoice()->value('id'))->with('warning', 'Une facture existe déjà pour ce devis.');
        }

        return view('pro.invoice-create', compact('user', 'clients', 'quote'));
    }

    public function storeInvoice(Request $request)
    {
        $user = $this->documentMember();
        $this->ensureCanCreateDocument($user, \App\Services\CommercialDocumentAllowance::INVOICE);

        $validated = $request->validate([
            'client_id' => ['nullable', $this->proClientRule($user->id)],
            'quote_id' => [
                'nullable',
                Rule::exists('pro_quotes', 'id')->where(fn ($query) => $query->where('user_id', $user->id)->where('status', 'accepted')),
            ],
            'client_name' => 'required|string|max:255',
            'client_email' => 'nullable|email|max:255',
            'client_phone' => 'nullable|string|max:20',
            'client_address' => 'nullable|string|max:500',
            'client_company' => 'nullable|string|max:255',
            'client_registration_number' => 'nullable|string|max:64',
            'client_vat_number' => 'nullable|string|max:64',
            'client_type' => 'required|in:individual,business',
            'subject' => 'required|string|max:255',
            'operation_type' => 'required|in:services,goods,mixed',
            'service_date' => 'required|date',
            'purchase_order_number' => 'nullable|string|max:100',
            'delivery_address' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:500',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'due_date' => 'nullable|date',
            'notes' => 'nullable|string|max:2000',
            'payment_method' => 'nullable|string|max:50',
            'payment_terms' => 'required|string|max:1000',
            'vat_exemption_reason' => 'nullable|required_if:tax_rate,0|string|max:255',
            'early_payment_discount' => 'required|string|max:255',
            'late_penalty_rate' => 'nullable|required_if:client_type,business|numeric|min:0|max:100',
        ]);

        if (! empty($validated['quote_id']) && $user->proInvoices()->where('quote_id', $validated['quote_id'])
            ->where('kind', ProInvoice::KIND_INVOICE)->where('status', '!=', 'cancelled')->exists()) {
            return back()->withInput()->withErrors(['quote_id' => 'Une facture existe déjà pour ce devis.']);
        }

        // Totaux : remise et TVA par ligne (App\Support\DocumentTotals).
        $taxRate = (float) ($validated['tax_rate'] ?? $user->defaultVatRate());
        $totals = \App\Support\DocumentTotals::compute($validated['items'], $taxRate);
        $items = collect($totals['items']);
        $subtotal = $totals['subtotal'];
        $tax = $totals['tax_amount'];
        $total = $totals['total'];

        $this->requireVatExemptionReason($totals['items'], $taxRate, $validated['vat_exemption_reason'] ?? null);

        $client = $this->resolveProClient($user, $validated);

        ProInvoice::create([
            'user_id' => $user->id,
            'pro_client_id' => $client?->id,
            'quote_id' => $validated['quote_id'] ?? null,
            'invoice_number' => 'BROUILLON-FAC-'.strtoupper(Str::random(10)),
            'client_name' => $validated['client_name'],
            'client_email' => $validated['client_email'] ?? null,
            'client_phone' => $validated['client_phone'] ?? null,
            'client_address' => $validated['client_address'] ?? null,
            'client_company' => $validated['client_company'] ?? null,
            'client_registration_number' => $validated['client_registration_number'] ?? null,
            'client_vat_number' => $validated['client_vat_number'] ?? null,
            'client_type' => $validated['client_type'],
            'subject' => $validated['subject'],
            'operation_type' => $validated['operation_type'],
            'service_date' => $validated['service_date'],
            'purchase_order_number' => $validated['purchase_order_number'] ?? null,
            'delivery_address' => $validated['delivery_address'] ?? null,
            'currency' => 'EUR',
            'items' => $items->toArray(),
            'subtotal' => $subtotal,
            'tax_rate' => $taxRate,
            'tax_amount' => $tax,
            'total' => $total,
            'status' => 'draft',
            'due_date' => $validated['due_date'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'payment_method' => $validated['payment_method'] ?? null,
            'payment_terms' => $validated['payment_terms'],
            'vat_exemption_reason' => $validated['vat_exemption_reason'] ?? null,
            'early_payment_discount' => $validated['early_payment_discount'],
            'late_penalty_rate' => $validated['late_penalty_rate'] ?? null,
        ]);

        return redirect()->route('pro.invoices')->with('success', 'Brouillon de facture créé. Le numéro définitif sera attribué lors de l’émission.');
    }

    public function showInvoice($id)
    {
        $user = $this->documentMember();
        $invoice = $user->proInvoices()->with('client', 'quote')->findOrFail($id);

        return view('pro.invoice-show', compact('user', 'invoice'));
    }

    public function downloadInvoice($id)
    {
        $user = $this->documentMember();
        $invoice = $user->proInvoices()->with('client', 'quote')->findOrFail($id);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pro.invoice-pdf', compact('user', 'invoice'));
        $pdf->setPaper('a4');

        return $pdf->download(($invoice->isCreditNote() ? 'Avoir_' : 'Facture_').$invoice->invoice_number.'.pdf');
    }

    public function sendInvoiceEmail(Request $request, $id)
    {
        $user = $this->documentMember();
        $invoice = $user->proInvoices()->with('client', 'quote')->findOrFail($id);
        $sendable = $invoice->isCreditNote() ? [ProInvoice::STATUS_ISSUED] : ['draft', 'sent', 'overdue'];
        if (! in_array($invoice->status, $sendable, true)) {
            return back()->with('error', 'Ce document ne peut plus être envoyé dans son état actuel.');
        }

        $validated = $request->validate([
            'email' => 'required|email|max:255',
            'message' => 'nullable|string|max:2000',
        ]);
        $label = $invoice->documentLabel();

        $this->finalizeInvoice($invoice, $user);
        $invoice->refresh();
        // Le statut « envoyée » est posé avant de produire le PDF : la pièce jointe
        // n'est plus marquée « brouillon ».
        $wasDraft = $invoice->status === 'draft';
        if ($wasDraft) {
            $invoice->update(['status' => 'sent', 'sent_at' => now()]);
        }
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pro.invoice-pdf', compact('user', 'invoice'));
        $pdf->setPaper('a4');

        try {
            Mail::send('emails.invoice', [
                'invoice' => $invoice,
                'user' => $user,
                'customMessage' => $validated['message'] ?? null,
            ], function ($mail) use ($validated, $invoice, $user, $pdf, $label) {
                $mail->to($validated['email'])
                    ->subject($label.' '.$invoice->invoice_number.' - '.($user->company_name ?? $user->name))
                    ->attachData($pdf->output(), ($invoice->isCreditNote() ? 'Avoir_' : 'Facture_').$invoice->invoice_number.'.pdf', [
                        'mime' => 'application/pdf',
                    ]);
            });
        } catch (\Throwable $exception) {
            // L'e-mail n'est pas parti : la facture finalisée ne passe pas pour envoyée.
            if ($wasDraft) {
                $invoice->update(['status' => 'draft', 'sent_at' => null]);
            }
            throw $exception;
        }

        // Rappel unique d'une facture impayée (Premium), à l'adresse réellement utilisée.
        $reminder = ! $invoice->isCreditNote() && $request->boolean('auto_reminder') && $user->hasActiveProSubscription()
            && $invoice->due_date !== null && $invoice->reminder_sent_at === null;
        $invoice->update(['sent_to_email' => $validated['email'], 'reminder_enabled' => $reminder]);

        return back()->with('success', $label.' envoyé'.($invoice->isCreditNote() ? '' : 'e').' par email à '.$validated['email']
            .($reminder ? '. Un rappel partira '.\App\Services\InvoiceFollowUpService::DELAY_DAYS.' jours après l’échéance si elle reste impayée.' : '.'));
    }

    public function destroyInvoice($id)
    {
        $user = $this->documentMember();
        $invoice = $user->proInvoices()->findOrFail($id);
        if (! $invoice->isEditable()) {
            return back()->with('error', 'Seul un brouillon peut être supprimé. Les factures émises sont conservées pour garantir la traçabilité.');
        }
        $invoice->delete();

        return redirect()->route('pro.invoices')->with('success', 'Facture supprimée avec succès.');
    }

    public function updateInvoiceStatus(Request $request, $id)
    {
        $user = $this->documentMember();
        $invoice = $user->proInvoices()->findOrFail($id);

        $request->validate([
            'status' => 'required|in:sent,paid,overdue,cancelled',
            'payment_method' => 'nullable|required_if:status,paid|string|max:50',
        ]);
        $target = $request->string('status')->toString();
        $allowedTransitions = [
            'draft' => ['sent', 'paid'],
            'sent' => ['paid', 'overdue'],
            'overdue' => ['paid'],
        ];
        if ($target === 'cancelled' && $invoice->canBeCredited()) {
            return redirect()->route('pro.invoices.show', $invoice->id)
                ->with('error', 'Une facture émise ne s’annule pas : elle se corrige par un avoir, depuis cette page.');
        }
        if (! in_array($target, $allowedTransitions[$invoice->status] ?? [], true)) {
            return back()->with('error', 'Cette transition est impossible : une facture finalisée conserve son historique.');
        }

        if ($invoice->status === 'draft') {
            $this->finalizeInvoice($invoice, $user);
        }

        $updateData = ['status' => $target];
        if ($target === 'sent') {
            $updateData['sent_at'] = now();
        }
        if ($target === 'paid') {
            $updateData['paid_at'] = now();
            $updateData['payment_method'] = $request->payment_method ?? 'other';

            // Update client revenue
            if ($invoice->pro_client_id) {
                $client = $user->proClients()->find($invoice->pro_client_id);
                if ($client) {
                    // Montant net : avoirs et acomptes déjà facturés déduits.
                    $client->increment('total_revenue', $invoice->fresh()->netTotal());
                    $client->increment('total_projects');
                    $client->update(['last_interaction_at' => now()]);
                }
            }
        }

        $invoice->update($updateData);

        return redirect()->route('pro.invoices')->with('success', 'Statut de la facture mis à jour.');
    }

    public function editInvoice($id)
    {
        $user = $this->documentMember();
        $invoice = $user->proInvoices()->with('quote')->findOrFail($id);
        if (! $invoice->isEditable()) {
            return redirect()->route('pro.invoices.show', $invoice)->with('error', 'Une facture émise ne peut plus être modifiée.');
        }
        $clients = $user->proClients()->orderBy('name')->get();

        return view('pro.invoice-edit', compact('user', 'invoice', 'clients'));
    }

    public function updateInvoice(Request $request, $id)
    {
        $user = $this->documentMember();
        $invoice = $user->proInvoices()->findOrFail($id);
        if (! $invoice->isEditable()) {
            return redirect()->route('pro.invoices.show', $invoice)->with('error', 'Une facture émise ne peut plus être modifiée.');
        }

        $validated = $request->validate([
            'client_id' => ['nullable', $this->proClientRule($user->id)],
            'client_name' => 'required|string|max:255',
            'client_email' => 'nullable|email|max:255',
            'client_phone' => 'nullable|string|max:20',
            'client_address' => 'nullable|string|max:500',
            'client_company' => 'sometimes|nullable|string|max:255',
            'client_registration_number' => 'sometimes|nullable|string|max:64',
            'client_vat_number' => 'sometimes|nullable|string|max:64',
            'client_type' => 'sometimes|required|in:individual,business',
            'subject' => 'required|string|max:255',
            'operation_type' => 'sometimes|required|in:services,goods,mixed',
            'service_date' => 'sometimes|required|date',
            'purchase_order_number' => 'sometimes|nullable|string|max:100',
            'delivery_address' => 'sometimes|nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:500',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'due_date' => 'nullable|date',
            'notes' => 'nullable|string|max:2000',
            'payment_method' => 'nullable|string|max:50',
            'payment_terms' => 'sometimes|required|string|max:1000',
            // Mêmes exigences qu'à la création pour les mentions de facture.
            'vat_exemption_reason' => 'sometimes|nullable|required_if:tax_rate,0|string|max:255',
            'early_payment_discount' => 'sometimes|required|string|max:255',
            'late_penalty_rate' => 'sometimes|nullable|required_if:client_type,business|numeric|min:0|max:100',
        ]);

        // Totaux : remise et TVA par ligne (App\Support\DocumentTotals).
        $taxRate = (float) ($validated['tax_rate'] ?? $user->defaultVatRate());
        $totals = \App\Support\DocumentTotals::compute($validated['items'], $taxRate);
        $items = collect($totals['items']);
        $subtotal = $totals['subtotal'];
        $tax = $totals['tax_amount'];
        $total = $totals['total'];

        // Champs repris de la création : enregistrés seulement s'ils ont été envoyés.
        $extra = collect($validated)->only([
            'client_company', 'client_registration_number', 'client_vat_number', 'client_type',
            'operation_type', 'service_date', 'purchase_order_number', 'delivery_address',
            'payment_terms', 'vat_exemption_reason', 'early_payment_discount', 'late_penalty_rate',
        ])->all();

        $this->requireVatExemptionReason($totals['items'], $taxRate,
            array_key_exists('vat_exemption_reason', $extra) ? $extra['vat_exemption_reason'] : $invoice->vat_exemption_reason);

        $invoice->update($extra + [
            'pro_client_id' => $this->resolveProClient($user, $validated)?->id,
            'client_name' => $validated['client_name'],
            'client_email' => $validated['client_email'] ?? null,
            'client_phone' => $validated['client_phone'] ?? null,
            'client_address' => $validated['client_address'] ?? null,
            'subject' => $validated['subject'],
            'items' => $items->toArray(),
            'subtotal' => $subtotal,
            'tax_rate' => $taxRate,
            'tax_amount' => $tax,
            'total' => $total,
            'due_date' => $validated['due_date'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'payment_method' => $validated['payment_method'] ?? null,
        ]);

        return redirect()->route('pro.invoices.show', $invoice->id)->with('success', 'Facture mise à jour avec succès.');
    }

    // =========================================
    // DOCUMENTS
    // =========================================

    public function documents()
    {
        $user = $this->ensurePro();
        $documents = $user->proDocuments()->latest()->paginate(15);

        return view('pro.documents', compact('user', 'documents'));
    }

    public function storeDocument(Request $request)
    {
        $user = $this->ensurePro();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:insurance,kbis,identity,diploma,certification,other',
            'file' => 'required|file|max:10240|mimes:pdf,jpg,jpeg,png,doc,docx',
            'expiry_date' => 'nullable|date',
            'notes' => 'nullable|string|max:500',
        ]);

        $file = $request->file('file');
        $path = $file->store('pro-documents/'.$user->id, config('filesystems.default', 'public'));

        ProDocument::create([
            'user_id' => $user->id,
            'name' => $validated['title'],
            'title' => $validated['title'],
            'type' => $validated['type'],
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'expiry_date' => $validated['expiry_date'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'status' => 'pending',
        ]);

        return redirect()->route('pro.documents')->with('success', 'Document envoyé. Il sera vérifié par notre équipe.');
    }

    public function deleteDocument($id)
    {
        $user = $this->ensurePro();
        $document = $user->proDocuments()->findOrFail($id);

        Storage::disk(config('filesystems.default', 'public'))->delete($document->file_path);
        $document->delete();

        return redirect()->route('pro.documents')->with('success', 'Document supprimé.');
    }

    // =========================================
    // CHANGEMENT DE STATUT
    // =========================================

    public function accountStatus()
    {
        $user = $this->ensurePro();

        return view('pro.account-status', compact('user'));
    }

    public function compliance()
    {
        $user = $this->ensurePro();
        $requirements = $user->proCommercialMissingRequirements();

        return view('pro.compliance', compact('user', 'requirements'));
    }

    public function acceptProTerms(Request $request)
    {
        $user = $this->ensurePro();
        $request->validate([
            'accept_pro_terms' => 'accepted',
            'certify_information' => 'accepted',
        ]);

        $user->update([
            'pro_terms_accepted_at' => now(),
            'pro_terms_version' => (string) config('legal.pro_terms_version'),
            'pro_terms_ip' => $request->ip(),
        ]);

        return redirect()->route('pro.compliance')->with('success', 'Conditions PRO acceptées. La checklist a été actualisée.');
    }

    public function updateAccountStatus(Request $request)
    {
        $user = $this->ensurePro();

        $validated = $request->validate([
            'pro_status' => 'required|in:particulier,auto-entrepreneur,entreprise',
            'company_name' => 'nullable|string|max:255',
            'siret' => [
                'nullable', 'string', 'max:64',
                function (string $attribute, mixed $value, \Closure $fail) use ($user) {
                    if (blank($value)) {
                        return;
                    }
                    $number = preg_replace('/[^A-Z0-9]/i', '', (string) $value);
                    $country = Str::lower(Str::ascii((string) $user->country));
                    if (in_array($country, ['france', 'mayotte'], true) && ! preg_match('/^\d{14}$/', $number)) {
                        $fail('Le SIRET doit contenir exactement 14 chiffres.');
                    }
                },
            ],
            'tva_number' => 'nullable|string|max:20',
            'insurance_number' => 'nullable|string|max:100',
        ]);

        $validated['siret'] = filled($validated['siret'] ?? null)
            ? strtoupper(preg_replace('/[^A-Z0-9]/i', '', $validated['siret']))
            : null;
        $validated['tva_number'] = filled($validated['tva_number'] ?? null)
            ? strtoupper(preg_replace('/\s+/', '', $validated['tva_number']))
            : null;

        $updateData = [
            'pro_status' => $validated['pro_status'],
            'company_name' => $validated['company_name'] ?? $user->company_name,
            'siret' => $validated['siret'] ?? $user->siret,
            'tva_number' => $validated['tva_number'] ?? $user->tva_number,
            'insurance_number' => $validated['insurance_number'] ?? $user->insurance_number,
        ];

        if ($validated['pro_status'] === 'entreprise') {
            $updateData['user_type'] = 'professionnel';
            $updateData['account_type'] = 'professionnel';
            $updateData['business_type'] = 'entreprise';
            $updateData['is_service_provider'] = true;
            $updateData['max_active_ads'] = 20;
        } elseif ($validated['pro_status'] === 'auto-entrepreneur') {
            $updateData['user_type'] = 'professionnel';
            $updateData['account_type'] = 'professionnel';
            $updateData['business_type'] = 'auto_entrepreneur';
            $updateData['is_service_provider'] = true;
            $updateData['max_active_ads'] = 10;
        } else {
            $updateData['user_type'] = 'particulier';
            $updateData['account_type'] = 'particulier';
            $updateData['business_type'] = null;
            $updateData['max_active_ads'] = 5;
        }

        $wasProfessional = $user->isProfessionnel();
        $user->update($updateData);

        // Passage en professionnel : on enchaîne sur l'identité qui figurera sur ses
        // devis et factures. C'est une recommandation : l'émission dépend seulement
        // des documents offerts et de Premium (App\Services\CommercialDocumentAllowance).
        if (! $wasProfessional && $user->isProfessionnel() && ! $user->canIssueCommercialDocuments()) {
            $remaining = count($user->proCommercialMissingRequirements());

            return redirect()->route('pro.compliance')->with('success', sprintf(
                'Votre statut est maintenant « %s ». Il reste %d %s à compléter pour l’identité professionnelle de vos devis et factures.',
                $user->getAccountTypeLabel(),
                $remaining,
                $remaining > 1 ? 'informations' : 'information'
            ));
        }

        return redirect()->route('pro.account-status')->with('success', 'Statut du compte mis à jour avec succès.');
    }

    // =========================================
    // ABONNEMENT PRO
    // =========================================

    public function subscription()
    {
        $user = Auth::user();
        $subscription = $user->subscriptionForManagement();
        $subscriptions = $user->proSubscriptions()->latest()->get();
        $proSubscriptionsEnabled = PlatformFeatures::proSubscriptionsEnabled();
        // Accès Premium à paiement unique (sans abonnement) que ce membre peut réellement payer.
        $oneTime = $this->providerSubscriptionService->oneTimeSalesFor($user);
        $oneTimeOffers = $oneTime['offers'];
        $oneTimeExtendsFrom = $oneTime['extends_from'];

        return view('pro.subscription', compact('user', 'subscription', 'subscriptions', 'proSubscriptionsEnabled', 'oneTimeOffers', 'oneTimeExtendsFrom'));
    }

    public function cancelSubscriptionRenewal()
    {
        $user = Auth::user();
        $subscription = $user->proSubscriptions()->billingOpen()->latest('id')->first();

        if (! $subscription) {
            return back()->with('error', 'Aucun abonnement actif à annuler.');
        }

        try {
            // Paiement en retard : rien n'a été payé pour la période en cours, la résiliation
            // prend donc effet tout de suite et la facture en attente est annulée.
            if ($subscription->payment_failed_at) {
                $ended = $this->providerSubscriptionService->endForNonPayment($subscription, notify: false);
                if ($ended && in_array($ended->status, ['cancelled', 'expired'], true)) {
                    return back()->with('success', 'Votre abonnement est résilié et votre compte est repassé en gratuit. Le paiement en attente est annulé : plus rien ne vous sera prélevé.');
                }
                // Paiement régularisé entre-temps, ou prélèvement en cours : résiliation ordinaire.
                $subscription = $subscription->fresh();
            }
            if (! $subscription->isActive()) {
                $this->providerSubscriptionService->cancelImmediately($subscription);

                return back()->with('success', 'Votre abonnement est résilié. Aucun nouveau renouvellement ne sera effectué. Les factures déjà émises restent consultables dans le portail Stripe.');
            }
            $subscription = $this->providerSubscriptionService->cancelAtPeriodEnd($subscription);

            return back()->with(
                'success',
                'Le renouvellement est arrêté. Votre accès reste actif jusqu’au '.optional($subscription->ends_at)->format('d/m/Y').'.'
            );
        } catch (\Throwable $exception) {
            report($exception);

            return back()->with('error', 'La résiliation n’a pas pu être confirmée. Réessayez depuis le portail de facturation ou contactez l’assistance.');
        }
    }

    public function resumeSubscriptionRenewal()
    {
        $user = Auth::user();
        $subscription = $user->proSubscriptions()->currentlyActive()->latest()->first();

        if (! $subscription) {
            return back()->with('error', 'Cet abonnement est déjà terminé.');
        }

        try {
            $this->providerSubscriptionService->resumeRenewal($subscription);

            return back()->with('success', 'Le renouvellement automatique est réactivé.');
        } catch (\Throwable $exception) {
            report($exception);

            return back()->with('error', 'La reprise du renouvellement n’a pas pu être confirmée. Réessayez ou contactez l’assistance.');
        }
    }

    public function openSubscriptionBillingPortal()
    {
        $user = Auth::user();

        try {
            $url = $this->providerSubscriptionService->createBillingPortalSession(
                $user,
                route('billing.index')
            );

            return redirect()->away($url);
        } catch (\Throwable $exception) {
            report($exception);

            return back()->with('error', 'Le portail de facturation est temporairement indisponible. Réessayez ou contactez l’assistance.');
        }
    }

    public function subscribeOnboarding(Request $request)
    {
        $user = Auth::user();

        // Handle skip (close modal without completing)
        if ($request->input('skip')) {
            $step = $request->input('current_step', 0);
            $user->update([
                'pro_onboarding_skipped' => true,
                'pro_onboarding_step' => $step,
            ]);

            return response()->json(['success' => true, 'redirect' => route('feed')]);
        }

        // Handle step-save (save progress per step without finishing)
        if ($request->input('save_step')) {
            $step = (int) $request->input('save_step');
            $updateData = ['pro_onboarding_step' => $step];

            // Save per-step data
            if ($step >= 2) {
                if ($request->filled('address')) {
                    $updateData['address'] = $request->input('address');
                }
                if ($request->filled('postal_code')) {
                    $updateData['postal_code'] = $request->input('postal_code');
                }
                if ($request->filled('city')) {
                    $updateData['city'] = $request->input('city');
                    $updateData['detected_city'] = $request->input('city');
                }
                if ($request->filled('country')) {
                    $updateData['country'] = $request->input('country');
                    $updateData['detected_country'] = $request->input('country');
                }
                if ($request->filled('phone')) {
                    $updateData['phone'] = $request->input('phone');
                }
                if ($request->filled('intervention_radius')) {
                    $updateData['pro_intervention_radius'] = (int) $request->input('intervention_radius');
                }
            }
            if ($step >= 3 && $request->has('categories')) {
                $enabledServices = MarketplaceCategoryRegistry::enabledServices();
                $enabledCategoryNames = array_keys($enabledServices);
                $enabledSubcategories = collect($enabledServices)
                    ->flatMap(fn (array $definition): array => $definition['subcategories'] ?? [])
                    ->unique()
                    ->all();

                $updateData['pro_service_categories'] = array_values(array_intersect(
                    (array) $request->input('categories'),
                    $enabledCategoryNames
                ));
                if ($request->has('subcategories')) {
                    $updateData['service_subcategories'] = array_values(array_intersect(
                        (array) $request->input('subcategories'),
                        $enabledSubcategories
                    ));
                }
            }
            if ($step >= 4) {
                if ($request->has('notifications_realtime')) {
                    $updateData['pro_notifications_realtime'] = (bool) $request->input('notifications_realtime');
                }
                if ($request->has('notifications_email')) {
                    $updateData['pro_notifications_email'] = (bool) $request->input('notifications_email');
                }
                if ($request->has('notifications_sms')) {
                    $updateData['pro_notifications_sms'] = (bool) $request->input('notifications_sms');
                }
                if ($request->filled('phone_sms')) {
                    $updateData['pro_phone_sms'] = $request->input('phone_sms');
                }
            }

            $user->update($updateData);
            $this->adoptOnboardingSkills($user, $updateData);

            return response()->json(['success' => true, 'step' => $step]);
        }

        // Handle complete without subscription (skip_subscription)
        if ($request->input('skip_subscription')) {
            $enabledServices = MarketplaceCategoryRegistry::enabledServices();
            $enabledCategoryNames = array_keys($enabledServices);
            $enabledSubcategories = collect($enabledServices)
                ->flatMap(fn (array $definition): array => $definition['subcategories'] ?? [])
                ->unique()
                ->values()
                ->all();

            $validated = $request->validate([
                'categories' => 'nullable|array',
                'categories.*' => ['string', Rule::in($enabledCategoryNames)],
                'subcategories' => 'nullable|array',
                'subcategories.*' => ['string', Rule::in($enabledSubcategories)],
                'intervention_radius' => 'nullable|integer|min:5|max:200',
                'notifications_realtime' => 'nullable|boolean',
                'notifications_email' => 'nullable|boolean',
                'notifications_sms' => 'nullable|boolean',
                'phone_sms' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:500',
                'postal_code' => 'nullable|string|max:20',
                'city' => 'nullable|string|max:100',
                'country' => 'nullable|string|max:100',
                'phone' => 'nullable|string|max:20',
            ]);

            $updateData = [
                'pro_onboarding_completed' => true,
                'pro_onboarding_step' => 7,
                'pro_onboarding_skipped' => false,
                'profile_completed' => true,
                'profile_completed_at' => now(),
            ];

            if (! empty($validated['categories'])) {
                $updateData['pro_service_categories'] = $validated['categories'];
                // Synchroniser service_category avec la première catégorie sélectionnée
                $updateData['service_category'] = $validated['categories'][0] ?? null;
            }
            if (! empty($validated['subcategories'])) {
                $updateData['service_subcategories'] = $validated['subcategories'];
                // Synchroniser profession avec la première sous-catégorie
                if (empty($user->profession)) {
                    $updateData['profession'] = $validated['subcategories'][0] ?? null;
                }
            }
            if (! empty($validated['intervention_radius'])) {
                $updateData['pro_intervention_radius'] = $validated['intervention_radius'];
            }
            if (isset($validated['notifications_realtime'])) {
                $updateData['pro_notifications_realtime'] = $validated['notifications_realtime'];
            }
            if (isset($validated['notifications_email'])) {
                $updateData['pro_notifications_email'] = $validated['notifications_email'];
            }
            if (isset($validated['notifications_sms'])) {
                $updateData['pro_notifications_sms'] = $validated['notifications_sms'];
            }
            if (! empty($validated['phone_sms'])) {
                $updateData['pro_phone_sms'] = $validated['phone_sms'];
            }
            if (! empty($validated['address'])) {
                $updateData['address'] = $validated['address'];
            }
            if (! empty($validated['postal_code'])) {
                $updateData['postal_code'] = $validated['postal_code'];
            }
            if (! empty($validated['phone'])) {
                $updateData['phone'] = $validated['phone'];
            }
            if (! empty($validated['city'])) {
                $updateData['city'] = $validated['city'];
                $updateData['detected_city'] = $validated['city'];
            }
            if (! empty($validated['country'])) {
                $updateData['country'] = $validated['country'];
                $updateData['detected_country'] = $validated['country'];
            }

            $user->update($updateData);
            $this->adoptOnboardingSkills($user, $updateData);

            return response()->json([
                'success' => true,
                'message' => 'Configuration terminée ! Vous pouvez souscrire à un abonnement plus tard.',
                'redirect' => route('feed'),
                'skipped_subscription' => true,
            ]);
        }

        if (! PlatformFeatures::proSubscriptionsEnabled()) {
            $message = PlatformFeatures::proSubscriptionUnavailableMessage();

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'subscriptions_unavailable' => true,
                ], 422);
            }

            return back()->with('info', $message);
        }

        // Full subscribe with payment via Stripe Checkout
        $validated = $request->validate([
            'plan' => 'required|in:monthly',
            'categories' => 'nullable|array',
            'subcategories' => 'nullable|array',
            'intervention_radius' => 'nullable|integer|min:5|max:200',
            'notifications_realtime' => 'boolean',
            'notifications_email' => 'boolean',
            'notifications_sms' => 'boolean',
            'phone_sms' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'postal_code' => 'nullable|string|max:20',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:20',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $planConfig = ProviderSubscriptionPlans::get($validated['plan']);
        if (! $planConfig || empty($planConfig['enabled'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cet abonnement n’est pas disponible pour le moment.',
            ], 422);
        }
        // Save onboarding data in session so we can finalize after payment
        session([
            'pro_onboarding_data' => [
                'plan' => $validated['plan'],
                'categories' => $validated['categories'] ?? $user->pro_service_categories ?? [],
                'subcategories' => $validated['subcategories'] ?? [],
                'intervention_radius' => $validated['intervention_radius'] ?? $user->pro_intervention_radius ?? 30,
                'notifications_realtime' => $validated['notifications_realtime'] ?? true,
                'notifications_email' => $validated['notifications_email'] ?? true,
                'notifications_sms' => $validated['notifications_sms'] ?? false,
                'phone_sms' => $validated['phone_sms'] ?? null,
                'address' => $validated['address'] ?? null,
                'postal_code' => $validated['postal_code'] ?? null,
                'city' => $validated['city'] ?? null,
                'country' => $validated['country'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
            ],
        ]);

        try {
            $session = $this->providerSubscriptionService->createCheckoutSession(
                $user,
                $validated['plan'],
                route('pro.onboarding.payment.success').'?session_id={CHECKOUT_SESSION_ID}',
                route('pro.onboarding.payment.cancel'),
                'pro_subscription'
            );

            // For standard form submissions (e.g. subscription page), redirect directly to Stripe
            if (! $request->expectsJson() && ! $request->ajax()) {
                return redirect()->away($session->url);
            }

            return response()->json([
                'success' => true,
                'requires_payment' => true,
                'checkout_url' => $session->url,
            ]);

        } catch (\Throwable $e) {
            \Log::error('Stripe Checkout creation error (pro onboarding): '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user_id' => $user->id,
            ]);

            $status = $e instanceof \DomainException ? 422 : 500;
            $message = $status === 422 ? $e->getMessage() : 'Le paiement ne peut pas être préparé actuellement. Veuillez réessayer.';
            if (! $request->expectsJson() && ! $request->ajax()) {
                return redirect()->route('pro.subscription')->with('error', $message);
            }

            return response()->json([
                'success' => false,
                'message' => $message,
            ], $status);
        }
    }

    /**
     * Handle successful Stripe payment for pro onboarding
     */
    public function onboardingPaymentSuccess(Request $request)
    {
        $sessionId = $request->get('session_id');
        $user = Auth::user();

        if (! $sessionId || ! $user) {
            return redirect()->route('pro.onboarding')->with('error', 'Session de paiement invalide.');
        }

        try {
            // Retrieve onboarding data from session
            $data = session('pro_onboarding_data', []);
            $categories = $data['categories'] ?? $user->pro_service_categories ?? [];
            $interventionRadius = $data['intervention_radius'] ?? $user->pro_intervention_radius ?? 30;

            $subscription = $this->providerSubscriptionService->completeCheckout(
                $sessionId,
                $user,
                [
                    'realtime_notifications' => $data['notifications_realtime'] ?? true,
                    'selected_categories' => $categories,
                    'intervention_radius' => $interventionRadius,
                ]
            );
            $plan = $subscription->plan;

            DB::beginTransaction();
            try {
                // Update user
                $updateData = [
                    'pro_onboarding_completed' => true,
                    'pro_onboarding_step' => 7,
                    'pro_onboarding_skipped' => false,
                    'profile_completed' => true,
                    'profile_completed_at' => now(),
                    'pro_subscription_plan' => $plan,
                    'pro_notifications_realtime' => $data['notifications_realtime'] ?? true,
                    'pro_notifications_email' => $data['notifications_email'] ?? true,
                    'pro_notifications_sms' => $data['notifications_sms'] ?? false,
                    'pro_service_categories' => $categories,
                    'service_category' => $categories[0] ?? null,
                    'pro_intervention_radius' => $interventionRadius,
                    'pro_status' => 'active',
                ];

                if (! empty($data['phone_sms'])) {
                    $updateData['pro_phone_sms'] = $data['phone_sms'];
                }
                if (! empty($data['address'])) {
                    $updateData['address'] = $data['address'];
                }
                if (! empty($data['postal_code'])) {
                    $updateData['postal_code'] = $data['postal_code'];
                }
                if (! empty($data['phone'])) {
                    $updateData['phone'] = $data['phone'];
                }
                if (! empty($data['city'])) {
                    $updateData['city'] = $data['city'];
                    $updateData['detected_city'] = $data['city'];
                }
                if (! empty($data['country'])) {
                    $updateData['country'] = $data['country'];
                    $updateData['detected_country'] = $data['country'];
                }
                if (! empty($data['subcategories'])) {
                    $updateData['service_subcategories'] = $data['subcategories'];
                }

                $user->update($updateData);
                $this->adoptOnboardingSkills($user, $updateData);

                // Update geolocation if provided
                if (! empty($data['latitude']) && ! empty($data['longitude'])) {
                    $user->update([
                        'latitude' => $data['latitude'],
                        'longitude' => $data['longitude'],
                        'geo_source' => 'onboarding',
                        'geo_detected_at' => now(),
                    ]);
                }

                DB::commit();

                // Clear onboarding session data
                session()->forget('pro_onboarding_data');

                return redirect()->route('pro.dashboard')->with('success', 'Félicitations ! Votre abonnement Prokejem est activé. Bienvenue dans votre espace professionnel !');

            } catch (\Exception $e) {
                DB::rollBack();
                \Log::error('Pro subscription creation after payment error: '.$e->getMessage(), [
                    'trace' => $e->getTraceAsString(),
                    'user_id' => $user->id,
                    'session_id' => $sessionId,
                ]);

                return redirect()->route('pro.onboarding')->with('error', 'Le paiement a été effectué mais une erreur est survenue. Veuillez contacter le support.');
            }

        } catch (\Throwable $e) {
            \Log::error('Stripe session retrieval error (pro onboarding): '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user_id' => $user->id,
            ]);

            return redirect()->route('pro.onboarding')->with('error', 'Erreur lors de la vérification du paiement. Veuillez réessayer.');
        }
    }

    /**
     * Handle cancelled Stripe payment for pro onboarding
     */
    public function onboardingPaymentCancel()
    {
        $user = Auth::user();

        // L'onboarding renvoie un membre qui n'est pas prestataire vers l'accueil, et un
        // prestataire déjà configuré vers son tableau de bord : le message serait perdu.
        // Ils reviennent donc à la page de l'abonnement, qui affiche le message.
        if ((! $user->isProfessionnel() && ! $user->isServiceProvider()) || $user->hasCompletedProOnboarding()) {
            return redirect()->route('pro.subscription')->with('info', 'Le paiement a été annulé. Vous pouvez réessayer quand vous le souhaitez.');
        }

        return redirect()->route('pro.onboarding')->with('info', 'Le paiement a été annulé. Vous pouvez réessayer ou choisir de configurer sans abonnement.');
    }

    // =========================================
    // ONBOARDING PAGE
    // =========================================

    public function onboarding()
    {
        $user = Auth::user();

        // If not a professional, redirect
        if (! $user->isProfessionnel() && ! $user->isServiceProvider()) {
            return redirect()->route('feed');
        }

        // If onboarding already completed, go to dashboard
        if ($user->hasCompletedProOnboarding()) {
            return redirect()->route('pro.dashboard');
        }

        $categories = $this->getServiceCategories();

        $proSubscriptionsEnabled = PlatformFeatures::proSubscriptionsEnabled();

        return view('pro.onboarding', compact('user', 'categories', 'proSubscriptionsEnabled'));
    }

    // =========================================
    // ONBOARDING DATA
    // =========================================

    public function getOnboardingData()
    {
        $user = Auth::user();

        return response()->json([
            'success' => true,
            'user' => [
                'name' => $user->name,
                'company_name' => $user->company_name,
                'city' => $user->city ?? $user->detected_city,
                'country' => $user->country ?? $user->detected_country,
                'latitude' => $user->latitude,
                'longitude' => $user->longitude,
                'phone' => $user->phone,
                'address' => $user->address,
                'profession' => $user->profession,
                'has_location' => $user->hasGeoLocation(),
            ],
            'categories' => $this->getServiceCategories(),
            'pro_subscriptions_enabled' => PlatformFeatures::proSubscriptionsEnabled(),
        ]);
    }

    // =========================================
    // ANALYTICS (PAGE)
    // =========================================

    public function analytics()
    {
        $user = $this->ensurePro();

        // Revenue data last 12 months
        $revenueData = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $revenue = $user->proInvoices()->where('status', 'paid')
                ->whereMonth('paid_at', $date->month)
                ->whereYear('paid_at', $date->year)
                ->sum(DB::raw(ProInvoice::NET_TOTAL_SQL));
            $revenueData[] = [
                'month' => $date->translatedFormat('M'),
                'year' => $date->year,
                'label' => $date->translatedFormat('M Y'),
                'revenue' => round($revenue, 2),
            ];
        }

        // New clients per month (6 months)
        $clientsData = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $clientsData[] = [
                'month' => $date->translatedFormat('M'),
                'count' => $user->proClients()
                    ->whereMonth('created_at', $date->month)
                    ->whereYear('created_at', $date->year)
                    ->count(),
            ];
        }

        // Quote conversion rate
        // Un brouillon n'a jamais été proposé à un client : il n'entre pas dans le taux.
        $totalQuotes = $user->proQuotes()->where('status', '!=', 'draft')->count();
        $acceptedQuotes = $user->proQuotes()->where('status', 'accepted')->count();
        $conversionRate = $totalQuotes > 0 ? round(($acceptedQuotes / $totalQuotes) * 100, 1) : 0;

        // Invoice payment rate
        // Seules les factures émises et non annulées comptent.
        $totalInvoices = $user->proInvoices()->whereIn('status', ['sent', 'overdue', 'paid'])->count();
        $paidInvoices = $user->proInvoices()->where('status', 'paid')->count();
        $paymentRate = $totalInvoices > 0 ? round(($paidInvoices / $totalInvoices) * 100, 1) : 0;

        // Clients classés par montant de devis facturés et payés. L'alias ne doit
        // pas porter le nom d'une colonne de pro_clients : avec « total_revenue »,
        // PostgreSQL refusait le tri (colonne ambiguë) et la page répondait 500.
        $topClients = $user->proClients()
            ->withSum(['quotes as paid_quotes_total' => function ($q) {
                $q->whereHas('invoice', function ($iq) {
                    $iq->where('status', 'paid');
                });
            }], 'total')
            ->orderByDesc('paid_quotes_total')
            ->take(5)
            ->get();

        // Summary stats
        $stats = [
            'total_revenue' => $user->proInvoices()->where('status', 'paid')->sum(DB::raw(ProInvoice::NET_TOTAL_SQL)),
            'monthly_revenue' => $user->proInvoices()->where('status', 'paid')
                ->whereMonth('paid_at', now()->month)
                ->whereYear('paid_at', now()->year)
                ->sum(DB::raw(ProInvoice::NET_TOTAL_SQL)),
            'avg_quote_value' => round($user->proQuotes()->where('status', '!=', 'draft')->avg('total') ?? 0, 2),
            'avg_invoice_value' => round($user->proInvoices()->where('status', 'paid')->avg(DB::raw(ProInvoice::NET_TOTAL_SQL)) ?? 0, 2),
            'total_clients' => $user->proClients()->count(),
            'active_clients' => $user->proClients()->where('status', 'active')->count(),
            'total_quotes' => $totalQuotes,
            'accepted_quotes' => $acceptedQuotes,
            'conversion_rate' => $conversionRate,
            'total_invoices' => $totalInvoices,
            'paid_invoices' => $paidInvoices,
            'payment_rate' => $paymentRate,
            'pending_amount' => $user->proInvoices()->whereIn('status', ['sent', 'overdue'])->sum(DB::raw(ProInvoice::NET_TOTAL_SQL)),
            'reviews_count' => $user->reviewsReceived()->count(),
            'average_rating' => round($user->reviewsReceived()->avg('rating') ?? 0, 1),
            'active_ads' => $user->ads()->where('status', 'active')->count(),
        ];

        return view('pro.analytics', compact(
            'user', 'stats', 'revenueData', 'clientsData', 'topClients'
        ));
    }

    // =========================================
    // AGENDA / PLANNING
    // =========================================

    public function agenda()
    {
        $user = $this->ensurePro();

        // Get upcoming events from quotes (quotes with scheduled dates)
        $upcomingQuotes = $user->proQuotes()
            ->where('status', 'accepted')
            ->with('client')
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get()
            ->map(function ($quote) {
                return [
                    'id' => 'quote_'.$quote->id,
                    'title' => $quote->title ?? ('Devis #'.$quote->quote_number),
                    'client' => $quote->client->name ?? 'Client inconnu',
                    'type' => 'quote',
                    'date' => $quote->valid_until ?? $quote->created_at,
                    'amount' => $quote->total,
                    'status' => $quote->status,
                    'color' => '#6366f1',
                ];
            });

        // Get unpaid invoices as reminders
        $pendingInvoices = $user->proInvoices()
            ->whereIn('status', ['sent', 'overdue'])
            ->with('client')
            ->orderBy('due_date', 'asc')
            ->take(20)
            ->get()
            ->map(function ($invoice) {
                return [
                    'id' => 'invoice_'.$invoice->id,
                    'title' => 'Facture #'.$invoice->invoice_number,
                    'client' => $invoice->client->name ?? 'Client inconnu',
                    'type' => 'invoice',
                    'date' => $invoice->due_date ?? $invoice->created_at,
                    'amount' => $invoice->total,
                    'status' => $invoice->status,
                    'color' => '#f59e0b',
                ];
            });

        // Recent client interactions
        $recentInteractions = $user->proClients()
            ->whereNotNull('last_interaction_at')
            ->orderBy('last_interaction_at', 'desc')
            ->take(10)
            ->get()
            ->map(function ($client) {
                return [
                    'id' => 'client_'.$client->id,
                    'title' => 'Interaction: '.$client->name,
                    'client' => $client->name,
                    'type' => 'interaction',
                    'date' => $client->last_interaction_at,
                    'status' => $client->status,
                    'color' => '#22c55e',
                ];
            });

        $events = $upcomingQuotes->concat($pendingInvoices)->concat($recentInteractions)
            ->sortByDesc('date')
            ->values();

        // Stats for agenda header
        $agendaStats = [
            'upcoming_deadlines' => $user->proInvoices()->where('status', 'sent')
                ->where('due_date', '>=', now()->toDateString())
                ->where('due_date', '<=', now()->addDays(7)->toDateString())
                ->count(),
            'overdue_invoices' => $user->proInvoices()->whereIn('status', ['sent', 'overdue'])
                ->where('due_date', '<', now()->toDateString())
                ->count(),
            // Devis envoyés, encore sans réponse.
            'active_quotes' => $user->proQuotes()->where('status', 'sent')->count(),
            'today_tasks' => $events->filter(function ($e) {
                return \Carbon\Carbon::parse($e['date'])->isToday();
            })->count(),
        ];

        return view('pro.agenda', compact('user', 'events', 'agendaStats'));
    }

    // =========================================
    // ANALYTICS (API endpoints)
    // =========================================

    public function getStats()
    {
        $user = $this->ensurePro();

        $monthlyData = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthlyData[] = [
                'month' => $date->translatedFormat('M Y'),
                'revenue' => $user->proInvoices()->where('status', 'paid')
                    ->whereMonth('paid_at', $date->month)
                    ->whereYear('paid_at', $date->year)
                    ->sum(DB::raw(ProInvoice::NET_TOTAL_SQL)),
                'clients' => $user->proClients()
                    ->whereMonth('created_at', $date->month)
                    ->whereYear('created_at', $date->year)
                    ->count(),
            ];
        }

        return response()->json([
            'success' => true,
            'monthly' => $monthlyData,
        ]);
    }

    // =========================================
    // CATÉGORIES DE SERVICES
    // =========================================

    /**
     * Public accessor for service categories (used by FeedController)
     */
    public function getServiceCategoriesPublic(): array
    {
        return $this->getServiceCategories();
    }

    /**
     * Liste des catégories avec sous-catégories (source unique : config/categories.php)
     */
    private function getServiceCategories(): array
    {
        return MarketplaceCategoryRegistry::enabledServiceOptions();
    }
}
