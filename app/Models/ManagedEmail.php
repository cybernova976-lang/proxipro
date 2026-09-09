<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManagedEmail extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_SENDING = 'sending';
    public const STATUS_SENT = 'sent';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'user_id', 'source_key', 'type', 'recipient_email', 'recipient_name',
        'subject', 'eyebrow', 'headline', 'body', 'cta_label', 'cta_url',
        'image_paths', 'metadata', 'status', 'scheduled_for', 'approved_by',
        'approved_at', 'sent_at', 'failure_message',
    ];

    protected function casts(): array
    {
        return [
            'image_paths' => 'array',
            'metadata' => 'array',
            'scheduled_for' => 'datetime',
            'approved_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
