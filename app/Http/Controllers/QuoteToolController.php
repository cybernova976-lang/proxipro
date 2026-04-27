<?php

namespace App\Http\Controllers;

use App\Models\ProQuote;
use App\Models\ProInvoice;
use App\Models\Transaction;
use App\Models\FreeQuoteUsage;
use App\Services\ReferralService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;

class QuoteToolController extends Controller
{
    public function __construct(protected ReferralService $referralService)
    {
    }

    private $creditPacks = [
        'pack_5' => ['credits' => 5, 'price' => 499, 'label' => 'Découverte', 'price_display' => '4,99'],
        'pack_20' => ['credits' => 20, 'price' => 1499, 'label' => 'Professionnel', 'price_display' => '14,99'],
        'pack_50' => ['credits' => 50, 'price' => 2999, 'label' => 'Entreprise', 'price_display' => '29,99'],
    ];

    /**
     * Vérifie si l'utilisateur peut utiliser l'essai gratuit.
     * Croise : user_id (compte), IP, et cookie fingerprint pour empêcher les abus.
     */
    private function canUseFreeTrial(Request $request): bool
    {
        $user = Auth::user();
        $ip = $request->ip();
        $fingerprint = $request->cookie('qt_fp');

        // Si l'utilisateur a déjà utilisé son essai (côté compte)
        if (!$user->canCreateFreeQuote()) {
            return false;
        }

        // Vérifier aussi côté table anti-abus (IP / fingerprint / autre compte)
        if (FreeQuoteUsage::hasUsedFreeTrial($user->id, $ip, $fingerprint)) {
            return false;
        }

        return true;
    }

    /**
     * Vérifie si l'utilisateur peut créer un document (essai ou crédits payants).
     */
    private function canCreateDocumentReal(Request $request): bool
    {
        $user = Auth::user();

        // Abonnés pro : génération illimitée gratuite
        if ($user->hasActiveProSubscription()) {
            return true;
        }

        // Crédits payants disponibles
        if (($user->paid_quotes_remaining ?? 0) > 0) {
            return true;
        }

        // Sinon, vérifier l'essai gratuit avec anti-abus
        return $this->canUseFreeTrial($request);
    }

    /**
     * Enregistre l'utilisation de l'essai gratuit (IP + fingerprint + user).
     */
    private function recordFreeUsage(Request $request, string $documentType): void
    {
        FreeQuoteUsage::recordUsage(
            Auth::id(),
            $request->ip(),
            $request->cookie('qt_fp'),
            $documentType
        );
    }

    /**
     * Génère un cookie fingerprint s'il n'existe pas encore (durée 2 ans).
     */
    private function ensureFingerprint(Request $request): void
    {
        if (!$request->cookie('qt_fp')) {
            Cookie::queue('qt_fp', hash('sha256', Str::random(40) . $request->ip() . now()->timestamp), 60 * 24 * 730);
        }
    }

    /**
     * Landing page SEO publique (pas d'auth)
     */
    public function landing(Request $request)
    {
        $this->ensureFingerprint($request);
        return view('tools.quote-landing');
    }

    /**
     * Formulaire de création de devis
     */
    public function createQuote(Request $request)
    {
        $user = Auth::user();
        $this->ensureFingerprint($request);

        if (!$this->canCreateDocumentReal($request)) {
            return redirect()->route('quote-tool.credits')
                ->with('warning', 'Vous avez utilisé votre devis gratuit. Achetez des crédits pour continuer.');
        }

        $hasSubscription = $user->hasActiveProSubscription();

        return view('tools.quote-create', [
            'user' => $user,
            'creditsRemaining' => $user->getDocumentCreditsRemaining(),
            'isFree' => !$hasSubscription && $this->canUseFreeTrial($request),
            'hasSubscription' => $hasSubscription,
        ]);
    }

