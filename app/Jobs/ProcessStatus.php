<?php

namespace App\Jobs;

use App\Models\AuditLog;
use App\Models\Device;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public ?array $data) {}

    public function handle(): void
    {
        if (! is_array($this->data)) {
            return;
        }

        $uid = $this->data['uid'] ?? null;
        if (! $uid) {
            return;
        }

        $device = Device::where('uid', $uid)->first();
        if (! $device) {
            return;
        }

        $status = strtolower($this->data['status'] ?? 'online');
        $updates = [
            'status' => $status,
            'last_seen_at' => Carbon::now('UTC'),
        ];

        if (! empty($this->data['firmware'])) {
            $updates['firmware'] = $this->data['firmware'];
        }
        if (isset($this->data['rssi'])) {
            $updates['rssi'] = (int) $this->data['rssi'];
        }
        if (! empty($this->data['mode'])) {
            $updates['mode'] = $this->data['mode'];
        }

        $device->update($updates);

        AuditLog::record(
            userId: null,
            action: 'device.status_change',
            subjectType: Device::class,
            subjectId: (string) $device->id,
            metadata: $this->data
        );
    }
}
