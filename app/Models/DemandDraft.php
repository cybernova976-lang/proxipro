<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DemandDraft extends Model
{
    protected $fillable = [
        'user_id',
        'payload',
        'current_step',
        'last_activity_at',
        'review_requested_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'current_step' => 'integer',
            'last_activity_at' => 'datetime',
            'review_requested_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
