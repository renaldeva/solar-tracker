{{-- Recent Telemetry History Log Table (10 Rows) --}}
<x-card :title="__('Recent Telemetry Event Log')" :subtitle="__('10 paket telemetri konsekutif terakhir dari :uid', ['uid' => $device->uid])">
    <div class="overflow-x-auto -mx-4 -my-4 p-4">
        <table class="min-w-full divide-y divide-line text-xs font-mono">
            <thead>
                <tr class="text-left uppercase text-muted tracking-wider">
                    <th class="py-2 pr-3 font-semibold">{{ __('Waktu (WIB)') }}</th>
                    <th class="py-2 px-3 font-semibold">{{ __('Elevasi Pitch') }}</th>
                    <th class="py-2 px-3 font-semibold">{{ __('Azimut Heading') }}</th>
                    <th class="py-2 px-3 font-semibold">{{ __('Tegangan & Daya DC') }}</th>
                    <th class="py-2 px-3 font-semibold">{{ __('Aktuator') }}</th>
                    <th class="py-2 px-3 font-semibold">{{ __('Angin') }}</th>
                    <th class="py-2 pl-3 font-semibold">{{ __('IMU S/G/A/M') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line text-ink">
                @forelse($recentTelemetries as $log)
                    <tr class="hover:bg-bg/40 transition-colors">
                        <td class="py-2.5 pr-3 text-muted whitespace-nowrap">
                            {{ formatTimeWib($log->recorded_at) }}
                        </td>
                        <td class="py-2.5 px-3 text-accent font-semibold whitespace-nowrap">
                            {{ formatNumberId($log->pitch_elev, 1) }}&deg;
                            <span class="text-muted font-normal">/ {{ formatNumberId($log->target_elev, 1) }}&deg;</span>
                        </td>
                        <td class="py-2.5 px-3 text-accent font-semibold whitespace-nowrap">
                            {{ formatNumberId($log->azimuth, 1) }}&deg;
                            <span class="text-muted font-normal">/ {{ formatNumberId($log->target_az, 1) }}&deg;</span>
                        </td>
                        <td class="py-2.5 px-3 text-ink font-semibold whitespace-nowrap">
                            {{ formatNumberId($log->voltage, 1) }} V &middot; <span class="text-accent">{{ formatPowerW($log->power) }}</span>
                        </td>
                        <td class="py-2.5 px-3 whitespace-nowrap">
                            <span class="inline-block px-1.5 py-0.5 rounded border border-line bg-bg text-muted uppercase text-[11px]">
                                {{ $log->elev_state }}/{{ $log->az_state }}
                            </span>
                        </td>
                        <td class="py-2.5 px-3 text-ink whitespace-nowrap">
                            {{ formatNumberId($log->wind_speed, 1) }} m/s
                        </td>
                        <td class="py-2.5 pl-3 text-muted whitespace-nowrap">
                            {{ $log->cal_sys }}/{{ $log->cal_gyro }}/{{ $log->cal_accel }}/{{ $log->cal_mag }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-6 text-center text-muted font-sans">
                            {{ __('Belum ada paket telemetri tercatat.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-card>