    /**
     * Traitement du devis + génération PDF
     */
    public function storeQuote(Request $request)
    {
        $user = Auth::user();

        if (!$this->canCreateDocumentReal($request)) {
            return redirect()->route('quote-tool.credits')
                ->with('warning', 'Vous avez utilisé votre devis gratuit. Achetez des crédits pour continuer.');
        }

        $validated = $request->validate([
            'client_name' => 'required|string|max:255',
            'client_email' => 'nullable|email|max:255',
            'client_phone' => 'nullable|string|max:20',
            'client_address' => 'nullable|string|max:500',
            'subject' => 'required|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:500',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'valid_until' => 'nullable|date|after:today',
            'notes' => 'nullable|string|max:2000',
            'conditions' => 'nullable|string|max:2000',
            'your_name' => 'required|string|max:255',
            'your_email' => 'nullable|email|max:255',
            'your_phone' => 'nullable|string|max:20',
            'your_address' => 'nullable|string|max:500',
            'your_company' => 'nullable|string|max:255',
            'your_siret' => 'nullable|string|max:17',
        ]);

        $items = collect($validated['items'])->map(function ($item) {
            $item['total'] = round($item['quantity'] * $item['unit_price'], 2);
            return $item;
        });

        $subtotal = $items->sum('total');
        $taxRate = $validated['tax_rate'] ?? 20;
        $tax = round($subtotal * $taxRate / 100, 2);
        $total = $subtotal + $tax;

        $token = Str::random(32);

        $quote = ProQuote::create([
            'user_id' => $user->id,
            'quote_number' => ProQuote::generateNumber($user->id),
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
            'status' => 'draft',
            'valid_until' => $validated['valid_until'] ?? now()->addDays(30),
            'notes' => $validated['notes'] ?? null,
            'conditions' => $validated['conditions'] ?? null,
        ]);

        // Décrémenter le crédit (les abonnés sont exemptés via useDocumentCredit)
        $isFreeUsage = !$user->hasActiveProSubscription() && $this->canUseFreeTrial($request);
        $user->useDocumentCredit();
        if ($isFreeUsage) {
            $this->recordFreeUsage($request, 'quote');
        }

        // Stocker les infos émetteur en session pour le PDF
        session(['quote_tool_emitter' => [
            'name' => $validated['your_name'],
            'email' => $validated['your_email'] ?? null,
            'phone' => $validated['your_phone'] ?? null,
            'address' => $validated['your_address'] ?? null,
            'company' => $validated['your_company'] ?? null,
            'siret' => $validated['your_siret'] ?? null,
        ]]);

        return redirect()->route('quote-tool.download', ['token' => $quote->id])
            ->with('success', 'Devis créé avec succès !');
    }

    /**
     * Formulaire de création de facture
     */
    public function createInvoice(Request $request)
    {
        $user = Auth::user();
        $this->ensureFingerprint($request);

        if (!$this->canCreateDocumentReal($request)) {
            return redirect()->route('quote-tool.credits')
                ->with('warning', 'Vous avez utilisé votre essai gratuit. Achetez des crédits pour continuer.');
        }

        $hasSubscription = $user->hasActiveProSubscription();

        return view('tools.invoice-create', [
            'user' => $user,
            'creditsRemaining' => $user->getDocumentCreditsRemaining(),
            'isFree' => !$hasSubscription && $this->canUseFreeTrial($request),
            'hasSubscription' => $hasSubscription,
        ]);
    }

