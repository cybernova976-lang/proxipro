<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProInvoice extends Model
{
    protected $fillable = [
        'user_id', 'client_id', 'pro_client_id', 'quote_id', 'invoice_number', 'client_name',
        'client_email', 'client_phone', 'client_address', 'subject', 'description',
        'items', 'subtotal', 'tax_rate', 'tax_amount', 'total', 'status',
        'due_date', 'paid_at', 'payment_method', 'notes', 'payment_terms',
        'client_company', 'client_registration_number', 'client_vat_number',
        'client_type', 'operation_type', 'service_date', 'purchase_order_number',
        'delivery_address', 'currency', 'vat_exemption_reason',
        'early_payment_discount', 'late_penalty_rate', 'seller_snapshot',
        'finalized_at', 'sent_at',
    ];

    protected $casts = [
        'items' => 'array',
        'subtotal' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'due_date' => 'date',
        'paid_at' => 'date',
        'service_date' => 'date',
        'late_penalty_rate' => 'decimal:2',
        'seller_snapshot' => 'array',
        'finalized_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function client()
    {
        return $this->belongsTo(ProClient::class, 'pro_client_id');
    }

    public function quote()
    {
        return $this->belongsTo(ProQuote::class, 'quote_id');
    }

    public static function generateNumber($userId): string
    {
        return \App\Support\ProDocumentNumber::next((int) $userId, 'invoice');
    }

    public function isEditable(): bool
    {
        return $this->status === 'draft' && $this->finalized_at === null;
    }

    public function getStatusBadgeClass(): string
    {
        return match ($this->status) {
            'draft' => 'bg-secondary',
            'sent' => 'bg-primary',
            'paid' => 'bg-success',
            'overdue' => 'bg-danger',
            'cancelled' => 'bg-warning',
            default => 'bg-secondary',
        };
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'draft' => 'Brouillon',
            'sent' => 'Envoyée',
            'paid' => 'Payée',
            'overdue' => 'En retard',
            'cancelled' => 'Annulée',
            default => 'Inconnu',
        };
    }

    public function isOverdue(): bool
    {
        return $this->status !== 'paid' && $this->due_date && $this->due_date->isPast();
    }

    public function getStatusColor(): string
    {
        return match ($this->status) {
            'draft' => 'secondary',
            'sent' => 'warning',
            'paid' => 'success',
            'overdue' => 'danger',
            'cancelled' => 'secondary',
            default => 'secondary',
        };
    }
}
