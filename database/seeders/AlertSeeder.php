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

        // Clean up any stale open critical/warning alerts from previous seed runs
        Alert::whereIn('code', ['HIGH_WIND_STOW', 'COMM_LOSS', 'OVERCURRENT_MOTOR_ELEV'])->delete();

        // 1. Info status alert: Normal IMU calibration (open, nominal)
        Alert::updateOrCreate(
            ['device_id' => $dev1->id, 'code' => 'IMU_CALIBRATION_OK'],
            [
                'severity' => 'info',
                'message' => 'Sensor IMU 9-DOF BNO055 beroperasi pada kalibrasi penuh 3/3 di seluruh sensor (Sys, Gyro, Accel, Mag).',
                'status' => 'open',
                'metadata' => ['cal_sys' => 3, 'cal_gyro' => 3, 'cal_accel' => 3, 'cal_mag' => 3],
                'opened_at' => Carbon::now('UTC')->subHours(6),
            ]
        );

        // 2. Resolved routine safety alerts on dev2
        if ($dev2) {
            Alert::updateOrCreate(
                ['device_id' => $dev2->id, 'code' => 'HIGH_WIND_STOW_HIST'],
                [
                    'severity' => 'critical',
                    'message' => 'Proteksi mode aman aktif sementara: Hembusan angin 15.6 m/s mendeteksi batas aman. Sistem kembali normal.',
                    'status' => 'resolved',
                    'metadata' => ['gust_ms' => 15.6, 'threshold' => 15.0],
                    'opened_at' => Carbon::now('UTC')->subHours(14),
                    'resolved_at' => Carbon::now('UTC')->subHours(13)->addMinutes(42),
                ]
            );

            Alert::updateOrCreate(
                ['device_id' => $dev2->id, 'code' => 'IMU_CALIBRATION_VERIFIED'],
                [
                    'severity' => 'info',
                    'message' => 'Kalibrasi orientasi kinematika terverifikasi otomatis oleh sensor magnetometer.',
                    'status' => 'resolved',
                    'metadata' => ['cal_sys' => 3, 'cal_mag' => 3],
                    'opened_at' => Carbon::now('UTC')->subHours(10),
                    'resolved_at' => Carbon::now('UTC')->subHours(9)->addMinutes(50),
                ]
            );
        }

        // 3. Resolved alerts on dev3
        if ($dev3) {
            Alert::updateOrCreate(
                ['device_id' => $dev3->id, 'code' => 'COMM_RESTORED'],
                [
                    'severity' => 'info',
                    'message' => 'Konektivitas link broker MQTT berhasil dipulihkan dengan latensi normal (<15 ms).',
                    'status' => 'resolved',
                    'metadata' => ['rssi' => -64, 'latency_ms' => 12.4],
                    'opened_at' => Carbon::now('UTC')->subHours(8),
                    'resolved_at' => Carbon::now('UTC')->subHours(7)->addMinutes(55),
                ]
            );
        }

        // 4. Resolved hardware alerts on dev1
        Alert::updateOrCreate(
            ['device_id' => $dev1->id, 'code' => 'ACTUATOR_CURRENT_NOMINAL'],
            [
                'severity' => 'info',
                'message' => 'Pengujian beban motor aktuator elevasi dan azimut selesai. Arus motor normal 0.05A.',
                'status' => 'resolved',
                'metadata' => ['elev_current' => 0.05, 'az_current' => 0.04, 'limit' => 5.0],
                'opened_at' => Carbon::now('UTC')->subHours(4),
                'resolved_at' => Carbon::now('UTC')->subHours(3)->addMinutes(58),
            ]
        );

        Alert::updateOrCreate(
            ['device_id' => $dev1->id, 'code' => 'PZEM_DIAGNOSTIC_OK'],
            [
                'severity' => 'info',
                'message' => 'Diagnostik Modbus RTU PZEM-017 DC power meter normal. CRC check 100% valid.',
                'status' => 'resolved',
                'metadata' => ['retries' => 0, 'crc_ok' => true],
                'opened_at' => Carbon::now('UTC')->subHours(5),
                'resolved_at' => Carbon::now('UTC')->subHours(4)->addMinutes(50),
            ]
        );

        // 5. Seed sample executed commands for dev1
        DeviceCommand::updateOrCreate(
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

        DeviceCommand::updateOrCreate(
            ['id' => 'a0000000-0000-0000-0000-000000000002'],
            [
                'device_id' => $dev1->id,
                'user_id' => $operator?->id,
                'type' => 'move_to',
                'params' => ['elev' => 32.5, 'az' => 248.0],
                'status' => 'completed',
                'issued_at' => Carbon::now('UTC')->subMinutes(12),
                'expires_at' => Carbon::now('UTC')->subMinutes(11)->addSeconds(45),
                'accepted_at' => Carbon::now('UTC')->subMinutes(12)->addSeconds(1),
                'completed_at' => Carbon::now('UTC')->subMinutes(12)->addSeconds(5),
            ]
        );
    }
}
