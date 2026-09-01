<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VerificationRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'document_type',
        'document_front',
        'document_back',
        'selfie',
        'payment_amount',
        'payment_id',
        'payment_status',
        'paid_at',
        'status',
        'admin_notes',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'payment_amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    public function scopeProfileVerification($query)
    {
        return $query->where('type', 'profile_verification');
    }

    public function scopeServiceProvider($query)
    {
        return $query->where('type', 'service_provider');
    }

    // Helpers
    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isApproved()
    {
        return $this->status === 'approved';
    }

    public function isPaid()
    {
        return $this->payment_status === 'paid';
    }

    // Prix de vérification
    public static function getVerificationPrice($type)
    {
        return IdentityVerification::getVerificationPrice($type);
    }
}
