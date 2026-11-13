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

    <!-- 3. Angin (Alarm jika $isWindAlert) -->
    <x-kpi-card 
        :title="__('Kecepatan Angin')" 
        :value="formatNumberId($telemetry?->wind_speed ?? 0, 1)" 
        unit="m/s"
        tone="{{ $isWindAlert ? 'danger' : 'ok' }}"
        badge="{{ $isWindAlert ? __('ALARM STOW') : __('AMAN') }}"
        subtitle="{{ __('Batas stow: ') . formatNumberId($windStowThreshold, 1) . ' m/s' }}"
    >
        <x-slot:icon>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </x-slot:icon>
        <div class="text-[11px] font-medium mt-1 {{ $isWindAlert ? 'text-rose-600 dark:text-rose-400 font-semibold' : 'text-muted' }}">
            {{ $isWindAlert ? __('Angin kencang, panel diamankan') : __('Angin aman, pelacakan aktif') }}
        </div>
    </x-kpi-card>

    <!-- 4. Output Listrik (voltage, current, power in Watt) -->
    <x-kpi-card 
        :title="__('Output Listrik')" 
        :value="formatPowerW($telemetry?->power ?? 0, false)" 
        unit="W"
        tone="accent"
        badge="DC"
        subtitle="{{ formatNumberId($telemetry?->voltage ?? 0, 1) . ' V · ' . formatNumberId($telemetry?->current ?? 0, 2) . ' A' }}"
    >
        <x-slot:icon>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
        </x-slot:icon>
        <div class="text-[11px] text-muted font-medium mt-1 flex items-center justify-between">
            <span>{{ __('Akumulasi: ') . formatNumberId(($telemetry?->energy_wh ?? 0) / 1000.0, 2) . ' kWh' }}</span>
            <span class="text-[10px] text-muted font-mono">{{ __('PZEM-017 DC Array') }}</span>
        </div>
    </x-kpi-card>
</div>
