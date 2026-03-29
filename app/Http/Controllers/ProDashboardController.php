<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ProClient;
use App\Models\ProQuote;
use App\Models\ProInvoice;
use App\Models\ProDocument;
use App\Models\ProSubscription;
use App\Models\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;

class ProDashboardController extends Controller
{
    public function __construct()
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
        if (!$user->isProfessionnel() && !$user->isServiceProvider()) {
            throw new \Illuminate\Http\Exceptions\HttpResponseException(
                redirect()->route('pro.dashboard')
                    ->with('warning', 'Cette fonctionnalité est réservée aux prestataires enregistrés. Vous devez d\'abord vous inscrire en tant que prestataire pour utiliser les outils de l\'Espace Pro.')
            );
        }
        return $user;
    }

    // =========================================
    // TABLEAU DE BORD
    // =========================================

    public function dashboard()
    {
        $user = Auth::user();
        $isPro = $user->isProfessionnel() || $user->isServiceProvider();

        // Accessible à tous : les non-pros voient un teaser
        if (!$isPro) {
            return view('pro.dashboard', [
                'user'           => $user,
                'isPro'          => false,
                'stats'          => [],
                'recentClients'  => collect(),
                'recentQuotes'   => collect(),
                'recentInvoices' => collect(),
                'subscription'   => null,
            ]);
        }

        $stats = [
            'total_clients' => $user->proClients()->count(),
            'active_clients' => $user->proClients()->where('status', 'active')->count(),
            'total_quotes' => $user->proQuotes()->count(),
            'pending_quotes' => $user->proQuotes()->where('status', 'pending')->count(),
            'total_invoices' => $user->proInvoices()->count(),
            'unpaid_invoices' => $user->proInvoices()->where('status', 'sent')->count(),
            'total_revenue' => $user->proInvoices()->where('status', 'paid')->sum('total'),
            'monthly_revenue' => $user->proInvoices()->where('status', 'paid')
                ->whereMonth('paid_at', now()->month)->sum('total'),
            'total_documents' => $user->proDocuments()->count(),
            'reviews_count' => $user->reviewsReceived()->count(),
            'average_rating' => round($user->reviewsReceived()->avg('rating') ?? 0, 1),
            'active_ads' => $user->ads()->where('status', 'active')->count(),
        ];

        $recentClients = $user->proClients()->latest('last_interaction_at')->take(5)->get();
        $recentQuotes = $user->proQuotes()->with('client')->latest()->take(5)->get();
        $recentInvoices = $user->proInvoices()->with('client')->latest()->take(5)->get();
        $subscription = $user->proSubscription;

        return view('pro.dashboard', compact('user', 'stats', 'recentClients', 'recentQuotes', 'recentInvoices', 'subscription') + ['isPro' => true]);
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
            $request->merge(['specialties' => !empty($specialties) ? array_values($specialties) : null]);
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
        ]);

        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $validated['avatar'] = $path;
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
        $user = $this->ensurePro();
        $quotes = $user->proQuotes()->with('client')->latest()->paginate(15);
        $clients = $user->proClients()->orderBy('name')->get();

        return view('pro.quotes', compact('user', 'quotes', 'clients'));
    }

    public function createQuote()
    {
        $user = $this->ensurePro();
        $clients = $user->proClients()->orderBy('name')->get();

        return view('pro.quote-create', compact('user', 'clients'));
    }

    public function storeQuote(Request $request)
    {
        $user = $this->ensurePro();

        $validated = $request->validate([
            'client_id' => 'nullable|exists:pro_clients,id',
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
        ]);

        $items = collect($validated['items'])->map(function ($item) {
            $item['total'] = round($item['quantity'] * $item['unit_price'], 2);
            return $item;
        });

        $subtotal = $items->sum('total');
        $taxRate = $validated['tax_rate'] ?? 20;
        $tax = round($subtotal * $taxRate / 100, 2);
        $total = $subtotal + $tax;

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

        // Auto-create client if doesn't exist
        if (empty($validated['client_id']) && $validated['client_name']) {
            $existingClient = $user->proClients()->where('name', $validated['client_name'])->first();
            if (!$existingClient) {
                $client = ProClient::create([
                    'provider_id' => $user->id,
                    'name' => $validated['client_name'],
                    'email' => $validated['client_email'] ?? null,
                    'phone' => $validated['client_phone'] ?? null,
                    'address' => $validated['client_address'] ?? null,
                    'status' => 'active',
                    'source' => 'quote',
                    'last_interaction_at' => now(),
                ]);
            }
        }

        return redirect()->route('pro.quotes')->with('success', 'Devis créé avec succès.');
    }

    public function showQuote($id)
    {
        $user = $this->ensurePro();
        $quote = $user->proQuotes()->with('client')->findOrFail($id);

        return view('pro.quote-show', compact('user', 'quote'));
    }

    public function updateQuoteStatus(Request $request, $id)
    {
        $user = $this->ensurePro();
        $quote = $user->proQuotes()->findOrFail($id);

        $request->validate(['status' => 'required|in:draft,sent,accepted,rejected,expired']);
        $quote->update(['status' => $request->status]);

        // Si le devis est accepté, on peut auto-générer une facture
        if ($request->status === 'accepted') {
            return redirect()->route('pro.quotes')->with('success', 'Devis accepté ! Vous pouvez maintenant créer une facture associée.');
        }

        return redirect()->route('pro.quotes')->with('success', 'Statut du devis mis à jour.');
    }

    public function editQuote($id)
    {
        $user = $this->ensurePro();
        $quote = $user->proQuotes()->findOrFail($id);
        $clients = $user->proClients()->orderBy('name')->get();

        return view('pro.quote-edit', compact('user', 'quote', 'clients'));
    }

    public function updateQuote(Request $request, $id)
    {
        $user = $this->ensurePro();
        $quote = $user->proQuotes()->findOrFail($id);

        $validated = $request->validate([
            'client_id' => 'nullable|exists:pro_clients,id',
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
            'valid_until' => 'nullable|date',
            'notes' => 'nullable|string|max:2000',
            'conditions' => 'nullable|string|max:2000',
        ]);

        $items = collect($validated['items'])->map(function ($item) {
            $item['total'] = round($item['quantity'] * $item['unit_price'], 2);
            return $item;
        });

        $subtotal = $items->sum('total');
        $taxRate = $validated['tax_rate'] ?? 20;
        $tax = round($subtotal * $taxRate / 100, 2);
        $total = $subtotal + $tax;

        $quote->update([
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

    public function deleteQuote($id)
    {
        $user = $this->ensurePro();
        $quote = $user->proQuotes()->findOrFail($id);
        $quote->delete();

        return redirect()->route('pro.quotes')->with('success', 'Devis supprimé avec succès.');
    }

    public function downloadQuote($id)
    {
        $user = $this->ensurePro();
        $quote = $user->proQuotes()->with('client')->findOrFail($id);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pro.quote-pdf', compact('user', 'quote'));
        $pdf->setPaper('a4');

        return $pdf->download('Devis_' . $quote->quote_number . '.pdf');
    }

    public function sendQuoteEmail(Request $request, $id)
    {
        $user = $this->ensurePro();
        $quote = $user->proQuotes()->with('client')->findOrFail($id);

        $request->validate([
            'email' => 'required|email',
            'message' => 'nullable|string|max:2000',
        ]);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pro.quote-pdf', compact('user', 'quote'));
        $pdf->setPaper('a4');

        \Illuminate\Support\Facades\Mail::send('emails.quote', [
            'quote' => $quote,
            'user' => $user,
            'customMessage' => $request->message,
        ], function ($mail) use ($request, $quote, $user, $pdf) {
            $mail->to($request->email)
                ->subject('Devis ' . $quote->quote_number . ' - ' . ($user->company_name ?? $user->name))
                ->attachData($pdf->output(), 'Devis_' . $quote->quote_number . '.pdf', [
                    'mime' => 'application/pdf',
                ]);
        });

        // Mark as sent
        if ($quote->status === 'draft') {
            $quote->update(['status' => 'sent']);
        }

        return redirect()->route('pro.quotes.show', $id)->with('success', 'Devis envoyé par email à ' . $request->email);
    }

    public function sendQuoteMessage(Request $request, $id)
    {
        $user = $this->ensurePro();
        $quote = $user->proQuotes()->with('client')->findOrFail($id);

        $request->validate([
            'recipient_id' => 'required|exists:users,id',
            'message' => 'nullable|string|max:2000',
        ]);

        $recipient = User::findOrFail($request->recipient_id);

        // Get or create conversation
        $conversation = \App\Models\Conversation::getOrCreate($user->id, $recipient->id, 'Devis ' . $quote->quote_number);

        // Build quote summary message
        $content = "📄 *Devis " . $quote->quote_number . "*\n\n"
            . "📋 Objet : " . $quote->subject . "\n"
            . "💰 Montant TTC : " . number_format($quote->total, 2, ',', ' ') . "€\n"
            . "📅 Date : " . $quote->created_at->format('d/m/Y') . "\n";
        if ($quote->valid_until) {
            $content .= "⏳ Valide jusqu'au : " . $quote->valid_until->format('d/m/Y') . "\n";
        }
        $content .= "\n🔗 Lien : " . route('pro.quotes.show', $quote->id);

        if ($request->message) {
            $content .= "\n\n💬 Message :\n" . $request->message;
        }

        \App\Models\Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'content' => $content,
        ]);

        // Mark as sent
        if ($quote->status === 'draft') {
            $quote->update(['status' => 'sent']);
        }

        return redirect()->route('pro.quotes.show', $id)->with('success', 'Devis envoyé via la messagerie à ' . $recipient->name);
    }

    // =========================================
    // FACTURES
    // =========================================

    public function invoices()
    {
        $user = $this->ensurePro();
        $invoices = $user->proInvoices()->with('client')->latest()->paginate(15);
        $clients = $user->proClients()->orderBy('name')->get();

        return view('pro.invoices', compact('user', 'invoices', 'clients'));
    }

    public function createInvoice($quoteId = null)
    {
        $user = $this->ensurePro();
        $clients = $user->proClients()->orderBy('name')->get();
        $quote = $quoteId ? $user->proQuotes()->find($quoteId) : null;

        return view('pro.invoice-create', compact('user', 'clients', 'quote'));
    }

    public function storeInvoice(Request $request)
    {
        $user = $this->ensurePro();

        $validated = $request->validate([
            'client_id' => 'nullable|exists:pro_clients,id',
            'quote_id' => 'nullable|exists:pro_quotes,id',
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
            'due_date' => 'nullable|date',
            'notes' => 'nullable|string|max:2000',
            'payment_method' => 'nullable|string|max:50',
        ]);

        $items = collect($validated['items'])->map(function ($item) {
            $item['total'] = round($item['quantity'] * $item['unit_price'], 2);
            return $item;
        });

        $subtotal = $items->sum('total');
        $taxRate = $validated['tax_rate'] ?? 20;
        $tax = round($subtotal * $taxRate / 100, 2);
        $total = $subtotal + $tax;

        ProInvoice::create([
            'user_id' => $user->id,
            'quote_id' => $validated['quote_id'] ?? null,
            'invoice_number' => ProInvoice::generateNumber($user->id),
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
            'due_date' => $validated['due_date'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'payment_method' => $validated['payment_method'] ?? null,
        ]);

        return redirect()->route('pro.invoices')->with('success', 'Facture créée avec succès.');
    }

    public function showInvoice($id)
    {
        $user = $this->ensurePro();
        $invoice = $user->proInvoices()->with('client', 'quote')->findOrFail($id);

        return view('pro.invoice-show', compact('user', 'invoice'));
    }

    public function downloadInvoice($id)
    {
        $user = $this->ensurePro();
        $invoice = $user->proInvoices()->with('client', 'quote')->findOrFail($id);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pro.invoice-pdf', compact('user', 'invoice'));
        $pdf->setPaper('a4');

        return $pdf->download('Facture_' . $invoice->invoice_number . '.pdf');
    }

    public function destroyInvoice($id)
    {
        $user = $this->ensurePro();
        $invoice = $user->proInvoices()->findOrFail($id);
        $invoice->delete();

        return redirect()->route('pro.invoices')->with('success', 'Facture supprimée avec succès.');
    }

    public function updateInvoiceStatus(Request $request, $id)
    {
        $user = $this->ensurePro();
        $invoice = $user->proInvoices()->findOrFail($id);

        $request->validate(['status' => 'required|in:draft,sent,paid,overdue,cancelled']);

        $updateData = ['status' => $request->status];
        if ($request->status === 'paid') {
            $updateData['paid_at'] = now();
            $updateData['payment_method'] = $request->payment_method ?? 'other';

            // Update client revenue
            if ($invoice->client_id) {
                $client = ProClient::find($invoice->client_id);
                if ($client) {
                    $client->increment('total_revenue', $invoice->total);
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
        $user = $this->ensurePro();
        $invoice = $user->proInvoices()->with('quote')->findOrFail($id);
        $clients = $user->proClients()->orderBy('name')->get();

        return view('pro.invoice-edit', compact('user', 'invoice', 'clients'));
    }

    public function updateInvoice(Request $request, $id)
    {
        $user = $this->ensurePro();
        $invoice = $user->proInvoices()->findOrFail($id);

        $validated = $request->validate([
            'client_id' => 'nullable|exists:pro_clients,id',
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
            'due_date' => 'nullable|date',
            'notes' => 'nullable|string|max:2000',
            'payment_method' => 'nullable|string|max:50',
        ]);

        $items = collect($validated['items'])->map(function ($item) {
            $item['total'] = round($item['quantity'] * $item['unit_price'], 2);
            return $item;
        });

        $subtotal = $items->sum('total');
        $taxRate = $validated['tax_rate'] ?? 20;
        $tax = round($subtotal * $taxRate / 100, 2);
        $total = $subtotal + $tax;

        $invoice->update([
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
        $path = $file->store('pro-documents/' . $user->id, 'public');

        ProDocument::create([
            'user_id' => $user->id,
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

        return redirect()->route('pro.documents')->with('success', 'Document uploadé avec succès. Il sera vérifié sous 48h.');
    }

    public function deleteDocument($id)
    {
        $user = $this->ensurePro();
        $document = $user->proDocuments()->findOrFail($id);

        Storage::disk('public')->delete($document->file_path);
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

    public function updateAccountStatus(Request $request)
    {
        $user = $this->ensurePro();

        $validated = $request->validate([
            'pro_status' => 'required|in:particulier,auto-entrepreneur,entreprise',
            'company_name' => 'nullable|string|max:255',
            'siret' => 'nullable|string|max:17',
            'tva_number' => 'nullable|string|max:20',
            'insurance_number' => 'nullable|string|max:100',
        ]);

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

        $user->update($updateData);

        return redirect()->route('pro.account-status')->with('success', 'Statut du compte mis à jour avec succès.');
    }

    // =========================================
    // ABONNEMENT PRO
    // =========================================

    public function subscription()
    {
        $user = $this->ensurePro();
        $subscription = $user->proSubscription;
        $subscriptions = $user->proSubscriptions()->latest()->get();

        return view('pro.subscription', compact('user', 'subscription', 'subscriptions'));
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
                if ($request->filled('address')) $updateData['address'] = $request->input('address');
                if ($request->filled('postal_code')) $updateData['postal_code'] = $request->input('postal_code');
                if ($request->filled('city')) {
                    $updateData['city'] = $request->input('city');
                    $updateData['detected_city'] = $request->input('city');
                }
                if ($request->filled('country')) {
                    $updateData['country'] = $request->input('country');
                    $updateData['detected_country'] = $request->input('country');
                }
                if ($request->filled('phone')) $updateData['phone'] = $request->input('phone');
                if ($request->filled('intervention_radius')) $updateData['pro_intervention_radius'] = (int) $request->input('intervention_radius');
            }
            if ($step >= 3 && $request->has('categories')) {
                $updateData['pro_service_categories'] = $request->input('categories');
                if ($request->has('subcategories')) {
                    $updateData['service_subcategories'] = $request->input('subcategories');
                }
            }
            if ($step >= 4) {
                if ($request->has('notifications_realtime')) $updateData['pro_notifications_realtime'] = (bool) $request->input('notifications_realtime');
                if ($request->has('notifications_email')) $updateData['pro_notifications_email'] = (bool) $request->input('notifications_email');
                if ($request->has('notifications_sms')) $updateData['pro_notifications_sms'] = (bool) $request->input('notifications_sms');
                if ($request->filled('phone_sms')) $updateData['pro_phone_sms'] = $request->input('phone_sms');
            }

            $user->update($updateData);
            return response()->json(['success' => true, 'step' => $step]);
        }

        // Handle complete without subscription (skip_subscription)
        if ($request->input('skip_subscription')) {
            $validated = $request->validate([
                'categories' => 'nullable|array',
                'subcategories' => 'nullable|array',
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

            if (!empty($validated['categories'])) {
                $updateData['pro_service_categories'] = $validated['categories'];
                // Synchroniser service_category avec la première catégorie sélectionnée
                $updateData['service_category'] = $validated['categories'][0] ?? null;
            }
            if (!empty($validated['subcategories'])) {
                $updateData['service_subcategories'] = $validated['subcategories'];
                // Synchroniser profession avec la première sous-catégorie
                if (empty($user->profession)) {
                    $updateData['profession'] = $validated['subcategories'][0] ?? null;
                }
            }
            if (!empty($validated['intervention_radius'])) $updateData['pro_intervention_radius'] = $validated['intervention_radius'];
            if (isset($validated['notifications_realtime'])) $updateData['pro_notifications_realtime'] = $validated['notifications_realtime'];
            if (isset($validated['notifications_email'])) $updateData['pro_notifications_email'] = $validated['notifications_email'];
            if (isset($validated['notifications_sms'])) $updateData['pro_notifications_sms'] = $validated['notifications_sms'];
            if (!empty($validated['phone_sms'])) $updateData['pro_phone_sms'] = $validated['phone_sms'];
            if (!empty($validated['address'])) $updateData['address'] = $validated['address'];
            if (!empty($validated['postal_code'])) $updateData['postal_code'] = $validated['postal_code'];
            if (!empty($validated['phone'])) $updateData['phone'] = $validated['phone'];
            if (!empty($validated['city'])) {
                $updateData['city'] = $validated['city'];
                $updateData['detected_city'] = $validated['city'];
            }
            if (!empty($validated['country'])) {
                $updateData['country'] = $validated['country'];
                $updateData['detected_country'] = $validated['country'];
            }

            $user->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Configuration terminée ! Vous pouvez souscrire à un abonnement plus tard.',
                'redirect' => route('feed'),
                'skipped_subscription' => true,
            ]);
        }

        // Full subscribe with payment via Stripe Checkout
        $validated = $request->validate([
            'plan' => 'required|in:monthly,annual',
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

        $amount = $validated['plan'] === 'annual' ? 85.00 : 9.99;
        $planLabel = $validated['plan'] === 'annual' ? 'Abonnement ProxiPro Annuel' : 'Abonnement ProxiPro Mensuel';

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
            Stripe::setApiKey(config('services.stripe.secret'));

            // Create or retrieve Stripe customer
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

            // Create Stripe Checkout Session
            $session = StripeSession::create([
                'customer' => $stripeCustomerId,
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'eur',
                        'product_data' => [
                            'name' => $planLabel,
                            'description' => $validated['plan'] === 'annual'
                                ? 'Accès complet pendant 1 an (soit 7,08€/mois)'
                                : 'Accès complet pendant 1 mois, renouvelable',
                        ],
                        'unit_amount' => (int) round($amount * 100), // Stripe uses cents
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => route('pro.onboarding.payment.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('pro.onboarding.payment.cancel'),
                'metadata' => [
                    'user_id' => $user->id,
                    'type' => 'pro_subscription',
                    'plan' => $validated['plan'],
                    'amount' => $amount,
                ],
            ]);

            // For standard form submissions (e.g. subscription page), redirect directly to Stripe
            if (!$request->expectsJson() && !$request->ajax()) {
                return redirect()->away($session->url);
            }

            return response()->json([
                'success' => true,
                'requires_payment' => true,
                'checkout_url' => $session->url,
            ]);

        } catch (\Exception $e) {
            \Log::error('Stripe Checkout creation error (pro onboarding): ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user_id' => $user->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du paiement : ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle successful Stripe payment for pro onboarding
     */
    public function onboardingPaymentSuccess(Request $request)
    {
        $sessionId = $request->get('session_id');
        $user = Auth::user();

        if (!$sessionId || !$user) {
            return redirect()->route('pro.onboarding')->with('error', 'Session de paiement invalide.');
        }

        try {
            Stripe::setApiKey(config('services.stripe.secret'));
            $session = StripeSession::retrieve($sessionId);

            if ($session->payment_status !== 'paid') {
                return redirect()->route('pro.onboarding')->with('error', 'Le paiement n\'a pas été confirmé. Veuillez réessayer.');
            }

            // Verify this session belongs to this user
            if ((int) $session->metadata->user_id !== $user->id) {
                return redirect()->route('pro.onboarding')->with('error', 'Session de paiement invalide.');
            }

            // Check if already processed (idempotency)
            $existingSub = ProSubscription::where('stripe_payment_intent', $session->payment_intent)->first();
            if ($existingSub) {
                return redirect()->route('pro.dashboard')->with('success', 'Votre abonnement est déjà actif !');
            }

            // Retrieve onboarding data from session
            $data = session('pro_onboarding_data', []);
            $plan = $data['plan'] ?? $session->metadata->plan ?? 'monthly';
            $amount = $plan === 'annual' ? 85.00 : 9.99;
            $categories = $data['categories'] ?? $user->pro_service_categories ?? [];
            $interventionRadius = $data['intervention_radius'] ?? $user->pro_intervention_radius ?? 30;

            DB::beginTransaction();
            try {
                // Create subscription
                $subscription = ProSubscription::create([
                    'user_id' => $user->id,
                    'plan' => $plan,
                    'amount' => $amount,
                    'status' => 'active',
                    'starts_at' => now(),
                    'ends_at' => $plan === 'annual' ? now()->addYear() : now()->addMonth(),
                    'auto_renew' => true,
                    'notifications_enabled' => true,
                    'realtime_notifications' => $data['notifications_realtime'] ?? true,
                    'selected_categories' => $categories,
                    'intervention_radius' => $interventionRadius,
                    'stripe_payment_intent' => $session->payment_intent,
                ]);

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

                if (!empty($data['phone_sms'])) $updateData['pro_phone_sms'] = $data['phone_sms'];
                if (!empty($data['address'])) $updateData['address'] = $data['address'];
                if (!empty($data['postal_code'])) $updateData['postal_code'] = $data['postal_code'];
                if (!empty($data['phone'])) $updateData['phone'] = $data['phone'];
                if (!empty($data['city'])) {
                    $updateData['city'] = $data['city'];
                    $updateData['detected_city'] = $data['city'];
                }
                if (!empty($data['country'])) {
                    $updateData['country'] = $data['country'];
                    $updateData['detected_country'] = $data['country'];
                }
                if (!empty($data['subcategories'])) {
                    $updateData['service_subcategories'] = $data['subcategories'];
                }

                $user->update($updateData);

                // Update geolocation if provided
                if (!empty($data['latitude']) && !empty($data['longitude'])) {
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

                return redirect()->route('pro.dashboard')->with('success', 'Félicitations ! Votre abonnement ProxiPro est activé. Bienvenue dans votre espace professionnel !');

            } catch (\Exception $e) {
                DB::rollBack();
                \Log::error('Pro subscription creation after payment error: ' . $e->getMessage(), [
                    'trace' => $e->getTraceAsString(),
                    'user_id' => $user->id,
                    'session_id' => $sessionId,
                ]);
                return redirect()->route('pro.onboarding')->with('error', 'Le paiement a été effectué mais une erreur est survenue. Veuillez contacter le support.');
            }

        } catch (\Exception $e) {
            \Log::error('Stripe session retrieval error (pro onboarding): ' . $e->getMessage(), [
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
        return redirect()->route('pro.onboarding')->with('info', 'Le paiement a été annulé. Vous pouvez réessayer ou choisir de configurer sans abonnement.');
    }

    // =========================================
    // ONBOARDING PAGE
    // =========================================

    public function onboarding()
    {
        $user = Auth::user();

        // If not a professional, redirect
        if (!$user->isProfessionnel() && !$user->isServiceProvider()) {
            return redirect()->route('feed');
        }

        // If onboarding already completed, go to dashboard
        if ($user->hasCompletedProOnboarding()) {
            return redirect()->route('pro.dashboard');
        }

        $categories = $this->getServiceCategories();

        return view('pro.onboarding', compact('user', 'categories'));
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
                ->sum('total');
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
        $totalQuotes = $user->proQuotes()->count();
        $acceptedQuotes = $user->proQuotes()->where('status', 'accepted')->count();
        $conversionRate = $totalQuotes > 0 ? round(($acceptedQuotes / $totalQuotes) * 100, 1) : 0;

        // Invoice payment rate
        $totalInvoices = $user->proInvoices()->count();
        $paidInvoices = $user->proInvoices()->where('status', 'paid')->count();
        $paymentRate = $totalInvoices > 0 ? round(($paidInvoices / $totalInvoices) * 100, 1) : 0;

        // Top clients by revenue
        $topClients = $user->proClients()
            ->withSum(['quotes as total_revenue' => function ($q) {
                $q->whereHas('invoice', function ($iq) {
                    $iq->where('status', 'paid');
                });
            }], 'total')
            ->orderByDesc('total_revenue')
            ->take(5)
            ->get();

        // Summary stats
        $stats = [
            'total_revenue' => $user->proInvoices()->where('status', 'paid')->sum('total'),
            'monthly_revenue' => $user->proInvoices()->where('status', 'paid')
                ->whereMonth('paid_at', now()->month)
                ->whereYear('paid_at', now()->year)
                ->sum('total'),
            'avg_quote_value' => round($user->proQuotes()->avg('total') ?? 0, 2),
            'avg_invoice_value' => round($user->proInvoices()->where('status', 'paid')->avg('total') ?? 0, 2),
            'total_clients' => $user->proClients()->count(),
            'active_clients' => $user->proClients()->where('status', 'active')->count(),
            'total_quotes' => $totalQuotes,
            'accepted_quotes' => $acceptedQuotes,
            'conversion_rate' => $conversionRate,
            'total_invoices' => $totalInvoices,
            'paid_invoices' => $paidInvoices,
            'payment_rate' => $paymentRate,
            'pending_amount' => $user->proInvoices()->whereIn('status', ['sent', 'pending'])->sum('total'),
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
                    'id' => 'quote_' . $quote->id,
                    'title' => $quote->title ?? ('Devis #' . $quote->quote_number),
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
            ->whereIn('status', ['sent', 'pending'])
            ->with('client')
            ->orderBy('due_date', 'asc')
            ->take(20)
            ->get()
            ->map(function ($invoice) {
                return [
                    'id' => 'invoice_' . $invoice->id,
                    'title' => 'Facture #' . $invoice->invoice_number,
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
                    'id' => 'client_' . $client->id,
                    'title' => 'Interaction: ' . $client->name,
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
            'upcoming_deadlines' => $user->proInvoices()->whereIn('status', ['sent', 'pending'])
                ->where('due_date', '>=', now())
                ->where('due_date', '<=', now()->addDays(7))
                ->count(),
            'overdue_invoices' => $user->proInvoices()->whereIn('status', ['sent', 'pending'])
                ->where('due_date', '<', now())
                ->count(),
            'active_quotes' => $user->proQuotes()->where('status', 'pending')->count(),
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
                    ->sum('total'),
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
        $categories = [];
        foreach (config('categories.services') as $name => $data) {
            $categories[$name] = [
                'icon'           => $data['icon'],
                'color'          => $data['color'],
                'subcategories'  => $data['subcategories'],
            ];
        }
        return $categories;
    }
}
