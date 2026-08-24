<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Une vue de profil, dedoublonnee par visiteur et par jour.
 *
 * @property int         $profile_user_id
 * @property int|null    $viewer_user_id
 * @property string      $viewer_key
 * @property \Carbon\Carbon $viewed_on
 */
class ProfileView extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'profile_user_id',
        'viewer_user_id',
        'viewer_key',
        'viewed_on',
    ];

    protected $casts = [
        'viewed_on' => 'date',
        'created_at' => 'datetime',
    ];

    /** Le profil consulte. */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(User::class, 'profile_user_id');
    }

    /** Le visiteur, s'il etait connecte. */
    public function viewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'viewer_user_id');
    }
}
