<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\Site;
use App\Models\Telemetry;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiTelemetryTest extends TestCase
{
    use RefreshDatabase;

    protected Device $device;

    protected function setUp(): void
    {
        parent::setUp();

        $site = Site::create([
            'name' => 'Test Site',
            'location' => 'Java',
            'latitude' => -6.2,
            'longitude' => 107.1,
            'timezone' => 'Asia/Jakarta',
            'daily_energy_target_kwh' => 50.0,
        ]);

        $this->device = Device::create([
            'uid' => 'ST-DEMO-001',
            'name' => 'Demo Tracker',
            'site_id' => $site->id,
            'mqtt_username' => 'device_ST-DEMO-001',
            'status' => 'online',
            'mode' => 'auto',
            'firmware' => '1.2.0-esp32s3',
            'settings' => Device::defaultSettings(),
        ]);
    }

    public function test_api_latest_telemetry_returns_proper_pzem_and_wind(): void
    {
        Telemetry::create([
            'device_id' => $this->device->id,
            'device_uid' => 'ST-DEMO-001',
            'recorded_at' => Carbon::now('UTC'),
            'received_at' => Carbon::now('UTC'),
            'mode' => 'auto',
            'pzem_voltage' => 12.40,
            'pzem_current' => 1.20,
            'pzem_power' => 14.88,
            'pzem_energy_wh' => 320.00,
            'wind_speed_ms' => 3.40,
            'wind_speed_kmh' => 12.24,
            'wind_direction_deg' => 145.00,
            'pzem_fault' => false,
            'wind_fault' => false,
        ]);

        $response = $this->getJson('/api/v1/telemetry/latest');

        $response->assertStatus(200);
        $response->assertJsonPath('data.uid', 'ST-DEMO-001');
        $response->assertJsonPath('data.pzem.voltage', 12.4);
        $response->assertJsonPath('data.pzem.current', 1.2);
        $response->assertJsonPath('data.pzem.power', 14.88);
        $response->assertJsonPath('data.pzem.energy_wh', 320);
        $response->assertJsonPath('data.wind.speed_ms', 3.4);
        $response->assertJsonPath('data.wind.direction_deg', 145);
        $response->assertJsonPath('data.flags.pzem_fault', false);
        $response->assertJsonPath('data.flags.wind_fault', false);
    }

    public function test_api_latest_telemetry_returns_null_when_sensors_fault(): void
    {
        Telemetry::create([
            'device_id' => $this->device->id,
            'device_uid' => 'ST-DEMO-001',
            'recorded_at' => Carbon::now('UTC'),
            'received_at' => Carbon::now('UTC'),
            'mode' => 'manual',
            'pzem_voltage' => null,
            'wind_speed_ms' => null,
            'pzem_fault' => true,
            'wind_fault' => true,
        ]);

        $response = $this->getJson('/api/v1/telemetry/latest');

        $response->assertStatus(200);
        $response->assertJsonPath('data.pzem', null);
        $response->assertJsonPath('data.wind', null);
        $response->assertJsonPath('data.flags.pzem_fault', true);
        $response->assertJsonPath('data.flags.wind_fault', true);
    }

    public function test_api_diagnostics_endpoint_returns_diagnostics(): void
    {
        Telemetry::create([
            'device_id' => $this->device->id,
            'device_uid' => 'ST-DEMO-001',
            'recorded_at' => Carbon::now('UTC'),
            'received_at' => Carbon::now('UTC'),
            'mode' => 'auto',
            'pzem_voltage' => 12.40,
            'pzem_current' => 1.20,
            'pzem_power' => 14.88,
            'pzem_energy_wh' => 320.00,
            'wind_speed_ms' => 3.40,
            'wind_speed_kmh' => 12.24,
            'wind_direction_deg' => 145.00,
            'pzem_fault' => false,
            'wind_fault' => false,
        ]);

        $response = $this->getJson('/api/v1/diagnostics/ST-DEMO-001');

        $response->assertStatus(200);
        $response->assertJsonPath('uid', 'ST-DEMO-001');
        $response->assertJsonPath('pzem_connected', true);
        $response->assertJsonPath('wind_connected', true);
    }

    public function test_api_command_store(): void
    {
        $response = $this->postJson('/api/v1/command', [
            'uid' => 'ST-DEMO-001',
            'type' => 'set_mode',
            'params' => ['mode' => 'auto'],
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $this->assertNotEmpty($response->json('cmd_id'));
    }
}

