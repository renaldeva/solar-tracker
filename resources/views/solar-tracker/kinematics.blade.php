{{-- Dynamic 2-Axis Mechanical Orientation & Compass Section --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- 1. Elevation Pitch Plane SVG -->
    <x-card :title="__('Elevation Pitch Kinematics')" :subtitle="__('Stroke linier aktuator 1 & sudut Pitch BNO055 (0° Horizon hingga 90° Zenit)')">
        <div class="relative bg-bg/80 rounded-md p-4 overflow-hidden border border-line flex flex-col items-center">
            <!-- SVG Elevation Diagram -->
            <svg class="w-full h-60 max-w-md" viewBox="0 0 300 200" fill="none">
                <!-- Ground Level & Pedestal -->
                <line x1="20" y1="180" x2="280" y2="180" stroke="#374151" stroke-width="2" stroke-dasharray="4 4" />
                <text x="25" y="195" fill="#9ca3af" font-size="9" font-family="'JetBrains Mono', monospace">0° HORIZON</text>
                <text x="155" y="22" fill="#9ca3af" font-size="9" font-family="'JetBrains Mono', monospace">90° ZENITH</text>
                
                <!-- Protractor Reference Arc -->
                <path d="M 230 180 A 80 80 0 0 0 150 100" stroke="#1f2937" stroke-width="1.5" stroke-dasharray="2 2" />
                <line x1="150" y1="180" x2="150" y2="30" stroke="#374151" stroke-width="1" stroke-dasharray="2 2" />

                <!-- Main Support Pedestal Mast -->
                <rect x="144" y="110" width="12" height="70" rx="2" fill="#374151" stroke="#4b5563" stroke-width="1.5" />
                <polygon points="135,180 165,180 155,160 145,160" fill="#1f2937" />

                <!-- Elevation Actuator 1 Cylinder -->
                <line x1="130" y1="165" x2="140" y2="125" stroke="#3b82f6" stroke-width="4" stroke-linecap="round" />
                <line x1="140" y1="125" x2="146" y2="105" stroke="#60a5fa" stroke-width="2.5" stroke-linecap="round" />

                <!-- Rotating Collector PV Panel Assembly -->
                <g transform="rotate(-{{ $elevCurrent }}, 150, 110)" class="transition-transform duration-700 ease-out">
                    <!-- Collector Plane (Solar Array) -->
                    <line x1="60" y1="110" x2="240" y2="110" stroke="#3b82f6" stroke-width="6" stroke-linecap="round" />
                    <line x1="65" y1="107" x2="235" y2="107" stroke="#93c5fd" stroke-width="2" stroke-linecap="round" />
                    
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
                <circle cx="150" cy="110" r="5" fill="#f9fafb" stroke="#3b82f6" stroke-width="2.5" />

                <!-- Target Elevation Reference Line -->
                <g transform="rotate(-{{ $elevTarget }}, 150, 110)">
                    <line x1="60" y1="110" x2="240" y2="110" stroke="#f59e0b" stroke-width="1.5" stroke-dasharray="4 3" opacity="0.85" />
                </g>
            </svg>

            <!-- Elevation Digital Overlays -->
            <div class="w-full grid grid-cols-3 gap-2.5 mt-3 pt-3 border-t border-line text-center font-mono">
                <div class="bg-panel p-2 rounded border border-line">
                    <div class="text-[10px] text-muted uppercase tracking-wider">{{ __('Pitch Saat Ini') }}</div>
                    <div class="text-sm sm:text-base font-bold text-accent mt-0.5">{{ formatNumberId($elevCurrent, 1) }}&deg;</div>
                </div>
                <div class="bg-panel p-2 rounded border border-line">
                    <div class="text-[10px] text-muted uppercase tracking-wider">{{ __('Target Sun Elev') }}</div>
                    <div class="text-sm sm:text-base font-bold text-warn mt-0.5">{{ formatNumberId($elevTarget, 1) }}&deg;</div>
                </div>
                <div class="bg-panel p-2 rounded border border-line">
                    <div class="text-[10px] text-muted uppercase tracking-wider">{{ __('Deviasi Elevasi') }}</div>
                    <div class="text-sm sm:text-base font-bold {{ abs($elevDeviation) <= 1.0 ? 'text-ok' : 'text-warn' }} mt-0.5">
                        {{ ($elevDeviation >= 0 ? '+' : '') . formatNumberId($elevDeviation, 1) }}&deg;
                    </div>
                </div>
            </div>
        </div>
    </x-card>

    <!-- 2. Azimuth Compass Dial SVG -->
    <x-card :title="__('360° Azimuth Compass Dial')" :subtitle="__('Heading aktuator 2 dengan busur batas mekanis 60° hingga 300°')">
        <div class="relative bg-bg/80 rounded-md p-4 overflow-hidden border border-line flex flex-col items-center">
            <!-- Circular Azimuth Compass SVG -->
            <svg class="w-full h-60 max-w-md" viewBox="0 0 240 240" fill="none">
                <!-- Outer Compass Dial -->
                <circle cx="120" cy="120" r="95" stroke="#374151" stroke-width="2" />
                <circle cx="120" cy="120" r="90" stroke="#1f2937" stroke-width="1.5" stroke-dasharray="4 4" />

                <!-- Travel Limit Sector (Dead zone 300° to 60°) -->
                <path d="M 120 120 L 198 75 A 90 90 0 0 0 42 75 Z" fill="#ef4444" fill-opacity="0.12" />
                <text x="120" y="52" fill="#ef4444" font-size="8" font-family="'JetBrains Mono', monospace" text-anchor="middle">STOW / DEAD ZONE</text>

                <!-- Degree Tick Marks -->
                @for($angle = 0; $angle < 360; $angle += 30)
                    <line 
                        x1="120" 
                        y1="28" 
                        x2="120" 
                        y2="{{ $angle % 90 === 0 ? '36' : '32' }}" 
                        stroke="{{ $angle % 90 === 0 ? '#9ca3af' : '#4b5563' }}" 
                        stroke-width="{{ $angle % 90 === 0 ? '2' : '1' }}" 
                        transform="rotate({{ $angle }}, 120, 120)" 
                    />
                @endfor

                <!-- Cardinal Points -->
                <text x="120" y="44" fill="#f9fafb" font-size="11" font-weight="bold" font-family="'JetBrains Mono', monospace" text-anchor="middle">U</text>
                <text x="202" y="124" fill="#9ca3af" font-size="10" font-weight="bold" font-family="'JetBrains Mono', monospace" text-anchor="middle">T</text>
                <text x="120" y="202" fill="#9ca3af" font-size="10" font-weight="bold" font-family="'JetBrains Mono', monospace" text-anchor="middle">S</text>
                <text x="38" y="124" fill="#9ca3af" font-size="10" font-weight="bold" font-family="'JetBrains Mono', monospace" text-anchor="middle">B</text>

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
                <circle cx="120" cy="120" r="7" fill="#0f172a" stroke="#3b82f6" stroke-width="2.5" />
                <circle cx="120" cy="120" r="2.5" fill="#3b82f6" />
            </svg>

            <!-- Azimuth Digital Overlays -->
            <div class="w-full grid grid-cols-3 gap-2.5 mt-3 pt-3 border-t border-line text-center font-mono">
                <div class="bg-panel p-2 rounded border border-line">
                    <div class="text-[10px] text-muted uppercase tracking-wider">{{ __('Azimut Saat Ini') }}</div>
                    <div class="text-sm sm:text-base font-bold text-accent mt-0.5">{{ formatNumberId($azCurrent, 1) }}&deg;</div>
                </div>
                <div class="bg-panel p-2 rounded border border-line">
                    <div class="text-[10px] text-muted uppercase tracking-wider">{{ __('Target Sun Az') }}</div>
                    <div class="text-sm sm:text-base font-bold text-warn mt-0.5">{{ formatNumberId($azTarget, 1) }}&deg;</div>
                </div>
                <div class="bg-panel p-2 rounded border border-line">
                    <div class="text-[10px] text-muted uppercase tracking-wider">{{ __('Deviasi Azimut') }}</div>
                    <div class="text-sm sm:text-base font-bold {{ abs($azDeviation) <= 1.0 ? 'text-ok' : 'text-warn' }} mt-0.5">
                        {{ ($azDeviation >= 0 ? '+' : '') . formatNumberId($azDeviation, 1) }}&deg;
                    </div>
                </div>
            </div>
        </div>
    </x-card>
</div>

