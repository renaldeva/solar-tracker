<?php

namespace App\Jobs;

use App\Models\AuditLog;
use App\Models\Command as DeviceCommand;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessAck implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public ?array $data) {}

    public function handle(): void
    {
        if (! is_array($this->data) || empty($this->data['cmd_id'])) {
            return;
        }

        $cmdId = $this->data['cmd_id'];
        $status = $this->data['status'] ?? 'accepted';
        $reason = $this->data['reason'] ?? null;
        $deviceUid = $this->data['uid'] ?? null;

        // Idempotency check in acks table
        if (DB::table('acks')->where('cmd_id', $cmdId)->exists()) {
            return;
        }

        DB::table('acks')->insert([
            'cmd_id' => $cmdId,
            'device_uid' => $deviceUid,
            'status' => $status,
            'reason' => $reason,
            'payload' => json_encode($this->data),
            'received_at' => Carbon::now('UTC'),
            'created_at' => Carbon::now('UTC'),
            'updated_at' => Carbon::now('UTC'),
        ]);

        $command = DeviceCommand::find($cmdId);
        if ($command) {
            $updates = [
                'status' => $status,
                'reason' => $reason,
            ];

            if ($status === 'accepted') {
                $updates['accepted_at'] = Carbon::now('UTC');
            } elseif (in_array($status, ['completed', 'rejected', 'failed'])) {
                $updates['completed_at'] = Carbon::now('UTC');
            }

            $command->update($updates);

            AuditLog::record(
                userId: null,
                action: "command.ack_{$status}",
                subjectType: DeviceCommand::class,
                subjectId: (string) $command->id,
                metadata: $this->data
            );
        }
    }
}
