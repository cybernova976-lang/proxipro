<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlockedEmail extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'reason',
        'blocked_by',
        'source_user_id',
    ];

    public static function normalize(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    public static function isBlocked(?string $email): bool
    {
        if (! $email) {
            return false;
        }

        return static::query()
            ->where('email', static::normalize($email))
            ->exists();
    }

    public function blockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocked_by')->withTrashed();
    }

    public function sourceUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'source_user_id')->withTrashed();
    }
}
