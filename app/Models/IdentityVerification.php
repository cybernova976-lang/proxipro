<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IdentityVerification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'document_type',
        'document_front',
        'document_front_status',
        'document_front_rejection_reason',
        'document_back',
        'document_back_status',
        'document_back_rejection_reason',
        'selfie',
        'selfie_status',
        'selfie_rejection_reason',
        'professional_document',
        'professional_document_type',
        'professional_document_status',
        'professional_document_rejection_reason',
        'payment_amount',
        'payment_id',
        'payment_status',
        'paid_at',
        'status',
        'rejection_reason',
        'admin_message',
        'submitted_at',
        'resubmitted_at',
        'resubmission_count',
        'reviewed_at',
        'reviewed_by',
    ];

    protected $casts = [
        'payment_amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'submitted_at' => 'datetime',
        'resubmitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(IdentityVerificationDocument::class, 'identity_verification_id');
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isApproved()
    {
        return $this->status === 'approved';
    }

    public function isRejected()
    {
        return $this->status === 'rejected';
    }

    public function isReturned()
    {
        return $this->status === 'returned';
    }

    public function isResubmission(): bool
    {
        return $this->resubmission_count > 0;
    }

    public function isPaid()
    {
        return $this->payment_status === 'paid';
    }

    /**
     * Demande sans frais. La vérification de profil est gratuite depuis le
     * 20/11/2026 : une demande gratuite porte un montant nul et le statut de
     * paiement « paid » (rien à régler), ce qui la place dans la file de l'administration.
     */
    public function isFree(): bool
    {
        return (float) $this->payment_amount === 0.0;
    }

    /**
     * Check if any document was rejected
     */
    public function hasRejectedDocuments(): bool
    {
        // Le selfie n'est plus demandé (20/11/2026) : il ne bloque plus aucune demande.
        return $this->document_front_status === 'rejected'
            || $this->document_back_status === 'rejected'
            || $this->professional_document_status === 'rejected';
    }

    /**
     * Check if all documents are approved
     */
    public function allDocumentsApproved(): bool
    {
        // Le selfie n'est plus demandé (20/11/2026) : il ne conditionne plus rien.
        $allGood = $this->document_front_status === 'approved';
        
        if ($this->document_back) {
            $allGood = $allGood && $this->document_back_status === 'approved';
        }
        
        if ($this->professional_document) {
            $allGood = $allGood && $this->professional_document_status === 'approved';
        }
        
        return $allGood;
    }

    /**
     * Get rejected documents list
     */
    public function getRejectedDocuments(): array
    {
        $rejected = [];
        $labels = [
            'document_front' => 'Recto du document d\'identité',
            'document_back' => 'Verso du document d\'identité',
            'professional_document' => $this->getProfessionalDocumentLabel(),
        ];
        
        foreach (['document_front', 'document_back', 'professional_document'] as $field) {
            if ($this->{$field . '_status'} === 'rejected') {
                $rejected[] = [
                    'field' => $field,
                    'label' => $labels[$field],
                    'reason' => $this->{$field . '_rejection_reason'},
                ];
            }
        }
        
        return $rejected;
    }

    /**
     * Get label for professional document type
     */
    public function getProfessionalDocumentLabel(): string
    {
        return match($this->professional_document_type) {
            'kbis' => 'Extrait Kbis',
            'sirene' => 'Avis de situation SIRENE',
            default => 'Document professionnel',
        };
    }

    /**
     * Check if professional document is required for this user
     */
    public static function requiresProfessionalDocument($user): bool
    {
        return $user->isEntreprise() || $user->isAutoEntrepreneur();
    }

    /**
     * Get the type of professional document needed
     */
    public static function getRequiredProfessionalDocumentType($user): ?string
    {
        if ($user->isEntreprise()) {
            return 'kbis';
        } elseif ($user->isAutoEntrepreneur()) {
            return 'sirene';
        }
        return null;
    }

    /**
     * Prix de la vérification, en euros : gratuite depuis le 20/11/2026
     * (décision du fondateur). Aucun paiement n'est demandé ni proposé.
     */
    public static function getVerificationPrice($type)
    {
        return 0.00;
    }

    // Coût en points pour la vérification
    public static function getVerificationPointsCost($type)
    {
        return null;
    }

    /**
     * La vérification de profil est gratuite ; le programme de points est supprimé.
     */
    public static function supportsPointsPayment($type): bool
    {
        return false;
    }
}
