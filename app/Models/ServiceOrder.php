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
            self::STATUS_FUNDED => 'Fonds bloques',
            self::STATUS_COMPLETED => 'Terminee',
            self::STATUS_REFUSED => 'Refusee',
            self::STATUS_DISPUTED => 'Litige ouvert',
            self::STATUS_REFUNDED => 'Remboursee',
            default => str_replace('_', ' ', (string) $this->status),
        };
    }

    public function getPaymentStatusLabelAttribute(): string
    {
        return match ($this->payment_status) {
            self::PAYMENT_AWAITING => 'Paiement en attente',
            self::PAYMENT_CHECKOUT_OPEN => 'Paiement en cours',
            self::PAYMENT_PAID => 'Paiement confirme',
            self::PAYMENT_RELEASED => 'Fonds liberes',
            self::PAYMENT_CANCELED => 'Paiement annule',
            self::PAYMENT_DISPUTED => 'Litige ouvert',
            self::PAYMENT_REFUNDED => 'Paiement rembourse',
            default => str_replace('_', ' ', (string) $this->payment_status),
        };
    }

    public function canSellerAccept(): bool
    {
        return $this->status === self::STATUS_PENDING_ACCEPTANCE;
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
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }
}