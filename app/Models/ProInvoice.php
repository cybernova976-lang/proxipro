<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProInvoice extends Model
{
    public const KIND_INVOICE = 'invoice';

    public const KIND_DEPOSIT = 'deposit';

    public const KIND_CREDIT_NOTE = 'credit_note';

    /** Statut d'un avoir émis : il n'est ni « envoyé », ni « payé », ni « en retard ». */
    public const STATUS_ISSUED = 'issued';

    /**
     * Montant réellement dû ou encaissé d'une facture : son total, moins les
     * avoirs émis sur elle, moins les acomptes déjà facturés. Toute somme de
     * factures (chiffre d'affaires, en attente) doit porter sur cette expression,
     * sinon un acompte et sa facture de solde seraient comptés deux fois.
     */
    public const NET_TOTAL_SQL = '(total - credited_total - deposit_deducted)';

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
        'sent_to_email', 'reminder_enabled', 'reminder_sent_at',
        'kind', 'parent_invoice_id', 'credit_reason', 'credited_total',
        'deposit_deducted', 'deposit_percentage',
    ];

    protected $attributes = [
        'kind' => self::KIND_INVOICE,
        'credited_total' => 0,
        'deposit_deducted' => 0,
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
        'reminder_enabled' => 'boolean',
        'reminder_sent_at' => 'datetime',
        'credited_total' => 'decimal:2',
        'deposit_deducted' => 'decimal:2',
        'deposit_percentage' => 'decimal:2',
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

    /** Pour un avoir : la facture qu'il corrige. */
    public function parentInvoice()
    {
        return $this->belongsTo(self::class, 'parent_invoice_id');
    }

    /** Avoirs émis sur cette facture. */
    public function creditNotes()
    {
        return $this->hasMany(self::class, 'parent_invoice_id')->where('kind', self::KIND_CREDIT_NOTE);
    }

    public static function generateNumber($userId): string
    {
        return \App\Support\ProDocumentNumber::next((int) $userId, 'invoice');
    }

    public function isCreditNote(): bool
    {
        return $this->kind === self::KIND_CREDIT_NOTE;
    }

    public function isDeposit(): bool
    {
        return $this->kind === self::KIND_DEPOSIT;
    }

    /** « Facture », « Facture d'acompte » ou « Avoir ». */
    public function documentLabel(): string
    {
        return match ($this->kind) {
            self::KIND_CREDIT_NOTE => 'Avoir',
            self::KIND_DEPOSIT => 'Facture d’acompte',
            default => 'Facture',
        };
    }

    /** Document émis : numéroté et figé. */
    public function isIssued(): bool
    {
        return $this->finalized_at !== null && $this->status !== 'draft';
    }

    /** Montant TTC encore dû ou encaissé : total, moins avoirs, moins acomptes déduits. */
    public function netTotal(): float
    {
        return round((float) $this->total - (float) $this->credited_total - (float) $this->deposit_deducted, 2);
    }

    /** Part TTC de la facture qu'un avoir peut encore annuler. */
    public function creditableTotal(): float
    {
        return round((float) $this->total - (float) $this->credited_total, 2);
    }

    /** Une facture émise, non annulée, peut recevoir un avoir tant qu'il reste un montant à annuler. */
    public function canBeCredited(): bool
    {
        return ! $this->isCreditNote()
            && in_array($this->status, ['sent', 'overdue', 'paid'], true)
            && $this->creditableTotal() > 0;
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
            self::STATUS_ISSUED => 'bg-primary',
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
            'cancelled' => (float) $this->credited_total > 0 ? 'Annulée par avoir' : 'Annulée',
            self::STATUS_ISSUED => 'Émis',
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
            self::STATUS_ISSUED => 'info',
            default => 'secondary',
        };
    }
}
