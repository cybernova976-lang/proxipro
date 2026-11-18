<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Preuve qu'un membre a accepté une version des conditions, avec la date et l'adresse IP. */
class TermsAcceptance extends Model
{
    protected $fillable = ['user_id', 'terms_version_id', 'accepted_at', 'ip', 'source'];

    protected function casts(): array
    {
        return ['accepted_at' => 'datetime'];
    }

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function version()
    {
        return $this->belongsTo(TermsVersion::class, 'terms_version_id');
    }
}
