<?php

namespace Tests\Feature;

use App\Console\Commands\MqttListenCommand;
use App\Models\Alert;
use App\Models\Command as DeviceCommand;
use App\Models\Device;
use App\Models\Site;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TelemetryIngestionTest extends TestCase
{
    use RefreshDatabase;

    protected Device $device;

    protected MqttListenCommand $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $site = Site::create([
            'name' => 'Test Solar Site',
            'location' => 'West Java',
            'latitude' => -6.2944,
            'longitude' => 107.1539,
            'timezone' => 'Asia/Jakarta',
            'daily_energy_target_kwh' => 45.0,
        ]);

        $this->device = Device::create([
            'uid' => 'ST-TEST-001',
            'name' => 'Unit Test Tracker',
            'site_id' => $site->id,
            'mqtt_username' => 'device_ST-TEST-001',
            'status' => 'offline',
            'mode' => 'auto',
            'firmware' => '1.0.0',
            'rssi' => -70,
            'settings' => Device::defaultSettings(),
        ]);

        $this->listener = app(MqttListenCommand::class);
    }

    public function test_telemetry_payload_is_stored_in_database(): void
    {
        $payload = [
            'timestamp' => 1776412800,
            'uptime_s' => 1250,
            'firmware' => '1.0.1',
            'rssi' => -63,
            'mode' => 'auto',
            'pzem' => [
                'voltage' => 48.50,
                'current' => 8.20,
                'power' => 397.70,
                'energy_wh' => 2150.40,
            ],
            'wind' => [
                'speed_ms' => 4.20,
            ],
            'imu' => [
                'pitch_elev' => 45.30,
                'azimuth' => 175.20,
                'roll' => 0.15,
                'cal_sys' => 3,
                'cal_gyro' => 3,
                'cal_accel' => 3,
                'cal_mag' => 3,
            ],
            'target' => [
                'elev' => 45.50,
                'az' => 175.00,
            ],
            'actuators' => [
                'elev_state' => 'idle',
                'az_state' => 'idle',
                'elev_current' => 0.05,
                'az_current' => 0.04,
            ],
            'gps' => [
                'lat' => -6.2944000,
                'lon' => 107.1539000,
                'alt' => 28.5,
                'satellites' => 9,
                'fix' => true,
                'hdop' => 1.05,
            ],
            'rtc' => [
                'temp_c' => 34.20,
                'drift_s' => 0,
            ],
            'flags' => [
                'wind_alarm' => false,
                'limit_elev_min' => false,
                'limit_elev_max' => false,
            ],
        ];

        $topic = "tracker/{$this->device->uid}/telemetry";
        $this->listener->handleTelemetry($topic, json_encode($payload));

        $this->assertDatabaseHas('telemetry', [
            'device_id' => $this->device->id,
            'firmware' => '1.0.1',
            'mode' => 'auto',
            'voltage' => 48.50,
            'current' => 8.20,
            'power' => 397.70,
            'energy_wh' => 2150.40,
            'wind_speed' => 4.20,
            'pitch_elev' => 45.30,
            'azimuth' => 175.20,
            'cal_sys' => 3,
            'temp_c' => 34.20,
            'fix' => true,
        ]);

        $this->device->refresh();
        $this->assertEquals('online', $this->device->status);
        $this->assertEquals('1.0.1', $this->device->firmware);
        $this->assertEquals(-63, $this->device->rssi);
        $this->assertNotNull($this->device->last_seen_at);
    }

    public function test_telemetry_with_missing_sensor_groups_does_not_fail(): void
    {
        $payload = [
            'timestamp' => 1776412810,
            'uptime_s' => 1260,
            'mode' => 'manual',
            // Missing pzem, imu, gps, rtc entirely
        ];

        $topic = "tracker/{$this->device->uid}/telemetry";
        $this->listener->handleTelemetry($topic, json_encode($payload));

        $this->assertDatabaseHas('telemetry', [
            'device_id' => $this->device->id,
            'mode' => 'manual',
            'voltage' => null,
            'pitch_elev' => null,
            'latitude' => null,
        ]);
    }

    public function test_status_lwt_offline_and_online_lifecycle(): void
    {
        $topic = "tracker/{$this->device->uid}/status";

        // 1. Device reports offline via LWT
        $offlinePayload = json_encode([
            'status' => 'offline',
            'timestamp' => time(),
            'reason' => 'lwt_connection_lost',
        ]);
        $this->listener->handleStatus($topic, $offlinePayload);

        $this->device->refresh();
        $this->assertEquals('offline', $this->device->status);

        $this->assertDatabaseHas('alerts', [
            'device_id' => $this->device->id,
            'code' => 'COMM_LOSS',
            'status' => 'open',
            'severity' => 'warning',
        ]);

        // 2. Device reconnects and reports online
        $onlinePayload = json_encode([
            'status' => 'online',
            'timestamp' => time(),
            'firmware' => '1.0.0',
        ]);
        $this->listener->handleStatus($topic, $onlinePayload);

        $this->device->refresh();
        $this->assertEquals('online', $this->device->status);

        $this->assertDatabaseHas('alerts', [
            'device_id' => $this->device->id,
            'code' => 'COMM_LOSS',
            'status' => 'resolved',
        ]);
    }

    public function test_high_wind_speed_raises_and_clears_stow_alert(): void
    {
        $topic = "tracker/{$this->device->uid}/telemetry";

        // 1. Wind speed above 15.0 m/s threshold
        $highWindPayload = [
            'timestamp' => time(),
            'mode' => 'stow',
            'wind' => ['speed_ms' => 16.8],
        ];
        $this->listener->handleTelemetry($topic, json_encode($highWindPayload));

        $this->assertDatabaseHas('alerts', [
            'device_id' => $this->device->id,
            'code' => 'HIGH_WIND_STOW',
            'status' => 'open',
            'severity' => 'critical',
        ]);

        // 2. Wind speed drops safely below threshold (< 12.0 m/s)
        $calmWindPayload = [
            'timestamp' => time() + 5,
            'mode' => 'auto',
            'wind' => ['speed_ms' => 4.5],
        ];
        $this->listener->handleTelemetry($topic, json_encode($calmWindPayload));

        $this->assertDatabaseHas('alerts', [
            'device_id' => $this->device->id,
            'code' => 'HIGH_WIND_STOW',
            'status' => 'resolved',
        ]);
    }

    public function test_imu_uncalibrated_raises_and_clears_alert(): void
    {
        $topic = "tracker/{$this->device->uid}/telemetry";

        // 1. IMU system cal is 0
        $uncalibratedPayload = [
            'timestamp' => time(),
            'imu' => ['cal_sys' => 0],
        ];
        $this->listener->handleTelemetry($topic, json_encode($uncalibratedPayload));

        $this->assertDatabaseHas('alerts', [
            'device_id' => $this->device->id,
            'code' => 'IMU_UNCALIBRATED',
            'status' => 'open',
            'severity' => 'warning',
        ]);

        // 2. IMU recovers calibration (>= 2)
        $calibratedPayload = [
            'timestamp' => time() + 5,
            'imu' => ['cal_sys' => 3],
        ];
        $this->listener->handleTelemetry($topic, json_encode($calibratedPayload));

        $this->assertDatabaseHas('alerts', [
            'device_id' => $this->device->id,
            'code' => 'IMU_UNCALIBRATED',
            'status' => 'resolved',
        ]);
    }

    public function test_command_ack_lifecycle(): void
    {
        $command = DeviceCommand::create([
            'device_id' => $this->device->id,
            'type' => 'move_to',
            'params' => ['elev' => 45.0, 'az' => 180.0],
            'status' => 'queued',
            'issued_at' => Carbon::now('UTC'),
        ]);

        $topic = "tracker/{$this->device->uid}/ack";

        // 1. Accepted ACK
        $this->listener->handleAck($topic, json_encode([
            'cmd_id' => $command->id,
            'status' => 'accepted',
        ]));

        $command->refresh();
        $this->assertEquals('accepted', $command->status);
        $this->assertNotNull($command->accepted_at);

        // 2. Completed ACK
        $this->listener->handleAck($topic, json_encode([
            'cmd_id' => $command->id,
            'status' => 'completed',
        ]));

        $command->refresh();
        $this->assertEquals('completed', $command->status);
        $this->assertNotNull($command->completed_at);
    }

    public function test_alert_business_rule_single_open_alert_per_code(): void
    {
        // First raise
        $alert1 = Alert::raise($this->device->id, 'warning', 'TEST_CODE', 'First message', ['attempt' => 1]);
        $this->assertEquals('open', $alert1->status);

        // Second raise of same code while first is open
        $alert2 = Alert::raise($this->device->id, 'critical', 'TEST_CODE', 'Updated message', ['attempt' => 2]);

        // Must be the SAME record updated, not a duplicate!
        $this->assertEquals($alert1->id, $alert2->id);
        $this->assertEquals('critical', $alert2->severity);
        $this->assertEquals('Updated message', $alert2->message);

        $this->assertEquals(1, Alert::where('device_id', $this->device->id)->where('code', 'TEST_CODE')->count());
    }
}
