<?php

namespace App\Services;

use App\Models\IdentityVerification;
use App\Models\IdentityVerificationDocument;
use App\Models\Transaction;
use App\Support\StripeSessionData;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class IdentityVerificationPaymentService
{
    /**
     * @return array{status: string, verification: IdentityVerification}
     */
    public function fulfill(
        object|array $session,
        ?int $expectedUserId = null,
        ?int $expectedVerificationId = null,
    ): array {
        $sessionId = StripeSessionData::id($session);
        $metadata = StripeSessionData::metadata($session);
        $verificationId = (int) ($metadata['verification_id'] ?? 0);
        $userId = (int) ($metadata['user_id'] ?? 0);

        if ($sessionId === '' || ! StripeSessionData::isPaid($session)) {
            throw new DomainException('Le paiement Stripe n’est pas confirmé.');
        }
        if ($verificationId < 1 || $userId < 1
            || ($expectedVerificationId !== null && $expectedVerificationId !== $verificationId)
            || ($expectedUserId !== null && $expectedUserId !== $userId)) {
            throw new DomainException('La demande de vérification ou son bénéficiaire est invalide.');
        }

        $verification = IdentityVerification::find($verificationId);
        if (! $verification || (int) $verification->user_id !== $userId) {
            throw new DomainException('La demande de vérification est introuvable.');
        }

        $expectedAmount = (int) round(IdentityVerification::getVerificationPrice($verification->type) * 100);
        $metadataAmount = (int) ($metadata['expected_amount_cents'] ?? $expectedAmount);
        if ($metadataAmount !== $expectedAmount
            || StripeSessionData::amountTotal($session) !== $expectedAmount
            || StripeSessionData::currency($session) !== 'eur'
            || (string) StripeSessionData::value($session, 'client_reference_id', '') !== (string) $verificationId) {
            throw new DomainException('Le montant, la devise ou la référence de vérification est invalide.');
        }

        $processed = DB::transaction(function () use ($session, $sessionId, $verificationId, $userId, $expectedAmount) {
            $verification = IdentityVerification::query()->lockForUpdate()->findOrFail($verificationId);
            $paymentIntent = (string) StripeSessionData::value($session, 'payment_intent', '');

            if ($verification->payment_status === 'paid'
                && $verification->payment_id
                && $verification->payment_id !== $paymentIntent) {
                throw new DomainException('Cette demande a déjà été réglée par un autre paiement.');
            }

            $transaction = Transaction::firstOrCreate(
                ['stripe_session_id' => $sessionId],
                [
                    'user_id' => $userId,
                    'amount' => $expectedAmount / 100,
                    'type' => 'IDENTITY_VERIFICATION',
                    'description' => 'Vérification de profil #'.$verification->id,
                    'status' => 'completed',
                    'metadata' => [
                        'verification_id' => $verification->id,
                        'verification_type' => $verification->type,
                        'payment_intent' => $paymentIntent ?: null,
                    ],
                ]
            );

            if (! $transaction->wasRecentlyCreated) {
                return false;
            }

            if ($verification->payment_status !== 'paid') {
                $this->moveDocumentsToPermanent($verification);
                $verification->forceFill([
                    'payment_status' => 'paid',
                    'payment_id' => $paymentIntent ?: $sessionId,
                    'paid_at' => now(),
                    'status' => 'pending',
                    'submitted_at' => now(),
                ])->save();
            }

            return true;
        });

        return [
            'status' => $processed ? 'processed' : 'duplicate',
            'verification' => IdentityVerification::findOrFail($verificationId),
        ];
    }

    public function moveDocumentsToPermanent(IdentityVerification $verification): void
    {
        $fields = ['document_front', 'document_back', 'selfie', 'professional_document'];
        $paths = array_filter(array_map(fn (string $field) => $verification->{$field}, $fields));

        if ($paths !== [] && collect($paths)->every(
            fn (string $path) => IdentityVerificationDocument::isDatabasePath($path)
        )) {
            return;
        }

        $disk = Storage::disk(config('filesystems.default', 'public'));
        foreach ($fields as $field) {
            $oldPath = $verification->{$field};
            if (! $oldPath || IdentityVerificationDocument::isDatabasePath($oldPath)) {
                continue;
            }

            $newPath = str_replace('verifications-temp/', 'verifications/', $oldPath);
            if ($disk->exists($oldPath)) {
                $disk->move($oldPath, $newPath);
                $verification->{$field} = $newPath;
            } elseif ($newPath !== $oldPath && $disk->exists($newPath)) {
                // Reprise sûre après une interruption survenue entre le déplacement
                // du fichier et la validation de la transaction en base.
                $verification->{$field} = $newPath;
            }
        }

        $verification->save();
    }
}
