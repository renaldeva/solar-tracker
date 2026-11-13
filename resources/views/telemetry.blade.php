<x-layouts.app :title="__('Telemetri')">
    <x-page-header 
        :title="__('Telemetry Data Explorer')" 
        :subtitle="__('Filter metrik deret waktu di seluruh perangkat, periksa data mentah sensor, dan ekspor data telemetri.')"
        :badge="__('Aliran Frekuensi Tinggi')"
    >
        <x-slot:actions>
            <div class="flex items-center space-x-2">
                <a 
                    href="{{ route('telemetry.export', request()->all()) }}" 
                    class="inline-flex items-center h-10 px-4 rounded-xl border border-line bg-panel hover:bg-slate-50 text-ink text-sm font-medium transition-colors shadow-xs"
                    title="{{ __('Ekspor CSV') }}"
                >
                    <svg class="w-4 h-4 mr-2 text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>{{ __('Ekspor CSV') }}</span>
                </a>
                <a 
                    href="{{ route('solar-tracker', ['uid' => $device->uid ?? '']) }}" 
                    class="inline-flex items-center h-10 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold transition-colors shadow-xs"
                >
                    <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    <span>{{ __('Kontrol Langsung') }}</span>
                </a>
            </div>
        </x-slot:actions>
    </x-page-header>

    <!-- Status Banner: "Apakah semuanya baik-baik saja?" -->
    <div class="mb-6 p-4 rounded-2xl bg-panel border border-line shadow-xs flex items-center justify-between">
        <div class="flex items-center space-x-3.5">
            <div class="w-3.5 h-3.5 rounded-full {{ ($device && $device->status === 'online') ? 'bg-emerald-500 ring-4 ring-emerald-50 dark:ring-emerald-950/50' : 'bg-amber-500 ring-4 ring-amber-50 dark:ring-amber-950/50' }}"></div>
            <div>
                <h2 class="text-sm font-semibold text-ink">
                    @if($device && $device->status === 'online')
                        Aliran Sinyal Telemetri Aktif & Sinkron
                    @else
                        Sinyal Telemetri Terbatas / Perangkat Nonaktif
                    @endif
                </h2>
                <p class="text-xs text-muted mt-0.5">
                    Memantau unit <strong class="text-ink">{{ $device->name ?? 'Semua Unit' }}</strong> ({{ $device->uid ?? '--' }}). Siklus kirim sensor setiap 5 detik.
                </p>
            </div>
        </div>
        <div class="hidden sm:flex items-center space-x-2">
            <span class="inline-flex items-center px-3 py-1 rounded-xl text-xs font-mono font-semibold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/60">
                <span class="w-2 h-2 rounded-full bg-emerald-500 mr-2 animate-pulse"></span>
                STREAM 5s
            </span>
        </div>
    </div>

    <!-- Filter & Control Card (Smart Nursery Style) -->
    <div class="bg-panel border border-line rounded-2xl p-5 mb-6 shadow-xs" x-data="{ rangeMode: '{{ $selectedRange }}' }">
        <form method="GET" action="{{ route('telemetry') }}" id="telemetryFilterForm" class="space-y-4">
            <!-- Row 1: Device Selector & Range Pills -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <!-- Device Picker -->
                <div class="flex items-center space-x-2">
                    <label for="filter-device" class="text-xs font-semibold text-muted uppercase tracking-wider">{{ __('Perangkat:') }}</label>
                    <div class="relative">
                        <select 
                            id="filter-device" 
                            name="device" 
                            onchange="this.form.submit()" 
                            class="appearance-none text-xs font-semibold bg-panel border border-line rounded-xl pl-3 pr-8 py-2 text-ink focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 shadow-2xs"
                        >
                            @foreach($devices as $dev)
                                <option value="{{ $dev->uid }}" {{ $device && $device->uid === $dev->uid ? 'selected' : '' }}>
                                    {{ $dev->name }} ({{ $dev->uid }})
                                </option>
                            @endforeach
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-muted">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Range Buttons (Pill Buttons) -->
                <div class="flex flex-wrap items-center gap-1.5 p-1 bg-panel-alt border border-line rounded-xl">
                    @foreach(['1h' => '1 Jam', '6h' => '6 Jam', '24h' => '24 Jam', '7d' => '7 Hari'] as $val => $label)
                        <button 
                            type="submit" 
                            name="range" 
                            value="{{ $val }}" 
                            @click="rangeMode = '{{ $val }}'"
                            class="px-3 py-1.5 text-xs font-semibold rounded-lg transition-all {{ $selectedRange === $val ? 'bg-blue-600 text-white shadow-xs' : 'text-muted hover:text-ink hover:bg-panel' }}"
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                    <button 
                        type="button" 
                        @click="rangeMode = 'custom'" 
                        :class="rangeMode === 'custom' ? 'bg-blue-600 text-white shadow-xs' : 'text-muted hover:text-ink hover:bg-panel'"
                        class="px-3 py-1.5 text-xs font-semibold rounded-lg transition-all cursor-pointer"
                    >
                        Kustom
                    </button>
                </div>
            </div>

            <!-- Custom Date Inputs (shown when range is custom) -->
            <div x-show="rangeMode === 'custom'" x-cloak class="pt-3 border-t border-line flex flex-wrap items-center gap-3">
                <input type="hidden" name="range" value="custom">
                <div class="flex items-center space-x-2 text-xs">
                    <span class="text-muted font-medium">Dari:</span>
                    <input 
                        type="datetime-local" 
                        name="start_date" 
                        value="{{ $startDate }}" 
                        class="bg-panel border border-line rounded-xl px-3 py-1.5 text-xs text-ink focus:outline-none focus:border-blue-500 font-mono shadow-2xs"
                    >
                </div>
                <div class="flex items-center space-x-2 text-xs">
                    <span class="text-muted font-medium">Sampai:</span>
                    <input 
                        type="datetime-local" 
                        name="end_date" 
                        value="{{ $endDate }}" 
                        class="bg-panel border border-line rounded-xl px-3 py-1.5 text-xs text-ink focus:outline-none focus:border-blue-500 font-mono shadow-2xs"
                    >
                </div>
                <button 
                    type="submit" 
                    class="px-4 py-1.5 rounded-xl bg-blue-600 text-white text-xs font-semibold hover:bg-blue-700 transition-colors shadow-xs cursor-pointer"
                >
                    Terapkan Rentang
                </button>
            </div>

            <!-- Metric Multi-Select Badges Row (Chips) -->
            <div class="pt-3 border-t border-line flex flex-wrap items-center gap-2">
                <span class="text-xs font-semibold text-muted uppercase tracking-wider mr-2">{{ __('Metrik Terlihat:') }}</span>
                @php
                    $availableMetrics = [
                        'power' => [
                            'label' => __('Daya (W)'), 
                            'active_class' => 'text-blue-700 bg-blue-50 border-blue-200 dark:text-blue-400 dark:bg-blue-950/40 dark:border-blue-800/60 ring-1 ring-blue-200 dark:ring-blue-800/60 font-semibold',
                            'dot' => 'bg-blue-600 dark:bg-blue-500'
                        ],
                        'voltage' => [
                            'label' => __('Tegangan (V)'), 
                            'active_class' => 'text-amber-700 bg-amber-50 border-amber-200 dark:text-amber-400 dark:bg-amber-950/40 dark:border-amber-800/60 ring-1 ring-amber-200 dark:ring-amber-800/60 font-semibold',
                            'dot' => 'bg-amber-500'
                        ],
                        'current' => [
                            'label' => __('Arus (A)'), 
                            'active_class' => 'text-emerald-700 bg-emerald-50 border-emerald-200 dark:text-emerald-400 dark:bg-emerald-950/40 dark:border-emerald-800/60 ring-1 ring-emerald-200 dark:ring-emerald-800/60 font-semibold',
                            'dot' => 'bg-emerald-500'
                        ],
                        'wind_speed' => [
                            'label' => __('Kecepatan Angin (m/s)'), 
                            'active_class' => 'text-rose-700 bg-rose-50 border-rose-200 dark:text-rose-400 dark:bg-rose-950/40 dark:border-rose-800/60 ring-1 ring-rose-200 dark:ring-rose-800/60 font-semibold',
                            'dot' => 'bg-rose-500'
                        ],
                        'pitch_elev' => [
                            'label' => __('Elevasi (°)'), 
                            'active_class' => 'text-purple-700 bg-purple-50 border-purple-200 dark:text-purple-400 dark:bg-purple-950/40 dark:border-purple-800/60 ring-1 ring-purple-200 dark:ring-purple-800/60 font-semibold',
                            'dot' => 'bg-purple-600 dark:bg-purple-500'
                        ],
                        'azimuth' => [
                            'label' => __('Azimut (°)'), 
                            'active_class' => 'text-cyan-700 bg-cyan-50 border-cyan-200 dark:text-cyan-400 dark:bg-cyan-950/40 dark:border-cyan-800/60 ring-1 ring-cyan-200 dark:ring-cyan-800/60 font-semibold',
                            'dot' => 'bg-cyan-600 dark:bg-cyan-500'
                        ],
                        'temp_c' => [
                            'label' => __('Suhu Panel (°C)'), 
                            'active_class' => 'text-pink-700 bg-pink-50 border-pink-200 dark:text-pink-400 dark:bg-pink-950/40 dark:border-pink-800/60 ring-1 ring-pink-200 dark:ring-pink-800/60 font-semibold',
                            'dot' => 'bg-pink-600 dark:bg-pink-500'
                        ],
                    ];
                @endphp

                @foreach($availableMetrics as $key => $meta)
                    @php $checked = in_array($key, $selectedMetrics, true); @endphp
                    <label class="cursor-pointer inline-flex items-center space-x-2 px-3 py-1.5 rounded-xl border text-xs font-medium transition-all shadow-2xs {{ $checked ? $meta['active_class'] : 'bg-panel text-muted border-line opacity-60 hover:opacity-100 hover:border-slate-300 dark:hover:border-slate-700' }}">
                        <input 
                            type="checkbox" 
                            name="metrics[]" 
                            value="{{ $key }}" 
                            onchange="document.getElementById('telemetryFilterForm').submit()" 
                            {{ $checked ? 'checked' : '' }} 
                            class="sr-only"
                        >
                        <span class="w-2 h-2 rounded-full {{ $checked ? $meta['dot'] : 'bg-slate-300' }}"></span>
                        <span>{{ $meta['label'] }}</span>
                    </label>
                @endforeach
            </div>
        </form>
    </div>

    <!-- Chart Card (Smart Nursery Style) -->
    <div 
        x-data="telemetryChartComponent(@js($chartData), '{{ $device->uid ?? '' }}', '{{ $selectedRange }}', @js($selectedMetrics), '{{ $startDate ?? '' }}', '{{ $endDate ?? '' }}')" 
        class="bg-white border border-line rounded-2xl p-5 mb-6 shadow-xs"
    >
        <div class="flex items-center justify-between pb-3.5 border-b border-line mb-4">
            <div class="flex items-center space-x-3">
                <h2 class="text-sm font-semibold text-ink">{{ __('Bentuk Gelombang Deret Waktu') }}</h2>
                <span class="text-xs text-muted font-mono">
                    <span class="text-ink font-semibold" x-text="chartData.total_count"></span> {{ __('rekaman') }}
                </span>
            </div>
            <div class="flex items-center space-x-2">
                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-mono font-semibold border border-emerald-200 text-emerald-700 bg-emerald-50">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5 animate-pulse"></span>
                    {{ __('STREAM AKTIF (5s)') }}
                </span>
            </div>
        </div>
        <div class="h-80 min-h-[320px] relative">
            <canvas id="telemetryChartCanvas"></canvas>
        </div>
    </div>

    <!-- High-Density Telemetry Table Card -->
    <div class="bg-panel border border-line rounded-2xl overflow-hidden mb-6 shadow-xs">
        <div class="p-4 sm:p-5 border-b border-line flex flex-col sm:flex-row sm:items-center justify-between gap-2 bg-panel-alt/60">
            <div>
                <h3 class="text-sm font-semibold text-ink">{{ __('Paket Sensor Terekam') }}</h3>
                <p class="text-xs text-muted mt-0.5">{{ __('Waktu ditampilkan dalam UTC+7 (WIB)') }}</p>
            </div>
            <div class="text-xs font-mono text-muted">
                {{ __('Halaman :current dari :total (:count total data)', [
                    'current' => $telemetries->currentPage(),
                    'total' => $telemetries->lastPage(),
                    'count' => $telemetries->total()
                ]) }}
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-panel-alt text-muted uppercase text-[11px] font-semibold tracking-wider border-b border-line">
                    <tr>
                        <th class="py-3 px-3.5">{{ __('Waktu (WIB)') }}</th>
                        <th class="py-3 px-3.5">{{ __('Mode') }}</th>
                        <th class="py-3 px-3.5">{{ __('Tegangan') }}</th>
                        <th class="py-3 px-3.5">{{ __('Arus') }}</th>
                        <th class="py-3 px-3.5">{{ __('Daya') }}</th>
                        <th class="py-3 px-3.5">{{ __('Kumulatif Wh') }}</th>
                        <th class="py-3 px-3.5">{{ __('Kecepatan Angin') }}</th>
                        <th class="py-3 px-3.5">{{ __('Elevasi') }}</th>
                        <th class="py-3 px-3.5">{{ __('Azimut') }}</th>
                        <th class="py-3 px-3.5">{{ __('Suhu') }}</th>
                        <th class="py-3 px-3.5">{{ __('Sinyal GPS') }}</th>
                        <th class="py-3 px-3.5">{{ __('Kalibrasi IMU') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line font-mono text-xs">
                    @forelse($telemetries as $tel)
                        <tr class="hover:bg-panel-alt/50 transition-colors">
                            <td class="py-3 px-3.5 font-mono text-ink whitespace-nowrap">
                                {{ $tel->recorded_at ? $tel->recorded_at->setTimezone('Asia/Jakarta')->format('d M H:i:s') . ' WIB' : '--' }}
                            </td>
                            <td class="py-3 px-3.5">
                                <span class="px-2 py-0.5 rounded-md text-[11px] font-mono uppercase border border-line
                                    {{ $tel->mode === 'auto' ? 'bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 border-blue-200 dark:border-blue-800/60' : ($tel->mode === 'stow' ? 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border-rose-200 dark:border-rose-800/60' : 'bg-slate-50 dark:bg-slate-800 text-muted') }}">
                                    {{ match(strtolower($tel->mode)) { 'auto' => __('Otomatis'), 'manual' => __('Manual'), 'stow' => __('Stow'), default => strtoupper($tel->mode) } }}
                                </span>
                            </td>
                            <td class="py-3 px-3.5 text-ink">{{ formatNumberId($tel->voltage, 1) }} V</td>
                            <td class="py-3 px-3.5 text-ink">{{ formatNumberId($tel->current, 2) }} A</td>
                            <td class="py-3 px-3.5 font-bold text-blue-600 dark:text-blue-400">{{ formatPowerW($tel->power) }}</td>
                            <td class="py-3 px-3.5 text-muted">{{ formatNumberId($tel->energy_wh, 0) }} Wh</td>
                            <td class="py-3 px-3.5 {{ $tel->wind_speed >= 15.0 ? 'text-rose-600 dark:text-rose-400 font-bold' : 'text-ink' }}">
                                {{ formatNumberId($tel->wind_speed, 1) }} m/s
                            </td>
                            <td class="py-3 px-3.5 text-ink">
                                {{ formatNumberId($tel->pitch_elev, 1) }}&deg;
                                <span class="text-muted text-[11px]">({{ formatNumberId($tel->target_elev, 1) }}&deg;)</span>
                            </td>
                            <td class="py-3 px-3.5 text-ink">
                                {{ formatNumberId($tel->azimuth, 1) }}&deg;
                                <span class="text-muted text-[11px]">({{ formatNumberId($tel->target_az, 1) }}&deg;)</span>
                            </td>
                            <td class="py-3 px-3.5 text-ink">{{ formatNumberId($tel->temp_c, 1) }}&deg;C</td>
                            <td class="py-3 px-3.5">
                                <span class="text-[11px] {{ $tel->fix ? 'text-emerald-600 dark:text-emerald-400 font-semibold' : 'text-amber-600 dark:text-amber-400' }}">
                                    {{ $tel->fix ? '3D (' . $tel->satellites . 's)' : 'No Fix' }}
                                </span>
                            </td>
                            <td class="py-3 px-3.5">
                                <span class="px-1.5 py-0.5 rounded text-[11px] font-mono border {{ $tel->cal_sys >= 2 ? 'border-emerald-200 dark:border-emerald-800/60 text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/40' : 'border-amber-200 dark:border-amber-800/60 text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/40' }}">
                                    Sys:{{ $tel->cal_sys ?? 0 }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="p-8 text-center text-muted font-sans text-sm">
                                {{ __('Tidak ada data telemetri ditemukan untuk perangkat dan rentang waktu yang dipilih.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($telemetries->hasPages())
            <div class="p-4 border-t border-line bg-panel-alt/60">
                {{ $telemetries->links() }}
            </div>
        @endif
    </div>

    @push('scripts')
    <script>
        function telemetryChartComponent(initialChartData, deviceUid, range, selectedMetrics, startDate, endDate) {
            return {
                chartData: initialChartData,
                deviceUid: deviceUid,
                range: range,
                selectedMetrics: selectedMetrics,
                startDate: startDate,
                endDate: endDate,
                chart: null,
                pollTimer: null,

                init() {
                    this.$nextTick(() => {
                        this.renderChart();
                    });

                    // 5-second polling for active stream
                    this.pollTimer = setInterval(() => {
                        this.refreshChartData();
                    }, 5000);
                },

                destroy() {
                    if (this.pollTimer) clearInterval(this.pollTimer);
                },

                configureMultiAxis(datasets) {
                    const hasPower = datasets.some(ds => {
                        const l = (ds.label || '').toLowerCase();
                        return l.includes('power') || l.includes('daya') || l.includes('(w)');
                    });

                    datasets.forEach((ds, idx) => {
                        const l = (ds.label || '').toLowerCase();
                        if (hasPower) {
                            ds.yAxisID = (l.includes('power') || l.includes('daya') || l.includes('(w)')) ? 'y' : 'y1';
                        } else {
                            ds.yAxisID = idx === 0 ? 'y' : 'y1';
                        }
                    });
                },

                refreshChartData() {
                    const metricsParam = this.selectedMetrics.join(',');
                    let url = `/telemetry/data?device=${encodeURIComponent(this.deviceUid)}&range=${encodeURIComponent(this.range)}&metrics=${encodeURIComponent(metricsParam)}`;
                    if (this.range === 'custom') {
                        if (this.startDate) url += `&start_date=${encodeURIComponent(this.startDate)}`;
                        if (this.endDate) url += `&end_date=${encodeURIComponent(this.endDate)}`;
                    }

                    fetch(url)
                        .then(res => res.json())
                        .then(data => {
                            if (!data || !data.labels) return;
                            this.chartData = data;
                            if (this.chart) {
                                this.configureMultiAxis(data.datasets);
                                this.chart.data.labels = data.labels;
                                this.chart.data.datasets = data.datasets;
                                this.chart.update('none');
                            }
                        })
                        .catch(err => console.debug('Telemetry polling paused:', err));
                },

                renderChart() {
                    const ctx = document.getElementById('telemetryChartCanvas');
                    if (!ctx) return;

                    const theme = window.getChartThemeColors ? window.getChartThemeColors() : {
                        text: '#64748b',
                        grid: '#f1f5f9',
                        tooltipBg: '#0f172a',
                        tooltipBorder: '#334155'
                    };

                    const numberFmt = new Intl.NumberFormat('id-ID');
                    const datasets = this.chartData.datasets || [];
                    this.configureMultiAxis(datasets);

                    const hasSecondary = datasets.some(ds => ds.yAxisID === 'y1');

                    this.chart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: this.chartData.labels || [],
                            datasets: datasets
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: {
                                mode: 'index',
                                intersect: false
                            },
                            plugins: {
                                legend: {
                                    display: true,
                                    position: 'top',
                                    labels: {
                                        boxWidth: 12,
                                        boxHeight: 12,
                                        font: { size: 12, family: 'Inter' },
                                        color: theme.text,
                                        padding: 14
                                    }
                                },
                                tooltip: {
                                    backgroundColor: theme.tooltipBg,
                                    titleColor: '#ffffff',
                                    bodyColor: '#ffffff',
                                    borderColor: theme.tooltipBorder,
                                    borderWidth: 1,
                                    padding: 10,
                                    titleFont: { size: 12, family: 'Inter' },
                                    bodyFont: { size: 12, family: 'JetBrains Mono' },
                                    cornerRadius: 8,
                                    callbacks: {
                                        label: function(context) {
                                            const label = context.dataset.label || '';
                                            const val = context.parsed.y;
                                            return `${label}: ${numberFmt.format(val)}`;
                                        }
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    grid: { color: theme.grid, drawTicks: false },
                                    ticks: {
                                        font: { size: 11, family: 'JetBrains Mono' },
                                        color: theme.text,
                                        maxTicksLimit: 12
                                    }
                                },
                                y: {
                                    type: 'linear',
                                    display: true,
                                    position: 'left',
                                    grid: { color: theme.grid, drawTicks: false },
                                    ticks: {
                                        font: { size: 11, family: 'JetBrains Mono' },
                                        color: theme.text,
                                        callback: function(value) {
                                            return numberFmt.format(value);
                                        }
                                    }
                                },
                                y1: {
                                    type: 'linear',
                                    display: hasSecondary,
                                    position: 'right',
                                    grid: { drawOnChartArea: false },
                                    ticks: {
                                        font: { size: 11, family: 'JetBrains Mono' },
                                        color: theme.text,
                                        callback: function(value) {
                                            return numberFmt.format(value);
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
    @endpush
</x-layouts.app>
