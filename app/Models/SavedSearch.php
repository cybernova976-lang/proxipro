<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SavedSearch extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'search_term',
        'category',
        'service_type',
        'country',
        'city',
        'latitude',
        'longitude',
        'radius_km',
        'filters',
        'is_active',
        'last_matched_at',
    ];

    protected function casts(): array
    {
        return [
            'filters' => 'array',
            'is_active' => 'boolean',
            'last_matched_at' => 'datetime',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'radius_km' => 'integer',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function matches()
    {
        return $this->hasMany(SavedSearchMatch::class);
    }

    public function buildFeedUrl(): string
    {
        $query = array_filter([
            'type' => $this->service_type,
            'category' => $this->category,
            'search' => $this->search_term,
            'radius' => $this->radius_km,
            'sort' => $this->filters['sort'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');

        return route('feed', $query);
    }
}