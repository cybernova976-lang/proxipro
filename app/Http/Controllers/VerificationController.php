<?php

namespace App\Http\Controllers;

use App\Models\IdentityVerification;
use App\Models\IdentityVerificationDocument;
use App\Models\User;
use App\Services\IdentityVerificationPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Throwable;

class VerificationController extends Controller
{
    public function __construct(private readonly IdentityVerificationPaymentService $verificationPayments)
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
        return $user->verificationProfileMissingFields();
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
            ->whereIn('status', ['awaiting_payment', 'pending'])
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
            'profile_verification_points' => null,
            'profile_verification_payment_methods' => ['card'],
            'service_provider_price' => IdentityVerification::getVerificationPrice('service_provider'),
            'service_provider_points' => IdentityVerification::getVerificationPointsCost('service_provider'),
            'user_points' => $user->available_points ?? 0,
        ]);
    }

    public function showDocument(string $document)
    {
        $storedDocument = IdentityVerificationDocument::findOrFail($document);
        $user = Auth::user();

        abort_unless($storedDocument->user_id === $user->id || $user->isAdmin(), 403);

        $content = base64_decode($storedDocument->content, true);
        abort_if($content === false, 404);

        $fileName = str_replace(['"', "\r", "\n"], '', $storedDocument->original_name);

        return response($content, 200, [
            'Content-Type' => $storedDocument->mime_type,
            'Content-Length' => (string) strlen($content),
            'Content-Disposition' => 'inline; filename="'.$fileName.'"',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Store verification documents (AJAX)
     */
    public function storeAjax(Request $request)
    {
        $user = Auth::user();
        $missingFields = $this->getProfileMissingFields($user);

        if ($missingFields !== []) {
            return response()->json([
                'success' => false,
                'message' => 'Complétez toutes les informations de votre profil avant de demander sa vérification.',
                'missing_profile_fields' => array_column($missingFields, 'label'),
                'redirect' => route('profile.edit'),
            ], 422);
        }

        $files = $this->validateVerificationSubmission($request, $user, includeType: true);

        // Vérifier si une demande est déjà en cours
        $existingVerification = IdentityVerification::where('user_id', $user->id)
            ->where('type', $request->type)
            ->whereIn('status', ['awaiting_payment', 'pending'])
            ->first();

        if ($existingVerification) {
            return response()->json([
                'success' => false,
                'message' => 'Vous avez déjà une demande de vérification en cours.',
            ], 400);
        }

        // Pour service_provider, vérifier que le profil est vérifié
        if ($request->type === 'service_provider' && ! $user->is_verified) {
            return response()->json([
                'success' => false,
                'message' => 'Vous devez d\'abord vérifier votre profil.',
                'need_profile_verification' => true,
            ], 400);
        }

        $price = IdentityVerification::getVerificationPrice($request->type);
        try {
            $verification = $this->createAwaitingPaymentVerification(
                $user,
                $request->type,
                $request->document_type,
                $files,
                $price
            );
        } catch (Throwable $e) {
            Log::error('Unable to prepare identity verification.', [
                'user_id' => $user->id,
                'storage' => 'database',
                'exception' => $e::class,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Impossible d’enregistrer vos documents. Veuillez réessayer.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'verification_id' => $verification->id,
            'message' => 'Vos documents ont bien été enregistrés.',
            'price' => $price,
        ]);
    }

    /**
     * Resubmit corrected documents on a returned verification
     */
    public function resubmit(Request $request)
    {
        $user = Auth::user();
        $missingFields = $this->getProfileMissingFields($user);

        if ($missingFields !== []) {
            return redirect()->route('verification.index')
                ->with('error', 'Complétez toutes les informations de votre profil avant de renvoyer votre demande.');
        }

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

        DB::transaction(function () use ($request, $rejectedFields, $verification, $user) {
            foreach ($rejectedFields as $field) {
                if (! $request->hasFile($field)) {
                    continue;
                }

                $this->deleteVerificationPath($verification->$field);
                $prepared = $this->prepareDatabaseDocument($request->file($field), $field);
                $verification->$field = $prepared['path'];
                $verification->{$field.'_status'} = 'pending';
                $verification->{$field.'_rejection_reason'} = null;
                $this->createDatabaseDocument($verification, $user->id, $field, $prepared);
            }

            $verification->status = 'pending';
            $verification->admin_message = null;
            $verification->resubmitted_at = now();
            $verification->resubmission_count = ($verification->resubmission_count ?? 0) + 1;
            $verification->submitted_at = now();
            $verification->save();
        });

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
        $missingFields = $this->getProfileMissingFields($user);

        if ($missingFields !== []) {
            return response()->json([
                'success' => false,
                'message' => 'Votre profil doit être entièrement complété avant le paiement et l’envoi de la demande.',
                'missing_profile_fields' => array_column($missingFields, 'label'),
                'redirect' => route('profile.edit'),
            ], 422);
        }

        $verification = IdentityVerification::where('id', $request->verification_id)
            ->where('user_id', $user->id)
            ->where('payment_status', 'pending')
            ->firstOrFail();

        $stripeSecret = config('services.stripe.secret');
        if (! $stripeSecret) {
            return response()->json([
                'success' => false,
                'message' => 'Le paiement est momentanément indisponible. Veuillez réessayer plus tard.',
            ], 503);
        }

        Stripe::setApiKey($stripeSecret);

        $amount = IdentityVerification::getVerificationPrice($verification->type);
        if ((float) $verification->payment_amount !== (float) $amount) {
            $verification->update(['payment_amount' => $amount]);
        }

        $priceLabel = number_format((float) $amount, 2, ',', ' ').'€';
        $pointsLabel = IdentityVerification::getVerificationPointsCost($verification->type).' points';
        $productName = $verification->type === 'profile_verification'
            ? "Vérification de profil Prokejem ({$priceLabel})"
            : "Badge Prestataire Vérifié Prokejem ({$priceLabel} / {$pointsLabel})";

        try {
            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'eur',
                        'product_data' => [
                            'name' => $productName,
                            'description' => 'Frais de vérification pour '.$user->name,
                        ],
                        'unit_amount' => (int) round($amount * 100),
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => route('verification.payment.success', ['id' => $verification->id]).'?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('verification.payment.cancel', ['id' => $verification->id]),
                'customer_email' => $user->email,
                'client_reference_id' => (string) $verification->id,
                'metadata' => [
                    'verification_id' => $verification->id,
                    'user_id' => $user->id,
                    'type' => 'identity_verification',
                    'verification_type' => $verification->type,
                    'expected_amount_cents' => (int) round($amount * 100),
                    'expected_currency' => 'eur',
                ],
            ]);

            return response()->json([
                'success' => true,
                'checkout_url' => $session->url,
            ]);
        } catch (\Exception $e) {
            Log::error('Unable to create verification Stripe checkout.', [
                'verification_id' => $verification->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Impossible d’ouvrir le paiement sécurisé. Veuillez réessayer.',
            ], 500);
        }
    }

    /**
     * Pay eligible verification types with points.
     */
    public function payWithPoints(Request $request)
    {
        $request->validate([
            'verification_id' => 'required|exists:identity_verifications,id',
        ]);

        $user = Auth::user();
        $missingFields = $this->getProfileMissingFields($user);

        if ($missingFields !== []) {
            return response()->json([
                'success' => false,
                'message' => 'Votre profil doit être entièrement complété avant l’envoi de la demande.',
                'missing_profile_fields' => array_column($missingFields, 'label'),
                'redirect' => route('profile.edit'),
            ], 422);
        }

        $verification = IdentityVerification::where('id', $request->verification_id)
            ->where('user_id', $user->id)
            ->where('payment_status', 'pending')
            ->firstOrFail();

        if (! IdentityVerification::supportsPointsPayment($verification->type)) {
            return response()->json([
                'success' => false,
                'message' => 'La vérification de profil nécessite un paiement sécurisé de 5 € par carte.',
            ], 422);
        }

        $pointsCost = IdentityVerification::getVerificationPointsCost($verification->type);

        try {
            $newBalance = DB::transaction(function () use ($user, $verification, $pointsCost) {
                $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
                $lockedVerification = IdentityVerification::query()->lockForUpdate()->findOrFail($verification->id);

                if ($lockedVerification->payment_status !== 'pending') {
                    throw ValidationException::withMessages(['verification_id' => 'Cette demande a déjà été réglée.']);
                }
                if (($lockedUser->available_points ?? 0) < $pointsCost
                    || ! $lockedUser->spendPoints($pointsCost, 'verification_payment', 'Vérification de profil - '.$lockedVerification->type)) {
                    throw ValidationException::withMessages([
                        'points' => 'Points insuffisants. Il vous faut '.$pointsCost.' points.',
                    ]);
                }

                $this->verificationPayments->moveDocumentsToPermanent($lockedVerification);
                $lockedVerification->forceFill([
                    'payment_status' => 'paid',
                    'payment_id' => 'points_'.$lockedVerification->id,
                    'paid_at' => now(),
                    'status' => 'pending',
                    'submitted_at' => now(),
                ])->save();

                return (int) $lockedUser->fresh()->available_points;
            });
        } catch (ValidationException $exception) {
            return response()->json([
                'success' => false,
                'message' => collect($exception->errors())->flatten()->first(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Paiement par points réussi ! Votre demande de vérification a été envoyée.',
            'new_balance' => $newBalance,
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
                $stripeSecret = config('services.stripe.secret');
                if (! $stripeSecret) {
                    throw new \RuntimeException('Stripe is not configured.');
                }

                Stripe::setApiKey($stripeSecret);
                $session = Session::retrieve($request->session_id);
                $this->verificationPayments->fulfill($session, Auth::id(), $verification->id);
            } catch (\Throwable $e) {
                Log::warning('Identity verification Stripe confirmation refused.', [
                    'verification_id' => $verification->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $verification->refresh();
        if ($verification->payment_status !== 'paid') {
            return redirect()->route('profile.show')->with('error', 'Le paiement n\'a pas pu être validé. Votre demande n\'a pas été envoyée à l\'administration.');
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

        if ($verification->payment_status === 'paid') {
            return redirect()->route('profile.show')
                ->with('error', 'Cette demande a déjà été payée et transmise à l’administration.');
        }

        return redirect()->route('verification.index')
            ->with('error', 'Paiement annulé. Vos documents sont conservés afin que vous puissiez reprendre le paiement.');
    }

    /**
     * Store verification documents (form submit)
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        // Check profile completeness first
        $missingFields = $this->getProfileMissingFields($user);
        if (! empty($missingFields)) {
            return redirect()->back()->with(
                'error',
                'Complétez toutes les informations de votre profil avant de soumettre votre vérification.'
            );
        }

        $files = $this->validateVerificationSubmission($request, $user);

        $existingVerification = IdentityVerification::where('user_id', $user->id)
            ->whereIn('status', ['awaiting_payment', 'pending'])
            ->first();

        if ($existingVerification) {
            return redirect()->back()->with('error', 'Vous avez déjà une demande de vérification en cours.');
        }

        try {
            $this->createAwaitingPaymentVerification(
                $user,
                'profile_verification',
                $request->document_type,
                $files,
                IdentityVerification::getVerificationPrice('profile_verification')
            );
        } catch (Throwable $e) {
            Log::error('Unable to prepare identity verification.', [
                'user_id' => $user->id,
                'storage' => 'database',
                'exception' => $e::class,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Impossible d’enregistrer vos documents. Veuillez réessayer.');
        }

        return redirect()->route('verification.index')->with('success', 'Vos documents ont bien été enregistrés.');
    }

    /**
     * Cancel pending verification
     */
    public function cancel()
    {
        $user = Auth::user();

        $verification = IdentityVerification::where('user_id', $user->id)
            ->whereIn('status', ['awaiting_payment', 'pending'])
            ->first();

        if ($verification) {
            if ($verification->payment_status === 'paid') {
                return redirect()->route('verification.index')
                    ->with('error', 'Une demande payée et transmise ne peut plus être annulée.');
            }

            $this->deleteVerificationFiles($verification);
            $verification->delete();
        }

        return redirect()->route('verification.index')->with('success', 'Votre demande de vérification a été annulée.');
    }

    private function validateVerificationSubmission(Request $request, $user, bool $includeType = false): array
    {
        $imageOrPdf = 'nullable|file|mimes:jpeg,png,jpg,pdf,webp,heic,heif|max:15360';
        $image = 'nullable|file|mimes:jpeg,png,jpg,webp,heic,heif|max:15360';
        $rules = [
            'document_type' => 'required|in:id_card,passport,driver_license,cni,permis,carte_sejour',
            'document_front' => $imageOrPdf,
            'document_front_camera' => $image,
            'document_back' => $imageOrPdf,
            'document_back_camera' => $image,
            'selfie' => $image,
            'selfie_camera' => $image,
        ];

        if ($includeType) {
            $rules['type'] = 'required|in:profile_verification,service_provider';
        }

        if (IdentityVerification::requiresProfessionalDocument($user)) {
            $rules['professional_document'] = 'required|file|mimes:jpeg,png,jpg,pdf,webp|max:15360';
            $rules['professional_document_type'] = 'required|in:kbis,sirene';
        }

        $request->validate($rules, [
            '*.mimes' => 'Le fichier doit être une image compatible ou un PDF.',
            '*.max' => 'Chaque fichier ne doit pas dépasser 15 Mo.',
            'professional_document.required' => 'Le justificatif de votre entreprise est obligatoire.',
        ]);

        $front = $request->file('document_front') ?: $request->file('document_front_camera');
        $back = $request->document_type === 'passport'
            ? null
            : ($request->file('document_back') ?: $request->file('document_back_camera'));
        $selfie = $request->file('selfie') ?: $request->file('selfie_camera');

        $errors = [];
        if (! $front) {
            $errors['document_front'] = 'Ajoutez la page d’identité ou le recto de votre document.';
        }
        if (! $selfie) {
            $errors['selfie'] = 'Ajoutez une photo de vous tenant votre document.';
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        return [
            'document_front' => $front,
            'document_back' => $back,
            'selfie' => $selfie,
            'professional_document' => $request->file('professional_document'),
            'professional_document_type' => IdentityVerification::getRequiredProfessionalDocumentType($user),
        ];
    }

    private function createAwaitingPaymentVerification($user, string $type, string $documentType, array $files, float $price): IdentityVerification
    {
        $preparedDocuments = [];
        foreach (['document_front', 'document_back', 'selfie', 'professional_document'] as $field) {
            if ($files[$field]) {
                $preparedDocuments[$field] = $this->prepareDatabaseDocument($files[$field], $field);
            }
        }

        return DB::transaction(function () use ($user, $type, $documentType, $files, $price, $preparedDocuments) {
            $verification = IdentityVerification::create([
                'user_id' => $user->id,
                'type' => $type,
                'document_type' => $documentType,
                'document_front' => $preparedDocuments['document_front']['path'],
                'document_front_status' => 'pending',
                'document_back' => $preparedDocuments['document_back']['path'] ?? null,
                'document_back_status' => 'pending',
                'selfie' => $preparedDocuments['selfie']['path'],
                'selfie_status' => 'pending',
                'professional_document' => $preparedDocuments['professional_document']['path'] ?? null,
                'professional_document_type' => $files['professional_document_type'],
                'professional_document_status' => 'pending',
                'payment_amount' => $price,
                'payment_status' => 'pending',
                'status' => 'awaiting_payment',
                'submitted_at' => null,
            ]);

            foreach ($preparedDocuments as $field => $prepared) {
                $this->createDatabaseDocument($verification, $user->id, $field, $prepared);
            }

            return $verification;
        });
    }

    private function prepareDatabaseDocument($file, string $field): array
    {
        $content = file_get_contents($file->getRealPath());
        if ($content === false) {
            throw new \RuntimeException("Unable to read {$field}.");
        }

        $extension = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'bin');
        $id = (string) Str::uuid();

        return [
            'id' => $id,
            'path' => IdentityVerificationDocument::path($id, $extension),
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
            'extension' => $extension,
            'size' => strlen($content),
            'content' => base64_encode($content),
        ];
    }

    private function createDatabaseDocument(
        IdentityVerification $verification,
        int $userId,
        string $field,
        array $prepared
    ): void {
        IdentityVerificationDocument::create([
            'id' => $prepared['id'],
            'identity_verification_id' => $verification->id,
            'user_id' => $userId,
            'field' => $field,
            'original_name' => $prepared['original_name'],
            'mime_type' => $prepared['mime_type'],
            'extension' => $prepared['extension'],
            'size' => $prepared['size'],
            'content' => $prepared['content'],
        ]);
    }

    private function deleteVerificationFiles(IdentityVerification $verification): void
    {
        foreach (['document_front', 'document_back', 'selfie', 'professional_document'] as $field) {
            $this->deleteVerificationPath($verification->$field);
        }
    }

    private function deleteVerificationPath(?string $path): void
    {
        $databaseId = IdentityVerificationDocument::idFromPath($path);
        if ($databaseId) {
            IdentityVerificationDocument::whereKey($databaseId)->delete();

            return;
        }

        if ($path) {
            Storage::disk(config('filesystems.default', 'public'))->delete($path);
        }
    }
}
