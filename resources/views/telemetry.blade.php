<x-layouts.app :title="__('Telemetri')">
    <!-- Page Header -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-bold uppercase tracking-wider bg-slate-100 text-slate-700 border border-slate-200">
                    {{ __('Aliran Frekuensi Tinggi') }}
                </span>
                <span class="text-sm font-medium text-slate-400">{{ __('Paket Sensor Edge 5 Detik') }}</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900 mt-1">{{ __('Telemetry Data Explorer') }}</h1>
            <p class="text-sm text-slate-500 mt-1">{{ __('Filter metrik deret waktu di seluruh perangkat, periksa data mentah sensor, dan ekspor kumpulan data rekayasa.') }}</p>
        </div>

        <div class="flex items-center space-x-2">
            <a href="{{ route('telemetry.export', request()->all()) }}" class="inline-flex items-center px-4 py-2.5 rounded-lg bg-white border border-slate-200/80 hover:bg-slate-50 text-slate-700 text-sm font-semibold shadow-2xs transition-colors h-11 min-h-[44px]">
                <svg class="w-4 h-4 mr-2 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                {{ __('Ekspor CSV') }}
            </a>
            <a href="{{ route('solar-tracker') }}" class="inline-flex items-center px-4 py-2.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-xs transition-colors h-11 min-h-[44px]">
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                {{ __('Kontrol Langsung') }}
            </a>
        </div>
    </div>

    <!-- Filter Toolbar Card -->
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-2xs p-6 mb-6">
        <form method="GET" action="{{ route('telemetry') }}" id="telemetryFilterForm" class="space-y-4">
            <!-- Top Controls Row -->
            <div class="flex flex-wrap items-center justify-between gap-4">
                <!-- Device Picker -->
                <div class="flex items-center space-x-2">
                    <label for="filter-device" class="text-sm font-semibold text-slate-700">{{ __('Perangkat:') }}</label>
                    <select id="filter-device" name="device" onchange="this.form.submit()" class="text-sm font-medium bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 h-11 min-h-[44px]">
                        @foreach($devices as $dev)
                            <option value="{{ $dev->uid }}" {{ $device && $device->uid === $dev->uid ? 'selected' : '' }}>
                                {{ $dev->name }} ({{ $dev->uid }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Time Range Buttons -->
                <div class="flex items-center space-x-1.5 bg-slate-100 p-1.5 rounded-lg">
                    @foreach(['1h' => __('1 Jam'), '6h' => __('6 Jam'), '24h' => __('24 Jam'), '7d' => __('7 Hari')] as $val => $label)
                        <button type="submit" name="range" value="{{ $val }}" 
                                class="px-3.5 py-2 text-sm font-semibold rounded-md transition-colors h-9 {{ $selectedRange === $val ? 'bg-white text-blue-600 shadow-2xs' : 'text-slate-600 hover:text-slate-900' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- Metric Multi-Select Badges Row -->
            <div class="pt-4 border-t border-slate-100 flex flex-wrap items-center gap-2.5">
                <span class="text-sm font-semibold text-slate-500 mr-2">{{ __('Metrik Terlihat:') }}</span>
                @php
                    $availableMetrics = [
                        'power' => ['label' => __('Daya (W)'), 'class' => 'text-blue-700 bg-blue-50 border-blue-200'],
                        'voltage' => ['label' => __('Tegangan (V)'), 'class' => 'text-amber-700 bg-amber-50 border-amber-200'],
                        'current' => ['label' => __('Arus (A)'), 'class' => 'text-emerald-700 bg-emerald-50 border-emerald-200'],
                        'wind_speed' => ['label' => __('Kecepatan Angin (m/s)'), 'class' => 'text-red-700 bg-red-50 border-red-200'],
                        'pitch_elev' => ['label' => __('Elevasi (°)'), 'class' => 'text-purple-700 bg-purple-50 border-purple-200'],
                        'azimuth' => ['label' => __('Azimut (°)'), 'class' => 'text-cyan-700 bg-cyan-50 border-cyan-200'],
                        'temp_c' => ['label' => __('Suhu Panel (°C)'), 'class' => 'text-rose-700 bg-rose-50 border-rose-200'],
                    ];
                @endphp

                @foreach($availableMetrics as $key => $meta)
                    <label class="cursor-pointer inline-flex items-center space-x-2 px-3 py-1.5 rounded-lg border text-sm font-medium transition-all {{ in_array($key, $selectedMetrics) ? $meta['class'] : 'bg-slate-50 text-slate-400 border-slate-200' }}">
                        <input type="checkbox" name="metrics[]" value="{{ $key }}" onchange="document.getElementById('telemetryFilterForm').submit()" {{ in_array($key, $selectedMetrics) ? 'checked' : '' }} class="rounded text-blue-600 focus:ring-0 w-4 h-4">
                        <span>{{ $meta['label'] }}</span>
                    </label>
                @endforeach
            </div>
        </form>
    </div>

    <!-- Chart Card -->
    <div 
        x-data="telemetryChartComponent(@js($chartData), '{{ $device->uid ?? '' }}', '{{ $selectedRange }}', @js($selectedMetrics))" 
        class="bg-white rounded-xl border border-slate-200/80 shadow-2xs p-6 mb-6"
    >
        <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-4">
            <div>
                <h2 class="text-base sm:text-lg font-bold text-slate-900">{{ __('Bentuk Gelombang Deret Waktu') }}</h2>
                <p class="text-xs sm:text-sm text-slate-400 mt-0.5">{{ __('Titik sensor resolusi tinggi pada rentang yang dipilih') }}</p>
            </div>
            <div class="flex items-center space-x-3">
                <span class="text-sm font-mono text-slate-500">
                    <span class="font-bold text-slate-800" x-text="chartData.total_count"></span> {{ __('rekaman ditemukan') }}
                </span>
                <span class="inline-flex items-center px-2.5 py-1 rounded text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 font-mono">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 mr-2 animate-pulse"></span>
                    {{ __('ALIRAN LANGSUNG') }}
                </span>
            </div>
        </div>
        <div class="h-80 min-h-[320px] relative">
            <canvas id="telemetryChartCanvas"></canvas>
        </div>
    </div>

    <!-- High-Density Telemetry Table -->
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-2xs overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-base font-bold text-slate-900">{{ __('Paket Sensor Terekam') }}</h3>
            <span class="text-sm text-slate-500">{{ __('Waktu ditampilkan dalam UTC+7 (WIB)') }}</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50/80 text-slate-500 font-semibold border-b border-slate-100 text-xs uppercase">
                    <tr>
                        <th class="py-3 px-4">{{ __('Waktu (WIB)') }}</th>
                        <th class="py-3 px-4">{{ __('Mode') }}</th>
                        <th class="py-3 px-4">{{ __('Tegangan') }}</th>
                        <th class="py-3 px-4">{{ __('Arus') }}</th>
                        <th class="py-3 px-4">{{ __('Daya') }}</th>
                        <th class="py-3 px-4">{{ __('Kumulatif Wh') }}</th>
                        <th class="py-3 px-4">{{ __('Kecepatan Angin') }}</th>
                        <th class="py-3 px-4">{{ __('Elevasi') }}</th>
                        <th class="py-3 px-4">{{ __('Azimut') }}</th>
                        <th class="py-3 px-4">{{ __('Suhu (°C)') }}</th>
                        <th class="py-3 px-4">{{ __('Sinyal GPS') }}</th>
                        <th class="py-3 px-4">{{ __('Kalibrasi IMU') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-mono text-xs sm:text-sm">
                    @forelse($telemetries as $tel)
                        <tr class="hover:bg-slate-50/60 transition-colors h-[52px]">
                            <td class="py-3 px-4 font-sans font-medium text-slate-800 whitespace-nowrap">
                                {{ $tel->recorded_at ? $tel->recorded_at->setTimezone('Asia/Jakarta')->translatedFormat('d M H:i:s') . ' WIB' : '--' }}
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-0.5 rounded text-xs font-bold uppercase font-sans
                                    {{ $tel->mode === 'auto' ? 'bg-blue-50 text-blue-700' : ($tel->mode === 'stow' ? 'bg-red-50 text-red-700' : 'bg-slate-100 text-slate-700') }}">
                                    {{ match(strtolower($tel->mode)) { 'auto' => __('Otomatis'), 'manual' => __('Manual'), 'stow' => __('Mode Aman (Stow)'), default => strtoupper($tel->mode) } }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-slate-700">{{ app()->getLocale() === 'id' ? formatNumberId($tel->voltage, 1) : number_format($tel->voltage, 1) }} V</td>
                            <td class="py-3 px-4 text-slate-700">{{ app()->getLocale() === 'id' ? formatNumberId($tel->current, 2) : number_format($tel->current, 2) }} A</td>
                            <td class="py-3 px-4 font-bold text-blue-600">{{ app()->getLocale() === 'id' ? formatPowerW($tel->power) : number_format($tel->power, 1) . ' W' }}</td>
                            <td class="py-3 px-4 text-slate-500">{{ app()->getLocale() === 'id' ? formatNumberId($tel->energy_wh, 0) : number_format($tel->energy_wh, 0) }} Wh</td>
                            <td class="py-3 px-4 {{ $tel->wind_speed >= 15.0 ? 'text-red-600 font-bold' : 'text-slate-700' }}">
                                {{ app()->getLocale() === 'id' ? formatNumberId($tel->wind_speed, 1) : number_format($tel->wind_speed, 1) }} m/s
                            </td>
                            <td class="py-3 px-4 text-slate-700">
                                {{ app()->getLocale() === 'id' ? formatNumberId($tel->pitch_elev, 1) : number_format($tel->pitch_elev, 1) }}&deg;
                                <span class="text-slate-400 text-xs">({{ app()->getLocale() === 'id' ? formatNumberId($tel->target_elev, 1) : number_format($tel->target_elev, 1) }}&deg;)</span>
                            </td>
                            <td class="py-3 px-4 text-slate-700">
                                {{ app()->getLocale() === 'id' ? formatNumberId($tel->azimuth, 1) : number_format($tel->azimuth, 1) }}&deg;
                                <span class="text-slate-400 text-xs">({{ app()->getLocale() === 'id' ? formatNumberId($tel->target_az, 1) : number_format($tel->target_az, 1) }}&deg;)</span>
                            </td>
                            <td class="py-3 px-4 text-slate-700">{{ app()->getLocale() === 'id' ? formatNumberId($tel->temp_c, 1) : number_format($tel->temp_c, 1) }}&deg;C</td>
                            <td class="py-3 px-4">
                                <span class="font-sans text-xs font-semibold {{ $tel->fix ? 'text-emerald-600' : 'text-amber-600' }}">
                                    {{ $tel->fix ? __('Sinyal 3D') . ' (' . $tel->satellites . 's)' : __('Belum Ada Sinyal') }}
                                </span>
                            </td>
                            <td class="py-3 px-4 font-sans">
                                <span class="px-2 py-0.5 rounded text-xs font-bold font-mono {{ $tel->cal_sys >= 2 ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                                    Sys:{{ $tel->cal_sys ?? 0 }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="p-8 text-center text-slate-400 font-sans text-sm">
                                {{ __('Tidak ada data telemetri ditemukan untuk perangkat dan rentang waktu yang dipilih.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($telemetries->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $telemetries->links() }}
            </div>
        @endif
    </div>

    @push('scripts')
    <script>
        function telemetryChartComponent(initialChartData, deviceUid, range, selectedMetrics) {
            return {
                chartData: initialChartData,
                deviceUid: deviceUid,
                range: range,
                selectedMetrics: selectedMetrics,
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

                refreshChartData() {
                    const metricsParam = this.selectedMetrics.join(',');
                    fetch(`/telemetry/data?device=${this.deviceUid}&range=${this.range}&metrics=${metricsParam}`)
                        .then(res => res.json())
                        .then(data => {
                            if (!data || !data.labels) return;
                            this.chartData = data;
                            if (this.chart) {
                                this.chart.data.labels = data.labels;
                                this.chart.data.datasets = data.datasets;
                                this.chart.update('none');
                            }
                        })
                        .catch(err => console.debug('Telemetry polling pause:', err));
                },

                renderChart() {
                    const ctx = document.getElementById('telemetryChartCanvas');
                    if (!ctx) return;

                    const numberFmt = new Intl.NumberFormat('id-ID');

                    this.chart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: this.chartData.labels,
                            datasets: this.chartData.datasets
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: true,
                                    position: 'top',
                                    labels: {
                                        boxWidth: 14,
                                        font: { size: 14, family: 'Inter' },
                                        color: '#475569'
                                    }
                                },
                                tooltip: {
                                    backgroundColor: '#0f172a',
                                    padding: 10,
                                    titleFont: { size: 13, family: 'Inter' },
                                    bodyFont: { size: 13, family: 'Inter' },
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
                                    grid: { color: 'rgba(241, 245, 249, 1)' },
                                    ticks: { font: { size: 13, family: 'Inter' }, color: '#64748b', maxTicksLimit: 12 }
                                },
                                y: {
                                    beginAtZero: true,
                                    grid: { color: 'rgba(241, 245, 249, 1)' },
                                    ticks: {
                                        font: { size: 13, family: 'Inter' },
                                        color: '#64748b',
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
