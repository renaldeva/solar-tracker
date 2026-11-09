<?php

namespace App\Http\Controllers;

use App\Models\Command;
use App\Models\Device;
use App\Services\TrackerCommandService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class CommandController extends Controller
{
    public function __construct(
        protected TrackerCommandService $commandService
    ) {}

    /**
     * Dispatch an actuator or mode command to the specified tracker node.
     */
    public function store(Request $request, string $uid): JsonResponse
    {
        $device = Device::where('uid', $uid)->firstOrFail();

        $validated = $request->validate([
            'type' => 'required|string|in:set_mode,move_to,stop,stow,calibrate,set_config',
            'params' => 'nullable|array',
        ]);

        try {
            $command = $this->commandService->dispatch(
                $device,
                $request->user(),
                $validated['type'],
                $validated['params'] ?? []
            );

            return response()->json([
                'success' => true,
                'message' => __('Command successfully queued and dispatched to device channel.'),
                'command_id' => $command->id,
                'status' => $command->status,
                'issued_at' => $command->issued_at->toIso8601String(),
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        } catch (ValidationException $e) {
            $firstError = collect($e->errors())->flatten()->first() ?: __('Safety validation failed.');

            return response()->json([
                'success' => false,
                'message' => $firstError,
            ], 422);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Internal command execution error: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Poll the current execution status of an issued command UUID.
     */
    public function status(string $id): JsonResponse
    {
        $command = Command::with(['device', 'user'])->findOrFail($id);

        return response()->json([
            'command_id' => $command->id,
            'device_uid' => $command->device?->uid,
            'type' => $command->type,
            'params' => $command->params,
            'status' => $command->status,
            'reason' => $command->reason,
            'issued_at' => $command->issued_at?->timezone('Asia/Jakarta')->format('H:i:s'),
            'accepted_at' => $command->accepted_at?->timezone('Asia/Jakarta')->format('H:i:s'),
            'completed_at' => $command->completed_at?->timezone('Asia/Jakarta')->format('H:i:s'),
            'is_terminal' => in_array($command->status, ['completed', 'rejected', 'failed', 'timeout'], true),
        ]);
    }

    /**
     * Fetch recent commands log for a tracker node.
     */
    public function recent(string $uid): JsonResponse
    {
        $device = Device::where('uid', $uid)->firstOrFail();

        $commands = Command::where('device_id', $device->id)
            ->with('user')
            ->orderByDesc('issued_at')
            ->limit(10)
            ->get()
            ->map(function (Command $cmd) {
                return [
                    'id' => $cmd->id,
                    'type' => $cmd->type,
                    'params' => $cmd->params,
                    'status' => $cmd->status,
                    'reason' => $cmd->reason,
                    'issued_by' => $cmd->user?->name ?? 'System',
                    'issued_at' => $cmd->issued_at?->timezone('Asia/Jakarta')->format('d M H:i:s'),
                    'duration_s' => ($cmd->completed_at && $cmd->issued_at) ? $cmd->issued_at->diffInSeconds($cmd->completed_at) : null,
                ];
            });

        return response()->json([
            'commands' => $commands,
        ]);
    }
}
