<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceReminder extends Model
{
    public const FREQUENCY_ONCE = 'once';

    public const FREQUENCY_MONTHLY = 'monthly';

    public const FREQUENCY_QUARTERLY = 'quarterly';

    public const FREQUENCY_SEMIANNUAL = 'semiannual';

    public const FREQUENCY_YEARLY = 'yearly';

    public const FREQUENCIES = [
        self::FREQUENCY_ONCE,
        self::FREQUENCY_MONTHLY,
        self::FREQUENCY_QUARTERLY,
        self::FREQUENCY_SEMIANNUAL,
        self::FREQUENCY_YEARLY,
    ];

    protected $fillable = [
        'user_id',
        'service_order_id',
        'frequency',
        'next_reminder_at',
        'last_sent_at',
        'last_email_status',
        'reminders_sent_count',
        'send_email',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'next_reminder_at' => 'datetime',
            'last_sent_at' => 'datetime',
            'reminders_sent_count' => 'integer',
            'send_email' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function serviceOrder()
    {
        return $this->belongsTo(ServiceOrder::class);
    }

    public function nextOccurrence()
    {
        $nextOccurrence = match ($this->frequency) {
            self::FREQUENCY_MONTHLY => $this->next_reminder_at->copy()->addMonthNoOverflow(),
            self::FREQUENCY_QUARTERLY => $this->next_reminder_at->copy()->addMonthsNoOverflow(3),
            self::FREQUENCY_SEMIANNUAL => $this->next_reminder_at->copy()->addMonthsNoOverflow(6),
            self::FREQUENCY_YEARLY => $this->next_reminder_at->copy()->addYearNoOverflow(),
            default => null,
        };

        while ($nextOccurrence?->isPast()) {
            $nextOccurrence = match ($this->frequency) {
                self::FREQUENCY_MONTHLY => $nextOccurrence->addMonthNoOverflow(),
                self::FREQUENCY_QUARTERLY => $nextOccurrence->addMonthsNoOverflow(3),
                self::FREQUENCY_SEMIANNUAL => $nextOccurrence->addMonthsNoOverflow(6),
                self::FREQUENCY_YEARLY => $nextOccurrence->addYearNoOverflow(),
                default => null,
            };
        }

        return $nextOccurrence;
    }

    public function getFrequencyLabelAttribute(): string
    {
        return match ($this->frequency) {
            self::FREQUENCY_MONTHLY => 'Tous les mois',
            self::FREQUENCY_QUARTERLY => 'Tous les 3 mois',
            self::FREQUENCY_SEMIANNUAL => 'Tous les 6 mois',
            self::FREQUENCY_YEARLY => 'Tous les ans',
            default => 'Une seule fois',
        };
    }
}
