<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Telemetry;
use App\Models\TelemetryHourly;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HistoricalDataController extends Controller
{
    /**
     * Display the Historical Data query interface and table.
     */
    public function index(Request $request): View
    {
        $devices = Device::with('site')->orderBy('name')->get();
        $selectedUid = $request->query('device', $devices->first()?->uid);
        $device = $devices->firstWhere('uid', $selectedUid) ?? $devices->first();

        $granularity = $request->query('granularity', 'hourly'); // hourly, daily, raw
        $startDate = $request->query('start_date', Carbon::now('Asia/Jakarta')->subDays(7)->format('Y-m-d'));
        $endDate = $request->query('end_date', Carbon::now('Asia/Jakarta')->format('Y-m-d'));

        $startUtc = Carbon::parse($startDate, 'Asia/Jakarta')->startOfDay()->setTimezone('UTC');
        $endUtc = Carbon::parse($endDate, 'Asia/Jakarta')->endOfDay()->setTimezone('UTC');

        if ($granularity === 'raw') {
            $query = Telemetry::query();
            if ($device) {
                $query->where('device_id', $device->id);
            }
            $query->whereBetween('recorded_at', [$startUtc, $endUtc])->orderByDesc('recorded_at');

            $records = $query->paginate(30)->withQueryString();

            $peakPowerW = (float) $query->max('power');
            $avgPowerW = (float) $query->avg('power');

            $summary = [
                'total_energy_kwh' => round(($query->max('energy_wh') - $query->min('energy_wh')) / 1000.0, 2),
                'peak_power_w' => $peakPowerW,
                'avg_power_w' => $avgPowerW,
                'peak_power_kw' => round($peakPowerW / 1000.0, 2),
                'avg_power_kw' => round($avgPowerW / 1000.0, 2),
                'max_wind_ms' => round((float) $query->max('wind_speed'), 1),
                'samples_count' => $query->count(),
            ];
        } else {
            // Hourly or Daily from telemetry_hourly
            $query = TelemetryHourly::query();
            if ($device) {
                $query->where('device_id', $device->id);
            }
            $query->whereBetween('hour_bucket', [$startUtc, $endUtc])->orderByDesc('hour_bucket');

            $summaryRecords = (clone $query)->get();

            $peakPowerW = (float) $summaryRecords->max('max_power_w');
            $avgPowerW = (float) $summaryRecords->avg('avg_power_w');

            $summary = [
                'total_energy_kwh' => round($summaryRecords->sum('energy_delta_wh') / 1000.0, 2),
                'peak_power_w' => $peakPowerW,
                'avg_power_w' => $avgPowerW,
                'peak_power_kw' => round($peakPowerW / 1000.0, 2),
                'avg_power_kw' => round($avgPowerW / 1000.0, 2),
                'max_wind_ms' => round((float) $summaryRecords->max('max_wind_ms'), 1),
                'samples_count' => $summaryRecords->sum('samples_count'),
                'avg_uptime_percent' => round((float) $summaryRecords->avg('uptime_percent'), 1),
            ];

            $records = $query->paginate(30)->withQueryString();
        }

        return view('historical-data', [
            'devices' => $devices,
            'device' => $device,
            'granularity' => $granularity,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'records' => $records,
            'summary' => $summary,
        ]);
    }

    /**
     * Streamed CSV export of the historical dataset.
     */
    public function export(Request $request): StreamedResponse
    {
        $selectedUid = $request->query('device');
        $device = Device::where('uid', $selectedUid)->first() ?? Device::first();

        $granularity = $request->query('granularity', 'hourly');
        $startDate = $request->query('start_date', Carbon::now('Asia/Jakarta')->subDays(7)->format('Y-m-d'));
        $endDate = $request->query('end_date', Carbon::now('Asia/Jakarta')->format('Y-m-d'));

        $startUtc = Carbon::parse($startDate, 'Asia/Jakarta')->startOfDay()->setTimezone('UTC');
        $endUtc = Carbon::parse($endDate, 'Asia/Jakarta')->endOfDay()->setTimezone('UTC');

        $fileName = sprintf(
            'historical-%s-%s-%s-to-%s.csv',
            $device->uid ?? 'all',
            $granularity,
            $startDate,
            $endDate
        );

        return response()->streamDownload(function () use ($device, $granularity, $startUtc, $endUtc) {
            $handle = fopen('php://output', 'w');

            if ($granularity === 'raw') {
                $rawHeaders = app()->getLocale() === 'en' ? [
                    'Recorded At (UTC)',
                    'Recorded At (WIB)',
                    'Device UID',
                    'Mode',
                    'Voltage (V)',
                    'Current (A)',
                    'Power (W)',
                    'Cumulative Energy (Wh)',
                    'Wind Speed (m/s)',
                    'Pitch Elevation (deg)',
                    'Azimuth (deg)',
                    'Cabinet Temp (C)',
                ] : [
                    'Waktu Catat (UTC)',
                    'Waktu Catat (WIB)',
                    'UID Perangkat',
                    'Mode',
                    'Tegangan (V)',
                    'Arus (A)',
                    'Daya (W)',
                    'Energi Kumulatif (Wh)',
                    'Kecepatan Angin (m/s)',
                    'Elevasi Pitch (°)',
                    'Azimut (°)',
                    'Suhu Kotak (°C)',
                ];

                fputcsv($handle, $rawHeaders);

                $query = Telemetry::query();
                if ($device) {
                    $query->where('device_id', $device->id);
                }
                $query->whereBetween('recorded_at', [$startUtc, $endUtc])
                    ->orderBy('recorded_at', 'asc');

                $query->chunk(500, function ($chunk) use ($handle) {
                    foreach ($chunk as $row) {
                        fputcsv($handle, [
                            $row->recorded_at?->toIso8601String(),
                            $row->recorded_at?->setTimezone('Asia/Jakarta')->toIso8601String(),
                            $row->device?->uid ?? '',
                            $row->mode,
                            $row->voltage !== null ? number_format((float) $row->voltage, 2, '.', '') : '',
                            $row->current !== null ? number_format((float) $row->current, 2, '.', '') : '',
                            $row->power !== null ? number_format((float) $row->power, 1, '.', '') : '',
                            $row->energy_wh !== null ? number_format((float) $row->energy_wh, 0, '.', '') : '',
                            $row->wind_speed !== null ? number_format((float) $row->wind_speed, 1, '.', '') : '',
                            $row->pitch_elev !== null ? number_format((float) $row->pitch_elev, 1, '.', '') : '',
                            $row->azimuth !== null ? number_format((float) $row->azimuth, 1, '.', '') : '',
                            $row->temp_c !== null ? number_format((float) $row->temp_c, 1, '.', '') : '',
                        ]);
                    }
                });
            } else {
                $hourlyHeaders = app()->getLocale() === 'en' ? [
                    'Hour Bucket (UTC)',
                    'Hour Bucket (WIB)',
                    'Device UID',
                    'Avg Power (W)',
                    'Max Power (W)',
                    'Min Power (W)',
                    'Energy Generated (Wh)',
                    'Energy Generated (kWh)',
                    'Max Wind (m/s)',
                    'Avg Wind (m/s)',
                    'Uptime (%)',
                    'Samples Count',
                ] : [
                    'Waktu Jam (UTC)',
                    'Waktu Jam (WIB)',
                    'UID Perangkat',
                    'Daya Rata-rata (W)',
                    'Daya Maksimum (W)',
                    'Daya Minimum (W)',
                    'Energi Dihasilkan (Wh)',
                    'Energi Dihasilkan (kWh)',
                    'Angin Maksimum (m/s)',
                    'Angin Rata-rata (m/s)',
                    'Waktu Aktif (%)',
                    'Jumlah Sampel',
                ];

                fputcsv($handle, $hourlyHeaders);

                $query = TelemetryHourly::query();
                if ($device) {
                    $query->where('device_id', $device->id);
                }
                $query->whereBetween('hour_bucket', [$startUtc, $endUtc])
                    ->orderBy('hour_bucket', 'asc');

                $query->chunk(500, function ($chunk) use ($handle) {
                    foreach ($chunk as $row) {
                        fputcsv($handle, [
                            $row->hour_bucket?->toIso8601String(),
                            $row->hour_bucket?->setTimezone('Asia/Jakarta')->toIso8601String(),
                            $row->device?->uid ?? '',
                            $row->avg_power_w !== null ? number_format((float) $row->avg_power_w, 1, '.', '') : '',
                            $row->max_power_w !== null ? number_format((float) $row->max_power_w, 1, '.', '') : '',
                            $row->min_power_w !== null ? number_format((float) $row->min_power_w, 1, '.', '') : '',
                            $row->energy_delta_wh !== null ? number_format((float) $row->energy_delta_wh, 0, '.', '') : '',
                            $row->energy_delta_wh !== null ? number_format((float) $row->energy_delta_wh / 1000.0, 3, '.', '') : '',
                            $row->max_wind_ms !== null ? number_format((float) $row->max_wind_ms, 1, '.', '') : '',
                            $row->avg_wind_ms !== null ? number_format((float) $row->avg_wind_ms, 1, '.', '') : '',
                            $row->uptime_percent !== null ? number_format((float) $row->uptime_percent, 1, '.', '') : '',
                            $row->samples_count,
                        ]);
                    }
                });
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }
}
