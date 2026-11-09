<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\AuditLog;
use App\Models\Device;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AlertsController extends Controller
{
    /**
     * Display a listing of system alerts, filterable by severity, status, and device.
     */
    public function index(Request $request): View
    {
        $statusFilter = $request->query('status', 'all');
        $severityFilter = $request->query('severity', 'all');
        $deviceFilter = $request->query('device_id', 'all');
        $search = $request->query('q');

        $query = Alert::with(['device.site', 'acknowledgedBy']);

        if ($statusFilter === 'active') {
            $query->whereIn('status', ['open', 'acknowledged']);
        } elseif (in_array($statusFilter, ['open', 'acknowledged', 'resolved'], true)) {
            $query->where('status', $statusFilter);
        }

        if (in_array($severityFilter, ['critical', 'warning', 'info'], true)) {
            $query->where('severity', $severityFilter);
        }

        if ($deviceFilter !== 'all' && is_numeric($deviceFilter)) {
            $query->where('device_id', (int) $deviceFilter);
        }

        if (! empty($search)) {
            $searchTerm = '%'.strtolower($search).'%';
            $query->where(function ($q) use ($searchTerm) {
                $q->whereRaw('LOWER(code) LIKE ?', [$searchTerm])
                    ->orWhereRaw('LOWER(message) LIKE ?', [$searchTerm])
                    ->orWhereHas('device', function ($dq) use ($searchTerm) {
                        $dq->whereRaw('LOWER(uid) LIKE ?', [$searchTerm])
                            ->orWhereRaw('LOWER(name) LIKE ?', [$searchTerm]);
                    });
            });
        }

        // Order by open/active alerts first, then newest
        $alerts = $query->orderByRaw("CASE WHEN status = 'open' THEN 1 WHEN status = 'acknowledged' THEN 2 ELSE 3 END")
            ->orderByDesc('opened_at')
            ->paginate(15)
            ->withQueryString();

        // Calculate KPI Metrics
        $kpis = [
            'active_total' => Alert::whereIn('status', ['open', 'acknowledged'])->count(),
            'critical_open' => Alert::where('severity', 'critical')->whereIn('status', ['open', 'acknowledged'])->count(),
            'warning_open' => Alert::where('severity', 'warning')->whereIn('status', ['open', 'acknowledged'])->count(),
            'resolved_24h' => Alert::where('status', 'resolved')->where('resolved_at', '>=', Carbon::now('UTC')->subDay())->count(),
        ];

        $devices = Device::orderBy('name')->get();

        return view('alerts', [
            'alerts' => $alerts,
            'devices' => $devices,
            'kpis' => $kpis,
            'filters' => [
                'status' => $statusFilter,
                'severity' => $severityFilter,
                'device_id' => $deviceFilter,
                'q' => $search,
            ],
        ]);
    }

    /**
     * Acknowledge an active alert.
     */
    public function acknowledge(Request $request, Alert $alert): JsonResponse|RedirectResponse
    {
        if (! $request->user()?->canControl()) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
            }

            abort(403, 'Unauthorized to acknowledge alerts.');
        }

        $alert->update([
            'status' => 'acknowledged',
            'acknowledged_by' => $request->user()->id,
            'acknowledged_at' => Carbon::now('UTC'),
        ]);

        AuditLog::record(
            $request->user()->id,
            'alert.acknowledged',
            Alert::class,
            (string) $alert->id,
            [
                'code' => $alert->code,
                'severity' => $alert->severity,
                'device_id' => $alert->device_id,
                'device_uid' => $alert->device?->uid,
            ]
        );

        $msg = __('Alert #:id [:code] successfully acknowledged.', [
            'id' => $alert->id,
            'code' => $alert->code,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'status' => 'acknowledged',
                'acknowledged_by' => $request->user()->name,
                'acknowledged_at' => Carbon::now('Asia/Jakarta')->format('H.i').' WIB',
            ]);
        }

        return back()->with('success', $msg);
    }

    /**
     * Mark an alert as resolved manually.
     */
    public function resolve(Request $request, Alert $alert): JsonResponse|RedirectResponse
    {
        if (! $request->user()?->canControl()) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => __('Unauthorized action.')], 403);
            }

            abort(403, __('Unauthorized to resolve alerts.'));
        }

        $alert->update([
            'status' => 'resolved',
            'resolved_at' => Carbon::now('UTC'),
        ]);

        AuditLog::record(
            $request->user()->id,
            'alert.resolved',
            Alert::class,
            (string) $alert->id,
            [
                'code' => $alert->code,
                'severity' => $alert->severity,
                'device_id' => $alert->device_id,
                'device_uid' => $alert->device?->uid,
            ]
        );

        $msg = __('Alert #:id [:code] resolved.', [
            'id' => $alert->id,
            'code' => $alert->code,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'status' => 'resolved',
                'resolved_at' => Carbon::now('Asia/Jakarta')->format('H.i').' WIB',
            ]);
        }

        return back()->with('success', $msg);
    }
}
