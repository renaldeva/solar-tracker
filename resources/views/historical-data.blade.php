<x-layouts.app :title="__('Data Historis')">
    <!-- Page Header -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-bold uppercase tracking-wider bg-slate-100 text-slate-700 border border-slate-200">
                    {{ __('Mesin Arsip') }}
                </span>
                <span class="text-sm font-medium text-slate-400">{{ __('Agregasi 2 Tahun & Kueri Rentang') }}</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900 mt-1">{{ __('Historical Telemetry & Analytics') }}</h1>
            <p class="text-sm text-slate-500 mt-1">{{ __('Filter rentang waktu, analisis tren generasi historis, dan ekspor spreadsheet CSV.') }}</p>
        </div>

        <div class="flex items-center space-x-2">
            <a href="{{ route('historical-data.export', request()->all()) }}" class="inline-flex items-center px-4 py-2.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-xs transition-colors h-11 min-h-[44px]">
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                {{ __('Ekspor Kumpulan Data CSV') }}
            </a>
        </div>
    </div>

    <!-- Query Filter Form -->
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-2xs p-6 mb-6">
        <form method="GET" action="{{ route('historical-data') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
            <!-- Device -->
            <div>
                <label for="hist-device" class="block text-sm font-semibold text-slate-700 mb-1.5">{{ __('Perangkat Pelacak') }}</label>
                <select id="hist-device" name="device" class="w-full text-sm font-medium bg-slate-50 border border-slate-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-blue-500 h-11 min-h-[44px]">
                    @foreach($devices as $dev)
                        <option value="{{ $dev->uid }}" {{ $device && $device->uid === $dev->uid ? 'selected' : '' }}>
                            {{ $dev->name }} ({{ $dev->uid }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Granularity -->
            <div>
                <label for="hist-granularity" class="block text-sm font-semibold text-slate-700 mb-1.5">{{ __('Resolusi Data') }}</label>
                <select id="hist-granularity" name="granularity" class="w-full text-sm font-medium bg-slate-50 border border-slate-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-blue-500 h-11 min-h-[44px]">
                    <option value="hourly" {{ $granularity === 'hourly' ? 'selected' : '' }}>{{ __('Ringkasan Per Jam (telemetry_hourly)') }}</option>
                    <option value="raw" {{ $granularity === 'raw' ? 'selected' : '' }}>{{ __('Telemetri Mentah 5 Detik') }}</option>
                </select>
            </div>

            <!-- Start Date -->
            <div>
                <label for="hist-start-date" class="block text-sm font-semibold text-slate-700 mb-1.5">{{ __('Tanggal Mulai') }}</label>
                <input type="date" id="hist-start-date" name="start_date" value="{{ $startDate }}" class="w-full text-sm font-medium bg-slate-50 border border-slate-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-blue-500 font-mono h-11 min-h-[44px]">
            </div>

            <!-- End Date -->
            <div>
                <label for="hist-end-date" class="block text-sm font-semibold text-slate-700 mb-1.5">{{ __('Tanggal Selesai') }}</label>
                <input type="date" id="hist-end-date" name="end_date" value="{{ $endDate }}" class="w-full text-sm font-medium bg-slate-50 border border-slate-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-blue-500 font-mono h-11 min-h-[44px]">
            </div>

            <!-- Submit Query Button -->
            <div>
                <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-xs transition-colors h-11 min-h-[44px]">
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    {{ __('Kueri Rekaman') }}
                </button>
            </div>
        </form>
    </div>

    <!-- Summary KPI Cards for Query Range -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-2xs">
            <span class="text-xs sm:text-[13px] font-medium text-slate-500">{{ __('Total Generasi') }}</span>
            <div class="mt-2 flex items-baseline space-x-1.5">
                <span class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900 font-mono">
                    {{ app()->getLocale() === 'id' ? formatNumberId($summary['total_energy_kwh'] ?? 0, 2) : number_format($summary['total_energy_kwh'] ?? 0, 2) }}
                </span>
                <span class="text-xs sm:text-sm font-semibold text-slate-500">kWh</span>
            </div>
            <div class="mt-1 text-xs text-slate-400">{{ __('Jumlah dalam rentang') }}</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-2xs">
            <span class="text-xs sm:text-[13px] font-medium text-slate-500">{{ __('Daya DC Puncak') }}</span>
            <div class="mt-2 flex items-baseline space-x-1.5">
                <span class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900 font-mono">
                    {{ app()->getLocale() === 'id' ? formatPowerW($summary['peak_power_w'] ?? 0) : number_format($summary['peak_power_w'] ?? 0, 1) . ' W' }}
                </span>
            </div>
            <div class="mt-1 text-xs text-slate-400">{{ __('Maks PZEM-017') }}</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-2xs">
            <span class="text-xs sm:text-[13px] font-medium text-slate-500">{{ __('Daya Rata-rata') }}</span>
            <div class="mt-2 flex items-baseline space-x-1.5">
                <span class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900 font-mono">
                    {{ app()->getLocale() === 'id' ? formatPowerW($summary['avg_power_w'] ?? 0) : number_format($summary['avg_power_w'] ?? 0, 1) . ' W' }}
                </span>
            </div>
            <div class="mt-1 text-xs text-slate-400">{{ __('Rata-rata Periode') }}</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-2xs">
            <span class="text-xs sm:text-[13px] font-medium text-slate-500">{{ __('Kecepatan Angin Maks') }}</span>
            <div class="mt-2 flex items-baseline space-x-1.5">
                <span class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900 font-mono">
                    {{ app()->getLocale() === 'id' ? formatNumberId($summary['max_wind_ms'] ?? 0, 1) : number_format($summary['max_wind_ms'] ?? 0, 1) }}
                </span>
                <span class="text-xs sm:text-sm font-semibold text-slate-500">m/s</span>
            </div>
            <div class="mt-1 text-xs text-slate-400">{{ __('Batas: 15,0 m/s') }}</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-2xs">
            <span class="text-xs sm:text-[13px] font-medium text-slate-500">{{ __('Sampel Rekaman') }}</span>
            <div class="mt-2 flex items-baseline space-x-1.5">
                <span class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900 font-mono">
                    {{ app()->getLocale() === 'id' ? formatNumberId($summary['samples_count'] ?? 0) : number_format($summary['samples_count'] ?? 0) }}
                </span>
                <span class="text-xs sm:text-sm font-semibold text-slate-500">pts</span>
            </div>
            <div class="mt-1 text-xs text-emerald-600 font-medium font-mono">
                {{ isset($summary['avg_uptime_percent']) ? (app()->getLocale() === 'id' ? formatNumberId($summary['avg_uptime_percent'], 1) : number_format($summary['avg_uptime_percent'], 1)) . '% ' . __('Waktu Operasional') : __('Aliran mentah') }}
            </div>
        </div>
    </div>

    <!-- Historical Data Table -->
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-2xs overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-base font-bold text-slate-900">
                {{ __('Hasil Kueri Historis') }} ({{ $granularity === 'raw' ? __('Telemetri Mentah 5 Detik') : __('Agregat Per Jam') }})
            </h3>
            <span class="text-sm text-slate-500 font-mono">{{ __('Zona Waktu: UTC+7 (WIB)') }}</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                @if($granularity === 'raw')
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
                            <th class="py-3 px-4">{{ __('Suhu') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-mono text-xs sm:text-sm">
                        @forelse($records as $row)
                            <tr class="hover:bg-slate-50/60 transition-colors h-[52px]">
                                <td class="py-3 px-4 font-sans font-medium text-slate-800 whitespace-nowrap">
                                    {{ $row->recorded_at ? $row->recorded_at->setTimezone('Asia/Jakarta')->translatedFormat('d M Y, H:i:s') . ' WIB' : '--' }}
                                </td>
                                <td class="py-3 px-4 font-sans text-xs font-bold">
                                    <span class="px-2 py-0.5 rounded font-bold uppercase
                                        {{ $row->mode === 'auto' ? 'bg-blue-50 text-blue-700' : ($row->mode === 'stow' ? 'bg-red-50 text-red-700' : 'bg-slate-100 text-slate-700') }}">
                                        {{ match(strtolower($row->mode)) { 'auto' => __('Otomatis'), 'manual' => __('Manual'), 'stow' => __('Mode Aman (Stow)'), default => strtoupper($row->mode) } }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-slate-700">{{ app()->getLocale() === 'id' ? formatNumberId($row->voltage, 1) : number_format($row->voltage, 1) }} V</td>
                                <td class="py-3 px-4 text-slate-700">{{ app()->getLocale() === 'id' ? formatNumberId($row->current, 2) : number_format($row->current, 2) }} A</td>
                                <td class="py-3 px-4 font-bold text-blue-600">{{ app()->getLocale() === 'id' ? formatPowerW($row->power) : number_format($row->power, 1) . ' W' }}</td>
                                <td class="py-3 px-4 text-slate-500">{{ app()->getLocale() === 'id' ? formatNumberId($row->energy_wh, 0) : number_format($row->energy_wh, 0) }} Wh</td>
                                <td class="py-3 px-4 text-slate-700">{{ app()->getLocale() === 'id' ? formatNumberId($row->wind_speed, 1) : number_format($row->wind_speed, 1) }} m/s</td>
                                <td class="py-3 px-4 text-slate-700">{{ app()->getLocale() === 'id' ? formatNumberId($row->pitch_elev, 1) : number_format($row->pitch_elev, 1) }}&deg;</td>
                                <td class="py-3 px-4 text-slate-700">{{ app()->getLocale() === 'id' ? formatNumberId($row->azimuth, 1) : number_format($row->azimuth, 1) }}&deg;</td>
                                <td class="py-3 px-4 text-slate-700">{{ app()->getLocale() === 'id' ? formatNumberId($row->temp_c, 1) : number_format($row->temp_c, 1) }}&deg;C</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="p-8 text-center text-slate-400 font-sans text-sm">
                                    {{ __('Tidak ada data ditemukan untuk parameter yang dipilih.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                @else
                    <thead class="bg-slate-50/80 text-slate-500 font-semibold border-b border-slate-100 text-xs uppercase">
                        <tr>
                            <th class="py-3 px-4">{{ __('Agregat Jam (WIB)') }}</th>
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
                    <tbody class="divide-y divide-slate-100 font-mono text-xs sm:text-sm">
                        @forelse($records as $row)
                            <tr class="hover:bg-slate-50/60 transition-colors h-[52px]">
                                <td class="py-3 px-4 font-sans font-medium text-slate-800 whitespace-nowrap">
                                    {{ $row->hour_bucket ? $row->hour_bucket->setTimezone('Asia/Jakarta')->translatedFormat('d M Y, H:00') . ' WIB' : '--' }}
                                </td>
                                <td class="py-3 px-4 font-bold text-blue-600">{{ app()->getLocale() === 'id' ? formatPowerW($row->avg_power_w) : number_format($row->avg_power_w, 1) . ' W' }}</td>
                                <td class="py-3 px-4 text-slate-700">{{ app()->getLocale() === 'id' ? formatPowerW($row->max_power_w) : number_format($row->max_power_w, 1) . ' W' }}</td>
                                <td class="py-3 px-4 text-slate-500">{{ app()->getLocale() === 'id' ? formatPowerW($row->min_power_w) : number_format($row->min_power_w, 1) . ' W' }}</td>
                                <td class="py-3 px-4 font-bold text-slate-900">
                                    {{ app()->getLocale() === 'id' ? formatNumberId($row->energy_delta_wh / 1000.0, 3) : number_format($row->energy_delta_wh / 1000.0, 3) }} kWh
                                    <span class="text-xs text-slate-400 font-normal">({{ app()->getLocale() === 'id' ? formatNumberId($row->energy_delta_wh, 0) : number_format($row->energy_delta_wh, 0) }} Wh)</span>
                                </td>
                                <td class="py-3 px-4 text-slate-700">{{ app()->getLocale() === 'id' ? formatNumberId($row->max_wind_ms, 1) : number_format($row->max_wind_ms, 1) }} m/s</td>
                                <td class="py-3 px-4 text-slate-500">{{ app()->getLocale() === 'id' ? formatNumberId($row->avg_wind_ms, 1) : number_format($row->avg_wind_ms, 1) }} m/s</td>
                                <td class="py-3 px-4 text-slate-600">{{ $row->samples_count }}</td>
                                <td class="py-3 px-4">
                                    <span class="px-2 py-0.5 rounded text-xs font-bold font-sans {{ $row->uptime_percent >= 90 ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                                        {{ app()->getLocale() === 'id' ? formatNumberId($row->uptime_percent, 1) : number_format($row->uptime_percent, 1) }}%
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="p-8 text-center text-slate-400 font-sans text-sm">
                                    {{ __('Tidak ada data agregat ditemukan. Coba jalankan tracker:aggregate-hourly atau perluas rentang tanggal.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                @endif
            </table>
        </div>

        @if($records->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $records->links() }}
            </div>
        @endif
    </div>
</x-layouts.app>
