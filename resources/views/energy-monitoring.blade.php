<x-layouts.app title="{{ __('Pemantauan Energi') }}">
    <!-- Page Header & Device Selector -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[11px] font-mono font-medium border border-accent/30 bg-accent/10 text-accent">
                    <span class="w-1.5 h-1.5 rounded-full bg-accent"></span>
                    <span>PZEM-017 DC Array</span>
                </span>
                <span class="text-xs font-mono text-muted">{{ __('Telemetri Modbus RTU Live') }}</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-ink mt-1.5">{{ __('Pemantauan Energi & Yield DC') }}</h1>
            <p class="text-xs sm:text-sm text-muted mt-0.5">{{ __('Analitik daya DC presisi tinggi, akumulasi kWh, dan parameter subsistem.') }}</p>
        </div>

        <div class="flex items-center flex-wrap gap-2.5">
            @if($devices->count() > 1)
                <form method="GET" action="{{ route('energy-monitoring') }}" class="flex items-center gap-2">
                    <label for="device-select" class="text-xs font-mono text-muted uppercase">{{ __('Perangkat:') }}</label>
                    <select id="device-select" name="device" onchange="this.form.submit()" class="h-9 text-xs font-mono font-medium bg-panel border border-line rounded px-2.5 py-1 text-ink focus:border-accent focus:outline-none cursor-pointer">
                        @foreach($devices as $dev)
                            <option value="{{ $dev->uid }}" {{ $device && $device->uid === $dev->uid ? 'selected' : '' }}>
                                {{ $dev->name }} ({{ $dev->uid }})
                            </option>
                        @endforeach
                    </select>
                </form>
            @else
                <div class="inline-flex items-center gap-2 h-9 px-3 rounded border border-line bg-panel text-xs font-mono text-ink">
                    <span class="w-1.5 h-1.5 rounded-full bg-ok"></span>
                    <span>{{ $device->name ?? 'ST-DEMO-001' }}</span>
                </div>
            @endif

            <a 
                href="{{ route('solar-tracker') }}" 
                class="inline-flex items-center gap-2 h-9 px-3.5 rounded border border-accent/40 bg-accent/10 hover:bg-accent/20 text-accent text-xs font-mono font-medium transition-colors"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                <span>{{ __('Kontrol Pelacak') }} &rarr;</span>
            </a>
        </div>
    </div>

    <!-- Live Reactive State Container with Alpine.js -->
    <div 
        x-data="energyMonitorComponent(@js($metrics), '{{ $device->uid ?? '' }}')" 
        class="space-y-6"
    >
        <!-- Top Electrical & Energy KPI Cards (5 Cards) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <!-- 1. DC Voltage Card -->
            <div class="bg-panel rounded-md border border-line p-4 flex flex-col justify-between">
                <div class="flex items-start justify-between gap-2 mb-2">
                    <span class="text-xs font-medium text-muted uppercase tracking-wider truncate">{{ __('Tegangan DC') }}</span>
                    <span 
                        class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[11px] font-mono font-medium border"
                        :class="metrics.pzem.voltage >= 200 ? 'border-ok/30 bg-ok/10 text-ok' : 'border-warn/30 bg-warn/10 text-warn'"
                    >
                        <span class="w-1.5 h-1.5 rounded-full" :class="metrics.pzem.voltage >= 200 ? 'bg-ok' : 'bg-warn'"></span>
                        <span x-text="metrics.pzem.voltage >= 200 ? 'NOMINAL' : 'STANDBY'"></span>
                    </span>
                </div>

                <div class="flex items-baseline gap-1 my-1">
                    <span class="text-3xl font-bold font-mono tabular-nums text-ink tracking-tight" x-text="formatNumberId(metrics.pzem.voltage, 1)"></span>
                    <span class="text-xs font-mono text-muted font-normal">V</span>
                </div>

                <div class="mt-2 pt-2 border-t border-line/50 flex items-center justify-between text-xs font-mono text-muted">
                    <span>Array String V</span>
                    <span>200 - 450 V</span>
                </div>
            </div>

            <!-- 2. DC Current Card -->
            <div class="bg-panel rounded-md border border-line p-4 flex flex-col justify-between">
                <div class="flex items-start justify-between gap-2 mb-2">
                    <span class="text-xs font-medium text-muted uppercase tracking-wider truncate">{{ __('Arus DC') }}</span>
                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[11px] font-mono font-medium border border-line bg-bg text-muted">
                        <span class="w-1.5 h-1.5 rounded-full bg-accent"></span>
                        <span>PZEM-017</span>
                    </span>
                </div>

                <div class="flex items-baseline gap-1 my-1">
                    <span class="text-3xl font-bold font-mono tabular-nums text-ink tracking-tight" x-text="formatNumberId(metrics.pzem.current, 2)"></span>
                    <span class="text-xs font-mono text-muted font-normal">A</span>
                </div>

                <div class="mt-2 pt-2 border-t border-line/50 flex items-center justify-between text-xs font-mono text-muted">
                    <span>Arus Shunt</span>
                    <span>Maks: 15.0 A</span>
                </div>
            </div>

            <!-- 3. Instantaneous DC Power Card (W only, exact string: Daya Sesaat) -->
            <div class="bg-panel rounded-md border border-line p-4 flex flex-col justify-between">
                <div class="flex items-start justify-between gap-2 mb-2">
                    <span class="text-xs font-medium text-muted uppercase tracking-wider truncate">{{ __('Daya Sesaat') }}</span>
                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[11px] font-mono font-medium border border-ok/30 bg-ok/10 text-ok">
                        <span class="w-1.5 h-1.5 rounded-full bg-ok animate-pulse"></span>
                        <span>{{ __('YIELD AKTIF') }}</span>
                    </span>
                </div>

                <div class="flex items-baseline gap-1 my-1">
                    <span class="text-3xl font-bold font-mono tabular-nums text-ink tracking-tight" x-text="formatPowerW(metrics.pzem.power_w, false)"></span>
                    <span class="text-xs font-mono text-muted font-normal">W</span>
                </div>

                <div class="mt-2 pt-2 border-t border-line/50 flex items-center justify-between text-xs font-mono text-muted">
                    <span class="truncate" x-text="metrics.pzem.label || '{{ __('Panel surya') }}'"></span>
                    <span class="text-muted/70">PZEM-017</span>
                </div>
            </div>

            <!-- 4. Daily Generation & Target Progress Card (exact string: Energi Harian) -->
            <div class="bg-panel rounded-md border border-line p-4 flex flex-col justify-between">
                <div class="flex items-start justify-between gap-2 mb-2">
                    <span class="text-xs font-medium text-muted uppercase tracking-wider truncate">{{ __('Energi Harian') }}</span>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-mono font-medium border border-accent/30 bg-accent/10 text-accent">
                        <span class="w-1.5 h-1.5 rounded-full bg-accent"></span>
                        <span x-text="metrics.progress_percent + '%'"></span>
                    </span>
                </div>

                <div class="flex items-baseline gap-1 my-1">
                    <span class="text-3xl font-bold font-mono tabular-nums text-ink tracking-tight" x-text="formatNumberId(metrics.daily_energy_kwh, 2)"></span>
                    <span class="text-xs font-mono text-muted font-normal">kWh</span>
                </div>

                <div class="mt-2 pt-2 border-t border-line/50">
                    <div class="w-full bg-line/60 rounded h-1.5 overflow-hidden mb-1.5">
                        <div class="bg-accent h-1.5 rounded transition-all duration-300" :style="'width: ' + Math.min(100, metrics.progress_percent) + '%'"></div>
                    </div>
                    <div class="flex items-center justify-between text-xs font-mono text-muted">
                        <span>{{ __('Target') }}: <span class="text-ink font-medium" x-text="formatNumberId(metrics.daily_target_kwh, 1)"></span> kWh</span>
                        <span :class="metrics.progress_percent >= 100 ? 'text-ok font-semibold' : 'text-muted'" x-text="metrics.progress_percent >= 100 ? '{{ __('Tercapai') }}' : '{{ __('Berjalan') }}'"></span>
                    </div>
                </div>
            </div>

            <!-- 5. Monthly Energy Card (exact string: Total Bulanan) -->
            <div class="bg-panel rounded-md border border-line p-4 flex flex-col justify-between">
                <div class="flex items-start justify-between gap-2 mb-2">
                    <span class="text-xs font-medium text-muted uppercase tracking-wider truncate">{{ __('Total Bulanan') }}</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-mono font-medium border border-line bg-bg text-muted">
                        {{ now()->locale('id')->translatedFormat('M Y') }}
                    </span>
                </div>

                <div class="flex items-baseline gap-1 my-1">
                    <span class="text-3xl font-bold font-mono tabular-nums text-ink tracking-tight" x-text="formatNumberId(metrics.monthly_energy_kwh, 1)"></span>
                    <span class="text-xs font-mono text-muted font-normal">kWh</span>
                </div>

                <div class="mt-2 pt-2 border-t border-line/50 flex items-center justify-between text-xs font-mono text-muted">
                    <span>{{ __('Akumulasi') }}</span>
                    <span class="text-ink font-medium" x-text="formatNumberId(metrics.pzem.energy_wh, 0) + ' Wh'"></span>
                </div>
            </div>
        </div>

        <!-- Charts Section (Grid 2 Kolom: 24h Live Power Line Chart + Today vs Yesterday Bar Chart) -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- 1. 24h Live Power Output (Line, Daya W) -->
            <x-card 
                :title="__('Output Daya Live 24 Jam')" 
                :subtitle="__('Deret waktu daya sesaat kontinu (W) dari PZEM-017')"
            >
                <x-slot:actions>
                    <div class="flex items-center gap-1.5 px-2 py-0.5 rounded border border-line bg-bg text-xs font-mono text-muted">
                        <span class="w-1.5 h-1.5 rounded-full bg-ok animate-pulse"></span>
                        <span class="text-ok font-semibold">LIVE</span>
                    </div>
                </x-slot:actions>

                <div class="min-h-[300px] relative">
                    <canvas id="livePowerChartCanvas"></canvas>
                </div>
            </x-card>

            <!-- 2. Hourly Production: Today vs Yesterday (Bar, exact string: Produksi per Jam) -->
            <x-card 
                :title="__('Produksi per Jam: Hari Ini vs Kemarin')" 
                :subtitle="__('Perbandingan energi kWh per jam (00:00 - 23:00 WIB)')"
            >
                <x-slot:actions>
                    <div class="flex items-center gap-3 text-xs font-mono">
                        <div class="flex items-center gap-1.5 text-accent">
                            <span class="w-2.5 h-2.5 rounded-xs bg-accent inline-block"></span>
                            <span>{{ __('Hari Ini') }}</span>
                        </div>
                        <div class="flex items-center gap-1.5 text-muted">
                            <span class="w-2.5 h-2.5 rounded-xs bg-[#475569] inline-block"></span>
                            <span>{{ __('Kemarin') }}</span>
                        </div>
                    </div>
                </x-slot:actions>

                <div class="min-h-[300px] relative">
                    <canvas id="compareProductionChartCanvas"></canvas>
                </div>
            </x-card>
        </div>

        <!-- Sub-System Parameters Section (Grid 4 Kolom) -->
        <x-card 
            :title="__('Status Sub-Sistem & Diagnostik Sensor')" 
            :subtitle="__('Parameter lingkungan, kinematika, dan termal dari mikrokontroler ESP32')"
        >
            <x-slot:actions>
                <div class="flex items-center gap-2 text-xs font-mono text-muted">
                    <span>{{ __('Data terakhir') }}:</span>
                    <span class="text-ink font-medium" x-text="metrics.recorded_at_wib"></span>
                </div>
            </x-slot:actions>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- 1. PZEM-017 DC Solar Array Diagnostic -->
                <div class="p-3.5 rounded border border-line bg-bg/50">
                    <div class="flex items-center justify-between mb-2.5">
                        <span class="text-xs font-semibold font-mono text-ink uppercase">{{ __('Sensor PZEM-017') }}</span>
                        <span class="text-[10px] font-mono px-1.5 py-0.5 rounded border border-line bg-bg text-muted">Modbus RTU</span>
                    </div>
                    <div class="space-y-1.5 text-xs font-mono">
                        <div class="flex justify-between">
                            <span class="text-muted">{{ __('Label:') }}</span>
                            <span class="text-ink truncate font-medium" x-text="metrics.pzem.label || '{{ __('Panel surya') }}'"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted">{{ __('Tegangan:') }}</span>
                            <span class="text-ink font-medium" x-text="formatNumberId(metrics.pzem.voltage, 1) + ' V'"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted">{{ __('Arus:') }}</span>
                            <span class="text-ink font-medium" x-text="formatNumberId(metrics.pzem.current, 2) + ' A'"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted">{{ __('Daya:') }}</span>
                            <span class="text-accent font-semibold" x-text="formatPowerW(metrics.pzem.power_w)"></span>
                        </div>
                        <div class="flex justify-between pt-1.5 border-t border-line/50">
                            <span class="text-muted">{{ __('Akumulasi:') }}</span>
                            <span class="text-ink font-medium" x-text="formatNumberId(metrics.pzem.energy_kwh, 2) + ' kWh'"></span>
                        </div>
                    </div>
                </div>

                <!-- 2. Wind Anemometer (wind_ms vs wind_stow_ms) -->
                <div class="p-3.5 rounded border border-line bg-bg/50">
                    <div class="flex items-center justify-between mb-2.5">
                        <span class="text-xs font-semibold font-mono text-ink uppercase">{{ __('Anemometer Angin') }}</span>
                        <span 
                            class="inline-flex items-center gap-1.5 px-1.5 py-0.5 rounded text-[10px] font-mono font-medium border"
                            :class="metrics.subsystem.wind_ms >= metrics.subsystem.wind_stow_ms ? 'border-danger/30 bg-danger/10 text-danger' : 'border-ok/30 bg-ok/10 text-ok'"
                        >
                            <span class="w-1.5 h-1.5 rounded-full" :class="metrics.subsystem.wind_ms >= metrics.subsystem.wind_stow_ms ? 'bg-danger' : 'bg-ok'"></span>
                            <span x-text="metrics.subsystem.wind_ms >= metrics.subsystem.wind_stow_ms ? '{{ __('ANGIN KENCANG') }}' : '{{ __('AMAN') }}'"></span>
                        </span>
                    </div>
                    <div class="space-y-1.5 text-xs font-mono">
                        <div class="flex justify-between">
                            <span class="text-muted">{{ __('Kecepatan:') }}</span>
                            <span class="text-ink font-medium" x-text="formatNumberId(metrics.subsystem.wind_ms, 1) + ' m/s'"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted">{{ __('Ambang Stow:') }}</span>
                            <span class="text-muted" x-text="formatNumberId(metrics.subsystem.wind_stow_ms, 1) + ' m/s'"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted">{{ __('Interlock:') }}</span>
                            <span :class="metrics.subsystem.wind_ms >= metrics.subsystem.wind_stow_ms ? 'text-danger font-bold' : 'text-ok font-medium'">
                                <span x-text="metrics.subsystem.wind_ms >= metrics.subsystem.wind_stow_ms ? '{{ __('MODE AMAN AKTIF') }}' : '{{ __('Normal') }}'"></span>
                            </span>
                        </div>
                        <div class="pt-1.5 border-t border-line/50 text-[11px] text-muted leading-tight">
                            {{ __('Proteksi hembusan angin ≥ 15.0 m/s') }}
                        </div>
                    </div>
                </div>

                <!-- 3. DS3231 RTC & Cabinet Environment (temp_c, drift_s) -->
                <div class="p-3.5 rounded border border-line bg-bg/50">
                    <div class="flex items-center justify-between mb-2.5">
                        <span class="text-xs font-semibold font-mono text-ink uppercase">{{ __('Kotak Kontrol & RTC') }}</span>
                        <span 
                            class="inline-flex items-center gap-1.5 px-1.5 py-0.5 rounded text-[10px] font-mono font-medium border"
                            :class="metrics.subsystem.temp_c > 50 ? 'border-warn/30 bg-warn/10 text-warn' : 'border-ok/30 bg-ok/10 text-ok'"
                        >
                            <span class="w-1.5 h-1.5 rounded-full" :class="metrics.subsystem.temp_c > 50 ? 'bg-warn' : 'bg-ok'"></span>
                            <span x-text="metrics.subsystem.temp_c > 50 ? 'TINGGI' : 'NOMINAL'"></span>
                        </span>
                    </div>
                    <div class="space-y-1.5 text-xs font-mono">
                        <div class="flex justify-between">
                            <span class="text-muted">{{ __('Suhu Kotak:') }}</span>
                            <span class="text-ink font-medium" x-text="formatNumberId(metrics.subsystem.temp_c, 1) + ' °C'"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted">{{ __('Sumber Jam:') }}</span>
                            <span class="text-ink">DS3231 TCXO</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted">{{ __('Drift RTC:') }}</span>
                            <span class="text-ink font-medium" x-text="metrics.subsystem.drift_s + ' s'"></span>
                        </div>
                        <div class="pt-1.5 border-t border-line/50 text-[11px] text-muted leading-tight">
                            {{ __('Sensor suhu kristal internal TCXO') }}
                        </div>
                    </div>
                </div>

                <!-- 4. Tracker Kinematics & BNO055 IMU (elev/target_elev, az/target_az) -->
                <div class="p-3.5 rounded border border-line bg-bg/50">
                    <div class="flex items-center justify-between mb-2.5">
                        <span class="text-xs font-semibold font-mono text-ink uppercase">{{ __('Kinematika Posisi') }}</span>
                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-mono font-medium border border-accent/30 bg-accent/10 text-accent">
                            <span class="w-1.5 h-1.5 rounded-full bg-accent"></span>
                            <span>IMU <span x-text="metrics.subsystem.cal_sys"></span>/3</span>
                        </span>
                    </div>
                    <div class="space-y-1.5 text-xs font-mono">
                        <div class="flex justify-between">
                            <span class="text-muted">{{ __('Elevasi:') }}</span>
                            <span class="text-ink font-medium">
                                <span x-text="formatNumberId(metrics.subsystem.elev, 1)"></span>°
                                <span class="text-muted text-[11px]">(T: <span x-text="formatNumberId(metrics.subsystem.target_elev, 1)"></span>°)</span>
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted">{{ __('Azimut:') }}</span>
                            <span class="text-ink font-medium">
                                <span x-text="formatNumberId(metrics.subsystem.az, 1)"></span>°
                                <span class="text-muted text-[11px]">(T: <span x-text="formatNumberId(metrics.subsystem.target_az, 1)"></span>°)</span>
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted">{{ __('Aktuator:') }}</span>
                            <span class="text-ink uppercase text-[11px]">
                                E: <span x-text="metrics.subsystem.elev_state"></span> | A: <span x-text="metrics.subsystem.az_state"></span>
                            </span>
                        </div>
                        <div class="pt-1.5 border-t border-line/50 text-[11px] text-muted leading-tight">
                            {{ __('Aktuator linier BTS7960 & BNO055') }}
                        </div>
                    </div>
                </div>
            </div>
        </x-card>
    </div>

    <!-- Alpine.js Component & Short-Polling Script -->
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
                    });

                    // Polling data JSON setiap 5 detik
                    this.pollTimer = setInterval(() => {
                        this.fetchLatestData();
                    }, 5000);
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
                                borderColor: '#3b82f6',
                                borderWidth: 2,
                                backgroundColor: 'transparent',
                                fill: false,
                                tension: 0.25,
                                pointRadius: 0,
                                pointHoverRadius: 4,
                                pointBackgroundColor: '#3b82f6',
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
                                    grid: { color: 'rgba(55, 65, 81, 0.45)' },
                                    ticks: { maxTicksLimit: 12 }
                                },
                                y: {
                                    beginAtZero: true,
                                    grid: { color: 'rgba(55, 65, 81, 0.45)' },
                                    ticks: {
                                        callback: (value) => window.formatNumberId ? window.formatNumberId(value, 0) : value
                                    },
                                    title: {
                                        display: true,
                                        text: 'Daya (W)',
                                        color: '#9ca3af',
                                        font: {
                                            family: "'JetBrains Mono', monospace",
                                            size: 11
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
                                    backgroundColor: '#3b82f6',
                                    borderRadius: 4,
                                    barPercentage: 0.6,
                                },
                                {
                                    label: yesterdayLabel,
                                    data: this.metrics.chart_compare.yesterday,
                                    backgroundColor: '#475569',
                                    borderRadius: 4,
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
                                    grid: { color: 'rgba(55, 65, 81, 0.45)' },
                                    ticks: {
                                        callback: (value) => window.formatNumberId ? window.formatNumberId(value, 1) : value
                                    },
                                    title: {
                                        display: true,
                                        text: 'Energi (kWh)',
                                        color: '#9ca3af',
                                        font: {
                                            family: "'JetBrains Mono', monospace",
                                            size: 11
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
