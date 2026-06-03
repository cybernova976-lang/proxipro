<?php

namespace App\Models;

use App\Support\ProviderSubscriptionPlans;
use Illuminate\Database\Eloquent\Model;

class ProSubscription extends Model
{
    protected $fillable = [
        'user_id', 'plan', 'amount', 'status', 'starts_at', 'ends_at',
        'cancelled_at', 'stripe_subscription_id', 'stripe_payment_intent',
        'auto_renew', 'notifications_enabled', 'realtime_notifications',
        'selected_categories', 'intervention_radius',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'auto_renew' => 'boolean',
        'notifications_enabled' => 'boolean',
        'realtime_notifications' => 'boolean',
        'selected_categories' => 'array',
        'amount' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && ($this->ends_at === null || $this->ends_at->isFuture());
    }

    public function isExpired(): bool
    {
        return $this->ends_at && $this->ends_at->isPast();
    }

    public function getPlanLabel(): string
    {
        return ProviderSubscriptionPlans::summaryLabel($this->plan);
    }

    public function daysRemaining(): int
    {
        if (!$this->ends_at) return 0;
        return max(0, now()->diffInDays($this->ends_at));
    }
}
