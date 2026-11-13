<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Telemetry extends Model
{
    use HasFactory;

    protected $table = 'telemetries';

    public $timestamps = false;

    protected $fillable = [
        'device_id',
        'device_uid',
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
        'pzem_voltage',
        'pzem_current',
        'pzem_power',
        'pzem_energy_wh',
        'wind_speed',
        'wind_speed_ms',
        'wind_speed_kmh',
        'wind_direction_deg',
        'pitch_elev',
        'azimuth',
        'roll',
        'imu_pitch_elev',
        'imu_azimuth',
        'imu_roll',
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
        'gps_lat',
        'gps_lon',
        'gps_alt',
        'gps_satellites',
        'gps_fix',
        'gps_hdop',
        'temp_c',
        'drift_s',
        'pzem_fault',
        'wind_fault',
        'imu_fault',
        'actuator_fault',
        'wind_alarm',
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
            'pzem_voltage' => 'float',
            'pzem_current' => 'float',
            'pzem_power' => 'float',
            'pzem_energy_wh' => 'float',
            'wind_speed' => 'float',
            'wind_speed_ms' => 'float',
            'wind_speed_kmh' => 'float',
            'wind_direction_deg' => 'float',
            'pitch_elev' => 'float',
            'azimuth' => 'float',
            'roll' => 'float',
            'imu_pitch_elev' => 'float',
            'imu_azimuth' => 'float',
            'imu_roll' => 'float',
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
            'gps_alt' => 'float',
            'gps_satellites' => 'integer',
            'gps_fix' => 'boolean',
            'gps_hdop' => 'float',
            'temp_c' => 'float',
            'drift_s' => 'integer',
            'pzem_fault' => 'boolean',
            'wind_fault' => 'boolean',
            'imu_fault' => 'boolean',
            'actuator_fault' => 'boolean',
            'wind_alarm' => 'boolean',
            'flags' => 'array',
            'raw_payload' => 'array',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
