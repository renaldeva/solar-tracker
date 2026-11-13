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
                    class="inline-flex items-center h-9 px-3 rounded-md border border-line bg-panel hover:bg-line/40 text-ink text-sm font-medium transition-colors"
                    title="{{ __('Ekspor CSV') }}"
                >
                    <svg class="w-4 h-4 mr-2 text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>{{ __('Ekspor CSV') }}</span>
                </a>
                <a 
                    href="{{ route('solar-tracker', ['uid' => $device->uid ?? '']) }}" 
                    class="inline-flex items-center h-9 px-3 rounded-md bg-accent text-ink text-sm font-medium hover:bg-blue-600 transition-colors"
                >
                    <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    <span>{{ __('Kontrol Langsung') }}</span>
                </a>
            </div>
        </x-slot:actions>
    </x-page-header>

    <!-- Filter & Control Toolbar -->
    <div class="bg-panel border border-line rounded-md p-4 mb-6" x-data="{ rangeMode: '{{ $selectedRange }}' }">
        <form method="GET" action="{{ route('telemetry') }}" id="telemetryFilterForm" class="space-y-4">
            <!-- Row 1: Device Selector & Range -->
            <div class="flex flex-wrap items-center justify-between gap-4">
                <!-- Device Picker -->
                <div class="flex items-center space-x-2">
                    <label for="filter-device" class="text-xs font-semibold text-muted uppercase tracking-wider">{{ __('Perangkat:') }}</label>
                    <select 
                        id="filter-device" 
                        name="device" 
                        onchange="this.form.submit()" 
                        class="text-xs font-medium bg-bg border border-line rounded-md px-3 py-1.5 text-ink focus:outline-none focus:border-accent"
                    >
                        @foreach($devices as $dev)
                            <option value="{{ $dev->uid }}" {{ $device && $device->uid === $dev->uid ? 'selected' : '' }}>
                                {{ $dev->name }} ({{ $dev->uid }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Range Buttons -->
                <div class="flex flex-wrap items-center gap-1">
                    @foreach(['1h' => '1 Jam', '6h' => '6 Jam', '24h' => '24 Jam', '7d' => '7 Hari'] as $val => $label)
                        <button 
                            type="submit" 
                            name="range" 
                            value="{{ $val }}" 
                            @click="rangeMode = '{{ $val }}'"
                            class="px-2.5 py-1 text-xs font-mono rounded transition-colors {{ $selectedRange === $val ? 'bg-accent text-ink' : 'bg-bg text-muted hover:text-ink border border-line' }}"
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                    <button 
                        type="button" 
                        @click="rangeMode = 'custom'" 
                        :class="rangeMode === 'custom' ? 'bg-accent text-ink' : 'bg-bg text-muted hover:text-ink border border-line'"
                        class="px-2.5 py-1 text-xs font-mono rounded transition-colors cursor-pointer"
                    >
                        Kustom
                    </button>
                </div>
            </div>

            <!-- Custom Date Inputs (shown when range is custom) -->
            <div x-show="rangeMode === 'custom'" x-cloak class="pt-3 border-t border-line flex flex-wrap items-center gap-3">
                <input type="hidden" name="range" value="custom">
                <div class="flex items-center space-x-2 text-xs">
                    <span class="text-muted">Dari:</span>
                    <input 
                        type="datetime-local" 
                        name="start_date" 
                        value="{{ $startDate }}" 
                        class="bg-bg border border-line rounded px-2.5 py-1 text-xs text-ink focus:outline-none focus:border-accent font-mono"
                    >
                </div>
                <div class="flex items-center space-x-2 text-xs">
                    <span class="text-muted">Sampai:</span>
                    <input 
                        type="datetime-local" 
                        name="end_date" 
                        value="{{ $endDate }}" 
                        class="bg-bg border border-line rounded px-2.5 py-1 text-xs text-ink focus:outline-none focus:border-accent font-mono"
                    >
                </div>
                <button 
                    type="submit" 
                    class="px-3 py-1 rounded bg-accent text-ink text-xs font-medium hover:bg-blue-600 transition-colors cursor-pointer"
                >
                    Terapkan Rentang
                </button>
            </div>

            <!-- Metric Multi-Select Badges Row -->
            <div class="pt-3 border-t border-line flex flex-wrap items-center gap-2">
                <span class="text-xs font-semibold text-muted uppercase tracking-wider mr-2">{{ __('Metrik Terlihat:') }}</span>
                @php
                    $availableMetrics = [
                        'power' => [
                            'label' => __('Daya (W)'), 
                            'active_class' => 'text-blue-400 bg-blue-500/10 border-blue-500/40',
                            'dot' => 'bg-blue-500'
                        ],
                        'voltage' => [
                            'label' => __('Tegangan (V)'), 
                            'active_class' => 'text-amber-400 bg-amber-500/10 border-amber-500/40',
                            'dot' => 'bg-amber-500'
                        ],
                        'current' => [
                            'label' => __('Arus (A)'), 
                            'active_class' => 'text-emerald-400 bg-emerald-500/10 border-emerald-500/40',
                            'dot' => 'bg-emerald-500'
                        ],
                        'wind_speed' => [
                            'label' => __('Kecepatan Angin (m/s)'), 
                            'active_class' => 'text-rose-400 bg-rose-500/10 border-rose-500/40',
                            'dot' => 'bg-rose-500'
                        ],
                        'pitch_elev' => [
                            'label' => __('Elevasi (°)'), 
                            'active_class' => 'text-purple-400 bg-purple-500/10 border-purple-500/40',
                            'dot' => 'bg-purple-500'
                        ],
                        'azimuth' => [
                            'label' => __('Azimut (°)'), 
                            'active_class' => 'text-cyan-400 bg-cyan-500/10 border-cyan-500/40',
                            'dot' => 'bg-cyan-500'
                        ],
                        'temp_c' => [
                            'label' => __('Suhu Panel (°C)'), 
                            'active_class' => 'text-pink-400 bg-pink-500/10 border-pink-500/40',
                            'dot' => 'bg-pink-500'
                        ],
                    ];
                @endphp

                @foreach($availableMetrics as $key => $meta)
                    @php $checked = in_array($key, $selectedMetrics, true); @endphp
                    <label class="cursor-pointer inline-flex items-center space-x-2 px-2.5 py-1 rounded border text-xs font-medium transition-all {{ $checked ? $meta['active_class'] : 'bg-bg/50 text-muted border-line opacity-60 hover:opacity-100' }}">
                        <input 
                            type="checkbox" 
                            name="metrics[]" 
                            value="{{ $key }}" 
                            onchange="document.getElementById('telemetryFilterForm').submit()" 
                            {{ $checked ? 'checked' : '' }} 
                            class="sr-only"
                        >
                        <span class="w-2 h-2 rounded-full {{ $checked ? $meta['dot'] : 'bg-line' }}"></span>
                        <span>{{ $meta['label'] }}</span>
                    </label>
                @endforeach
            </div>
        </form>
    </div>

    <!-- Chart Panel -->
    <div 
        x-data="telemetryChartComponent(@js($chartData), '{{ $device->uid ?? '' }}', '{{ $selectedRange }}', @js($selectedMetrics), '{{ $startDate ?? '' }}', '{{ $endDate ?? '' }}')" 
        class="bg-panel border border-line rounded-md p-4 mb-6"
    >
        <div class="flex items-center justify-between pb-3 border-b border-line mb-3">
            <div class="flex items-center space-x-3">
                <h2 class="text-sm font-semibold text-ink">{{ __('Bentuk Gelombang Deret Waktu') }}</h2>
                <span class="text-xs text-muted font-mono">
                    <span class="text-ink font-semibold" x-text="chartData.total_count"></span> {{ __('rekaman') }}
                </span>
            </div>
            <div class="flex items-center space-x-2">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono font-semibold border border-ok/30 text-ok bg-ok/10">
                    <span class="w-1.5 h-1.5 rounded-full bg-ok mr-1.5 animate-pulse"></span>
                    {{ __('STREAM AKTIF (5s)') }}
                </span>
            </div>
        </div>
        <div class="h-80 min-h-[320px] relative">
            <canvas id="telemetryChartCanvas"></canvas>
        </div>
    </div>

    <!-- High-Density Telemetry Table -->
    <div class="bg-panel border border-line rounded-md overflow-hidden mb-6">
        <div class="p-4 border-b border-line flex flex-col sm:flex-row sm:items-center justify-between gap-2">
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
                <thead class="bg-bg/40 text-muted uppercase text-xs tracking-wider border-b border-line">
                    <tr>
                        <th class="py-2.5 px-3 font-medium">{{ __('Waktu (WIB)') }}</th>
                        <th class="py-2.5 px-3 font-medium">{{ __('Mode') }}</th>
                        <th class="py-2.5 px-3 font-medium">{{ __('Tegangan') }}</th>
                        <th class="py-2.5 px-3 font-medium">{{ __('Arus') }}</th>
                        <th class="py-2.5 px-3 font-medium">{{ __('Daya') }}</th>
                        <th class="py-2.5 px-3 font-medium">{{ __('Kumulatif Wh') }}</th>
                        <th class="py-2.5 px-3 font-medium">{{ __('Kecepatan Angin') }}</th>
                        <th class="py-2.5 px-3 font-medium">{{ __('Elevasi') }}</th>
                        <th class="py-2.5 px-3 font-medium">{{ __('Azimut') }}</th>
                        <th class="py-2.5 px-3 font-medium">{{ __('Suhu') }}</th>
                        <th class="py-2.5 px-3 font-medium">{{ __('Sinyal GPS') }}</th>
                        <th class="py-2.5 px-3 font-medium">{{ __('Kalibrasi IMU') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line font-mono text-xs">
                    @forelse($telemetries as $tel)
                        <tr class="hover:bg-line/20 transition-colors">
                            <td class="py-2.5 px-3 font-mono text-ink whitespace-nowrap">
                                {{ $tel->recorded_at ? $tel->recorded_at->setTimezone('Asia/Jakarta')->format('d M H:i:s') . ' WIB' : '--' }}
                            </td>
                            <td class="py-2.5 px-3">
                                <span class="px-1.5 py-0.5 rounded text-[11px] font-mono uppercase border border-line
                                    {{ $tel->mode === 'auto' ? 'bg-blue-500/10 text-blue-400 border-blue-500/30' : ($tel->mode === 'stow' ? 'bg-rose-500/10 text-rose-400 border-rose-500/30' : 'bg-bg text-muted') }}">
                                    {{ match(strtolower($tel->mode)) { 'auto' => __('Otomatis'), 'manual' => __('Manual'), 'stow' => __('Stow'), default => strtoupper($tel->mode) } }}
                                </span>
                            </td>
                            <td class="py-2.5 px-3 text-ink">{{ formatNumberId($tel->voltage, 1) }} V</td>
                            <td class="py-2.5 px-3 text-ink">{{ formatNumberId($tel->current, 2) }} A</td>
                            <td class="py-2.5 px-3 font-bold text-accent">{{ formatPowerW($tel->power) }}</td>
                            <td class="py-2.5 px-3 text-muted">{{ formatNumberId($tel->energy_wh, 0) }} Wh</td>
                            <td class="py-2.5 px-3 {{ $tel->wind_speed >= 15.0 ? 'text-danger font-bold' : 'text-ink' }}">
                                {{ formatNumberId($tel->wind_speed, 1) }} m/s
                            </td>
                            <td class="py-2.5 px-3 text-ink">
                                {{ formatNumberId($tel->pitch_elev, 1) }}&deg;
                                <span class="text-muted text-[11px]">({{ formatNumberId($tel->target_elev, 1) }}&deg;)</span>
                            </td>
                            <td class="py-2.5 px-3 text-ink">
                                {{ formatNumberId($tel->azimuth, 1) }}&deg;
                                <span class="text-muted text-[11px]">({{ formatNumberId($tel->target_az, 1) }}&deg;)</span>
                            </td>
                            <td class="py-2.5 px-3 text-ink">{{ formatNumberId($tel->temp_c, 1) }}&deg;C</td>
                            <td class="py-2.5 px-3">
                                <span class="text-[11px] {{ $tel->fix ? 'text-ok font-semibold' : 'text-warn' }}">
                                    {{ $tel->fix ? '3D (' . $tel->satellites . 's)' : 'No Fix' }}
                                </span>
                            </td>
                            <td class="py-2.5 px-3">
                                <span class="px-1.5 py-0.5 rounded text-[11px] font-mono border {{ $tel->cal_sys >= 2 ? 'border-ok/30 text-ok bg-ok/10' : 'border-warn/30 text-warn bg-warn/10' }}">
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
            <div class="p-3 border-t border-line bg-bg/40">
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
                                        color: '#9ca3af',
                                        padding: 14
                                    }
                                },
                                tooltip: {
                                    backgroundColor: '#1f2937',
                                    titleColor: '#f9fafb',
                                    bodyColor: '#f9fafb',
                                    borderColor: '#374151',
                                    borderWidth: 1,
                                    padding: 10,
                                    titleFont: { size: 12, family: 'Inter' },
                                    bodyFont: { size: 12, family: 'JetBrains Mono' },
                                    cornerRadius: 6,
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
                                    grid: { color: '#374151', drawTicks: false },
                                    ticks: {
                                        font: { size: 11, family: 'JetBrains Mono' },
                                        color: '#9ca3af',
                                        maxTicksLimit: 12
                                    }
                                },
                                y: {
                                    type: 'linear',
                                    display: true,
                                    position: 'left',
                                    grid: { color: '#374151', drawTicks: false },
                                    ticks: {
                                        font: { size: 11, family: 'JetBrains Mono' },
                                        color: '#9ca3af',
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
                                        color: '#9ca3af',
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
