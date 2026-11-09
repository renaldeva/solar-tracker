<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelemetryHourly extends Model
{
    use HasFactory;

    protected $table = 'telemetry_hourly';

    protected $fillable = [
        'device_id',
        'hour_bucket',
        'avg_power_w',
        'max_power_w',
        'min_power_w',
        'energy_delta_wh',
        'max_wind_ms',
        'avg_wind_ms',
        'uptime_percent',
        'samples_count',
    ];

    protected function casts(): array
    {
        return [
            'hour_bucket' => 'datetime',
            'avg_power_w' => 'float',
            'max_power_w' => 'float',
            'min_power_w' => 'float',
            'energy_delta_wh' => 'float',
            'max_wind_ms' => 'float',
            'avg_wind_ms' => 'float',
            'uptime_percent' => 'float',
            'samples_count' => 'integer',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
