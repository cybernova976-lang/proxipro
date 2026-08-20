<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsageDailyMetric extends Model
{
    protected $fillable = [
        'metric_date',
        'event_name',
        'route_name',
        'device_type',
        'app_mode',
        'count',
    ];

    protected function casts(): array
    {
        return [
            'metric_date' => 'immutable_date',
            'count' => 'integer',
        ];
    }
}
