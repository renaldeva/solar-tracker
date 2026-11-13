<x-layouts.app :title="__('Laporan')">
    <x-page-header 
        :title="__('Energy Production Reports')" 
        :subtitle="__('Hasilkan pernyataan hasil panen surya harian dan bulanan yang terverifikasi untuk operasi, pemeliharaan, dan akuntansi.')"
        :badge="__('Laporan Produksi Energi Standar')"
    >
        <x-slot:actions>
            <div class="flex items-center space-x-2">
                <a 
                    href="{{ route('reports.export', request()->all()) }}" 
                    class="inline-flex items-center h-10 px-4 rounded-xl border border-line bg-panel hover:bg-panel-alt text-ink text-sm font-medium transition-colors shadow-xs"
                    title="{{ __('Unduh Laporan CSV') }}"
                >
                    <svg class="w-4 h-4 mr-2 text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>{{ __('Unduh Laporan CSV') }}</span>
                </a>
            </div>
        </x-slot:actions>
    </x-page-header>

    <!-- Status Banner: "Apakah semuanya baik-baik saja?" -->
    <div class="mb-6 p-4 rounded-2xl bg-panel border border-line shadow-xs flex items-center justify-between">
        <div class="flex items-center space-x-3.5">
            <div class="w-3.5 h-3.5 rounded-full bg-emerald-500 ring-4 ring-emerald-50 dark:ring-emerald-950/50"></div>
            <div>
                <h2 class="text-sm font-semibold text-ink">
                    Laporan Produksi Energi Terverifikasi
                </h2>
                <p class="text-xs text-muted mt-0.5">
                    Periode: <strong class="text-ink">{{ $reportType === 'daily' ? 'Harian (' . $selectedDate . ')' : 'Bulanan (' . $selectedMonth . ')' }}</strong> &middot; Mencakup {{ count($rows) }} unit pelacak aktif.
                </p>
            </div>
        </div>
        <div class="hidden sm:flex items-center space-x-2">
            <span class="px-3 py-1 rounded-xl text-xs font-mono font-semibold bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800/60">
                {{ $reportType === 'daily' ? 'HARIAN' : 'BULANAN' }}
            </span>
        </div>
    </div>

    <!-- Report Criteria Card (Smart Nursery Style) -->
    <div class="bg-panel border border-line rounded-2xl p-4 sm:p-5 mb-6 shadow-xs">
        <form method="GET" action="{{ route('reports') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5 items-end">
            <!-- Report Type -->
            <div>
                <label for="rep-type" class="block text-xs font-semibold text-muted uppercase tracking-wider mb-1.5">{{ __('Tipe Laporan') }}</label>
                <div class="relative">
                    <select id="rep-type" name="type" onchange="this.form.submit()" class="appearance-none w-full text-xs font-semibold bg-panel border border-line rounded-xl pl-3 pr-8 py-2.5 text-ink focus:outline-none focus:border-blue-500 shadow-2xs">
                        <option value="daily" {{ $reportType === 'daily' ? 'selected' : '' }}>{{ __('Ringkasan Energi Harian') }}</option>
                        <option value="monthly" {{ $reportType === 'monthly' ? 'selected' : '' }}>{{ __('Audit Produksi Bulanan') }}</option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2.5 text-muted">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Date / Month Picker -->
            @if($reportType === 'daily')
                <div>
                    <label for="rep-date" class="block text-xs font-semibold text-muted uppercase tracking-wider mb-1.5">{{ __('Tanggal Target') }}</label>
                    <input type="date" id="rep-date" name="date" value="{{ $selectedDate }}" class="w-full text-xs font-mono bg-panel border border-line rounded-xl px-3 py-2 text-ink focus:outline-none focus:border-blue-500 shadow-2xs">
                </div>
            @else
                <div>
                    <label for="rep-month" class="block text-xs font-semibold text-muted uppercase tracking-wider mb-1.5">{{ __('Bulan Target') }}</label>
                    <input type="month" id="rep-month" name="month" value="{{ $selectedMonth }}" class="w-full text-xs font-mono bg-panel border border-line rounded-xl px-3 py-2 text-ink focus:outline-none focus:border-blue-500 shadow-2xs">
                </div>
            @endif

            <!-- Site Filter -->
            <div>
                <label for="rep-site" class="block text-xs font-semibold text-muted uppercase tracking-wider mb-1.5">{{ __('Lokasi Fasilitas') }}</label>
                <div class="relative">
                    <select id="rep-site" name="site_id" class="appearance-none w-full text-xs font-semibold bg-panel border border-line rounded-xl pl-3 pr-8 py-2.5 text-ink focus:outline-none focus:border-blue-500 shadow-2xs">
                        <option value="">{{ __('Semua Fasilitas') }}</option>
                        @foreach($sites as $site)
                            <option value="{{ $site->id }}" {{ $selectedSiteId == $site->id ? 'selected' : '' }}>{{ $site->name }}</option>
                        @endforeach
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2.5 text-muted">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Device Filter -->
            <div>
                <label for="rep-device" class="block text-xs font-semibold text-muted uppercase tracking-wider mb-1.5">{{ __('Perangkat Khusus') }}</label>
                <div class="relative">
                    <select id="rep-device" name="device_id" class="appearance-none w-full text-xs font-semibold bg-panel border border-line rounded-xl pl-3 pr-8 py-2.5 text-ink focus:outline-none focus:border-blue-500 shadow-2xs">
                        <option value="">{{ __('Semua Perangkat') }}</option>
                        @foreach($devices as $dev)
                            <option value="{{ $dev->id }}" {{ $selectedDeviceId == $dev->id ? 'selected' : '' }}>{{ $dev->name }} ({{ $dev->uid }})</option>
                        @endforeach
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2.5 text-muted">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Generate Button -->
            <div>
                <button type="submit" class="w-full inline-flex items-center justify-center h-10 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold transition-colors shadow-xs cursor-pointer">
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>{{ __('Hasilkan Laporan') }}</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Summary KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <x-kpi-card 
            :title="__('Total Generasi')" 
            :value="app()->getLocale() === 'id' ? formatNumberId($summary['total_energy_kwh'], 2) : number_format($summary['total_energy_kwh'], 2)" 
            unit="kWh"
            tone="ok"
            :subtitle="__('Agregat Hasil Panen DC')"
        />

        <x-kpi-card 
            :title="__('Daya Puncak Array')" 
            :value="app()->getLocale() === 'id' ? formatPowerW($summary['avg_peak_power_w'] ?? 0) : number_format($summary['avg_peak_power_w'] ?? 0, 1) . ' W'" 
            tone="accent"
            :subtitle="__('Output puncak terdaftar')"
        />

        <x-kpi-card 
            :title="__('Ketersediaan Operasi')" 
            :value="app()->getLocale() === 'id' ? formatNumberId($summary['avg_uptime_percent'], 1) : number_format($summary['avg_uptime_percent'], 1)" 
            unit="%"
            :tone="$summary['avg_uptime_percent'] >= 90 ? 'ok' : 'warn'"
            :subtitle="__('Rata-rata waktu aktif')"
        />

        <x-kpi-card 
            :title="__('Peringatan Dipicu')" 
            :value="$summary['total_alerts']" 
            unit="kejadian"
            :tone="$summary['total_alerts'] > 0 ? 'warn' : 'default'"
            :subtitle="__('Keamanan & perangkat keras')"
        />
    </div>

    <!-- Report Table Card (Smart Nursery Style) -->
    <div class="bg-panel border border-line rounded-2xl overflow-hidden mb-6 shadow-xs">
        <div class="p-4 sm:p-5 border-b border-line flex flex-col sm:flex-row sm:items-center justify-between gap-2 bg-panel-alt/60">
            <div>
                <h3 class="text-sm font-semibold text-ink">
                    {{ __('Pernyataan Kinerja') }}: {{ $reportType === 'daily' ? __('Hari') . ' ' . $selectedDate : __('Bulan') . ' ' . $selectedMonth }}
                </h3>
                <p class="text-xs text-muted mt-0.5">{{ __('Laporan audit generasi energi surya terverifikasi.') }}</p>
            </div>
            <div class="text-xs font-mono text-muted">
                {{ __('Total Unit') }}: <span class="font-semibold text-ink">{{ count($rows) }}</span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-panel-alt text-muted uppercase text-[11px] font-semibold tracking-wider border-b border-line">
                    <tr>
                        <th class="py-3 px-4">{{ __('Periode') }}</th>
                        <th class="py-3 px-4">{{ __('Perangkat Pelacak') }}</th>
                        <th class="py-3 px-4">{{ __('Lokasi Fasilitas') }}</th>
                        <th class="py-3 px-4">{{ __('Hasil Energi') }}</th>
                        <th class="py-3 px-4">{{ __('Progres Target') }}</th>
                        <th class="py-3 px-4">{{ __('Daya Puncak') }}</th>
                        <th class="py-3 px-4">{{ __('Daya Rata-rata') }}</th>
                        <th class="py-3 px-4">{{ __('Kecepatan Angin Maks') }}</th>
                        <th class="py-3 px-4">{{ __('Waktu Operasional') }}</th>
                        <th class="py-3 px-4 text-right">{{ __('Peringatan') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line font-mono text-xs">
                    @forelse($rows as $row)
                        <tr class="hover:bg-panel-alt/50 transition-colors">
                            <td class="py-3.5 px-4 text-ink whitespace-nowrap">{{ $row['period_label'] }}</td>
                            <td class="py-3.5 px-4">
                                <div class="font-semibold text-ink font-sans">{{ $row['device_name'] }}</div>
                                <div class="text-[11px] text-muted">{{ $row['device_uid'] }}</div>
                            </td>
                            <td class="py-3.5 px-4 font-sans text-muted">{{ $row['site_name'] }}</td>
                            <td class="py-3.5 px-4 font-bold text-blue-600 dark:text-blue-400">
                                {{ app()->getLocale() === 'id' ? formatNumberId($row['energy_kwh'], 2) : number_format($row['energy_kwh'], 2) }} kWh
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="flex items-center space-x-2">
                                    <div class="w-16 bg-panel-alt border border-line rounded-full h-2 overflow-hidden">
                                        <div class="bg-blue-600 h-full rounded-full" style="width: {{ min(100, $row['progress_percent']) }}%"></div>
                                    </div>
                                    <span class="text-[11px] font-semibold text-ink">{{ app()->getLocale() === 'id' ? formatNumberId($row['progress_percent'], 1) : number_format($row['progress_percent'], 1) }}%</span>
                                </div>
                            </td>
                            <td class="py-3.5 px-4 text-ink">
                                {{ app()->getLocale() === 'id' ? formatPowerW($row['peak_power_w'] ?? 0) : number_format($row['peak_power_w'] ?? 0, 1) . ' W' }}
                            </td>
                            <td class="py-3.5 px-4 text-muted">
                                {{ app()->getLocale() === 'id' ? formatPowerW($row['avg_power_w'] ?? 0) : number_format($row['avg_power_w'] ?? 0, 1) . ' W' }}
                            </td>
                            <td class="py-3.5 px-4 {{ $row['max_wind_ms'] >= 15.0 ? 'text-rose-600 dark:text-rose-400 font-bold' : 'text-ink' }}">
                                {{ app()->getLocale() === 'id' ? formatNumberId($row['max_wind_ms'], 1) : number_format($row['max_wind_ms'], 1) }} m/s
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="inline-flex items-center space-x-1 {{ $row['uptime_percent'] >= 90 ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $row['uptime_percent'] >= 90 ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                                    <span class="font-semibold">{{ app()->getLocale() === 'id' ? formatNumberId($row['uptime_percent'], 1) : number_format($row['uptime_percent'], 1) }}%</span>
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <span class="px-2 py-0.5 rounded-md text-[11px] font-mono border {{ $row['alerts_count'] > 0 ? 'border-amber-200 dark:border-amber-800/60 text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/40 font-semibold' : 'border-line text-muted bg-panel-alt' }}">
                                    {{ $row['alerts_count'] }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="p-8 text-center text-muted font-sans text-sm">
                                <div class="w-12 h-12 rounded-2xl bg-panel-alt text-muted mx-auto flex items-center justify-center mb-3">
                                    <svg class="w-6 h-6 text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <div class="text-sm font-semibold text-ink">{{ __('Tidak ada data ditemukan yang cocok dengan kriteria laporan yang ditentukan.') }}</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.app>
