<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Telemetry;
use App\Models\TelemetryHourly;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DeviceMonitoringController extends Controller
{
    /**
     * Display a listing of registered tracker nodes with connectivity metrics.
     */
    public function index(Request $request): View
    {
        $statusFilter = $request->query('status', 'all');
        $search = trim($request->query('search', ''));

        // Query devices with eager-loaded relations
        $query = Device::query()->with(['site', 'latestTelemetry']);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('uid', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if (in_array($statusFilter, ['online', 'unstable', 'offline'], true)) {
            $query->where('status', $statusFilter);
        }

        $devices = $query->orderByRaw("CASE WHEN status = 'online' THEN 1 WHEN status = 'unstable' THEN 2 ELSE 3 END")
            ->orderBy('name')
            ->get();

        // High-level KPI counts
        $totalTrackers = Device::count();
        $onlineCount = Device::where('status', 'online')->count();
        $unstableCount = Device::where('status', 'unstable')->count();
        $offlineCount = Device::where('status', 'offline')->count();

        // Calculate latest telemetry lag from the most active device
        $latestRecord = Telemetry::latest('recorded_at')->first();
        $telemetryLagSeconds = $latestRecord ? max(0, Carbon::now()->diffInSeconds($latestRecord->recorded_at)) : null;

        // Link Health / MQTT Status
        $brokerHost = config('mqtt.host', 'mosquitto');
        $brokerPort = (int) config('mqtt.port', 1883);
        $brokerSocketOk = false;
        $connectionLatencyMs = null;

        $startSocket = microtime(true);
        $socket = @fsockopen($brokerHost, $brokerPort, $errCode, $errMsg, 0.25);
        if ($socket) {
            $brokerSocketOk = true;
            $connectionLatencyMs = round((microtime(true) - $startSocket) * 1000, 1);
            fclose($socket);
        }

        // Telemetry ingestion rate (packets received in last 60 seconds)
        $packetsLastMinute = Telemetry::where('received_at', '>=', Carbon::now()->subMinute())->count();
        $listenerStatus = ($packetsLastMinute > 0 || $onlineCount > 0) ? 'Operational' : 'Idle';

        // Compute 24-hour connectivity presence bars for each device
        $now = Carbon::now('Asia/Jakarta');
        $twentyFourHoursAgo = Carbon::now()->subHours(24);

        $deviceConnectivity = [];
        foreach ($devices as $device) {
            $hourlyRecords = TelemetryHourly::where('device_id', $device->id)
                ->where('hour_bucket', '>=', $twentyFourHoursAgo)
                ->get()
                ->keyBy(function (TelemetryHourly $h) {
                    return $h->hour_bucket->timezone('Asia/Jakarta')->format('Y-m-d H:00');
                });

            $slots = [];
            for ($i = 23; $i >= 0; $i--) {
                $slotTime = $now->copy()->subHours($i);
                $key = $slotTime->format('Y-m-d H:00');
                $hourly = $hourlyRecords->get($key);

                if ($hourly) {
                    $status = ($hourly->uptime_percent >= 80.0) ? 'online' : 'unstable';
                    $samples = $hourly->samples_count;
                    $uptime = $hourly->uptime_percent;
                } else {
                    // Check if current hour and device is online
                    if ($i === 0 && $device->status === 'online') {
                        $status = 'online';
                        $samples = max(1, $packetsLastMinute);
                        $uptime = 100.0;
                    } elseif ($device->status === 'online' && $i <= 1) {
                        $status = 'online';
                        $samples = 120;
                        $uptime = 100.0;
                    } elseif ($device->status === 'unstable' && $i <= 2) {
                        $status = 'unstable';
                        $samples = 30;
                        $uptime = 45.0;
                    } else {
                        $status = 'offline';
                        $samples = 0;
                        $uptime = 0.0;
                    }
                }

                $slots[] = [
                    'hour' => $slotTime->format('H:00'),
                    'time_wib' => $slotTime->format('d M H:00').' WIB',
                    'status' => $status,
                    'samples' => $samples,
                    'uptime' => $uptime,
                ];
            }
            $deviceConnectivity[$device->id] = $slots;
        }

        return view('device-monitoring', [
            'devices' => $devices,
            'totalTrackers' => $totalTrackers,
            'onlineCount' => $onlineCount,
            'unstableCount' => $unstableCount,
            'offlineCount' => $offlineCount,
            'telemetryLagSeconds' => $telemetryLagSeconds,
            'statusFilter' => $statusFilter,
            'search' => $search,
            'brokerSocketOk' => $brokerSocketOk,
            'brokerHost' => $brokerHost,
            'brokerPort' => $brokerPort,
            'connectionLatencyMs' => $connectionLatencyMs,
            'listenerStatus' => $listenerStatus,
            'packetsLastMinute' => $packetsLastMinute,
            'deviceConnectivity' => $deviceConnectivity,
        ]);
    }

    /**
     * Export registered devices list and telemetry snapshot as CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        $statusFilter = $request->query('status', 'all');
        $query = Device::query()->with(['site', 'latestTelemetry']);

        if (in_array($statusFilter, ['online', 'unstable', 'offline'], true)) {
            $query->where('status', $statusFilter);
        }

        $devices = $query->orderBy('uid')->get();
        $filename = 'solar_trackers_'.Carbon::now()->format('Ymd_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($devices) {
            $handle = fopen('php://output', 'w');
            // Write UTF-8 BOM for Excel compatibility
            fwrite($handle, "\xEF\xBB\xBF");

            // CSV Header Row
            $csvHeaders = app()->getLocale() === 'en' ? [
                'UID',
                'Device Name',
                'Site',
                'Status',
                'Mode',
                'Firmware',
                'Wi-Fi RSSI (dBm)',
                'Last Telemetry (WIB)',
                'Pitch Elevation (°)',
                'Target Elevation (°)',
                'Azimuth (°)',
                'Target Azimuth (°)',
                'Actuator 1 Elevation State',
                'Actuator 2 Azimuth State',
                'DC Voltage (V)',
                'DC Current (A)',
                'DC Power (W)',
                'Daily Energy (kWh)',
                'Wind Speed (m/s)',
                'Enclosure Temp (°C)',
                'IMU Cal Sys',
                'IMU Cal Gyro',
                'IMU Cal Accel',
                'IMU Cal Mag',
                'GPS Satellites',
                'GPS Fix',
            ] : [
                'UID',
                'Nama Perangkat',
                'Lokasi Fasilitas',
                'Status',
                'Mode',
                'Firmware',
                'Wi-Fi RSSI (dBm)',
                'Telemetri Terakhir (WIB)',
                'Elevasi Pitch (°)',
                'Target Elevasi (°)',
                'Azimut (°)',
                'Target Azimut (°)',
                'Status Aktuator 1 Elevasi',
                'Status Aktuator 2 Azimut',
                'Tegangan DC (V)',
                'Arus DC (A)',
                'Daya DC (W)',
                'Energi Harian (kWh)',
                'Kecepatan Angin (m/s)',
                'Suhu Kotak (°C)',
                'Kalibrasi IMU Sys',
                'Kalibrasi IMU Gyro',
                'Kalibrasi IMU Aksel',
                'Kalibrasi IMU Mag',
                'Satelit GPS',
                'Sinyal GPS',
            ];

            fputcsv($handle, $csvHeaders);

            foreach ($devices as $device) {
                $tel = $device->latestTelemetry;

                fputcsv($handle, [
                    $device->uid,
                    $device->name,
                    $device->site?->name ?? (app()->getLocale() === 'id' ? 'Lokasi Default' : 'Default Site'),
                    strtoupper($device->status),
                    strtoupper($device->mode),
                    $device->firmware,
                    $device->rssi ?? '--',
                    $device->last_seen_at ? $device->last_seen_at->toIso8601String() : '--',
                    $tel ? number_format($tel->pitch_elev, 1, '.', '') : '--',
                    $tel ? number_format($tel->target_elev, 1, '.', '') : '--',
                    $tel ? number_format($tel->azimuth, 1, '.', '') : '--',
                    $tel ? number_format($tel->target_az, 1, '.', '') : '--',
                    $tel ? $tel->elev_state : '--',
                    $tel ? $tel->az_state : '--',
                    $tel ? number_format($tel->voltage, 2, '.', '') : '--',
                    $tel ? number_format($tel->current, 2, '.', '') : '--',
                    $tel ? number_format($tel->power, 1, '.', '') : '--',
                    $tel ? number_format($tel->energy_wh / 1000.0, 3, '.', '') : '--',
                    $tel ? number_format($tel->wind_speed, 1, '.', '') : '--',
                    $tel ? number_format($tel->temp_c, 1, '.', '') : '--',
                    $tel ? $tel->cal_sys : '--',
                    $tel ? $tel->cal_gyro : '--',
                    $tel ? $tel->cal_accel : '--',
                    $tel ? $tel->cal_mag : '--',
                    $tel ? $tel->satellites : '--',
                    $tel ? ($tel->fix ? '3D Fix' : 'No Fix') : '--',
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
