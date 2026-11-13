<?php

namespace App\Console\Commands;

use App\Jobs\ProcessTelemetry;
use App\Models\Telemetry;
use Illuminate\Console\Command;
use PhpMqtt\Client\Facades\MQTT;

class TestMqttInject extends Command
{
    protected $signature = 'test:mqtt-inject {--case=all}';

    protected $description = 'Inject synthetic telemetry to MQTT for E2E test';

    public function handle(): int
    {
        $uid = config('mqtt.device_uid', 'ST-DEMO-001');
        $client = MQTT::connection('default');
        $cases = [
            'full' => [
                'pzem' => ['voltage' => 99.99, 'current' => 8.88, 'power' => 888.88, 'energy_wh' => 1234],
                'wind' => ['speed_ms' => 7.77, 'speed_kmh' => 27.97, 'direction_deg' => 222],
                'flags' => [
                    'pzem_fault' => false, 'wind_fault' => false, 'imu_fault' => false,
                    'actuator_fault' => false, 'wind_alarm' => false,
                ],
            ],
            'no_pzem' => [
                'wind' => ['speed_ms' => 2.22, 'speed_kmh' => 7.99, 'direction_deg' => 90],
                'flags' => [
                    'pzem_fault' => true, 'wind_fault' => false, 'imu_fault' => false,
                    'actuator_fault' => false, 'wind_alarm' => false,
                ],
            ],
            'no_wind' => [
                'pzem' => ['voltage' => 11.11, 'current' => 1.11, 'power' => 12.32, 'energy_wh' => 300],
                'flags' => [
                    'pzem_fault' => false, 'wind_fault' => true, 'imu_fault' => false,
                    'actuator_fault' => false, 'wind_alarm' => false,
                ],
            ],
        ];

        foreach ($cases as $name => $case) {
            $payload = array_merge([
                'uid' => $uid,
                'firmware' => 'test-harness',
                'rssi' => -50,
                'uptime_s' => 1,
                'mode' => 'manual',
            ], $case);

            $client->publish("tracker/{$uid}/telemetry", json_encode($payload), 1);
            $this->info("published case: {$name}");

            // Ingest and process telemetry payload to verify E2E parser and database storage
            (new ProcessTelemetry($payload))->handle();

            sleep(1);

            $latest = Telemetry::latest('id')->first();
            if ($name === 'full') {
                $this->assert($latest->pzem_voltage == 99.99, 'pzem_voltage');
                $this->assert($latest->wind_speed_ms == 7.77, 'wind_speed_ms');
            }
            if ($name === 'no_pzem') {
                $this->assert($latest->pzem_fault === true, 'pzem_fault');
                $this->assert($latest->pzem_voltage === null, 'pzem_voltage null');
            }
            if ($name === 'no_wind') {
                $this->assert($latest->wind_fault === true, 'wind_fault');
                $this->assert($latest->wind_speed_ms === null, 'wind_speed_ms null');
            }
        }
        $this->info('ALL CASES OK');

        return self::SUCCESS;
    }

    private function assert(bool $ok, string $label): void
    {
        $this->line(($ok ? '✔ ' : '✘ ').$label);
        if (! $ok) {
            exit(1);
        }
    }
}
