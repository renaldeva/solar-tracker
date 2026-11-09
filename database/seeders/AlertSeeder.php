<?php

namespace Database\Seeders;

use App\Models\Alert;
use App\Models\Command as DeviceCommand;
use App\Models\Device;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AlertSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $dev1 = Device::where('uid', 'ST-DEMO-001')->first();
        $dev2 = Device::where('uid', 'ST-PILOT-002')->first();
        $dev3 = Device::where('uid', 'ST-PILOT-003')->first();
        $operator = User::where('role', 'operator')->first();

        if (! $dev1) {
            return;
        }

        // 1. Critical open alert on dev2
        if ($dev2) {
            Alert::updateOrCreate(
                ['device_id' => $dev2->id, 'code' => 'HIGH_WIND_STOW'],
                [
                    'severity' => 'critical',
                    'message' => 'Wind speed 17.8 m/s exceeded safe threshold (15.0 m/s). System in protective aerodynamic stow.',
                    'status' => 'open',
                    'metadata' => ['wind_speed' => 17.8, 'threshold' => 15.0, 'gust_peak' => 19.2],
                    'opened_at' => Carbon::now('UTC')->subMinutes(25),
                ]
            );

            // 2. Warning acknowledged alert on dev2
            Alert::updateOrCreate(
                ['device_id' => $dev2->id, 'code' => 'IMU_UNCALIBRATED'],
                [
                    'severity' => 'warning',
                    'message' => 'BNO055 IMU sensor calibration dropped (0/3). Orientation readings may require mechanical verification.',
                    'status' => 'acknowledged',
                    'metadata' => ['cal_sys' => 0, 'cal_mag' => 1],
                    'acknowledged_by' => $operator?->id,
                    'acknowledged_at' => Carbon::now('UTC')->subMinutes(15),
                    'opened_at' => Carbon::now('UTC')->subMinutes(45),
                ]
            );
        }

        // 3. Warning open alert on dev3 (offline)
        if ($dev3) {
            Alert::updateOrCreate(
                ['device_id' => $dev3->id, 'code' => 'COMM_LOSS'],
                [
                    'severity' => 'warning',
                    'message' => 'Device ST-PILOT-003 connection lost (broker LWT triggered)',
                    'status' => 'open',
                    'metadata' => ['last_rssi' => -92, 'lwt_reason' => 'keepalive_timeout'],
                    'opened_at' => Carbon::now('UTC')->subHours(18),
                ]
            );
        }

        // 4. Resolved critical alert on dev1
        Alert::updateOrCreate(
            ['device_id' => $dev1->id, 'code' => 'ACTUATOR_OVERCURRENT'],
            [
                'severity' => 'critical',
                'message' => 'Actuator motor current exceeded 5.0A threshold (Elev: 5.42A, Az: 0.12A).',
                'status' => 'resolved',
                'metadata' => ['elev_current' => 5.42, 'az_current' => 0.12, 'limit' => 5.0],
                'opened_at' => Carbon::now('UTC')->subHours(4),
                'resolved_at' => Carbon::now('UTC')->subHours(3)->addMinutes(45),
            ]
        );

        // 5. Resolved warning alert on dev1
        Alert::updateOrCreate(
            ['device_id' => $dev1->id, 'code' => 'PZEM_FAULT'],
            [
                'severity' => 'warning',
                'message' => 'Modbus RTU communication failure with PZEM-017 DC power meter.',
                'status' => 'resolved',
                'metadata' => ['retries' => 3, 'crc_errors' => 2],
                'opened_at' => Carbon::now('UTC')->subHours(8),
                'resolved_at' => Carbon::now('UTC')->subHours(7)->addMinutes(30),
            ]
        );

        // 6. Seed sample executed commands for dev1
        DeviceCommand::firstOrCreate(
            ['id' => 'a0000000-0000-0000-0000-000000000001'],
            [
                'device_id' => $dev1->id,
                'user_id' => $operator?->id,
                'type' => 'set_mode',
                'params' => ['mode' => 'auto'],
                'status' => 'completed',
                'issued_at' => Carbon::now('UTC')->subMinutes(30),
                'expires_at' => Carbon::now('UTC')->subMinutes(29)->addSeconds(45),
                'accepted_at' => Carbon::now('UTC')->subMinutes(30)->addSeconds(1),
                'completed_at' => Carbon::now('UTC')->subMinutes(30)->addSeconds(3),
            ]
        );

        DeviceCommand::firstOrCreate(
            ['id' => 'a0000000-0000-0000-0000-000000000002'],
            [
                'device_id' => $dev1->id,
                'user_id' => $operator?->id,
                'type' => 'move_to',
                'params' => ['elev' => 45.0, 'az' => 140.0],
                'status' => 'completed',
                'issued_at' => Carbon::now('UTC')->subMinutes(12),
                'expires_at' => Carbon::now('UTC')->subMinutes(11)->addSeconds(45),
                'accepted_at' => Carbon::now('UTC')->subMinutes(12)->addSeconds(1),
                'completed_at' => Carbon::now('UTC')->subMinutes(12)->addSeconds(8),
            ]
        );
    }
}
