<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\Command as DeviceCommand;
use App\Models\Device;
use App\Models\Site;
use App\Models\Telemetry;
use App\Models\User;
use App\Services\AlertEngineService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommandControlTest extends TestCase
{
    use RefreshDatabase;

    protected User $operator;

    protected User $viewer;

    protected Site $site;

    protected Device $device;

    protected function setUp(): void
    {
        parent::setUp();

        $this->operator = User::factory()->operator()->create([
            'email' => 'operator_cmd@example.com',
            'name' => 'Field Operator',
        ]);

        $this->viewer = User::factory()->viewer()->create([
            'email' => 'viewer_cmd@example.com',
            'name' => 'Read-Only Viewer',
        ]);

        $this->site = Site::create([
            'name' => 'Cikarang Industrial Pilot',
            'location' => 'Bekasi, West Java',
            'latitude' => -6.2944,
            'longitude' => 107.1539,
            'timezone' => 'Asia/Jakarta',
            'daily_energy_target_kwh' => 45.0,
        ]);

        $this->device = Device::create([
            'uid' => 'ST-TEST-001',
            'name' => 'Test Tracker 01',
            'site_id' => $this->site->id,
            'mqtt_username' => 'device_ST-TEST-001',
            'status' => 'online',
            'mode' => 'auto',
            'firmware' => '1.0.0',
            'rssi' => -60,
            'last_seen_at' => Carbon::now('UTC'),
            'settings' => Device::defaultSettings(),
        ]);
    }

    /**
     * Seed fresh valid telemetry packet so stale interlock does not trigger.
     */
    protected function seedFreshTelemetry(float $windSpeed = 3.5, float $elev = 35.0, float $az = 120.0): Telemetry
    {
        return Telemetry::create([
            'device_id' => $this->device->id,
            'recorded_at' => Carbon::now('UTC'),
            'received_at' => Carbon::now('UTC'),
            'firmware' => '1.0.0',
            'rssi' => -60,
            'uptime_s' => 1200,
            'mode' => 'manual',
            'voltage' => 48.2,
            'current' => 5.1,
            'power' => 245.8,
            'energy_wh' => 1500.0,
            'wind_speed' => $windSpeed,
            'pitch_elev' => $elev,
            'azimuth' => $az,
            'roll' => 0.1,
            'cal_sys' => 3,
            'cal_gyro' => 3,
            'cal_accel' => 3,
            'cal_mag' => 3,
            'target_elev' => $elev,
            'target_az' => $az,
            'elev_state' => 'idle',
            'az_state' => 'idle',
            'elev_current' => 0.05,
            'az_current' => 0.05,
            'latitude' => -6.2944,
            'longitude' => 107.1539,
            'altitude' => 30.0,
            'satellites' => 8,
            'fix' => true,
            'hdop' => 1.0,
            'temp_c' => 31.5,
            'drift_s' => 0,
            'flags' => [],
        ]);
    }

    public function test_operator_can_dispatch_valid_actuator_move_command(): void
    {
        $this->seedFreshTelemetry(windSpeed: 4.2);

        $response = $this->actingAs($this->operator)
            ->postJson(route('commands.store', ['uid' => $this->device->uid]), [
                'type' => 'move_to',
                'params' => [
                    'elev' => 45.0,
                    'az' => 180.0,
                ],
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure(['command_id', 'status', 'issued_at']);

        $commandId = $response->json('command_id');
        $this->assertDatabaseHas('commands', [
            'id' => $commandId,
            'device_id' => $this->device->id,
            'user_id' => $this->operator->id,
            'type' => 'move_to',
            'status' => 'sent',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->operator->id,
            'action' => 'tracker.command_issued',
        ]);
    }

    public function test_viewer_is_forbidden_from_dispatching_commands(): void
    {
        $this->seedFreshTelemetry();

        $response = $this->actingAs($this->viewer)
            ->postJson(route('commands.store', ['uid' => $this->device->uid]), [
                'type' => 'set_mode',
                'params' => ['mode' => 'stow'],
            ]);

        $response->assertStatus(403);
        $this->assertDatabaseCount('commands', 0);
    }

    public function test_safety_interlock_blocks_movement_during_high_wind(): void
    {
        // Seed telemetry with wind speed 16.5 m/s (above 15.0 m/s threshold)
        $this->seedFreshTelemetry(windSpeed: 16.5);

        $response = $this->actingAs($this->operator)
            ->postJson(route('commands.store', ['uid' => $this->device->uid]), [
                'type' => 'move_to',
                'params' => [
                    'elev' => 40.0,
                    'az' => 150.0,
                ],
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);

        $this->assertStringContainsString('High wind alert', $response->json('message'));
        $this->assertDatabaseCount('commands', 0);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->operator->id,
            'action' => 'command.interlock_blocked',
        ]);
    }

    public function test_safety_interlock_blocks_movement_when_telemetry_is_stale(): void
    {
        // Telemetry recorded 25 seconds ago (stale limit is 15s)
        Telemetry::create([
            'device_id' => $this->device->id,
            'recorded_at' => Carbon::now('UTC')->subSeconds(25),
            'received_at' => Carbon::now('UTC')->subSeconds(25),
            'firmware' => '1.0.0',
            'wind_speed' => 2.0,
            'pitch_elev' => 35.0,
            'azimuth' => 110.0,
            'flags' => [],
        ]);

        $response = $this->actingAs($this->operator)
            ->postJson(route('commands.store', ['uid' => $this->device->uid]), [
                'type' => 'move_to',
                'params' => [
                    'elev' => 40.0,
                    'az' => 120.0,
                ],
            ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('Telemetry is stale', $response->json('message'));
        $this->assertDatabaseCount('commands', 0);
    }

    public function test_safety_interlock_blocks_out_of_bounds_angles(): void
    {
        $this->seedFreshTelemetry();

        // Target elevation 90.0° exceeds 85.0° hard limit
        $response = $this->actingAs($this->operator)
            ->postJson(route('commands.store', ['uid' => $this->device->uid]), [
                'type' => 'move_to',
                'params' => [
                    'elev' => 90.0,
                    'az' => 120.0,
                ],
            ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('out of physical travel bounds', $response->json('message'));

        // Target azimuth 45.0° exceeds 60.0° hard minimum limit
        $responseAz = $this->actingAs($this->operator)
            ->postJson(route('commands.store', ['uid' => $this->device->uid]), [
                'type' => 'move_to',
                'params' => [
                    'elev' => 45.0,
                    'az' => 45.0,
                ],
            ]);

        $responseAz->assertStatus(422);
        $this->assertStringContainsString('out of mechanical travel bounds', $responseAz->json('message'));
    }

    public function test_command_status_and_recent_endpoints(): void
    {
        $command = DeviceCommand::create([
            'device_id' => $this->device->id,
            'user_id' => $this->operator->id,
            'type' => 'set_mode',
            'params' => ['mode' => 'stow'],
            'status' => 'sent',
            'issued_at' => Carbon::now('UTC'),
            'expires_at' => Carbon::now('UTC')->addSeconds(15),
        ]);

        $statusResponse = $this->actingAs($this->operator)
            ->getJson(route('commands.status', ['id' => $command->id]));

        $statusResponse->assertStatus(200)
            ->assertJson([
                'command_id' => $command->id,
                'device_uid' => $this->device->uid,
                'type' => 'set_mode',
                'status' => 'sent',
                'is_terminal' => false,
            ]);

        $recentResponse = $this->actingAs($this->operator)
            ->getJson(route('commands.recent', ['uid' => $this->device->uid]));

        $recentResponse->assertStatus(200)
            ->assertJsonCount(1, 'commands');
    }

    public function test_command_timeout_watchdog_marks_expired_commands(): void
    {
        $expiredCmd = DeviceCommand::create([
            'device_id' => $this->device->id,
            'user_id' => $this->operator->id,
            'type' => 'move_to',
            'params' => ['elev' => 45.0, 'az' => 120.0],
            'status' => 'sent',
            'issued_at' => Carbon::now('UTC')->subSeconds(20),
            'expires_at' => Carbon::now('UTC')->subSeconds(5),
        ]);

        $this->artisan('tracker:check-timeouts')
            ->assertSuccessful();

        $expiredCmd->refresh();
        $this->assertEquals('timeout', $expiredCmd->status);
        $this->assertNotNull($expiredCmd->completed_at);
        $this->assertStringContainsString('timed out after 15 seconds', $expiredCmd->reason);
    }

    public function test_alert_engine_evaluates_and_deduplicates_conditions(): void
    {
        $engine = app(AlertEngineService::class);

        // 1. High wind creates Critical alert
        $payload1 = [
            'wind' => ['speed_ms' => 18.5],
            'imu' => ['cal_sys' => 3],
            'actuators' => ['elev_current' => 0.1, 'az_current' => 0.1],
        ];
        $engine->evaluateTelemetry($this->device, $payload1);

        $this->assertDatabaseHas('alerts', [
            'device_id' => $this->device->id,
            'code' => 'HIGH_WIND_STOW',
            'severity' => 'critical',
            'status' => 'open',
        ]);

        // Repeat payload: Must deduplicate (count remains 1)
        $engine->evaluateTelemetry($this->device, $payload1);
        $this->assertEquals(1, Alert::where('device_id', $this->device->id)->where('code', 'HIGH_WIND_STOW')->count());

        // 2. Wind drops below hysteresis (15 - 3 = 12 m/s): Auto-resolves alert
        $payload2 = [
            'wind' => ['speed_ms' => 8.0],
            'imu' => ['cal_sys' => 3],
        ];
        $engine->evaluateTelemetry($this->device, $payload2);

        $this->assertDatabaseHas('alerts', [
            'device_id' => $this->device->id,
            'code' => 'HIGH_WIND_STOW',
            'status' => 'resolved',
        ]);
    }

    public function test_operator_can_acknowledge_and_resolve_alerts(): void
    {
        $alert = Alert::raise(
            $this->device->id,
            'warning',
            'IMU_UNCALIBRATED',
            'BNO055 IMU sensor calibration dropped.'
        );

        $this->assertEquals('open', $alert->status);

        // Acknowledge
        $ackRes = $this->actingAs($this->operator)
            ->postJson(route('alerts.acknowledge', ['alert' => $alert->id]));

        $ackRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status' => 'acknowledged',
            ]);

        $alert->refresh();
        $this->assertEquals('acknowledged', $alert->status);
        $this->assertEquals($this->operator->id, $alert->acknowledged_by);

        // Resolve
        $resRes = $this->actingAs($this->operator)
            ->postJson(route('alerts.resolve', ['alert' => $alert->id]));

        $resRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status' => 'resolved',
            ]);

        $alert->refresh();
        $this->assertEquals('resolved', $alert->status);
        $this->assertNotNull($alert->resolved_at);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->operator->id,
            'action' => 'alert.resolved',
        ]);
    }

    public function test_viewer_cannot_acknowledge_alerts(): void
    {
        $alert = Alert::raise(
            $this->device->id,
            'warning',
            'IMU_UNCALIBRATED',
            'BNO055 IMU uncalibrated'
        );

        $response = $this->actingAs($this->viewer)
            ->postJson(route('alerts.acknowledge', ['alert' => $alert->id]));

        $response->assertStatus(403);
        $alert->refresh();
        $this->assertEquals('open', $alert->status);
    }
}
