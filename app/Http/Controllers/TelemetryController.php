<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Telemetry;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TelemetryController extends Controller
{
    /**
     * Display the Live Telemetry Explorer page.
     */
    public function index(Request $request): View
    {
        $devices = Device::with('site')->orderBy('name')->get();
        $selectedUid = $request->query('device', $devices->first()?->uid);
        $device = $devices->firstWhere('uid', $selectedUid) ?? $devices->first();

        $range = $request->query('range', '1h');
        $selectedMetrics = $request->query('metrics', ['power', 'voltage', 'current', 'wind_speed']);
        if (! is_array($selectedMetrics)) {
            $selectedMetrics = explode(',', (string) $selectedMetrics);
        }

        [$startUtc, $endUtc] = $this->parseTimeRange($range, $request->query('start_date'), $request->query('end_date'));

        $telemetryQuery = Telemetry::query();
        if ($device) {
            $telemetryQuery->where('device_id', $device->id);
        }
        $telemetryQuery->whereBetween('recorded_at', [$startUtc, $endUtc]);

        $paginatedRecords = (clone $telemetryQuery)
            ->orderByDesc('recorded_at')
            ->paginate(30)
            ->withQueryString();

        $chartData = $this->buildChartSeries($telemetryQuery, $selectedMetrics);

        return view('telemetry', [
            'devices' => $devices,
            'device' => $device,
            'selectedRange' => $range,
            'selectedMetrics' => $selectedMetrics,
            'telemetries' => $paginatedRecords,
            'chartData' => $chartData,
            'startDate' => $request->query('start_date'),
            'endDate' => $request->query('end_date'),
        ]);
    }

    /**
     * JSON data endpoint for asynchronous chart updates.
     */
    public function data(Request $request): JsonResponse
    {
        $selectedUid = $request->query('device');
        $device = Device::where('uid', $selectedUid)->first() ?? Device::first();

        $range = $request->query('range', '1h');
        $selectedMetrics = $request->query('metrics', ['power', 'voltage', 'current', 'wind_speed']);
        if (! is_array($selectedMetrics)) {
            $selectedMetrics = explode(',', (string) $selectedMetrics);
        }

        [$startUtc, $endUtc] = $this->parseTimeRange($range, $request->query('start_date'), $request->query('end_date'));

        $telemetryQuery = Telemetry::query();
        if ($device) {
            $telemetryQuery->where('device_id', $device->id);
        }
        $telemetryQuery->whereBetween('recorded_at', [$startUtc, $endUtc]);

        $chartData = $this->buildChartSeries($telemetryQuery, $selectedMetrics);

        return response()->json($chartData);
    }

    /**
     * Streamed CSV export of filtered telemetry.
     */
    public function export(Request $request): StreamedResponse
    {
        $selectedUid = $request->query('device');
        $device = Device::where('uid', $selectedUid)->first() ?? Device::first();

        $range = $request->query('range', '24h');
        [$startUtc, $endUtc] = $this->parseTimeRange($range, $request->query('start_date'), $request->query('end_date'));

        $telemetryQuery = Telemetry::query();
        if ($device) {
            $telemetryQuery->where('device_id', $device->id);
        }
        $telemetryQuery->whereBetween('recorded_at', [$startUtc, $endUtc])
            ->orderBy('recorded_at', 'asc');

        $fileName = sprintf('telemetry-%s-%s.csv', $device->uid ?? 'all', Carbon::now('Asia/Jakarta')->format('Ymd_His'));

        return response()->streamDownload(function () use ($telemetryQuery) {
            $handle = fopen('php://output', 'w');
            $headers = app()->getLocale() === 'en' ? [
                'Timestamp (UTC)',
                'Timestamp (WIB)',
                'Device UID',
                'Operating Mode',
                'DC Voltage (V)',
                'DC Current (A)',
                'DC Power (W)',
                'Cumulative Energy (Wh)',
                'Wind Speed (m/s)',
                'Pitch Elevation (deg)',
                'Target Elevation (deg)',
                'Azimuth (deg)',
                'Target Azimuth (deg)',
                'Cabinet Temp (C)',
                'RTC Drift (s)',
                'GPS Fix',
                'GPS Satellites',
                'IMU Cal Sys (0-3)',
                'Elev Actuator State',
                'Az Actuator State',
            ] : [
                'Waktu Catat (UTC)',
                'Waktu Catat (WIB)',
                'UID Perangkat',
                'Mode Operasi',
                'Tegangan DC (V)',
                'Arus DC (A)',
                'Daya DC (W)',
                'Energi Kumulatif (Wh)',
                'Kecepatan Angin (m/s)',
                'Elevasi Pitch (°)',
                'Target Elevasi (°)',
                'Azimut (°)',
                'Target Azimut (°)',
                'Suhu Kotak (°C)',
                'Penyimpangan RTC (s)',
                'Sinyal GPS',
                'Satelit GPS',
                'Kalibrasi IMU Sys (0-3)',
                'Status Aktuator Elevasi',
                'Status Aktuator Azimut',
            ];
            fputcsv($handle, $headers);

            $telemetryQuery->chunk(500, function ($records) use ($handle) {
                foreach ($records as $row) {
                    $wibTime = $row->recorded_at ? $row->recorded_at->setTimezone('Asia/Jakarta')->toIso8601String() : '';
                    fputcsv($handle, [
                        $row->recorded_at?->toIso8601String(),
                        $wibTime,
                        $row->device?->uid ?? '',
                        $row->mode,
                        $row->voltage !== null ? number_format((float) $row->voltage, 2, '.', '') : '',
                        $row->current !== null ? number_format((float) $row->current, 2, '.', '') : '',
                        $row->power !== null ? number_format((float) $row->power, 1, '.', '') : '',
                        $row->energy_wh !== null ? number_format((float) $row->energy_wh, 0, '.', '') : '',
                        $row->wind_speed !== null ? number_format((float) $row->wind_speed, 1, '.', '') : '',
                        $row->pitch_elev !== null ? number_format((float) $row->pitch_elev, 1, '.', '') : '',
                        $row->target_elev !== null ? number_format((float) $row->target_elev, 1, '.', '') : '',
                        $row->azimuth !== null ? number_format((float) $row->azimuth, 1, '.', '') : '',
                        $row->target_az !== null ? number_format((float) $row->target_az, 1, '.', '') : '',
                        $row->temp_c !== null ? number_format((float) $row->temp_c, 1, '.', '') : '',
                        $row->drift_s,
                        $row->fix ? 'TRUE' : 'FALSE',
                        $row->satellites,
                        $row->cal_sys,
                        $row->elev_state,
                        $row->az_state,
                    ]);
                }
            });

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    /**
     * Parse time range strings into UTC start and end carbon dates.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    protected function parseTimeRange(string $range, ?string $customStart = null, ?string $customEnd = null): array
    {
        $now = Carbon::now('UTC');

        return match ($range) {
            '1h' => [$now->copy()->subHour(), $now],
            '6h' => [$now->copy()->subHours(6), $now],
            '24h' => [$now->copy()->subDay(), $now],
            '7d' => [$now->copy()->subDays(7), $now],
            'custom' => [
                $customStart ? Carbon::parse($customStart, 'Asia/Jakarta')->setTimezone('UTC') : $now->copy()->subDay(),
                $customEnd ? Carbon::parse($customEnd, 'Asia/Jakarta')->setTimezone('UTC') : $now,
            ],
            default => [$now->copy()->subHour(), $now],
        };
    }

    /**
     * Build time-series labels and datasets for Chart.js.
     *
     * @param  array<string>  $metrics
     * @return array<string, mixed>
     */
    protected function buildChartSeries($query, array $metrics): array
    {
        // Downsample if dataset is large to maintain crisp rendering
        $totalCount = (clone $query)->count();
        $records = (clone $query)
            ->orderBy('recorded_at', 'asc')
            ->limit(200)
            ->get();

        $labels = [];
        $dataMap = [];
        foreach ($metrics as $metric) {
            $dataMap[$metric] = [];
        }

        foreach ($records as $record) {
            $labels[] = $record->recorded_at ? $record->recorded_at->setTimezone('Asia/Jakarta')->format('H:i:s') : '';
            foreach ($metrics as $m) {
                $val = match ($m) {
                    'power' => (float) $record->power,
                    'voltage' => (float) $record->voltage,
                    'current' => (float) $record->current,
                    'wind_speed' => (float) $record->wind_speed,
                    'pitch_elev' => (float) $record->pitch_elev,
                    'azimuth' => (float) $record->azimuth,
                    'temp_c' => (float) $record->temp_c,
                    default => null,
                };
                $dataMap[$m][] = $val;
            }
        }

        $metricConfigs = [
            'power' => ['label' => 'Power (W)', 'color' => '#2563eb', 'unit' => 'W'],
            'voltage' => ['label' => 'Voltage (V)', 'color' => '#d97706', 'unit' => 'V'],
            'current' => ['label' => 'Current (A)', 'color' => '#059669', 'unit' => 'A'],
            'wind_speed' => ['label' => 'Wind (m/s)', 'color' => '#dc2626', 'unit' => 'm/s'],
            'pitch_elev' => ['label' => 'Elevation (°)', 'color' => '#7c3aed', 'unit' => '°'],
            'azimuth' => ['label' => 'Azimuth (°)', 'color' => '#0891b2', 'unit' => '°'],
            'temp_c' => ['label' => 'Cabinet Temp (°C)', 'color' => '#e11d48', 'unit' => '°C'],
        ];

        $datasets = [];
        foreach ($metrics as $m) {
            if (! isset($metricConfigs[$m])) {
                continue;
            }
            $config = $metricConfigs[$m];
            $datasets[] = [
                'label' => __($config['label']),
                'data' => $dataMap[$m] ?? [],
                'borderColor' => $config['color'],
                'backgroundColor' => 'transparent',
                'borderWidth' => 2,
                'pointRadius' => count($labels) > 60 ? 1 : 2,
                'tension' => 0.25,
            ];
        }

        return [
            'labels' => $labels,
            'datasets' => $datasets,
            'total_count' => $totalCount,
        ];
    }
}
