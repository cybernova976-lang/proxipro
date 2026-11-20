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
        'sent_to_email', 'reminder_enabled', 'reminder_sent_at',
        'public_token', 'response_name', 'response_channel',
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
        'reminder_enabled' => 'boolean',
        'reminder_sent_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function client()
    {
        return $this->belongsTo(ProClient::class, 'pro_client_id');
    }

    /** Facture du devis (hors acompte). Une facture annulée par un avoir ne compte plus. */
    public function invoice()
    {
        return $this->hasOne(ProInvoice::class, 'quote_id')
            ->where('kind', ProInvoice::KIND_INVOICE)->where('status', '!=', 'cancelled');
    }

    /** Factures d'acompte du devis, hors acomptes annulés par un avoir. */
    public function depositInvoices()
    {
        return $this->hasMany(ProInvoice::class, 'quote_id')
            ->where('kind', ProInvoice::KIND_DEPOSIT)->where('status', '!=', 'cancelled');
    }

    public static function generateNumber($userId): string
    {
        return \App\Support\ProDocumentNumber::next((int) $userId, 'quote');
    }

    public function isEditable(): bool
    {
        return $this->status === 'draft' && $this->issued_at === null;
    }

    /** Le client peut encore répondre : devis envoyé, validité non dépassée. */
    public function isAwaitingResponse(): bool
    {
        return $this->status === 'sent'
            && ($this->valid_until === null || $this->valid_until->copy()->endOfDay()->isFuture());
    }

    /**
     * Adresse personnelle où le client lit le devis et y répond, sans compte.
     * Le jeton est créé au premier besoin et ne change plus ; un brouillon n'en a pas.
     */
    public function responseUrl(): ?string
    {
        if ($this->status === 'draft' || $this->issued_at === null) {
            return null;
        }
        if (! $this->public_token) {
            $this->forceFill(['public_token' => \Illuminate\Support\Str::random(40)])->saveQuietly();
        }

        return route('quotes.respond', $this->public_token);
    }

    /**
     * « Accepté en ligne par Jeanne Martin le 03/12/2026 à 14:05 (heure de Paris) »,
     * ou null sans réponse en ligne. Les dates sont enregistrées en UTC : l'heure
     * est donnée dans un fuseau nommé, pour que l'horodatage se lise sans ambiguïté.
     */
    public function onlineResponseLabel(): ?string
    {
        $at = $this->status === 'accepted' ? $this->accepted_at : ($this->status === 'refused' ? $this->refused_at : null);
        if ($this->response_channel !== 'online' || ! $at || ! $this->response_name) {
            return null;
        }

        $at = $at->copy()->timezone('Europe/Paris');

        return ($this->status === 'accepted' ? 'Accepté' : 'Refusé').' en ligne par '.$this->response_name
            .' le '.$at->format('d/m/Y').' à '.$at->format('H:i').' (heure de Paris)';
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
