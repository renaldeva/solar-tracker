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
        $wibNow = Carbon::now('Asia/Jakarta');

        foreach ($devices as $device) {
            $isPrimary = $device->uid === 'ST-DEMO-001';
            $isSecondary = $device->uid === 'ST-PILOT-002';
            $powerScale = match ($device->uid) {
                'ST-DEMO-001' => 1.0,
                'ST-PILOT-002' => 0.88,
                default => 0.75,
            };

            // 1. Seed Hourly Rollups for past 30 days
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
                        $avgPower = round((480.0 * $sunCurve + rand(-15, 15)) * $powerScale, 1);
                        $avgPower = max(0.0, $avgPower);
                        $maxPower = round($avgPower * 1.12, 1);
                        $energyDelta = round($avgPower * 1.0, 2); // Wh in 1 hour
                    } else {
                        $avgPower = 0.0;
                        $maxPower = 0.0;
                        $energyDelta = 0.0;
                    }

                    $maxWind = round(3.5 + (sin($hour / 3.0) * 2.0) + (rand(0, 30) / 10.0), 2);
                    $avgWind = round($maxWind * 0.75, 2);

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
                            'uptime_percent' => $device->uid === 'ST-PILOT-003' && $daysAgo === 0 ? 0.0 : ($isSecondary && $daysAgo === 0 ? 82.5 : 99.8),
                            'samples_count' => 720,
                        ]
                    );
                }
            }

            // 2. High-resolution telemetry for today
            if ($isPrimary || $isSecondary) {
                $startToday = Carbon::today('Asia/Jakarta')->setTimezone('UTC');
                $current = clone $startToday;
                $cumulativeEnergy = 1250.0 * $powerScale;
                $stopTime = $isPrimary ? $now : (clone $now)->subMinutes(15);

                while ($current->lte($stopTime)) {
                    $wibTime = (clone $current)->timezone('Asia/Jakarta');
                    $hourFrac = $wibTime->hour + ($wibTime->minute / 60.0);

                    if ($hourFrac >= 6.0 && $hourFrac <= 18.0) {
                        $progress = ($hourFrac - 6.0) / 12.0;
                        $sunElev = round(72.0 * sin($progress * M_PI), 2);
                        $sunAz = round(70.0 + ($progress * 200.0), 2);
                        $power = round((510.0 * sin($progress * M_PI) + rand(-10, 10)) * $powerScale, 2);
                        $power = max(0.0, $power);
                        $voltage = round(48.5 + (3.0 * sin($progress * M_PI)) + (rand(-10, 10) / 10.0), 2);
                        $currentA = $voltage > 0 ? round($power / $voltage, 2) : 0.0;
                    } else {
                        $sunElev = 10.0;
                        $sunAz = 90.0;
                        $power = 0.0;
                        $voltage = 14.5;
                        $currentA = 0.0;
                    }

                    $cumulativeEnergy += ($power * (15.0 / 60.0));
                    $windSpeed = round(3.8 + (sin($current->timestamp / 3600.0) * 1.5) + (rand(-10, 10) / 10.0), 2);
                    $windSpeed = max(0.8, $windSpeed);

                    Telemetry::updateOrCreate(
                        [
                            'device_id' => $device->id,
                            'recorded_at' => $current,
                        ],
                        [
                            'received_at' => (clone $current)->addSeconds(1),
                            'firmware' => $device->firmware ?? '1.0.0',
                            'rssi' => ($isPrimary ? -64 : -79) + rand(-2, 2),
                            'uptime_s' => 14500,
                            'mode' => $isSecondary && $windSpeed > 15 ? 'stow' : 'auto',
                            'voltage' => $voltage,
                            'current' => $currentA,
                            'power' => $power,
                            'energy_wh' => round($cumulativeEnergy, 2),
                            'wind_speed' => $windSpeed,
                            'pitch_elev' => $sunElev,
                            'azimuth' => $sunAz,
                            'roll' => 0.22,
                            'cal_sys' => 3,
                            'cal_gyro' => 3,
                            'cal_accel' => 3,
                            'cal_mag' => 3,
                            'target_elev' => $sunElev,
                            'target_az' => $sunAz,
                            'elev_state' => 'idle',
                            'az_state' => 'idle',
                            'elev_current' => 0.05,
                            'az_current' => 0.04,
                            'latitude' => -6.2944,
                            'longitude' => 107.1539,
                            'altitude' => 28.5,
                            'satellites' => 9,
                            'fix' => true,
                            'hdop' => 1.05,
                            'temp_c' => round(29.0 + (10.0 * max(0.0, sin(deg2rad($sunElev)))), 2),
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

                // Fresh live telemetry packet right now for the primary device
                if ($isPrimary) {
                    $liveRecorded = (clone $now)->subSeconds(2);
                    Telemetry::updateOrCreate(
                        [
                            'device_id' => $device->id,
                            'recorded_at' => $liveRecorded,
                        ],
                        [
                            'received_at' => (clone $liveRecorded)->addSeconds(1),
                            'firmware' => '1.0.0',
                            'rssi' => -62,
                            'uptime_s' => 28900,
                            'mode' => 'auto',
                            'voltage' => 49.8,
                            'current' => 9.45,
                            'power' => 470.6,
                            'energy_wh' => round($cumulativeEnergy, 2),
                            'wind_speed' => 3.4,
                            'pitch_elev' => 28.4,
                            'azimuth' => 245.2,
                            'roll' => 0.15,
                            'cal_sys' => 3,
                            'cal_gyro' => 3,
                            'cal_accel' => 3,
                            'cal_mag' => 3,
                            'target_elev' => 28.5,
                            'target_az' => 245.0,
                            'elev_state' => 'idle',
                            'az_state' => 'idle',
                            'elev_current' => 0.08,
                            'az_current' => 0.06,
                            'latitude' => -6.2944,
                            'longitude' => 107.1539,
                            'altitude' => 28.5,
                            'satellites' => 10,
                            'fix' => true,
                            'hdop' => 0.95,
                            'temp_c' => 33.5,
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
                        'last_seen_at' => $liveRecorded,
                        'rssi' => -62,
                    ]);
                } elseif ($isSecondary) {
                    $device->update([
                        'status' => 'unstable',
                        'mode' => 'auto',
                        'last_seen_at' => (clone $now)->subMinutes(12),
                        'rssi' => -79,
                    ]);
                }
            } else {
                // Device 3 offline
                $device->update([
                    'status' => 'offline',
                    'mode' => 'stow',
                    'last_seen_at' => (clone $now)->subHours(18),
                    'rssi' => -92,
                ]);
            }
        }
    }
}
