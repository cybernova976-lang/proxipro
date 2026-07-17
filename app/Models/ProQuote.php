<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProQuote extends Model
{
    protected $fillable = [
        'user_id', 'client_id', 'pro_client_id', 'quote_number', 'client_name', 'client_email',
        'client_phone', 'client_address', 'subject', 'description', 'items',
        'subtotal', 'tax_rate', 'tax_amount', 'total', 'status',
        'valid_until', 'notes', 'conditions', 'client_company',
        'client_registration_number', 'client_vat_number', 'operation_type',
        'execution_location', 'currency', 'is_free', 'deposit_percentage',
        'seller_snapshot', 'issued_at', 'sent_at', 'accepted_at', 'refused_at',
    ];

    protected $casts = [
        'items' => 'array',
        'subtotal' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'valid_until' => 'date',
        'is_free' => 'boolean',
        'deposit_percentage' => 'decimal:2',
        'seller_snapshot' => 'array',
        'issued_at' => 'datetime',
        'sent_at' => 'datetime',
        'accepted_at' => 'datetime',
        'refused_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function client()
    {
        return $this->belongsTo(ProClient::class, 'pro_client_id');
    }

    public function invoice()
    {
        return $this->hasOne(ProInvoice::class, 'quote_id');
    }

    public static function generateNumber($userId): string
    {
        return \App\Support\ProDocumentNumber::next((int) $userId, 'quote');
    }

    public function isEditable(): bool
    {
        return $this->status === 'draft' && $this->issued_at === null;
    }

    public function getStatusBadgeClass(): string
    {
        return match ($this->status) {
            'draft' => 'bg-secondary',
            'sent' => 'bg-primary',
            'accepted' => 'bg-success',
            'refused', 'rejected' => 'bg-danger',
            'expired' => 'bg-warning',
            default => 'bg-secondary',
        };
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'draft' => 'Brouillon',
            'sent' => 'Envoyé',
            'accepted' => 'Accepté',
            'refused', 'rejected' => 'Refusé',
            'expired' => 'Expiré',
            default => 'Inconnu',
        };
    }

    public function getStatusColor(): string
    {
        return match ($this->status) {
            'draft' => 'secondary',
            'sent', 'pending' => 'warning',
            'accepted' => 'success',
            'refused', 'rejected' => 'danger',
            'expired' => 'secondary',
            default => 'secondary',
        };
    }
}
