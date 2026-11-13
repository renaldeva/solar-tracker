<x-layouts.app title="{{ __('Pemantauan Energi') }}">
    <!-- Page Header & Device Selector -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold border border-blue-200 dark:border-blue-800/60 bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300">
                    <span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span>
                    <span>PZEM-017 DC Array</span>
                </span>
                <span class="text-xs text-muted">{{ __('Telemetri Modbus RTU Real-Time') }}</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-ink mt-1.5">{{ __('Pemantauan Energi & Yield DC') }}</h1>
            <p class="text-xs sm:text-sm text-muted mt-0.5">{{ __('Analitik daya DC presisi tinggi, akumulasi kWh, dan parameter subsistem.') }}</p>
        </div>

        <div class="flex items-center flex-wrap gap-2.5">
            @if($devices->count() > 1)
                <form method="GET" action="{{ route('energy-monitoring') }}" class="flex items-center gap-2">
                    <label for="device-select" class="text-xs font-semibold text-muted uppercase">{{ __('Perangkat:') }}</label>
                    <select id="device-select" name="device" onchange="this.form.submit()" class="h-10 text-xs sm:text-sm font-medium bg-panel border border-line rounded-xl px-3 py-1 text-ink focus:border-blue-500 focus:outline-none cursor-pointer shadow-2xs">
                        @foreach($devices as $dev)
                            <option value="{{ $dev->uid }}" {{ $device && $device->uid === $dev->uid ? 'selected' : '' }}>
                                {{ $dev->name }} ({{ $dev->uid }})
                            </option>
                        @endforeach
                    </select>
                </form>
            @else
                <div class="inline-flex items-center gap-2 h-10 px-3.5 rounded-xl border border-line bg-panel text-xs sm:text-sm font-medium text-ink shadow-2xs">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span>{{ $device->name ?? 'ST-DEMO-001' }}</span>
                </div>
            @endif

            <a 
                href="{{ route('solar-tracker') }}" 
                class="inline-flex items-center gap-1.5 h-10 px-4 rounded-xl border border-blue-200 dark:border-blue-800/60 bg-blue-50 dark:bg-blue-950/40 hover:bg-blue-100 dark:hover:bg-blue-900/50 text-blue-700 dark:text-blue-300 text-xs sm:text-sm font-semibold transition-colors shadow-2xs"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                <span>{{ __('Kontrol Pelacak') }}</span>
            </a>
        </div>
    </div>

    <!-- Live Reactive State Container with Alpine.js -->
    <div 
        x-data="energyMonitorComponent(@js($metrics), '{{ $device->uid ?? '' }}')" 
        class="space-y-6"
    >
        <!-- Banner Status Besar: "Apakah semuanya baik-baik saja?" -->
        <div class="p-4 rounded-2xl border flex items-center justify-between gap-3 bg-emerald-50/80 dark:bg-emerald-950/40 border-emerald-200 dark:border-emerald-800/60 text-emerald-950 dark:text-emerald-200">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-emerald-500 text-white flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-sm sm:text-base font-bold tracking-tight">
                        {{ __('Pembangkitan Energi DC Berjalan Normal') }}
                    </h2>
                    <p class="text-xs text-slate-600 dark:text-slate-300 mt-0.5">
                        {{ __('Sensor PZEM-017 aktif membaca arus dan tegangan. Arus listrik mengalir stabil.') }}
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/60">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>POLLING AKTIF (5s)</span>
                </span>
            </div>
        </div>

        <!-- Top Electrical & Energy KPI Cards (5 Cards) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <!-- 1. DC Voltage Card -->
            <div class="bg-panel rounded-2xl border border-line p-5 flex flex-col justify-between hover:border-blue-200 transition-colors">
                <div class="flex items-start justify-between gap-2 mb-2">
                    <span class="text-xs font-semibold text-muted uppercase tracking-wider truncate">{{ __('Tegangan DC') }}</span>
                    <span 
                        class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold border"
                        :class="metrics.pzem.voltage >= 200 ? 'border-emerald-200 dark:border-emerald-800/60 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300' : 'border-amber-200 dark:border-amber-800/60 bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300'"
                    >
                        <span class="w-1.5 h-1.5 rounded-full" :class="metrics.pzem.voltage >= 200 ? 'bg-emerald-500' : 'bg-amber-500'"></span>
                        <span x-text="metrics.pzem.voltage >= 200 ? 'NOMINAL' : 'STANDBY'"></span>
                    </span>
                </div>

                <div class="my-1">
                    <div class="flex items-baseline gap-1">
                        <span class="text-2xl sm:text-3xl font-bold font-mono tabular-nums text-ink tracking-tight" x-text="formatNumberId(metrics.pzem.voltage, 1)"></span>
                        <span class="text-sm font-semibold text-muted font-mono">V</span>
                    </div>
                </div>

                <div class="mt-2 pt-2 border-t border-line flex items-center justify-between text-xs text-muted">
                    <span>{{ __('Rentang nominal') }}</span>
                    <span class="font-mono text-ink font-medium">200 - 450 V</span>
                </div>
            </div>

            <!-- 2. DC Current Card -->
            <div class="bg-panel rounded-2xl border border-line p-5 flex flex-col justify-between hover:border-blue-200 transition-colors">
                <div class="flex items-start justify-between gap-2 mb-2">
                    <span class="text-xs font-semibold text-muted uppercase tracking-wider truncate">{{ __('Arus DC') }}</span>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold border border-blue-200 dark:border-blue-800/60 bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span>
                        <span>PZEM-017</span>
                    </span>
                </div>

                <div class="my-1">
                    <div class="flex items-baseline gap-1">
                        <span class="text-2xl sm:text-3xl font-bold font-mono tabular-nums text-ink tracking-tight" x-text="formatNumberId(metrics.pzem.current, 2)"></span>
                        <span class="text-sm font-semibold text-muted font-mono">A</span>
                    </div>
                </div>

                <div class="mt-2 pt-2 border-t border-line flex items-center justify-between text-xs text-muted">
                    <span>{{ __('Kuat arus beban') }}</span>
                    <span class="font-mono text-ink font-medium">Maks 15.0 A</span>
                </div>
            </div>

            <!-- 3. Instantaneous DC Power Card (W only) -->
            <div class="bg-panel rounded-2xl border border-line p-5 flex flex-col justify-between hover:border-blue-200 transition-colors">
                <div class="flex items-start justify-between gap-2 mb-2">
                    <span class="text-xs font-semibold text-muted uppercase tracking-wider truncate">{{ __('Daya Sesaat') }}</span>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold border border-emerald-200 dark:border-emerald-800/60 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span>{{ __('YIELD AKTIF') }}</span>
                    </span>
                </div>

                <div class="my-1">
                    <div class="flex items-baseline gap-1">
                        <span class="text-2xl sm:text-3xl font-bold font-mono tabular-nums text-blue-600 tracking-tight" x-text="formatPowerW(metrics.pzem.power_w, false)"></span>
                        <span class="text-sm font-semibold text-muted font-mono">W</span>
                    </div>
                </div>

                <div class="mt-2 pt-2 border-t border-slate-100 flex items-center justify-between text-xs text-muted">
                    <span class="truncate" x-text="metrics.pzem.label || '{{ __('Panel surya') }}'"></span>
                    <span class="text-slate-500 font-medium font-mono">Watt (W)</span>
                </div>
            </div>

            <!-- 4. Daily Generation & Target Progress Card -->
            <div class="bg-panel rounded-2xl border border-line p-5 flex flex-col justify-between hover:border-blue-200 transition-colors">
                <div class="flex items-start justify-between gap-2 mb-2">
                    <span class="text-xs font-semibold text-muted uppercase tracking-wider truncate">{{ __('Energi Harian') }}</span>
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold border border-blue-200 dark:border-blue-800/60 bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span>
                        <span x-text="metrics.progress_percent + '%'"></span>
                    </span>
                </div>

                <div class="my-1">
                    <div class="flex items-baseline gap-1">
                        <span class="text-2xl sm:text-3xl font-bold font-mono tabular-nums text-ink tracking-tight" x-text="formatNumberId(metrics.daily_energy_kwh, 2)"></span>
                        <span class="text-sm font-semibold text-muted font-mono">kWh</span>
                    </div>
                </div>

                <div class="mt-2 pt-2 border-t border-line">
                    <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-1.5 overflow-hidden mb-1.5">
                        <div class="bg-blue-600 h-1.5 rounded-full transition-all duration-300" :style="'width: ' + Math.min(100, metrics.progress_percent) + '%'"></div>
                    </div>
                    <div class="flex items-center justify-between text-xs text-muted">
                        <span>{{ __('Target') }}: <span class="text-ink font-semibold font-mono" x-text="formatNumberId(metrics.daily_target_kwh, 1)"></span> kWh</span>
                        <span :class="metrics.progress_percent >= 100 ? 'text-emerald-600 dark:text-emerald-400 font-bold' : 'text-slate-500'" x-text="metrics.progress_percent >= 100 ? '{{ __('Tercapai') }}' : '{{ __('Berjalan') }}'"></span>
                    </div>
                </div>
            </div>

            <!-- 5. Monthly Energy Card -->
            <div class="bg-panel rounded-2xl border border-line p-5 flex flex-col justify-between hover:border-blue-200 transition-colors">
                <div class="flex items-start justify-between gap-2 mb-2">
                    <span class="text-xs font-semibold text-muted uppercase tracking-wider truncate">{{ __('Total Bulanan') }}</span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold border border-line bg-panel-alt text-ink">
                        {{ now()->locale('id')->translatedFormat('M Y') }}
                    </span>
                </div>

                <div class="my-1">
                    <div class="flex items-baseline gap-1">
                        <span class="text-2xl sm:text-3xl font-bold font-mono tabular-nums text-ink tracking-tight" x-text="formatNumberId(metrics.monthly_energy_kwh, 1)"></span>
                        <span class="text-sm font-semibold text-muted font-mono">kWh</span>
                    </div>
                </div>

                <div class="mt-2 pt-2 border-t border-line flex items-center justify-between text-xs text-muted">
                    <span>{{ __('Akumulasi') }}</span>
                    <span class="text-ink font-semibold font-mono" x-text="formatNumberId(metrics.pzem.energy_wh, 0) + ' Wh'"></span>
                </div>
            </div>
        </div>

        <!-- Charts Section (Grid 2 Kolom: 24h Live Power Line Chart + Today vs Yesterday Bar Chart) -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- 1. 24h Live Power Output (Line, Daya W) -->
            <x-card 
                :title="__('Output Daya Live 24 Jam')" 
                :subtitle="__('Deret waktu daya listrik sesaat kontinu (W) dari sensor PZEM-017')"
            >
                <x-slot:actions>
                    <div class="flex items-center gap-1.5 px-2.5 py-0.5 rounded-full border border-emerald-200 dark:border-emerald-800/60 bg-emerald-50 dark:bg-emerald-950/40 text-xs font-semibold text-emerald-700 dark:text-emerald-400">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span>LIVE</span>
                    </div>
                </x-slot:actions>

                <div class="relative w-full h-72 sm:h-80 overflow-hidden">
                    <canvas id="livePowerChartCanvas"></canvas>
                </div>
            </x-card>

            <!-- 2. Hourly Production: Today vs Yesterday (Bar) -->
            <x-card 
                :title="__('Produksi per Jam: Hari Ini vs Kemarin')" 
                :subtitle="__('Perbandingan energi kWh per jam (00:00 - 23:00 WIB)')"
            >
                <x-slot:actions>
                    <div class="flex items-center gap-3 text-xs">
                        <div class="flex items-center gap-1.5 text-blue-700 dark:text-blue-400 font-semibold">
                            <span class="w-3 h-3 rounded-full bg-blue-600 inline-block"></span>
                            <span>{{ __('Hari Ini') }}</span>
                        </div>
                        <div class="flex items-center gap-1.5 text-muted font-semibold">
                            <span class="w-3 h-3 rounded-full bg-blue-300 dark:bg-blue-800 inline-block"></span>
                            <span>{{ __('Kemarin') }}</span>
                        </div>
                    </div>
                </x-slot:actions>

                <div class="relative w-full h-72 sm:h-80 overflow-hidden">
                    <canvas id="compareProductionChartCanvas"></canvas>
                </div>
            </x-card>
        </div>

        <!-- Sub-System Parameters Section (Di dalam Accordion "Lihat detail") -->
        <div x-data="{ showDetail: false }" class="mb-6">
            <button 
                type="button" 
                @click="showDetail = !showDetail" 
                class="w-full flex items-center justify-between p-4 rounded-2xl border border-line bg-panel hover:bg-panel-alt text-sm font-semibold text-ink transition-colors cursor-pointer shadow-2xs"
            >
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-xl bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0 border border-blue-100 dark:border-blue-900/50">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                        </svg>
                    </div>
                    <span>{{ __('Lihat Detail Sub-Sistem (Angin, Posisi Panel, dan Suhu Kabinet)') }}</span>
                </div>
                <svg class="w-5 h-5 text-slate-400 transform transition-transform duration-200" :class="showDetail ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <div x-show="showDetail" x-cloak class="mt-4">
                <x-card 
                    :title="__('Status Sub-Sistem & Diagnostik Sensor')" 
                    :subtitle="__('Parameter lingkungan, kinematika, dan termal dari mikrokontroler ESP32')"
                >
                    <x-slot:actions>
                        <div class="flex items-center gap-2 text-xs text-muted">
                            <span>{{ __('Data terakhir') }}:</span>
                            <span class="text-ink font-semibold font-mono" x-text="metrics.recorded_at_wib"></span>
                        </div>
                    </x-slot:actions>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <!-- 1. PZEM-017 DC Solar Array Diagnostic -->
                        <div class="p-4 rounded-xl border border-line bg-panel-alt/60">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-xs font-bold text-ink uppercase">{{ __('Sensor PZEM-017') }}</span>
                                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full border border-blue-200 dark:border-blue-800/60 bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300">Modbus RTU</span>
                            </div>
                            <div class="space-y-2 text-xs">
                                <div class="flex justify-between">
                                    <span class="text-muted">{{ __('Label:') }}</span>
                                    <span class="text-ink truncate font-medium" x-text="metrics.pzem.label || '{{ __('Panel surya') }}'"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted">{{ __('Tegangan:') }}</span>
                                    <span class="text-ink font-bold font-mono" x-text="formatNumberId(metrics.pzem.voltage, 1) + ' V'"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted">{{ __('Arus:') }}</span>
                                    <span class="text-ink font-bold font-mono" x-text="formatNumberId(metrics.pzem.current, 2) + ' A'"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted">{{ __('Daya:') }}</span>
                                    <span class="text-blue-600 dark:text-blue-400 font-bold font-mono" x-text="formatPowerW(metrics.pzem.power_w)"></span>
                                </div>
                                <div class="flex justify-between pt-2 border-t border-line">
                                    <span class="text-muted">{{ __('Akumulasi:') }}</span>
                                    <span class="text-ink font-bold font-mono" x-text="formatNumberId(metrics.pzem.energy_kwh, 2) + ' kWh'"></span>
                                </div>
                            </div>
                        </div>

                        <!-- 2. Wind Speed & Stow Threshold Subsystem -->
                        <div class="p-4 rounded-xl border border-line bg-panel-alt/60">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-xs font-bold text-ink uppercase">{{ __('Kecepatan Angin') }}</span>
                                <span 
                                    class="text-[10px] font-semibold px-2 py-0.5 rounded-full border"
                                    :class="metrics.subsystem.wind_alert ? 'border-rose-200 dark:border-rose-800/60 bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300' : 'border-emerald-200 dark:border-emerald-800/60 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300'"
                                    x-text="metrics.subsystem.wind_alert ? 'STOW ALARM' : 'AMAN'"
                                ></span>
                            </div>
                            <div class="space-y-2 text-xs">
                                <div class="flex justify-between">
                                    <span class="text-muted">{{ __('Kecepatan:') }}</span>
                                    <span class="text-ink font-bold font-mono" x-text="formatNumberId(metrics.subsystem.wind_ms, 1) + ' m/s'"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted">{{ __('Batas Stow:') }}</span>
                                    <span class="text-ink font-mono font-medium" x-text="formatNumberId(metrics.subsystem.wind_stow_ms, 1) + ' m/s'"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted">{{ __('Status Stow:') }}</span>
                                    <span :class="metrics.subsystem.is_stowed ? 'text-amber-700 dark:text-amber-400 font-bold' : 'text-ink font-medium'" x-text="metrics.subsystem.is_stowed ? '{{ __('Aktif (Datar)') }}' : '{{ __('Tidak Aktif') }}'"></span>
                                </div>
                                <div class="pt-2 border-t border-line">
                                    <p class="text-[11px] text-muted leading-tight">
                                        {{ __('Panel otomatis mendatar jika angin melebihi batas.') }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- 3. Kinematic Position & Tracking Alignment -->
                        <div class="p-4 rounded-xl border border-line bg-panel-alt/60">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-xs font-bold text-ink uppercase">{{ __('Orientasi Tracker') }}</span>
                                <span 
                                    class="text-[10px] font-semibold px-2 py-0.5 rounded-full border"
                                    :class="metrics.subsystem.is_aligned ? 'border-emerald-200 dark:border-emerald-800/60 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300' : 'border-amber-200 dark:border-amber-800/60 bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300'"
                                    x-text="metrics.subsystem.is_aligned ? 'SEJAJAR' : 'MENYESUAIKAN'"
                                ></span>
                            </div>
                            <div class="space-y-2 text-xs">
                                <div class="flex justify-between">
                                    <span class="text-muted">{{ __('Elevasi:') }}</span>
                                    <span class="text-ink font-bold font-mono" x-text="formatNumberId(metrics.subsystem.elev, 1) + '° / ' + formatNumberId(metrics.subsystem.target_elev, 1) + '°'"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted">{{ __('Azimut:') }}</span>
                                    <span class="text-ink font-bold font-mono" x-text="formatNumberId(metrics.subsystem.az, 1) + '° / ' + formatNumberId(metrics.subsystem.target_az, 1) + '°'"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted">{{ __('Deviasi Total:') }}</span>
                                    <span class="font-bold font-mono" :class="metrics.subsystem.is_aligned ? 'text-emerald-700 dark:text-emerald-400' : 'text-amber-700 dark:text-amber-400'" x-text="formatNumberId(metrics.subsystem.total_dev, 1) + '°'"></span>
                                </div>
                                <div class="flex justify-between pt-2 border-t border-line">
                                    <span class="text-muted">{{ __('Batas Toleransi:') }}</span>
                                    <span class="text-ink font-mono font-medium" x-text="formatNumberId(metrics.subsystem.threshold, 1) + '°'"></span>
                                </div>
                            </div>
                        </div>

                        <!-- 4. Thermal & Enclosure Health Diagnostic -->
                        <div class="p-4 rounded-xl border border-line bg-panel-alt/60">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-xs font-bold text-ink uppercase">{{ __('Suhu & Termal') }}</span>
                                <span 
                                    class="text-[10px] font-semibold px-2 py-0.5 rounded-full border"
                                    :class="metrics.subsystem.temp_c >= 55 ? 'border-rose-200 dark:border-rose-800/60 bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300' : 'border-emerald-200 dark:border-emerald-800/60 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300'"
                                    x-text="metrics.subsystem.temp_c >= 55 ? 'TINGGI' : 'NORMAL'"
                                ></span>
                            </div>
                            <div class="space-y-2 text-xs">
                                <div class="flex justify-between">
                                    <span class="text-muted">{{ __('Suhu Kabinet:') }}</span>
                                    <span class="text-ink font-bold font-mono" x-text="formatNumberId(metrics.subsystem.temp_c, 1) + ' °C'"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted">{{ __('Ambang Kritis:') }}</span>
                                    <span class="text-ink font-mono font-medium">55.0 °C</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted">{{ __('Suhu DS3231:') }}</span>
                                    <span class="text-ink font-mono font-medium" x-text="formatNumberId(metrics.subsystem.temp_c, 1) + ' °C'"></span>
                                </div>
                                <div class="pt-2 border-t border-line">
                                    <p class="text-[11px] text-muted leading-tight">
                                        {{ __('Sensor internal ESP32 & modul RTC DS3231.') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </x-card>
            </div>
        </div>
    </div>

    <!-- Chart.js and Polling Reactive Component -->
    <script>
        function energyMonitorComponent(initialMetrics, deviceUid) {
            return {
                metrics: initialMetrics || {},
                deviceUid: deviceUid,
                liveChart: null,
                compareChart: null,
                pollTimer: null,

                init() {
                    this.$nextTick(() => {
                        this.initLiveChart();
                        this.initCompareChart();
                        this.startPolling();
                    });

                    if (typeof this.$cleanup === 'function') {
                        this.$cleanup(() => this.teardown());
                    }
                    window.addEventListener('pagehide', () => this.teardown(), { once: true });
                    window.addEventListener('beforeunload', () => this.teardown(), { once: true });
                },

                teardown() {
                    if (this.pollTimer) {
                        clearInterval(this.pollTimer);
                        this.pollTimer = null;
                    }
                    if (this.liveChart) {
                        try { this.liveChart.destroy(); } catch (e) {}
                        this.liveChart = null;
                    }
                    if (this.compareChart) {
                        try { this.compareChart.destroy(); } catch (e) {}
                        this.compareChart = null;
                    }
                },

                destroy() {
                    this.teardown();
                },

                formatPowerW(val, withUnit = true) {
                    return window.formatPowerW ? window.formatPowerW(val, withUnit) : (withUnit ? `${val} W` : val);
                },

                formatNumberId(val, decimals = 1) {
                    return window.formatNumberId ? window.formatNumberId(val, decimals) : val;
                },

                fetchLatestData() {
                    if (!this.deviceUid) return;

                    fetch(`/energy-monitoring/data?device=${this.deviceUid}`)
                        .then(res => {
                            if (!res.ok) throw new Error('Network error');
                            return res.json();
                        })
                        .then(data => {
                            if (!data || typeof data !== 'object' || !data.pzem) return;
                            this.metrics = data;

                            // Update grafik live 24 jam
                            if (this.liveChart && data.chart_24h && Array.isArray(data.chart_24h.labels)) {
                                this.liveChart.data.labels = data.chart_24h.labels;
                                if (this.liveChart.data.datasets && this.liveChart.data.datasets[0]) {
                                    this.liveChart.data.datasets[0].data = data.chart_24h.values || [];
                                }
                                this.liveChart.update('none');
                            }

                            // Update grafik perbandingan hari ini vs kemarin
                            if (this.compareChart && data.chart_compare && Array.isArray(data.chart_compare.labels)) {
                                this.compareChart.data.labels = data.chart_compare.labels;
                                if (this.compareChart.data.datasets && this.compareChart.data.datasets[0]) {
                                    this.compareChart.data.datasets[0].data = data.chart_compare.today || [];
                                }
                                if (this.compareChart.data.datasets && this.compareChart.data.datasets[1]) {
                                    this.compareChart.data.datasets[1].data = data.chart_compare.yesterday || [];
                                }
                                this.compareChart.update('none');
                            }
                        })
                        .catch(err => console.debug('Polling jeda:', err));
                },

                startPolling() {
                    if (this.pollTimer) {
                        clearInterval(this.pollTimer);
                    }
                    this.pollTimer = setInterval(() => {
                        this.fetchLatestData();
                    }, 5000);
                },

                initLiveChart() {
                    const ctx = document.getElementById('livePowerChartCanvas');
                    if (!ctx) return;

                    // Safari memory guard: ensure canvas has no leftover chart instance
                    if (window.Chart && typeof window.Chart.getChart === 'function') {
                        const existing = window.Chart.getChart(ctx);
                        if (existing) {
                            try { existing.destroy(); } catch (e) {}
                        }
                    }
                    if (this.liveChart) {
                        try { this.liveChart.destroy(); } catch (e) {}
                        this.liveChart = null;
                    }

                    const powerLabel = window.__ ? window.__('Daya (W)') : 'Daya (W)';
                    const themeColors = window.getChartThemeColors ? window.getChartThemeColors() : {
                        primary: '#2563eb',
                        primaryFill: 'rgba(37, 99, 235, 0.08)',
                        grid: 'rgba(226, 232, 240, 0.7)',
                        text: '#64748b',
                        isDark: false
                    };

                    const labels = this.metrics?.chart_24h?.labels || [];
                    const values = this.metrics?.chart_24h?.values || [];

                    this.liveChart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: powerLabel,
                                data: values,
                                borderColor: themeColors.primary,
                                borderWidth: 2.5,
                                backgroundColor: themeColors.primaryFill,
                                fill: true,
                                tension: 0.35,
                                pointRadius: 0,
                                pointHoverRadius: 5,
                                pointBackgroundColor: themeColors.primary,
                                pointBorderColor: themeColors.isDark ? '#111a2e' : '#ffffff',
                                pointBorderWidth: 2,
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            resizeDelay: 50,
                            animation: { duration: 300 },
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    callbacks: {
                                        label: (item) => ` ${window.formatPowerW ? window.formatPowerW(item.raw) : item.raw + ' W'}`
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    grid: { color: themeColors.grid },
                                    ticks: { color: themeColors.text, maxTicksLimit: 12 }
                                },
                                y: {
                                    beginAtZero: true,
                                    grid: { color: themeColors.grid },
                                    ticks: {
                                        color: themeColors.text,
                                        callback: (value) => window.formatNumberId ? window.formatNumberId(value, 0) : value
                                    },
                                    title: {
                                        display: true,
                                        text: 'Daya (W)',
                                        color: themeColors.text,
                                        font: {
                                            family: "'Inter', sans-serif",
                                            size: 11,
                                            weight: '500'
                                        }
                                    }
                                }
                            }
                        }
                    });
                },

                initCompareChart() {
                    const ctx = document.getElementById('compareProductionChartCanvas');
                    if (!ctx) return;

                    // Safari memory guard: ensure canvas has no leftover chart instance
                    if (window.Chart && typeof window.Chart.getChart === 'function') {
                        const existing = window.Chart.getChart(ctx);
                        if (existing) {
                            try { existing.destroy(); } catch (e) {}
                        }
                    }
                    if (this.compareChart) {
                        try { this.compareChart.destroy(); } catch (e) {}
                        this.compareChart = null;
                    }

                    const todayLabel = window.__ ? window.__('Hari Ini (kWh)') : 'Hari Ini (kWh)';
                    const yesterdayLabel = window.__ ? window.__('Kemarin (kWh)') : 'Kemarin (kWh)';
                    const themeColors = window.getChartThemeColors ? window.getChartThemeColors() : {
                        barNew: '#2563eb',
                        barOld: '#dbeafe',
                        grid: 'rgba(226, 232, 240, 0.7)',
                        text: '#64748b'
                    };

                    const labels = this.metrics?.chart_compare?.labels || [];
                    const todayData = this.metrics?.chart_compare?.today || [];
                    const yesterdayData = this.metrics?.chart_compare?.yesterday || [];

                    this.compareChart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: labels,
                            datasets: [
                                {
                                    label: todayLabel,
                                    data: todayData,
                                    backgroundColor: themeColors.barNew,
                                    borderRadius: 6,
                                    barPercentage: 0.6,
                                },
                                {
                                    label: yesterdayLabel,
                                    data: yesterdayData,
                                    backgroundColor: themeColors.barOld,
                                    borderRadius: 6,
                                    barPercentage: 0.6,
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            resizeDelay: 50,
                            animation: { duration: 300 },
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    callbacks: {
                                        label: (item) => ` ${item.dataset.label}: ${window.formatNumberId ? window.formatNumberId(item.raw, 2) : item.raw} kWh`
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    grid: { display: false },
                                    ticks: { color: themeColors.text, maxTicksLimit: 12 }
                                },
                                y: {
                                    beginAtZero: true,
                                    grid: { color: themeColors.grid },
                                    ticks: {
                                        color: themeColors.text,
                                        callback: (value) => window.formatNumberId ? window.formatNumberId(value, 1) : value
                                    },
                                    title: {
                                        display: true,
                                        text: 'Energi (kWh)',
                                        color: themeColors.text,
                                        font: {
                                            family: "'Inter', sans-serif",
                                            size: 11,
                                            weight: '500'
                                        }
                                    }
                                }
                            }
                        }
                    });
                }
            };
        }
    </script>
</x-layouts.app>
