<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Telemetry;
use App\Models\TelemetryHourly;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EnergyMonitoringController extends Controller
{
    /**
     * Display the Energy Monitoring dashboard page.
     */
    public function index(Request $request): View
    {
        $devices = Device::with('site')->orderBy('name')->get();
        $selectedUid = $request->query('device', $devices->first()?->uid);
        $device = $devices->firstWhere('uid', $selectedUid) ?? $devices->first();

        $metrics = $this->calculateEnergyMetrics($device);

        return view('energy-monitoring', [
            'devices' => $devices,
            'device' => $device,
            'metrics' => $metrics,
        ]);
    }

    /**
     * Live JSON data endpoint for short-polling (every 5s).
     */
    public function data(Request $request): JsonResponse
    {
        $selectedUid = $request->query('device');
        $device = Device::where('uid', $selectedUid)->first() ?? Device::first();

        if (! $device) {
            return response()->json(['error' => 'Device not found'], 404);
        }

        $metrics = $this->calculateEnergyMetrics($device);

        return response()->json($metrics);
    }

    /**
     * Compute comprehensive electrical, energy, and sub-system parameters.
     *
     * @return array<string, mixed>
     */
    protected function calculateEnergyMetrics(?Device $device): array
    {
        if (! $device) {
            return [
                'has_data' => false,
                'pzem' => ['voltage' => 0, 'current' => 0, 'power_w' => 0, 'power_kw' => 0, 'energy_wh' => 0, 'energy_kwh' => 0, 'label' => 'Solar array'],
                'daily_energy_kwh' => 0.0,
                'monthly_energy_kwh' => 0.0,
                'daily_target_kwh' => 50.0,
                'progress_percent' => 0.0,
                'subsystem' => ['temp_c' => 0, 'drift_s' => 0, 'wind_ms' => 0, 'wind_stow_ms' => 15.0, 'elev' => 0, 'target_elev' => 0, 'az' => 0, 'target_az' => 0, 'elev_state' => 'idle', 'az_state' => 'idle'],
                'chart_24h' => ['labels' => [], 'values' => []],
                'chart_compare' => ['labels' => [], 'today' => [], 'yesterday' => []],
            ];
        }

        $latest = Telemetry::where('device_id', $device->id)
            ->orderByDesc('recorded_at')
            ->first();

        $settings = $device->settings ?? Device::defaultSettings();
        $measurementLabel = $settings['measurement_label'] ?? 'Solar array';
        $dailyTargetKwh = (float) ($settings['daily_energy_target_kwh'] ?? 50.0);
        $windStowThreshold = (float) ($settings['wind_stow_ms'] ?? 15.0);

        // Time ranges in WIB (UTC+7)
        $nowWib = Carbon::now('Asia/Jakarta');
        $startOfTodayUtc = $nowWib->copy()->startOfDay()->setTimezone('UTC');
        $startOfYesterdayUtc = $nowWib->copy()->subDay()->startOfDay()->setTimezone('UTC');
        $endOfYesterdayUtc = $nowWib->copy()->subDay()->endOfDay()->setTimezone('UTC');
        $startOfMonthUtc = $nowWib->copy()->startOfMonth()->setTimezone('UTC');

        // Daily energy (from start of today WIB to now)
        $todayFirst = Telemetry::where('device_id', $device->id)
            ->where('recorded_at', '>=', $startOfTodayUtc)
            ->orderBy('recorded_at', 'asc')
            ->first();

        $todayLast = Telemetry::where('device_id', $device->id)
            ->where('recorded_at', '>=', $startOfTodayUtc)
            ->orderBy('recorded_at', 'desc')
            ->first();

        $dailyEnergyWh = 0.0;
        if ($todayFirst && $todayLast && $todayLast->energy_wh >= $todayFirst->energy_wh) {
            $dailyEnergyWh = (float) ($todayLast->energy_wh - $todayFirst->energy_wh);
        } else {
            // Aggregate from hourly table for today
            $hourlySum = TelemetryHourly::where('device_id', $device->id)
                ->where('hour_bucket', '>=', $startOfTodayUtc)
                ->sum('energy_delta_wh');
            $dailyEnergyWh = (float) $hourlySum;
        }
        $dailyEnergyKwh = round($dailyEnergyWh / 1000.0, 2);

        // Monthly energy total (kWh)
        $monthlyHourlySum = TelemetryHourly::where('device_id', $device->id)
            ->where('hour_bucket', '>=', $startOfMonthUtc)
            ->sum('energy_delta_wh');
        $monthlyEnergyKwh = round(((float) $monthlyHourlySum + $dailyEnergyWh) / 1000.0, 1);

        $progressPercent = min(100.0, round(($dailyEnergyKwh / max(0.1, $dailyTargetKwh)) * 100.0, 1));

        // 24h Live Power Line Chart (Last 24 hours in 1-hour intervals)
        $labels24h = [];
        $values24h = [];
        for ($h = 23; $h >= 0; $h--) {
            $hourStart = Carbon::now('UTC')->subHours($h)->startOfHour();
            $hourEnd = $hourStart->copy()->endOfHour();

            // Try hourly aggregate first, fallback to raw avg
            $hourlyRecord = TelemetryHourly::where('device_id', $device->id)
                ->where('hour_bucket', $hourStart)
                ->first();

            if ($hourlyRecord && $hourlyRecord->avg_power_w !== null) {
                $avgPowerW = (float) $hourlyRecord->avg_power_w;
            } else {
                $avgPowerW = (float) Telemetry::where('device_id', $device->id)
                    ->whereBetween('recorded_at', [$hourStart, $hourEnd])
                    ->avg('power');
            }

            $labels24h[] = $hourStart->setTimezone('Asia/Jakarta')->format('H:i');
            $values24h[] = round($avgPowerW, 1);
        }

        // Today vs Yesterday Hourly Production Bar Chart (00:00 to 23:00 WIB)
        $compareLabels = [];
        $todayHourlyKwh = [];
        $yesterdayHourlyKwh = [];

        for ($hour = 0; $hour < 24; $hour++) {
            $compareLabels[] = sprintf('%02d:00', $hour);

            // Today hour bucket in UTC
            $todayBucketUtc = $nowWib->copy()->startOfDay()->addHours($hour)->setTimezone('UTC');
            $todayBucketEndUtc = $todayBucketUtc->copy()->endOfHour();

            $todayRecord = TelemetryHourly::where('device_id', $device->id)
                ->where('hour_bucket', $todayBucketUtc)
                ->first();

            if ($todayRecord) {
                $todayKwh = round((float) $todayRecord->energy_delta_wh / 1000.0, 3);
            } else {
                $todayRaw = Telemetry::where('device_id', $device->id)
                    ->whereBetween('recorded_at', [$todayBucketUtc, $todayBucketEndUtc])
                    ->get();
                if ($todayRaw->count() >= 2) {
                    $delta = max(0.0, (float) ($todayRaw->last()->energy_wh - $todayRaw->first()->energy_wh));
                    $todayKwh = round($delta / 1000.0, 3);
                } else {
                    $todayKwh = 0.0;
                }
            }
            $todayHourlyKwh[] = $todayKwh;

            // Yesterday hour bucket in UTC
            $yesterdayBucketUtc = $nowWib->copy()->subDay()->startOfDay()->addHours($hour)->setTimezone('UTC');
            $yesterdayBucketEndUtc = $yesterdayBucketUtc->copy()->endOfHour();

            $yesterdayRecord = TelemetryHourly::where('device_id', $device->id)
                ->where('hour_bucket', $yesterdayBucketUtc)
                ->first();

            if ($yesterdayRecord) {
                $yesterdayKwh = round((float) $yesterdayRecord->energy_delta_wh / 1000.0, 3);
            } else {
                $yesterdayRaw = Telemetry::where('device_id', $device->id)
                    ->whereBetween('recorded_at', [$yesterdayBucketUtc, $yesterdayBucketEndUtc])
                    ->get();
                if ($yesterdayRaw->count() >= 2) {
                    $delta = max(0.0, (float) ($yesterdayRaw->last()->energy_wh - $yesterdayRaw->first()->energy_wh));
                    $yesterdayKwh = round($delta / 1000.0, 3);
                } else {
                    $yesterdayKwh = 0.0;
                }
            }
            $yesterdayHourlyKwh[] = $yesterdayKwh;
        }

        $voltage = $latest ? (float) $latest->voltage : 0.0;
        $current = $latest ? (float) $latest->current : 0.0;
        $powerW = $latest ? (float) $latest->power : 0.0;
        $powerKw = round($powerW / 1000.0, 2);
        $energyWh = $latest ? (float) $latest->energy_wh : 0.0;
        $energyKwh = round($energyWh / 1000.0, 2);

        return [
            'has_data' => $latest !== null,
            'recorded_at_wib' => $latest?->recorded_at?->setTimezone('Asia/Jakarta')->format('d M Y, H:i:s T') ?? '--',
            'pzem' => [
                'voltage' => $voltage,
                'current' => $current,
                'power_w' => $powerW,
                'power_kw' => $powerKw,
                'energy_wh' => $energyWh,
                'energy_kwh' => $energyKwh,
                'label' => $measurementLabel,
            ],
            'daily_energy_kwh' => $dailyEnergyKwh,
            'monthly_energy_kwh' => $monthlyEnergyKwh,
            'daily_target_kwh' => $dailyTargetKwh,
            'progress_percent' => $progressPercent,
            'subsystem' => [
                'temp_c' => $latest ? (float) $latest->temp_c : 0.0,
                'drift_s' => $latest ? (int) $latest->drift_s : 0,
                'wind_ms' => $latest ? (float) $latest->wind_speed : 0.0,
                'wind_stow_ms' => $windStowThreshold,
                'elev' => $latest ? (float) $latest->pitch_elev : 0.0,
                'target_elev' => $latest ? (float) $latest->target_elev : 0.0,
                'az' => $latest ? (float) $latest->azimuth : 0.0,
                'target_az' => $latest ? (float) $latest->target_az : 0.0,
                'elev_state' => $latest->elev_state ?? 'idle',
                'az_state' => $latest->az_state ?? 'idle',
                'cal_sys' => $latest ? (int) $latest->cal_sys : 0,
            ],
            'chart_24h' => [
                'labels' => $labels24h,
                'values' => $values24h,
            ],
            'chart_compare' => [
                'labels' => $compareLabels,
                'today' => $todayHourlyKwh,
                'yesterday' => $yesterdayHourlyKwh,
            ],
        ];
    }
}
