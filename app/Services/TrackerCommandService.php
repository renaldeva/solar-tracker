<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Command;
use App\Models\Device;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\MqttClient;
use Throwable;

class TrackerCommandService
{
    /**
     * Dispatch an operational command to a solar tracker node with full safety interlocks.
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function dispatch(Device $device, User $user, string $type, array $params = []): Command
    {
        // 1. Role Authorization Interlock
        if (! $user->canControl()) {
            AuditLog::record(
                $user->id,
                'command.rejected',
                Device::class,
                (string) $device->id,
                ['reason' => 'unauthorized_role', 'type' => $type]
            );

            throw new AuthorizationException('Unauthorized: Your role does not have permission to issue actuator control commands.');
        }

        // Validate command type
        $allowedTypes = ['set_mode', 'move_to', 'stop', 'stow', 'calibrate', 'set_config'];
        if (! in_array($type, $allowedTypes, true)) {
            throw ValidationException::withMessages([
                'type' => ["Unknown command type '{$type}'. Allowed: ".implode(', ', $allowedTypes)],
            ]);
        }

        $latestTelemetry = $device->telemetries()->latest('recorded_at')->first();
        $settings = $device->settings ?? Device::defaultSettings();
        $windStowThreshold = (float) ($settings['wind_stow_ms'] ?? 15.0);

        // 2. High Wind Safety Interlock
        $currentWind = $latestTelemetry?->wind_speed ?? 0.0;
        if ($currentWind >= $windStowThreshold && in_array($type, ['move_to', 'set_mode'], true)) {
            $requestedMode = $params['mode'] ?? '';
            if ($requestedMode !== 'stow') {
                AuditLog::record(
                    $user->id,
                    'command.interlock_blocked',
                    Device::class,
                    (string) $device->id,
                    [
                        'reason' => 'high_wind_interlock',
                        'wind_speed' => $currentWind,
                        'threshold' => $windStowThreshold,
                        'command_type' => $type,
                    ]
                );

                throw ValidationException::withMessages([
                    'interlock' => [
                        "Safety Interlock: High wind alert ({$currentWind} m/s >= {$windStowThreshold} m/s limit). Manual slewing is locked for aerodynamic safety.",
                    ],
                ]);
            }
        }

        // 3. Stale Telemetry Safety Interlock
        if ($type === 'move_to') {
            $telemetryAgeSeconds = $latestTelemetry ? abs(Carbon::now('UTC')->timestamp - $latestTelemetry->recorded_at->timestamp) : 999;
            if ($telemetryAgeSeconds > 15) {
                AuditLog::record(
                    $user->id,
                    'command.interlock_blocked',
                    Device::class,
                    (string) $device->id,
                    [
                        'reason' => 'stale_telemetry_interlock',
                        'telemetry_age_seconds' => $telemetryAgeSeconds,
                        'threshold' => 15,
                    ]
                );

                throw ValidationException::withMessages([
                    'interlock' => [
                        "Safety Interlock: Telemetry is stale ({$telemetryAgeSeconds}s since last packet > 15.0s limit). Movement rejected to prevent unmonitored actuator stroke.",
                    ],
                ]);
            }
        }

        // 4. Physical Travel Limits Interlock
        if ($type === 'move_to') {
            $elevMin = (float) ($settings['elev_min'] ?? 10.0);
            $elevMax = (float) ($settings['elev_max'] ?? 85.0);
            $azMin = (float) ($settings['az_min'] ?? 60.0);
            $azMax = (float) ($settings['az_max'] ?? 300.0);

            $targetElev = isset($params['elev']) ? (float) $params['elev'] : null;
            $targetAz = isset($params['az']) ? (float) $params['az'] : null;

            if ($targetElev === null || $targetAz === null) {
                throw ValidationException::withMessages([
                    'params' => ['Both target elevation (elev) and azimuth (az) coordinates are required for movement.'],
                ]);
            }

            if ($targetElev < $elevMin || $targetElev > $elevMax) {
                AuditLog::record(
                    $user->id,
                    'command.interlock_blocked',
                    Device::class,
                    (string) $device->id,
                    [
                        'reason' => 'elevation_limit_exceeded',
                        'target_elev' => $targetElev,
                        'bounds' => [$elevMin, $elevMax],
                    ]
                );

                throw ValidationException::withMessages([
                    'elev' => [
                        "Safety Interlock: Target elevation {$targetElev}° is out of physical travel bounds [{$elevMin}°, {$elevMax}°].",
                    ],
                ]);
            }

            if ($targetAz < $azMin || $targetAz > $azMax) {
                AuditLog::record(
                    $user->id,
                    'command.interlock_blocked',
                    Device::class,
                    (string) $device->id,
                    [
                        'reason' => 'azimuth_limit_exceeded',
                        'target_az' => $targetAz,
                        'bounds' => [$azMin, $azMax],
                    ]
                );

                throw ValidationException::withMessages([
                    'az' => [
                        "Safety Interlock: Target azimuth {$targetAz}° is out of mechanical travel bounds [{$azMin}°, {$azMax}°].",
                    ],
                ]);
            }
        }

        // 5. Create Command Record in Database
        $timeoutSeconds = (int) config('solar.command_timeout_seconds', 15);
        $command = Command::create([
            'device_id' => $device->id,
            'user_id' => $user->id,
            'type' => $type,
            'params' => $params,
            'status' => 'sent',
            'issued_at' => Carbon::now('UTC'),
            'expires_at' => Carbon::now('UTC')->addSeconds($timeoutSeconds),
        ]);

        // Record Audit Log
        AuditLog::record(
            $user->id,
            'tracker.command_issued',
            Device::class,
            (string) $device->id,
            [
                'command_id' => $command->id,
                'type' => $type,
                'params' => $params,
            ]
        );

        // 6. Transmit Command Payload via MQTT
        $this->publishToMqtt($device, $command, $type, $params);

        return $command;
    }

    /**
     * Publish command payload to device MQTT topic.
     */
    protected function publishToMqtt(Device $device, Command $command, string $type, array $params): void
    {
        if (app()->environment('testing')) {
            return;
        }

        $payload = json_encode([
            'cmd_id' => $command->id,
            'type' => $type,
            'params' => $params,
            'issued_at' => $command->issued_at->toIso8601String(),
            'expires_at' => $command->expires_at->toIso8601String(),
        ]);

        $host = config('mqtt.host') ?? config('mqtt-client.connections.default.host') ?? env('MQTT_HOST', '10.132.15.43');
        $port = (int) (config('mqtt.port') ?? config('mqtt-client.connections.default.port') ?? env('MQTT_PORT', 1884));
        $clientId = 'backend_cmd_'.substr(md5(uniqid('', true)), 0, 8);

        $authUsername = config('mqtt.username')
            ?? config('mqtt-client.connections.default.connection_settings.auth.username')
            ?? env('MQTT_USERNAME')
            ?? env('MQTT_AUTH_USERNAME', 'device_ST-DEMO-001');
        $authPassword = config('mqtt.password')
            ?? config('mqtt-client.connections.default.connection_settings.auth.password')
            ?? env('MQTT_PASSWORD')
            ?? env('MQTT_AUTH_PASSWORD', 'LHm8PSGX0wff9CkZ');

        try {
            $mqtt = new MqttClient($host, $port, $clientId);
            $settings = (new ConnectionSettings)
                ->setConnectTimeout(5)
                ->setSocketTimeout(5);

            if (! empty($authUsername)) {
                $settings->setUsername((string) $authUsername);
                if (! empty($authPassword)) {
                    $settings->setPassword((string) $authPassword);
                }
            }

            $mqtt->connect($settings, true);
            $topic = "tracker/{$device->uid}/cmd";
            $mqtt->publish($topic, $payload, 1);
            $mqtt->disconnect();
        } catch (Throwable $e) {
            // If in testing or broker temporary drop, log failure but don't crash
            $command->update([
                'status' => 'failed',
                'reason' => 'MQTT publish error: '.$e->getMessage(),
            ]);
        }
    }
}
