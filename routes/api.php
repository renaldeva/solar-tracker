<?php

use App\Http\Controllers\Api\CommandController;
use App\Http\Controllers\Api\DiagnosticsController;
use App\Http\Controllers\Api\TelemetryController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/telemetry/latest', [TelemetryController::class, 'latest']);
    Route::get('/telemetry/history', [TelemetryController::class, 'history']);
    Route::get('/diagnostics/{uid}', [DiagnosticsController::class, 'show']);
    Route::post('/command', [CommandController::class, 'store']);
});

