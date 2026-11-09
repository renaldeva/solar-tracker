<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\AuditLog;
use App\Models\Device;
use App\Models\Site;
use App\Models\Telemetry;
use App\Models\TelemetryHourly;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseFiveEnergyAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Site $site;

    protected Device $device;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->operator()->create([
            'email' => 'operator_p5@example.com',
            'name' => 'Energy Operator',
        ]);

        $this->site = Site::create([
            'name' => 'Cikarang Industrial Solar Park',
            'location' => 'Bekasi, West Java',
            'latitude' => -6.2944,
            'longitude' => 107.1539,
            'timezone' => 'Asia/Jakarta',
            'daily_energy_target_kwh' => 50.0,
        ]);

        $this->device = Device::create([
            'uid' => 'ST-TEST-005',
            'name' => 'Tracker 05 Testing',
            'site_id' => $this->site->id,
            'mqtt_username' => 'device_ST-TEST-005',
            'status' => 'online',
            'mode' => 'auto',
            'firmware' => '1.2.0',
            'rssi' => -62,
            'last_seen_at' => Carbon::now('UTC'),
            'settings' => [
                'wind_stow_ms' => 15.0,
                'elev_min' => 10.0,
                'elev_max' => 85.0,
                'az_min' => 60.0,
                'az_max' => 300.0,
                'telemetry_interval_s' => 5,
                'measurement_label' => 'Solar array string A',
                'daily_energy_target_kwh' => 50.0,
            ],
        ]);
    }

    /**
     * Test 1: Connectivity watchdog updates device status and raises/resolves DEVICE_OFFLINE alerts.
     */
    public function test_connectivity_watchdog_detects_offline_devices_and_manages_alerts(): void
    {
        // Set last_seen_at to 200s ago (> 180s threshold)
        $this->device->update([
            'status' => 'online',
            'last_seen_at' => Carbon::now('UTC')->subSeconds(200),
        ]);

        $this->artisan('tracker:update-connectivity')
            ->assertSuccessful();

        $this->device->refresh();
        $this->assertEquals('offline', $this->device->status);

        // Assert DEVICE_OFFLINE alert raised
        $alert = Alert::where('device_id', $this->device->id)
            ->where('code', 'DEVICE_OFFLINE')
            ->where('status', 'open')
            ->first();

        $this->assertNotNull($alert);
        $this->assertEquals('warning', $alert->severity);

        // When device comes back online (< 45s ago)
        $this->device->update([
            'last_seen_at' => Carbon::now('UTC')->subSeconds(10),
            'rssi' => -60,
        ]);

        $this->artisan('tracker:update-connectivity')
            ->assertSuccessful();

        $this->device->refresh();
        $this->assertEquals('online', $this->device->status);

        // Assert DEVICE_OFFLINE alert auto-resolved
        $resolvedAlert = Alert::where('device_id', $this->device->id)
            ->where('code', 'DEVICE_OFFLINE')
            ->where('status', 'resolved')
            ->first();

        $this->assertNotNull($resolvedAlert);
    }

    /**
     * Test 2: Hourly aggregation calculates accurate avg/max power, energy delta, and uptime.
     */
    public function test_hourly_aggregation_computes_accurate_telemetry_metrics(): void
    {
        $targetHour = Carbon::now('UTC')->subHour()->startOfHour();

        // Seed 10 telemetry points within the target hour
        for ($i = 0; $i < 10; $i++) {
            Telemetry::create([
                'device_id' => $this->device->id,
                'recorded_at' => $targetHour->copy()->addMinutes($i * 5),
                'received_at' => $targetHour->copy()->addMinutes($i * 5),
                'voltage' => 220.0,
                'current' => 5.0,
                'power' => 1000.0 + ($i * 10), // 1000 to 1090 W
                'energy_wh' => 20000.0 + ($i * 50), // energy delta = 450 Wh
                'wind_speed' => 4.0 + ($i * 0.5), // max wind = 8.5 m/s
                'mode' => 'auto',
                'pitch_elev' => 45.0,
                'azimuth' => 180.0,
            ]);
        }

        $this->artisan('tracker:aggregate-hourly', ['--hours' => 2])
            ->assertSuccessful();

        $hourly = TelemetryHourly::where('device_id', $this->device->id)
            ->where('hour_bucket', $targetHour)
            ->first();

        $this->assertNotNull($hourly);
        $this->assertEquals(1045.0, $hourly->avg_power_w);
        $this->assertEquals(1090.0, $hourly->max_power_w);
        $this->assertEquals(1000.0, $hourly->min_power_w);
        $this->assertEquals(450.0, $hourly->energy_delta_wh);
        $this->assertEquals(8.5, $hourly->max_wind_ms);
        $this->assertEquals(10, $hourly->samples_count);
    }

    /**
     * Test 3: Data retention purge cleans up telemetry older than configured days.
     */
    public function test_retention_purge_cleans_aged_telemetry_and_records_audit(): void
    {
        $now = Carbon::now('UTC');

        // Create aged telemetry (40 days old)
        Telemetry::create([
            'device_id' => $this->device->id,
            'recorded_at' => $now->copy()->subDays(40),
            'received_at' => $now->copy()->subDays(40),
            'voltage' => 210.0,
            'power' => 800.0,
            'energy_wh' => 10000.0,
        ]);

        // Create fresh telemetry (2 days old)
        Telemetry::create([
            'device_id' => $this->device->id,
            'recorded_at' => $now->copy()->subDays(2),
            'received_at' => $now->copy()->subDays(2),
            'voltage' => 215.0,
            'power' => 900.0,
            'energy_wh' => 15000.0,
        ]);

        $this->artisan('tracker:purge-retention', ['--days' => 30, '--hourly-days' => 730])
            ->assertSuccessful();

        $this->assertEquals(1, Telemetry::where('device_id', $this->device->id)->count());

        $audit = AuditLog::where('action', 'purge_retention')->first();
        $this->assertNotNull($audit);
        $this->assertEquals(1, $audit->metadata['deleted_raw']);
    }

    /**
     * Test 4: Energy monitoring index and live JSON data endpoints.
     */
    public function test_energy_monitoring_page_and_live_data_endpoint(): void
    {
        Telemetry::create([
            'device_id' => $this->device->id,
            'recorded_at' => Carbon::now('UTC'),
            'received_at' => Carbon::now('UTC'),
            'voltage' => 230.5,
            'current' => 6.2,
            'power' => 1429.1,
            'energy_wh' => 52300.0,
            'wind_speed' => 5.8,
            'temp_c' => 32.4,
            'drift_s' => 1,
            'pitch_elev' => 42.0,
            'target_elev' => 42.5,
            'azimuth' => 195.0,
            'target_az' => 195.0,
            'cal_sys' => 3,
        ]);

        // Test HTML view
        $viewResponse = $this->actingAs($this->user)
            ->get(route('energy-monitoring', ['device' => $this->device->uid]));

        $viewResponse->assertOk();
        $viewResponse->assertSee('Energy Monitoring');
        $viewResponse->assertSee('Solar array string A');

        // Test Live JSON data endpoint
        $jsonResponse = $this->actingAs($this->user)
            ->get(route('energy-monitoring.data', ['device' => $this->device->uid]));

        $jsonResponse->assertOk();
        $jsonResponse->assertJsonStructure([
            'has_data',
            'pzem' => ['voltage', 'current', 'power_w', 'power_kw', 'energy_wh', 'energy_kwh', 'label'],
            'daily_energy_kwh',
            'monthly_energy_kwh',
            'daily_target_kwh',
            'progress_percent',
            'subsystem' => ['temp_c', 'drift_s', 'wind_ms', 'wind_stow_ms', 'elev', 'az'],
            'chart_24h' => ['labels', 'values'],
            'chart_compare' => ['labels', 'today', 'yesterday'],
        ]);

        $this->assertEquals(230.5, $jsonResponse->json('pzem.voltage'));
        $this->assertEquals(1429.1, $jsonResponse->json('pzem.power_w'));
    }

    /**
     * Test 5: Telemetry data endpoint and CSV export.
     */
    public function test_telemetry_explorer_endpoints_and_csv_export(): void
    {
        Telemetry::create([
            'device_id' => $this->device->id,
            'recorded_at' => Carbon::now('UTC')->subMinutes(5),
            'received_at' => Carbon::now('UTC')->subMinutes(5),
            'voltage' => 225.0,
            'current' => 4.5,
            'power' => 1012.5,
            'energy_wh' => 45000.0,
            'wind_speed' => 3.2,
            'mode' => 'auto',
            'pitch_elev' => 50.0,
            'azimuth' => 210.0,
            'temp_c' => 29.8,
            'fix' => true,
            'satellites' => 8,
            'cal_sys' => 3,
        ]);

        // Test Explorer Page
        $pageResponse = $this->actingAs($this->user)
            ->get(route('telemetry', ['device' => $this->device->uid, 'range' => '1h']));

        $pageResponse->assertOk();
        $pageResponse->assertSee('Telemetry Data Explorer');

        // Test Chart Series JSON Endpoint
        $dataResponse = $this->actingAs($this->user)
            ->get(route('telemetry.data', ['device' => $this->device->uid, 'range' => '1h', 'metrics' => 'power,wind_speed']));

        $dataResponse->assertOk();
        $dataResponse->assertJsonStructure([
            'labels',
            'datasets',
            'total_count',
        ]);

        // Test CSV Export
        $exportResponse = $this->actingAs($this->user)
            ->get(route('telemetry.export', ['device' => $this->device->uid, 'range' => '1h']));

        $exportResponse->assertOk();
        $exportResponse->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    /**
     * Test 6: Historical data query and CSV export.
     */
    public function test_historical_data_query_and_csv_export(): void
    {
        $hour = Carbon::now('UTC')->subHours(3)->startOfHour();
        TelemetryHourly::create([
            'device_id' => $this->device->id,
            'hour_bucket' => $hour,
            'avg_power_w' => 1100.0,
            'max_power_w' => 1250.0,
            'min_power_w' => 950.0,
            'energy_delta_wh' => 1100.0,
            'max_wind_ms' => 6.2,
            'avg_wind_ms' => 4.1,
            'uptime_percent' => 100.0,
            'samples_count' => 720,
        ]);

        // Test Historical Page (Hourly)
        $pageResponse = $this->actingAs($this->user)
            ->get(route('historical-data', ['device' => $this->device->uid, 'granularity' => 'hourly']));

        $pageResponse->assertOk();
        $pageResponse->assertSee('Historical Telemetry');
        $pageResponse->assertSee('1,100.0 W');

        // Test Historical CSV Export
        $csvResponse = $this->actingAs($this->user)
            ->get(route('historical-data.export', ['device' => $this->device->uid, 'granularity' => 'hourly']));

        $csvResponse->assertOk();
        $csvResponse->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    /**
     * Test 7: Reports generation and daily/monthly CSV export.
     */
    public function test_reports_generation_and_csv_export(): void
    {
        $hour = Carbon::now('UTC')->subHours(2)->startOfHour();
        TelemetryHourly::create([
            'device_id' => $this->device->id,
            'hour_bucket' => $hour,
            'avg_power_w' => 1200.0,
            'max_power_w' => 1300.0,
            'min_power_w' => 1000.0,
            'energy_delta_wh' => 2400.0, // 2.4 kWh
            'max_wind_ms' => 5.5,
            'avg_wind_ms' => 3.8,
            'uptime_percent' => 98.5,
            'samples_count' => 710,
        ]);

        // Test Reports Page (Daily)
        $dailyView = $this->actingAs($this->user)
            ->get(route('reports', ['type' => 'daily', 'date' => Carbon::now('Asia/Jakarta')->format('Y-m-d')]));

        $dailyView->assertOk();
        $dailyView->assertSee('Energy Production Reports');
        $dailyView->assertSee('Tracker 05 Testing');

        // Test Daily CSV Export
        $dailyExport = $this->actingAs($this->user)
            ->get(route('reports.export', ['type' => 'daily', 'date' => Carbon::now('Asia/Jakarta')->format('Y-m-d')]));

        $dailyExport->assertOk();
        $dailyExport->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        // Test Monthly CSV Export
        $monthlyExport = $this->actingAs($this->user)
            ->get(route('reports.export', ['type' => 'monthly', 'month' => Carbon::now('Asia/Jakarta')->format('Y-m')]));

        $monthlyExport->assertOk();
        $monthlyExport->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }
}
