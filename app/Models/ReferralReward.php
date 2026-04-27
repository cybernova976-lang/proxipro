<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferralReward extends Model
{
    protected $fillable = [
        'referrer_user_id',
        'referee_user_id',
        'source_transaction_id',
        'reward_type',
        'points',
        'granted_at',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'granted_at' => 'datetime',
        ];
    }

    public function referrer()
    {
        return $this->belongsTo(User::class, 'referrer_user_id');
    }

    public function referee()
    {
        return $this->belongsTo(User::class, 'referee_user_id');
    }

    public function sourceTransaction()
    {
        return $this->belongsTo(Transaction::class, 'source_transaction_id');
    }
}