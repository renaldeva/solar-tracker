<?php

namespace Database\Seeders;

use App\Models\Alert;
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
        $device = Device::where('uid', 'ST-DEMO-001')->first();
        if (! $device) {
            return;
        }

        $now = Carbon::now('UTC');

        // 1. Seed Hourly Rollups for the past 7 days (for Day/Week/Month Chart)
        for ($daysAgo = 7; $daysAgo >= 0; $daysAgo--) {
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
                $wibHour = ($hour);
                if ($wibHour >= 6 && $wibHour <= 18) {
                    $dayProgress = ($wibHour - 6) / 12.0;
                    $sunCurve = sin($dayProgress * M_PI);
                    $avgPower = round(480.0 * $sunCurve + rand(-15, 15), 1);
                    $avgPower = max(0.0, $avgPower);
                    $maxPower = round($avgPower * 1.12, 1);
                    $energyDelta = round(($avgPower * 1.0), 2); // Wh in 1 hour
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
                        'uptime_percent' => 100.0,
                        'samples_count' => 720,
                    ]
                );
            }
        }

        // 2. Seed High-Resolution 5-minute Telemetry for Today
        $startToday = Carbon::today('Asia/Jakarta')->setTimezone('UTC');
        $current = clone $startToday;
        $cumulativeEnergy = 1420.0;

        while ($current->lte($now)) {
            $wibTime = (clone $current)->timezone('Asia/Jakarta');
            $hourFrac = $wibTime->hour + ($wibTime->minute / 60.0);

            if ($hourFrac >= 6.0 && $hourFrac <= 18.0) {
                $progress = ($hourFrac - 6.0) / 12.0;
                $sunElev = round(72.0 * sin($progress * M_PI), 2);
                $sunAz = round(70.0 + ($progress * 200.0), 2);
                $power = round(510.0 * sin($progress * M_PI) + rand(-10, 10), 2);
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

            $cumulativeEnergy += ($power * (5.0 / 60.0));
            $windSpeed = round(3.8 + (sin($current->timestamp / 3600.0) * 1.5) + (rand(-10, 10) / 10.0), 2);
            $windSpeed = max(0.8, $windSpeed);

            Telemetry::updateOrCreate(
                [
                    'device_id' => $device->id,
                    'recorded_at' => $current,
                ],
                [
                    'received_at' => (clone $current)->addSeconds(1),
                    'firmware' => '1.0.0',
                    'rssi' => -64 + rand(-2, 2),
                    'uptime_s' => 14500,
                    'mode' => 'auto',
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

            $current->addMinutes(15); // 15-minute intervals for clean plotting
        }

        // 3. Update device real-time status to online
        $latest = Telemetry::where('device_id', $device->id)->latest('recorded_at')->first();
        if ($latest) {
            $device->update([
                'status' => 'online',
                'mode' => $latest->mode,
                'last_seen_at' => $now,
                'rssi' => $latest->rssi,
            ]);
        }

        // 4. Seed a couple of historical and informative alerts
        Alert::updateOrCreate(
            [
                'device_id' => $device->id,
                'code' => 'HIGH_WIND_STOW_HIST',
            ],
            [
                'severity' => 'critical',
                'message' => 'Protective stow engaged: Gust 16.4 m/s exceeded 15.0 m/s threshold',
                'status' => 'resolved',
                'metadata' => ['gust_ms' => 16.4, 'threshold' => 15.0],
                'opened_at' => Carbon::yesterday()->setHour(14)->setTimezone('UTC'),
                'resolved_at' => Carbon::yesterday()->setHour(14)->addMinutes(18)->setTimezone('UTC'),
            ]
        );

        Alert::updateOrCreate(
            [
                'device_id' => $device->id,
                'code' => 'IMU_CALIBRATION_OK',
            ],
            [
                'severity' => 'info',
                'message' => 'BNO055 9-DOF IMU operating at full 3/3 calibration across all sensors',
                'status' => 'open',
                'metadata' => ['cal_sys' => 3, 'cal_gyro' => 3, 'cal_accel' => 3, 'cal_mag' => 3],
                'opened_at' => Carbon::today()->setHour(6)->setTimezone('UTC'),
            ]
        );
    }
}
