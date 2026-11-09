<?php

namespace App\Http\Controllers;

use App\Models\Command;
use App\Models\Device;
use App\Models\Telemetry;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SolarTrackerController extends Controller
{
    /**
     * Display the detailed dual-axis tracker orientation, kinematics, and telemetry view.
     */
    public function index(Request $request): View
    {
        $devices = Device::with(['site'])->orderBy('uid')->get();

        $selectedUid = $request->query('uid');
        $device = $devices->firstWhere('uid', $selectedUid) ?? $devices->first();

        if (! $device) {
            abort(404, 'No solar tracker devices registered in system.');
        }

        // Fetch latest telemetry for this device
        $telemetry = Telemetry::where('device_id', $device->id)
            ->latest('recorded_at')
            ->first();

        // Fetch recent telemetry log entries
        $recentTelemetries = Telemetry::where('device_id', $device->id)
            ->latest('recorded_at')
            ->limit(10)
            ->get();

        // Calculate alignment deviations
        $elevCurrent = $telemetry?->pitch_elev ?? 0.0;
        $elevTarget = $telemetry?->target_elev ?? 0.0;
        $azCurrent = $telemetry?->azimuth ?? 0.0;
        $azTarget = $telemetry?->target_az ?? 0.0;

        $elevDeviation = round($elevCurrent - $elevTarget, 2);
        $azDeviation = round($azCurrent - $azTarget, 2);
        $totalDeviation = round(sqrt(pow($elevDeviation, 2) + pow($azDeviation, 2)), 2);

        $threshold = (float) ($device->settings['misalignment_threshold_deg'] ?? 5.0);
        $isAligned = $totalDeviation <= $threshold;

        // Hardware safety limits
        $windStowThreshold = (float) ($device->settings['wind_stow_ms'] ?? 15.0);
        $isWindAlert = ($telemetry?->wind_speed ?? 0.0) >= $windStowThreshold;

        // IMU Calibration status dictionary
        $calLabels = [
            3 => ['label' => 'Full (3/3)', 'color' => 'bg-emerald-500', 'text' => 'text-emerald-700'],
            2 => ['label' => 'Good (2/3)', 'color' => 'bg-emerald-400', 'text' => 'text-emerald-600'],
            1 => ['label' => 'Low (1/3)', 'color' => 'bg-amber-400', 'text' => 'text-amber-700'],
            0 => ['label' => 'Uncalibrated (0/3)', 'color' => 'bg-rose-400', 'text' => 'text-rose-700'],
        ];

        // Telemetry lag
        $telemetryLag = $telemetry ? max(0, Carbon::now()->diffInSeconds($telemetry->recorded_at)) : null;

        // Fetch recent actuator commands
        $recentCommands = Command::where('device_id', $device->id)
            ->with('user')
            ->orderByDesc('issued_at')
            ->limit(5)
            ->get();

        return view('solar-tracker', [
            'devices' => $devices,
            'device' => $device,
            'telemetry' => $telemetry,
            'recentTelemetries' => $recentTelemetries,
            'recentCommands' => $recentCommands,
            'elevCurrent' => $elevCurrent,
            'elevTarget' => $elevTarget,
            'azCurrent' => $azCurrent,
            'azTarget' => $azTarget,
            'elevDeviation' => $elevDeviation,
            'azDeviation' => $azDeviation,
            'totalDeviation' => $totalDeviation,
            'threshold' => $threshold,
            'isAligned' => $isAligned,
            'windStowThreshold' => $windStowThreshold,
            'isWindAlert' => $isWindAlert,
            'calLabels' => $calLabels,
            'telemetryLag' => $telemetryLag,
        ]);
    }
}