    /**
     * Traitement de la facture + génération PDF
     */
    public function storeInvoice(Request $request)
    {
        $user = Auth::user();

        if (!$this->canCreateDocumentReal($request)) {
            return redirect()->route('quote-tool.credits')
                ->with('warning', 'Vous avez utilisé votre essai gratuit. Achetez des crédits pour continuer.');
        }

        $validated = $request->validate([
            'client_name' => 'required|string|max:255',
            'client_email' => 'nullable|email|max:255',
            'client_phone' => 'nullable|string|max:20',
            'client_address' => 'nullable|string|max:500',
            'subject' => 'nullable|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:500',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'payment_status' => 'required|in:unpaid,paid',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'due_date' => 'nullable|date',
            'notes' => 'nullable|string|max:2000',
            'payment_method' => 'nullable|string|max:50',
            'your_name' => 'required|string|max:255',
            'your_email' => 'nullable|email|max:255',
            'your_phone' => 'nullable|string|max:20',
            'your_address' => 'nullable|string|max:500',
            'your_company' => 'nullable|string|max:255',
            'your_siret' => 'nullable|string|max:17',
        ]);

        $items = collect($validated['items'])->map(function ($item) {
            $item['total'] = round($item['quantity'] * $item['unit_price'], 2);
            return $item;
        });

        $subtotal = $items->sum('total');
        $taxRate = $validated['tax_rate'] ?? 20;
        $tax = round($subtotal * $taxRate / 100, 2);
        $total = $subtotal + $tax;
        $subject = trim((string) ($validated['subject'] ?? ''));
        if ($subject === '') {
            $subject = trim((string) data_get($validated, 'items.0.description', ''));
        }
        if ($subject === '') {
            $subject = 'Facture';
        }
        $isPaid = ($validated['payment_status'] ?? 'unpaid') === 'paid';

        $invoice = ProInvoice::create([
            'user_id' => $user->id,
            'invoice_number' => ProInvoice::generateNumber($user->id),
            'client_name' => $validated['client_name'],
            'client_email' => $validated['client_email'] ?? null,
            'client_phone' => $validated['client_phone'] ?? null,
            'client_address' => $validated['client_address'] ?? null,
            'subject' => $subject,
            'items' => $items->toArray(),
            'subtotal' => $subtotal,
            'tax_rate' => $taxRate,
            'tax_amount' => $tax,
            'total' => $total,
            'status' => $isPaid ? 'paid' : 'sent',
            'due_date' => $validated['due_date'] ?? null,
            'paid_at' => $isPaid ? now() : null,
            'notes' => $validated['notes'] ?? null,
            'payment_method' => $validated['payment_method'] ?? null,
        ]);

        // Décrémenter le crédit (les abonnés sont exemptés via useDocumentCredit)
        $isFreeUsage = !$user->hasActiveProSubscription() && $this->canUseFreeTrial($request);
        $user->useDocumentCredit();
        if ($isFreeUsage) {
            $this->recordFreeUsage($request, 'invoice');
        }

        session(['quote_tool_emitter' => [
            'name' => $validated['your_name'],
            'email' => $validated['your_email'] ?? null,
            'phone' => $validated['your_phone'] ?? null,
            'address' => $validated['your_address'] ?? null,
            'company' => $validated['your_company'] ?? null,
            'siret' => $validated['your_siret'] ?? null,
        ]]);

        return redirect()->route('quote-tool.download', ['token' => 'inv-' . $invoice->id])
            ->with('success', 'Facture créée avec succès !');
    }

    /**
     * Télécharger le document PDF
     */
    public function downloadDocument($token)
    {
        $user = Auth::user();
        $emitter = session('quote_tool_emitter', [
            'name' => $user->company_name ?? $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'address' => $user->address,
            'company' => $user->company_name,
            'siret' => $user->siret,
        ]);

        $isInvoice = str_starts_with($token, 'inv-');
        $id = $isInvoice ? (int) str_replace('inv-', '', $token) : (int) $token;

        if ($isInvoice) {
            $document = $user->proInvoices()->findOrFail($id);
            $type = 'invoice';
            $view = 'pro.invoice-pdf';
            $filename = 'Facture_' . $document->invoice_number . '.pdf';
        } else {
            $document = $user->proQuotes()->findOrFail($id);
            $type = 'quote';
            $view = 'tools.quote-pdf';
            $filename = 'Devis_' . $document->quote_number . '.pdf';
        }

        if (request()->has('pdf')) {
            $pdfData = [
                'document' => $document,
                'emitter' => $emitter,
                'user' => $user,
            ];

            if ($isInvoice) {
                $pdfUser = clone $user;
                $pdfUser->name = $emitter['name'] ?? $pdfUser->name;
                $pdfUser->company_name = $emitter['company'] ?? $pdfUser->company_name;
                $pdfUser->email = $emitter['email'] ?? $pdfUser->email;
                $pdfUser->phone = $emitter['phone'] ?? $pdfUser->phone;
                $pdfUser->address = $emitter['address'] ?? $pdfUser->address;
                $pdfUser->siret = $emitter['siret'] ?? $pdfUser->siret;

                $pdfData = [
                    'invoice' => $document,
                    'user' => $pdfUser,
                    'statusLabel' => $document->status === 'paid' ? 'Payée' : 'Non payée',
                ];
            }

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView($view, $pdfData);
            $pdf->setPaper('a4');
            return $pdf->download($filename);
        }

        return view('tools.document-download', [
            'document' => $document,
            'type' => $type,
            'token' => $token,
            'emitter' => $emitter,
            'user' => $user,
            'creditsRemaining' => $user->getDocumentCreditsRemaining(),
        ]);
    }

    /**
     * Page d'achat de crédits
     */
    public function purchaseCredits()
    {
        $user = Auth::user();

        $hasSubscription = $user->hasActiveProSubscription();

        return view('tools.purchase-credits', [
            'user' => $user,
            'packs' => $this->creditPacks,
            'creditsRemaining' => $user->getDocumentCreditsRemaining(),
            'hasSubscription' => $hasSubscription,
        ]);
    }

