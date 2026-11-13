<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\Telemetry;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class DiagnosticsController extends Controller
{
    /**
     * Show device diagnostics.
     */
    public function show(string $uid): JsonResponse
    {
        $device = Device::where('uid', $uid)->first();
        $latest = Telemetry::where(function ($query) use ($uid, $device) {
            $query->where('device_uid', $uid);
            if ($device) {
                $query->orWhere('device_id', $device->id);
            }
        })->latest('received_at')->first();

        if (! $latest) {
            $latest = Telemetry::latest('id')->first();
        }

        $lastSeen = $latest?->received_at ?? $device?->last_seen_at ?? Carbon::now('UTC');
        $secondsSinceLast = $lastSeen ? max(0, Carbon::now('UTC')->diffInSeconds($lastSeen)) : 0;

        $oneHourAgo = Carbon::now('UTC')->subHour();
        $messagesLastHour = Telemetry::where(function ($query) use ($uid, $device) {
            $query->where('device_uid', $uid);
            if ($device) {
                $query->orWhere('device_id', $device->id);
            }
        })->where('received_at', '>=', $oneHourAgo)->count();

        $pzemConnected = $latest ? (! $latest->pzem_fault && ($latest->pzem_voltage !== null || $latest->voltage !== null)) : false;
        $windConnected = $latest ? (! $latest->wind_fault && ($latest->wind_speed_ms !== null || $latest->wind_speed !== null)) : false;

        $pzemLast = [
            'voltage' => $latest?->pzem_voltage ?? $latest?->voltage ?? 0.0,
            'current' => $latest?->pzem_current ?? $latest?->current ?? 0.0,
            'power' => $latest?->pzem_power ?? $latest?->power ?? 0.0,
            'energy_wh' => $latest?->pzem_energy_wh ?? $latest?->energy_wh ?? 0.0,
        ];

        $windLast = [
            'speed_ms' => $latest?->wind_speed_ms ?? $latest?->wind_speed ?? 0.0,
            'speed_kmh' => $latest?->wind_speed_kmh ?? 0.0,
            'direction_deg' => $latest?->wind_direction_deg ?? 0.0,
        ];

        return response()->json([
            'uid' => $uid,
            'last_seen' => $lastSeen?->toIso8601String(),
            'seconds_since_last' => $secondsSinceLast,
            'mode' => $latest?->mode ?? $device?->mode ?? 'auto',
            'pzem_connected' => $pzemConnected,
            'wind_connected' => $windConnected,
            'messages_last_hour' => $messagesLastHour,
            'pzem_last' => $pzemLast,
            'wind_last' => $windLast,
        ]);
    }
}

