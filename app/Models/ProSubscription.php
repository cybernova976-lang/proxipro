<?php

namespace App\Models;

use App\Support\ProviderSubscriptionPlans;
use Illuminate\Database\Eloquent\Model;

class ProSubscription extends Model
{
    public const TERMINAL_STRIPE_STATUSES = ['canceled', 'incomplete_expired'];

    /** Délai laissé au membre pour régulariser un paiement refusé, avantages conservés. */
    public const PAYMENT_GRACE_DAYS = 15;

    /** Rappels envoyés pendant ce délai, en jours après le premier échec. */
    public const PAYMENT_REMINDER_DAYS = [7, 12];

    protected $fillable = [
        'user_id', 'plan', 'amount', 'status', 'starts_at', 'ends_at',
        'cancelled_at', 'stripe_subscription_id', 'stripe_payment_intent',
        'stripe_checkout_session_id', 'stripe_status', 'last_payment_at', 'payment_failed_at',
        'grace_ends_at', 'payment_reminders_sent', 'payment_method_session_id',
        'auto_renew', 'notifications_enabled', 'realtime_notifications',
        'selected_categories', 'intervention_radius',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'last_payment_at' => 'datetime',
        'payment_failed_at' => 'datetime',
        'grace_ends_at' => 'datetime',
        'payment_reminders_sent' => 'integer',
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

    /**
     * Avantages ouverts. Après un paiement refusé, c'est le délai de
     * régularisation qui décide, et non plus la période annoncée par Stripe.
     */
    public function isActive(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->payment_failed_at !== null) {
            return $this->grace_ends_at !== null && $this->grace_ends_at->isFuture();
        }

        return $this->ends_at === null || $this->ends_at->isFuture();
    }

    /** Un paiement a été refusé et le membre peut encore régulariser sans rien perdre. */
    public function inPaymentGrace(): bool
    {
        return $this->payment_failed_at !== null && $this->isActive();
    }

    /** Jours pleins ou entamés avant le retour au compte gratuit. */
    public function graceDaysLeft(): int
    {
        if (! $this->grace_ends_at) {
            return 0;
        }

        return max(0, (int) ceil(now()->diffInDays($this->grace_ends_at, false)));
    }

    public function isExpired(): bool
    {
        return $this->ends_at && $this->ends_at->isPast();
    }

    public function getPlanLabel(): string
    {
        $label = ProviderSubscriptionPlans::get($this->plan)['label'] ?? ucfirst($this->plan);

        return $label.' ('.number_format((float) $this->amount, 2, ',', ' ').' €'.($this->plan === 'annual' ? '/an' : '/mois').')';
    }

    /** Stripe can still collect payment even after local Premium access has stopped. */
    public function scopeBillingOpen($query)
    {
        return $query->whereNotNull('stripe_subscription_id')
            ->where(function ($query) {
                $query->whereNull('stripe_status')
                    ->orWhereNotIn('stripe_status', self::TERMINAL_STRIPE_STATUSES);
            });
    }

    public function hasOpenBilling(): bool
    {
        return filled($this->stripe_subscription_id)
            && ! in_array($this->stripe_status, self::TERMINAL_STRIPE_STATUSES, true);
    }

    /** Même règle que isActive(), côté requête. */
    public function scopeCurrentlyActive($query)
    {
        return $query->where('status', 'active')
            ->where(function ($query) {
                $query->where(function ($query) {
                    $query->whereNull('payment_failed_at')
                        ->where(function ($query) {
                            $query->whereNull('ends_at')->orWhere('ends_at', '>', now());
                        });
                })->orWhere(function ($query) {
                    $query->whereNotNull('payment_failed_at')->where('grace_ends_at', '>', now());
                });
            });
    }

    /** Abonnements dont un paiement a été refusé et qui gardent encore leurs avantages. */
    public function scopeInPaymentGrace($query)
    {
        return $query->where('status', 'active')
            ->whereNotNull('payment_failed_at')
            ->where('grace_ends_at', '>', now());
    }

    public function daysRemaining(): int
    {
        if (! $this->ends_at) {
            return 0;
        }

        return max(0, (int) now()->diffInDays($this->ends_at));
    }
}
