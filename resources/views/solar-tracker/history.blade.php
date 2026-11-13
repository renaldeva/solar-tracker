{{-- Recent Telemetry History Log Table (10 Rows) --}}
<x-card :title="__('Riwayat Telemetri Terakhir')" :subtitle="__('10 paket data telemetri berurutan dari :uid', ['uid' => $device->uid])">
    <div class="overflow-x-auto -mx-5 -my-5 p-5">
        <table class="min-w-full divide-y divide-line text-xs font-sans">
            <thead>
                <tr class="text-left font-semibold text-muted uppercase tracking-wider">
                    <th class="py-2.5 pr-3">{{ __('Waktu (WIB)') }}</th>
                    <th class="py-2.5 px-3">{{ __('Elevasi') }}</th>
                    <th class="py-2.5 px-3">{{ __('Azimut') }}</th>
                    <th class="py-2.5 px-3">{{ __('Tegangan & Daya DC') }}</th>
                    <th class="py-2.5 px-3">{{ __('Status Motor') }}</th>
                    <th class="py-2.5 px-3">{{ __('Angin') }}</th>
                    <th class="py-2.5 pl-3">{{ __('Kalibrasi IMU') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line text-ink">
                @forelse($recentTelemetries as $log)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-3 pr-3 text-muted whitespace-nowrap font-mono">
                            {{ formatTimeWib($log->recorded_at) }}
                        </td>
                        <td class="py-3 px-3 font-semibold text-blue-600 whitespace-nowrap font-mono">
                            {{ formatNumberId($log->pitch_elev, 1) }}&deg;
                            <span class="text-muted font-normal text-slate-400">/ {{ formatNumberId($log->target_elev, 1) }}&deg;</span>
                        </td>
                        <td class="py-3 px-3 font-semibold text-blue-600 whitespace-nowrap font-mono">
                            {{ formatNumberId($log->azimuth, 1) }}&deg;
                            <span class="text-muted font-normal text-slate-400">/ {{ formatNumberId($log->target_az, 1) }}&deg;</span>
                        </td>
                        <td class="py-3 px-3 font-medium whitespace-nowrap font-mono">
                            {{ formatNumberId($log->voltage, 1) }} V &middot; <span class="text-blue-600 font-semibold">{{ formatPowerW($log->power) }}</span>
                        </td>
                        <td class="py-3 px-3 whitespace-nowrap">
                            <span class="inline-block px-2 py-0.5 rounded-full border border-slate-200 bg-slate-100 text-slate-700 text-[11px] font-medium capitalize">
                                {{ $log->elev_state }}/{{ $log->az_state }}
                            </span>
                        </td>
                        <td class="py-3 px-3 font-mono whitespace-nowrap">
                            {{ formatNumberId($log->wind_speed, 1) }} m/s
                        </td>
                        <td class="py-3 pl-3 text-muted font-mono whitespace-nowrap">
                            {{ $log->cal_sys }}/{{ $log->cal_gyro }}/{{ $log->cal_accel }}/{{ $log->cal_mag }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-muted font-sans">
                            {{ __('Belum ada paket telemetri tercatat.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-card>
