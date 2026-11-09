<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Device;
use App\Models\Site;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DeviceManagementController extends Controller
{
    /**
     * Display a listing of tracker devices.
     */
    public function index(Request $request): View
    {
        $devices = Device::with('site')->orderBy('name')->get();
        $sites = Site::orderBy('name')->get();

        return view('device-management', [
            'devices' => $devices,
            'sites' => $sites,
        ]);
    }

    /**
     * Store a newly created device in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'uid' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:devices,uid'],
            'name' => ['required', 'string', 'max:100'],
            'site_id' => ['required', 'exists:sites,id'],
            'mqtt_username' => ['nullable', 'string', 'max:100'],
            'elev_min' => ['required', 'numeric', 'min:0', 'max:45'],
            'elev_max' => ['required', 'numeric', 'min:50', 'max:90'],
            'az_min' => ['required', 'numeric', 'min:0', 'max:180'],
            'az_max' => ['required', 'numeric', 'min:180', 'max:360'],
            'wind_stow_ms' => ['required', 'numeric', 'min:5', 'max:35'],
            'telemetry_interval_s' => ['required', 'integer', 'min:1', 'max:60'],
            'measurement_label' => ['required', 'string', 'max:100'],
            'daily_energy_target_kwh' => ['required', 'numeric', 'min:1', 'max:500'],
        ]);

        $settings = [
            'elev_min' => (float) $validated['elev_min'],
            'elev_max' => (float) $validated['elev_max'],
            'az_min' => (float) $validated['az_min'],
            'az_max' => (float) $validated['az_max'],
            'wind_stow_ms' => (float) $validated['wind_stow_ms'],
            'telemetry_interval_s' => (int) $validated['telemetry_interval_s'],
            'measurement_label' => $validated['measurement_label'],
            'daily_energy_target_kwh' => (float) $validated['daily_energy_target_kwh'],
            'heading_offset' => 0.0,
            'misalignment_threshold_deg' => 5.0,
        ];

        $device = Device::create([
            'uid' => strtoupper($validated['uid']),
            'name' => $validated['name'],
            'site_id' => $validated['site_id'],
            'mqtt_username' => ($validated['mqtt_username'] ?? null) ?: 'device_'.strtoupper($validated['uid']),
            'status' => 'offline',
            'mode' => 'auto',
            'settings' => $settings,
        ]);

        AuditLog::record(
            $request->user()?->id,
            'device.created',
            Device::class,
            (string) $device->id,
            ['uid' => $device->uid, 'name' => $device->name]
        );

        return redirect()->route('device-management')
            ->with('status', __('Tracker device :name (:uid) registered successfully.', ['name' => $device->name, 'uid' => $device->uid]));
    }

    /**
     * Update the specified device in storage.
     */
    public function update(Request $request, Device $device): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'site_id' => ['required', 'exists:sites,id'],
            'mqtt_username' => ['nullable', 'string', 'max:100'],
            'elev_min' => ['required', 'numeric', 'min:0', 'max:45'],
            'elev_max' => ['required', 'numeric', 'min:50', 'max:90'],
            'az_min' => ['required', 'numeric', 'min:0', 'max:180'],
            'az_max' => ['required', 'numeric', 'min:180', 'max:360'],
            'wind_stow_ms' => ['required', 'numeric', 'min:5', 'max:35'],
            'telemetry_interval_s' => ['required', 'integer', 'min:1', 'max:60'],
            'measurement_label' => ['required', 'string', 'max:100'],
            'daily_energy_target_kwh' => ['required', 'numeric', 'min:1', 'max:500'],
        ]);

        $settings = array_merge($device->settings ?? Device::defaultSettings(), [
            'elev_min' => (float) $validated['elev_min'],
            'elev_max' => (float) $validated['elev_max'],
            'az_min' => (float) $validated['az_min'],
            'az_max' => (float) $validated['az_max'],
            'wind_stow_ms' => (float) $validated['wind_stow_ms'],
            'telemetry_interval_s' => (int) $validated['telemetry_interval_s'],
            'measurement_label' => $validated['measurement_label'],
            'daily_energy_target_kwh' => (float) $validated['daily_energy_target_kwh'],
        ]);

        $device->update([
            'name' => $validated['name'],
            'site_id' => $validated['site_id'],
            'mqtt_username' => ($validated['mqtt_username'] ?? null) ?: $device->mqtt_username,
            'settings' => $settings,
        ]);

        AuditLog::record(
            $request->user()?->id,
            'device.updated',
            Device::class,
            (string) $device->id,
            ['settings' => $settings]
        );

        return redirect()->route('device-management')
            ->with('status', __('Configuration for :name updated successfully.', ['name' => $device->name]));
    }

    /**
     * Remove the specified device from storage.
     */
    public function destroy(Request $request, Device $device): RedirectResponse
    {
        $uid = $device->uid;
        $name = $device->name;

        AuditLog::record(
            $request->user()?->id,
            'device.deleted',
            Device::class,
            (string) $device->id,
            ['uid' => $uid, 'name' => $name]
        );

        $device->delete();

        return redirect()->route('device-management')
            ->with('status', __('Device :name (:uid) removed from registry.', ['name' => $name, 'uid' => $uid]));
    }
}
