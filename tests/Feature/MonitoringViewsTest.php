<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\Device;
use App\Models\Site;
use App\Models\Telemetry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitoringViewsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Device $device;

    protected Site $site;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->operator()->create([
            'email' => 'operator_test@example.com',
        ]);

        $this->site = Site::create([
            'name' => 'Cikarang Pilot Site',
            'location' => 'West Java',
            'latitude' => -6.2944,
            'longitude' => 107.1539,
            'timezone' => 'Asia/Jakarta',
            'daily_energy_target_kwh' => 50.00,
        ]);

        $this->device = Device::create([
            'uid' => 'ST-DEMO-001',
            'name' => 'Dual-Axis Pilot Array 01',
            'site_id' => $this->site->id,
            'mqtt_username' => 'device_ST-DEMO-001',
            'status' => 'online',
            'mode' => 'auto',
            'firmware' => '1.0.0',
            'rssi' => -65,
            'last_seen_at' => Carbon::now(),
            'settings' => Device::defaultSettings(),
        ]);

        // Create sample telemetry
        Telemetry::create([
            'device_id' => $this->device->id,
            'recorded_at' => Carbon::now(),
            'received_at' => Carbon::now(),
            'firmware' => '1.0.0',
            'rssi' => -65,
            'uptime_s' => 3600,
            'mode' => 'auto',
            'voltage' => 48.2,
            'current' => 9.4,
            'power' => 453.08,
            'energy_wh' => 1250.0,
            'wind_speed' => 3.8,
            'pitch_elev' => 60.5,
            'azimuth' => 210.4,
            'roll' => 0.1,
            'cal_sys' => 3,
            'cal_gyro' => 3,
            'cal_accel' => 3,
            'cal_mag' => 3,
            'target_elev' => 60.5,
            'target_az' => 210.3,
            'elev_state' => 'idle',
            'az_state' => 'idle',
            'elev_current' => 0.0,
            'az_current' => 0.0,
            'latitude' => -6.2944,
            'longitude' => 107.1539,
            'altitude' => 35.0,
            'satellites' => 8,
            'fix' => true,
            'hdop' => 0.9,
            'temp_c' => 32.5,
            'drift_s' => 0,
        ]);
    }

    public function test_authenticated_user_can_view_dashboard_with_kpis(): void
    {
        $response = $this->actingAs($this->user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Industrial Solar Tracker Dashboard');
        $response->assertSee('Primary Array');
        $response->assertSee('ST-DEMO-001');
        $response->assertViewHas('kpis');
        $response->assertViewHas('chartData');
        $response->assertViewHas('primaryDevice');
        $response->assertViewHas('site');
        $response->assertViewHas('criticalAlerts');
    }

    public function test_authenticated_user_can_view_dashboard_with_device_offline_and_comm_loss_alerts(): void
    {
        Alert::create([
            'device_id' => $this->device->id,
            'severity' => 'critical',
            'code' => 'DEVICE_OFFLINE',
            'message' => 'Device ST-DEMO-001 connection lost (broker LWT or timeout)',
            'status' => 'open',
            'metadata' => [
                'status' => 'offline',
                'reason' => 'lwt_connection_lost',
                'nested' => ['foo' => 'bar'],
            ],
            'opened_at' => Carbon::now(),
        ]);

        Alert::create([
            'device_id' => $this->device->id,
            'severity' => 'critical',
            'code' => 'COMM_LOSS',
            'message' => 'Device ST-DEMO-001 connection lost (broker LWT or timeout)',
            'status' => 'open',
            'metadata' => [
                'status' => 'offline',
                'reason' => 'lwt_connection_lost',
            ],
            'opened_at' => Carbon::now(),
        ]);

        $response = $this->actingAs($this->user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('DEVICE_OFFLINE');
        $response->assertSee('COMM_LOSS');
    }

    public function test_authenticated_user_can_view_device_monitoring_and_filter_by_status(): void
    {
        $response = $this->actingAs($this->user)->get('/device-monitoring');

        $response->assertStatus(200);
        $response->assertSee('Device Monitoring');
        $response->assertSee('Online Trackers');
        $response->assertSee('Link Health');
        $response->assertSee('ST-DEMO-001');
        $response->assertSee('24-Hour Connectivity Timeline');
        $response->assertViewHas('totalTrackers', 1);
        $response->assertViewHas('onlineCount', 1);

        // Filter by status=online
        $responseOnline = $this->actingAs($this->user)->get('/device-monitoring?status=online');
        $responseOnline->assertStatus(200);
        $responseOnline->assertSee('ST-DEMO-001');

        // Filter by status=offline
        $responseOffline = $this->actingAs($this->user)->get('/device-monitoring?status=offline');
        $responseOffline->assertStatus(200);
        $responseOffline->assertSee('No tracker nodes found');
    }

    public function test_device_monitoring_csv_export_returns_streamed_csv(): void
    {
        $response = $this->actingAs($this->user)->get('/device-monitoring/export');

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        $this->assertStringContainsString('UID', $content);
        $this->assertStringContainsString('Device Name', $content);
        $this->assertStringContainsString('ST-DEMO-001', $content);
        $this->assertStringContainsString('Dual-Axis Pilot Array 01', $content);
        $this->assertStringContainsString('453.1', $content);
    }

    public function test_authenticated_user_can_view_solar_tracker_detail_and_kinematics(): void
    {
        $response = $this->actingAs($this->user)->get('/solar-tracker');

        $response->assertStatus(200);
        $response->assertSee('Dual-Axis Solar Tracker');
        $response->assertSee('Elevation Pitch Kinematics');
        $response->assertSee('360° Azimuth Compass Dial');
        $response->assertSee('BNO055 IMU Calibration Matrix');
        $response->assertSee('PZEM-017 DC Array');
        $response->assertSee('BTS7960 Actuators');
        $response->assertSee('ST-DEMO-001');

        $responseWithUid = $this->actingAs($this->user)->get('/solar-tracker?uid=ST-DEMO-001');
        $responseWithUid->assertStatus(200);
        $responseWithUid->assertViewHas('elevCurrent', 60.5);
        $responseWithUid->assertViewHas('azCurrent', 210.4);
        $responseWithUid->assertViewHas('isAligned', true);
    }
}
