<x-layouts.app title="{{ __('Dashboard') }}">
    <!-- Banner Status Besar: "Apakah semuanya baik-baik saja?" -->
    @php
        $isAllNominal = ($kpis['active_alerts'] == 0) && ($kpis['max_wind_ms'] < 15.0);
    @endphp
    <div class="mb-6 p-4 rounded-2xl border flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 {{ $isAllNominal ? 'bg-emerald-50/80 dark:bg-emerald-950/40 border-emerald-200 dark:border-emerald-800/60 text-emerald-950 dark:text-emerald-200' : 'bg-amber-50/80 dark:bg-amber-950/40 border-amber-200 dark:border-amber-800/60 text-amber-950 dark:text-amber-200' }}">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 {{ $isAllNominal ? 'bg-emerald-500 text-white' : 'bg-amber-500 text-white' }}">
                @if($isAllNominal)
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                @else
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                @endif
            </div>
            <div>
                <h2 class="text-sm sm:text-base font-bold tracking-tight">
                    {{ $isAllNominal ? __('Semua Sistem Beroperasi Normal') : __('Perlu Tindakan Penanganan') }}
                </h2>
                <p class="text-xs text-slate-600 dark:text-slate-300 mt-0.5">
                    {{ $isAllNominal 
                        ? __('Pelacak surya aktif mengikuti posisi matahari dan produksi daya stabil.') 
                        : __('Terdapat ') . $kpis['active_alerts'] . __(' peringatan aktif pada perangkat yang membutuhkan perhatian.') }}
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2 self-end sm:self-auto shrink-0">
            <a 
                href="{{ route('solar-tracker') }}" 
                class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl border border-blue-200 dark:border-blue-800/60 bg-blue-50 dark:bg-blue-950/50 hover:bg-blue-100 dark:hover:bg-blue-900/50 text-blue-700 dark:text-blue-300 text-xs font-semibold transition-colors"
            >
                <span>{{ __('Kontrol Pelacak') }}</span>
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </a>
        </div>
    </div>

    <!-- Top KPI Cards Row (5 Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        <!-- 1. Total Perangkat -->
        <x-kpi-card 
            :title="__('Total Perangkat')" 
            :value="$kpis['total_devices']" 
            unit="Unit"
            badge="{{ $kpis['online_devices'] }} Online"
            tone="ok"
            subtitle="{{ $kpis['online_devices'] }} aktif normal · {{ $kpis['offline_devices'] }} mati"
        >
            <x-slot:icon>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
            </x-slot:icon>
        </x-kpi-card>

        <!-- 2. Energi Hari Ini (dengan progress bar ke target) -->
        <x-kpi-card 
            :title="__('Energi Hari Ini')" 
            :value="formatNumberId($kpis['daily_energy_kwh'], 1)" 
            unit="kWh"
            badge="{{ $kpis['energy_progress_percent'] }}%"
            tone="accent"
            subtitle="{{ __('Target: ') . formatNumberId($kpis['energy_target_kwh'], 1) . ' kWh' }}"
        >
            <x-slot:icon>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
            </x-slot:icon>
            <!-- Progress Bar ke Target -->
            <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden mt-1">
                <div class="bg-blue-600 h-1.5 rounded-full transition-all duration-300" style="width: {{ min(100, $kpis['energy_progress_percent']) }}%"></div>
            </div>
        </x-kpi-card>

        <!-- 3. Daya Puncak Hari Ini (W) -->
        <x-kpi-card 
            :title="__('Daya Puncak Hari Ini')" 
            :value="formatPowerW($kpis['peak_power_w'] ?? ($kpis['peak_power_kw'] * 1000), false)" 
            unit="W"
            :subtitle="__('Output daya listrik tertinggi')"
            tone="accent"
        >
            <x-slot:icon>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
            </x-slot:icon>
        </x-kpi-card>

        <!-- 4. Alert Aktif (merah jika > 0) -->
        <x-kpi-card 
            :title="__('Alert Aktif')" 
            :value="$kpis['active_alerts']" 
            tone="{{ $kpis['active_alerts'] > 0 ? 'danger' : 'ok' }}"
            badge="{{ $kpis['active_alerts'] > 0 ? __('PERHATIAN') : __('NORMAL') }}"
            subtitle="{{ $kpis['active_alerts'] > 0 ? __('Perlu tindakan segera') : __('Semua sistem aman') }}"
        >
            <x-slot:icon>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
            </x-slot:icon>
        </x-kpi-card>

        <!-- 5. Angin Maks (m/s, merah jika > 15) -->
        <x-kpi-card 
            :title="__('Angin Maks')" 
            :value="formatNumberId($kpis['max_wind_ms'], 1)" 
            unit="m/s"
            tone="{{ $kpis['max_wind_ms'] >= 15.0 ? 'danger' : 'neutral' }}"
            badge="{{ $kpis['max_wind_ms'] >= 15.0 ? __('STOW') : __('AMAN') }}"
            subtitle="{{ $kpis['max_wind_ms'] >= 15.0 ? __('Batas stow terlampaui') : __('Di bawah batas aman (15 m/s)') }}"
        >
            <x-slot:icon>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </x-slot:icon>
        </x-kpi-card>
    </div>

    <!-- Middle Row: Power Production Chart (2/3) + Target Gauge & Critical Alerts (1/3) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <!-- Grafik Produksi Daya (2/3 cols) -->
        <div class="lg:col-span-2">
            <div 
                x-data="powerChartComponent(@js($chartData))" 
                class="bg-panel rounded-2xl border border-line p-5 sm:p-6 flex flex-col h-full"
            >
                <!-- Chart Header & Tab Selector -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-line gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-xl bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 border border-blue-100 dark:border-blue-900/50 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-sm sm:text-base font-bold text-ink tracking-tight">{{ __('Produksi Daya') }}</h2>
                            <p class="text-xs text-muted mt-0.5">{{ __('Hasil daya listrik real-time dari array surya (PZEM-017)') }}</p>
                        </div>
                    </div>

                    <!-- Day / Week / Month Pill Selector -->
                    <div class="inline-flex items-center p-1 bg-slate-100/80 dark:bg-slate-800/80 rounded-full self-start sm:self-auto border border-slate-200/60 dark:border-slate-700/60">
                        <button 
                            type="button" 
                            @click="switchView('day')" 
                            :class="activeView === 'day' ? 'bg-blue-600 text-white font-semibold shadow-xs' : 'text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white'"
                            class="px-3 py-1 rounded-full text-xs font-medium transition-all cursor-pointer"
                        >
                            {{ __('Hari') }} (W)
                        </button>
                        <button 
                            type="button" 
                            @click="switchView('week')" 
                            :class="activeView === 'week' ? 'bg-blue-600 text-white font-semibold shadow-xs' : 'text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white'"
                            class="px-3 py-1 rounded-full text-xs font-medium transition-all cursor-pointer"
                        >
                            {{ __('Minggu') }} (kWh)
                        </button>
                        <button 
                            type="button" 
                            @click="switchView('month')" 
                            :class="activeView === 'month' ? 'bg-blue-600 text-white font-semibold shadow-xs' : 'text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white'"
                            class="px-3 py-1 rounded-full text-xs font-medium transition-all cursor-pointer"
                        >
                            {{ __('Bulan') }} (kWh)
                        </button>
                    </div>
                </div>

                <!-- Canvas Area -->
                <div class="mt-4 relative w-full h-72 sm:h-80 overflow-hidden">
                    <canvas id="powerChartCanvas"></canvas>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan (1/3 col): Target Gauge + Alert Kritis -->
        <div class="lg:col-span-1 space-y-6">
            <!-- 1. Gauge Setengah Lingkaran: Target Energi Harian -->
            <div class="bg-panel rounded-2xl border border-line p-5 flex flex-col items-center justify-between">
                <div class="w-full flex items-center justify-between pb-3 border-b border-line">
                    <span class="text-xs font-semibold text-muted uppercase tracking-wider">{{ __('Capaian Target Energi') }}</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-blue-50 dark:bg-blue-950/50 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800/60">
                        {{ $kpis['energy_progress_percent'] }}%
                    </span>
                </div>

                <!-- Half-circle SVG Gauge -->
                @php
                    $gaugePercent = min(100, max(0, (float) $kpis['energy_progress_percent']));
                    // Circumference of semicircle r=70 is pi * 70 = 219.91
                    $semiCircumference = 219.91;
                    $dashOffset = $semiCircumference * (1 - ($gaugePercent / 100));
                @endphp
                <div class="relative w-48 h-28 my-3 flex items-end justify-center">
                    <svg class="w-48 h-28 overflow-visible" viewBox="0 0 160 90">
                        <!-- Background arc -->
                        <path 
                            d="M 15 80 A 65 65 0 0 1 145 80" 
                            fill="none" 
                            stroke="var(--gauge-bg)" 
                            stroke-width="12" 
                            stroke-linecap="round"
                        />
                        <!-- Value arc -->
                        <path 
                            d="M 15 80 A 65 65 0 0 1 145 80" 
                            fill="none" 
                            stroke="var(--gauge-fill)" 
                            stroke-width="12" 
                            stroke-linecap="round"
                            stroke-dasharray="204.2"
                            stroke-dashoffset="{{ 204.2 * (1 - ($gaugePercent / 100)) }}"
                            class="transition-all duration-700 ease-out"
                        />
                    </svg>
                    <div class="absolute bottom-1 text-center">
                        <div class="text-2xl font-bold font-mono text-ink tabular-nums leading-none">
                            {{ $gaugePercent }}<span class="text-base font-normal text-muted">%</span>
                        </div>
                        <div class="text-[11px] text-muted font-medium mt-1">
                            {{ formatNumberId($kpis['daily_energy_kwh'], 1) }} / {{ formatNumberId($kpis['energy_target_kwh'], 1) }} kWh
                        </div>
                    </div>
                </div>

                <div class="w-full pt-3 border-t border-line text-center">
                    <p class="text-xs text-muted">
                        {{ $gaugePercent >= 100 ? __('Target harian tercapai!') : __('Produksi aktif menuju target harian.') }}
                    </p>
                </div>
            </div>

            <!-- 2. Panel Alert Kritis -->
            <x-card :title="__('Alert Kritis')" :subtitle="__('Peringatan terbuka yang memerlukan perhatian')">
                <x-slot:actions>
                    <a href="{{ route('alerts') }}" class="text-xs font-semibold text-blue-600 hover:text-blue-800 transition-colors">
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
                                        'border' => 'border-rose-200 dark:border-rose-800/60',
                                        'bg' => 'bg-rose-50 dark:bg-rose-950/40',
                                        'text' => 'text-rose-700 dark:text-rose-300',
                                        'dot' => 'bg-rose-500',
                                    ],
                                    'warning' => [
                                        'border' => 'border-amber-200 dark:border-amber-800/60',
                                        'bg' => 'bg-amber-50 dark:bg-amber-950/40',
                                        'text' => 'text-amber-700 dark:text-amber-300',
                                        'dot' => 'bg-amber-500',
                                    ],
                                    default => [
                                        'border' => 'border-blue-200 dark:border-blue-800/60',
                                        'bg' => 'bg-blue-50 dark:bg-blue-950/40',
                                        'text' => 'text-blue-700 dark:text-blue-300',
                                        'dot' => 'bg-blue-500',
                                    ],
                                };
                            @endphp
                            <div class="p-3 rounded-xl border border-line bg-panel-alt/50 hover:bg-panel-alt transition-colors">
                                <div class="flex items-center justify-between gap-2 mb-1.5">
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-semibold border {{ $sevConfig['border'] }} {{ $sevConfig['bg'] }} {{ $sevConfig['text'] }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $sevConfig['dot'] }}"></span>
                                        <span>{{ strtoupper($alert->severity) }}</span>
                                    </span>
                                    <span class="text-[11px] text-muted">
                                        {{ $alert->opened_at?->diffForHumans() }}
                                    </span>
                                </div>
                                <div class="font-semibold text-xs text-ink truncate">{{ $alert->code }}</div>
                                <div class="text-xs text-muted mt-0.5 leading-snug">{{ $alert->display_message }}</div>
                                <div class="text-[11px] text-muted/80 mt-1.5 pt-1.5 border-t border-line">
                                    {{ __('Perangkat: ') }} {{ $alert->device->name ?? $alert->device->uid ?? __('Sistem') }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-6 text-center border border-dashed border-line rounded-xl bg-panel-alt/30">
                        <div class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-700 dark:text-emerald-400 mb-1">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
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
            <x-card :title="__('Status Perangkat')" :subtitle="__('Daftar node solar tracker terdaftar dan kondisinya')">
                <x-slot:actions>
                    <a href="{{ route('device-monitoring') }}" class="text-xs font-semibold text-blue-600 hover:text-blue-800 transition-colors">
                        {{ __('Pemantauan Lengkap') }} &rarr;
                    </a>
                </x-slot:actions>

                <div class="overflow-x-auto -mx-5 -my-5 p-5">
                    <table class="min-w-full divide-y divide-line text-xs">
                        <thead>
                            <tr class="text-left font-semibold text-muted uppercase tracking-wider">
                                <th class="py-2.5 pr-3">{{ __('Perangkat') }}</th>
                                <th class="py-2.5 px-3">{{ __('Status') }}</th>
                                <th class="py-2.5 px-3">{{ __('Mode') }}</th>
                                <th class="py-2.5 px-3 text-right">{{ __('Daya DC') }}</th>
                                <th class="py-2.5 px-3 text-right">{{ __('Tegangan') }}</th>
                                <th class="py-2.5 px-3 text-right">{{ __('Sinyal') }}</th>
                                <th class="py-2.5 pl-3 text-right">{{ __('Terakhir Dilihat') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line text-ink">
                            @forelse($devices as $dev)
                                <tr class="hover:bg-panel-alt transition-colors">
                                    <td class="py-3 pr-3">
                                        <div class="font-semibold text-ink">{{ $dev->name }}</div>
                                        <div class="font-mono text-[11px] text-muted">{{ $dev->uid }}</div>
                                    </td>
                                    <td class="py-3 px-3">
                                        <x-status-pill :status="$dev->status" size="sm" />
                                    </td>
                                    <td class="py-3 px-3">
                                        <span class="inline-block px-2 py-0.5 rounded-full text-[11px] font-semibold uppercase border border-line bg-panel-alt text-ink">
                                            {{ $dev->mode }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-3 text-right font-mono font-semibold text-ink">
                                        {{ formatPowerW($dev->latestTelemetry?->power) }}
                                    </td>
                                    <td class="py-3 px-3 text-right font-mono text-muted">
                                        {{ $dev->latestTelemetry ? formatNumberId($dev->latestTelemetry->voltage, 1) . ' V' : '--' }}
                                    </td>
                                    <td class="py-3 px-3 text-right font-mono text-muted">
                                        {{ $dev->rssi ? $dev->rssi . ' dBm' : '--' }}
                                    </td>
                                    <td class="py-3 pl-3 text-right font-mono text-muted">
                                        {{ $dev->last_seen_at ? $dev->last_seen_at->setTimezone('Asia/Jakarta')->format('H.i.s') . ' WIB' : '--' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-8 text-center text-muted">
                                        {{ __('Tidak ada perangkat terdaftar.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>

        <!-- Panel Metrik Live dari $latestTelemetry (1/3 col) dengan accordion detail -->
        <div class="lg:col-span-1" x-data="{ showDetail: false }">
            <x-card 
                :title="__('Metrik Langsung')" 
                :subtitle="$primaryDevice ? $primaryDevice->name . ' (' . $primaryDevice->uid . ')' : __('Tidak ada perangkat')"
            >
                <x-slot:actions>
                    <x-status-pill :status="$primaryDevice->status ?? 'offline'" size="sm" />
                </x-slot:actions>

                @if($primaryDevice)
                    <div class="space-y-3">
                        <!-- Mode Operasi -->
                        <div class="flex items-center justify-between p-3 rounded-xl border border-line bg-panel-alt/60 text-xs">
                            <span class="text-muted font-medium">{{ __('Mode Operasi:') }}</span>
                            <span class="font-bold text-blue-600 dark:text-blue-400 uppercase">{{ $primaryDevice->mode }}</span>
                        </div>

                        <!-- 3 Parameter Utama (Tegangan, Arus, Daya) -->
                        <div class="grid grid-cols-3 gap-2">
                            <div class="p-3 rounded-xl border border-line bg-panel text-center">
                                <div class="text-[11px] font-semibold text-muted uppercase">{{ __('Tegangan') }}</div>
                                <div class="text-base font-bold font-mono text-ink mt-0.5">
                                    {{ $latestTelemetry ? formatNumberId($latestTelemetry->voltage, 1) : '--' }}
                                </div>
                                <div class="text-[10px] text-muted font-medium">V</div>
                            </div>
                            <div class="p-3 rounded-xl border border-line bg-panel text-center">
                                <div class="text-[11px] font-semibold text-muted uppercase">{{ __('Arus') }}</div>
                                <div class="text-base font-bold font-mono text-ink mt-0.5">
                                    {{ $latestTelemetry ? formatNumberId($latestTelemetry->current, 2) : '--' }}
                                </div>
                                <div class="text-[10px] text-muted font-medium">A</div>
                            </div>
                            <div class="p-3 rounded-xl border border-line bg-panel text-center">
                                <div class="text-[11px] font-semibold text-muted uppercase">{{ __('Daya') }}</div>
                                <div class="text-base font-bold font-mono text-blue-600 dark:text-blue-400 mt-0.5 truncate">
                                    {{ formatPowerW($latestTelemetry?->power, false) }}
                                </div>
                                <div class="text-[10px] text-muted font-medium">W</div>
                            </div>
                        </div>

                        <!-- Tombol Accordion Lihat Detail Teknis -->
                        <button 
                            type="button" 
                            @click="showDetail = !showDetail" 
                            class="w-full flex items-center justify-between p-2.5 rounded-xl border border-line bg-panel-alt hover:bg-panel-alt/80 text-xs font-semibold text-ink transition-colors cursor-pointer"
                        >
                            <span>{{ __('Lihat Detail Penyesuaian Sudut') }}</span>
                            <svg class="w-4 h-4 transform transition-transform duration-200" :class="showDetail ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <!-- Area Detail Teknis (Elevasi & Azimut) -->
                        <div x-show="showDetail" x-cloak class="space-y-2.5 pt-1">
                            <!-- Elevasi (Pitch) Alignment -->
                            <div class="p-3 rounded-xl border border-line bg-panel-alt/50">
                                <div class="flex items-center justify-between text-xs mb-1.5">
                                    <span class="font-semibold text-ink">{{ __('Sudut Kemiringan (Elevasi)') }}</span>
                                    <span class="text-[11px] text-muted capitalize">
                                        {{ $latestTelemetry?->elev_state ?? 'idle' }}
                                    </span>
                                </div>
                                <div class="grid grid-cols-2 gap-2 text-xs">
                                    <div>
                                        <div class="text-[10px] text-muted">{{ __('Saat Ini (Sensor)') }}</div>
                                        <div class="text-sm font-bold font-mono text-ink mt-0.5">
                                            {{ $latestTelemetry ? formatNumberId($latestTelemetry->pitch_elev, 1) : '--' }}°
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-[10px] text-muted">{{ __('Target (Matahari)') }}</div>
                                        <div class="text-sm font-bold font-mono text-blue-600 dark:text-blue-400 mt-0.5">
                                            {{ $latestTelemetry ? formatNumberId($latestTelemetry->target_elev, 1) : '--' }}°
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Azimut (Heading) Alignment -->
                            <div class="p-3 rounded-xl border border-line bg-panel-alt/50">
                                <div class="flex items-center justify-between text-xs mb-1.5">
                                    <span class="font-semibold text-ink">{{ __('Arah Kompas (Azimut)') }}</span>
                                    <span class="text-[11px] text-muted capitalize">
                                        {{ $latestTelemetry?->az_state ?? 'idle' }}
                                    </span>
                                </div>
                                <div class="grid grid-cols-2 gap-2 text-xs">
                                    <div>
                                        <div class="text-[10px] text-muted">{{ __('Saat Ini (Sensor)') }}</div>
                                        <div class="text-sm font-bold font-mono text-ink mt-0.5">
                                            {{ $latestTelemetry ? formatNumberId($latestTelemetry->azimuth, 1) : '--' }}°
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-[10px] text-muted">{{ __('Target (Matahari)') }}</div>
                                        <div class="text-sm font-bold font-mono text-blue-600 dark:text-blue-400 mt-0.5">
                                            {{ $latestTelemetry ? formatNumberId($latestTelemetry->target_az, 1) : '--' }}°
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Link to Solar Tracker -->
                        <a 
                            href="{{ route('solar-tracker') }}" 
                            class="w-full inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl border border-blue-200 dark:border-blue-800/60 bg-blue-50 dark:bg-blue-950/50 hover:bg-blue-100 dark:hover:bg-blue-900/50 text-blue-700 dark:text-blue-300 font-semibold text-xs transition-colors"
                        >
                            <span>{{ __('Buka Kontrol Tracker') }}</span>
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
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

                    // Safari memory guard: ensure canvas has no leftover chart instance
                    if (window.Chart && typeof window.Chart.getChart === 'function') {
                        const existing = window.Chart.getChart(ctx);
                        if (existing) {
                            try { existing.destroy(); } catch (e) {}
                        }
                    }
                    if (this.chart) {
                        try { this.chart.destroy(); } catch (e) {}
                        this.chart = null;
                    }

                    const dataset = this.chartData[this.activeView];
                    const initialLabel = this.activeView === 'day' 
                        ? (window.__ ? window.__('Daya (W)') : 'Daya (W)') 
                        : (window.__ ? window.__('Produksi per Jam') : 'Produksi per Jam');

                    const themeColors = window.getChartThemeColors ? window.getChartThemeColors() : {
                        primary: '#2563eb',
                        primaryFill: 'rgba(37, 99, 235, 0.08)',
                        grid: 'rgba(226, 232, 240, 0.7)',
                        text: '#64748b',
                        isDark: false
                    };

                    this.chart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: dataset.labels,
                            datasets: [{
                                label: initialLabel,
                                data: dataset.values,
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
                                        color: themeColors.grid,
                                    },
                                    ticks: {
                                        color: themeColors.text,
                                        maxTicksLimit: 12
                                    }
                                },
                                y: {
                                    beginAtZero: true,
                                    grid: {
                                        color: themeColors.grid,
                                    },
                                    ticks: {
                                        color: themeColors.text,
                                        callback: (value) => {
                                            return window.formatNumberId ? window.formatNumberId(value, 0) : value;
                                        }
                                    },
                                    title: {
                                        display: true,
                                        text: this.activeView === 'day' ? 'Daya (W)' : dataset.unit,
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
