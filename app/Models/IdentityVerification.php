<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
     * Check if any document was rejected
     */
    public function hasRejectedDocuments(): bool
    {
        return $this->document_front_status === 'rejected'
            || $this->document_back_status === 'rejected'
            || $this->selfie_status === 'rejected'
            || $this->professional_document_status === 'rejected';
    }

    /**
     * Check if all documents are approved
     */
    public function allDocumentsApproved(): bool
    {
        $allGood = $this->document_front_status === 'approved'
            && $this->selfie_status === 'approved';
        
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
            'selfie' => 'Selfie de vérification',
            'professional_document' => $this->getProfessionalDocumentLabel(),
        ];
        
        foreach (['document_front', 'document_back', 'selfie', 'professional_document'] as $field) {
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
        return $user->isProfessionnel();
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

    // Prix de vérification selon le type (en points et en euros)
    public static function getVerificationPrice($type)
    {
        return match($type) {
            'profile_verification' => 5.00,
            'service_provider' => 10.00,
            default => 5.00,
        };
    }

    // Coût en points pour la vérification
    public static function getVerificationPointsCost($type)
    {
        return match($type) {
            'profile_verification' => 10,
            'service_provider' => 20,
            default => 10,
        };
    }
}
