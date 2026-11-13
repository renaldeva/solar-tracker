<x-layouts.app title="{{ __('Pemantauan Energi') }}">
    <!-- Page Header & Device Selector -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold border border-blue-200 bg-blue-50 text-blue-700">
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
                    <select id="device-select" name="device" onchange="this.form.submit()" class="h-10 text-xs sm:text-sm font-medium bg-white border border-line rounded-xl px-3 py-1 text-ink focus:border-blue-500 focus:outline-none cursor-pointer shadow-2xs">
                        @foreach($devices as $dev)
                            <option value="{{ $dev->uid }}" {{ $device && $device->uid === $dev->uid ? 'selected' : '' }}>
                                {{ $dev->name }} ({{ $dev->uid }})
                            </option>
                        @endforeach
                    </select>
                </form>
            @else
                <div class="inline-flex items-center gap-2 h-10 px-3.5 rounded-xl border border-line bg-white text-xs sm:text-sm font-medium text-ink shadow-2xs">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span>{{ $device->name ?? 'ST-DEMO-001' }}</span>
                </div>
            @endif

            <a 
                href="{{ route('solar-tracker') }}" 
                class="inline-flex items-center gap-1.5 h-10 px-4 rounded-xl border border-blue-200 bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs sm:text-sm font-semibold transition-colors shadow-2xs"
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
        <div class="p-4 rounded-2xl border flex items-center justify-between gap-3 bg-emerald-50/80 border-emerald-200 text-emerald-950">
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
                    <p class="text-xs text-slate-600 mt-0.5">
                        {{ __('Sensor PZEM-017 aktif membaca arus dan tegangan. Arus listrik mengalir stabil.') }}
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>POLLING AKTIF (5s)</span>
                </span>
            </div>
        </div>

        <!-- Top Electrical & Energy KPI Cards (5 Cards) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <!-- 1. DC Voltage Card -->
            <div class="bg-white rounded-2xl border border-line p-5 flex flex-col justify-between hover:border-blue-200 transition-colors">
                <div class="flex items-start justify-between gap-2 mb-2">
                    <span class="text-xs font-semibold text-muted uppercase tracking-wider truncate">{{ __('Tegangan DC') }}</span>
                    <span 
                        class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold border"
                        :class="metrics.pzem.voltage >= 200 ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-amber-200 bg-amber-50 text-amber-700'"
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

                <div class="mt-2 pt-2 border-t border-slate-100 flex items-center justify-between text-xs text-muted">
                    <span>{{ __('Rentang nominal') }}</span>
                    <span class="font-mono text-slate-700 font-medium">200 - 450 V</span>
                </div>
            </div>

            <!-- 2. DC Current Card -->
            <div class="bg-white rounded-2xl border border-line p-5 flex flex-col justify-between hover:border-blue-200 transition-colors">
                <div class="flex items-start justify-between gap-2 mb-2">
                    <span class="text-xs font-semibold text-muted uppercase tracking-wider truncate">{{ __('Arus DC') }}</span>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold border border-blue-200 bg-blue-50 text-blue-700">
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

                <div class="mt-2 pt-2 border-t border-slate-100 flex items-center justify-between text-xs text-muted">
                    <span>{{ __('Kuat arus beban') }}</span>
                    <span class="font-mono text-slate-700 font-medium">Maks 15.0 A</span>
                </div>
            </div>

            <!-- 3. Instantaneous DC Power Card (W only) -->
            <div class="bg-white rounded-2xl border border-line p-5 flex flex-col justify-between hover:border-blue-200 transition-colors">
                <div class="flex items-start justify-between gap-2 mb-2">
                    <span class="text-xs font-semibold text-muted uppercase tracking-wider truncate">{{ __('Daya Sesaat') }}</span>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold border border-emerald-200 bg-emerald-50 text-emerald-700">
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
            <div class="bg-white rounded-2xl border border-line p-5 flex flex-col justify-between hover:border-blue-200 transition-colors">
                <div class="flex items-start justify-between gap-2 mb-2">
                    <span class="text-xs font-semibold text-muted uppercase tracking-wider truncate">{{ __('Energi Harian') }}</span>
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold border border-blue-200 bg-blue-50 text-blue-700">
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

                <div class="mt-2 pt-2 border-t border-slate-100">
                    <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden mb-1.5">
                        <div class="bg-blue-600 h-1.5 rounded-full transition-all duration-300" :style="'width: ' + Math.min(100, metrics.progress_percent) + '%'"></div>
                    </div>
                    <div class="flex items-center justify-between text-xs text-muted">
                        <span>{{ __('Target') }}: <span class="text-slate-800 font-semibold font-mono" x-text="formatNumberId(metrics.daily_target_kwh, 1)"></span> kWh</span>
                        <span :class="metrics.progress_percent >= 100 ? 'text-emerald-600 font-bold' : 'text-slate-500'" x-text="metrics.progress_percent >= 100 ? '{{ __('Tercapai') }}' : '{{ __('Berjalan') }}'"></span>
                    </div>
                </div>
            </div>

            <!-- 5. Monthly Energy Card -->
            <div class="bg-white rounded-2xl border border-line p-5 flex flex-col justify-between hover:border-blue-200 transition-colors">
                <div class="flex items-start justify-between gap-2 mb-2">
                    <span class="text-xs font-semibold text-muted uppercase tracking-wider truncate">{{ __('Total Bulanan') }}</span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold border border-slate-200 bg-slate-100 text-slate-700">
                        {{ now()->locale('id')->translatedFormat('M Y') }}
                    </span>
                </div>

                <div class="my-1">
                    <div class="flex items-baseline gap-1">
                        <span class="text-2xl sm:text-3xl font-bold font-mono tabular-nums text-ink tracking-tight" x-text="formatNumberId(metrics.monthly_energy_kwh, 1)"></span>
                        <span class="text-sm font-semibold text-muted font-mono">kWh</span>
                    </div>
                </div>

                <div class="mt-2 pt-2 border-t border-slate-100 flex items-center justify-between text-xs text-muted">
                    <span>{{ __('Akumulasi') }}</span>
                    <span class="text-slate-800 font-semibold font-mono" x-text="formatNumberId(metrics.pzem.energy_wh, 0) + ' Wh'"></span>
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
                    <div class="flex items-center gap-1.5 px-2.5 py-0.5 rounded-full border border-emerald-200 bg-emerald-50 text-xs font-semibold text-emerald-700">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span>LIVE</span>
                    </div>
                </x-slot:actions>

                <div class="min-h-[300px] relative">
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
                        <div class="flex items-center gap-1.5 text-blue-700 font-semibold">
                            <span class="w-3 h-3 rounded-full bg-blue-600 inline-block"></span>
                            <span>{{ __('Hari Ini') }}</span>
                        </div>
                        <div class="flex items-center gap-1.5 text-slate-500 font-semibold">
                            <span class="w-3 h-3 rounded-full bg-blue-200 inline-block"></span>
                            <span>{{ __('Kemarin') }}</span>
                        </div>
                    </div>
                </x-slot:actions>

                <div class="min-h-[300px] relative">
                    <canvas id="compareProductionChartCanvas"></canvas>
                </div>
            </x-card>
        </div>

        <!-- Sub-System Parameters Section (Di dalam Accordion "Lihat detail") -->
        <div x-data="{ showDetail: false }" class="mb-6">
            <button 
                type="button" 
                @click="showDetail = !showDetail" 
                class="w-full flex items-center justify-between p-4 rounded-2xl border border-line bg-white hover:bg-slate-50 text-sm font-semibold text-slate-800 transition-colors cursor-pointer shadow-2xs"
            >
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0 border border-blue-100">
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
                        <div class="p-4 rounded-xl border border-line bg-slate-50/60">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-xs font-bold text-ink uppercase">{{ __('Sensor PZEM-017') }}</span>
                                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full border border-blue-200 bg-blue-50 text-blue-700">Modbus RTU</span>
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
                                    <span class="text-blue-600 font-bold font-mono" x-text="formatPowerW(metrics.pzem.power_w)"></span>
                                </div>
                                <div class="flex justify-between pt-2 border-t border-slate-200">
                                    <span class="text-muted">{{ __('Akumulasi:') }}</span>
                                    <span class="text-ink font-bold font-mono" x-text="formatNumberId(metrics.pzem.energy_kwh, 2) + ' kWh'"></span>
                                </div>
                            </div>
                        </div>

                        <!-- 2. Wind Speed & Stow Threshold Subsystem -->
                        <div class="p-4 rounded-xl border border-line bg-slate-50/60">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-xs font-bold text-ink uppercase">{{ __('Kecepatan Angin') }}</span>
                                <span 
                                    class="text-[10px] font-semibold px-2 py-0.5 rounded-full border"
                                    :class="metrics.subsystem.wind_alert ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700'"
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
                                    <span class="text-slate-700 font-mono font-medium" x-text="formatNumberId(metrics.subsystem.wind_stow_ms, 1) + ' m/s'"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted">{{ __('Status Stow:') }}</span>
                                    <span :class="metrics.subsystem.is_stowed ? 'text-amber-700 font-bold' : 'text-slate-700 font-medium'" x-text="metrics.subsystem.is_stowed ? '{{ __('Aktif (Datar)') }}' : '{{ __('Tidak Aktif') }}'"></span>
                                </div>
                                <div class="pt-2 border-t border-slate-200">
                                    <p class="text-[11px] text-muted leading-tight">
                                        {{ __('Panel otomatis mendatar jika angin melebihi batas.') }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- 3. Kinematic Position & Tracking Alignment -->
                        <div class="p-4 rounded-xl border border-line bg-slate-50/60">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-xs font-bold text-ink uppercase">{{ __('Orientasi Tracker') }}</span>
                                <span 
                                    class="text-[10px] font-semibold px-2 py-0.5 rounded-full border"
                                    :class="metrics.subsystem.is_aligned ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-amber-200 bg-amber-50 text-amber-700'"
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
                                    <span class="font-bold font-mono" :class="metrics.subsystem.is_aligned ? 'text-emerald-700' : 'text-amber-700'" x-text="formatNumberId(metrics.subsystem.total_dev, 1) + '°'"></span>
                                </div>
                                <div class="flex justify-between pt-2 border-t border-slate-200">
                                    <span class="text-muted">{{ __('Batas Toleransi:') }}</span>
                                    <span class="text-slate-700 font-mono font-medium" x-text="formatNumberId(metrics.subsystem.threshold, 1) + '°'"></span>
                                </div>
                            </div>
                        </div>

                        <!-- 4. Thermal & Enclosure Health Diagnostic -->
                        <div class="p-4 rounded-xl border border-line bg-slate-50/60">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-xs font-bold text-ink uppercase">{{ __('Suhu & Termal') }}</span>
                                <span 
                                    class="text-[10px] font-semibold px-2 py-0.5 rounded-full border"
                                    :class="metrics.subsystem.temp_c >= 55 ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700'"
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
                                    <span class="text-slate-700 font-mono font-medium">55.0 °C</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted">{{ __('Suhu DS3231:') }}</span>
                                    <span class="text-slate-700 font-mono font-medium" x-text="formatNumberId(metrics.subsystem.temp_c, 1) + ' °C'"></span>
                                </div>
                                <div class="pt-2 border-t border-slate-200">
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
                metrics: initialMetrics,
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
                },

                destroy() {
                    if (this.pollTimer) {
                        clearInterval(this.pollTimer);
                    }
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
                        .then(res => res.json())
                        .then(data => {
                            if (!data || !data.pzem) return;
                            this.metrics = data;

                            // Update grafik live 24 jam
                            if (this.liveChart && data.chart_24h) {
                                this.liveChart.data.labels = data.chart_24h.labels;
                                this.liveChart.data.datasets[0].data = data.chart_24h.values;
                                this.liveChart.update('none');
                            }

                            // Update grafik perbandingan hari ini vs kemarin
                            if (this.compareChart && data.chart_compare) {
                                this.compareChart.data.labels = data.chart_compare.labels;
                                this.compareChart.data.datasets[0].data = data.chart_compare.today;
                                this.compareChart.data.datasets[1].data = data.chart_compare.yesterday;
                                this.compareChart.update('none');
                            }
                        })
                        .catch(err => console.debug('Polling jeda:', err));
                },

                startPolling() {
                    // Poll setiap 5 detik sesuai requirement
                    this.pollTimer = setInterval(() => {
                        this.fetchLatestData();
                    }, 5000);
                },

                initLiveChart() {
                    const ctx = document.getElementById('livePowerChartCanvas');
                    if (!ctx) return;

                    const powerLabel = window.__ ? window.__('Daya (W)') : 'Daya (W)';

                    this.liveChart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: this.metrics.chart_24h.labels,
                            datasets: [{
                                label: powerLabel,
                                data: this.metrics.chart_24h.values,
                                borderColor: '#2563eb',
                                borderWidth: 2.5,
                                backgroundColor: 'rgba(37, 99, 235, 0.08)',
                                fill: true,
                                tension: 0.35,
                                pointRadius: 0,
                                pointHoverRadius: 5,
                                pointBackgroundColor: '#2563eb',
                                pointBorderColor: '#ffffff',
                                pointBorderWidth: 2,
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
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
                                    grid: { color: 'rgba(226, 232, 240, 0.7)' },
                                    ticks: { maxTicksLimit: 12 }
                                },
                                y: {
                                    beginAtZero: true,
                                    grid: { color: 'rgba(226, 232, 240, 0.7)' },
                                    ticks: {
                                        callback: (value) => window.formatNumberId ? window.formatNumberId(value, 0) : value
                                    },
                                    title: {
                                        display: true,
                                        text: 'Daya (W)',
                                        color: '#64748b',
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

                    const todayLabel = window.__ ? window.__('Hari Ini (kWh)') : 'Hari Ini (kWh)';
                    const yesterdayLabel = window.__ ? window.__('Kemarin (kWh)') : 'Kemarin (kWh)';

                    this.compareChart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: this.metrics.chart_compare.labels,
                            datasets: [
                                {
                                    label: todayLabel,
                                    data: this.metrics.chart_compare.today,
                                    backgroundColor: '#2563eb',
                                    borderRadius: 6,
                                    barPercentage: 0.6,
                                },
                                {
                                    label: yesterdayLabel,
                                    data: this.metrics.chart_compare.yesterday,
                                    backgroundColor: '#93c5fd',
                                    borderRadius: 6,
                                    barPercentage: 0.6,
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
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
                                    ticks: { maxTicksLimit: 12 }
                                },
                                y: {
                                    beginAtZero: true,
                                    grid: { color: 'rgba(226, 232, 240, 0.7)' },
                                    ticks: {
                                        callback: (value) => window.formatNumberId ? window.formatNumberId(value, 1) : value
                                    },
                                    title: {
                                        display: true,
                                        text: 'Energi (kWh)',
                                        color: '#64748b',
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
