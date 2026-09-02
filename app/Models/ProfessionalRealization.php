<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ProfessionalRealization extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'photo_path',
        'position',
    ];

    protected static function booted(): void
    {
        static::deleted(function (ProfessionalRealization $realization): void {
            if (filled($realization->photo_path)) {
                Storage::disk(config('filesystems.default', 'public'))->delete($realization->photo_path);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
