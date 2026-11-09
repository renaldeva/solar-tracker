<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Timezone Display
    |--------------------------------------------------------------------------
    | Real-time display timezone for user interface (WIB - Western Indonesia Time)
    */
    'display_timezone' => env('APP_DISPLAY_TIMEZONE', 'Asia/Jakarta'),

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    | Battery monitoring is hidden behind this flag as requested in specifications.
    */
    'feature_battery' => (bool) env('FEATURE_BATTERY', false),

    /*
    |--------------------------------------------------------------------------
    | Default Safety Thresholds
    |--------------------------------------------------------------------------
    */
    'wind_stow_threshold_ms' => (float) env('SOLAR_WIND_STOW_MS', 15.0),
    'command_timeout_seconds' => (int) env('SOLAR_COMMAND_TIMEOUT_SECONDS', 30),
];
