<x-layouts.app title="{{ __('Dashboard') }}">
    <!-- Page Header -->
    <x-page-header 
        :title="__('Dashboard Pemantauan Solar Tracker')" 
        :subtitle="__('Monitoring telemetri dual-axis real-time dan analitik performa array surya.')"
    >
        <x-slot:actions>
            <a 
                href="{{ route('solar-tracker') }}" 
                class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded border border-accent/40 bg-accent/10 hover:bg-accent/20 text-accent text-xs font-mono font-medium transition-colors"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                <span>{{ __('Kontrol Pelacak') }} &rarr;</span>
            </a>
        </x-slot:actions>
    </x-page-header>

    <!-- Top KPI Cards Row (5 Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        <!-- 1. Total Perangkat -->
        <x-kpi-card 
            :title="__('Total Perangkat')" 
            :value="$kpis['total_devices']" 
            unit="Unit"
            badge="{{ $kpis['online_devices'] }} Online"
            tone="ok"
            subtitle="{{ $kpis['online_devices'] }} online · {{ $kpis['offline_devices'] }} offline"
        />

        <!-- 2. Energi Hari Ini (dengan progress bar ke target) -->
        <div class="bg-panel rounded-md border border-line p-4 flex flex-col justify-between">
            <div class="flex items-start justify-between gap-2 mb-2">
                <span class="text-xs font-medium text-muted uppercase tracking-wider truncate">{{ __('Energi Hari Ini') }}</span>
                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[11px] font-mono font-medium border border-accent/30 bg-accent/10 text-accent">
                    <span class="w-1.5 h-1.5 rounded-full bg-accent"></span>
                    <span>{{ $kpis['energy_progress_percent'] }}%</span>
                </span>
            </div>

            <div class="flex items-baseline gap-1 my-1">
                <span class="text-3xl font-bold font-mono tabular-nums text-ink tracking-tight">{{ formatNumberId($kpis['daily_energy_kwh'], 1) }}</span>
                <span class="text-xs font-mono text-muted font-normal">kWh</span>
            </div>

            <div class="mt-2 pt-2 border-t border-line/50">
                <div class="w-full bg-line/60 rounded h-1.5 overflow-hidden mb-1.5">
                    <div class="bg-accent h-1.5 rounded transition-all duration-300" style="width: {{ min(100, $kpis['energy_progress_percent']) }}%"></div>
                </div>
                <div class="flex items-center justify-between text-xs font-mono text-muted">
                    <span>{{ __('Target') }}:</span>
                    <span class="text-ink font-medium">{{ formatNumberId($kpis['energy_target_kwh'], 1) }} kWh</span>
                </div>
            </div>
        </div>

        <!-- 3. Daya Puncak Hari Ini (W) -->
        <x-kpi-card 
            :title="__('Daya Puncak Hari Ini')" 
            :value="formatPowerW($kpis['peak_power_w'] ?? ($kpis['peak_power_kw'] * 1000), false)" 
            unit="W"
            :subtitle="__('Maksimum array')"
        />

        <!-- 4. Alert Aktif (merah jika > 0) -->
        <x-kpi-card 
            :title="__('Alert Aktif')" 
            :value="$kpis['active_alerts']" 
            tone="{{ $kpis['active_alerts'] > 0 ? 'danger' : 'ok' }}"
            badge="{{ $kpis['active_alerts'] > 0 ? __('PERHATIAN') : __('NOMINAL') }}"
            subtitle="{{ $kpis['active_alerts'] > 0 ? __('Membutuhkan tindakan') : __('Semua sistem normal') }}"
        />

        <!-- 5. Angin Maks (m/s, merah jika > 15) -->
        <x-kpi-card 
            :title="__('Angin Maks')" 
            :value="formatNumberId($kpis['max_wind_ms'], 1)" 
            unit="m/s"
            tone="{{ $kpis['max_wind_ms'] >= 15.0 ? 'danger' : 'neutral' }}"
            badge="{{ $kpis['max_wind_ms'] >= 15.0 ? __('STOW') : __('NORMAL') }}"
            subtitle="{{ __('Batas stow: 15.0 m/s') }}"
        />
    </div>

    <!-- Middle Row: Power Production Chart (2/3) + Critical Alerts (1/3) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <!-- Grafik Produksi Daya (2/3 cols) -->
        <div class="lg:col-span-2">
            <div 
                x-data="powerChartComponent(@js($chartData))" 
                class="bg-panel rounded-md border border-line p-4 flex flex-col h-full"
            >
                <!-- Chart Header & Tab Selector -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-3 border-b border-line gap-2">
                    <div>
                        <h2 class="text-sm font-semibold text-ink tracking-tight">{{ __('Produksi Daya') }}</h2>
                        <p class="text-xs text-muted mt-0.5">{{ __('Hasil daya DC surya dari sensor PZEM-017') }}</p>
                    </div>

                    <!-- Day / Week / Month Tab Selector -->
                    <div class="inline-flex items-center p-0.5 bg-bg border border-line rounded self-start sm:self-auto">
                        <button 
                            type="button" 
                            @click="switchView('day')" 
                            :class="activeView === 'day' ? 'bg-accent/20 text-accent font-semibold border border-accent/40' : 'text-muted hover:text-ink border border-transparent'"
                            class="px-2.5 py-1 rounded text-xs font-mono transition-colors cursor-pointer"
                        >
                            {{ __('Hari') }} (W)
                        </button>
                        <button 
                            type="button" 
                            @click="switchView('week')" 
                            :class="activeView === 'week' ? 'bg-accent/20 text-accent font-semibold border border-accent/40' : 'text-muted hover:text-ink border border-transparent'"
                            class="px-2.5 py-1 rounded text-xs font-mono transition-colors cursor-pointer"
                        >
                            {{ __('Minggu') }} (kWh)
                        </button>
                        <button 
                            type="button" 
                            @click="switchView('month')" 
                            :class="activeView === 'month' ? 'bg-accent/20 text-accent font-semibold border border-accent/40' : 'text-muted hover:text-ink border border-transparent'"
                            class="px-2.5 py-1 rounded text-xs font-mono transition-colors cursor-pointer"
                        >
                            {{ __('Bulan') }} (kWh)
                        </button>
                    </div>
                </div>

                <!-- Canvas Area -->
                <div class="mt-4 flex-1 min-h-[300px] relative">
                    <canvas id="powerChartCanvas"></canvas>
                </div>
            </div>
        </div>

        <!-- Panel Alert Kritis (1/3 col) -->
        <div class="lg:col-span-1">
            <x-card :title="__('Alert Kritis')" :subtitle="__('Peringatan terbuka yang memerlukan tinjauan')">
                <x-slot:actions>
                    <a href="{{ route('alerts') }}" class="text-xs font-mono text-accent hover:underline">
                        {{ __('Lihat semua') }} &rarr;
                    </a>
                </x-slot:actions>

                @if($criticalAlerts->isNotEmpty())
                    <div class="space-y-2.5">
                        @foreach($criticalAlerts as $alert)
                            @php
                                $alertSeverity = strtolower((string) $alert->severity);
                                $sevConfig = match($alertSeverity) {
                                    'critical' => [
                                        'border' => 'border-danger/30',
                                        'bg' => 'bg-danger/10',
                                        'text' => 'text-danger',
                                        'dot' => 'bg-danger',
                                    ],
                                    'warning' => [
                                        'border' => 'border-warn/30',
                                        'bg' => 'bg-warn/10',
                                        'text' => 'text-warn',
                                        'dot' => 'bg-warn',
                                    ],
                                    default => [
                                        'border' => 'border-accent/30',
                                        'bg' => 'bg-accent/10',
                                        'text' => 'text-accent',
                                        'dot' => 'bg-accent',
                                    ],
                                };
                            @endphp
                            <div class="p-3 rounded border border-line bg-bg/50">
                                <div class="flex items-center justify-between gap-2 mb-1.5">
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[10px] font-mono font-medium border {{ $sevConfig['border'] }} {{ $sevConfig['bg'] }} {{ $sevConfig['text'] }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $sevConfig['dot'] }}"></span>
                                        <span>{{ strtoupper($alert->severity) }}</span>
                                    </span>
                                    <span class="text-[11px] font-mono text-muted">
                                        {{ $alert->opened_at?->diffForHumans() }}
                                    </span>
                                </div>
                                <div class="font-mono font-semibold text-xs text-ink truncate">{{ $alert->code }}</div>
                                <div class="text-xs text-muted mt-0.5 leading-snug">{{ $alert->display_message }}</div>
                                <div class="text-[11px] font-mono text-muted/70 mt-1.5 pt-1.5 border-t border-line/40">
                                    UID: {{ $alert->device->uid ?? __('System') }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-6 text-center border border-line/40 rounded bg-bg/40">
                        <div class="inline-flex items-center gap-1.5 text-xs font-mono font-medium text-ok mb-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-ok"></span>
                            <span>{{ __('Semua Sistem Normal') }}</span>
                        </div>
                        <p class="text-xs text-muted mt-1">{{ __('Tidak ada peringatan kritis aktif.') }}</p>
                    </div>
                @endif
            </x-card>
        </div>
    </div>

    <!-- Bottom Row: Device Status Table (2/3) + Live Metric Panel (1/3) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Tabel Status Perangkat (2/3 cols) -->
        <div class="lg:col-span-2">
            <x-card :title="__('Status Perangkat')" :subtitle="__('Daftar node solar tracker terdaftar')">
                <x-slot:actions>
                    <a href="{{ route('device-monitoring') }}" class="text-xs font-mono text-accent hover:underline">
                        {{ __('Buka Pemantauan') }} &rarr;
                    </a>
                </x-slot:actions>

                <div class="overflow-x-auto -mx-4 -my-4 p-4">
                    <table class="min-w-full divide-y divide-line text-xs">
                        <thead>
                            <tr class="text-left font-mono uppercase text-muted tracking-wider">
                                <th class="py-2 pr-3">{{ __('Perangkat') }}</th>
                                <th class="py-2 px-3">{{ __('Status') }}</th>
                                <th class="py-2 px-3">{{ __('Mode') }}</th>
                                <th class="py-2 px-3 text-right">{{ __('Daya DC') }}</th>
                                <th class="py-2 px-3 text-right">{{ __('Tegangan') }}</th>
                                <th class="py-2 px-3 text-right">{{ __('Sinyal') }}</th>
                                <th class="py-2 pl-3 text-right">{{ __('Terakhir Dilihat') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line text-ink">
                            @forelse($devices as $dev)
                                <tr class="hover:bg-bg/40 transition-colors">
                                    <td class="py-2.5 pr-3">
                                        <div class="font-medium text-ink">{{ $dev->name }}</div>
                                        <div class="font-mono text-[11px] text-muted">{{ $dev->uid }}</div>
                                    </td>
                                    <td class="py-2.5 px-3">
                                        <x-status-pill :status="$dev->status" size="sm" />
                                    </td>
                                    <td class="py-2.5 px-3">
                                        <span class="inline-block px-1.5 py-0.5 rounded font-mono text-[11px] uppercase border border-line bg-bg text-muted">
                                            {{ $dev->mode }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 px-3 text-right font-mono font-medium text-ink">
                                        {{ formatPowerW($dev->latestTelemetry?->power) }}
                                    </td>
                                    <td class="py-2.5 px-3 text-right font-mono text-muted">
                                        {{ $dev->latestTelemetry ? formatNumberId($dev->latestTelemetry->voltage, 1) . ' V' : '--' }}
                                    </td>
                                    <td class="py-2.5 px-3 text-right font-mono text-muted">
                                        {{ $dev->rssi ? $dev->rssi . ' dBm' : '--' }}
                                    </td>
                                    <td class="py-2.5 pl-3 text-right font-mono text-muted">
                                        {{ $dev->last_seen_at ? $dev->last_seen_at->setTimezone('Asia/Jakarta')->format('H.i.s') . ' WIB' : '--' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-6 text-center text-muted">
                                        {{ __('Tidak ada perangkat terdaftar.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>

        <!-- Panel Metrik Live dari $latestTelemetry (1/3 col) -->
        <div class="lg:col-span-1">
            <x-card 
                :title="__('Metrik Live Array')" 
                :subtitle="$primaryDevice ? $primaryDevice->name . ' (' . $primaryDevice->uid . ')' : __('Tidak ada perangkat')"
            >
                <x-slot:actions>
                    <x-status-pill :status="$primaryDevice->status ?? 'offline'" size="sm" />
                </x-slot:actions>

                @if($primaryDevice)
                    <div class="space-y-3">
                        <!-- Mode & Status Header -->
                        <div class="flex items-center justify-between p-2.5 rounded border border-line bg-bg/50 text-xs font-mono">
                            <span class="text-muted">{{ __('Mode Operasi:') }}</span>
                            <span class="font-bold text-accent uppercase">{{ $primaryDevice->mode }}</span>
                        </div>

                        <!-- Parameter List (voltage, current, power) -->
                        <div class="grid grid-cols-3 gap-2">
                            <div class="p-2.5 rounded border border-line bg-bg/50">
                                <div class="text-[10px] font-mono text-muted uppercase">{{ __('Tegangan') }}</div>
                                <div class="text-base font-bold font-mono text-ink mt-0.5">
                                    {{ $latestTelemetry ? formatNumberId($latestTelemetry->voltage, 1) : '--' }}
                                </div>
                                <div class="text-[10px] font-mono text-muted">V</div>
                            </div>
                            <div class="p-2.5 rounded border border-line bg-bg/50">
                                <div class="text-[10px] font-mono text-muted uppercase">{{ __('Arus') }}</div>
                                <div class="text-base font-bold font-mono text-ink mt-0.5">
                                    {{ $latestTelemetry ? formatNumberId($latestTelemetry->current, 2) : '--' }}
                                </div>
                                <div class="text-[10px] font-mono text-muted">A</div>
                            </div>
                            <div class="p-2.5 rounded border border-line bg-bg/50">
                                <div class="text-[10px] font-mono text-muted uppercase">{{ __('Daya') }}</div>
                                <div class="text-base font-bold font-mono text-ink mt-0.5 truncate">
                                    {{ formatPowerW($latestTelemetry?->power, false) }}
                                </div>
                                <div class="text-[10px] font-mono text-muted">W</div>
                            </div>
                        </div>

                        <!-- Elevasi (Pitch) Alignment -->
                        <div class="p-2.5 rounded border border-line bg-bg/50">
                            <div class="flex items-center justify-between text-xs mb-1.5">
                                <span class="font-medium text-ink">{{ __('Elevasi (Pitch)') }}</span>
                                <span class="font-mono text-[11px] text-muted uppercase">
                                    {{ $latestTelemetry?->elev_state ?? 'idle' }}
                                </span>
                            </div>
                            <div class="grid grid-cols-2 gap-2 text-xs font-mono">
                                <div>
                                    <div class="text-[10px] text-muted">{{ __('Saat Ini (BNO)') }}</div>
                                    <div class="text-sm font-bold text-ink mt-0.5">
                                        {{ $latestTelemetry ? formatNumberId($latestTelemetry->pitch_elev, 1) : '--' }}°
                                    </div>
                                </div>
                                <div>
                                    <div class="text-[10px] text-muted">{{ __('Target (SPA)') }}</div>
                                    <div class="text-sm font-bold text-accent mt-0.5">
                                        {{ $latestTelemetry ? formatNumberId($latestTelemetry->target_elev, 1) : '--' }}°
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Azimut (Heading) Alignment -->
                        <div class="p-2.5 rounded border border-line bg-bg/50">
                            <div class="flex items-center justify-between text-xs mb-1.5">
                                <span class="font-medium text-ink">{{ __('Azimut (Heading)') }}</span>
                                <span class="font-mono text-[11px] text-muted uppercase">
                                    {{ $latestTelemetry?->az_state ?? 'idle' }}
                                </span>
                            </div>
                            <div class="grid grid-cols-2 gap-2 text-xs font-mono">
                                <div>
                                    <div class="text-[10px] text-muted">{{ __('Saat Ini (BNO)') }}</div>
                                    <div class="text-sm font-bold text-ink mt-0.5">
                                        {{ $latestTelemetry ? formatNumberId($latestTelemetry->azimuth, 1) : '--' }}°
                                    </div>
                                </div>
                                <div>
                                    <div class="text-[10px] text-muted">{{ __('Target (SPA)') }}</div>
                                    <div class="text-sm font-bold text-accent mt-0.5">
                                        {{ $latestTelemetry ? formatNumberId($latestTelemetry->target_az, 1) : '--' }}°
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Link to Solar Tracker -->
                        <a 
                            href="{{ route('solar-tracker') }}" 
                            class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded border border-accent/40 bg-accent/10 hover:bg-accent/20 text-accent font-medium text-xs font-mono transition-colors"
                        >
                            <span>{{ __('Buka Kontrol Tracker') }}</span>
                            <span>&rarr;</span>
                        </a>
                    </div>
                @else
                    <p class="text-xs text-muted">{{ __('Tidak ada perangkat terdeteksi.') }}</p>
                @endif
            </x-card>
        </div>
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
                    if (this.chart.options.scales && this.chart.options.scales.y && this.chart.options.scales.y.title) {
                        this.chart.options.scales.y.title.text = view === 'day' ? 'Daya (W)' : dataset.unit;
                    }
                    this.chart.update();
                },
                renderChart() {
                    const ctx = document.getElementById('powerChartCanvas');
                    if (!ctx) return;

                    const dataset = this.chartData[this.activeView];
                    const initialLabel = this.activeView === 'day' 
                        ? (window.__ ? window.__('Daya (W)') : 'Daya (W)') 
                        : (window.__ ? window.__('Produksi per Jam') : 'Produksi per Jam');

                    this.chart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: dataset.labels,
                            datasets: [{
                                label: initialLabel,
                                data: dataset.values,
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
                                legend: {
                                    display: false
                                },
                                tooltip: {
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
                                    grid: {
                                        color: 'rgba(55, 65, 81, 0.45)',
                                    },
                                    ticks: {
                                        maxTicksLimit: 12
                                    }
                                },
                                y: {
                                    beginAtZero: true,
                                    grid: {
                                        color: 'rgba(55, 65, 81, 0.45)',
                                    },
                                    ticks: {
                                        callback: (value) => {
                                            return window.formatNumberId ? window.formatNumberId(value, 0) : value;
                                        }
                                    },
                                    title: {
                                        display: true,
                                        text: this.activeView === 'day' ? 'Daya (W)' : dataset.unit,
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
