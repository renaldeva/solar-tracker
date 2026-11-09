<?php

namespace App\Console\Commands;

use App\Models\Command as DeviceCommand;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckCommandTimeoutsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tracker:check-timeouts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Evaluate active commands and transition expired commands to timeout status';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $now = Carbon::now('UTC');

        $expiredCommands = DeviceCommand::whereIn('status', ['sent', 'accepted'])
            ->where(function ($query) use ($now) {
                $query->where('expires_at', '<', $now)
                    ->orWhere('issued_at', '<', $now->copy()->subSeconds(15));
            })
            ->get();

        $count = 0;
        foreach ($expiredCommands as $cmd) {
            $cmd->update([
                'status' => 'timeout',
                'reason' => 'Command timed out after 15 seconds with no terminal ACK received from device.',
                'completed_at' => $now,
            ]);
            $count++;
            $this->warn("Command {$cmd->id} ({$cmd->type}) marked as TIMEOUT");
        }

        if ($count > 0) {
            $this->info("Processed {$count} timed-out command(s).");
        }

        return Command::SUCCESS;
    }
}
