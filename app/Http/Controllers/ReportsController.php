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
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportsController extends Controller
{
    /**
     * Display the Energy & Performance Reports page.
     */
    public function index(Request $request): View
    {
        $sites = Site::orderBy('name')->get();
        $devices = Device::with('site')->orderBy('name')->get();

        $reportType = $request->query('type', 'daily'); // daily, monthly
        $selectedDate = $request->query('date', Carbon::now('Asia/Jakarta')->format('Y-m-d'));
        $selectedMonth = $request->query('month', Carbon::now('Asia/Jakarta')->format('Y-m'));
        $selectedSiteId = $request->query('site_id');
        $selectedDeviceId = $request->query('device_id');

        $rows = $this->generateReportData($reportType, $selectedDate, $selectedMonth, $selectedSiteId, $selectedDeviceId);

        $summary = [
            'total_energy_kwh' => round(collect($rows)->sum('energy_kwh'), 2),
            'avg_peak_power_w' => round(collect($rows)->avg('peak_power_w') ?? 0, 1),
            'avg_peak_power_kw' => round(collect($rows)->avg('peak_power_kw') ?? 0, 2),
            'avg_uptime_percent' => round(collect($rows)->avg('uptime_percent') ?? 0, 1),
            'total_alerts' => collect($rows)->sum('alerts_count'),
            'record_count' => count($rows),
        ];

        return view('reports', [
            'sites' => $sites,
            'devices' => $devices,
            'reportType' => $reportType,
            'selectedDate' => $selectedDate,
            'selectedMonth' => $selectedMonth,
            'selectedSiteId' => $selectedSiteId,
            'selectedDeviceId' => $selectedDeviceId,
            'rows' => $rows,
            'summary' => $summary,
        ]);
    }

    /**
     * Download the report as a CSV spreadsheet.
     */
    public function export(Request $request): StreamedResponse
    {
        $reportType = $request->query('type', 'daily');
        $selectedDate = $request->query('date', Carbon::now('Asia/Jakarta')->format('Y-m-d'));
        $selectedMonth = $request->query('month', Carbon::now('Asia/Jakarta')->format('Y-m'));
        $selectedSiteId = $request->query('site_id');
        $selectedDeviceId = $request->query('device_id');

        $rows = $this->generateReportData($reportType, $selectedDate, $selectedMonth, $selectedSiteId, $selectedDeviceId);

        $fileName = sprintf(
            'solar-energy-report-%s-%s.csv',
            $reportType,
            $reportType === 'daily' ? $selectedDate : $selectedMonth
        );

        return response()->streamDownload(function () use ($rows, $reportType) {
            $handle = fopen('php://output', 'w');

            $headers = app()->getLocale() === 'en' ? [
                $reportType === 'daily' ? 'Date (WIB)' : 'Month (WIB)',
                'Device UID',
                'Device Name',
                'Site',
                'Energy Generated (kWh)',
                'Target Energy (kWh)',
                'Target Progress (%)',
                'Peak Power (kW)',
                'Average Power (kW)',
                'Max Wind Speed (m/s)',
                'Operating Uptime (%)',
                'Alerts Count',
            ] : [
                $reportType === 'daily' ? 'Tanggal (WIB)' : 'Bulan (WIB)',
                'UID Perangkat',
                'Nama Perangkat',
                'Lokasi Fasilitas',
                'Energi Dihasilkan (kWh)',
                'Target Energi (kWh)',
                'Progres Target (%)',
                'Daya Puncak (W)',
                'Daya Rata-rata (W)',
                'Kecepatan Angin Maks (m/s)',
                'Waktu Operasional (%)',
                'Jumlah Peringatan',
            ];

            fputcsv($handle, $headers);

            foreach ($rows as $row) {
                $isId = app()->getLocale() === 'id';
                fputcsv($handle, [
                    $row['period_label'],
                    $row['device_uid'],
                    $row['device_name'],
                    $row['site_name'],
                    number_format((float) $row['energy_kwh'], 2, '.', ''),
                    number_format((float) $row['target_kwh'], 2, '.', ''),
                    number_format((float) $row['progress_percent'], 1, '.', ''),
                    $isId
                        ? number_format((float) ($row['peak_power_w'] ?? $row['peak_power_kw'] * 1000.0), 1, '.', '')
                        : number_format((float) $row['peak_power_kw'], 2, '.', ''),
                    $isId
                        ? number_format((float) ($row['avg_power_w'] ?? $row['avg_power_kw'] * 1000.0), 1, '.', '')
                        : number_format((float) $row['avg_power_kw'], 2, '.', ''),
                    number_format((float) $row['max_wind_ms'], 1, '.', ''),
                    number_format((float) $row['uptime_percent'], 1, '.', ''),
                    $row['alerts_count'],
                ]);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    /**
     * Generate report data rows.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function generateReportData(
        string $type,
        string $date,
        string $month,
        ?string $siteId,
        ?string $deviceId
    ): array {
        $devicesQuery = Device::with('site');
        if ($deviceId) {
            $devicesQuery->where('id', $deviceId);
        }
        if ($siteId) {
            $devicesQuery->where('site_id', $siteId);
        }
        $devices = $devicesQuery->get();

        $rows = [];

        if ($type === 'daily') {
            // Single date or multi-device daily summary
            $startUtc = Carbon::parse($date, 'Asia/Jakarta')->startOfDay()->setTimezone('UTC');
            $endUtc = Carbon::parse($date, 'Asia/Jakarta')->endOfDay()->setTimezone('UTC');

            foreach ($devices as $dev) {
                $targetKwh = (float) ($dev->settings['daily_energy_target_kwh'] ?? 50.0);

                // Try hourly aggregates first
                $hourly = TelemetryHourly::where('device_id', $dev->id)
                    ->whereBetween('hour_bucket', [$startUtc, $endUtc])
                    ->get();

                if ($hourly->isNotEmpty()) {
                    $energyKwh = round($hourly->sum('energy_delta_wh') / 1000.0, 2);
                    $peakPowerW = round((float) $hourly->max('max_power_w'), 1);
                    $avgPowerW = round((float) $hourly->avg('avg_power_w'), 1);
                    $peakPowerKw = round($peakPowerW / 1000.0, 2);
                    $avgPowerKw = round($avgPowerW / 1000.0, 2);
                    $maxWind = round((float) $hourly->max('max_wind_ms'), 1);
                    $uptime = round((float) $hourly->avg('uptime_percent'), 1);
                } else {
                    // Fallback to raw telemetry
                    $raw = Telemetry::where('device_id', $dev->id)
                        ->whereBetween('recorded_at', [$startUtc, $endUtc])
                        ->get();

                    if ($raw->isNotEmpty()) {
                        $delta = max(0.0, (float) ($raw->last()->energy_wh - $raw->first()->energy_wh));
                        $energyKwh = round($delta / 1000.0, 2);
                        $peakPowerW = round((float) $raw->max('power'), 1);
                        $avgPowerW = round((float) $raw->avg('power'), 1);
                        $peakPowerKw = round($peakPowerW / 1000.0, 2);
                        $avgPowerKw = round($avgPowerW / 1000.0, 2);
                        $maxWind = round((float) $raw->max('wind_speed'), 1);
                        $uptime = 100.0;
                    } else {
                        $energyKwh = 0.0;
                        $peakPowerW = 0.0;
                        $avgPowerW = 0.0;
                        $peakPowerKw = 0.0;
                        $avgPowerKw = 0.0;
                        $maxWind = 0.0;
                        $uptime = 0.0;
                    }
                }

                $alertsCount = Alert::where('device_id', $dev->id)
                    ->whereBetween('opened_at', [$startUtc, $endUtc])
                    ->count();

                $progress = min(100.0, round(($energyKwh / max(0.1, $targetKwh)) * 100.0, 1));

                $rows[] = [
                    'period_label' => $date,
                    'device_uid' => $dev->uid,
                    'device_name' => $dev->name,
                    'site_name' => $dev->site?->name ?? 'Default Site',
                    'energy_kwh' => $energyKwh,
                    'target_kwh' => $targetKwh,
                    'progress_percent' => $progress,
                    'peak_power_w' => $peakPowerW,
                    'avg_power_w' => $avgPowerW,
                    'peak_power_kw' => $peakPowerKw,
                    'avg_power_kw' => $avgPowerKw,
                    'max_wind_ms' => $maxWind,
                    'uptime_percent' => $uptime,
                    'alerts_count' => $alertsCount,
                ];
            }
        } else {
            // Monthly summary: days of month
            $startMonthUtc = Carbon::parse($month, 'Asia/Jakarta')->startOfMonth()->setTimezone('UTC');
            $endMonthUtc = Carbon::parse($month, 'Asia/Jakarta')->endOfMonth()->setTimezone('UTC');

            foreach ($devices as $dev) {
                $targetKwh = (float) ($dev->settings['daily_energy_target_kwh'] ?? 50.0) * 30;

                $hourly = TelemetryHourly::where('device_id', $dev->id)
                    ->whereBetween('hour_bucket', [$startMonthUtc, $endMonthUtc])
                    ->get();

                $energyKwh = round($hourly->sum('energy_delta_wh') / 1000.0, 2);
                $peakPowerW = round((float) $hourly->max('max_power_w'), 1);
                $avgPowerW = round((float) $hourly->avg('avg_power_w'), 1);
                $peakPowerKw = round($peakPowerW / 1000.0, 2);
                $avgPowerKw = round($avgPowerW / 1000.0, 2);
                $maxWind = round((float) $hourly->max('max_wind_ms'), 1);
                $uptime = round((float) $hourly->avg('uptime_percent') ?? 0, 1);

                $alertsCount = Alert::where('device_id', $dev->id)
                    ->whereBetween('opened_at', [$startMonthUtc, $endMonthUtc])
                    ->count();

                $progress = min(100.0, round(($energyKwh / max(0.1, $targetKwh)) * 100.0, 1));

                $rows[] = [
                    'period_label' => $month,
                    'device_uid' => $dev->uid,
                    'device_name' => $dev->name,
                    'site_name' => $dev->site?->name ?? 'Default Site',
                    'energy_kwh' => $energyKwh,
                    'target_kwh' => $targetKwh,
                    'progress_percent' => $progress,
                    'peak_power_w' => $peakPowerW,
                    'avg_power_w' => $avgPowerW,
                    'peak_power_kw' => $peakPowerKw,
                    'avg_power_kw' => $avgPowerKw,
                    'max_wind_ms' => $maxWind,
                    'uptime_percent' => $uptime,
                    'alerts_count' => $alertsCount,
                ];
            }
        }

        return $rows;
    }
}
