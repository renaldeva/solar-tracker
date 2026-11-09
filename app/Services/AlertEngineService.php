<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\Device;

class AlertEngineService
{
    /**
     * Evaluate real-time telemetry packet and raise or auto-resolve alerts based on rules.
     *
     * @return array<string, Alert|null>
     */
    public function evaluateTelemetry(Device $device, array $payload): array
    {
        $evaluated = [];
        $settings = $device->settings ?? Device::defaultSettings();
        $windStowThreshold = (float) ($settings['wind_stow_ms'] ?? config('solar.wind_stow_threshold_ms', 15.0));

        // 1. Wind Stow Condition (Critical)
        $windSpeed = isset($payload['wind']['speed_ms']) ? (float) $payload['wind']['speed_ms'] : null;
        if ($windSpeed !== null) {
            if ($windSpeed >= $windStowThreshold) {
                $evaluated['HIGH_WIND_STOW'] = Alert::raise(
                    $device->id,
                    'critical',
                    'HIGH_WIND_STOW',
                    sprintf('Wind speed %.1f m/s exceeded safe threshold (%.1f m/s). System in protective stow.', $windSpeed, $windStowThreshold),
                    ['wind_speed' => $windSpeed, 'threshold' => $windStowThreshold]
                );
            } elseif ($windSpeed < ($windStowThreshold - 3.0)) {
                $evaluated['HIGH_WIND_STOW'] = Alert::resolve($device->id, 'HIGH_WIND_STOW');
            }
        }

        // 2. IMU Calibration Loss (Warning)
        $calSys = isset($payload['imu']['cal_sys']) ? (int) $payload['imu']['cal_sys'] : null;
        if ($calSys !== null) {
            if ($calSys === 0) {
                $evaluated['IMU_UNCALIBRATED'] = Alert::raise(
                    $device->id,
                    'warning',
                    'IMU_UNCALIBRATED',
                    'BNO055 IMU sensor calibration dropped (0/3). Orientation readings may be inaccurate.',
                    ['cal_sys' => 0]
                );
            } elseif ($calSys >= 2) {
                $evaluated['IMU_UNCALIBRATED'] = Alert::resolve($device->id, 'IMU_UNCALIBRATED');
            }
        }

        // 3. Actuator Overcurrent (Critical)
        $elevCurrent = isset($payload['actuators']['elev_current']) ? (float) $payload['actuators']['elev_current'] : 0.0;
        $azCurrent = isset($payload['actuators']['az_current']) ? (float) $payload['actuators']['az_current'] : 0.0;
        $maxActuatorCurrent = 5.0; // Amperes

        if ($elevCurrent > $maxActuatorCurrent || $azCurrent > $maxActuatorCurrent) {
            $evaluated['ACTUATOR_OVERCURRENT'] = Alert::raise(
                $device->id,
                'critical',
                'ACTUATOR_OVERCURRENT',
                sprintf('Actuator motor current exceeded %.1fA threshold (Elev: %.2fA, Az: %.2fA).', $maxActuatorCurrent, $elevCurrent, $azCurrent),
                ['elev_current' => $elevCurrent, 'az_current' => $azCurrent, 'limit' => $maxActuatorCurrent]
            );
        } elseif ($elevCurrent <= 3.0 && $azCurrent <= 3.0) {
            $evaluated['ACTUATOR_OVERCURRENT'] = Alert::resolve($device->id, 'ACTUATOR_OVERCURRENT');
        }

        // 4. Actuator Stall / Hardware Fault Flag
        $flags = $payload['flags'] ?? [];
        if (! empty($flags['actuator_fault'])) {
            $evaluated['ACTUATOR_FAULT'] = Alert::raise(
                $device->id,
                'critical',
                'ACTUATOR_FAULT',
                'Actuator mechanical stall or driver error detected on BTS7960 H-bridge.',
                ['flags' => $flags]
            );
        } elseif (isset($flags['actuator_fault']) && ! $flags['actuator_fault']) {
            $evaluated['ACTUATOR_FAULT'] = Alert::resolve($device->id, 'ACTUATOR_FAULT');
        }

        // 5. PZEM Modbus Communication Fault
        if (! empty($flags['pzem_fault'])) {
            $evaluated['PZEM_FAULT'] = Alert::raise(
                $device->id,
                'warning',
                'PZEM_FAULT',
                'Modbus RTU communication failure with PZEM-017 DC power meter.',
                ['flags' => $flags]
            );
        } elseif (isset($flags['pzem_fault']) && ! $flags['pzem_fault']) {
            $evaluated['PZEM_FAULT'] = Alert::resolve($device->id, 'PZEM_FAULT');
        }

        // 6. RTC Time Drift (Warning)
        $driftSeconds = isset($payload['rtc']['drift_s']) ? (int) $payload['rtc']['drift_s'] : null;
        if ($driftSeconds !== null) {
            if (abs($driftSeconds) > 30) {
                $evaluated['RTC_DRIFT'] = Alert::raise(
                    $device->id,
                    'warning',
                    'RTC_DRIFT',
                    sprintf('RTC DS3231 time drift exceeded tolerance (%d seconds from NTP/GPS).', $driftSeconds),
                    ['drift_seconds' => $driftSeconds]
                );
            } elseif (abs($driftSeconds) <= 5) {
                $evaluated['RTC_DRIFT'] = Alert::resolve($device->id, 'RTC_DRIFT');
            }
        }

        // 7. GPS Fix Lost (Warning)
        if (isset($payload['gps']['fix'])) {
            $hasFix = (bool) $payload['gps']['fix'];
            $satellites = (int) ($payload['gps']['satellites'] ?? 0);

            if (! $hasFix && $satellites < 3) {
                $evaluated['GPS_NO_FIX'] = Alert::raise(
                    $device->id,
                    'warning',
                    'GPS_NO_FIX',
                    'GPS M8N satellite lock lost. System falls back to RTC astronomical coordinates.',
                    ['satellites' => $satellites, 'fix' => false]
                );
            } elseif ($hasFix && $satellites >= 4) {
                $evaluated['GPS_NO_FIX'] = Alert::resolve($device->id, 'GPS_NO_FIX');
            }
        }

        return $evaluated;
    }

    /**
     * Evaluate device online/offline status changes.
     */
    public function evaluateStatus(Device $device, string $status, ?array $metadata = null): ?Alert
    {
        if ($status === 'offline') {
            Alert::raise(
                $device->id,
                'warning',
                'DEVICE_OFFLINE',
                "Device {$device->uid} connection lost (broker LWT or timeout)",
                $metadata
            );

            return Alert::raise(
                $device->id,
                'warning',
                'COMM_LOSS',
                "Device {$device->uid} connection lost (broker LWT or timeout)",
                $metadata
            );
        }

        Alert::resolve($device->id, 'DEVICE_OFFLINE');

        return Alert::resolve($device->id, 'COMM_LOSS');
    }
}
