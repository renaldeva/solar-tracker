<x-layouts.app :title="__('Laporan')">
    <!-- Page Header -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-bold uppercase tracking-wider bg-slate-100 text-slate-700 border border-slate-200">
                    {{ __('Audit & Kepatuhan') }}
                </span>
                <span class="text-sm font-medium text-slate-400">{{ __('Laporan Produksi Energi Standar') }}</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900 mt-1">{{ __('Energy Production Reports') }}</h1>
            <p class="text-sm text-slate-500 mt-1">{{ __('Hasilkan pernyataan hasil panen surya harian dan bulanan yang terverifikasi untuk operasi, pemeliharaan, dan akuntansi.') }}</p>
        </div>

        <div class="flex items-center space-x-2">
            <a href="{{ route('reports.export', request()->all()) }}" class="inline-flex items-center px-4 py-2.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-xs transition-colors h-11 min-h-[44px]">
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                {{ __('Unduh Laporan CSV') }}
            </a>
        </div>
    </div>

    <!-- Report Criteria Card -->
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-2xs p-6 mb-6">
        <form method="GET" action="{{ route('reports') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
            <!-- Report Type -->
            <div>
                <label for="rep-type" class="block text-sm font-semibold text-slate-700 mb-1.5">{{ __('Tipe Laporan') }}</label>
                <select id="rep-type" name="type" onchange="this.form.submit()" class="w-full text-sm font-medium bg-slate-50 border border-slate-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-blue-500 h-11 min-h-[44px]">
                    <option value="daily" {{ $reportType === 'daily' ? 'selected' : '' }}>{{ __('Ringkasan Energi Harian') }}</option>
                    <option value="monthly" {{ $reportType === 'monthly' ? 'selected' : '' }}>{{ __('Audit Produksi Bulanan') }}</option>
                </select>
            </div>

            <!-- Date / Month Picker -->
            @if($reportType === 'daily')
                <div>
                    <label for="rep-date" class="block text-sm font-semibold text-slate-700 mb-1.5">{{ __('Tanggal Target') }}</label>
                    <input type="date" id="rep-date" name="date" value="{{ $selectedDate }}" class="w-full text-sm font-medium bg-slate-50 border border-slate-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-blue-500 font-mono h-11 min-h-[44px]">
                </div>
            @else
                <div>
                    <label for="rep-month" class="block text-sm font-semibold text-slate-700 mb-1.5">{{ __('Bulan Target') }}</label>
                    <input type="month" id="rep-month" name="month" value="{{ $selectedMonth }}" class="w-full text-sm font-medium bg-slate-50 border border-slate-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-blue-500 font-mono h-11 min-h-[44px]">
                </div>
            @endif

            <!-- Site Filter -->
            <div>
                <label for="rep-site" class="block text-sm font-semibold text-slate-700 mb-1.5">{{ __('Lokasi Fasilitas') }}</label>
                <select id="rep-site" name="site_id" class="w-full text-sm font-medium bg-slate-50 border border-slate-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-blue-500 h-11 min-h-[44px]">
                    <option value="">{{ __('Semua Fasilitas') }}</option>
                    @foreach($sites as $site)
                        <option value="{{ $site->id }}" {{ $selectedSiteId == $site->id ? 'selected' : '' }}>{{ $site->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Device Filter -->
            <div>
                <label for="rep-device" class="block text-sm font-semibold text-slate-700 mb-1.5">{{ __('Perangkat Khusus') }}</label>
                <select id="rep-device" name="device_id" class="w-full text-sm font-medium bg-slate-50 border border-slate-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-blue-500 h-11 min-h-[44px]">
                    <option value="">{{ __('Semua Perangkat') }}</option>
                    @foreach($devices as $dev)
                        <option value="{{ $dev->id }}" {{ $selectedDeviceId == $dev->id ? 'selected' : '' }}>{{ $dev->name }} ({{ $dev->uid }})</option>
                    @endforeach
                </select>
            </div>

            <!-- Generate Button -->
            <div>
                <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-xs transition-colors h-11 min-h-[44px]">
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    {{ __('Hasilkan Laporan') }}
                </button>
            </div>
        </form>
    </div>

    <!-- Summary KPI Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-2xs">
            <span class="text-xs sm:text-[13px] font-medium text-slate-500">{{ __('Total Generasi') }}</span>
            <div class="mt-2 flex items-baseline space-x-1.5">
                <span class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900 font-mono">
                    {{ app()->getLocale() === 'id' ? formatNumberId($summary['total_energy_kwh'], 2) : number_format($summary['total_energy_kwh'], 2) }}
                </span>
                <span class="text-xs sm:text-sm font-semibold text-slate-500">kWh</span>
            </div>
            <div class="mt-1 text-xs text-slate-400">{{ __('Agregat Hasil Panen DC') }}</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-2xs">
            <span class="text-xs sm:text-[13px] font-medium text-slate-500">{{ __('Daya Puncak Array') }}</span>
            <div class="mt-2 flex items-baseline space-x-1.5">
                <span class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900 font-mono">
                    {{ app()->getLocale() === 'id' ? formatPowerW($summary['avg_peak_power_w'] ?? 0) : number_format($summary['avg_peak_power_w'] ?? 0, 1) . ' W' }}
                </span>
            </div>
            <div class="mt-1 text-xs text-slate-400">{{ __('Output puncak terdaftar') }}</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-2xs">
            <span class="text-xs sm:text-[13px] font-medium text-slate-500">{{ __('Ketersediaan Operasi') }}</span>
            <div class="mt-2 flex items-baseline space-x-1.5">
                <span class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900 font-mono">
                    {{ app()->getLocale() === 'id' ? formatNumberId($summary['avg_uptime_percent'], 1) : number_format($summary['avg_uptime_percent'], 1) }}
                </span>
                <span class="text-xs sm:text-sm font-semibold text-slate-500">%</span>
            </div>
            <div class="mt-1 text-xs text-emerald-600 font-medium">{{ __('Rata-rata waktu aktif') }}</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-2xs">
            <span class="text-xs sm:text-[13px] font-medium text-slate-500">{{ __('Peringatan Dipicu') }}</span>
            <div class="mt-2 flex items-baseline space-x-1.5">
                <span class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900 font-mono">{{ $summary['total_alerts'] }}</span>
                <span class="text-xs sm:text-sm font-semibold text-slate-500">{{ __('kejadian') }}</span>
            </div>
            <div class="mt-1 text-xs {{ $summary['total_alerts'] > 0 ? 'text-amber-600' : 'text-slate-400' }}">{{ __('Keamanan & perangkat keras') }}</div>
        </div>
    </div>

    <!-- Report Table -->
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-2xs overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-base font-bold text-slate-900">
                {{ __('Pernyataan Kinerja') }}: {{ $reportType === 'daily' ? __('Hari') . ' ' . $selectedDate : __('Bulan') . ' ' . $selectedMonth }}
            </h3>
            <span class="text-sm text-slate-500 font-mono">{{ __('Total Unit') }}: {{ count($rows) }}</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50/80 text-slate-500 font-semibold border-b border-slate-100 text-xs uppercase">
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
                        <th class="py-3 px-4">{{ __('Peringatan') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-mono text-xs sm:text-sm">
                    @forelse($rows as $row)
                        <tr class="hover:bg-slate-50/60 transition-colors h-[52px]">
                            <td class="py-3 px-4 font-sans font-medium text-slate-800 whitespace-nowrap">{{ $row['period_label'] }}</td>
                            <td class="py-3 px-4 font-sans">
                                <div class="font-bold text-slate-900">{{ $row['device_name'] }}</div>
                                <div class="text-xs text-slate-400 font-mono">{{ $row['device_uid'] }}</div>
                            </td>
                            <td class="py-3 px-4 font-sans text-slate-600">{{ $row['site_name'] }}</td>
                            <td class="py-3 px-4 font-bold text-blue-600">
                                {{ app()->getLocale() === 'id' ? formatNumberId($row['energy_kwh'], 2) : number_format($row['energy_kwh'], 2) }} kWh
                            </td>
                            <td class="py-3 px-4 font-sans">
                                <div class="flex items-center space-x-2">
                                    <div class="w-16 bg-slate-100 rounded-full h-2 overflow-hidden">
                                        <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $row['progress_percent'] }}%"></div>
                                    </div>
                                    <span class="text-xs font-mono font-bold text-slate-700">{{ app()->getLocale() === 'id' ? formatNumberId($row['progress_percent'], 1) : number_format($row['progress_percent'], 1) }}%</span>
                                </div>
                            </td>
                            <td class="py-3 px-4 text-slate-700 font-medium">
                                {{ app()->getLocale() === 'id' ? formatPowerW($row['peak_power_w'] ?? 0) : number_format($row['peak_power_w'] ?? 0, 1) . ' W' }}
                            </td>
                            <td class="py-3 px-4 text-slate-500 font-medium">
                                {{ app()->getLocale() === 'id' ? formatPowerW($row['avg_power_w'] ?? 0) : number_format($row['avg_power_w'] ?? 0, 1) . ' W' }}
                            </td>
                            <td class="py-3 px-4 text-slate-700">
                                {{ app()->getLocale() === 'id' ? formatNumberId($row['max_wind_ms'], 1) : number_format($row['max_wind_ms'], 1) }} m/s
                            </td>
                            <td class="py-3 px-4 font-sans">
                                <span class="px-2 py-0.5 rounded text-xs font-bold font-mono {{ $row['uptime_percent'] >= 90 ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                                    {{ app()->getLocale() === 'id' ? formatNumberId($row['uptime_percent'], 1) : number_format($row['uptime_percent'], 1) }}%
                                </span>
                            </td>
                            <td class="py-3 px-4 font-sans">
                                <span class="px-2 py-0.5 rounded text-xs font-bold font-mono {{ $row['alerts_count'] > 0 ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $row['alerts_count'] }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="p-8 text-center text-slate-400 font-sans text-sm">
                                {{ __('Tidak ada data ditemukan yang cocok dengan kriteria laporan yang ditentukan.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.app>
