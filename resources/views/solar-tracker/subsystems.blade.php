{{-- Hardware Subsystems: BNO055 IMU Calibration & GPS M8N / RTC DS3231 Diagnostics --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- 1. BNO055 IMU Calibration Matrix -->
    <x-card :title="__('BNO055 IMU Calibration Matrix')" :subtitle="__('Register kalibrasi sensor orientasi absolut 9-DOF (0 = Belum Kalibrasi, 3 = Penuh)')">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            @php
                $matrix = [
                    ['name' => __('System'), 'val' => $telemetry?->cal_sys ?? 0],
                    ['name' => __('Gyroscope'), 'val' => $telemetry?->cal_gyro ?? 0],
                    ['name' => __('Accelerometer'), 'val' => $telemetry?->cal_accel ?? 0],
                    ['name' => __('Magnetometer'), 'val' => $telemetry?->cal_mag ?? 0],
                ];
            @endphp

            @foreach($matrix as $item)
                @php
                    $val = (int) $item['val'];
                    $cal = $calLabels[$val] ?? $calLabels[0];
                    $barColor = match($val) {
                        3 => 'bg-ok',
                        2 => 'bg-accent',
                        1 => 'bg-warn',
                        default => 'bg-line/40',
                    };
                @endphp
                <div class="p-3 rounded border border-line bg-bg/50 text-center font-mono">
                    <div class="text-[10px] font-semibold text-muted uppercase">{{ $item['name'] }}</div>
                    <div class="text-xl font-bold text-ink mt-1">{{ $val }} / 3</div>
                    <div class="text-[11px] font-semibold mt-0.5 {{ $val === 3 ? 'text-ok' : ($val > 0 ? 'text-warn' : 'text-muted') }}">
                        {{ __($cal['label']) }}
                    </div>
                    
                    <!-- 3-bar level indicator -->
                    <div class="flex items-center justify-center gap-1 mt-2">
                        <span class="h-1.5 w-4 rounded {{ $val >= 1 ? $barColor : 'bg-line/40' }}"></span>
                        <span class="h-1.5 w-4 rounded {{ $val >= 2 ? $barColor : 'bg-line/40' }}"></span>
                        <span class="h-1.5 w-4 rounded {{ $val >= 3 ? $barColor : 'bg-line/40' }}"></span>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="text-xs font-mono text-muted mt-3 pt-3 border-t border-line flex items-center justify-between">
            <span>Roll Angle: <strong class="text-ink">{{ formatNumberId($telemetry?->roll ?? 0, 1) }}&deg;</strong></span>
            <span>Heading Offset: <strong class="text-ink">{{ formatNumberId($device->settings['heading_offset'] ?? 0, 1) }}&deg;</strong></span>
        </div>
    </x-card>

    <!-- 2. GPS M8N & RTC DS3231 Diagnostics -->
    <x-card :title="__('GPS M8N & Time Reference')" :subtitle="__('Koordinat Solar SPA & sumber sinkronisasi RTC DS3231')">
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5 text-xs font-mono">
            <div class="p-2.5 rounded border border-line bg-bg/50">
                <div class="text-[10px] text-muted uppercase tracking-wider">{{ __('Fix Status') }}</div>
                <div class="font-bold text-ink mt-1 flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full {{ ($telemetry?->fix ?? false) ? 'bg-ok' : 'bg-danger' }}"></span>
                    <span>{{ ($telemetry?->fix ?? false) ? '3D GPS Fix' : 'No Fix' }}</span>
                </div>
            </div>

            <div class="p-2.5 rounded border border-line bg-bg/50">
                <div class="text-[10px] text-muted uppercase tracking-wider">{{ __('Satelit Terlacak') }}</div>
                <div class="font-bold text-ink mt-1">
                    {{ $telemetry?->satellites ?? 0 }} Sats &middot; HDOP {{ formatNumberId($telemetry?->hdop ?? 0.9, 1) }}
                </div>
            </div>

            <div class="p-2.5 rounded border border-line bg-bg/50">
                <div class="text-[10px] text-muted uppercase tracking-wider">{{ __('RTC DS3231') }}</div>
                <div class="font-bold text-ink mt-1">
                    {{ __('Tersinkron') }} ({{ $telemetry?->drift_s ?? 0 }}s)
                </div>
            </div>

            <div class="p-2.5 rounded border border-line bg-bg/50">
                <div class="text-[10px] text-muted uppercase tracking-wider">{{ __('Latitude') }}</div>
                <div class="font-bold text-ink mt-1">
                    {{ $telemetry?->latitude ? formatNumberId($telemetry->latitude, 5) : '-6.29440' }}
                </div>
            </div>

            <div class="p-2.5 rounded border border-line bg-bg/50">
                <div class="text-[10px] text-muted uppercase tracking-wider">{{ __('Longitude') }}</div>
                <div class="font-bold text-ink mt-1">
                    {{ $telemetry?->longitude ? formatNumberId($telemetry->longitude, 5) : '107.15390' }}
                </div>
            </div>

            <div class="p-2.5 rounded border border-line bg-bg/50">
                <div class="text-[10px] text-muted uppercase tracking-wider">{{ __('Altitude') }}</div>
                <div class="font-bold text-ink mt-1">
                    {{ $telemetry?->altitude ? formatNumberId($telemetry->altitude, 1) . ' m' : '42,0 m' }}
                </div>
            </div>
        </div>

        <div class="text-xs font-mono text-muted mt-3 pt-3 border-t border-line flex items-center justify-between">
            <span>{{ __('Site:') }} <strong class="text-ink">{{ $device->site?->name ?? 'Cikarang Industrial Pilot' }}</strong></span>
            <span>Timezone: <strong class="text-ink">WIB (UTC+7)</strong></span>
        </div>
    </x-card>
</div>

