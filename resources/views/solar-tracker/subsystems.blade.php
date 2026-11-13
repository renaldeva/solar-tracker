{{-- Hardware Subsystems: BNO055 IMU Calibration & GPS M8N / RTC DS3231 Diagnostics --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- 1. BNO055 IMU Calibration Matrix -->
    <x-card :title="__('BNO055 IMU Calibration Matrix')" :subtitle="__('Tingkat kalibrasi orientasi sensor 9-DOF (0 = Belum Terkalibrasi, 3 = Penuh/Optimal)')">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            @php
                $matrix = [
                    ['name' => __('Sistem'), 'val' => $telemetry?->cal_sys ?? 0],
                    ['name' => __('Giroskop'), 'val' => $telemetry?->cal_gyro ?? 0],
                    ['name' => __('Akselerometer'), 'val' => $telemetry?->cal_accel ?? 0],
                    ['name' => __('Magnetometer'), 'val' => $telemetry?->cal_mag ?? 0],
                ];
            @endphp

            @foreach($matrix as $item)
                @php
                    $val = (int) $item['val'];
                    $cal = $calLabels[$val] ?? $calLabels[0];
                    $barColor = match($val) {
                        3 => 'bg-emerald-500',
                        2 => 'bg-blue-600',
                        1 => 'bg-amber-500',
                        default => 'bg-slate-200',
                    };
                @endphp
                <div class="p-3.5 rounded-xl border border-line bg-slate-50/60 text-center">
                    <div class="text-[11px] font-semibold text-muted uppercase">{{ $item['name'] }}</div>
                    <div class="text-xl font-bold font-mono text-ink mt-1">{{ $val }} / 3</div>
                    <div class="text-xs font-semibold mt-0.5 {{ $val === 3 ? 'text-emerald-700' : ($val > 0 ? 'text-amber-700' : 'text-slate-500') }}">
                        {{ __($cal['label']) }}
                    </div>
                    
                    <!-- 3-bar level indicator -->
                    <div class="flex items-center justify-center gap-1.5 mt-2.5">
                        <span class="h-1.5 w-4 rounded-full {{ $val >= 1 ? $barColor : 'bg-slate-200' }}"></span>
                        <span class="h-1.5 w-4 rounded-full {{ $val >= 2 ? $barColor : 'bg-slate-200' }}"></span>
                        <span class="h-1.5 w-4 rounded-full {{ $val >= 3 ? $barColor : 'bg-slate-200' }}"></span>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="text-xs text-muted mt-3 pt-3 border-t border-line flex items-center justify-between">
            <span>Sudut Roll: <strong class="text-ink font-mono">{{ formatNumberId($telemetry?->roll ?? 0, 1) }}&deg;</strong></span>
            <span>Offset Heading: <strong class="text-ink font-mono">{{ formatNumberId($device->settings['heading_offset'] ?? 0, 1) }}&deg;</strong></span>
        </div>
    </x-card>

    <!-- 2. GPS M8N & RTC DS3231 Diagnostics -->
    <x-card :title="__('GPS & Sinkronisasi Waktu')" :subtitle="__('Koordinat geografis lokasi array & sinkronisasi jam RTC DS3231')">
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-xs">
            <div class="p-3 rounded-xl border border-line bg-slate-50/60">
                <div class="text-[10px] font-semibold text-muted uppercase tracking-wider">{{ __('Status GPS') }}</div>
                <div class="font-bold text-ink mt-1 flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full {{ ($telemetry?->fix ?? false) ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                    <span>{{ ($telemetry?->fix ?? false) ? '3D GPS Fix' : 'No Fix' }}</span>
                </div>
            </div>

            <div class="p-3 rounded-xl border border-line bg-slate-50/60">
                <div class="text-[10px] font-semibold text-muted uppercase tracking-wider">{{ __('Satelit Terdeteksi') }}</div>
                <div class="font-bold text-ink mt-1 font-mono">
                    {{ $telemetry?->satellites ?? 0 }} Sats &middot; HDOP {{ formatNumberId($telemetry?->hdop ?? 0.9, 1) }}
                </div>
            </div>

            <div class="p-3 rounded-xl border border-line bg-slate-50/60">
                <div class="text-[10px] font-semibold text-muted uppercase tracking-wider">{{ __('Jam RTC DS3231') }}</div>
                <div class="font-bold text-ink mt-1">
                    {{ __('Tersinkron') }} ({{ $telemetry?->drift_s ?? 0 }}s)
                </div>
            </div>

            <div class="p-3 rounded-xl border border-line bg-slate-50/60">
                <div class="text-[10px] font-semibold text-muted uppercase tracking-wider">{{ __('Garis Lintang (Lat)') }}</div>
                <div class="font-bold font-mono text-ink mt-1">
                    {{ $telemetry?->latitude ? formatNumberId($telemetry->latitude, 5) : '-6.29440' }}
                </div>
            </div>

            <div class="p-3 rounded-xl border border-line bg-slate-50/60">
                <div class="text-[10px] font-semibold text-muted uppercase tracking-wider">{{ __('Garis Bujur (Long)') }}</div>
                <div class="font-bold font-mono text-ink mt-1">
                    {{ $telemetry?->longitude ? formatNumberId($telemetry->longitude, 5) : '107.15390' }}
                </div>
            </div>

            <div class="p-3 rounded-xl border border-line bg-slate-50/60">
                <div class="text-[10px] font-semibold text-muted uppercase tracking-wider">{{ __('Ketinggian (Alt)') }}</div>
                <div class="font-bold font-mono text-ink mt-1">
                    {{ $telemetry?->altitude ? formatNumberId($telemetry->altitude, 1) . ' m' : '42,0 m' }}
                </div>
            </div>
        </div>

        <div class="text-xs text-muted mt-3 pt-3 border-t border-line flex items-center justify-between">
            <span>{{ __('Lokasi:') }} <strong class="text-ink">{{ $device->site?->name ?? 'Cikarang Industrial Pilot' }}</strong></span>
            <span>Zona Waktu: <strong class="text-ink">WIB (UTC+7)</strong></span>
        </div>
    </x-card>
</div>
