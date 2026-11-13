<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Command as DeviceCommand;
use App\Models\Device;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use PhpMqtt\Client\Facades\MQTT;
use Throwable;

class CommandController extends Controller
{
    /**
     * Store and publish a command via MQTT.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'uid' => 'required|string',
            'type' => 'required|string',
            'params' => 'nullable|array',
        ]);

        $uid = $validated['uid'];
        $type = $validated['type'];
        $params = $validated['params'] ?? [];
        $cmdId = (string) Str::uuid();

        $device = Device::where('uid', $uid)->first();
        if ($device) {
            DeviceCommand::create([
                'id' => $cmdId,
                'device_id' => $device->id,
                'type' => $type,
                'params' => $params,
                'status' => 'sent',
                'issued_at' => Carbon::now('UTC'),
                'expires_at' => Carbon::now('UTC')->addSeconds(15),
            ]);
        }

        $payload = json_encode([
            'cmd_id' => $cmdId,
            'type' => $type,
            'params' => $params,
            'issued_at' => Carbon::now('UTC')->toIso8601String(),
        ]);

        $published = false;
        try {
            $mqtt = MQTT::connection('default');
            $topic = "tracker/{$uid}/cmd";
            $mqtt->publish($topic, $payload, 1);
            $published = true;
        } catch (Throwable) {
            $published = false;
        }

        return response()->json([
            'success' => true,
            'cmd_id' => $cmdId,
            'published' => $published,
            'message' => 'Command queued and dispatched to device.',
        ]);
    }
}
