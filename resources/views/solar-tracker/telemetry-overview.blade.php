{{-- Core 4 KPI Cards: Elevasi, Azimut, Angin, Output Listrik --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <!-- 1. Elevasi -->
    <x-kpi-card 
        :title="__('Elevasi')" 
        :value="formatNumberId($elevCurrent, 1)" 
        unit="°"
        tone="{{ abs($elevDeviation) <= 2.5 ? 'ok' : 'warn' }}"
        badge="{{ abs($elevDeviation) <= 2.5 ? __('PRESISI') : __('KOREKSI') }}"
        subtitle="{{ __('Target: ') . formatNumberId($elevTarget, 1) . '° · Dev: ' . ($elevDeviation >= 0 ? '+' : '') . formatNumberId($elevDeviation, 1) . '°' }}"
    >
        <x-slot:icon>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
            </svg>
        </x-slot:icon>
        <div class="text-[11px] text-muted font-medium mt-1 flex items-center justify-between">
            <span>{{ abs($elevDeviation) <= 2.5 ? __('Normal, panel sejajar matahari') : __('Aktuator pitch mengoreksi sudut') }}</span>
            <span class="text-[10px] text-muted font-mono">{{ __('BTS7960 Actuators') }}</span>
        </div>
    </x-kpi-card>

    <!-- 2. Azimut -->
    <x-kpi-card 
        :title="__('Azimut')" 
        :value="formatNumberId($azCurrent, 1)" 
        unit="°"
        tone="{{ abs($azDeviation) <= 2.5 ? 'ok' : 'warn' }}"
        badge="{{ abs($azDeviation) <= 2.5 ? __('PRESISI') : __('KOREKSI') }}"
        subtitle="{{ __('Target: ') . formatNumberId($azTarget, 1) . '° · Dev: ' . ($azDeviation >= 0 ? '+' : '') . formatNumberId($azDeviation, 1) . '°' }}"
    >
        <x-slot:icon>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </x-slot:icon>
        <div class="text-[11px] text-muted font-medium mt-1 flex items-center justify-between">
            <span>{{ abs($azDeviation) <= 2.5 ? __('Normal, panel sejajar matahari') : __('Aktuator azimut sedang berputar') }}</span>
            <span class="text-[10px] text-muted font-mono">{{ $telemetry?->az_state ?? 'idle' }}</span>
        </div>
    </x-kpi-card>

    <!-- 3. Angin (Alarm jika $isWindAlert atau flags.wind_alarm) -->
    @php
        $isWindFault = (bool) ($telemetry?->wind_fault ?? false) || ($telemetry && $telemetry->wind_speed_ms === null && $telemetry->wind_speed === null);
        $hasWindAlarm = (bool) ($telemetry?->wind_alarm ?? false) || $isWindAlert;
    @endphp
    <x-kpi-card 
        :title="__('Kecepatan Angin')" 
        :value="$isWindFault ? '-' : formatNumberId($telemetry?->wind_speed_ms ?? $telemetry?->wind_speed ?? 0, 1)" 
        :unit="$isWindFault ? '' : 'm/s'"
        tone="{{ $isWindFault ? 'warn' : ($hasWindAlarm ? 'danger' : 'ok') }}"
        badge="{{ $isWindFault ? __('OFFLINE') : ($hasWindAlarm ? __('ALARM STOW') : __('AMAN')) }}"
        subtitle="{{ $isWindFault ? __('Sensor angin offline') : ((__('Batas stow: ') . formatNumberId($windStowThreshold, 1) . ' m/s') . ($telemetry?->wind_direction_deg !== null ? ' · Arah: ' . round($telemetry->wind_direction_deg) . '°' : '')) }}"
    >
        <x-slot:icon>
            <svg class="w-4 h-4 {{ $isWindFault ? 'text-amber-500' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </x-slot:icon>
        <div class="text-[11px] font-medium mt-1 {{ $isWindFault ? 'text-amber-600 dark:text-amber-400' : ($hasWindAlarm ? 'text-rose-600 dark:text-rose-400 font-semibold' : 'text-muted') }}">
            @if($isWindFault)
                {{ __('Sensor angin offline — periksa kabel anemometer') }}
            @elseif($hasWindAlarm)
                {{ __('Angin kencang, panel otomatis diamankan (stow)') }}
            @else
                {{ __('Angin aman, pelacakan aktif') }}
            @endif
        </div>
    </x-kpi-card>

    <!-- 4. Output Listrik (voltage, current, power in Watt) -->
    @php
        $isPzemFault = (bool) ($telemetry?->pzem_fault ?? false) || ($telemetry && $telemetry->pzem_voltage === null && $telemetry->voltage === null);
    @endphp
    <x-kpi-card 
        :title="__('Output Listrik')" 
        :value="$isPzemFault ? '-' : formatPowerW($telemetry?->pzem_power ?? $telemetry?->power ?? 0, false)" 
        :unit="$isPzemFault ? '' : 'W'"
        tone="{{ $isPzemFault ? 'danger' : 'accent' }}"
        badge="{{ $isPzemFault ? __('OFFLINE') : 'DC' }}"
        subtitle="{{ $isPzemFault ? __('PZEM offline') : (formatNumberId($telemetry?->pzem_voltage ?? $telemetry?->voltage ?? 0, 1) . ' V · ' . formatNumberId($telemetry?->pzem_current ?? $telemetry?->current ?? 0, 2) . ' A') }}"
    >
        <x-slot:icon>
            @if($isPzemFault)
                <svg class="w-4 h-4 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            @else
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
            @endif
        </x-slot:icon>
        <div class="text-[11px] font-medium mt-1 flex items-center justify-between {{ $isPzemFault ? 'text-rose-600 dark:text-rose-400' : 'text-muted' }}">
            @if($isPzemFault)
                <span>{{ __('PZEM offline — sensor arus/tegangan terputus') }}</span>
                <span class="text-[10px] font-mono text-rose-500 font-semibold">{{ __('FAULT') }}</span>
            @else
                <span>{{ __('Akumulasi: ') . formatNumberId(($telemetry?->pzem_energy_wh ?? $telemetry?->energy_wh ?? 0) / 1000.0, 2) . ' kWh' }}</span>
                <span class="text-[10px] text-muted font-mono">{{ __('PZEM-017 DC Array') }}</span>
            @endif
        </div>
    </x-kpi-card>
</div>
