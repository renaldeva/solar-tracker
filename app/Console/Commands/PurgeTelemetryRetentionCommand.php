<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\Telemetry;
use App\Models\TelemetryHourly;
use Carbon\Carbon;
use Illuminate\Console\Command;

class PurgeTelemetryRetentionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tracker:purge-retention 
                            {--days=30 : Days to retain high-frequency raw telemetry (default 30)} 
                            {--hourly-days=730 : Days to retain hourly aggregated telemetry (default 730/2 years)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Purge aged raw telemetry and hourly records according to data retention policy';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $rawDays = (int) $this->option('days');
        $hourlyDays = (int) $this->option('hourly-days');

        $now = Carbon::now('UTC');
        $rawCutoff = $now->copy()->subDays($rawDays);
        $hourlyCutoff = $now->copy()->subDays($hourlyDays);

        $this->line("Purging raw telemetry older than {$rawCutoff->toDateTimeString()} UTC ({$rawDays} days)...");
        $deletedRaw = Telemetry::where('recorded_at', '<', $rawCutoff)->delete();

        $this->line("Purging hourly aggregates older than {$hourlyCutoff->toDateTimeString()} UTC ({$hourlyDays} days)...");
        $deletedHourly = TelemetryHourly::where('hour_bucket', '<', $hourlyCutoff)->delete();

        AuditLog::record(
            null,
            'purge_retention',
            'telemetry',
            null,
            [
                'deleted_raw' => $deletedRaw,
                'deleted_hourly' => $deletedHourly,
                'raw_retention_days' => $rawDays,
                'hourly_retention_days' => $hourlyDays,
            ]
        );

        $this->info("Retention cleanup completed: {$deletedRaw} raw rows deleted, {$deletedHourly} hourly rows deleted.");

        return Command::SUCCESS;
    }
}
