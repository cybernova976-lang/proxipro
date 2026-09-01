<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProDocument extends Model
{
    protected $fillable = [
        'user_id', 'name', 'title', 'type', 'file_path', 'file_name',
        'file_size', 'mime_type', 'expiry_date', 'status', 'notes',
    ];

    protected $casts = [
        'expiry_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getTypeLabel(): string
    {
        return match($this->type) {
            'insurance' => 'Attestation d\'assurance',
            'kbis' => 'Extrait Kbis',
            'certificate', 'certification' => 'Certificat / Qualification',
            'diploma' => 'Diplôme',
            'identity' => 'Pièce d\'identité',
            'other' => 'Autre document',
            default => ucfirst($this->type),
        };
    }

    public function getTypeIcon(): string
    {
        return match($this->type) {
            'insurance' => 'fas fa-shield-alt',
            'kbis' => 'fas fa-building',
            'certificate', 'certification' => 'fas fa-award',
            'diploma' => 'fas fa-graduation-cap',
            'identity' => 'fas fa-id-card',
            'other' => 'fas fa-file-alt',
            default => 'fas fa-file',
        };
    }

    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function isExpiringSoon(): bool
    {
        return $this->expiry_date && $this->expiry_date->isBetween(now(), now()->addDays(30));
    }

    public function getFileSizeFormatted(): string
    {
        $bytes = $this->file_size;
        if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' Mo';
        if ($bytes >= 1024) return round($bytes / 1024, 1) . ' Ko';
        return $bytes . ' o';
    }
}