    /**
     * Créer une session Stripe Checkout pour l'achat de crédits
     */
    public function processPurchase(Request $request)
    {
        $request->validate([
            'pack' => 'required|in:pack_5,pack_20,pack_50',
        ]);

        $user = Auth::user();
        $pack = $this->creditPacks[$request->pack];

        try {
            Stripe::setApiKey(config('services.stripe.secret'));

            $stripeCustomerId = $user->stripe_id;
            if (!$stripeCustomerId) {
                $customer = \Stripe\Customer::create([
                    'email' => $user->email,
                    'name' => $user->company_name ?? $user->name,
                    'metadata' => ['user_id' => $user->id],
                ]);
                $stripeCustomerId = $customer->id;
                $user->update(['stripe_id' => $stripeCustomerId]);
            }

            $session = StripeSession::create([
                'customer' => $stripeCustomerId,
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'eur',
                        'product_data' => [
                            'name' => 'Pack ' . $pack['label'] . ' - ' . $pack['credits'] . ' documents',
                            'description' => 'Crédits pour créer ' . $pack['credits'] . ' devis ou factures professionnels',
                        ],
                        'unit_amount' => $pack['price'],
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => route('quote-tool.credits.success') . '?session_id={CHECKOUT_SESSION_ID}&pack=' . $request->pack,
                'cancel_url' => route('quote-tool.credits.cancel'),
                'metadata' => [
                    'user_id' => $user->id,
                    'type' => 'quote_tool_credits',
                    'pack' => $request->pack,
                    'credits' => $pack['credits'],
                ],
            ]);

            return redirect()->away($session->url);

        } catch (\Exception $e) {
            \Log::error('Stripe Checkout error (quote tool): ' . $e->getMessage());
            return redirect()->route('quote-tool.credits')
                ->with('error', 'Erreur lors de la création du paiement. Veuillez réessayer.');
        }
    }

    /**
     * Callback succès Stripe
     */
    public function purchaseSuccess(Request $request)
    {
        $sessionId = $request->get('session_id');
        $packKey = $request->get('pack');
        $user = Auth::user();

        if (!$sessionId || !$packKey || !isset($this->creditPacks[$packKey])) {
            return redirect()->route('quote-tool.credits')->with('error', 'Session de paiement invalide.');
        }

        try {
            Stripe::setApiKey(config('services.stripe.secret'));
            $session = StripeSession::retrieve($sessionId);

            if ($session->payment_status !== 'paid') {
                return redirect()->route('quote-tool.credits')->with('error', 'Le paiement n\'a pas été confirmé.');
            }

            if ((int) $session->metadata->user_id !== $user->id) {
                return redirect()->route('quote-tool.credits')->with('error', 'Session invalide.');
            }

            // Idempotence
            $existing = Transaction::where('stripe_session_id', $sessionId)->first();
            if ($existing) {
                return redirect()->route('quote-tool.credits')
                    ->with('success', 'Vos crédits ont déjà été ajoutés !');
            }

            $pack = $this->creditPacks[$packKey];

            DB::transaction(function () use ($user, $pack, $sessionId) {
                $transaction = Transaction::create([
                    'user_id' => $user->id,
                    'amount' => $pack['price'] / 100,
                    'type' => 'DOCUMENT_CREDITS',
                    'description' => 'Achat pack ' . $pack['label'] . ' - ' . $pack['credits'] . ' crédits documents',
                    'status' => 'completed',
                    'stripe_session_id' => $sessionId,
                    'metadata' => [
                        'credits_added' => $pack['credits'],
                        'pack' => $packKey,
                        'payment_channel' => 'stripe',
                    ],
                ]);

                $user->increment('paid_quotes_remaining', $pack['credits']);
                $this->referralService->grantFirstPurchaseRewards($user->fresh(), $transaction);
            });

            $user->refresh();
            return redirect()->route('quote-tool.credits')
                ->with('success', $pack['credits'] . ' crédits ajoutés avec succès ! Vous pouvez maintenant créer ' . $user->getDocumentCreditsRemaining() . ' document(s).');

        } catch (\Exception $e) {
            \Log::error('Stripe success callback error (quote tool): ' . $e->getMessage());
            return redirect()->route('quote-tool.credits')
                ->with('error', 'Erreur lors de la confirmation du paiement.');
        }
    }

    /**
     * Callback annulation Stripe
     */
    public function purchaseCancel()
    {
        return redirect()->route('quote-tool.credits')
            ->with('info', 'Paiement annulé. Vous pouvez réessayer quand vous le souhaitez.');
    }

}
