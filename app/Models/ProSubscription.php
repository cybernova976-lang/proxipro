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
        'expiry_reminder_sent_at', 'auto_renew', 'notifications_enabled', 'realtime_notifications',
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
        'expiry_reminder_sent_at' => 'datetime',
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

        // Accès à paiement unique payé d'avance : il n'ouvre rien avant sa date de début.
        if ($this->isOneTimeAccess() && $this->startsLater()) {
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

    /** Un paiement refusé attend toujours d'être réglé, que le délai coure encore ou non. */
    public function isPaymentLate(): bool
    {
        return $this->payment_failed_at !== null && in_array($this->status, ['active', 'pending'], true);
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
        $amount = number_format((float) $this->amount, 2, ',', ' ').' €';

        if ($this->isOneTimeAccess()) {
            // Un seul paiement : ni « par mois », ni renouvellement.
            return 'Paiement unique '.$this->oneTimeMonths().' mois ('.$amount.')';
        }

        $label = ProviderSubscriptionPlans::get($this->plan)['label'] ?? ucfirst($this->plan);

        return $label.' ('.$amount.($this->plan === 'annual' ? '/an' : '/mois').')';
    }

    /**
     * Accès réglé en une fois par carte : ni abonnement Stripe, ni prélèvement, ni
     * reconduction. Il s'arrête seul à sa date de fin.
     */
    public function isOneTimeAccess(): bool
    {
        return ProviderSubscriptionPlans::isOneTime($this->plan);
    }

    /** Durée payée, en mois : celle du catalogue, ou celle de la période si la formule n'y est plus. */
    public function oneTimeMonths(): int
    {
        if (ProviderSubscriptionPlans::get((string) $this->plan) !== null) {
            return ProviderSubscriptionPlans::months($this->plan);
        }

        return $this->starts_at && $this->ends_at
            ? max(1, (int) round($this->starts_at->diffInMonths($this->ends_at)))
            : 1;
    }

    /** « paiement unique 3 mois », à lire après « Premium · ». */
    public function oneTimeLabel(): string
    {
        return 'paiement unique '.$this->oneTimeMonths().' mois';
    }

    /** Accès payé d'avance qui commencera à la fin de l'accès en cours. */
    public function startsLater(): bool
    {
        return $this->starts_at !== null && $this->starts_at->isFuture();
    }

    /**
     * Accès à paiement unique déjà payé, en file : il n'ouvre encore rien, et
     * commencera seul à sa date de début (même règle que scopePaidAhead()).
     */
    public function isPaidAhead(): bool
    {
        return $this->status === 'active' && $this->isOneTimeAccess() && $this->startsLater()
            && ($this->ends_at === null || $this->ends_at->isFuture());
    }

    /** Même règle que isPaidAhead(), côté requête. */
    public function scopePaidAhead($query)
    {
        return $query->oneTimeAccess()->where('status', 'active')
            ->where('starts_at', '>', now())
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', now()));
    }

    /** Accès en cours, ou accès à paiement unique déjà payé qui le prolongera. */
    public function scopeActiveOrPaidAhead($query)
    {
        return $query->where(function ($query) {
            $query->where(fn ($query) => $query->currentlyActive())
                ->orWhere(fn ($query) => $query->paidAhead());
        });
    }

    /** Même règle que isOneTimeAccess(), côté requête. */
    public function scopeOneTimeAccess($query)
    {
        return $query->where(function ($query) {
            $query->whereIn('plan', array_keys(ProviderSubscriptionPlans::oneTime()))
                ->orWhere('plan', 'like', ProviderSubscriptionPlans::ONE_TIME_PREFIX.'%');
        });
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
            // Accès à paiement unique payé d'avance : rien avant sa date de début.
            // Les abonnements Stripe et les accès offerts ne sont pas concernés.
            ->where(function ($query) {
                $query->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now())
                    ->orWhereNull('plan')
                    ->orWhere(function ($query) {
                        $query->whereNotIn('plan', array_keys(ProviderSubscriptionPlans::oneTime()))
                            ->where('plan', 'not like', ProviderSubscriptionPlans::ONE_TIME_PREFIX.'%');
                    });
            })
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

    /** Même règle que isPaymentLate(), côté requête. */
    public function scopePaymentLate($query)
    {
        return $query->whereNotNull('payment_failed_at')->whereIn('status', ['active', 'pending']);
    }

    public function daysRemaining(): int
    {
        if (! $this->ends_at) {
            return 0;
        }

        return max(0, (int) now()->diffInDays($this->ends_at));
    }
}
