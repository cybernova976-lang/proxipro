<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IdentityVerificationDocument extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'identity_verification_id',
        'user_id',
        'field',
        'original_name',
        'mime_type',
        'extension',
        'size',
        'content',
    ];

    protected $hidden = [
        'content',
    ];

    public function verification(): BelongsTo
    {
        return $this->belongsTo(IdentityVerification::class, 'identity_verification_id');
    }

    public static function path(string $id, string $extension): string
    {
        return 'verification-documents/' . $id . '.' . $extension;
    }

    public static function idFromPath(?string $path): ?string
    {
        if (!$path || !preg_match('#^verification-documents/([0-9a-f-]{36})\.[a-z0-9]+$#i', $path, $matches)) {
            return null;
        }

        return strtolower($matches[1]);
    }

    public static function isDatabasePath(?string $path): bool
    {
        return self::idFromPath($path) !== null;
    }
}
