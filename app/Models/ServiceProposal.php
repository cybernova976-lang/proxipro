<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceProposal extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_REFUSED = 'refused';

    public const STATUS_WITHDRAWN = 'withdrawn';

    protected $fillable = [
        'ad_id',
        'provider_id',
        'service_order_id',
        'amount',
        'message',
        'scheduled_for',
        'status',
        'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'scheduled_for' => 'datetime',
            'responded_at' => 'datetime',
        ];
    }

    public function ad()
    {
        return $this->belongsTo(Ad::class);
    }

    public function provider()
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    public function serviceOrder()
    {
        return $this->belongsTo(ServiceOrder::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'En attente',
            self::STATUS_ACCEPTED => 'Acceptée',
            self::STATUS_REFUSED => 'Refusée',
            self::STATUS_WITHDRAWN => 'Retirée',
            default => ucfirst($this->status),
        };
    }
}
