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
        // 1. Default Site
        $site = Site::firstOrCreate(
            ['name' => 'Cikarang Industrial Solar Pilot'],
            [
                'location' => 'West Java, Indonesia',
                'latitude' => -6.2944,
                'longitude' => 107.1539,
                'timezone' => 'Asia/Jakarta',
                'daily_energy_target_kwh' => 50.00,
            ]
        );

        // 2. Demo Devices
        Device::firstOrCreate(
            ['uid' => 'ST-DEMO-001'],
            [
                'name' => 'Dual-Axis Pilot Array 01',
                'site_id' => $site->id,
                'mqtt_username' => 'device_ST-DEMO-001',
                'status' => 'online',
                'mode' => 'auto',
                'firmware' => '1.0.0',
                'rssi' => -65,
                'settings' => Device::defaultSettings(),
            ]
        );

        Device::firstOrCreate(
            ['uid' => 'ST-PILOT-002'],
            [
                'name' => 'Dual-Axis Pilot Array 02 (South Field)',
                'site_id' => $site->id,
                'mqtt_username' => 'device_ST-PILOT-002',
                'status' => 'unstable',
                'mode' => 'auto',
                'firmware' => '1.0.0',
                'rssi' => -79,
                'last_seen_at' => now()->subMinutes(12),
                'settings' => Device::defaultSettings(),
            ]
        );

        Device::firstOrCreate(
            ['uid' => 'ST-PILOT-003'],
            [
                'name' => 'Dual-Axis Test Bench (Rooftop)',
                'site_id' => $site->id,
                'mqtt_username' => 'device_ST-PILOT-003',
                'status' => 'offline',
                'mode' => 'stow',
                'firmware' => '0.9.8',
                'rssi' => -92,
                'last_seen_at' => now()->subHours(18),
                'settings' => Device::defaultSettings(),
            ]
        );

        // 3. Seed Telemetry & Hourly Rollups
        $this->call(TelemetrySeeder::class);

        // 4. Seed Alerts and Commands
        $this->call(AlertSeeder::class);
    }
}
