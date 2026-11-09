<?php

namespace App\Console\Commands;

use App\Models\Alert;
use App\Models\Device;
use Carbon\Carbon;
use Illuminate\Console\Command;

class UpdateDeviceConnectivityCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tracker:update-connectivity';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update device connectivity states (online/unstable/offline) and manage DEVICE_OFFLINE alerts';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $now = Carbon::now('UTC');
        $devices = Device::all();

        $onlineCount = 0;
        $unstableCount = 0;
        $offlineCount = 0;

        foreach ($devices as $device) {
            $lastSeen = $device->last_seen_at;
            $secondsSinceSeen = $lastSeen ? abs($now->timestamp - $lastSeen->timestamp) : PHP_INT_MAX;
            $rssi = $device->rssi;

            $previousStatus = $device->status;

            // Connectivity thresholds from specification:
            // - online: last_seen < 45 s (and rssi >= -80)
            // - unstable: 45 <= last_seen <= 180 s OR rssi < -80
            // - offline: last_seen > 180 s (or never seen)
            if ($secondsSinceSeen > 180 || $lastSeen === null) {
                $newStatus = 'offline';
                $offlineCount++;
            } elseif ($secondsSinceSeen >= 45 || ($rssi !== null && $rssi < -80)) {
                $newStatus = 'unstable';
                $unstableCount++;
            } else {
                $newStatus = 'online';
                $onlineCount++;
            }

            if ($previousStatus !== $newStatus) {
                $device->update(['status' => $newStatus]);
                $this->info("Device {$device->uid} status updated: {$previousStatus} -> {$newStatus}");
            }

            // Manage DEVICE_OFFLINE alert lifecycle
            if ($newStatus === 'offline') {
                Alert::raise(
                    $device->id,
                    'warning',
                    'DEVICE_OFFLINE',
                    "Device {$device->uid} heartbeat lost (last seen {$secondsSinceSeen}s ago).",
                    [
                        'last_seen_at' => $lastSeen?->toIso8601String(),
                        'seconds_offline' => $secondsSinceSeen,
                        'rssi' => $rssi,
                    ]
                );
            } elseif ($newStatus === 'online') {
                Alert::resolve($device->id, 'DEVICE_OFFLINE');
            }
        }

        $this->line("Connectivity check complete: {$onlineCount} online, {$unstableCount} unstable, {$offlineCount} offline.");

        return Command::SUCCESS;
    }
}
