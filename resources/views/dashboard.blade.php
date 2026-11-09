<x-layouts.app title="{{ __('Dashboard') }}">
    <!-- Page Header -->
    <x-page-header 
        :title="__('Industrial Solar Tracker Dashboard')" 
        :subtitle="__('Real-time dual-axis array monitoring and telemetry analytics for Cikarang Industrial Pilot.')"
        :badge="__('Live Telemetry')"
    >
        <x-slot:actions>
            <div class="flex items-center space-x-2">
                <a href="{{ route('solar-tracker') }}" class="inline-flex items-center h-11 min-h-[44px] px-4 py-2.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-xs transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    <span>{{ __('Live Tracker Control') }}</span>
                </a>
            </div>
        </x-slot:actions>
    </x-page-header>

    <!-- Top KPI Cards Row -->
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4 mb-6">
        <!-- Total Trackers -->
        <x-kpi-card 
            :title="__('Total Trackers')" 
            :value="$kpis['total_devices']" 
            :unit="__('Units')"
            badge="PILOT"
            badgeType="info"
        />

        <!-- Online -->
        <x-kpi-card 
            :title="__('Online')" 
            :value="$kpis['online_devices']" 
            badge="ACTIVE"
            badgeType="success"
        />

        <!-- Offline -->
        <x-kpi-card 
            :title="__('Offline')" 
            :value="$kpis['offline_devices']" 
            badge="{{ $kpis['offline_devices'] > 0 ? 'ALERT' : 'NOMINAL' }}"
            badgeType="{{ $kpis['offline_devices'] > 0 ? 'danger' : 'neutral' }}"
        />

        <!-- Daily Energy (kWh) -->
        <div class="bg-white rounded-xl border border-slate-200/80 p-6 shadow-2xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs sm:text-[13px] font-semibold text-slate-500 uppercase tracking-wider">{{ __('Daily Energy') }}</span>
                <span class="text-xs font-bold text-blue-600 font-mono">{{ $kpis['energy_progress_percent'] }}%</span>
            </div>
            <div class="mt-2 flex items-baseline space-x-1.5">
                <span class="text-3xl sm:text-4xl font-bold tracking-tight text-slate-900 font-mono">{{ formatNumberId($kpis['daily_energy_kwh'], 1) }}</span>
                <span class="text-sm font-semibold text-slate-500">kWh</span>
            </div>
            <div class="mt-3 w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: {{ min(100, $kpis['energy_progress_percent']) }}%"></div>
            </div>
            <div class="mt-1.5 text-xs text-slate-400">{{ __('Target:') }} {{ formatNumberId($kpis['energy_target_kwh'], 1) }} kWh</div>
        </div>

        <!-- Peak Power Today (W) - In Watt (W) only, never kW -->
        <x-kpi-card 
            :title="__('Daya Puncak Hari Ini')" 
            :value="formatPowerW($kpis['peak_power_w'] ?? ($kpis['peak_power_kw'] * 1000), false)" 
            unit="W"
            :subtitle="__('Array maximum')"
        />

        <!-- Active Alerts -->
        <x-kpi-card 
            :title="__('Active Alerts')" 
            :value="$kpis['active_alerts']" 
            badge="{{ $kpis['active_alerts'] > 0 ? 'WARNING' : 'NORMAL' }}"
            badgeType="{{ $kpis['active_alerts'] > 0 ? 'warning' : 'success' }}"
        />

        <!-- Max Wind 24h -->
        <x-kpi-card 
            :title="__('Max Wind 24h')" 
            :value="formatNumberId($kpis['max_wind_ms'], 1)" 
            unit="m/s"
            :subtitle="__('Stow at 15.0 m/s')"
        />
    </div>

    <!-- Main Grid: Power Production Chart + Primary Array -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <!-- Power Production Chart (2 cols) -->
        <div class="lg:col-span-2">
            <div 
                x-data="powerChartComponent(@js($chartData))" 
                class="bg-white rounded-xl border border-slate-200/80 shadow-2xs p-6"
            >
                <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-slate-100 gap-3">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">{{ __('Power Production') }}</h2>
                        <p class="text-sm text-slate-500 mt-0.5">{{ __('Real-time solar DC generation from PZEM-017 sensor') }}</p>
                    </div>

                    <!-- Day / Week / Month Selector -->
                    <div class="flex items-center space-x-1 bg-slate-100 p-1 rounded-lg text-sm font-semibold self-start sm:self-auto">
                        <button 
                            type="button" 
                            @click="switchView('day')" 
                            :class="activeView === 'day' ? 'bg-white text-blue-700 shadow-2xs' : 'text-slate-500 hover:text-slate-800'"
                            class="px-3.5 py-1.5 rounded-md transition-colors cursor-pointer"
                        >
                            {{ __('Day') }}
                        </button>
                        <button 
                            type="button" 
                            @click="switchView('week')" 
                            :class="activeView === 'week' ? 'bg-white text-blue-700 shadow-2xs' : 'text-slate-500 hover:text-slate-800'"
                            class="px-3.5 py-1.5 rounded-md transition-colors cursor-pointer"
                        >
                            {{ __('Week') }}
                        </button>
                        <button 
                            type="button" 
                            @click="switchView('month')" 
                            :class="activeView === 'month' ? 'bg-white text-blue-700 shadow-2xs' : 'text-slate-500 hover:text-slate-800'"
                            class="px-3.5 py-1.5 rounded-md transition-colors cursor-pointer"
                        >
                            {{ __('Month') }}
                        </button>
                    </div>
                </div>

                <!-- Canvas (min-height 320px) -->
                <div class="mt-4 min-h-[320px] relative">
                    <canvas id="powerChartCanvas"></canvas>
                </div>
            </div>
        </div>

        <!-- Primary Array Panel (1 col) -->
        <div>
            <x-card :title="__('Primary Array')" :subtitle="__('Dual-Axis mechanical alignment status')">
                <x-slot:actions>
                    <x-status-pill :status="$primaryDevice->status ?? 'offline'" size="md" />
                </x-slot:actions>

                @if($primaryDevice)
                    <div class="space-y-4">
                        <!-- Device Header Bar -->
                        <div class="flex items-center justify-between p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                            <div>
                                <div class="text-sm font-bold text-slate-800">{{ $primaryDevice->name }}</div>
                                <div class="text-xs font-mono text-slate-400">UID: {{ $primaryDevice->uid }}</div>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-1 rounded text-xs font-bold uppercase tracking-wider bg-blue-100 text-blue-800">
                                {{ __('Mode:') }} {{ __($primaryDevice->mode) }}
                            </span>
                        </div>

                        <!-- Elevation Alignment (Pitch) -->
                        <div class="p-3.5 rounded-xl border border-slate-200/80 bg-white">
                            <div class="flex items-center justify-between text-xs sm:text-sm mb-2">
                                <span class="font-medium text-slate-600">{{ __('Elevation (Pitch Axis)') }}</span>
                                <span class="font-mono text-xs font-semibold text-slate-600">
                                    {{ $latestTelemetry?->elev_state ?? 'idle' }}
                                </span>
                            </div>
                            <div class="grid grid-cols-2 gap-2 mt-2">
                                <div class="bg-slate-50 p-2.5 rounded-lg">
                                    <div class="text-xs text-slate-500">{{ __('Current (BNO055)') }}</div>
                                    <div class="text-xl font-bold text-slate-900 font-mono mt-0.5">
                                        {{ $latestTelemetry ? formatNumberId($latestTelemetry->pitch_elev, 1) : '--' }}&deg;
                                    </div>
                                </div>
                                <div class="bg-blue-50/60 p-2.5 rounded-lg border border-blue-100">
                                    <div class="text-xs text-blue-600 font-medium">{{ __('Target (Solar SPA)') }}</div>
                                    <div class="text-xl font-bold text-blue-700 font-mono mt-0.5">
                                        {{ $latestTelemetry ? formatNumberId($latestTelemetry->target_elev, 1) : '--' }}&deg;
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Azimuth Alignment (Heading) -->
                        <div class="p-3.5 rounded-xl border border-slate-200/80 bg-white">
                            <div class="flex items-center justify-between text-xs sm:text-sm mb-2">
                                <span class="font-medium text-slate-600">{{ __('Azimuth (Heading Axis)') }}</span>
                                <span class="font-mono text-xs font-semibold text-slate-600">
                                    {{ $latestTelemetry?->az_state ?? 'idle' }}
                                </span>
                            </div>
                            <div class="grid grid-cols-2 gap-2 mt-2">
                                <div class="bg-slate-50 p-2.5 rounded-lg">
                                    <div class="text-xs text-slate-500">{{ __('Current (BNO055)') }}</div>
                                    <div class="text-xl font-bold text-slate-900 font-mono mt-0.5">
                                        {{ $latestTelemetry ? formatNumberId($latestTelemetry->azimuth, 1) : '--' }}&deg;
                                    </div>
                                </div>
                                <div class="bg-blue-50/60 p-2.5 rounded-lg border border-blue-100">
                                    <div class="text-xs text-blue-600 font-medium">{{ __('Target (Solar SPA)') }}</div>
                                    <div class="text-xl font-bold text-blue-700 font-mono mt-0.5">
                                        {{ $latestTelemetry ? formatNumberId($latestTelemetry->target_az, 1) : '--' }}&deg;
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Live Electrical Output (Power in W only) -->
                        <div class="flex items-center justify-between p-3 rounded-lg bg-emerald-50/70 border border-emerald-100 text-sm">
                            <div class="flex items-center space-x-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                <span class="font-semibold text-emerald-900">{{ __('Array DC Output:') }}</span>
                            </div>
                            <span class="font-mono font-bold text-emerald-700 text-base">
                                {{ formatPowerW($latestTelemetry?->power) }}
                                <span class="text-xs font-normal text-emerald-600">({{ $latestTelemetry ? formatNumberId($latestTelemetry->voltage, 1) : '0,0' }} V)</span>
                            </span>
                        </div>

                        <a href="{{ route('solar-tracker') }}" class="block text-center py-2.5 px-4 h-11 min-h-[44px] rounded-lg border border-blue-200 text-sm font-semibold text-blue-600 hover:bg-blue-50 transition-colors flex items-center justify-center">
                            {{ __('Open Tracker Detail') }} &rarr;
                        </a>
                    </div>
                @else
                    <p class="text-sm text-slate-500">{{ __('No tracker nodes found') }}</p>
                @endif
            </x-card>
        </div>
    </div>

    <!-- Bottom Row: Site Performance + System Alerts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Site Performance -->
        <x-card :title="__('Site Performance')" :subtitle="__('Industrial solar pilot facility operating summary')">
            <div class="overflow-x-auto rounded-lg border border-slate-200/80">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-5 py-3 text-left font-semibold text-slate-600">{{ __('Site Facility') }}</th>
                            <th class="px-5 py-3 text-left font-semibold text-slate-600">{{ __('Trackers') }}</th>
                            <th class="px-5 py-3 text-left font-semibold text-slate-600">{{ __('Availability') }}</th>
                            <th class="px-5 py-3 text-right font-semibold text-slate-600">{{ __('Today\'s Generation') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        <tr>
                            <td class="px-5 py-4 font-semibold text-slate-800">
                                {{ $site->name ?? 'Cikarang Industrial Solar Pilot' }}
                                <div class="text-xs text-slate-400 font-mono mt-0.5">{{ $site->location ?? 'Bekasi, West Java' }}</div>
                            </td>
                            <td class="px-5 py-4 text-slate-600">{{ $devices->count() }} {{ __('unit') }}</td>
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/60">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5"></span>
                                    100% {{ __('Operational') }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="font-mono font-bold text-slate-900 text-base">{{ formatNumberId($kpis['daily_energy_kwh'], 2) }} kWh</div>
                                <div class="text-xs text-slate-400 mt-0.5">{{ __('Target:') }} {{ formatNumberId($kpis['energy_target_kwh'], 1) }} kWh</div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </x-card>

        <!-- Critical Alerts (Top 3 open alerts, rendered with translated event messages) -->
        <x-card :title="__('System Alerts')" :subtitle="__('Open alerts requiring operational review')">
            <x-slot:actions>
                <a href="{{ route('alerts') }}" class="text-sm font-semibold text-blue-600 hover:text-blue-700">{{ __('View all alerts') }} &rarr;</a>
            </x-slot:actions>

            @if($criticalAlerts->isNotEmpty())
                <div class="space-y-3">
                    @foreach($criticalAlerts as $alert)
                        @php
                            $badgeStyle = match($alert->severity) {
                                'critical' => 'bg-rose-50 text-rose-700 border-rose-200',
                                'warning' => 'bg-amber-50 text-amber-700 border-amber-200',
                                default => 'bg-blue-50 text-blue-700 border-blue-200',
                            };
                        @endphp
                        <div class="flex items-start justify-between p-4 rounded-xl border border-slate-200/80 bg-white">
                            <div class="flex items-start space-x-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold uppercase border {{ $badgeStyle }} mt-0.5">
                                    {{ __($alert->severity) }}
                                </span>
                                <div>
                                    <div class="text-sm font-bold text-slate-800">{{ $alert->code }}</div>
                                    <div class="text-sm text-slate-600 mt-0.5">{{ $alert->display_message }}</div>
                                    <div class="text-xs text-slate-400 mt-1 font-mono">
                                        {{ __('Device:') }} {{ $alert->device->uid ?? __('System') }} &bull; {{ $alert->opened_at?->diffForHumans() }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="p-8 text-center rounded-xl bg-slate-50/60 border border-slate-100">
                    <div class="w-12 h-12 rounded-full bg-emerald-100 text-emerald-600 mx-auto flex items-center justify-center mb-3">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div class="text-base font-bold text-slate-800">{{ __('All Systems Nominal') }}</div>
                    <div class="text-sm text-slate-500 mt-1">{{ __('No critical alerts detected across tracker nodes or sensors.') }}</div>
                </div>
            @endif
        </x-card>
    </div>

    <!-- Chart.js Alpine Component Script -->
    <script>
        function powerChartComponent(chartData) {
            return {
                chartData: chartData,
                activeView: 'day',
                chart: null,
                init() {
                    this.$nextTick(() => {
                        this.renderChart();
                    });
                },
                switchView(view) {
                    this.activeView = view;
                    if (!this.chart) return;

                    const dataset = this.chartData[view];
                    this.chart.data.labels = dataset.labels;
                    this.chart.data.datasets[0].data = dataset.values;
                    const powerLabel = window.__ ? window.__('Daya (W)') : 'Daya (W)';
                    const energyLabel = window.__ ? window.__('Produksi per Jam') : 'Produksi per Jam';
                    this.chart.data.datasets[0].label = view === 'day' ? powerLabel : energyLabel;
                    this.chart.options.scales.y.title.text = view === 'day' ? 'Daya (W)' : dataset.unit;
                    this.chart.update();
                },
                renderChart() {
                    const ctx = document.getElementById('powerChartCanvas');
                    if (!ctx) return;

                    const dataset = this.chartData[this.activeView];
                    
                    // Chart gradient background
                    const gradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 300);
                    gradient.addColorStop(0, 'rgba(37, 99, 235, 0.25)');
                    gradient.addColorStop(1, 'rgba(37, 99, 235, 0.00)');

                    const initialLabel = this.activeView === 'day' ? (window.__ ? window.__('Daya (W)') : 'Daya (W)') : (window.__ ? window.__('Produksi per Jam') : 'Produksi per Jam');

                    this.chart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: dataset.labels,
                            datasets: [{
                                label: initialLabel,
                                data: dataset.values,
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
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    backgroundColor: '#0f172a',
                                    titleFont: { size: 13, family: 'Inter', weight: '600' },
                                    bodyFont: { size: 13, family: 'Inter' },
                                    padding: 10,
                                    cornerRadius: 6,
                                    callbacks: {
                                        label: (context) => {
                                            const val = context.parsed.y;
                                            if (this.activeView === 'day') {
                                                return window.formatPowerW ? window.formatPowerW(val) : `${val} W`;
                                            }
                                            return `${window.formatNumberId ? window.formatNumberId(val, 2) : val} kWh`;
                                        }
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
                                        callback: (value) => {
                                            return window.formatNumberId ? window.formatNumberId(value, 0) : value;
                                        }
                                    },
                                    title: {
                                        display: true,
                                        text: this.activeView === 'day' ? 'Daya (W)' : dataset.unit,
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
</x-layouts.app>
