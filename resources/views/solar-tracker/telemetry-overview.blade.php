{{-- Core Telemetry Metric Grid (Alignment, DC Output, Actuators, Wind & Temperature) --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <!-- 1. Indikator Deviasi Total -->
    <div class="bg-panel rounded-md border border-line p-4 flex flex-col justify-between">
        <div class="flex items-start justify-between gap-2 mb-2">
            <span class="text-xs font-medium text-muted uppercase tracking-wider truncate">{{ __('Status Deviasi Total') }}</span>
            @if($isAligned)
                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[11px] font-mono font-medium border border-ok/30 bg-ok/10 text-ok">
                    <span class="w-1.5 h-1.5 rounded-full bg-ok"></span>
                    <span>ALIGNED</span>
                </span>
            @else
                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[11px] font-mono font-medium border border-danger/30 bg-danger/10 text-danger">
                    <span class="w-1.5 h-1.5 rounded-full bg-danger animate-pulse"></span>
                    <span>DEV &gt; {{ formatNumberId($threshold, 1) }}&deg;</span>
                </span>
            @endif
        </div>

        <div class="flex items-baseline gap-1 my-1">
            <span class="text-3xl font-bold font-mono tabular-nums {{ $isAligned ? 'text-ok' : 'text-danger' }} tracking-tight">
                {{ formatNumberId($totalDeviation, 1) }}&deg;
            </span>
            <span class="text-xs font-mono text-muted">/ {{ formatNumberId($threshold, 1) }}&deg; maks</span>
        </div>

        <div class="mt-2 pt-2 border-t border-line/50 flex items-center justify-between text-xs font-mono text-muted">
            <span>Elev: {{ ($elevDeviation >= 0 ? '+' : '') . formatNumberId($elevDeviation, 1) }}&deg;</span>
            <span>Az: {{ ($azDeviation >= 0 ? '+' : '') . formatNumberId($azDeviation, 1) }}&deg;</span>
        </div>
    </div>

    <!-- 2. Output DC (PZEM-017 DC Array - Power in W only) -->
    <div class="bg-panel rounded-md border border-line p-4 flex flex-col justify-between">
        <div class="flex items-start justify-between gap-2 mb-2">
            <span class="text-xs font-medium text-muted uppercase tracking-wider truncate">{{ __('PZEM-017 DC Array') }}</span>
            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[11px] font-mono border border-line bg-bg text-muted">
                {{ $device->settings['measurement_label'] ?? __('Panel surya') }}
            </span>
        </div>

        <div class="flex items-baseline gap-1 my-1">
            <span class="text-3xl font-bold font-mono tabular-nums text-accent tracking-tight">
                {{ formatPowerW($telemetry?->power ?? 0, false) }}
            </span>
            <span class="text-xs font-mono text-muted font-normal">W</span>
        </div>

        <div class="mt-2 pt-2 border-t border-line/50 flex items-center justify-between text-xs font-mono text-muted">
            <span>{{ formatNumberId($telemetry?->voltage ?? 0, 1) }} V</span>
            <span>&middot;</span>
            <span>{{ formatNumberId($telemetry?->current ?? 0, 2) }} A</span>
            <span>&middot;</span>
            <span>{{ formatNumberId(($telemetry?->energy_wh ?? 0) / 1000.0, 2) }} kWh</span>
        </div>
    </div>

    <!-- 3. Mode & Status Aktuator (BTS7960 Actuators) -->
    <div class="bg-panel rounded-md border border-line p-4 flex flex-col justify-between">
        <div class="flex items-start justify-between gap-2 mb-2">
            <span class="text-xs font-medium text-muted uppercase tracking-wider truncate">{{ __('BTS7960 Actuators') }}</span>
            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[11px] font-mono font-medium border border-line bg-bg text-muted">
                <span class="w-1.5 h-1.5 rounded-full {{ ($telemetry?->elev_state !== 'idle' || $telemetry?->az_state !== 'idle') ? 'bg-accent animate-pulse' : 'bg-muted' }}"></span>
                <span>{{ strtoupper($device->mode) }}</span>
            </span>
        </div>

        <div class="my-1 space-y-1 text-xs font-mono">
            <div class="flex items-center justify-between">
                <span class="text-muted">{{ __('Elev:') }}</span>
                <span class="font-bold {{ $telemetry?->elev_state !== 'idle' ? 'text-accent' : 'text-ink' }}">
                    {{ strtoupper($telemetry?->elev_state ?? 'IDLE') }} ({{ formatNumberId($telemetry?->elev_current ?? 0, 2) }} A)
                </span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-muted">{{ __('Azimuth:') }}</span>
                <span class="font-bold {{ $telemetry?->az_state !== 'idle' ? 'text-accent' : 'text-ink' }}">
                    {{ strtoupper($telemetry?->az_state ?? 'IDLE') }} ({{ formatNumberId($telemetry?->az_current ?? 0, 2) }} A)
                </span>
            </div>
        </div>

        <div class="mt-2 pt-2 border-t border-line/50 text-[11px] font-mono text-muted flex items-center justify-between">
            <span>Driver: 24V DC BTS7960</span>
            <span class="text-ink font-medium">{{ $telemetry?->firmware ?? 'v1.0' }}</span>
        </div>
    </div>

    <!-- 4. Angin & Suhu Enclosure -->
    <div class="bg-panel rounded-md border border-line p-4 flex flex-col justify-between">
        <div class="flex items-start justify-between gap-2 mb-2">
            <span class="text-xs font-medium text-muted uppercase tracking-wider truncate">{{ __('Angin & Suhu Kotak') }}</span>
            <span 
                class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[11px] font-mono font-medium border"
                :class="'{{ $isWindAlert ? 'border-danger/30 bg-danger/10 text-danger' : 'border-ok/30 bg-ok/10 text-ok' }}'"
            >
                <span class="w-1.5 h-1.5 rounded-full {{ $isWindAlert ? 'bg-danger' : 'bg-ok' }}"></span>
                <span>{{ $isWindAlert ? __('STOW LIMIT') : __('NOMINAL') }}</span>
            </span>
        </div>

        <div class="flex items-baseline gap-1 my-1">
            <span class="text-3xl font-bold font-mono tabular-nums {{ $isWindAlert ? 'text-danger' : 'text-ink' }} tracking-tight">
                {{ formatNumberId($telemetry?->wind_speed ?? 0, 1) }}
            </span>
            <span class="text-xs font-mono text-muted font-normal">m/s</span>
            <span class="text-[11px] font-mono text-muted ml-auto">Batas: {{ formatNumberId($windStowThreshold, 1) }} m/s</span>
        </div>

        <div class="mt-2 pt-2 border-t border-line/50 flex items-center justify-between text-xs font-mono text-muted">
            <span>Suhu DS3231:</span>
            <span class="text-ink font-bold">{{ formatNumberId($telemetry?->temp_c ?? 0, 1) }} &deg;C</span>
        </div>
    </div>
</div>

