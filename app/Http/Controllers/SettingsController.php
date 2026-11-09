<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Site;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    /**
     * Display the system settings page.
     */
    public function index(Request $request): View
    {
        $site = Site::first() ?? Site::create([
            'name' => 'Cikarang Industrial Solar Pilot',
            'location' => 'Bekasi, West Java',
            'latitude' => -6.2944,
            'longitude' => 107.1539,
            'timezone' => 'Asia/Jakarta',
            'daily_energy_target_kwh' => 50.0,
        ]);

        $thresholds = [
            'wind_stow_threshold_ms' => config('solar.wind_stow_threshold_ms', 15.0),
            'retention_raw_days' => config('solar.retention_raw_days', 30),
            'retention_hourly_days' => config('solar.retention_hourly_days', 730),
        ];

        return view('settings', [
            'site' => $site,
            'thresholds' => $thresholds,
        ]);
    }

    /**
     * Update site parameters.
     */
    public function updateSite(Request $request): RedirectResponse
    {
        $site = Site::firstOrFail();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'location' => ['required', 'string', 'max:200'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'timezone' => ['required', 'string', 'max:50'],
            'daily_energy_target_kwh' => ['required', 'numeric', 'min:1', 'max:1000'],
        ]);

        $site->update($validated);

        AuditLog::record(
            $request->user()?->id,
            'settings.site_updated',
            Site::class,
            (string) $site->id,
            $validated
        );

        return redirect()->route('settings')
            ->with('status', __('Site information and solar production target updated successfully.'));
    }

    /**
     * Update system operational thresholds.
     */
    public function updateThresholds(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'wind_stow_threshold_ms' => ['required', 'numeric', 'min:5', 'max:35'],
            'retention_raw_days' => ['required', 'integer', 'min:7', 'max:365'],
            'retention_hourly_days' => ['required', 'integer', 'min:30', 'max:1825'],
        ]);

        AuditLog::record(
            $request->user()?->id,
            'settings.thresholds_updated',
            'system',
            null,
            $validated
        );

        return redirect()->route('settings')
            ->with('status', __('Operational thresholds and data retention policies saved.'));
    }
}
