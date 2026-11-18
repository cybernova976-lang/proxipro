<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceOrder extends Model
{
    use HasFactory;

    public const STATUS_PENDING_ACCEPTANCE = 'pending_acceptance';

    public const STATUS_AWAITING_PAYMENT = 'awaiting_payment';

    public const STATUS_FUNDED = 'funded';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_REFUSED = 'refused';

    public const STATUS_DISPUTED = 'disputed';

    public const STATUS_REFUNDED = 'refunded';

    /** Annulée par le client ou le prestataire avant tout paiement. */
    public const STATUS_CANCELED = 'canceled';

    /** Commandes en cours : rien ne doit les faire disparaître tant qu'elles ne sont pas closes. */
    public const OPEN_STATUSES = [
        self::STATUS_PENDING_ACCEPTANCE, self::STATUS_AWAITING_PAYMENT, self::STATUS_FUNDED, self::STATUS_DISPUTED,
    ];

    /**
     * Commandes à conserver : en cours, ou closes après un paiement (terminée,
     * remboursée). Elles portent l'argent des clients et les commissions.
     */
    public const KEPT_STATUSES = [
        ...self::OPEN_STATUSES, self::STATUS_COMPLETED, self::STATUS_REFUNDED,
    ];

    public const PAYMENT_AWAITING = 'awaiting_payment';

    public const PAYMENT_CHECKOUT_OPEN = 'checkout_open';

    public const PAYMENT_PAID = 'paid';

    public const PAYMENT_RELEASED = 'released';

    public const PAYMENT_CANCELED = 'canceled';

    public const PAYMENT_DISPUTED = 'disputed';

    public const PAYMENT_REFUNDED = 'refunded';

    protected $fillable = [
        'order_number',
        'ad_id',
        'buyer_id',
        'seller_id',
        'amount',
        'commission_amount',
        'seller_amount',
        'status',
        'payment_status',
        'message',
        'scheduled_for',
        'accepted_at',
        'paid_at',
        'released_at',
        'refunded_at',
        'refused_at',
        'refused_reason',
        'disputed_at',
        'dispute_reason',
        'admin_resolution',
        'admin_resolution_note',
        'admin_resolved_at',
        'admin_resolved_by',
        'stripe_checkout_session_id',
        'stripe_payment_intent_id',
        'stripe_transfer_id',
        'stripe_refund_id',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'seller_amount' => 'decimal:2',
            'scheduled_for' => 'datetime',
            'accepted_at' => 'datetime',
            'paid_at' => 'datetime',
            'released_at' => 'datetime',
            'refunded_at' => 'datetime',
            'refused_at' => 'datetime',
            'disputed_at' => 'datetime',
            'admin_resolved_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING_ACCEPTANCE => 'En attente d\'acceptation',
            self::STATUS_AWAITING_PAYMENT => 'En attente de paiement',
            self::STATUS_FUNDED => 'Payée',
            self::STATUS_COMPLETED => 'Terminée',
            self::STATUS_REFUSED => 'Refusée',
            self::STATUS_DISPUTED => 'Litige ouvert',
            self::STATUS_REFUNDED => 'Remboursée',
            self::STATUS_CANCELED => 'Annulée',
            default => str_replace('_', ' ', (string) $this->status),
        };
    }

    public function getPaymentStatusLabelAttribute(): string
    {
        return match ($this->payment_status) {
            // Lu après le mot « Paiement » : pas besoin de le répéter.
            self::PAYMENT_AWAITING => 'En attente',
            self::PAYMENT_CHECKOUT_OPEN => 'En cours',
            self::PAYMENT_PAID => 'Reçu, conservé en sécurité',
            self::PAYMENT_RELEASED => 'Versé au prestataire',
            self::PAYMENT_CANCELED => 'Annulé',
            self::PAYMENT_DISPUTED => 'Bloqué par un litige',
            self::PAYMENT_REFUNDED => 'Remboursé au client',
            default => str_replace('_', ' ', (string) $this->payment_status),
        };
    }

    /**
     * Réservation passée sur une DEMANDE (possible jusqu'en juillet 2026) : les
     * rôles y sont inversés, l'auteur de la demande s'y retrouve « prestataire »
     * alors qu'il est le client. Une telle commande ne peut qu'être refusée.
     */
    public function isReservationOnDemand(): bool
    {
        return $this->ad !== null
            && $this->ad->service_type === 'demande'
            && (int) $this->ad->user_id === (int) $this->seller_id;
    }

    /** Commandes où une action de ce membre est attendue : à accepter, ou à payer. */
    public static function awaitingActionCount(User $user): int
    {
        return static::query()
            ->where(fn ($orders) => $orders
                ->where(fn ($asSeller) => $asSeller->where('seller_id', $user->id)->where('status', self::STATUS_PENDING_ACCEPTANCE))
                ->orWhere(fn ($asBuyer) => $asBuyer->where('buyer_id', $user->id)->where('status', self::STATUS_AWAITING_PAYMENT)))
            ->count();
    }

    public function canSellerAccept(): bool
    {
        return $this->status === self::STATUS_PENDING_ACCEPTANCE && ! $this->isReservationOnDemand();
    }

    public function canSellerRefuse(): bool
    {
        return $this->status === self::STATUS_PENDING_ACCEPTANCE;
    }

    public function canBuyerPay(): bool
    {
        return $this->status === self::STATUS_AWAITING_PAYMENT
            && in_array($this->payment_status, [self::PAYMENT_AWAITING, self::PAYMENT_CHECKOUT_OPEN], true);
    }

    /**
     * Une commande peut être annulée tant qu'aucun paiement n'est encaissé :
     * par le client à tout moment avant de payer, par le prestataire une fois
     * la commande en attente de paiement (avant, il la refuse).
     */
    public function canBeCanceledBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }
        if ((int) $user->id === (int) $this->buyer_id) {
            return $this->status === self::STATUS_PENDING_ACCEPTANCE || $this->canBuyerPay();
        }

        return (int) $user->id === (int) $this->seller_id && $this->canBuyerPay();
    }

    /** Date d'annulation, lue dans les métadonnées de la commande. */
    public function getCanceledAtAttribute(): ?\Illuminate\Support\Carbon
    {
        $date = $this->metadata['canceled_at'] ?? null;

        return $date ? \Illuminate\Support\Carbon::parse($date) : null;
    }

    /** Nombre de rappels « confirmez la fin de la prestation » déjà envoyés au client. */
    public function getReleaseRemindersSentAttribute(): int
    {
        return (int) ($this->metadata['release_reminders_sent'] ?? 0);
    }

    public function getReleaseReminderLastAtAttribute(): ?\Illuminate\Support\Carbon
    {
        $date = $this->metadata['release_reminder_last_at'] ?? null;

        return $date ? \Illuminate\Support\Carbon::parse($date) : null;
    }

    public function canBuyerRelease(): bool
    {
        return $this->status === self::STATUS_FUNDED && $this->payment_status === self::PAYMENT_PAID;
    }

    public function canBuyerDispute(): bool
    {
        return $this->status === self::STATUS_FUNDED && $this->payment_status === self::PAYMENT_PAID;
    }

    public function canAdminResolveDispute(): bool
    {
        return $this->status === self::STATUS_DISPUTED && $this->payment_status === self::PAYMENT_DISPUTED;
    }

    public function ad()
    {
        return $this->belongsTo(Ad::class);
    }

    public function buyer()
    {
        // Une commande reste une pièce d'audit même lorsque le compte client
        // a été supprimé. Inclure les comptes soft-deleted évite de casser
        // l'espace de pilotage administrateur.
        return $this->belongsTo(User::class, 'buyer_id')->withTrashed();
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id')->withTrashed();
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function serviceReminder()
    {
        return $this->hasOne(ServiceReminder::class);
    }
}
