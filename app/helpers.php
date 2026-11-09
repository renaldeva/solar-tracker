<?php

use Carbon\Carbon;
use Illuminate\Support\Number;

if (! function_exists('formatPowerW')) {
    /**
     * Format a power value in Watts (W) only, never kW.
     * Round to a whole number when >= 100 W, otherwise one decimal.
     * Uses Indonesian number formatting (dot for thousands, comma for decimals).
     */
    function formatPowerW(float|int|null $watts, bool $withUnit = true): string
    {
        if ($watts === null) {
            return $withUnit ? '-- W' : '--';
        }

        $val = (float) $watts;
        $abs = abs($val);

        if ($abs >= 100.0) {
            $formatted = number_format(round($val), 0, ',', '.');
        } else {
            $formatted = number_format($val, 1, ',', '.');
        }

        return $withUnit ? "{$formatted} W" : $formatted;
    }
}

if (! function_exists('formatNumberId')) {
    /**
     * Format a generic numeric value using Indonesian locale.
     */
    function formatNumberId(float|int|null $number, int $decimals = 1): string
    {
        if ($number === null) {
            return '--';
        }

        return Number::format((float) $number, precision: $decimals, locale: 'id');
    }
}

if (! function_exists('formatDateWib')) {
    /**
     * Format a Carbon date into Indonesian WIB format.
     * Example: "2 November 2026, 22.09 WIB".
     */
    function formatDateWib(?Carbon $date, string $format = 'j F Y, H.i'): string
    {
        if (! $date) {
            return '--';
        }

        return $date->copy()->timezone('Asia/Jakarta')->locale('id')->translatedFormat($format).' WIB';
    }
}

if (! function_exists('formatTimeWib')) {
    /**
     * Format time in WIB.
     * Example: "22.09 WIB".
     */
    function formatTimeWib(?Carbon $date): string
    {
        if (! $date) {
            return '--';
        }

        return $date->copy()->timezone('Asia/Jakarta')->locale('id')->translatedFormat('H.i').' WIB';
    }
}
