<x-layouts.app :title="__('Data Historis')">
    <x-page-header 
        :title="__('Historical Telemetry & Analytics')" 
        :subtitle="__('Filter rentang waktu, analisis tren generasi historis, dan ekspor spreadsheet CSV.')"
        :badge="__('Mesin Arsip')"
    >
        <x-slot:actions>
            <div class="flex items-center space-x-2">
                <a 
                    href="{{ route('historical-data.export', request()->all()) }}" 
                    class="inline-flex items-center h-10 px-4 rounded-xl border border-line bg-panel hover:bg-slate-50 text-ink text-sm font-medium transition-colors shadow-xs"
                    title="{{ __('Ekspor Kumpulan Data CSV') }}"
                >
                    <svg class="w-4 h-4 mr-2 text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>{{ __('Ekspor Kumpulan Data CSV') }}</span>
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
                    Arsip Data Historis Siap & Terverifikasi
                </h2>
                <p class="text-xs text-muted mt-0.5">
                    Menampilkan rekaman untuk perangkat <strong class="text-ink">{{ $device->name ?? 'Semua Unit' }}</strong> ({{ $granularity === 'raw' ? 'Resolusi mentah 5 detik' : ($granularity === 'daily' ? 'Agregat harian' : 'Agregat per jam') }}).
                </p>
            </div>
        </div>
        <div class="hidden sm:flex items-center space-x-2">
            <span class="px-3 py-1 rounded-xl text-xs font-mono font-semibold bg-panel-alt text-ink border border-line">
                {{ formatNumberId($summary['samples_count'] ?? 0) }} Data Poin
            </span>
        </div>
    </div>

    <!-- Query Filter Form Card (Smart Nursery Style) -->
    <div class="bg-panel border border-line rounded-2xl p-4 sm:p-5 mb-6 shadow-xs">
        <form method="GET" action="{{ route('historical-data') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5 items-end">
            <!-- Device -->
            <div>
                <label for="hist-device" class="block text-xs font-semibold text-muted uppercase tracking-wider mb-1.5">{{ __('Perangkat Pelacak') }}</label>
                <div class="relative">
                    <select id="hist-device" name="device" class="appearance-none w-full text-xs font-semibold bg-panel border border-line rounded-xl pl-3 pr-8 py-2.5 text-ink focus:outline-none focus:border-blue-500 shadow-2xs">
                        @foreach($devices as $dev)
                            <option value="{{ $dev->uid }}" {{ $device && $device->uid === $dev->uid ? 'selected' : '' }}>
                                {{ $dev->name }} ({{ $dev->uid }})
                            </option>
                        @endforeach
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2.5 text-muted">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Granularity -->
            <div>
                <label for="hist-granularity" class="block text-xs font-semibold text-muted uppercase tracking-wider mb-1.5">{{ __('Resolusi Data') }}</label>
                <div class="relative">
                    <select id="hist-granularity" name="granularity" class="appearance-none w-full text-xs font-semibold bg-panel border border-line rounded-xl pl-3 pr-8 py-2.5 text-ink focus:outline-none focus:border-blue-500 shadow-2xs">
                        <option value="hourly" {{ $granularity === 'hourly' ? 'selected' : '' }}>{{ __('Ringkasan Per Jam (Hourly)') }}</option>
                        <option value="daily" {{ $granularity === 'daily' ? 'selected' : '' }}>{{ __('Ringkasan Harian (Daily)') }}</option>
                        <option value="raw" {{ $granularity === 'raw' ? 'selected' : '' }}>{{ __('Telemetri Mentah 5s (Raw)') }}</option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2.5 text-muted">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Start Date -->
            <div>
                <label for="hist-start-date" class="block text-xs font-semibold text-muted uppercase tracking-wider mb-1.5">{{ __('Tanggal Mulai') }}</label>
                <input type="date" id="hist-start-date" name="start_date" value="{{ $startDate }}" class="w-full text-xs font-mono bg-panel border border-line rounded-xl px-3 py-2 text-ink focus:outline-none focus:border-blue-500 shadow-2xs">
            </div>

            <!-- End Date -->
            <div>
                <label for="hist-end-date" class="block text-xs font-semibold text-muted uppercase tracking-wider mb-1.5">{{ __('Tanggal Selesai') }}</label>
                <input type="date" id="hist-end-date" name="end_date" value="{{ $endDate }}" class="w-full text-xs font-mono bg-panel border border-line rounded-xl px-3 py-2 text-ink focus:outline-none focus:border-blue-500 shadow-2xs">
            </div>

            <!-- Submit Query Button -->
            <div>
                <button type="submit" class="w-full inline-flex items-center justify-center h-10 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold transition-colors shadow-xs cursor-pointer">
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <span>{{ __('Kueri Rekaman') }}</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Summary KPI Cards -->
    <div class="grid grid-cols-2 md:grid-cols-3 {{ ($granularity !== 'raw' && isset($summary['avg_uptime_percent'])) ? 'lg:grid-cols-6' : 'lg:grid-cols-5' }} gap-4 mb-6">
        <x-kpi-card 
            :title="__('Total Generasi')" 
            :value="app()->getLocale() === 'id' ? formatNumberId($summary['total_energy_kwh'] ?? 0, 2) : number_format($summary['total_energy_kwh'] ?? 0, 2)" 
            unit="kWh"
            tone="ok"
            :subtitle="__('Akumulasi rentang kueri')"
        />

        <x-kpi-card 
            :title="__('Daya DC Puncak')" 
            :value="app()->getLocale() === 'id' ? formatPowerW($summary['peak_power_w'] ?? 0) : number_format($summary['peak_power_w'] ?? 0, 1) . ' W'" 
            tone="accent"
            :subtitle="__('Maks PZEM-017')"
        />

        <x-kpi-card 
            :title="__('Daya Rata-rata')" 
            :value="app()->getLocale() === 'id' ? formatPowerW($summary['avg_power_w'] ?? 0) : number_format($summary['avg_power_w'] ?? 0, 1) . ' W'" 
            tone="accent"
            :subtitle="__('Rata-rata periode')"
        />

        <x-kpi-card 
            :title="__('Kecepatan Angin Maks')" 
            :value="app()->getLocale() === 'id' ? formatNumberId($summary['max_wind_ms'] ?? 0, 1) : number_format($summary['max_wind_ms'] ?? 0, 1)" 
            unit="m/s"
            :tone="($summary['max_wind_ms'] ?? 0) >= 15.0 ? 'danger' : 'default'"
            :subtitle="__('Batas aman: 15,0 m/s')"
        />

        <x-kpi-card 
            :title="__('Sampel Rekaman')" 
            :value="app()->getLocale() === 'id' ? formatNumberId($summary['samples_count'] ?? 0, 0) : number_format($summary['samples_count'] ?? 0)" 
            unit="pts"
            tone="neutral"
            :subtitle="__('Total titik data terekam')"
        />

        @if($granularity !== 'raw' && isset($summary['avg_uptime_percent']))
            <x-kpi-card 
                :title="__('Rata-rata Uptime')" 
                :value="app()->getLocale() === 'id' ? formatNumberId($summary['avg_uptime_percent'], 1) : number_format($summary['avg_uptime_percent'], 1)" 
                unit="%"
                :tone="$summary['avg_uptime_percent'] >= 90 ? 'ok' : 'warn'"
                :badge="$summary['avg_uptime_percent'] >= 90 ? 'NOMINAL' : 'DEGRADASI'"
                :subtitle="__('Ketersediaan node tracker')"
            />
        @endif
    </div>

    <!-- Historical Data Table Card -->
    <div class="bg-panel border border-line rounded-2xl overflow-hidden mb-6 shadow-xs">
        <div class="p-4 sm:p-5 border-b border-line flex flex-col sm:flex-row sm:items-center justify-between gap-2 bg-panel-alt/60">
            <div>
                <h3 class="text-sm font-semibold text-ink">
                    {{ __('Hasil Kueri Historis') }} ({{ $granularity === 'raw' ? __('Telemetri Mentah 5 Detik') : ($granularity === 'daily' ? __('Agregat Harian') : __('Agregat Per Jam')) }})
                </h3>
                <p class="text-xs text-muted mt-0.5">{{ __('Zona Waktu: UTC+7 (WIB)') }}</p>
            </div>
            <div class="text-xs font-mono text-muted">
                {{ __('Halaman :current dari :total (:count total data)', [
                    'current' => $records->currentPage(),
                    'total' => $records->lastPage(),
                    'count' => $records->total()
                ]) }}
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                @if($granularity === 'raw')
                    <thead class="bg-panel-alt text-muted uppercase text-[11px] font-semibold tracking-wider border-b border-line">
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
                            <th class="py-3 px-4">{{ __('Suhu') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line font-mono text-xs">
                        @forelse($records as $row)
                            <tr class="hover:bg-panel-alt/50 transition-colors">
                                <td class="py-3 px-4 text-ink whitespace-nowrap">
                                    {{ $row->recorded_at ? $row->recorded_at->setTimezone('Asia/Jakarta')->translatedFormat('d M Y, H:i:s') . ' WIB' : '--' }}
                                </td>
                                <td class="py-3 px-4">
                                    <span class="px-2 py-0.5 rounded-md text-[11px] font-mono uppercase border border-line
                                        {{ $row->mode === 'auto' ? 'bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 border-blue-200 dark:border-blue-800/60' : ($row->mode === 'stow' ? 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border-rose-200 dark:border-rose-800/60' : 'bg-panel-alt text-muted') }}">
                                        {{ match(strtolower($row->mode)) { 'auto' => __('Otomatis'), 'manual' => __('Manual'), 'stow' => __('Stow'), default => strtoupper($row->mode) } }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-ink">{{ app()->getLocale() === 'id' ? formatNumberId($row->voltage, 1) : number_format($row->voltage, 1) }} V</td>
                                <td class="py-3 px-4 text-ink">{{ app()->getLocale() === 'id' ? formatNumberId($row->current, 2) : number_format($row->current, 2) }} A</td>
                                <td class="py-3 px-4 font-bold text-blue-600 dark:text-blue-400">{{ app()->getLocale() === 'id' ? formatPowerW($row->power) : number_format($row->power, 1) . ' W' }}</td>
                                <td class="py-3 px-4 text-muted">{{ app()->getLocale() === 'id' ? formatNumberId($row->energy_wh, 0) : number_format($row->energy_wh, 0) }} Wh</td>
                                <td class="py-3 px-4 {{ $row->wind_speed >= 15.0 ? 'text-rose-600 dark:text-rose-400 font-bold' : 'text-ink' }}">{{ app()->getLocale() === 'id' ? formatNumberId($row->wind_speed, 1) : number_format($row->wind_speed, 1) }} m/s</td>
                                <td class="py-3 px-4 text-ink">{{ app()->getLocale() === 'id' ? formatNumberId($row->pitch_elev, 1) : number_format($row->pitch_elev, 1) }}&deg;</td>
                                <td class="py-3 px-4 text-ink">{{ app()->getLocale() === 'id' ? formatNumberId($row->azimuth, 1) : number_format($row->azimuth, 1) }}&deg;</td>
                                <td class="py-3 px-4 text-ink">{{ app()->getLocale() === 'id' ? formatNumberId($row->temp_c, 1) : number_format($row->temp_c, 1) }}&deg;C</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="p-8 text-center text-muted font-sans text-sm">
                                    {{ __('Tidak ada data ditemukan untuk parameter yang dipilih.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                @else
                    <thead class="bg-panel-alt text-muted uppercase text-[11px] font-semibold tracking-wider border-b border-line">
                        <tr>
                            <th class="py-3 px-4">{{ __('Periode (WIB)') }}</th>
                            <th class="py-3 px-4">{{ __('Daya Rata-rata') }}</th>
                            <th class="py-3 px-4">{{ __('Daya Puncak') }}</th>
                            <th class="py-3 px-4">{{ __('Daya Min') }}</th>
                            <th class="py-3 px-4">{{ __('Delta Energi') }}</th>
                            <th class="py-3 px-4">{{ __('Angin Maks') }}</th>
                            <th class="py-3 px-4">{{ __('Angin Rata-rata') }}</th>
                            <th class="py-3 px-4">{{ __('Sampel') }}</th>
                            <th class="py-3 px-4">{{ __('Waktu Operasional') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line font-mono text-xs">
                        @forelse($records as $row)
                            <tr class="hover:bg-panel-alt/50 transition-colors">
                                <td class="py-3 px-4 text-ink whitespace-nowrap">
                                    {{ $row->hour_bucket ? $row->hour_bucket->setTimezone('Asia/Jakarta')->translatedFormat('d M Y, H:00') . ' WIB' : '--' }}
                                </td>
                                <td class="py-3 px-4 font-bold text-blue-600 dark:text-blue-400">{{ app()->getLocale() === 'id' ? formatPowerW($row->avg_power_w) : number_format($row->avg_power_w, 1) . ' W' }}</td>
                                <td class="py-3 px-4 text-ink">{{ app()->getLocale() === 'id' ? formatPowerW($row->max_power_w) : number_format($row->max_power_w, 1) . ' W' }}</td>
                                <td class="py-3 px-4 text-muted">{{ app()->getLocale() === 'id' ? formatPowerW($row->min_power_w) : number_format($row->min_power_w, 1) . ' W' }}</td>
                                <td class="py-3 px-4 font-bold text-ink">
                                    {{ app()->getLocale() === 'id' ? formatNumberId($row->energy_delta_wh / 1000.0, 3) : number_format($row->energy_delta_wh / 1000.0, 3) }} kWh
                                    <span class="text-[11px] text-muted font-normal">({{ app()->getLocale() === 'id' ? formatNumberId($row->energy_delta_wh, 0) : number_format($row->energy_delta_wh, 0) }} Wh)</span>
                                </td>
                                <td class="py-3 px-4 text-ink">{{ app()->getLocale() === 'id' ? formatNumberId($row->max_wind_ms, 1) : number_format($row->max_wind_ms, 1) }} m/s</td>
                                <td class="py-3 px-4 text-muted">{{ app()->getLocale() === 'id' ? formatNumberId($row->avg_wind_ms, 1) : number_format($row->avg_wind_ms, 1) }} m/s</td>
                                <td class="py-3 px-4 text-muted">{{ $row->samples_count }}</td>
                                <td class="py-3 px-4">
                                    <span class="inline-flex items-center space-x-1 font-mono text-xs {{ $row->uptime_percent >= 90 ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $row->uptime_percent >= 90 ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                                        <span class="font-semibold">{{ app()->getLocale() === 'id' ? formatNumberId($row->uptime_percent, 1) : number_format($row->uptime_percent, 1) }}%</span>
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="p-8 text-center text-muted font-sans text-sm">
                                    {{ __('Tidak ada data agregat ditemukan. Coba jalankan tracker:aggregate-hourly atau perluas rentang tanggal.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                @endif
            </table>
        </div>

        @if($records->hasPages())
            <div class="p-4 border-t border-line bg-panel-alt/60">
                {{ $records->links() }}
            </div>
        @endif
    </div>
</x-layouts.app>
