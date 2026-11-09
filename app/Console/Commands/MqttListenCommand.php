<?php

namespace App\Console\Commands;

use App\Models\Alert;
use App\Models\AuditLog;
use App\Models\Command as DeviceCommand;
use App\Models\Device;
use App\Models\Site;
use App\Models\Telemetry;
use App\Services\AlertEngineService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use PhpMqtt\Client\Facades\MQTT;
use Throwable;

class MqttListenCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mqtt:listen';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ingest real-time telemetry, status, events, and acks from solar trackers';

    /**
     * Flag to control main execution loop.
     */
    protected bool $running = true;

    public function line($string, $style = null, $verbosity = null): void
    {
        if ($this->output) {
            parent::line($string, $style, $verbosity);
        }
    }

    public function info($string, $verbosity = null): void
    {
        if ($this->output) {
            parent::info($string, $verbosity);
        }
    }

    public function warn($string, $verbosity = null): void
    {
        if ($this->output) {
            parent::warn($string, $verbosity);
        }
    }

    public function error($string, $verbosity = null): void
    {
        if ($this->output) {
            parent::error($string, $verbosity);
        }
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting Solar Tracker MQTT Telemetry Listener...');

        // Register signal handlers for graceful shutdown if pcntl is available
        if (extension_loaded('pcntl')) {
            pcntl_async_signals(true);
            pcntl_signal(SIGINT, function () {
                $this->info('Shutdown signal received (SIGINT). Stopping listener...');
                $this->running = false;
            });
            pcntl_signal(SIGTERM, function () {
                $this->info('Shutdown signal received (SIGTERM). Stopping listener...');
                $this->running = false;
            });
        }

        while ($this->running) {
            try {
                $host = config('mqtt-client.connections.default.host', 'mosquitto');
                $port = config('mqtt-client.connections.default.port', 1883);

                $this->info("Connecting to MQTT broker at {$host}:{$port}...");
                $mqtt = MQTT::connection('default');
                $this->info('Connected successfully as solar_backend. Subscribing to topics...');

                // 1. Telemetry topic: tracker/{uid}/telemetry
                $mqtt->subscribe('tracker/+/telemetry', function (string $topic, string $message) {
                    $this->handleTelemetry($topic, $message);
                }, 0);

                // 2. Status & LWT topic: tracker/{uid}/status
                $mqtt->subscribe('tracker/+/status', function (string $topic, string $message) {
                    $this->handleStatus($topic, $message);
                }, 1);

                // 3. Event topic: tracker/{uid}/event
                $mqtt->subscribe('tracker/+/event', function (string $topic, string $message) {
                    $this->handleEvent($topic, $message);
                }, 1);

                // 4. Command Ack topic: tracker/{uid}/ack
                $mqtt->subscribe('tracker/+/ack', function (string $topic, string $message) {
                    $this->handleAck($topic, $message);
                }, 1);

                $this->info('Subscribed to tracker/+/telemetry, status, event, ack. Entering event loop.');

                // Run client loop
                $mqtt->loop(true);
            } catch (Throwable $e) {
                if (! $this->running) {
                    break;
                }

                $this->error('MQTT listener exception: '.$e->getMessage().'. Reconnecting in 3 seconds...');
                Log::warning('MQTT listener disconnected: '.$e->getMessage());

                try {
                    MQTT::disconnect('default');
                } catch (Throwable) {
                    // Ignore disconnect errors on already closed socket
                }

                sleep(3);
            }
        }

        $this->info('Solar Tracker MQTT Listener stopped cleanly.');

        return Command::SUCCESS;
    }

    /**
     * Handle incoming telemetry data packet.
     */
    public function handleTelemetry(string $topic, string $message): void
    {
        $uid = $this->extractUid($topic);
        if (! $uid) {
            return;
        }

        $device = Device::where('uid', $uid)->first();
        if (! $device) {
            $site = Site::first();
            if ($site) {
                $device = Device::create([
                    'uid' => $uid,
                    'name' => "Tracker {$uid}",
                    'site_id' => $site->id,
                    'mqtt_username' => "device_{$uid}",
                    'status' => 'online',
                    'mode' => 'auto',
                    'firmware' => '1.0.0',
                    'settings' => Device::defaultSettings(),
                ]);
                $this->info("Auto-provisioned new device in database: {$uid}");
            } else {
                $this->warn("Telemetry received for unknown device UID: {$uid}");

                return;
            }
        }

        $payload = json_decode($message, true);
        if (! is_array($payload)) {
            $this->error("Malformed JSON telemetry for device {$uid}");

            return;
        }

        // Parse timestamp safely from DS3231 RTC
        $recordedAt = Carbon::now('UTC');
        if (! empty($payload['timestamp'])) {
            if (is_numeric($payload['timestamp'])) {
                $recordedAt = Carbon::createFromTimestampUTC((int) $payload['timestamp']);
            } else {
                $recordedAt = Carbon::parse($payload['timestamp'])->setTimezone('UTC');
            }
        }

        $receivedAt = Carbon::now('UTC');

        // Extract sensor groups safely (never assume presence)
        $pzem = $payload['pzem'] ?? [];
        $wind = $payload['wind'] ?? [];
        $imu = $payload['imu'] ?? [];
        $target = $payload['target'] ?? [];
        $actuators = $payload['actuators'] ?? [];
        $gps = $payload['gps'] ?? [];
        $rtc = $payload['rtc'] ?? [];
        $flags = $payload['flags'] ?? [];

        // Ingest into telemetry table
        Telemetry::create([
            'device_id' => $device->id,
            'recorded_at' => $recordedAt,
            'received_at' => $receivedAt,
            'firmware' => $payload['firmware'] ?? $device->firmware,
            'rssi' => $payload['rssi'] ?? null,
            'uptime_s' => $payload['uptime_s'] ?? null,
            'mode' => $payload['mode'] ?? $device->mode,
            'voltage' => isset($pzem['voltage']) ? (float) $pzem['voltage'] : null,
            'current' => isset($pzem['current']) ? (float) $pzem['current'] : null,
            'power' => isset($pzem['power']) ? (float) $pzem['power'] : null,
            'energy_wh' => isset($pzem['energy_wh']) ? (float) $pzem['energy_wh'] : null,
            'wind_speed' => isset($wind['speed_ms']) ? (float) $wind['speed_ms'] : null,
            'pitch_elev' => isset($imu['pitch_elev']) ? (float) $imu['pitch_elev'] : null,
            'azimuth' => isset($imu['azimuth']) ? (float) $imu['azimuth'] : null,
            'roll' => isset($imu['roll']) ? (float) $imu['roll'] : null,
            'cal_sys' => isset($imu['cal_sys']) ? (int) $imu['cal_sys'] : null,
            'cal_gyro' => isset($imu['cal_gyro']) ? (int) $imu['cal_gyro'] : null,
            'cal_accel' => isset($imu['cal_accel']) ? (int) $imu['cal_accel'] : null,
            'cal_mag' => isset($imu['cal_mag']) ? (int) $imu['cal_mag'] : null,
            'target_elev' => isset($target['elev']) ? (float) $target['elev'] : null,
            'target_az' => isset($target['az']) ? (float) $target['az'] : null,
            'elev_state' => $actuators['elev_state'] ?? null,
            'az_state' => $actuators['az_state'] ?? null,
            'elev_current' => isset($actuators['elev_current']) ? (float) $actuators['elev_current'] : null,
            'az_current' => isset($actuators['az_current']) ? (float) $actuators['az_current'] : null,
            'latitude' => isset($gps['lat']) ? (float) $gps['lat'] : null,
            'longitude' => isset($gps['lon']) ? (float) $gps['lon'] : null,
            'altitude' => isset($gps['alt']) ? (float) $gps['alt'] : null,
            'satellites' => isset($gps['satellites']) ? (int) $gps['satellites'] : null,
            'fix' => ! empty($gps['fix']),
            'hdop' => isset($gps['hdop']) ? (float) $gps['hdop'] : null,
            'temp_c' => isset($rtc['temp_c']) ? (float) $rtc['temp_c'] : null,
            'drift_s' => isset($rtc['drift_s']) ? (int) $rtc['drift_s'] : null,
            'flags' => $flags,
            'raw_payload' => $payload,
        ]);

        // Update device real-time state
        $device->update([
            'last_seen_at' => $receivedAt,
            'status' => 'online',
            'firmware' => $payload['firmware'] ?? $device->firmware,
            'rssi' => $payload['rssi'] ?? $device->rssi,
            'mode' => $payload['mode'] ?? $device->mode,
        ]);

        // Real-time Safety Alert Checks
        $this->evaluateTelemetryAlerts($device, $payload);

        $this->line(sprintf(
            '[%s] Telemetry %s: Mode=%s Elev=%.1f° Az=%.1f° Pwr=%.1fW Wind=%.1fm/s',
            $receivedAt->toTimeString(),
            $uid,
            $payload['mode'] ?? 'auto',
            $imu['pitch_elev'] ?? 0.0,
            $imu['azimuth'] ?? 0.0,
            $pzem['power'] ?? 0.0,
            $wind['speed_ms'] ?? 0.0
        ));
    }

    /**
     * Handle device status & LWT messages.
     */
    public function handleStatus(string $topic, string $message): void
    {
        $uid = $this->extractUid($topic);
        if (! $uid) {
            return;
        }

        $device = Device::where('uid', $uid)->first();
        if (! $device) {
            return;
        }

        $payload = json_decode($message, true);
        if (! is_array($payload)) {
            return;
        }

        $status = $payload['status'] ?? 'offline';

        $device->update([
            'status' => $status,
            'last_seen_at' => Carbon::now('UTC'),
            'firmware' => $payload['firmware'] ?? $device->firmware,
            'rssi' => $payload['rssi'] ?? $device->rssi,
        ]);

        $alertEngine = app(AlertEngineService::class);
        $alertEngine->evaluateStatus($device, $status, $payload);

        if ($status === 'offline') {
            $this->warn("Device {$uid} went OFFLINE (LWT)");
        } else {
            $this->info("Device {$uid} reported ONLINE");
        }
    }

    /**
     * Handle device event messages.
     */
    public function handleEvent(string $topic, string $message): void
    {
        $uid = $this->extractUid($topic);
        if (! $uid) {
            return;
        }

        $device = Device::where('uid', $uid)->first();
        if (! $device) {
            return;
        }

        $payload = json_decode($message, true);
        if (! is_array($payload)) {
            return;
        }

        $type = strtoupper($payload['type'] ?? 'SYSTEM_EVENT');
        $severity = $payload['severity'] ?? 'info';
        $msg = $payload['message'] ?? "Device event: {$type}";

        if (in_array($severity, ['critical', 'warning'])) {
            Alert::raise($device->id, $severity, $type, $msg, $payload['data'] ?? []);
        }

        AuditLog::record(
            userId: null,
            action: 'device.event',
            subjectType: Device::class,
            subjectId: (string) $device->id,
            metadata: $payload
        );

        $this->info("Device {$uid} EVENT: [{$severity}] {$msg}");
    }

    /**
     * Handle command acknowledgment messages from devices.
     */
    public function handleAck(string $topic, string $message): void
    {
        $uid = $this->extractUid($topic);
        if (! $uid) {
            return;
        }

        $payload = json_decode($message, true);
        if (! is_array($payload) || empty($payload['cmd_id'])) {
            return;
        }

        $cmdId = $payload['cmd_id'];
        $status = $payload['status'] ?? 'accepted';
        $reason = $payload['reason'] ?? null;

        $command = DeviceCommand::find($cmdId);
        if (! $command) {
            $this->warn("ACK received for unknown command UUID: {$cmdId}");

            return;
        }

        $updates = [
            'status' => $status,
            'reason' => $reason,
        ];

        if ($status === 'accepted') {
            $updates['accepted_at'] = Carbon::now('UTC');
        } elseif (in_array($status, ['completed', 'rejected', 'failed'])) {
            $updates['completed_at'] = Carbon::now('UTC');
        }

        $command->update($updates);

        $this->info("Command {$cmdId} ACK: status={$status}".($reason ? " (reason: {$reason})" : ''));
    }

    /**
     * Evaluate telemetry for automatic safety alert transitions.
     */
    protected function evaluateTelemetryAlerts(Device $device, array $payload): void
    {
        $alertEngine = app(AlertEngineService::class);
        $alertEngine->evaluateTelemetry($device, $payload);
    }

    /**
     * Extract the device UID from the topic.
     * Topic format: tracker/{uid}/{channel}
     */
    protected function extractUid(string $topic): ?string
    {
        $parts = explode('/', $topic);

        return $parts[1] ?? null;
    }
}
