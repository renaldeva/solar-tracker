<?php

namespace App\Console\Commands;

use App\Models\Device;
use Carbon\Carbon;
use Illuminate\Console\Command;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\MqttClient;
use Throwable;

class TrackerSimulateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tracker:simulate
                            {uid=ST-DEMO-001 : Unique hardware identifier of the solar tracker}
                            {--interval=5 : Telemetry reporting interval in seconds}
                            {--mode=auto : Initial tracking mode (auto, manual, stow)}
                            {--wind=3.5 : Base wind speed in m/s}
                            {--gust : Periodically simulate wind gusts over 15 m/s}
                            {--fault : Inject sensor fault simulation}
                            {--host= : Override MQTT broker host}
                            {--port= : Override MQTT broker port}
                            {--iterations=0 : Maximum iterations to run (0 for infinite)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Simulate physical ESP32 dual-axis solar tracker with realistic sensors and kinematics';

    /**
     * Tracking state.
     */
    protected string $uid;

    protected string $mode;

    protected float $currentElev = 35.0; // Panel elevation (pitch) in degrees

    protected float $currentAz = 110.0;  // Panel azimuth (heading) in degrees

    protected float $targetElev = 35.0;

    protected float $targetAz = 110.0;

    protected string $elevState = 'idle';

    protected string $azState = 'idle';

    protected float $elevCurrent = 0.05; // BTS7960 motor current (A)

    protected float $azCurrent = 0.05;   // BTS7960 motor current (A)

    protected float $cumulativeEnergyWh = 2450.0;

    protected int $uptime = 120;

    protected bool $running = true;

    // Safety and wind state
    protected bool $windStowActive = false;

    protected int $windStowHysteresisTicks = 0;

    protected string $preStowMode = 'auto';

    // Pending command tracking
    protected ?string $activeCommandId = null;

    protected ?float $commandTargetElev = null;

    protected ?float $commandTargetAz = null;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->uid = (string) $this->argument('uid');
        $this->mode = (string) $this->option('mode');
        $interval = (int) $this->option('interval');
        $maxIterations = (int) $this->option('iterations');

        $host = $this->option('host') ?: (config('mqtt-client.connections.default.host') ?: 'mosquitto');
        $port = (int) ($this->option('port') ?: (config('mqtt-client.connections.default.port') ?: 1883));

        // Use device specific credentials
        $username = "device_{$this->uid}";
        $password = 'DemoDeviceSecret123!';

        $this->info('================================================================================');
        $this->info(' SOLAR TRACKER HARDWARE SIMULATOR (ESP32 DUAL-AXIS PILOT)');
        $this->info('================================================================================');
        $this->line(" Device UID:      {$this->uid}");
        $this->line(" MQTT Broker:     {$host}:{$port}");
        $this->line(" Auth Username:   {$username}");
        $this->line(" Update Interval: {$interval}s");
        $this->line(" Initial Mode:    {$this->mode}");
        $this->line(' Gust Sim:        '.($this->option('gust') ? 'ENABLED (>15 m/s)' : 'DISABLED'));
        $this->info('--------------------------------------------------------------------------------');

        // Graceful signal handling
        if (extension_loaded('pcntl')) {
            pcntl_async_signals(true);
            pcntl_signal(SIGINT, fn () => $this->shutdown());
            pcntl_signal(SIGTERM, fn () => $this->shutdown());
        }

        $clientId = "sim_{$this->uid}_".substr(md5(uniqid('', true)), 0, 8);
        $mqtt = new MqttClient($host, $port, $clientId);

        // Configure Last Will & Testament (LWT) for broker
        $lwtTopic = "tracker/{$this->uid}/status";
        $lwtPayload = json_encode([
            'status' => 'offline',
            'timestamp' => time(),
            'reason' => 'lwt_connection_lost',
        ]);

        $settings = (new ConnectionSettings)
            ->setUsername($username)
            ->setPassword($password)
            ->setConnectTimeout(5)
            ->setSocketTimeout(5)
            ->setKeepAliveInterval(10)
            ->setLastWillTopic($lwtTopic)
            ->setLastWillMessage($lwtPayload)
            ->setLastWillQualityOfService(1)
            ->setRetainLastWill(true);

        $this->info("Connecting to Mosquitto broker as {$username}...");

        try {
            $mqtt->connect($settings, true);
        } catch (Throwable $e) {
            $this->error('Failed to connect to broker: '.$e->getMessage());

            return Command::FAILURE;
        }

        $this->info('Connected successfully!');

        // 1. Publish Retained Online Status
        $onlinePayload = json_encode([
            'status' => 'online',
            'timestamp' => time(),
            'firmware' => '1.0.0',
            'ip' => '192.168.1.120',
            'rssi' => -62,
            'uptime_s' => $this->uptime,
            'free_heap' => 184320,
        ]);
        $mqtt->publish($lwtTopic, $onlinePayload, 1, true);
        $this->info("Published retained ONLINE status to {$lwtTopic}");

        // 2. Subscribe to incoming commands
        $cmdTopic = "tracker/{$this->uid}/cmd";
        $mqtt->subscribe($cmdTopic, function (string $topic, string $message) use ($mqtt) {
            $this->handleIncomingCommand($mqtt, $message);
        }, 1);
        $this->info("Subscribed to command channel: {$cmdTopic}");

        $iteration = 0;
        $lastTelemetryTime = 0.0;
        $loopStartedAt = microtime(true);

        $this->info('Starting simulation loop. Press Ctrl+C to terminate.');
        $this->info('================================================================================');

        while ($this->running) {
            $currentTime = microtime(true);

            // Process MQTT incoming messages & network events non-blocking
            $mqtt->loopOnce($loopStartedAt, false, 20000);

            // Transmit periodic telemetry every $interval seconds
            if ($currentTime - $lastTelemetryTime >= $interval) {
                $lastTelemetryTime = $currentTime;
                $iteration++;

                $this->stepPhysics($interval);
                $this->publishTelemetry($mqtt);

                if ($maxIterations > 0 && $iteration >= $maxIterations) {
                    $this->info("Reached requested maximum iterations ({$maxIterations}). Stopping.");
                    break;
                }
            }

            usleep(50000); // 50ms loop cycle
        }

        // Clean disconnect: publish retained offline status before exit
        $offlinePayload = json_encode([
            'status' => 'offline',
            'timestamp' => time(),
            'reason' => 'normal_shutdown',
        ]);
        $mqtt->publish($lwtTopic, $offlinePayload, 1, true);
        $mqtt->disconnect();

        $this->info('Simulator exited cleanly.');

        return Command::SUCCESS;
    }

    /**
     * Advance simulation physics by $deltaSeconds.
     */
    protected function stepPhysics(int $deltaSeconds): void
    {
        $this->uptime += $deltaSeconds;

        // 1. Solar Ephemeris & Sun Position Calculation (Cikarang: -6.2944° S, 107.1539° E)
        // Simulate a daytime trajectory: peak elevation at noon, sweeps East to West.
        $now = Carbon::now('Asia/Jakarta');
        $hourFrac = $now->hour + ($now->minute / 60.0) + ($now->second / 3600.0);

        // Normalize daytime between 6:00 (dawn) and 18:00 (dusk)
        if ($hourFrac >= 6.0 && $hourFrac <= 18.0) {
            $dayProgress = ($hourFrac - 6.0) / 12.0; // 0.0 at dawn, 0.5 at noon, 1.0 at dusk
            $sunElev = 75.0 * sin($dayProgress * M_PI); // Peaks at 75° at noon
            $sunAz = 70.0 + ($dayProgress * 200.0);    // 70° (East) -> 170° (Noon) -> 270° (West)
        } else {
            // Night rest position
            $sunElev = 10.0;
            $sunAz = 90.0;
        }

        // 2. Wind Simulation & Gust Injection
        $baseWind = (float) $this->option('wind');
        $windSpeed = $baseWind + (sin($this->uptime / 15.0) * 1.5) + (mt_rand(-50, 50) / 100.0);
        $windSpeed = max(0.5, $windSpeed);

        // Inject severe gusts if requested
        if ($this->option('gust') && ($this->uptime % 60 >= 20 && $this->uptime % 60 <= 35)) {
            $windSpeed = 16.5 + (mt_rand(0, 20) / 10.0); // 16.5 - 18.5 m/s
        }

        // 3. Autonomous Wind Stow Safety Logic
        if ($windSpeed >= 15.0) {
            if (! $this->windStowActive) {
                $this->windStowActive = true;
                $this->preStowMode = $this->mode;
                $this->mode = 'stow';
                $this->windStowHysteresisTicks = 0;
                $this->warn(sprintf('!!! HIGH WIND TRIGGER: %.1f m/s >= 15.0 m/s. Autonomous stow engaged!', $windSpeed));
            }
        } elseif ($this->windStowActive) {
            if ($windSpeed < 12.0) {
                $this->windStowHysteresisTicks++;
                // Require 3 consecutive calm ticks (~15s) to recover
                if ($this->windStowHysteresisTicks >= 3) {
                    $this->windStowActive = false;
                    $this->mode = $this->preStowMode;
                    $this->info("Wind speed safe (<12.0 m/s). Restored tracking mode: {$this->mode}");
                }
            } else {
                $this->windStowHysteresisTicks = 0;
            }
        }

        // 4. Update Target Position based on Mode
        if ($this->mode === 'stow') {
            $this->targetElev = 10.0;
            $this->targetAz = 180.0;
        } elseif ($this->mode === 'auto') {
            $this->targetElev = max(10.0, min(85.0, $sunElev));
            $this->targetAz = max(60.0, min(300.0, $sunAz));
        } elseif ($this->mode === 'manual' && $this->commandTargetElev !== null) {
            $this->targetElev = $this->commandTargetElev;
            $this->targetAz = $this->commandTargetAz;
        }

        // 5. Actuator Kinematics & BTS7960 Drive (speed ~1.5°/s)
        $maxSpeed = 1.5 * $deltaSeconds; // max angle change this step

        // Elevation axis movement
        $elevDiff = $this->targetElev - $this->currentElev;
        if (abs($elevDiff) > 0.5) {
            $elevStep = max(-$maxSpeed, min($maxSpeed, $elevDiff));
            $this->currentElev += $elevStep;
            $this->elevState = $elevStep > 0 ? 'moving_up' : 'moving_down';
            $this->elevCurrent = 2.85 + (mt_rand(-20, 20) / 100.0);
        } else {
            $this->elevState = 'idle';
            $this->elevCurrent = 0.04;
        }

        // Azimuth axis movement
        $azDiff = $this->targetAz - $this->currentAz;
        if (abs($azDiff) > 0.5) {
            $azStep = max(-$maxSpeed, min($maxSpeed, $azDiff));
            $this->currentAz += $azStep;
            $this->azState = $azStep > 0 ? 'moving_west' : 'moving_east';
            $this->azCurrent = 3.10 + (mt_rand(-25, 25) / 100.0);
        } else {
            $this->azState = 'idle';
            $this->azCurrent = 0.04;
        }

        // 6. Check active command completion
        if ($this->activeCommandId !== null) {
            if ($this->elevState === 'idle' && $this->azState === 'idle') {
                $this->completeActiveCommand();
            }
        }

        // 7. PZEM-017 DC Solar Measurement Point (Single DC source: "Solar array")
        if ($sunElev > 5.0) {
            $elevRad = deg2rad($sunElev);
            $misalign = sqrt(pow($this->currentElev - $sunElev, 2) + pow($this->currentAz - $sunAz, 2));
            $alignFactor = max(0.0, cos(deg2rad(min(90.0, $misalign))));

            $power = 520.0 * sin($elevRad) * $alignFactor + (mt_rand(-8, 8) / 10.0);
            $power = max(0.0, $power);
            $voltage = 48.0 + (3.5 * sin($elevRad)) + (mt_rand(-20, 20) / 100.0);
            $current = $voltage > 0 ? ($power / $voltage) : 0.0;
        } else {
            $power = 0.0;
            $voltage = 14.2 + (mt_rand(-10, 10) / 100.0);
            $current = 0.0;
        }

        $this->cumulativeEnergyWh += ($power * $deltaSeconds) / 3600.0;

        $this->cachedPzem = [
            'voltage' => round($voltage, 2),
            'current' => round($current, 2),
            'power' => round($power, 2),
            'energy_wh' => round($this->cumulativeEnergyWh, 2),
        ];

        $this->cachedWind = [
            'speed_ms' => round($windSpeed, 2),
        ];
    }

    protected array $cachedPzem = [];

    protected array $cachedWind = [];

    protected ?MqttClient $mqttClientRef = null;

    /**
     * Assemble and publish high-resolution telemetry payload.
     */
    protected function publishTelemetry(MqttClient $mqtt): void
    {
        $this->mqttClientRef = $mqtt;

        // BNO055 IMU Readings
        $isFault = (bool) $this->option('fault');
        $cal = $isFault ? 0 : 3;

        $pitchElev = round($this->currentElev + (mt_rand(-5, 5) / 100.0), 2);
        $azimuth = round($this->currentAz + (mt_rand(-8, 8) / 100.0), 2);
        $roll = round(0.25 + (mt_rand(-5, 5) / 100.0), 2);

        // DS3231 RTC & Enclosure Temperature
        $sunFactor = max(0.0, sin(deg2rad($this->targetElev)));
        $tempC = round(28.0 + (12.0 * $sunFactor) + (mt_rand(-30, 30) / 100.0), 2);

        $payload = [
            'timestamp' => Carbon::now('UTC')->timestamp,
            'uptime_s' => $this->uptime,
            'firmware' => '1.0.0',
            'rssi' => -62 + mt_rand(-3, 3),
            'mode' => $this->mode,
            'pzem' => $this->cachedPzem,
            'wind' => $this->cachedWind,
            'imu' => [
                'pitch_elev' => $pitchElev,
                'azimuth' => $azimuth,
                'roll' => $roll,
                'cal_sys' => $cal,
                'cal_gyro' => $cal,
                'cal_accel' => $cal,
                'cal_mag' => $cal,
            ],
            'target' => [
                'elev' => round($this->targetElev, 2),
                'az' => round($this->targetAz, 2),
            ],
            'actuators' => [
                'elev_state' => $this->elevState,
                'az_state' => $this->azState,
                'elev_current' => round($this->elevCurrent, 2),
                'az_current' => round($this->azCurrent, 2),
            ],
            'gps' => [
                'lat' => -6.2944000,
                'lon' => 107.1539000,
                'alt' => 28.5,
                'satellites' => 9,
                'fix' => true,
                'hdop' => 1.08,
            ],
            'rtc' => [
                'temp_c' => $tempC,
                'drift_s' => 0,
            ],
            'flags' => [
                'wind_alarm' => $this->windStowActive,
                'limit_elev_min' => ($this->currentElev <= 10.1),
                'limit_elev_max' => ($this->currentElev >= 84.9),
                'limit_az_min' => ($this->currentAz <= 60.1),
                'limit_az_max' => ($this->currentAz >= 299.9),
                'actuator_fault' => false,
                'imu_fault' => $isFault,
                'pzem_fault' => false,
            ],
        ];

        $topic = "tracker/{$this->uid}/telemetry";
        $json = json_encode($payload);
        $mqtt->publish($topic, $json, 0);

        $this->line(sprintf(
            '[%s] #%d PUB %s: Mode=%s Elev=%.1f°(tgt:%.1f°) Az=%.1f°(tgt:%.1f°) Pwr=%.1fW Wind=%.1fm/s Act=(%s/%s)',
            Carbon::now('Asia/Jakarta')->format('H:i:s'),
            $this->uptime,
            $topic,
            $this->mode,
            $pitchElev,
            $this->targetElev,
            $azimuth,
            $this->targetAz,
            $this->cachedPzem['power'],
            $this->cachedWind['speed_ms'],
            $this->elevState,
            $this->azState
        ));
    }

    /**
     * Handle incoming command from web backend.
     */
    protected function handleIncomingCommand(MqttClient $mqtt, string $message): void
    {
        $payload = json_decode($message, true);
        if (! is_array($payload) || empty($payload['cmd_id'])) {
            $this->error('Received malformed command payload');

            return;
        }

        $cmdId = $payload['cmd_id'];
        $type = $payload['type'] ?? '';
        $params = $payload['params'] ?? [];

        $this->info(">>> CMD RECEIVED [{$cmdId}]: type={$type}");

        // 1. Check safety: If wind stow is active, reject movement/mode change
        if ($this->windStowActive && in_array($type, ['move_to', 'set_mode'])) {
            $this->publishAck($mqtt, $cmdId, 'rejected', 'Wind stow is active. Operator override blocked for safety.');

            return;
        }

        switch ($type) {
            case 'set_mode':
                $newMode = $params['mode'] ?? 'auto';
                if (! in_array($newMode, ['auto', 'manual', 'stow'])) {
                    $this->publishAck($mqtt, $cmdId, 'rejected', "Invalid mode '{$newMode}'. Allowed: auto, manual, stow.");

                    return;
                }
                $this->mode = $newMode;
                $this->publishAck($mqtt, $cmdId, 'accepted');
                $this->publishAck($mqtt, $cmdId, 'completed');
                $this->info("Mode switched to: {$this->mode}");
                break;

            case 'move_to':
                if ($this->mode !== 'manual') {
                    $this->publishAck($mqtt, $cmdId, 'rejected', "Tracker must be in 'manual' mode to accept move_to commands.");

                    return;
                }

                $elev = isset($params['elev']) ? (float) $params['elev'] : null;
                $az = isset($params['az']) ? (float) $params['az'] : null;

                if ($elev === null || $az === null) {
                    $this->publishAck($mqtt, $cmdId, 'rejected', 'Missing target elevation or azimuth parameters.');

                    return;
                }

                // Check physical hard limit bounds
                if ($elev < 10.0 || $elev > 85.0) {
                    $this->publishAck($mqtt, $cmdId, 'rejected', "Target elevation {$elev}° out of physical limits [10.0°, 85.0°].");

                    return;
                }
                if ($az < 60.0 || $az > 300.0) {
                    $this->publishAck($mqtt, $cmdId, 'rejected', "Target azimuth {$az}° out of physical limits [60.0°, 300.0°].");

                    return;
                }

                $this->activeCommandId = $cmdId;
                $this->commandTargetElev = $elev;
                $this->commandTargetAz = $az;

                $this->publishAck($mqtt, $cmdId, 'accepted');
                $this->info("Driving actuators to target: Elev={$elev}°, Az={$az}°");
                break;

            case 'stop':
                $this->elevState = 'idle';
                $this->azState = 'idle';
                $this->targetElev = $this->currentElev;
                $this->targetAz = $this->currentAz;
                $this->commandTargetElev = null;
                $this->commandTargetAz = null;

                $this->publishAck($mqtt, $cmdId, 'completed');
                $this->info('Actuators emergency stopped.');
                break;

            case 'stow':
                $this->mode = 'stow';
                $this->targetElev = 10.0;
                $this->targetAz = 180.0;
                $this->commandTargetElev = null;
                $this->commandTargetAz = null;
                $this->activeCommandId = $cmdId;

                $this->publishAck($mqtt, $cmdId, 'accepted');
                $this->info('Stow commanded. Moving to aerodynamic rest position (10°, 180°)...');
                break;

            case 'set_config':
                $this->publishAck($mqtt, $cmdId, 'completed');
                $this->info('Configuration updated successfully.');
                break;

            default:
                $this->publishAck($mqtt, $cmdId, 'rejected', "Unknown command type: {$type}");
                break;
        }
    }

    /**
     * Publish command acknowledgment.
     */
    protected function publishAck(MqttClient $mqtt, string $cmdId, string $status, ?string $reason = null): void
    {
        $ackTopic = "tracker/{$this->uid}/ack";
        $payload = [
            'cmd_id' => $cmdId,
            'status' => $status,
            'reason' => $reason,
            'timestamp' => time(),
            'current_state' => [
                'mode' => $this->mode,
                'pitch_elev' => round($this->currentElev, 2),
                'azimuth' => round($this->currentAz, 2),
            ],
        ];

        $mqtt->publish($ackTopic, json_encode($payload), 1);
        $this->line("<<< ACK SENT [{$cmdId}]: status={$status}".($reason ? " ({$reason})" : ''));
    }

    /**
     * Mark the currently active actuator move command as completed.
     */
    protected function completeActiveCommand(): void
    {
        if ($this->activeCommandId !== null && $this->mqttClientRef !== null) {
            $cmdId = $this->activeCommandId;
            $this->activeCommandId = null;
            $this->commandTargetElev = null;
            $this->commandTargetAz = null;

            $this->publishAck($this->mqttClientRef, $cmdId, 'completed');
            $this->info("Command {$cmdId} completed: Target position reached.");
        }
    }

    /**
     * Handle shutdown signal.
     */
    protected function shutdown(): void
    {
        $this->info('Termination signal received. Exiting simulation loop...');
        $this->running = false;
    }
}
