<?php

use App\Http\Controllers\AlertsController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\CommandController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceManagementController;
use App\Http\Controllers\DeviceMonitoringController;
use App\Http\Controllers\EnergyMonitoringController;
use App\Http\Controllers\HistoricalDataController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SolarTrackerController;
use App\Http\Controllers\TelemetryController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;

// Guest Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);

    Route::get('/forgot-password', [PasswordResetController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])->name('password.update');
});

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::match(['get', 'post'], '/logout', [LoginController::class, 'logout'])->name('logout');

    Route::get('/', function () {
        return redirect()->route('dashboard');
    });

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/device-monitoring/export', [DeviceMonitoringController::class, 'export'])->name('device-monitoring.export');
    Route::get('/device-monitoring', [DeviceMonitoringController::class, 'index'])->name('device-monitoring');
    Route::get('/solar-tracker', [SolarTrackerController::class, 'index'])->name('solar-tracker');
    Route::get('/energy-monitoring/data', [EnergyMonitoringController::class, 'data'])->name('energy-monitoring.data');
    Route::get('/energy-monitoring', [EnergyMonitoringController::class, 'index'])->name('energy-monitoring');
    Route::get('/telemetry/data', [TelemetryController::class, 'data'])->name('telemetry.data');
    Route::get('/telemetry/export', [TelemetryController::class, 'export'])->name('telemetry.export');
    Route::get('/telemetry', [TelemetryController::class, 'index'])->name('telemetry');
    Route::get('/alerts', [AlertsController::class, 'index'])->name('alerts');
    Route::get('/historical-data/export', [HistoricalDataController::class, 'export'])->name('historical-data.export');
    Route::get('/historical-data', [HistoricalDataController::class, 'index'])->name('historical-data');
    Route::get('/reports/export', [ReportsController::class, 'export'])->name('reports.export');
    Route::get('/reports', [ReportsController::class, 'index'])->name('reports');

    // Command telemetry polling & status
    Route::get('/commands/{id}/status', [CommandController::class, 'status'])->name('commands.status');
    Route::get('/tracker/{uid}/commands', [CommandController::class, 'recent'])->name('commands.recent');

    // Operator and Admin Protected
    Route::middleware('role:admin,operator')->group(function () {
        Route::post('/tracker/{uid}/commands', [CommandController::class, 'store'])->name('commands.store');
        Route::post('/alerts/{alert}/acknowledge', [AlertsController::class, 'acknowledge'])->name('alerts.acknowledge');
        Route::post('/alerts/{alert}/resolve', [AlertsController::class, 'resolve'])->name('alerts.resolve');

        // Device Management
        Route::get('/device-management', [DeviceManagementController::class, 'index'])->name('device-management');
        Route::post('/device-management', [DeviceManagementController::class, 'store'])->name('device-management.store');
        Route::put('/device-management/{device}', [DeviceManagementController::class, 'update'])->name('device-management.update');
        Route::delete('/device-management/{device}', [DeviceManagementController::class, 'destroy'])->name('device-management.destroy');

        // Settings
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
        Route::post('/settings/site', [SettingsController::class, 'updateSite'])->name('settings.site');
        Route::post('/settings/thresholds', [SettingsController::class, 'updateThresholds'])->name('settings.thresholds');
    });

    // Admin Only
    Route::middleware('role:admin')->group(function () {
        Route::get('/user-management', [UserManagementController::class, 'index'])->name('user-management');
        Route::post('/user-management', [UserManagementController::class, 'store'])->name('user-management.store');
        Route::put('/user-management/{user}', [UserManagementController::class, 'update'])->name('user-management.update');
        Route::post('/user-management/{user}/toggle-status', [UserManagementController::class, 'toggleStatus'])->name('user-management.toggle-status');
        Route::delete('/user-management/{user}', [UserManagementController::class, 'destroy'])->name('user-management.destroy');
    });
});
