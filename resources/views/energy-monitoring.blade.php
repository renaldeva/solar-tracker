<x-layouts.app title="{{ __('Energy Monitoring') }}">
    <!-- Page Header & Device Selector -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2">
                <span class="inline-flex items-center px-2.5 py-1 rounded text-xs font-bold uppercase tracking-wider bg-blue-50 text-blue-700 border border-blue-200/60">
                    PZEM-017 DC Array
                </span>
                <span class="text-xs sm:text-sm font-medium text-slate-400">{{ __('Live Modbus RTU Telemetry') }}</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900 mt-1.5">{{ __('Energy Monitoring & DC Solar Yield') }}</h1>
            <p class="text-sm sm:text-base text-slate-500 mt-0.5">{{ __('High-precision DC generation analytics, cumulative kWh rollups, and sub-system parameters.') }}</p>
        </div>

        <div class="flex items-center space-x-3">
            @if($devices->count() > 1)
                <form method="GET" action="{{ route('energy-monitoring') }}" class="flex items-center space-x-2">
                    <label for="device-select" class="text-sm font-semibold text-slate-600">{{ __('Tracker:') }}</label>
                    <select id="device-select" name="device" onchange="this.form.submit()" class="h-11 min-h-[44px] text-sm font-medium bg-white border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 shadow-2xs">
                        @foreach($devices as $dev)
                            <option value="{{ $dev->uid }}" {{ $device && $device->uid === $dev->uid ? 'selected' : '' }}>
                                {{ $dev->name }} ({{ $dev->uid }})
                            </option>
                        @endforeach
                    </select>
                </form>
            @else
                <div class="inline-flex items-center h-11 min-h-[44px] px-3.5 py-2 rounded-lg bg-white border border-slate-200/80 text-sm font-medium text-slate-700 shadow-2xs">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 mr-2"></span>
                    {{ $device->name ?? 'ST-DEMO-001' }}
                </div>
            @endif

            <a href="{{ route('solar-tracker') }}" class="inline-flex items-center h-11 min-h-[44px] px-4 py-2.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-xs transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                <span>{{ __('Live Tracker Control') }}</span>
            </a>
        </div>
    </div>

    <!-- Live Reactive State Container with Alpine.js -->
    <div 
        x-data="energyMonitorComponent(@js($metrics), '{{ $device->uid ?? '' }}')" 
        class="space-y-6"
    >
        <!-- Top Electrical & Energy KPI Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6">
            <!-- DC Voltage Card -->
            <div class="bg-white rounded-xl border border-slate-200/80 p-6 shadow-2xs relative overflow-hidden flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs sm:text-[13px] font-semibold text-slate-500 uppercase tracking-wider">{{ __('DC Voltage') }}</span>
                    <span class="text-xs font-bold px-2 py-0.5 rounded uppercase font-mono"
                          :class="metrics.pzem.voltage >= 200 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200'">
                        <span x-text="metrics.pzem.voltage >= 200 ? '{{ __('NOMINAL') }}' : '{{ __('STANDBY') }}'"></span>
                    </span>
                </div>
                <div class="mt-3 flex items-baseline space-x-1.5">
                    <span class="text-3xl sm:text-4xl font-bold tracking-tight text-slate-900 font-mono" x-text="formatNumberId(metrics.pzem.voltage, 1)"></span>
                    <span class="text-sm font-semibold text-slate-500">V</span>
                </div>
                <div class="mt-3 flex items-center justify-between text-xs text-slate-400">
                    <span>Array String V</span>
                    <span>Range: 200 - 450V</span>
                </div>
            </div>

            <!-- DC Current Card -->
            <div class="bg-white rounded-xl border border-slate-200/80 p-6 shadow-2xs relative overflow-hidden flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs sm:text-[13px] font-semibold text-slate-500 uppercase tracking-wider">{{ __('DC Current') }}</span>
                    <span class="text-xs font-bold px-2 py-0.5 rounded uppercase font-mono bg-blue-50 text-blue-700 border border-blue-200">
                        PZEM-017
                    </span>
                </div>
                <div class="mt-3 flex items-baseline space-x-1.5">
                    <span class="text-3xl sm:text-4xl font-bold tracking-tight text-slate-900 font-mono" x-text="formatNumberId(metrics.pzem.current, 2)"></span>
                    <span class="text-sm font-semibold text-slate-500">A</span>
                </div>
                <div class="mt-3 flex items-center justify-between text-xs text-slate-400">
                    <span>Shunt Current</span>
                    <span>Max: 15.0A</span>
                </div>
            </div>

            <!-- Instantaneous DC Power Card (Revision 3: Rename to 'Daya Sesaat', Single value in W only, No secondary badge, No kW) -->
            <div class="bg-white rounded-xl border border-slate-200/80 p-6 shadow-2xs relative overflow-hidden flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs sm:text-[13px] font-semibold text-slate-500 uppercase tracking-wider">{{ __('Daya Sesaat') }}</span>
                    <span class="text-xs font-medium text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200/60">{{ __('Active Yield') }}</span>
                </div>
                <div class="mt-3 flex items-baseline space-x-1.5">
                    <span class="text-3xl sm:text-4xl font-bold tracking-tight text-slate-900 font-mono" x-text="formatPowerW(metrics.pzem.power_w, false)"></span>
                    <span class="text-sm font-semibold text-slate-500">W</span>
                </div>
                <div class="mt-3 flex items-center justify-between text-xs text-slate-500">
                    <span class="font-medium" x-text="metrics.pzem.label || '{{ __('Panel surya') }}'"></span>
                    <span class="text-slate-400 font-mono">PZEM-017</span>
                </div>
            </div>

            <!-- Daily Generation & Target Progress Card (ENERGY in kWh) -->
            <div class="bg-white rounded-xl border border-slate-200/80 p-6 shadow-2xs relative overflow-hidden flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs sm:text-[13px] font-semibold text-slate-500 uppercase tracking-wider">{{ __('Daily Energy') }}</span>
                    <span class="text-xs font-bold text-blue-600 font-mono" x-text="metrics.progress_percent + '%'"></span>
                </div>
                <div class="mt-3 flex items-baseline space-x-1.5">
                    <span class="text-3xl sm:text-4xl font-bold tracking-tight text-slate-900 font-mono" x-text="formatNumberId(metrics.daily_energy_kwh, 2)"></span>
                    <span class="text-sm font-semibold text-slate-500">kWh</span>
                </div>
                <div class="mt-3 w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                    <div class="bg-blue-600 h-2 rounded-full transition-all duration-500" :style="'width: ' + Math.min(100, metrics.progress_percent) + '%'"></div>
                </div>
                <div class="mt-1.5 flex items-center justify-between text-xs text-slate-400">
                    <span>{{ __('Target:') }} <span x-text="formatNumberId(metrics.daily_target_kwh, 1)"></span> kWh</span>
                    <span x-text="metrics.progress_percent >= 100 ? '{{ __('Goal Met') }}' : '{{ __('In Progress') }}'"></span>
                </div>
            </div>

            <!-- Monthly Energy Card (ENERGY in kWh) -->
            <div class="bg-white rounded-xl border border-slate-200/80 p-6 shadow-2xs relative overflow-hidden flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs sm:text-[13px] font-semibold text-slate-500 uppercase tracking-wider">{{ __('Monthly Total') }}</span>
                    <span class="text-xs font-bold px-2 py-0.5 rounded bg-slate-100 text-slate-600 font-mono">
                        {{ now()->locale(app()->getLocale())->translatedFormat('M Y') }}
                    </span>
                </div>
                <div class="mt-3 flex items-baseline space-x-1.5">
                    <span class="text-3xl sm:text-4xl font-bold tracking-tight text-slate-900 font-mono" x-text="formatNumberId(metrics.monthly_energy_kwh, 1)"></span>
                    <span class="text-sm font-semibold text-slate-500">kWh</span>
                </div>
                <div class="mt-3 flex items-center justify-between text-xs text-slate-400">
                    <span>{{ __('Cumulative') }}</span>
                    <span class="text-slate-500 font-mono" x-text="formatNumberId(metrics.pzem.energy_wh, 0) + ' Wh raw'"></span>
                </div>
            </div>
        </div>

        <!-- Charts Section (Grid: 24h Live Power in W + Today vs Yesterday Production in kWh) -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- 24h Live Power Line Chart (in W only, extra chip removed per Revision 3) -->
            <div class="bg-white rounded-xl border border-slate-200/80 shadow-2xs p-6">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900 flex items-center space-x-2">
                            <span>{{ __('24-Hour Live Power Output') }}</span>
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        </h2>
                        <p class="text-sm text-slate-500 mt-0.5">{{ __('Continuous instantaneous DC wattage from PZEM-017') }}</p>
                    </div>
                </div>
                <div class="min-h-[320px] relative">
                    <canvas id="livePowerChartCanvas"></canvas>
                </div>
            </div>

            <!-- Hourly Production: Today vs Yesterday Bar Chart (in kWh) -->
            <div class="bg-white rounded-xl border border-slate-200/80 shadow-2xs p-6">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">{{ __('Hourly Production: Today vs. Yesterday') }}</h2>
                        <p class="text-sm text-slate-500 mt-0.5">{{ __('Hour-by-hour kWh comparison (00:00 - 23:00 WIB)') }}</p>
                    </div>
                    <div class="flex items-center space-x-4 text-sm font-medium">
                        <span class="flex items-center space-x-1.5 text-blue-600">
                            <span class="w-3 h-3 rounded-xs bg-blue-600 inline-block"></span>
                            <span>{{ __('Today') }}</span>
                        </span>
                        <span class="flex items-center space-x-1.5 text-slate-400">
                            <span class="w-3 h-3 rounded-xs bg-slate-300 inline-block"></span>
                            <span>{{ __('Yesterday') }}</span>
                        </span>
                    </div>
                </div>
                <div class="min-h-[320px] relative">
                    <canvas id="compareProductionChartCanvas"></canvas>
                </div>
            </div>
        </div>

        <!-- Sub-System Parameters Section -->
        <div class="bg-white rounded-xl border border-slate-200/80 shadow-2xs p-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-slate-100 gap-2 mb-5">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">{{ __('Sub-System Parameters & Sensor Health') }}</h2>
                    <p class="text-sm text-slate-500 mt-0.5">
                        {{ __('Environmental, kinematic, and thermal diagnostics from ESP32 edge controller') }}
                    </p>
                </div>
                <div class="flex items-center space-x-2 text-xs sm:text-sm text-slate-500 font-mono">
                    <span>{{ __('Last data received') }}:</span>
                    <span class="font-semibold text-slate-700" x-text="metrics.recorded_at_wib"></span>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- PZEM-017 DC Solar Array Diagnostic (Power in W only via formatPowerW) -->
                <div class="p-5 rounded-xl bg-slate-50/80 border border-slate-200/70">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-sm font-bold text-slate-700">{{ __('PZEM-017 Sensor') }}</span>
                        <span class="text-xs font-semibold text-blue-600 bg-blue-50 px-2 py-0.5 rounded border border-blue-200/60">Modbus RTU</span>
                    </div>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-slate-500">{{ __('Measurement Label:') }}</span>
                            <span class="font-semibold text-slate-800" x-text="metrics.pzem.label || '{{ __('Panel surya') }}'"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">{{ __('DC Voltage:') }}</span>
                            <span class="font-mono font-semibold text-slate-800" x-text="formatNumberId(metrics.pzem.voltage, 1) + ' V'"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">{{ __('DC Current:') }}</span>
                            <span class="font-mono font-semibold text-slate-800" x-text="formatNumberId(metrics.pzem.current, 2) + ' A'"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">{{ __('Array Power:') }}</span>
                            <span class="font-mono font-semibold text-blue-600" x-text="formatPowerW(metrics.pzem.power_w)"></span>
                        </div>
                        <div class="flex justify-between pt-2 border-t border-slate-200/60">
                            <span class="text-slate-500">{{ __('Cumulative Energy:') }}</span>
                            <span class="font-mono font-semibold text-slate-900" x-text="formatNumberId(metrics.pzem.energy_kwh, 2) + ' kWh'"></span>
                        </div>
                    </div>
                </div>

                <!-- Wind Anemometer -->
                <div class="p-5 rounded-xl bg-slate-50/80 border border-slate-200/70">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-sm font-bold text-slate-700">{{ __('Wind Anemometer') }}</span>
                        <span class="text-xs font-semibold px-2 py-0.5 rounded font-mono"
                              :class="metrics.subsystem.wind_ms >= metrics.subsystem.wind_stow_ms ? 'bg-red-50 text-red-700 border border-red-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200'">
                            <span x-text="metrics.subsystem.wind_ms >= metrics.subsystem.wind_stow_ms ? '{{ __('ANGIN KENCANG') }}' : '{{ __('AMAN') }}'"></span>
                        </span>
                    </div>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-slate-500">{{ __('Current Velocity:') }}</span>
                            <span class="font-mono font-semibold text-slate-800" x-text="formatNumberId(metrics.subsystem.wind_ms, 1) + ' m/s'"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">{{ __('Stow Threshold:') }}</span>
                            <span class="font-mono text-slate-600" x-text="formatNumberId(metrics.subsystem.wind_stow_ms, 1) + ' m/s'"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">{{ __('Safety Interlock:') }}</span>
                            <span class="font-semibold" :class="metrics.subsystem.wind_ms >= metrics.subsystem.wind_stow_ms ? 'text-red-600 font-bold' : 'text-slate-600'">
                                <span x-text="metrics.subsystem.wind_ms >= metrics.subsystem.wind_stow_ms ? '{{ __('MODE AMAN AKTIF') }}' : '{{ __('Normal') }}'"></span>
                            </span>
                        </div>
                        <div class="mt-2 text-xs text-slate-400">
                            {{ __('Protects structural integrity during gusts ≥ 15.0 m/s') }}
                        </div>
                    </div>
                </div>

                <!-- DS3231 RTC Cabinet Environment -->
                <div class="p-5 rounded-xl bg-slate-50/80 border border-slate-200/70">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-sm font-bold text-slate-700">{{ __('RTC & Enclosure') }}</span>
                        <span class="text-xs font-semibold px-2 py-0.5 rounded font-mono"
                              :class="metrics.subsystem.temp_c > 50 ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-600'">
                            <span x-text="metrics.subsystem.temp_c > 50 ? '{{ __('ELEVATED') }}' : '{{ __('NOMINAL') }}'"></span>
                        </span>
                    </div>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-slate-500">{{ __('Cabinet Temperature:') }}</span>
                            <span class="font-mono font-semibold text-slate-800" x-text="formatNumberId(metrics.subsystem.temp_c, 1) + ' °C'"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">{{ __('RTC Time Source:') }}</span>
                            <span class="text-slate-700 font-medium">DS3231 Onboard</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">{{ __('Clock Drift:') }}</span>
                            <span class="font-mono font-semibold text-slate-800" x-text="metrics.subsystem.drift_s + ' s'"></span>
                        </div>
                        <div class="mt-2 text-xs text-slate-400">
                            {{ __('Temperature recorded from high-accuracy TCXO crystal') }}
                        </div>
                    </div>
                </div>

                <!-- Tracker Kinematics & BNO055 IMU -->
                <div class="p-5 rounded-xl bg-slate-50/80 border border-slate-200/70">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-sm font-bold text-slate-700">{{ __('Actuator Kinematics') }}</span>
                        <span class="text-xs font-semibold px-2 py-0.5 rounded font-mono bg-blue-50 text-blue-700 border border-blue-200/60">
                            IMU Cal <span x-text="metrics.subsystem.cal_sys"></span>/3
                        </span>
                    </div>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-slate-500">{{ __('Elevation (Pitch):') }}</span>
                            <span class="font-mono font-semibold text-slate-800">
                                <span x-text="formatNumberId(metrics.subsystem.elev, 1)"></span>&deg;
                                <span class="text-xs text-slate-400">({{ __('Target:') }} <span x-text="formatNumberId(metrics.subsystem.target_elev, 1)"></span>&deg;)</span>
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">{{ __('Azimuth (Heading):') }}</span>
                            <span class="font-mono font-semibold text-slate-800">
                                <span x-text="formatNumberId(metrics.subsystem.az, 1)"></span>&deg;
                                <span class="text-xs text-slate-400">({{ __('Target:') }} <span x-text="formatNumberId(metrics.subsystem.target_az, 1)"></span>&deg;)</span>
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">{{ __('Actuator States:') }}</span>
                            <span class="font-mono text-xs font-medium text-slate-700">
                                Elev: <span x-text="metrics.subsystem.elev_state"></span> | Az: <span x-text="metrics.subsystem.az_state"></span>
                            </span>
                        </div>
                        <div class="mt-2 text-xs text-slate-400">
                            {{ __('Dual BTS7960 linear actuator position feedback') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
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

                    // Short-polling every 5s
                    this.pollTimer = setInterval(() => {
                        this.fetchLatestData();
                    }, 5000);
                },

                destroy() {
                    if (this.pollTimer) {
                        clearInterval(this.pollTimer);
                    }
                },

                formatNumber(val, decimals = 1) {
                    if (val === null || val === undefined || isNaN(val)) return '--';
                    return window.formatNumberId ? window.formatNumberId(val, decimals) : Number(val).toFixed(decimals);
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

                            // Update live power chart
                            if (this.liveChart && data.chart_24h) {
                                this.liveChart.data.labels = data.chart_24h.labels;
                                this.liveChart.data.datasets[0].data = data.chart_24h.values;
                                this.liveChart.update('none');
                            }

                            // Update today vs yesterday chart
                            if (this.compareChart && data.chart_compare) {
                                this.compareChart.data.labels = data.chart_compare.labels;
                                this.compareChart.data.datasets[0].data = data.chart_compare.today;
                                this.compareChart.data.datasets[1].data = data.chart_compare.yesterday;
                                this.compareChart.update('none');
                            }
                        })
                        .catch(err => console.debug('Energy polling pause:', err));
                },

                initLiveChart() {
                    const ctx = document.getElementById('livePowerChartCanvas');
                    if (!ctx) return;

                    const gradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 300);
                    gradient.addColorStop(0, 'rgba(37, 99, 235, 0.28)');
                    gradient.addColorStop(1, 'rgba(37, 99, 235, 0.00)');

                    const powerLabel = window.__ ? window.__('Daya (W)') : 'Daya (W)';

                    this.liveChart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: this.metrics.chart_24h.labels,
                            datasets: [{
                                label: powerLabel,
                                data: this.metrics.chart_24h.values,
                                borderColor: '#2563eb',
                                borderWidth: 2,
                                backgroundColor: gradient,
                                fill: true,
                                tension: 0.35,
                                pointRadius: 2,
                                pointHoverRadius: 5,
                                pointBackgroundColor: '#1d4ed8',
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    backgroundColor: '#0f172a',
                                    padding: 10,
                                    titleFont: { size: 13, family: 'Inter', weight: '600' },
                                    bodyFont: { size: 13, family: 'Inter' },
                                    cornerRadius: 6,
                                    callbacks: {
                                        label: (item) => ` ${window.formatPowerW ? window.formatPowerW(item.raw) : item.raw + ' W'}`
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    grid: { color: 'rgba(241, 245, 249, 1)' },
                                    ticks: { font: { size: 12, family: 'Inter' }, color: '#64748b', maxTicksLimit: 12 }
                                },
                                y: {
                                    beginAtZero: true,
                                    grid: { color: 'rgba(241, 245, 249, 1)' },
                                    ticks: {
                                        font: { size: 12, family: 'Inter' },
                                        color: '#64748b',
                                        callback: (value) => window.formatNumberId ? window.formatNumberId(value, 0) : value
                                    },
                                    title: {
                                        display: true,
                                        text: 'Daya (W)',
                                        font: { size: 13, family: 'Inter', weight: '600' },
                                        color: '#64748b'
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
                                    borderRadius: 4,
                                    barPercentage: 0.6,
                                },
                                {
                                    label: yesterdayLabel,
                                    data: this.metrics.chart_compare.yesterday,
                                    backgroundColor: '#cbd5e1',
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
                                    backgroundColor: '#0f172a',
                                    padding: 10,
                                    titleFont: { size: 13, family: 'Inter', weight: '600' },
                                    bodyFont: { size: 13, family: 'Inter' },
                                    cornerRadius: 6,
                                    callbacks: {
                                        label: (item) => ` ${item.dataset.label}: ${window.formatNumberId ? window.formatNumberId(item.raw, 2) : item.raw} kWh`
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    grid: { display: false },
                                    ticks: { font: { size: 11, family: 'Inter' }, color: '#64748b', maxTicksLimit: 12 }
                                },
                                y: {
                                    beginAtZero: true,
                                    grid: { color: 'rgba(241, 245, 249, 1)' },
                                    ticks: {
                                        font: { size: 12, family: 'Inter' },
                                        color: '#64748b',
                                        callback: (value) => window.formatNumberId ? window.formatNumberId(value, 1) : value
                                    },
                                    title: {
                                        display: true,
                                        text: window.__ ? (window.__('Energi (kWh)') || 'Energi (kWh)') : 'Energi (kWh)',
                                        font: { size: 13, family: 'Inter', weight: '600' },
                                        color: '#64748b'
                                    }
                                }
                            }
                        }
                    });
                }
            };
        }
    </script>
    @endpush
</x-layouts.app>
