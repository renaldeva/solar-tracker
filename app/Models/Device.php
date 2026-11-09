<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Device extends Model
{
    use HasFactory;

    protected $fillable = [
        'uid',
        'name',
        'site_id',
        'mqtt_username',
        'status',
        'mode',
        'last_seen_at',
        'firmware',
        'rssi',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
            'rssi' => 'integer',
            'settings' => 'array',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function telemetries(): HasMany
    {
        return $this->hasMany(Telemetry::class);
    }

    public function latestTelemetry(): HasOne
    {
        return $this->hasOne(Telemetry::class)->latestOfMany('recorded_at');
    }

    public function hourlyTelemetries(): HasMany
    {
        return $this->hasMany(TelemetryHourly::class);
    }

    public function commands(): HasMany
    {
        return $this->hasMany(Command::class)->orderByDesc('issued_at');
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class)->orderByDesc('opened_at');
    }

    public function openAlerts(): HasMany
    {
        return $this->hasMany(Alert::class)->whereIn('status', ['open', 'acknowledged']);
    }

    /**
     * Get default settings for a device.
     */
    public static function defaultSettings(): array
    {
        return [
            'wind_stow_ms' => 15.0,
            'elev_min' => 10.0,
            'elev_max' => 85.0,
            'az_min' => 60.0,
            'az_max' => 300.0,
            'telemetry_interval_s' => 5,
            'measurement_label' => 'Solar array',
            'heading_offset' => 0.0,
            'misalignment_threshold_deg' => 5.0,
        ];
    }
}
