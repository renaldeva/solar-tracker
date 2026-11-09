<?php

namespace App\Console\Commands;

use App\Models\Device;
use App\Models\Telemetry;
use App\Models\TelemetryHourly;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AggregateHourlyTelemetryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tracker:aggregate-hourly 
                            {--hours=2 : Number of recent hours to aggregate} 
                            {--device= : Specific device UID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Aggregate raw 5s telemetry into hourly statistics (telemetry_hourly)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $hoursBack = (int) $this->option('hours');
        $deviceUid = $this->option('device');

        $devicesQuery = Device::query();
        if ($deviceUid) {
            $devicesQuery->where('uid', $deviceUid);
        }
        $devices = $devicesQuery->get();

        if ($devices->isEmpty()) {
            $this->warn('No devices found to aggregate.');

            return Command::SUCCESS;
        }

        $now = Carbon::now('UTC');
        $aggregatedCount = 0;

        // Iterate backwards through the requested hours
        for ($i = 0; $i < $hoursBack; $i++) {
            $hourTarget = $now->copy()->subHours($i);
            $startOfHour = $hourTarget->copy()->startOfHour();
            $endOfHour = $hourTarget->copy()->endOfHour();

            foreach ($devices as $device) {
                $telemetries = Telemetry::where('device_id', $device->id)
                    ->whereBetween('recorded_at', [$startOfHour, $endOfHour])
                    ->orderBy('recorded_at', 'asc')
                    ->get();

                $samplesCount = $telemetries->count();
                if ($samplesCount === 0) {
                    continue;
                }

                $avgPower = round((float) $telemetries->avg('power'), 2);
                $maxPower = round((float) $telemetries->max('power'), 2);
                $minPower = round((float) $telemetries->min('power'), 2);

                // Energy delta calculation:
                // PZEM-017 reports cumulative Wh. Difference between first and last reading represents generated energy.
                $firstEnergy = $telemetries->first()?->energy_wh;
                $lastEnergy = $telemetries->last()?->energy_wh;

                if ($firstEnergy !== null && $lastEnergy !== null && $lastEnergy >= $firstEnergy) {
                    $energyDeltaWh = round((float) ($lastEnergy - $firstEnergy), 2);
                } else {
                    $minEnergy = (float) $telemetries->min('energy_wh');
                    $maxEnergy = (float) $telemetries->max('energy_wh');
                    $energyDeltaWh = round(max(0.0, $maxEnergy - $minEnergy), 2);
                }

                $maxWind = round((float) $telemetries->max('wind_speed'), 2);
                $avgWind = round((float) $telemetries->avg('wind_speed'), 2);

                // Expected samples in 1 hour at 5s interval: 3600 / 5 = 720 samples
                $uptimePercent = round(min(100.0, ($samplesCount / 720.0) * 100.0), 2);

                TelemetryHourly::updateOrCreate(
                    [
                        'device_id' => $device->id,
                        'hour_bucket' => $startOfHour,
                    ],
                    [
                        'avg_power_w' => $avgPower,
                        'max_power_w' => $maxPower,
                        'min_power_w' => $minPower,
                        'energy_delta_wh' => max(0.0, $energyDeltaWh),
                        'max_wind_ms' => $maxWind,
                        'avg_wind_ms' => $avgWind,
                        'uptime_percent' => $uptimePercent,
                        'samples_count' => $samplesCount,
                    ]
                );

                $aggregatedCount++;
            }
        }

        $this->info("Hourly aggregation completed: {$aggregatedCount} bucket records processed.");

        return Command::SUCCESS;
    }
}
