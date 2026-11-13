<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Modèle de devis réutilisable (avantage Premium) : jamais de données client. */
class ProQuoteTemplate extends Model
{
    /** Nombre maximal de modèles par prestataire. */
    public const LIMIT = 20;

    protected $fillable = [
        'user_id', 'name', 'subject', 'operation_type', 'items', 'tax_rate',
        'deposit_percentage', 'is_free', 'notes', 'conditions',
    ];

    protected function casts(): array
    {
        return [
            'items' => 'array',
            'tax_rate' => 'decimal:2',
            'deposit_percentage' => 'decimal:2',
            'is_free' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Valeurs reprises par le formulaire « Créer un devis ». */
    public function formPayload(): array
    {
        return [
            'subject' => $this->subject,
            'operation_type' => $this->operation_type,
            'tax_rate' => (float) $this->tax_rate,
            'deposit_percentage' => $this->deposit_percentage !== null ? (float) $this->deposit_percentage : null,
            'is_free' => $this->is_free ? '1' : '0',
            'notes' => (string) $this->notes,
            'conditions' => (string) $this->conditions,
            'items' => collect($this->items)->map(fn ($item) => [
                'description' => (string) ($item['description'] ?? ''),
                'quantity' => (float) ($item['quantity'] ?? 1),
                'unit_price' => (float) ($item['unit_price'] ?? 0),
            ])->values()->all(),
        ];
    }
}
