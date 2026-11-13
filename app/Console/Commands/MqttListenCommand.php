<?php

namespace App\Console\Commands;

use App\Jobs\ProcessAck;
use App\Jobs\ProcessStatus;
use App\Jobs\ProcessTelemetry;
use App\Models\Alert;
use App\Models\AuditLog;
use App\Models\Command as DeviceCommand;
use App\Models\Device;
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
                $uid = config('mqtt.device_uid', 'ST-DEMO-001');
                $host = config('mqtt.host', config('mqtt-client.connections.default.host', '10.132.15.43'));
                $port = config('mqtt.port', config('mqtt-client.connections.default.port', 1884));

                $this->info("Connecting to MQTT broker at {$host}:{$port}...");
                $mqtt = MQTT::connection('default');
                $this->info("Connected successfully. Subscribing to tracker/{$uid}/# with QoS 1...");

                // 1. Telemetry topic: tracker/{uid}/telemetry with QoS 1
                $mqtt->subscribe("tracker/{$uid}/telemetry", function (string $topic, string $message) {
                    $this->handleTelemetry($topic, $message);
                }, 1);

                // Wildcard subscription for other/additional tracker nodes
                $mqtt->subscribe('tracker/+/telemetry', function (string $topic, string $message) {
                    $this->handleTelemetry($topic, $message);
                }, 1);

                // 2. Status & LWT topic: tracker/{uid}/status with QoS 1
                $mqtt->subscribe('tracker/+/status', function (string $topic, string $message) {
                    $this->handleStatus($topic, $message);
                }, 1);

                // 3. Event topic: tracker/{uid}/event with QoS 1
                $mqtt->subscribe('tracker/+/event', function (string $topic, string $message) {
                    $this->handleEvent($topic, $message);
                }, 1);

                // 4. Command Ack topic: tracker/{uid}/ack with QoS 1
                $mqtt->subscribe('tracker/+/ack', function (string $topic, string $message) {
                    $this->handleAck($topic, $message);
                }, 1);

                $this->info("Subscribed to tracker/{$uid}/# (telemetry, status, event, ack) with QoS 1. Entering event loop.");

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

        $payload = json_decode($message, true);
        if (! is_array($payload)) {
            $this->error("Malformed JSON telemetry for device {$uid}");

            return;
        }

        $payload['uid'] = $payload['uid'] ?? $uid;
        $telemetry = (new ProcessTelemetry($payload))->handle();

        $this->line(sprintf(
            '[%s] Telemetry %s: Mode=%s Elev=%.1f° Az=%.1f° Pwr=%.1fW Wind=%.1fm/s',
            $telemetry->received_at?->toTimeString() ?? Carbon::now()->toTimeString(),
            $uid,
            $telemetry->mode ?? 'auto',
            $telemetry->pitch_elev ?? 0.0,
            $telemetry->azimuth ?? 0.0,
            $telemetry->pzem_power ?? $telemetry->power ?? 0.0,
            $telemetry->wind_speed_ms ?? $telemetry->wind_speed ?? 0.0
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

        $payload['uid'] = $payload['uid'] ?? $uid;
        (new ProcessStatus($payload))->handle();

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

        $payload['uid'] = $payload['uid'] ?? $uid;
        (new ProcessAck($payload))->handle();

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
