<?php

namespace App\Http\Controllers;

use App\Models\IdentityVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Stripe\Stripe;
use Stripe\Checkout\Session;

class VerificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show verification form
     */
    public function index()
    {
        $user = Auth::user();
        $verification = IdentityVerification::where('user_id', $user->id)
            ->latest()
            ->first();

        // Check profile completeness
        $missingFields = $this->getProfileMissingFields($user);
        $requiresProDoc = IdentityVerification::requiresProfessionalDocument($user);
        $proDocType = IdentityVerification::getRequiredProfessionalDocumentType($user);

        // Mark related notifications as read
        $user->unreadNotifications()
            ->where('type', 'App\\Notifications\\VerificationDocumentRejected')
            ->get()
            ->each(function ($notification) use ($verification) {
                if ($verification && isset($notification->data['verification_id']) && $notification->data['verification_id'] == $verification->id) {
                    $notification->markAsRead();
                }
            });

        return view('verification.index', compact('user', 'verification', 'missingFields', 'requiresProDoc', 'proDocType'));
    }

    /**
     * Get missing profile fields
     */
    private function getProfileMissingFields($user): array
    {
        $missing = [];
        if (!$user->name) $missing[] = ['field' => 'name', 'label' => 'Nom complet'];
        if (!$user->phone) $missing[] = ['field' => 'phone', 'label' => 'Numéro de téléphone'];
        if (!$user->city && !$user->detected_city) $missing[] = ['field' => 'city', 'label' => 'Ville'];
        if (!$user->country && !$user->detected_country) $missing[] = ['field' => 'country', 'label' => 'Pays'];
        if (!$user->address) $missing[] = ['field' => 'address', 'label' => 'Adresse'];
        
        if ($user->isProfessionnel()) {
            // profession peut venir de service_subcategories si pas renseigné directement
            $hasProfession = !empty($user->profession) 
                || (!empty($user->service_subcategories) && is_array($user->service_subcategories) && count($user->service_subcategories) > 0);
            if (!$hasProfession) $missing[] = ['field' => 'profession', 'label' => 'Profession / Métier'];
            if (!$user->business_type) $missing[] = ['field' => 'business_type', 'label' => 'Type d\'activité (entreprise/auto-entrepreneur)'];
        }
        
        return $missing;
    }

    /**
     * API: Get verification status
     */
    public function getStatus()
    {
        $user = Auth::user();
        
        $pendingVerification = IdentityVerification::where('user_id', $user->id)
            ->where('status', 'pending')
            ->first();
        
        $returnedVerification = IdentityVerification::where('user_id', $user->id)
            ->where('status', 'returned')
            ->first();
        
        $pendingPayment = IdentityVerification::where('user_id', $user->id)
            ->where('payment_status', 'pending')
            ->where('status', 'pending')
            ->first();

        return response()->json([
            'is_verified' => $user->is_verified,
            'is_service_provider' => $user->is_service_provider,
            'service_provider_verified' => $user->service_provider_verified,
            'has_pending_verification' => (bool) $pendingVerification,
            'has_returned_verification' => (bool) $returnedVerification,
            'has_pending_payment' => (bool) $pendingPayment,
            'pending_verification_id' => $pendingPayment?->id,
            'profile_verification_price' => IdentityVerification::getVerificationPrice('profile_verification'),
            'profile_verification_points' => IdentityVerification::getVerificationPointsCost('profile_verification'),
            'service_provider_price' => IdentityVerification::getVerificationPrice('service_provider'),
            'service_provider_points' => IdentityVerification::getVerificationPointsCost('service_provider'),
            'user_points' => $user->available_points ?? 0,
        ]);
    }

    /**
     * Store verification documents (AJAX)
     */
    public function storeAjax(Request $request)
    {
        \Log::info('storeAjax called', [
            'has_document_front' => $request->hasFile('document_front'),
            'has_selfie' => $request->hasFile('selfie'),
            'has_document_back' => $request->hasFile('document_back'),
            'has_professional_document' => $request->hasFile('professional_document'),
            'all_files' => array_keys($request->allFiles()),
            'all_input' => array_keys($request->all()),
        ]);
        
        $rules = [
            'type' => 'required|in:profile_verification,service_provider',
            'document_type' => 'required|in:cni,passport,permis,carte_sejour,id_card,driver_license',
            'document_front' => 'required|file|mimes:jpeg,png,jpg,pdf|max:8192',
            'document_back' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:8192',
            'selfie' => 'required|image|max:8192',
        ];

        $user = Auth::user();

        // Professional document required for pros
        if (IdentityVerification::requiresProfessionalDocument($user)) {
            $rules['professional_document'] = 'required|file|mimes:jpeg,png,jpg,pdf|max:8192';
            $rules['professional_document_type'] = 'required|in:kbis,sirene';
        }

        $request->validate($rules, [
            'document_front.required' => 'Veuillez télécharger le recto de votre document.',
            'document_front.mimes' => 'Le fichier doit être une image (JPG, PNG) ou un PDF.',
            'document_front.max' => 'Le fichier ne doit pas dépasser 8 Mo.',
            'document_back.mimes' => 'Le fichier doit être une image (JPG, PNG) ou un PDF.',
            'document_back.max' => 'Le fichier ne doit pas dépasser 8 Mo.',
            'selfie.required' => 'Veuillez télécharger votre selfie.',
            'selfie.image' => 'Le selfie doit être une image.',
            'selfie.max' => 'Le selfie ne doit pas dépasser 8 Mo.',
            'professional_document.required' => 'Le document professionnel est obligatoire pour les professionnels.',
            'professional_document.mimes' => 'Le document professionnel doit être une image ou un PDF.',
        ]);

        // Vérifier si une demande est déjà en cours
        $existingVerification = IdentityVerification::where('user_id', $user->id)
            ->where('type', $request->type)
            ->where('status', 'pending')
            ->first();

        if ($existingVerification) {
            return response()->json([
                'success' => false,
                'message' => 'Vous avez déjà une demande de vérification en cours.',
            ], 400);
        }

        // Pour service_provider, vérifier que le profil est vérifié
        if ($request->type === 'service_provider' && !$user->is_verified) {
            return response()->json([
                'success' => false,
                'message' => 'Vous devez d\'abord vérifier votre profil.',
                'need_profile_verification' => true,
            ], 400);
        }

        // Stocker les documents
        $documentFront = $request->file('document_front')->store('verifications-temp/' . $user->id, config('filesystems.default', 'public'));
        $documentBack = $request->hasFile('document_back') 
            ? $request->file('document_back')->store('verifications-temp/' . $user->id, config('filesystems.default', 'public'))
            : null;
        $selfie = $request->file('selfie')->store('verifications-temp/' . $user->id, config('filesystems.default', 'public'));
        
        $professionalDocument = null;
        $professionalDocumentType = null;
        if ($request->hasFile('professional_document')) {
            $professionalDocument = $request->file('professional_document')->store('verifications-temp/' . $user->id, config('filesystems.default', 'public'));
            $professionalDocumentType = $request->professional_document_type;
        }

        $price = IdentityVerification::getVerificationPrice($request->type);

        $verification = IdentityVerification::create([
            'user_id' => $user->id,
            'type' => $request->type,
            'document_type' => $request->document_type,
            'document_front' => $documentFront,
            'document_front_status' => 'pending',
            'document_back' => $documentBack,
            'document_back_status' => $documentBack ? 'pending' : null,
            'selfie' => $selfie,
            'selfie_status' => 'pending',
            'professional_document' => $professionalDocument,
            'professional_document_type' => $professionalDocumentType,
            'professional_document_status' => $professionalDocument ? 'pending' : null,
            'payment_amount' => $price,
            'payment_status' => 'pending',
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'verification_id' => $verification->id,
            'message' => 'Documents téléchargés. Veuillez procéder au paiement.',
            'price' => $price,
        ]);
    }

    /**
     * Resubmit corrected documents on a returned verification
     */
    public function resubmit(Request $request)
    {
        $user = Auth::user();
        
        $verification = IdentityVerification::where('user_id', $user->id)
            ->where('status', 'returned')
            ->latest()
            ->firstOrFail();

        $rejectedDocs = $verification->getRejectedDocuments();
        $rejectedFields = array_column($rejectedDocs, 'field');

        // If no specific documents are rejected (fallback full resubmit), allow all main docs
        if (empty($rejectedFields)) {
            $rejectedFields = ['document_front', 'selfie'];
            if ($verification->document_back) {
                $rejectedFields[] = 'document_back';
            }
            if ($verification->professional_document) {
                $rejectedFields[] = 'professional_document';
            }
        }

        // Build validation rules only for rejected documents
        $rules = [];
        foreach ($rejectedFields as $field) {
            if ($field === 'selfie') {
                $rules[$field] = 'required|image|max:8192';
            } else {
                $rules[$field] = 'required|file|mimes:jpeg,png,jpg,pdf|max:8192';
            }
        }

        $request->validate($rules);

        // Replace rejected documents
        foreach ($rejectedFields as $field) {
            if ($request->hasFile($field)) {
                // Delete old file
                if ($verification->$field) {
                    Storage::disk(config('filesystems.default', 'public'))->delete($verification->$field);
                }
                // Store new file
                $newPath = $request->file($field)->store('verifications/' . $user->id, config('filesystems.default', 'public'));
                $verification->$field = $newPath;
                $verification->{$field . '_status'} = 'pending';
                $verification->{$field . '_rejection_reason'} = null;
            }
        }

        $verification->status = 'pending';
        $verification->admin_message = null;
        $verification->resubmitted_at = now();
        $verification->resubmission_count = ($verification->resubmission_count ?? 0) + 1;
        $verification->submitted_at = now();
        $verification->save();

        // Marquer les anciennes notifications de vérification comme lues
        $user->unreadNotifications()
            ->where('type', \App\Notifications\VerificationDocumentRejected::class)
            ->update(['read_at' => now()]);

        return redirect()->route('verification.index')->with('success', 'Vos documents corrigés ont été renvoyés avec succès ! Notre équipe les réexaminera dans les plus brefs délais.');
    }

    /**
     * Create Stripe payment session
     */
    public function createPaymentSession(Request $request)
    {
        $request->validate([
            'verification_id' => 'required|exists:identity_verifications,id',
        ]);

        $user = Auth::user();
        $verification = IdentityVerification::where('id', $request->verification_id)
            ->where('user_id', $user->id)
            ->where('payment_status', 'pending')
            ->firstOrFail();

        Stripe::setApiKey(config('services.stripe.secret'));

        $productName = $verification->type === 'profile_verification' 
            ? 'Vérification de profil ProxiPro (10€ / 20 points)'
            : 'Badge Prestataire Vérifié ProxiPro (10€ / 20 points)';

        try {
            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'eur',
                        'product_data' => [
                            'name' => $productName,
                            'description' => 'Frais de vérification pour ' . $user->name,
                        ],
                        'unit_amount' => (int)($verification->payment_amount * 100),
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => route('verification.payment.success', ['id' => $verification->id]) . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('verification.payment.cancel', ['id' => $verification->id]),
                'customer_email' => $user->email,
                'metadata' => [
                    'verification_id' => $verification->id,
                    'user_id' => $user->id,
                    'type' => $verification->type,
                ],
            ]);

            return response()->json([
                'success' => true,
                'checkout_url' => $session->url,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du paiement: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Pay verification with points (20 points)
     */
    public function payWithPoints(Request $request)
    {
        $request->validate([
            'verification_id' => 'required|exists:identity_verifications,id',
        ]);

        $user = Auth::user();
        $verification = IdentityVerification::where('id', $request->verification_id)
            ->where('user_id', $user->id)
            ->where('payment_status', 'pending')
            ->firstOrFail();

        $pointsCost = IdentityVerification::getVerificationPointsCost($verification->type);

        // Check if user has enough points
        if (($user->available_points ?? 0) < $pointsCost) {
            return response()->json([
                'success' => false,
                'message' => 'Points insuffisants. Il vous faut ' . $pointsCost . ' points. Vous avez ' . ($user->available_points ?? 0) . ' points.',
            ], 400);
        }

        // Deduct points
        $user->spendPoints($pointsCost, 'verification_payment', 'Vérification de profil - ' . $verification->type);

        // Move documents to permanent folder
        $this->moveDocumentsToPermanent($verification);

        // Update verification status
        $verification->update([
            'payment_status' => 'paid',
            'payment_id' => 'points_' . $pointsCost,
            'paid_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Paiement par points réussi ! Votre demande de vérification a été envoyée.',
            'new_balance' => $user->fresh()->available_points,
        ]);
    }

    /**
     * Payment success callback
     */
    public function paymentSuccess(Request $request, $id)
    {
        $verification = IdentityVerification::findOrFail($id);
        
        if ($verification->user_id !== Auth::id()) {
            abort(403);
        }

        if ($request->has('session_id')) {
            try {
                Stripe::setApiKey(config('services.stripe.secret'));
                $session = Session::retrieve($request->session_id);
                
                if ($session->payment_status === 'paid') {
                    // Déplacer les documents vers dossier permanent
                    $this->moveDocumentsToPermanent($verification);
                    
                    $verification->update([
                        'payment_status' => 'paid',
                        'payment_id' => $session->payment_intent,
                        'paid_at' => now(),
                    ]);
                }
            } catch (\Exception $e) {
                \Log::error('Erreur vérification paiement: ' . $e->getMessage());
            }
        }

        $message = $verification->type === 'profile_verification'
            ? 'Paiement reçu ! Votre demande de vérification de profil a été envoyée. Vous serez notifié une fois validée.'
            : 'Paiement reçu ! Votre demande de badge Prestataire Particulier Vérifié a été envoyée. Vous serez notifié une fois validée.';

        return redirect()->route('profile.show')->with('success', $message);
    }

    /**
     * Payment cancel callback
     */
    public function paymentCancel($id)
    {
        $verification = IdentityVerification::findOrFail($id);
        
        if ($verification->user_id !== Auth::id()) {
            abort(403);
        }

        // Supprimer les documents temporaires
        Storage::disk(config('filesystems.default', 'public'))->delete($verification->document_front);
        if ($verification->document_back) {
            Storage::disk(config('filesystems.default', 'public'))->delete($verification->document_back);
        }
        Storage::disk(config('filesystems.default', 'public'))->delete($verification->selfie);

        $verification->delete();

        return redirect()->route('profile.show')->with('error', 'Paiement annulé. Votre demande n\'a pas été envoyée.');
    }

    /**
     * Move documents from temp to permanent folder
     */
    private function moveDocumentsToPermanent(IdentityVerification $verification)
    {
        $userId = $verification->user_id;

        if (Storage::disk(config('filesystems.default', 'public'))->exists($verification->document_front)) {
            $newPath = str_replace('verifications-temp/', 'verifications/', $verification->document_front);
            Storage::disk(config('filesystems.default', 'public'))->move($verification->document_front, $newPath);
            $verification->document_front = $newPath;
        }

        if ($verification->document_back && Storage::disk(config('filesystems.default', 'public'))->exists($verification->document_back)) {
            $newPath = str_replace('verifications-temp/', 'verifications/', $verification->document_back);
            Storage::disk(config('filesystems.default', 'public'))->move($verification->document_back, $newPath);
            $verification->document_back = $newPath;
        }

        if (Storage::disk(config('filesystems.default', 'public'))->exists($verification->selfie)) {
            $newPath = str_replace('verifications-temp/', 'verifications/', $verification->selfie);
            Storage::disk(config('filesystems.default', 'public'))->move($verification->selfie, $newPath);
            $verification->selfie = $newPath;
        }

        $verification->save();
    }

    /**
     * Store verification documents (form submit)
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $rules = [
            'document_type' => 'required|in:id_card,passport,driver_license,cni,permis,carte_sejour',
            'document_front' => 'required|file|mimes:jpeg,png,jpg,pdf|max:8192',
            'document_back' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:8192',
            'selfie' => 'required|image|mimes:jpeg,png,jpg|max:8192',
        ];

        // Professional document required for pros
        if (IdentityVerification::requiresProfessionalDocument($user)) {
            $rules['professional_document'] = 'required|file|mimes:jpeg,png,jpg,pdf|max:8192';
            $rules['professional_document_type'] = 'required|in:kbis,sirene';
        }

        $request->validate($rules);

        $existingVerification = IdentityVerification::where('user_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if ($existingVerification) {
            return redirect()->back()->with('error', 'Vous avez déjà une demande de vérification en cours.');
        }

        $documentFront = $request->file('document_front')->store('verifications/' . $user->id, config('filesystems.default', 'public'));
        
        $documentBack = null;
        if ($request->hasFile('document_back')) {
            $documentBack = $request->file('document_back')->store('verifications/' . $user->id, config('filesystems.default', 'public'));
        }
        
        $selfie = $request->file('selfie')->store('verifications/' . $user->id, config('filesystems.default', 'public'));

        $professionalDocument = null;
        $professionalDocumentType = null;
        if ($request->hasFile('professional_document')) {
            $professionalDocument = $request->file('professional_document')->store('verifications/' . $user->id, config('filesystems.default', 'public'));
            $professionalDocumentType = $request->professional_document_type;
        }

        IdentityVerification::create([
            'user_id' => $user->id,
            'type' => 'profile_verification',
            'document_type' => $request->document_type,
            'document_front' => $documentFront,
            'document_front_status' => 'pending',
            'document_back' => $documentBack,
            'document_back_status' => $documentBack ? 'pending' : null,
            'selfie' => $selfie,
            'selfie_status' => 'pending',
            'professional_document' => $professionalDocument,
            'professional_document_type' => $professionalDocumentType,
            'professional_document_status' => $professionalDocument ? 'pending' : null,
            'payment_amount' => IdentityVerification::getVerificationPrice('profile_verification'),
            'payment_status' => 'pending',
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        return redirect()->route('verification.index')->with('success', 'Vos documents de vérification ont été envoyés avec succès ! Ils seront examinés dans les plus brefs délais.');
    }

    /**
     * Cancel pending verification
     */
    public function cancel()
    {
        $user = Auth::user();
        
        $verification = IdentityVerification::where('user_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if ($verification) {
            Storage::disk(config('filesystems.default', 'public'))->delete($verification->document_front);
            if ($verification->document_back) {
                Storage::disk(config('filesystems.default', 'public'))->delete($verification->document_back);
            }
            Storage::disk(config('filesystems.default', 'public'))->delete($verification->selfie);
            
            $verification->delete();
        }

        return redirect()->route('verification.index')->with('success', 'Votre demande de vérification a été annulée.');
    }
}
