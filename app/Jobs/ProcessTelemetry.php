<?php

namespace App\Jobs;

use App\Models\Device;
use App\Models\Site;
use App\Models\Telemetry;
use App\Services\AlertEngineService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessTelemetry implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public array $data) {}

    public function handle(): Telemetry
    {
        $payload = $this->data;
        $uid = $payload['uid'] ?? 'ST-DEMO-001';

        // Auto-provision or link device
        $device = Device::where('uid', $uid)->first();
        if (! $device) {
            $site = Site::first();
            if ($site) {
                $device = Device::create([
                    'uid' => $uid,
                    'name' => "Tracker {$uid}",
                    'site_id' => $site->id,
                    'mqtt_username' => "device_{$uid}",
                    'status' => 'online',
                    'mode' => $payload['mode'] ?? 'auto',
                    'firmware' => $payload['firmware'] ?? '1.2.0-esp32s3',
                    'settings' => Device::defaultSettings(),
                ]);
            }
        }

        $recordedAt = Carbon::now('UTC');
        if (! empty($payload['timestamp'])) {
            if (is_numeric($payload['timestamp'])) {
                $recordedAt = Carbon::createFromTimestampUTC((int) $payload['timestamp']);
            } else {
                $recordedAt = Carbon::parse($payload['timestamp'])->setTimezone('UTC');
            }
        }

        $receivedAt = Carbon::now('UTC');

        // Extract sensor groups safely (never assume presence)
        $pzem = is_array($payload['pzem'] ?? null) ? $payload['pzem'] : [];
        $wind = is_array($payload['wind'] ?? null) ? $payload['wind'] : [];
        $imu = is_array($payload['imu'] ?? null) ? $payload['imu'] : [];
        $target = is_array($payload['target'] ?? null) ? $payload['target'] : [];
        $actuators = is_array($payload['actuators'] ?? null) ? $payload['actuators'] : [];
        $gps = is_array($payload['gps'] ?? null) ? $payload['gps'] : [];
        $rtc = is_array($payload['rtc'] ?? null) ? $payload['rtc'] : [];
        $flags = is_array($payload['flags'] ?? null) ? $payload['flags'] : [];

        // Flags evaluation
        $pzemFault = isset($flags['pzem_fault']) ? (bool) $flags['pzem_fault'] : empty($pzem);
        $windFault = isset($flags['wind_fault']) ? (bool) $flags['wind_fault'] : empty($wind);
        $imuFault = ! empty($flags['imu_fault']);
        $actuatorFault = ! empty($flags['actuator_fault']);
        $windAlarm = ! empty($flags['wind_alarm']);

        // PZEM fields
        $pzemVoltage = isset($pzem['voltage']) && ! $pzemFault ? (float) $pzem['voltage'] : null;
        $pzemCurrent = isset($pzem['current']) && ! $pzemFault ? (float) $pzem['current'] : null;
        $pzemPower = isset($pzem['power']) && ! $pzemFault ? (float) $pzem['power'] : null;
        $pzemEnergyWh = isset($pzem['energy_wh']) && ! $pzemFault ? (float) $pzem['energy_wh'] : null;

        // Wind fields
        $windSpeedMs = isset($wind['speed_ms']) && ! $windFault ? (float) $wind['speed_ms'] : null;
        $windSpeedKmh = isset($wind['speed_kmh']) && ! $windFault ? (float) $wind['speed_kmh'] : null;
        $windDirectionDeg = null;
        if (isset($wind['direction_deg']) && ! $windFault) {
            $dir = (float) $wind['direction_deg'];
            if ($dir >= 0.0 && $dir <= 360.0) {
                $windDirectionDeg = $dir;
            }
        }

        // GPS lat / lon string validation
        $gpsLat = null;
        if (isset($gps['lat'])) {
            $gpsLat = is_scalar($gps['lat']) ? (string) $gps['lat'] : null;
        }
        $gpsLon = null;
        if (isset($gps['lon'])) {
            $gpsLon = is_scalar($gps['lon']) ? (string) $gps['lon'] : null;
        }

        $telemetry = Telemetry::create([
            'device_id' => $device?->id,
            'device_uid' => $uid,
            'recorded_at' => $recordedAt,
            'received_at' => $receivedAt,
            'firmware' => $payload['firmware'] ?? $device?->firmware ?? '1.2.0-esp32s3',
            'rssi' => isset($payload['rssi']) ? (int) $payload['rssi'] : null,
            'uptime_s' => isset($payload['uptime_s']) ? (int) $payload['uptime_s'] : null,
            'mode' => $payload['mode'] ?? $device?->mode ?? 'auto',

            // PZEM dual fields
            'voltage' => $pzemVoltage,
            'current' => $pzemCurrent,
            'power' => $pzemPower,
            'energy_wh' => $pzemEnergyWh,
            'pzem_voltage' => $pzemVoltage,
            'pzem_current' => $pzemCurrent,
            'pzem_power' => $pzemPower,
            'pzem_energy_wh' => $pzemEnergyWh,

            // Wind dual fields
            'wind_speed' => $windSpeedMs,
            'wind_speed_ms' => $windSpeedMs,
            'wind_speed_kmh' => $windSpeedKmh,
            'wind_direction_deg' => $windDirectionDeg,

            // IMU dual fields
            'pitch_elev' => isset($imu['pitch_elev']) ? (float) $imu['pitch_elev'] : null,
            'azimuth' => isset($imu['azimuth']) ? (float) $imu['azimuth'] : null,
            'roll' => isset($imu['roll']) ? (float) $imu['roll'] : null,
            'imu_pitch_elev' => isset($imu['pitch_elev']) ? (float) $imu['pitch_elev'] : null,
            'imu_azimuth' => isset($imu['azimuth']) ? (float) $imu['azimuth'] : null,
            'imu_roll' => isset($imu['roll']) ? (float) $imu['roll'] : null,
            'cal_sys' => isset($imu['cal_sys']) ? (int) $imu['cal_sys'] : null,
            'cal_gyro' => isset($imu['cal_gyro']) ? (int) $imu['cal_gyro'] : null,
            'cal_accel' => isset($imu['cal_accel']) ? (int) $imu['cal_accel'] : null,
            'cal_mag' => isset($imu['cal_mag']) ? (int) $imu['cal_mag'] : null,

            // Target
            'target_elev' => isset($target['elev']) ? (float) $target['elev'] : null,
            'target_az' => isset($target['az']) ? (float) $target['az'] : null,

            // Actuators
            'elev_state' => $actuators['elev_state'] ?? null,
            'az_state' => $actuators['az_state'] ?? null,
            'elev_current' => isset($actuators['elev_current']) ? (float) $actuators['elev_current'] : null,
            'az_current' => isset($actuators['az_current']) ? (float) $actuators['az_current'] : null,

            // GPS
            'latitude' => is_numeric($gpsLat) ? (float) $gpsLat : null,
            'longitude' => is_numeric($gpsLon) ? (float) $gpsLon : null,
            'altitude' => isset($gps['alt']) ? (float) $gps['alt'] : null,
            'satellites' => isset($gps['satellites']) ? (int) $gps['satellites'] : null,
            'fix' => ! empty($gps['fix']),
            'hdop' => isset($gps['hdop']) ? (float) $gps['hdop'] : null,
            'gps_lat' => $gpsLat,
            'gps_lon' => $gpsLon,
            'gps_alt' => isset($gps['alt']) ? (float) $gps['alt'] : null,
            'gps_satellites' => isset($gps['satellites']) ? (int) $gps['satellites'] : null,
            'gps_fix' => ! empty($gps['fix']),
            'gps_hdop' => isset($gps['hdop']) ? (float) $gps['hdop'] : null,

            // RTC
            'temp_c' => isset($rtc['temp_c']) ? (float) $rtc['temp_c'] : null,
            'drift_s' => isset($rtc['drift_s']) ? (int) $rtc['drift_s'] : null,

            // Fault flags
            'pzem_fault' => $pzemFault,
            'wind_fault' => $windFault,
            'imu_fault' => $imuFault,
            'actuator_fault' => $actuatorFault,
            'wind_alarm' => $windAlarm,
            'flags' => $flags,
            'raw_payload' => $payload,
        ]);

        if ($device) {
            $device->update([
                'last_seen_at' => $receivedAt,
                'status' => 'online',
                'firmware' => $payload['firmware'] ?? $device->firmware,
                'rssi' => $payload['rssi'] ?? $device->rssi,
                'mode' => $payload['mode'] ?? $device->mode,
            ]);

            app(AlertEngineService::class)->evaluateTelemetry($device, $payload);
        }

        return $telemetry;
    }
}
