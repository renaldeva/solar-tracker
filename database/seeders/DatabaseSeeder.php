<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\Site;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Default Site (configured for ~130W dual-axis array system)
        $site = Site::firstOrCreate(
            ['name' => 'Cikarang Industrial Solar Pilot'],
            [
                'location' => 'West Java, Indonesia',
                'latitude' => -6.2944,
                'longitude' => 107.1539,
                'timezone' => 'Asia/Jakarta',
                'daily_energy_target_kwh' => 3.50,
            ]
        );

        $site->update([
            'daily_energy_target_kwh' => 3.50,
        ]);

        // 2. Initial Devices
        $dev1Settings = array_merge(Device::defaultSettings(), [
            'daily_energy_target_kwh' => 1.20,
            'misalignment_threshold_deg' => 5.0,
            'wind_stow_ms' => 15.0,
        ]);

        Device::updateOrCreate(
            ['uid' => 'ST-DEMO-001'],
            [
                'name' => 'Dual-Axis Pilot Array 01',
                'site_id' => $site->id,
                'mqtt_username' => 'device_ST-DEMO-001',
                'status' => 'offline',
                'mode' => 'auto',
                'firmware' => '1.0.0',
                'rssi' => null,
                'settings' => $dev1Settings,
            ]
        );

        Device::updateOrCreate(
            ['uid' => 'ST-PILOT-002'],
            [
                'name' => 'Dual-Axis Pilot Array 02 (South Field)',
                'site_id' => $site->id,
                'mqtt_username' => 'device_ST-PILOT-002',
                'status' => 'offline',
                'mode' => 'auto',
                'firmware' => '1.0.0',
                'rssi' => null,
                'settings' => $dev1Settings,
            ]
        );

        Device::updateOrCreate(
            ['uid' => 'ST-PILOT-003'],
            [
                'name' => 'Dual-Axis Test Bench (Rooftop)',
                'site_id' => $site->id,
                'mqtt_username' => 'device_ST-PILOT-003',
                'status' => 'offline',
                'mode' => 'auto',
                'firmware' => '1.0.0',
                'rssi' => null,
                'settings' => $dev1Settings,
            ]
        );

        // 3. Telemetry & Alerts seeders (clean / no dummy data)
        $this->call(TelemetrySeeder::class);
        $this->call(AlertSeeder::class);
    }
}
