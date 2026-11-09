<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\Device;
use App\Models\Site;
use App\Models\Telemetry;
use App\Models\TelemetryHourly;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the system dashboard.
     */
    public function index(Request $request): View
    {
        $site = Site::first();
        $devices = Device::with(['latestTelemetry'])->get();

        $totalDevices = $devices->count();
        $onlineDevices = $devices->where('status', 'online')->count();
        $unstableDevices = $devices->where('status', 'unstable')->count();
        $offlineDevices = $devices->where('status', 'offline')->count();

        // 1. Calculate Today's Energy & Peak Power from Telemetry & Rollups
        $todayStart = Carbon::today('Asia/Jakarta')->setTimezone('UTC');
        $primaryDevice = $devices->where('uid', 'ST-DEMO-001')->first() ?? $devices->first();

        // Energy generated today (Wh -> kWh)
        $todayHourlyWh = TelemetryHourly::where('hour_bucket', '>=', $todayStart)->sum('energy_delta_wh') ?: 0;
        $dailyEnergyKwh = round($todayHourlyWh / 1000.0, 2);

        // Fallback to telemetry table if rollups not run yet
        if ($dailyEnergyKwh <= 0.0) {
            $firstToday = Telemetry::where('recorded_at', '>=', $todayStart)->oldest('recorded_at')->first();
            $lastToday = Telemetry::where('recorded_at', '>=', $todayStart)->latest('recorded_at')->first();
            if ($firstToday && $lastToday && $lastToday->energy_wh > $firstToday->energy_wh) {
                $dailyEnergyKwh = round(($lastToday->energy_wh - $firstToday->energy_wh) / 1000.0, 2);
            } elseif ($lastToday) {
                $dailyEnergyKwh = round(($lastToday->energy_wh ?? 0) / 1000.0, 2);
            }
        }

        // Peak power today (kW)
        $peakPowerW = Telemetry::where('recorded_at', '>=', $todayStart)->max('power')
            ?? TelemetryHourly::where('hour_bucket', '>=', $todayStart)->max('max_power_w')
            ?? 0.0;
        $peakPowerKw = round($peakPowerW / 1000.0, 2);

        // Max wind speed last 24h
        $last24h = Carbon::now('UTC')->subDay();
        $maxWind = Telemetry::where('recorded_at', '>=', $last24h)->max('wind_speed')
            ?? TelemetryHourly::where('hour_bucket', '>=', $last24h)->max('max_wind_ms')
            ?? 0.0;

        // Active alerts count
        $activeAlertsCount = Alert::active()->count();

        $energyTargetKwh = (float) ($site->daily_energy_target_kwh ?? 50.0);
        $energyProgress = $energyTargetKwh > 0 ? min(100.0, round(($dailyEnergyKwh / $energyTargetKwh) * 100, 1)) : 0;

        $kpis = [
            'total_devices' => $totalDevices,
            'online_devices' => $onlineDevices,
            'unstable_devices' => $unstableDevices,
            'offline_devices' => $offlineDevices,
            'daily_energy_kwh' => $dailyEnergyKwh,
            'energy_target_kwh' => $energyTargetKwh,
            'energy_progress_percent' => $energyProgress,
            'peak_power_w' => (float) $peakPowerW,
            'peak_power_kw' => $peakPowerKw,
            'active_alerts' => $activeAlertsCount,
            'max_wind_ms' => round((float) $maxWind, 1),
        ];

        // 2. Power Production Chart Datasets (Day, Week, Month)
        // Day view: 24 hours of today in WIB (00:00 to 23:00)
        $dayLabels = [];
        $dayValues = [];
        for ($h = 0; $h < 24; $h++) {
            $dayLabels[] = sprintf('%02d:00', $h);
            $bucketTime = (clone $todayStart)->addHours($h);
            $hourlyRecord = TelemetryHourly::where('hour_bucket', '>=', $bucketTime)
                ->where('hour_bucket', '<', (clone $bucketTime)->addHour())
                ->first();

            $dayValues[] = $hourlyRecord ? round($hourlyRecord->avg_power_w, 1) : 0.0;
        }

        // Week view: past 7 days (Daily generation in kWh)
        $weekLabels = [];
        $weekValues = [];
        for ($d = 6; $d >= 0; $d--) {
            $dayDate = Carbon::today('Asia/Jakarta')->subDays($d);
            $weekLabels[] = $dayDate->format('D (d M)');
            $whSum = TelemetryHourly::where('hour_bucket', '>=', (clone $dayDate)->setTimezone('UTC'))
                ->where('hour_bucket', '<', (clone $dayDate)->addDay()->setTimezone('UTC'))
                ->sum('energy_delta_wh') ?: 0;
            $weekValues[] = round($whSum / 1000.0, 2);
        }

        // Month view: past 30 days
        $monthLabels = [];
        $monthValues = [];
        for ($d = 29; $d >= 0; $d--) {
            $dayDate = Carbon::today('Asia/Jakarta')->subDays($d);
            $monthLabels[] = $dayDate->format('d M');
            $whSum = TelemetryHourly::where('hour_bucket', '>=', (clone $dayDate)->setTimezone('UTC'))
                ->where('hour_bucket', '<', (clone $dayDate)->addDay()->setTimezone('UTC'))
                ->sum('energy_delta_wh') ?: 0;
            $monthValues[] = round($whSum / 1000.0, 2);
        }

        $chartData = [
            'day' => ['labels' => $dayLabels, 'values' => $dayValues, 'unit' => 'W'],
            'week' => ['labels' => $weekLabels, 'values' => $weekValues, 'unit' => 'kWh'],
            'month' => ['labels' => $monthLabels, 'values' => $monthValues, 'unit' => 'kWh'],
        ];

        // 3. Top 3 Open Alerts
        $criticalAlerts = Alert::active()
            ->with('device')
            ->orderByRaw("CASE WHEN severity = 'critical' THEN 1 WHEN severity = 'warning' THEN 2 ELSE 3 END")
            ->orderByDesc('opened_at')
            ->take(3)
            ->get();

        // 4. Primary Device Live Metrics
        $latestTelemetry = $primaryDevice?->latestTelemetry;

        return view('dashboard', [
            'site' => $site,
            'devices' => $devices,
            'primaryDevice' => $primaryDevice,
            'latestTelemetry' => $latestTelemetry,
            'kpis' => $kpis,
            'chartData' => $chartData,
            'criticalAlerts' => $criticalAlerts,
        ]);
    }
}
