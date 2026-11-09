<x-layouts.app title="{{ __('Dual-Axis Solar Tracker') }}">
    <x-page-header 
        :title="__('Dual-Axis Solar Tracker')" 
        :subtitle="__('Real-time BNO055 IMU orientation, PZEM-017 DC electricals, dual BTS7960 kinematics, and sensor diagnostics.')"
        badge="{{ $device->name }}"
    >
        <x-slot:actions>
            <div class="flex items-center space-x-3">
                <!-- Device Selector -->
                <div class="relative">
                    <select 
                        onchange="window.location.href = '{{ route('solar-tracker') }}?uid=' + this.value"
                        class="h-11 min-h-[44px] text-sm font-semibold py-2 pl-3.5 pr-9 rounded-lg border border-slate-300 bg-white text-slate-700 shadow-2xs focus:ring-2 focus:ring-blue-500 focus:outline-none"
                    >
                        @foreach($devices as $d)
                            <option value="{{ $d->uid }}" {{ $d->uid === $device->uid ? 'selected' : '' }}>
                                {{ $d->name }} ({{ $d->uid }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Status Pills -->
                <x-status-pill :status="$device->status" :pulse="$device->status === 'online'" size="md" />
                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-mono font-bold uppercase tracking-wider {{ $device->mode === 'auto' ? 'bg-blue-50 text-blue-700 border border-blue-200/80' : 'bg-amber-50 text-amber-700 border border-amber-200/80' }}">
                    {{ __('Mode:') }} {{ __($device->mode) }}
                </span>
            </div>
        </x-slot:actions>
    </x-page-header>

    @if($isWindAlert)
        <div class="mb-6 p-5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 flex items-center justify-between shadow-2xs">
            <div class="flex items-center space-x-3.5">
                <span class="p-2.5 bg-rose-100 rounded-lg text-rose-600 shrink-0">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </span>
                <div>
                    <div class="font-bold text-base">{{ __('HIGH WIND SAFETY ALERT: STOW ENGAGED') }}</div>
                    <div class="text-sm text-rose-700 mt-0.5">
                        {{ __('Current wind speed is :speed m/s, exceeding safety limit of :limit m/s. Actuators interlocked in flat stow position.', ['speed' => formatNumberId($telemetry?->wind_speed ?? 0, 1), 'limit' => formatNumberId($windStowThreshold, 1)]) }}
                    </div>
                </div>
            </div>
            <span class="text-xs font-mono font-bold px-3 py-1.5 bg-rose-200/70 text-rose-800 rounded-lg shrink-0">{{ __('INTERLOCKED') }}</span>
        </div>
    @endif

    <!-- Dynamic 2-Axis Mechanical Orientation & Compass Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- 1. Elevation Pitch Plane SVG -->
        <x-card :title="__('Elevation Pitch Kinematics')" :subtitle="__('Actuator 1 linear stroke & BNO055 Pitch Angle (0° Horizon to 90° Zenith)')">
            <div class="relative bg-slate-900 rounded-xl p-5 overflow-hidden border border-slate-800 flex flex-col items-center">
                <!-- SVG Elevation Diagram -->
                <svg class="w-full h-64 max-w-md" viewBox="0 0 300 200" fill="none">
                    <!-- Ground Level & Pedestal -->
                    <line x1="20" y1="180" x2="280" y2="180" stroke="#334155" stroke-width="2" stroke-dasharray="4 4" />
                    <text x="25" y="195" fill="#64748b" font-size="10" font-family="monospace">0° HORIZON</text>
                    <text x="155" y="25" fill="#64748b" font-size="10" font-family="monospace">90° ZENITH</text>
                    
                    <!-- Protractor Reference Arc -->
                    <path d="M 230 180 A 80 80 0 0 0 150 100" stroke="#1e293b" stroke-width="1.5" stroke-dasharray="2 2" />
                    <line x1="150" y1="180" x2="150" y2="35" stroke="#334155" stroke-width="1" stroke-dasharray="2 2" />

                    <!-- Main Support Pedestal Mast -->
                    <rect x="144" y="110" width="12" height="70" rx="3" fill="#475569" stroke="#64748b" stroke-width="1.5" />
                    <polygon points="135,180 165,180 155,160 145,160" fill="#334155" />

                    <!-- Elevation Actuator 1 Cylinder (Pedestal base to panel bracket) -->
                    <line x1="130" y1="165" x2="140" y2="125" stroke="#3b82f6" stroke-width="4" stroke-linecap="round" />
                    <line x1="140" y1="125" x2="146" y2="105" stroke="#93c5fd" stroke-width="2.5" stroke-linecap="round" />

                    <!-- Rotating Collector PV Panel Assembly -->
                    <g transform="rotate(-{{ $elevCurrent }}, 150, 110)" class="transition-transform duration-700 ease-out">
                        <!-- Collector Plane (Solar Array) -->
                        <line x1="60" y1="110" x2="240" y2="110" stroke="#2563eb" stroke-width="7" stroke-linecap="round" />
                        <line x1="65" y1="107" x2="235" y2="107" stroke="#60a5fa" stroke-width="2" stroke-linecap="round" />
                        
                        <!-- Panel cells pattern -->
                        @for($p = 75; $p <= 225; $p += 25)
                            <line x1="{{ $p }}" y1="106" x2="{{ $p }}" y2="114" stroke="#1d4ed8" stroke-width="1.5" />
                        @endfor

                        <!-- Mounting Frame Bracket -->
                        <polygon points="144,110 156,110 153,124 147,124" fill="#94a3b8" />
                        <!-- Normal Vector / Sun perpendicular vector -->
                        <line x1="150" y1="110" x2="150" y2="60" stroke="#fbbf24" stroke-width="1.5" stroke-dasharray="3 3" />
                        <circle cx="150" cy="58" r="2.5" fill="#fbbf24" />
                    </g>

                    <!-- Central Pivot Bearing Point -->
                    <circle cx="150" cy="110" r="5" fill="#f8fafc" stroke="#2563eb" stroke-width="2.5" />

                    <!-- Target Elevation Reference Line -->
                    <g transform="rotate(-{{ $elevTarget }}, 150, 110)">
                        <line x1="60" y1="110" x2="240" y2="110" stroke="#f59e0b" stroke-width="1.5" stroke-dasharray="4 3" opacity="0.8" />
                    </g>
                </svg>

                <!-- Elevation Digital Overlays -->
                <div class="w-full grid grid-cols-3 gap-3 mt-4 pt-4 border-t border-slate-800 text-center font-mono">
                    <div class="bg-slate-800/60 p-2.5 rounded-lg border border-slate-700/50">
                        <div class="text-xs text-slate-400 uppercase tracking-wider">{{ __('Current Pitch') }}</div>
                        <div class="text-base font-bold text-blue-400 mt-0.5">{{ formatNumberId($elevCurrent, 1) }}&deg;</div>
                    </div>
                    <div class="bg-slate-800/60 p-2.5 rounded-lg border border-slate-700/50">
                        <div class="text-xs text-slate-400 uppercase tracking-wider">{{ __('Target Sun Elev') }}</div>
                        <div class="text-base font-bold text-amber-400 mt-0.5">{{ formatNumberId($elevTarget, 1) }}&deg;</div>
                    </div>
                    <div class="bg-slate-800/60 p-2.5 rounded-lg border border-slate-700/50">
                        <div class="text-xs text-slate-400 uppercase tracking-wider">{{ __('Elevation Error') }}</div>
                        <div class="text-base font-bold {{ abs($elevDeviation) <= 1.0 ? 'text-emerald-400' : 'text-amber-400' }} mt-0.5">
                            {{ ($elevDeviation >= 0 ? '+' : '') . formatNumberId($elevDeviation, 1) }}&deg;
                        </div>
                    </div>
                </div>
            </div>
        </x-card>

        <!-- 2. Azimuth Compass Dial SVG -->
        <x-card :title="__('360° Azimuth Compass Dial')" :subtitle="__('Actuator 2 slew heading with 60° to 300° mechanical travel limit arc')">
            <div class="relative bg-slate-900 rounded-xl p-5 overflow-hidden border border-slate-800 flex flex-col items-center">
                <!-- Circular Azimuth Compass SVG -->
                <svg class="w-full h-64 max-w-md" viewBox="0 0 240 240" fill="none">
                    <!-- Outer Compass Dial -->
                    <circle cx="120" cy="120" r="95" stroke="#334155" stroke-width="2" />
                    <circle cx="120" cy="120" r="90" stroke="#1e293b" stroke-width="1.5" stroke-dasharray="4 4" />

                    <!-- Travel Limit Sector (60° to 300° is active tracking window) -->
                    <path d="M 120 120 L 198 75 A 90 90 0 0 0 42 75 Z" fill="#ef4444" fill-opacity="0.08" />
                    <text x="120" y="55" fill="#f87171" font-size="8" font-family="monospace" text-anchor="middle">STOW / DEAD ZONE</text>

                    <!-- Degree Tick Marks -->
                    @for($angle = 0; $angle < 360; $angle += 30)
                        <line 
                            x1="120" 
                            y1="28" 
                            x2="120" 
                            y2="{{ $angle % 90 === 0 ? '36' : '32' }}" 
                            stroke="{{ $angle % 90 === 0 ? '#94a3b8' : '#475569' }}" 
                            stroke-width="{{ $angle % 90 === 0 ? '2' : '1' }}" 
                            transform="rotate({{ $angle }}, 120, 120)" 
                        />
                    @endfor

                    <!-- Cardinal Points -->
                    <text x="120" y="44" fill="#f8fafc" font-size="11" font-weight="bold" font-family="monospace" text-anchor="middle">U</text>
                    <text x="202" y="124" fill="#94a3b8" font-size="10" font-weight="bold" font-family="monospace" text-anchor="middle">T</text>
                    <text x="120" y="202" fill="#94a3b8" font-size="10" font-weight="bold" font-family="monospace" text-anchor="middle">S</text>
                    <text x="38" y="124" fill="#94a3b8" font-size="10" font-weight="bold" font-family="monospace" text-anchor="middle">B</text>

                    <!-- Target Azimuth Sun Pointer -->
                    <g transform="rotate({{ $azTarget }}, 120, 120)">
                        <line x1="120" y1="120" x2="120" y2="40" stroke="#f59e0b" stroke-width="2" stroke-dasharray="3 3" />
                        <circle cx="120" cy="38" r="4.5" fill="#f59e0b" />
                    </g>

                    <!-- Current Azimuth Heading Needle -->
                    <g transform="rotate({{ $azCurrent }}, 120, 120)" class="transition-transform duration-700 ease-out">
                        <polygon points="120,32 125,120 120,112 115,120" fill="#3b82f6" />
                        <polygon points="120,208 123,120 120,128 117,120" fill="#64748b" />
                    </g>

                    <!-- Center Compass Pivot -->
                    <circle cx="120" cy="120" r="7" fill="#0f172a" stroke="#60a5fa" stroke-width="2.5" />
                    <circle cx="120" cy="120" r="2.5" fill="#60a5fa" />
                </svg>

                <!-- Azimuth Digital Overlays -->
                <div class="w-full grid grid-cols-3 gap-3 mt-4 pt-4 border-t border-slate-800 text-center font-mono">
                    <div class="bg-slate-800/60 p-2.5 rounded-lg border border-slate-700/50">
                        <div class="text-xs text-slate-400 uppercase tracking-wider">{{ __('Current Azimuth') }}</div>
                        <div class="text-base font-bold text-blue-400 mt-0.5">{{ formatNumberId($azCurrent, 1) }}&deg;</div>
                    </div>
                    <div class="bg-slate-800/60 p-2.5 rounded-lg border border-slate-700/50">
                        <div class="text-xs text-slate-400 uppercase tracking-wider">{{ __('Target Sun Az') }}</div>
                        <div class="text-base font-bold text-amber-400 mt-0.5">{{ formatNumberId($azTarget, 1) }}&deg;</div>
                    </div>
                    <div class="bg-slate-800/60 p-2.5 rounded-lg border border-slate-700/50">
                        <div class="text-xs text-slate-400 uppercase tracking-wider">{{ __('Azimuth Error') }}</div>
                        <div class="text-base font-bold {{ abs($azDeviation) <= 1.0 ? 'text-emerald-400' : 'text-amber-400' }} mt-0.5">
                            {{ ($azDeviation >= 0 ? '+' : '') . formatNumberId($azDeviation, 1) }}&deg;
                        </div>
                    </div>
                </div>
            </div>
        </x-card>
    </div>

    <!-- Core Telemetry Metric Grid (DC Electrical, Actuators, Wind & Temperature) -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <!-- 1. Tracking Alignment Deviation -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between text-sm text-slate-500 mb-1">
                <span>{{ __('Alignment Status') }}</span>
                @if($isAligned)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        {{ __('ALIGNED') }} (&lt; {{ formatNumberId($threshold, 1) }}&deg;)
                    </span>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                        {{ __('CORRECTING') }}
                    </span>
                @endif
            </div>
            <div class="text-3xl font-bold font-mono text-slate-900 mt-2">
                {{ formatNumberId($totalDeviation, 1) }}&deg;
            </div>
            <div class="text-xs sm:text-sm text-slate-400 mt-2 flex items-center justify-between font-mono">
                <span>Elev: {{ ($elevDeviation >= 0 ? '+' : '') . formatNumberId($elevDeviation, 1) }}&deg;</span>
                <span>Az: {{ ($azDeviation >= 0 ? '+' : '') . formatNumberId($azDeviation, 1) }}&deg;</span>
            </div>
        </div>

        <!-- 2. PZEM-017 DC Array (Solar Array) - Power in W only via formatPowerW -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between text-sm text-slate-500 mb-1">
                <span>{{ __('PZEM-017 DC Array') }}</span>
                <span class="text-xs font-mono px-2 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-200/60">
                    {{ $device->settings['measurement_label'] ?? __('Panel surya') }}
                </span>
            </div>
            <div class="text-3xl font-bold font-mono text-amber-600 mt-2">
                {{ formatPowerW($telemetry?->power ?? 0) }}
            </div>
            <div class="text-xs sm:text-sm text-slate-500 mt-2 flex items-center justify-between font-mono">
                <span>{{ formatNumberId($telemetry?->voltage ?? 0, 1) }} V</span>
                <span>&middot;</span>
                <span>{{ formatNumberId($telemetry?->current ?? 0, 2) }} A</span>
                <span>&middot;</span>
                <span>{{ formatNumberId(($telemetry?->energy_wh ?? 0) / 1000.0, 3) }} kWh</span>
            </div>
        </div>

        <!-- 3. Dual BTS7960 Actuator States -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between text-sm text-slate-500 mb-1">
                <span>{{ __('BTS7960 Actuators') }}</span>
                <span class="text-xs font-mono px-2 py-0.5 rounded bg-slate-100 text-slate-600 border border-slate-200">
                    24V DC Driver
                </span>
            </div>
            <div class="mt-2 space-y-2 text-xs sm:text-sm font-mono">
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">{{ __('Act 1 (Elevation):') }}</span>
                    <span class="font-bold {{ $telemetry?->elev_state !== 'idle' ? 'text-blue-600 font-extrabold' : 'text-slate-700' }}">
                        {{ strtoupper($telemetry?->elev_state ?? 'IDLE') }} ({{ formatNumberId($telemetry?->elev_current ?? 0, 2) }} A)
                    </span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">{{ __('Act 2 (Azimuth):') }}</span>
                    <span class="font-bold {{ $telemetry?->az_state !== 'idle' ? 'text-blue-600 font-extrabold' : 'text-slate-700' }}">
                        {{ strtoupper($telemetry?->az_state ?? 'IDLE') }} ({{ formatNumberId($telemetry?->az_current ?? 0, 2) }} A)
                    </span>
                </div>
            </div>
        </div>

        <!-- 4. Environmental & Enclosure Temp -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between text-sm text-slate-500 mb-1">
                <span>{{ __('Environment & Enclosure') }}</span>
                <span class="text-xs font-mono px-2 py-0.5 rounded {{ $isWindAlert ? 'bg-rose-100 text-rose-800' : 'bg-emerald-50 text-emerald-700' }}">
                    {{ $isWindAlert ? __('STOW LIMIT') : __('NOMINAL') }}
                </span>
            </div>
            <div class="flex items-baseline space-x-2 mt-2">
                <div class="text-3xl font-bold font-mono text-slate-900">
                    {{ formatNumberId($telemetry?->wind_speed ?? 0, 1) }} <span class="text-sm font-sans font-semibold text-slate-600">m/s</span>
                </div>
                <span class="text-xs text-slate-400 font-mono">/ {{ formatNumberId($windStowThreshold, 1) }} limit</span>
            </div>
            <div class="text-xs sm:text-sm text-slate-500 mt-2 flex items-center justify-between">
                <span>DS3231 Enclosure:</span>
                <span class="font-mono font-bold text-slate-800">{{ formatNumberId($telemetry?->temp_c ?? 0, 1) }} &deg;C</span>
            </div>
        </div>
    </div>

    <!-- Hardware Subsystems: BNO055 IMU Calibration & GPS M8N Diagnostics -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- BNO055 IMU Calibration Matrix -->
        <x-card :title="__('BNO055 IMU Calibration Matrix')" :subtitle="__('9-DOF Absolute Orientation Sensor Calibration Registers (0 = Uncalibrated, 3 = Fully Calibrated)')">
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
                        $cal = $calLabels[$item['val']] ?? $calLabels[0];
                    @endphp
                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/80 text-center">
                        <div class="text-xs font-semibold text-slate-600 uppercase">{{ $item['name'] }}</div>
                        <div class="text-xl font-bold font-mono text-slate-900 mt-1">{{ $item['val'] }} / 3</div>
                        <div class="text-xs font-semibold {{ $cal['text'] }} mt-0.5">{{ __($cal['label']) }}</div>
                        
                        <!-- 3-bar visual calibration level -->
                        <div class="flex items-center justify-center space-x-1 mt-2.5">
                            <span class="h-2 w-5 rounded-full {{ $item['val'] >= 1 ? $cal['color'] : 'bg-slate-200' }}"></span>
                            <span class="h-2 w-5 rounded-full {{ $item['val'] >= 2 ? $cal['color'] : 'bg-slate-200' }}"></span>
                            <span class="h-2 w-5 rounded-full {{ $item['val'] >= 3 ? $cal['color'] : 'bg-slate-200' }}"></span>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="text-xs sm:text-sm text-slate-500 mt-4 pt-3 border-t border-slate-100 flex items-center justify-between">
                <span>IMU Roll Angle: <strong class="font-mono text-slate-700">{{ formatNumberId($telemetry?->roll ?? 0, 1) }}&deg;</strong></span>
                <span>Heading Offset: <strong class="font-mono text-slate-700">{{ formatNumberId($device->settings['heading_offset'] ?? 0, 1) }}&deg;</strong></span>
            </div>
        </x-card>

        <!-- GPS M8N & RTC DS3231 Diagnostics -->
        <x-card :title="__('GPS M8N & Time Reference')" :subtitle="__('Solar Position Algorithm (SPA) Coordinates & DS3231 RTC Clock Source')">
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-sm">
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/80">
                    <div class="text-xs text-slate-400 uppercase tracking-wider">{{ __('Fix Status') }}</div>
                    <div class="font-bold text-slate-800 mt-1 flex items-center space-x-1.5">
                        <span class="w-2.5 h-2.5 rounded-full {{ ($telemetry?->fix ?? false) ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                        <span>{{ ($telemetry?->fix ?? false) ? '3D GPS Fix' : 'No Fix' }}</span>
                    </div>
                </div>

                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/80">
                    <div class="text-xs text-slate-400 uppercase tracking-wider">{{ __('Satellites Tracked') }}</div>
                    <div class="font-bold font-mono text-slate-800 mt-1">
                        {{ $telemetry?->satellites ?? 0 }} Sats &middot; HDOP {{ formatNumberId($telemetry?->hdop ?? 0.9, 1) }}
                    </div>
                </div>

                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/80">
                    <div class="text-xs text-slate-400 uppercase tracking-wider">{{ __('RTC DS3231 Clock') }}</div>
                    <div class="font-bold font-mono text-slate-800 mt-1">
                        {{ __('Synced') }} (Drift: {{ $telemetry?->drift_s ?? 0 }}s)
                    </div>
                </div>

                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/80">
                    <div class="text-xs text-slate-400 uppercase tracking-wider">{{ __('Latitude') }}</div>
                    <div class="font-bold font-mono text-slate-800 mt-1">
                        {{ $telemetry?->latitude ? formatNumberId($telemetry->latitude, 5) : '-6.29440' }}
                    </div>
                </div>

                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/80">
                    <div class="text-xs text-slate-400 uppercase tracking-wider">{{ __('Longitude') }}</div>
                    <div class="font-bold font-mono text-slate-800 mt-1">
                        {{ $telemetry?->longitude ? formatNumberId($telemetry->longitude, 5) : '107.15390' }}
                    </div>
                </div>

                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/80">
                    <div class="text-xs text-slate-400 uppercase tracking-wider">{{ __('Altitude') }}</div>
                    <div class="font-bold font-mono text-slate-800 mt-1">
                        {{ $telemetry?->altitude ? formatNumberId($telemetry->altitude, 1) . ' m' : '42,0 m' }}
                    </div>
                </div>
            </div>
            <div class="text-xs sm:text-sm text-slate-500 mt-4 pt-3 border-t border-slate-100 flex items-center justify-between">
                <span>{{ __('Site:') }} <strong class="text-slate-700">{{ $device->site?->name ?? 'Cikarang Industrial Pilot' }}</strong></span>
                <span>Timezone: <strong class="text-slate-700">WIB (Asia/Jakarta, UTC+7)</strong></span>
            </div>
        </x-card>
    </div>

    <!-- Supervisory Actuator Control Panel -->
    <div class="mb-6" id="supervisory-control-section">
        <x-card :title="__('Supervisory Actuator Control Panel')" :subtitle="__('Safety-interlocked manual command override, mode switching, and hardware calibration')">
            {{-- Safety & Role Status Banner --}}
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-4 rounded-xl mb-5 text-sm border {{ $isWindAlert ? 'bg-rose-50 border-rose-200 text-rose-800' : (($telemetryLag && $telemetryLag > 15) ? 'bg-amber-50 border-amber-200 text-amber-800' : 'bg-slate-50 border-slate-200/80 text-slate-700') }}">
                <div class="flex items-center space-x-3">
                    @if($isWindAlert)
                        <span class="w-3 h-3 rounded-full bg-rose-500 animate-ping shrink-0"></span>
                        <div class="font-medium">
                            <strong class="font-bold">{{ __('Safety Interlock Engaged:') }}</strong> {{ __('Wind speed (:speed m/s) ≥ :limit m/s. Manual movement is hardware-locked.', ['speed' => formatNumberId($telemetry?->wind_speed ?? 0, 1), 'limit' => formatNumberId($windStowThreshold, 1)]) }}
                        </div>
                    @elseif($telemetryLag && $telemetryLag > 15)
                        <span class="w-3 h-3 rounded-full bg-amber-500 shrink-0"></span>
                        <div class="font-medium">
                            <strong class="font-bold">{{ __('Interlock Warning:') }}</strong> {{ __('Telemetry is delayed (:lag s ago). Live telemetry packets required for slewing.', ['lag' => $telemetryLag]) }}
                        </div>
                    @else
                        <span class="w-3 h-3 rounded-full bg-emerald-500 shrink-0"></span>
                        <div class="font-medium">
                            <strong class="font-bold text-slate-800">{{ __('Hardware Interlocks Nominal:') }}</strong> {{ __('Wind within safe limits (< :limit m/s). Actuators ready.', ['limit' => formatNumberId($windStowThreshold, 1)]) }}
                        </div>
                    @endif
                </div>

                <div class="flex items-center space-x-2 shrink-0">
                    @if(auth()->user()->canControl())
                        <span class="inline-flex items-center px-3 py-1 rounded text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                            <svg class="w-4 h-4 mr-1.5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                            {{ __('Operator / Admin Authorized') }}
                        </span>
                    @else
                        <span class="inline-flex items-center px-3 py-1 rounded text-xs font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                            <svg class="w-4 h-4 mr-1.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                            {{ __('Viewer Mode (Read-Only)') }}
                        </span>
                    @endif
                </div>
            </div>

            {{-- Command Execution Progress Card --}}
            <div id="commandExecutionCard" class="hidden mb-5 p-5 rounded-xl bg-slate-900 text-white shadow-md border border-slate-700/80 transition-all">
                <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                    <div class="flex items-center space-x-2">
                        <span id="cmdSpinner" class="w-4 h-4 rounded-full border-2 border-blue-400 border-t-transparent animate-spin"></span>
                        <span class="text-sm font-bold uppercase tracking-wider text-blue-400" id="cmdTitle">{{ __('Command Dispatched') }}</span>
                        <span class="text-xs font-mono text-slate-400" id="cmdUuidLabel"></span>
                    </div>
                    <button type="button" onclick="document.getElementById('commandExecutionCard').classList.add('hidden')" class="text-slate-400 hover:text-white text-base cursor-pointer">&times;</button>
                </div>

                {{-- Visual Execution Timeline --}}
                <div class="grid grid-cols-4 gap-3 my-4 text-center text-xs">
                    <div id="step-sent" class="p-2.5 rounded-lg bg-blue-900/60 border border-blue-700 text-blue-200 font-semibold">
                        1. {{ __('Sent to Queue') }}
                    </div>
                    <div id="step-accepted" class="p-2.5 rounded-lg bg-slate-800 border border-slate-700 text-slate-400 font-medium">
                        2. {{ __('Device ACK') }}
                    </div>
                    <div id="step-executing" class="p-2.5 rounded-lg bg-slate-800 border border-slate-700 text-slate-400 font-medium">
                        3. {{ __('Actuators Driving') }}
                    </div>
                    <div id="step-completed" class="p-2.5 rounded-lg bg-slate-800 border border-slate-700 text-slate-400 font-medium">
                        4. {{ __('Reached Target') }}
                    </div>
                </div>

                <div id="cmdMessageBanner" class="text-sm text-slate-300 font-mono bg-slate-800/80 p-3 rounded-lg border border-slate-700">
                    {{ __('Awaiting device acknowledgment via MQTT broker channel...') }}
                </div>
            </div>

            {{-- Supervisory Control Buttons Grid (Min-height 44px, text 16px) --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 {{ auth()->user()->canControl() ? '' : 'opacity-60 pointer-events-none' }}">
                {{-- 1. Operational Mode Switching --}}
                <div class="p-5 rounded-xl bg-slate-50 border border-slate-200/80 space-y-3.5">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-sm text-slate-800">1. {{ __('Operational Mode') }}</span>
                        <span class="text-xs font-mono uppercase px-2.5 py-0.5 rounded bg-blue-100 text-blue-800 font-semibold" id="activeModeBadge">
                            {{ __('Current:') }} {{ strtoupper($device->mode ?? 'auto') }}
                        </span>
                    </div>

                    <div class="space-y-2.5">
                        <button type="button" 
                                onclick="dispatchTrackerCommand('set_mode', { mode: 'auto' })"
                                class="w-full h-11 min-h-[44px] py-2 px-3.5 rounded-lg text-sm font-semibold flex items-center justify-between border transition-all cursor-pointer {{ ($device->mode ?? 'auto') === 'auto' ? 'bg-blue-600 text-white border-blue-700 shadow-xs' : 'bg-white hover:bg-slate-100 text-slate-700 border-slate-200' }}">
                            <span>{{ __('Auto Tracking (Sun SPA)') }}</span>
                            <span class="text-xs opacity-75">Autonomous</span>
                        </button>

                        <button type="button" 
                                onclick="dispatchTrackerCommand('set_mode', { mode: 'manual' })"
                                class="w-full h-11 min-h-[44px] py-2 px-3.5 rounded-lg text-sm font-semibold flex items-center justify-between border transition-all cursor-pointer {{ ($device->mode ?? '') === 'manual' ? 'bg-amber-600 text-white border-amber-700 shadow-xs' : 'bg-white hover:bg-slate-100 text-slate-700 border-slate-200' }}">
                            <span>{{ __('Manual Slew Override') }}</span>
                            <span class="text-xs opacity-75">Operator Control</span>
                        </button>

                        <button type="button" 
                                onclick="dispatchTrackerCommand('stow', {})"
                                class="w-full h-11 min-h-[44px] py-2 px-3.5 rounded-lg text-sm font-semibold flex items-center justify-between border transition-all cursor-pointer {{ ($device->mode ?? '') === 'stow' ? 'bg-slate-800 text-white border-slate-900 shadow-xs' : 'bg-white hover:bg-slate-100 text-slate-700 border-slate-200' }}">
                            <span>{{ __('Protective Stow (0°)') }}</span>
                            <span class="text-xs opacity-75">Aerodynamic Park</span>
                        </button>
                    </div>
                </div>

                {{-- 2. Manual Actuator Slew Coordinates --}}
                <div class="p-5 rounded-xl bg-slate-50 border border-slate-200/80 space-y-3.5">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-sm text-slate-800">2. {{ __('Manual Move Vector') }}</span>
                        <span class="text-xs text-slate-500 font-mono">Limits: 10&ndash;85&deg; / 60&ndash;300&deg;</span>
                    </div>

                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs text-slate-600 font-medium mb-1 flex items-center justify-between">
                                <span>{{ __('Target Elevation (Pitch)') }}</span>
                                <span class="font-mono text-slate-400">10.0&deg; &ndash; 85.0&deg;</span>
                            </label>
                            <div class="relative">
                                <input type="number" id="inputTargetElev" step="0.5" min="10" max="85" value="{{ number_format($elevTarget ?: 35.0, 1, '.', '') }}"
                                       class="w-full h-11 min-h-[44px] px-3.5 py-2 rounded-lg border border-slate-300 bg-white text-slate-800 text-base font-mono font-semibold focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                <span class="absolute right-3.5 top-2.5 text-sm text-slate-400 font-semibold">&deg;</span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs text-slate-600 font-medium mb-1 flex items-center justify-between">
                                <span>{{ __('Target Azimuth (Heading)') }}</span>
                                <span class="font-mono text-slate-400">60.0&deg; &ndash; 300.0&deg;</span>
                            </label>
                            <div class="relative">
                                <input type="number" id="inputTargetAz" step="0.5" min="60" max="300" value="{{ number_format($azTarget ?: 120.0, 1, '.', '') }}"
                                       class="w-full h-11 min-h-[44px] px-3.5 py-2 rounded-lg border border-slate-300 bg-white text-slate-800 text-base font-mono font-semibold focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                <span class="absolute right-3.5 top-2.5 text-sm text-slate-400 font-semibold">&deg;</span>
                            </div>
                        </div>

                        <button type="button" 
                                onclick="dispatchMoveCommand()"
                                class="w-full h-11 min-h-[44px] py-2 px-4 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm shadow-xs transition-colors flex items-center justify-center space-x-2 cursor-pointer">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                            <span>{{ __('Drive Actuators to Target') }}</span>
                        </button>
                    </div>
                </div>

                {{-- 3. Immediate Hardware Overrides & Diagnostics --}}
                <div class="p-5 rounded-xl bg-slate-50 border border-slate-200/80 space-y-3.5">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-sm text-slate-800">3. {{ __('Immediate Safety Overrides') }}</span>
                        <span class="text-xs font-mono text-rose-600 font-semibold">{{ __('Priority Interlocks') }}</span>
                    </div>

                    <div class="space-y-2.5">
                        <button type="button" 
                                onclick="dispatchTrackerCommand('stop', {})"
                                class="w-full h-11 min-h-[44px] py-2.5 px-4 rounded-lg bg-rose-600 hover:bg-rose-700 text-white font-bold text-sm shadow-xs transition-colors flex items-center justify-center space-x-2 cursor-pointer">
                            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 9h6v6H9z" />
                            </svg>
                            <span>{{ __('EMERGENCY STOP (HALT)') }}</span>
                        </button>

                        <button type="button" 
                                onclick="dispatchTrackerCommand('stow', {})"
                                class="w-full h-11 min-h-[44px] py-2 px-4 rounded-lg bg-amber-600 hover:bg-amber-700 text-white font-semibold text-sm shadow-xs transition-colors flex items-center justify-center space-x-2 cursor-pointer">
                            <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                            </svg>
                            <span>{{ __('Force Safe Stow') }}</span>
                        </button>

                        <button type="button" 
                                onclick="dispatchTrackerCommand('calibrate', {})"
                                class="w-full h-11 min-h-[44px] py-2 px-4 rounded-lg bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 font-semibold text-sm transition-colors flex items-center justify-center space-x-2 cursor-pointer">
                            <svg class="w-4 h-4 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <span>{{ __('Recalibrate BNO055 IMU') }}</span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- 4. Recent Command Execution Log Table --}}
            <div class="mt-6 pt-5 border-t border-slate-100">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-sm font-bold text-slate-800 uppercase tracking-wider">{{ __('Recent Dispatched Commands Log') }}</span>
                    <button type="button" onclick="refreshCommandsLog()" class="text-xs text-blue-600 hover:underline font-medium cursor-pointer">
                        {{ __('Refresh Log') }}
                    </button>
                </div>

                <div class="overflow-x-auto rounded-lg border border-slate-200">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-slate-600 text-xs">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold">{{ __('Issued (WIB)') }}</th>
                                <th class="px-4 py-3 text-left font-semibold">{{ __('Command Type') }}</th>
                                <th class="px-4 py-3 text-left font-semibold">{{ __('Parameters') }}</th>
                                <th class="px-4 py-3 text-left font-semibold">{{ __('Status') }}</th>
                                <th class="px-4 py-3 text-left font-semibold">{{ __('Issued By') }}</th>
                                <th class="px-4 py-3 text-right font-semibold">{{ __('Duration') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-mono" id="recentCommandsTableBody">
                            @forelse($recentCommands as $cmd)
                                <tr class="hover:bg-slate-50/60">
                                    <td class="px-4 py-3 text-slate-700 whitespace-nowrap">
                                        {{ formatDateWib($cmd->issued_at, 'd M, H.i.s') }}
                                    </td>
                                    <td class="px-4 py-3 text-slate-800 font-semibold whitespace-nowrap">
                                        {{ $cmd->type }}
                                    </td>
                                    <td class="px-4 py-3 text-slate-600 max-w-xs truncate">
                                        {{ !empty($cmd->params) ? json_encode($cmd->params) : '--' }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        @if($cmd->status === 'completed')
                                            <span class="px-2 py-0.5 rounded text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                {{ __('Completed') }}
                                            </span>
                                        @elseif($cmd->status === 'rejected' || $cmd->status === 'failed')
                                            <span class="px-2 py-0.5 rounded text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200" title="{{ $cmd->reason }}">
                                                {{ __($cmd->status) }}
                                            </span>
                                        @elseif($cmd->status === 'timeout')
                                            <span class="px-2 py-0.5 rounded text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                                {{ __('Timeout') }}
                                            </span>
                                        @else
                                            <span class="px-2 py-0.5 rounded text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                                {{ __($cmd->status) }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-slate-600 whitespace-nowrap font-sans text-xs">
                                        {{ $cmd->user?->name ?? 'System' }}
                                    </td>
                                    <td class="px-4 py-3 text-right text-slate-500 whitespace-nowrap">
                                        {{ ($cmd->completed_at && $cmd->issued_at) ? $cmd->issued_at->diffInSeconds($cmd->completed_at) . 's' : '--' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-5 text-center text-slate-400 font-sans">
                                        {{ __('No recent actuator commands issued.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </x-card>
    </div>

    <!-- Recent Telemetry History Log Table -->
    <x-card :title="__('Recent Telemetry Event Log')" :subtitle="__('Last 10 consecutive packets recorded from :uid', ['uid' => $device->uid])">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-3.5 text-left font-semibold text-slate-600">{{ __('Timestamp (WIB)') }}</th>
                        <th class="px-5 py-3.5 text-left font-semibold text-slate-600">{{ __('Elevation Pitch') }}</th>
                        <th class="px-5 py-3.5 text-left font-semibold text-slate-600">{{ __('Azimuth Heading') }}</th>
                        <th class="px-5 py-3.5 text-left font-semibold text-slate-600">{{ __('DC Voltage & Power') }}</th>
                        <th class="px-5 py-3.5 text-left font-semibold text-slate-600">{{ __('Actuators') }}</th>
                        <th class="px-5 py-3.5 text-left font-semibold text-slate-600">{{ __('Wind') }}</th>
                        <th class="px-5 py-3.5 text-left font-semibold text-slate-600">{{ __('IMU Sys/G/A/M') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white font-mono">
                    @forelse($recentTelemetries as $log)
                        <tr class="hover:bg-slate-50/80">
                            <td class="px-5 py-3 text-slate-700">
                                {{ formatTimeWib($log->recorded_at) }}
                            </td>
                            <td class="px-5 py-3 text-blue-700 font-semibold">
                                {{ formatNumberId($log->pitch_elev, 1) }}&deg;
                                <span class="text-slate-400 font-normal">/ {{ formatNumberId($log->target_elev, 1) }}&deg;</span>
                            </td>
                            <td class="px-5 py-3 text-blue-700 font-semibold">
                                {{ formatNumberId($log->azimuth, 1) }}&deg;
                                <span class="text-slate-400 font-normal">/ {{ formatNumberId($log->target_az, 1) }}&deg;</span>
                            </td>
                            <td class="px-5 py-3 text-amber-600 font-semibold">
                                {{ formatNumberId($log->voltage, 1) }} V &middot; {{ formatPowerW($log->power) }}
                            </td>
                            <td class="px-5 py-3 text-slate-700">
                                <span class="text-xs px-2 py-0.5 rounded bg-slate-100 text-slate-600 uppercase">
                                    {{ $log->elev_state }}/{{ $log->az_state }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-slate-700">
                                {{ formatNumberId($log->wind_speed, 1) }} m/s
                            </td>
                            <td class="px-5 py-3 text-slate-700 text-xs">
                                {{ $log->cal_sys }}/{{ $log->cal_gyro }}/{{ $log->cal_accel }}/{{ $log->cal_mag }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-8 text-center text-slate-400 font-sans">
                                {{ __('No telemetry packets logged yet.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    @push('scripts')
    <script>
        const DEVICE_UID = '{{ $device->uid }}';
        const CSRF_TOKEN = '{{ csrf_token() }}';
        let currentPollingInterval = null;

        function dispatchMoveCommand() {
            const elevInput = document.getElementById('inputTargetElev');
            const azInput = document.getElementById('inputTargetAz');

            const elev = parseFloat(elevInput.value);
            const az = parseFloat(azInput.value);

            if (isNaN(elev) || elev < 10 || elev > 85) {
                alert(window.__ ? window.__('Target elevation must be between 10.0° and 85.0°.') : 'Target elevation must be between 10.0° and 85.0°.');
                return;
            }

            if (isNaN(az) || az < 60 || az > 300) {
                alert(window.__ ? window.__('Target azimuth must be between 60.0° and 300.0°.') : 'Target azimuth must be between 60.0° and 300.0°.');
                return;
            }

            dispatchTrackerCommand('move_to', { elev: elev, az: az });
        }

        async function dispatchTrackerCommand(type, params) {
            const card = document.getElementById('commandExecutionCard');
            const title = document.getElementById('cmdTitle');
            const uuidLabel = document.getElementById('cmdUuidLabel');
            const banner = document.getElementById('cmdMessageBanner');
            const spinner = document.getElementById('cmdSpinner');

            // Reset UI step indicators
            card.classList.remove('hidden');
            title.textContent = window.__ ? window.__("Dispatching ':type' command...", { type: type }) : `Dispatching '${type}' command...`;
            title.className = 'text-xs font-bold uppercase tracking-wider text-blue-400';
            uuidLabel.textContent = '';
            spinner.classList.remove('hidden');
            banner.textContent = window.__ ? window.__('Transmitting command payload through Mosquitto MQTT broker...') : 'Transmitting command payload through Mosquitto MQTT broker...';
            banner.className = 'text-xs text-slate-300 font-mono bg-slate-800/80 p-2.5 rounded-lg border border-slate-700';

            setStepActive('step-sent');
            setStepIdle('step-accepted');
            setStepIdle('step-executing');
            setStepIdle('step-completed');

            try {
                const response = await fetch(`/tracker/${DEVICE_UID}/commands`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        type: type,
                        params: params
                    })
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    title.textContent = window.__ ? window.__('Command Queued: :type', { type: type }) : `Command Queued: ${type}`;
                    uuidLabel.textContent = `UUID: ${data.command_id.substring(0, 8)}...`;
                    banner.textContent = data.message;
                    
                    // Begin polling status
                    startStatusPolling(data.command_id);
                } else {
                    spinner.classList.add('hidden');
                    title.textContent = window.__ ? window.__('Command Blocked by Interlock') : 'Command Blocked by Interlock';
                    title.className = 'text-xs font-bold uppercase tracking-wider text-rose-400';
                    banner.textContent = data.message || (window.__ ? window.__('Safety interlock rejection or authorization error.') : 'Safety interlock rejection or authorization error.');
                    banner.className = 'text-xs text-rose-300 font-mono bg-rose-950/60 p-2.5 rounded-lg border border-rose-800';
                    setStepError('step-sent');
                    refreshCommandsLog();
                }
            } catch (err) {
                console.error(err);
                spinner.classList.add('hidden');
                title.textContent = window.__ ? window.__('Network Error') : 'Network Error';
                title.className = 'text-xs font-bold uppercase tracking-wider text-rose-400';
                banner.textContent = window.__ ? window.__('Failed to communicate with web application backend.') : 'Failed to communicate with web application backend.';
                banner.className = 'text-xs text-rose-300 font-mono bg-rose-950/60 p-2.5 rounded-lg border border-rose-800';
            }
        }

        function startStatusPolling(commandId) {
            if (currentPollingInterval) {
                clearInterval(currentPollingInterval);
            }

            let pollCount = 0;
            currentPollingInterval = setInterval(async () => {
                pollCount++;
                try {
                    const res = await fetch(`/commands/${commandId}/status`);
                    if (!res.ok) return;

                    const data = await res.json();
                    updateExecutionTimeline(data);

                    if (data.is_terminal || pollCount > 30) {
                        clearInterval(currentPollingInterval);
                        refreshCommandsLog();
                    }
                } catch (e) {
                    console.error('Polling error', e);
                }
            }, 1000);
        }

        function updateExecutionTimeline(cmd) {
            const title = document.getElementById('cmdTitle');
            const banner = document.getElementById('cmdMessageBanner');
            const spinner = document.getElementById('cmdSpinner');

            if (cmd.status === 'accepted') {
                setStepActive('step-accepted');
                setStepActive('step-executing');
                title.textContent = window.__ ? window.__('Command Accepted & Driving Actuators') : 'Command Accepted & Driving Actuators';
                banner.textContent = window.__ ? window.__('ESP32 acknowledged command at :time. BTS7960 motors energised.', { time: cmd.accepted_at }) : `ESP32 acknowledged command at ${cmd.accepted_at}. BTS7960 motors energised.`;
            } else if (cmd.status === 'completed') {
                spinner.classList.add('hidden');
                setStepActive('step-accepted');
                setStepActive('step-executing');
                setStepSuccess('step-completed');
                title.textContent = window.__ ? window.__('Execution Completed Successfully') : 'Execution Completed Successfully';
                title.className = 'text-xs font-bold uppercase tracking-wider text-emerald-400';
                banner.textContent = window.__ ? window.__('Tracker completed target stroke at :time. Actuators holding position.', { time: cmd.completed_at }) : `Tracker completed target stroke at ${cmd.completed_at}. Actuators holding position.`;
                banner.className = 'text-xs text-emerald-300 font-mono bg-emerald-950/60 p-2.5 rounded-lg border border-emerald-800';
            } else if (cmd.status === 'rejected' || cmd.status === 'failed') {
                spinner.classList.add('hidden');
                title.textContent = window.__ ? window.__('Command :status', { status: cmd.status.toUpperCase() }) : `Command ${cmd.status.toUpperCase()}`;
                title.className = 'text-xs font-bold uppercase tracking-wider text-rose-400';
                banner.textContent = window.__ ? window.__('Reason: :reason', { reason: cmd.reason || (window.__('Hardware safety condition triggered rejection.')) }) : `Reason: ${cmd.reason || 'Hardware safety condition triggered rejection.'}`;
                banner.className = 'text-xs text-rose-300 font-mono bg-rose-950/60 p-2.5 rounded-lg border border-rose-800';
                setStepError('step-executing');
            } else if (cmd.status === 'timeout') {
                spinner.classList.add('hidden');
                title.textContent = window.__ ? window.__('Command Timed Out') : 'Command Timed Out';
                title.className = 'text-xs font-bold uppercase tracking-wider text-amber-400';
                banner.textContent = window.__ ? window.__('Device did not report completion within safety timeout window.') : 'Device did not report completion within safety timeout window.';
                banner.className = 'text-xs text-amber-300 font-mono bg-amber-950/60 p-2.5 rounded-lg border border-amber-800';
            }
        }

        function setStepActive(id) {
            const el = document.getElementById(id);
            if (!el) return;
            el.className = 'p-2 rounded-lg bg-blue-900/60 border border-blue-500 text-blue-200 font-semibold animate-pulse';
        }

        function setStepSuccess(id) {
            const el = document.getElementById(id);
            if (!el) return;
            el.className = 'p-2 rounded-lg bg-emerald-900/80 border border-emerald-600 text-emerald-200 font-bold';
        }

        function setStepError(id) {
            const el = document.getElementById(id);
            if (!el) return;
            el.className = 'p-2 rounded-lg bg-rose-900/80 border border-rose-600 text-rose-200 font-bold';
        }

        function setStepIdle(id) {
            const el = document.getElementById(id);
            if (!el) return;
            el.className = 'p-2 rounded-lg bg-slate-800 border border-slate-700 text-slate-400 font-medium';
        }

        async function refreshCommandsLog() {
            try {
                const res = await fetch(`/tracker/${DEVICE_UID}/commands`);
                if (!res.ok) return;

                const data = await res.json();
                const tbody = document.getElementById('recentCommandsTableBody');
                if (!tbody) return;

                if (!data.commands || data.commands.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="6" class="px-4 py-5 text-center text-slate-400 font-sans">${window.__ ? window.__('No recent actuator commands issued.') : 'No recent actuator commands issued.'}</td></tr>`;
                    return;
                }

                tbody.innerHTML = data.commands.map(cmd => {
                    let statusBadge = `<span class="px-2 py-0.5 rounded text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200">${cmd.status}</span>`;
                    if (cmd.status === 'completed') {
                        statusBadge = `<span class="px-2 py-0.5 rounded text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">${window.__ ? window.__('Completed') : 'Completed'}</span>`;
                    } else if (cmd.status === 'rejected' || cmd.status === 'failed') {
                        statusBadge = `<span class="px-2 py-0.5 rounded text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200" title="${cmd.reason || ''}">${cmd.status}</span>`;
                    } else if (cmd.status === 'timeout') {
                        statusBadge = `<span class="px-2 py-0.5 rounded text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">${window.__ ? window.__('Timeout') : 'Timeout'}</span>`;
                    }

                    return `
                        <tr class="hover:bg-slate-50/60">
                            <td class="px-4 py-3 text-slate-700 whitespace-nowrap">${cmd.issued_at || '--'}</td>
                            <td class="px-4 py-3 text-slate-800 font-semibold whitespace-nowrap">${cmd.type}</td>
                            <td class="px-4 py-3 text-slate-600 max-w-xs truncate">${cmd.params ? JSON.stringify(cmd.params) : '--'}</td>
                            <td class="px-4 py-3 whitespace-nowrap">${statusBadge}</td>
                            <td class="px-4 py-3 text-slate-600 whitespace-nowrap font-sans text-xs">${cmd.issued_by || 'System'}</td>
                            <td class="px-4 py-3 text-right text-slate-500 whitespace-nowrap">${cmd.duration_s !== null ? cmd.duration_s + 's' : '--'}</td>
                        </tr>
                    `;
                }).join('');
            } catch (e) {
                console.error('Failed to reload command log', e);
            }
        }
    </script>
    @endpush
</x-layouts.app>
