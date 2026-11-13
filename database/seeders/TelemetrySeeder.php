<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\Telemetry;
use App\Models\TelemetryHourly;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TelemetrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $devices = Device::all();
        if ($devices->isEmpty()) {
            return;
        }

        $now = Carbon::now('UTC');

        // Clean up legacy high-power telemetry records from previous seed versions
        Telemetry::where('power', '>', 130.5)->delete();

        foreach ($devices as $device) {
            $isPrimary = $device->uid === 'ST-DEMO-001';
            $powerScale = match ($device->uid) {
                'ST-DEMO-001' => 1.0,
                'ST-PILOT-002' => 0.98,
                default => 0.95,
            };

            // 1. Seed Hourly Rollups for the past 30 days (130 W nominal curve)
            for ($daysAgo = 30; $daysAgo >= 0; $daysAgo--) {
                for ($hour = 0; $hour < 24; $hour++) {
                    $bucket = Carbon::today('Asia/Jakarta')
                        ->subDays($daysAgo)
                        ->setHour($hour)
                        ->setMinute(0)
                        ->setSecond(0)
                        ->setTimezone('UTC');

                    if ($bucket->isFuture()) {
                        continue;
                    }

                    // Solar daylight hours in WIB (06:00 to 18:00)
                    $wibHour = $hour;
                    if ($wibHour >= 6 && $wibHour <= 18) {
                        $dayProgress = ($wibHour - 6) / 12.0;
                        $sunCurve = sin($dayProgress * M_PI);
                        $avgPower = round((125.0 * $sunCurve + rand(-4, 4)) * $powerScale, 1);
                        $avgPower = max(0.0, $avgPower);
                        $maxPower = round(min(130.5, $avgPower * 1.05), 1);
                        $energyDelta = round(($avgPower * 1.0), 2); // Wh in 1 hour
                    } else {
                        $avgPower = 0.0;
                        $maxPower = 0.0;
                        $energyDelta = 0.0;
                    }

                    $maxWind = round(2.8 + (sin($hour / 4.0) * 1.2) + (rand(0, 15) / 10.0), 1);
                    $avgWind = round($maxWind * 0.75, 1);

                    TelemetryHourly::updateOrCreate(
                        [
                            'device_id' => $device->id,
                            'hour_bucket' => $bucket,
                        ],
                        [
                            'avg_power_w' => $avgPower,
                            'max_power_w' => $maxPower,
                            'min_power_w' => 0.0,
                            'energy_delta_wh' => $energyDelta,
                            'max_wind_ms' => $maxWind,
                            'avg_wind_ms' => $avgWind,
                            'uptime_percent' => 99.8,
                            'samples_count' => 720,
                        ]
                    );
                }
            }

            // 2. High-resolution telemetry for Today
            $startToday = Carbon::today('Asia/Jakarta')->setTimezone('UTC');
            Telemetry::where('device_id', $device->id)->where('recorded_at', '>=', $startToday)->delete();
            $current = clone $startToday;
            $cumulativeEnergy = 920.0 * $powerScale;

            while ($current->lte($now)) {
                $wibTime = (clone $current)->timezone('Asia/Jakarta');
                $hourFrac = $wibTime->hour + ($wibTime->minute / 60.0);

                if ($hourFrac >= 6.0 && $hourFrac <= 18.0) {
                    $progress = ($hourFrac - 6.0) / 12.0;
                    $sunElev = round(65.0 * sin($progress * M_PI), 2);
                    $sunAz = round(75.0 + ($progress * 190.0), 2);
                    $power = round((130.0 * sin($progress * M_PI) + (rand(-3, 3) / 10.0)) * $powerScale, 1);
                    $power = max(0.0, min(130.0, $power));
                    $voltage = round(18.5 + (0.5 * sin($progress * M_PI)), 1);
                    $currentA = $voltage > 0 ? round($power / $voltage, 2) : 0.0;
                } else {
                    $sunElev = 15.0;
                    $sunAz = 90.0;
                    $power = 130.0; // Keep live power steady at 130 W as requested for demonstration
                    $voltage = 18.6;
                    $currentA = 6.99;
                }

                $cumulativeEnergy += ($power * (15.0 / 60.0));
                $windSpeed = round(2.6 + (sin($current->timestamp / 3600.0) * 0.8) + (rand(-5, 5) / 10.0), 1);
                $windSpeed = max(1.2, min(4.5, $windSpeed));

                Telemetry::updateOrCreate(
                    [
                        'device_id' => $device->id,
                        'recorded_at' => $current,
                    ],
                    [
                        'received_at' => (clone $current)->addSeconds(1),
                        'firmware' => '1.0.0',
                        'rssi' => $device->rssi ?? -64,
                        'uptime_s' => 28500,
                        'mode' => 'auto',
                        'voltage' => $voltage,
                        'current' => $currentA,
                        'power' => $power,
                        'energy_wh' => round($cumulativeEnergy, 1),
                        'wind_speed' => $windSpeed,
                        'pitch_elev' => $sunElev,
                        'azimuth' => $sunAz,
                        'roll' => 0.12,
                        'cal_sys' => 3,
                        'cal_gyro' => 3,
                        'cal_accel' => 3,
                        'cal_mag' => 3,
                        'target_elev' => $sunElev + 0.1,
                        'target_az' => $sunAz + 0.2,
                        'elev_state' => 'idle',
                        'az_state' => 'idle',
                        'elev_current' => 0.05,
                        'az_current' => 0.04,
                        'latitude' => -6.2944,
                        'longitude' => 107.1539,
                        'altitude' => 28.5,
                        'satellites' => 10,
                        'fix' => true,
                        'hdop' => 0.95,
                        'temp_c' => 31.8,
                        'drift_s' => 0,
                        'flags' => [
                            'wind_alarm' => false,
                            'limit_elev_min' => false,
                            'limit_elev_max' => false,
                        ],
                    ]
                );

                $current->addMinutes(15);
            }

            // Fresh live telemetry record at right now
            $liveTime = (clone $now)->subSeconds(2);
            $livePower = 130.0 * $powerScale;
            $liveVoltage = 18.6;
            $liveCurrent = round($livePower / $liveVoltage, 2);

            Telemetry::updateOrCreate(
                [
                    'device_id' => $device->id,
                    'recorded_at' => $liveTime,
                ],
                [
                    'received_at' => (clone $liveTime)->addSeconds(1),
                    'firmware' => '1.0.0',
                    'rssi' => $device->rssi ?? -62,
                    'uptime_s' => 34200,
                    'mode' => 'auto',
                    'voltage' => $liveVoltage,
                    'current' => $liveCurrent,
                    'power' => $livePower,
                    'energy_wh' => round($cumulativeEnergy, 1),
                    'wind_speed' => 2.8,
                    'pitch_elev' => 32.4,
                    'azimuth' => 248.2,
                    'roll' => 0.12,
                    'cal_sys' => 3,
                    'cal_gyro' => 3,
                    'cal_accel' => 3,
                    'cal_mag' => 3,
                    'target_elev' => 32.5,
                    'target_az' => 248.0,
                    'elev_state' => 'idle',
                    'az_state' => 'idle',
                    'elev_current' => 0.05,
                    'az_current' => 0.04,
                    'latitude' => -6.2944,
                    'longitude' => 107.1539,
                    'altitude' => 28.5,
                    'satellites' => 10,
                    'fix' => true,
                    'hdop' => 0.95,
                    'temp_c' => 31.5,
                    'drift_s' => 0,
                    'flags' => [
                        'wind_alarm' => false,
                        'limit_elev_min' => false,
                        'limit_elev_max' => false,
                    ],
                ]
            );

            $device->update([
                'status' => 'online',
                'mode' => 'auto',
                'last_seen_at' => $liveTime,
                'rssi' => $device->rssi ?? -62,
            ]);
        }
    }
}
