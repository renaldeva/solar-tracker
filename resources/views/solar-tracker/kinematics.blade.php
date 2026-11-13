{{-- Dynamic 2-Axis Mechanical Orientation & Compass Section --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- 1. Elevation Pitch Plane SVG -->
    <x-card :title="__('Elevation Pitch Kinematics')" :subtitle="__('Kemiringan panel surya dari 0° (Mendatar) hingga 90° (Tegak Lurus)')">
        <div class="relative bg-panel-alt/60 rounded-xl p-4 overflow-hidden border border-line flex flex-col items-center">
            <!-- SVG Elevation Diagram -->
            <svg class="w-full h-60 max-w-md" viewBox="0 0 300 200" fill="none">
                <!-- Ground Level & Pedestal -->
                <line x1="20" y1="180" x2="280" y2="180" stroke="#cbd5e1" stroke-width="2" stroke-dasharray="4 4" />
                <text x="25" y="195" fill="#64748b" font-size="10" font-family="'Inter', sans-serif" font-weight="600">0° HORIZON</text>
                <text x="155" y="22" fill="#64748b" font-size="10" font-family="'Inter', sans-serif" font-weight="600">90° ZENIT</text>
                
                <!-- Protractor Reference Arc -->
                <path d="M 230 180 A 80 80 0 0 0 150 100" stroke="#e2e8f0" stroke-width="2" stroke-dasharray="3 3" />
                <line x1="150" y1="180" x2="150" y2="30" stroke="#cbd5e1" stroke-width="1.5" stroke-dasharray="2 2" />

                <!-- Main Support Pedestal Mast -->
                <rect x="144" y="110" width="12" height="70" rx="3" fill="#94a3b8" stroke="#64748b" stroke-width="1.5" />
                <polygon points="135,180 165,180 155,160 145,160" fill="#cbd5e1" />

                <!-- Elevation Actuator 1 Cylinder -->
                <line x1="130" y1="165" x2="140" y2="125" stroke="#2563eb" stroke-width="4" stroke-linecap="round" />
                <line x1="140" y1="125" x2="146" y2="105" stroke="#93c5fd" stroke-width="2.5" stroke-linecap="round" />

                <!-- Rotating Collector PV Panel Assembly -->
                <g transform="rotate({{ -1 * (float) $elevCurrent }}, 150, 110)" class="transition-transform duration-700 ease-out">
                    <!-- Collector Plane (Solar Array) -->
                    <line x1="60" y1="110" x2="240" y2="110" stroke="#2563eb" stroke-width="7" stroke-linecap="round" />
                    <line x1="65" y1="107" x2="235" y2="107" stroke="#bfdbfe" stroke-width="2" stroke-linecap="round" />
                    
                    <!-- Panel cells pattern -->
                    @for($p = 75; $p <= 225; $p += 25)
                        <line x1="{{ $p }}" y1="106" x2="{{ $p }}" y2="114" stroke="#1d4ed8" stroke-width="1.5" />
                    @endfor

                    <!-- Mounting Frame Bracket -->
                    <polygon points="144,110 156,110 153,124 147,124" fill="#64748b" />
                    <!-- Normal Vector -->
                    <line x1="150" y1="110" x2="150" y2="60" stroke="#f59e0b" stroke-width="1.5" stroke-dasharray="3 3" />
                    <circle cx="150" cy="58" r="2.5" fill="#f59e0b" />
                </g>

                <!-- Central Pivot Bearing Point -->
                <circle cx="150" cy="110" r="5" fill="#ffffff" stroke="#2563eb" stroke-width="3" />

                <!-- Target Elevation Reference Line -->
                <g transform="rotate({{ -1 * (float) $elevTarget }}, 150, 110)">
                    <line x1="60" y1="110" x2="240" y2="110" stroke="#f59e0b" stroke-width="2" stroke-dasharray="4 3" opacity="0.9" />
                </g>
            </svg>

            <!-- Elevation Digital Overlays -->
            <div class="w-full grid grid-cols-3 gap-2.5 mt-3 pt-3 border-t border-line text-center">
                <div class="bg-panel p-2.5 rounded-xl border border-line shadow-2xs">
                    <div class="text-[10px] font-semibold text-muted uppercase tracking-wider">{{ __('Saat Ini') }}</div>
                    <div class="text-sm sm:text-base font-bold text-blue-600 dark:text-blue-400 font-mono mt-0.5">{{ formatNumberId($elevCurrent, 1) }}&deg;</div>
                </div>
                <div class="bg-panel p-2.5 rounded-xl border border-line shadow-2xs">
                    <div class="text-[10px] font-semibold text-muted uppercase tracking-wider">{{ __('Target Matahari') }}</div>
                    <div class="text-sm sm:text-base font-bold text-amber-600 dark:text-amber-400 font-mono mt-0.5">{{ formatNumberId($elevTarget, 1) }}&deg;</div>
                </div>
                <div class="bg-panel p-2.5 rounded-xl border border-line shadow-2xs">
                    <div class="text-[10px] font-semibold text-muted uppercase tracking-wider">{{ __('Deviasi') }}</div>
                    <div class="text-sm sm:text-base font-bold font-mono {{ abs($elevDeviation) <= 2.5 ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }} mt-0.5">
                        {{ ($elevDeviation >= 0 ? '+' : '') . formatNumberId($elevDeviation, 1) }}&deg;
                    </div>
                </div>
            </div>
        </div>
    </x-card>

    <!-- 2. Azimuth 360° Compass Compass Rose SVG -->
    <x-card :title="__('360° Azimuth Compass Dial')" :subtitle="__('Putaran horizontal panel dari Utara (0°) ke Timur (90°), Selatan (180°), dan Barat (270°)')">
        <div class="relative bg-panel-alt/60 rounded-xl p-4 overflow-hidden border border-line flex flex-col items-center">
            <!-- 360 Degree Azimuth SVG -->
            <svg class="w-full h-60 max-w-md" viewBox="0 0 300 200" fill="none">
                <!-- Outer Compass Ring -->
                <circle cx="150" cy="100" r="85" stroke="#cbd5e1" stroke-width="2" />
                <circle cx="150" cy="100" r="70" stroke="#f1f5f9" stroke-width="1.5" />

                <!-- Cardinal Direction Labels -->
                <text x="150" y="26" text-anchor="middle" fill="#ef4444" font-size="11" font-family="'Inter', sans-serif" font-weight="700">U (0°)</text>
                <text x="246" y="104" text-anchor="middle" fill="#64748b" font-size="11" font-family="'Inter', sans-serif" font-weight="600">T (90°)</text>
                <text x="150" y="196" text-anchor="middle" fill="#64748b" font-size="11" font-family="'Inter', sans-serif" font-weight="600">S (180°)</text>
                <text x="54" y="104" text-anchor="middle" fill="#64748b" font-size="11" font-family="'Inter', sans-serif" font-weight="600">B (270°)</text>

                <!-- Tick Marks -->
                @for($deg = 0; $deg < 360; $deg += 30)
                    <line 
                        x1="150" y1="18" 
                        x2="150" y2="{{ $deg % 90 == 0 ? 25 : 21 }}" 
                        stroke="{{ $deg == 0 ? '#ef4444' : '#94a3b8' }}" 
                        stroke-width="{{ $deg % 90 == 0 ? 2 : 1 }}" 
                        transform="rotate({{ $deg }}, 150, 100)" 
                    />
                @endfor

                <!-- Target SPA Sun Azimuth Needle (Amber) -->
                <g transform="rotate({{ $azTarget }}, 150, 100)" class="transition-transform duration-500 ease-out opacity-85">
                    <line x1="150" y1="100" x2="150" y2="38" stroke="#f59e0b" stroke-width="2" stroke-dasharray="4 2" />
                    <polygon points="150,28 146,38 154,38" fill="#f59e0b" />
                </g>

                <!-- Current Tracker Azimuth Arrow (Primary Blue) -->
                <g transform="rotate({{ $azCurrent }}, 150, 100)" class="transition-transform duration-700 ease-out">
                    <!-- South Counter-weight tail -->
                    <polygon points="150,142 147,100 153,100" fill="#93c5fd" />
                    <!-- North Heading Arrow Point -->
                    <polygon points="150,30 144,98 156,98" fill="#2563eb" />
                </g>

                <!-- Center Center Pivot -->
                <circle cx="150" cy="100" r="5" fill="#ffffff" stroke="#2563eb" stroke-width="3" />
            </svg>

            <!-- Azimuth Digital Overlays -->
            <div class="w-full grid grid-cols-3 gap-2.5 mt-3 pt-3 border-t border-line text-center">
                <div class="bg-panel p-2.5 rounded-xl border border-line shadow-2xs">
                    <div class="text-[10px] font-semibold text-muted uppercase tracking-wider">{{ __('Saat Ini') }}</div>
                    <div class="text-sm sm:text-base font-bold text-blue-600 dark:text-blue-400 font-mono mt-0.5">{{ formatNumberId($azCurrent, 1) }}&deg;</div>
                </div>
                <div class="bg-panel p-2.5 rounded-xl border border-line shadow-2xs">
                    <div class="text-[10px] font-semibold text-muted uppercase tracking-wider">{{ __('Target Matahari') }}</div>
                    <div class="text-sm sm:text-base font-bold text-amber-600 dark:text-amber-400 font-mono mt-0.5">{{ formatNumberId($azTarget, 1) }}&deg;</div>
                </div>
                <div class="bg-panel p-2.5 rounded-xl border border-line shadow-2xs">
                    <div class="text-[10px] font-semibold text-muted uppercase tracking-wider">{{ __('Deviasi') }}</div>
                    <div class="text-sm sm:text-base font-bold font-mono {{ abs($azDeviation) <= 2.5 ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }} mt-0.5">
                        {{ ($azDeviation >= 0 ? '+' : '') . formatNumberId($azDeviation, 1) }}&deg;
                    </div>
                </div>
            </div>
        </div>
    </x-card>
</div>
