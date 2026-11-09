<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Telemetry extends Model
{
    use HasFactory;

    protected $table = 'telemetry';

    public $timestamps = false;

    protected $fillable = [
        'device_id',
        'recorded_at',
        'received_at',
        'firmware',
        'rssi',
        'uptime_s',
        'mode',
        'voltage',
        'current',
        'power',
        'energy_wh',
        'wind_speed',
        'pitch_elev',
        'azimuth',
        'roll',
        'cal_sys',
        'cal_gyro',
        'cal_accel',
        'cal_mag',
        'target_elev',
        'target_az',
        'elev_state',
        'az_state',
        'elev_current',
        'az_current',
        'latitude',
        'longitude',
        'altitude',
        'satellites',
        'fix',
        'hdop',
        'temp_c',
        'drift_s',
        'flags',
        'raw_payload',
    ];

    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
            'received_at' => 'datetime',
            'rssi' => 'integer',
            'uptime_s' => 'integer',
            'voltage' => 'float',
            'current' => 'float',
            'power' => 'float',
            'energy_wh' => 'float',
            'wind_speed' => 'float',
            'pitch_elev' => 'float',
            'azimuth' => 'float',
            'roll' => 'float',
            'cal_sys' => 'integer',
            'cal_gyro' => 'integer',
            'cal_accel' => 'integer',
            'cal_mag' => 'integer',
            'target_elev' => 'float',
            'target_az' => 'float',
            'elev_current' => 'float',
            'az_current' => 'float',
            'latitude' => 'float',
            'longitude' => 'float',
            'altitude' => 'float',
            'satellites' => 'integer',
            'fix' => 'boolean',
            'hdop' => 'float',
            'temp_c' => 'float',
            'drift_s' => 'integer',
            'flags' => 'array',
            'raw_payload' => 'array',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
