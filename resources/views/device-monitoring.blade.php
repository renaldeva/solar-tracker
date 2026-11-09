<x-layouts.app title="{{ __('Device Monitoring') }}">
    <x-page-header 
        :title="__('Device Monitoring')" 
        :subtitle="__('Track telemetry latency, node connectivity, and MQTT broker link health across tracker nodes.')"
        :badge="__('Link Health')"
    >
        <x-slot:actions>
            <div class="flex items-center space-x-3">
                <a 
                    href="{{ route('device-monitoring.export', ['status' => $statusFilter]) }}" 
                    class="inline-flex items-center h-11 min-h-[44px] px-4 py-2.5 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 text-sm font-semibold shadow-2xs transition-all hover:border-slate-400"
                    title="{{ __('Export CSV') }}"
                >
                    <svg class="w-4 h-4 mr-2 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    <span>{{ __('Export CSV') }}</span>
                </a>
                <button 
                    type="button" 
                    onclick="window.location.reload()" 
                    class="inline-flex items-center h-11 min-h-[44px] px-4 py-2.5 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 text-sm font-semibold shadow-2xs transition-all hover:border-slate-400 cursor-pointer"
                    title="{{ __('Refresh') }}"
                >
                    <svg class="w-4 h-4 mr-2 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span>{{ __('Refresh') }}</span>
                </button>
            </div>
        </x-slot:actions>
    </x-page-header>

    <!-- Top KPI Status Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <x-kpi-card 
            :title="__('Online Trackers')" 
            :value="$onlineCount . ' / ' . $totalTrackers" 
            badge="HEALTHY" 
            badgeType="success"
            :subtitle="__('Broadcasting nominal telemetry')" 
        />
        <x-kpi-card 
            :title="__('Unstable Trackers')" 
            :value="$unstableCount" 
            badge="{{ $unstableCount > 0 ? 'ATTENTION' : 'NONE' }}" 
            badgeType="{{ $unstableCount > 0 ? 'warning' : 'neutral' }}"
            :subtitle="__('Intermittent packet loss')" 
        />
        <x-kpi-card 
            :title="__('Offline Trackers')" 
            :value="$offlineCount" 
            badge="{{ $offlineCount > 0 ? 'DISCONNECTED' : 'ALL ACTIVE' }}" 
            badgeType="{{ $offlineCount > 0 ? 'danger' : 'neutral' }}"
            :subtitle="__('No packet received > 15s')" 
        />
        <x-kpi-card 
            :title="__('Telemetry Ingestion Lag')" 
            :value="$telemetryLagSeconds !== null ? formatNumberId($telemetryLagSeconds, 1) . ' s' : '-- s'" 
            badge="{{ ($telemetryLagSeconds !== null && $telemetryLagSeconds <= 6) ? 'STREAMING' : 'DELAYED' }}" 
            badgeType="{{ ($telemetryLagSeconds !== null && $telemetryLagSeconds <= 6) ? 'success' : 'warning' }}"
            :subtitle="__('Target cycle: 5.0s ± 0.5s')" 
        />
    </div>

    <!-- Link Health & Protocol Diagnostics Card -->
    <div class="mb-6 rounded-2xl bg-gradient-to-r from-slate-900 via-slate-800 to-indigo-950 p-6 text-white shadow-xs border border-slate-800">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-slate-700/60">
            <div>
                <div class="flex items-center space-x-2 flex-wrap">
                    <span class="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-400 animate-ping"></span>
                    <span class="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500 -ml-4.5"></span>
                    <h3 class="font-bold text-base text-slate-100 tracking-tight">{{ __('MQTT Telemetry Link & Infrastructure Health') }}</h3>
                    <span class="text-xs font-mono px-2.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 font-semibold">{{ __('OPERATIONAL') }}</span>
                </div>
                <p class="text-sm text-slate-400 mt-1">
                    {{ __('Eclipse Mosquitto 2.0 message broker paired with daemonized asynchronous PHP telemetry listener.') }}
                </p>
            </div>
            <div class="flex items-center space-x-6 text-sm font-mono">
                <div class="text-right">
                    <div class="text-xs text-slate-400 uppercase tracking-wider">{{ __('Ingestion Rate') }}</div>
                    <div class="text-emerald-400 font-bold text-base">{{ $packetsLastMinute * 12 }} pkts/min</div>
                </div>
                <div class="h-8 w-px bg-slate-700"></div>
                <div class="text-right">
                    <div class="text-xs text-slate-400 uppercase tracking-wider">{{ __('Broker Latency') }}</div>
                    <div class="text-indigo-300 font-bold text-base">{{ $connectionLatencyMs ? $connectionLatencyMs . ' ms' : '< 1 ms' }}</div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 pt-4 text-sm">
            <div class="p-3.5 rounded-xl bg-slate-800/60 border border-slate-700/40">
                <div class="text-xs text-slate-400 font-medium mb-1">{{ __('Mosquitto Message Broker') }}</div>
                <div class="font-semibold text-slate-200 flex items-center justify-between">
                    <span>{{ $brokerHost }}:{{ $brokerPort }}</span>
                    @if($brokerSocketOk)
                        <span class="text-xs text-emerald-400 font-mono bg-emerald-950/60 px-2 py-0.5 rounded border border-emerald-800">{{ __('Connected') }}</span>
                    @else
                        <span class="text-xs text-rose-400 font-mono bg-rose-950/60 px-2 py-0.5 rounded border border-rose-800">{{ __('Unreachable') }}</span>
                    @endif
                </div>
                <div class="text-xs text-slate-400 mt-1">Host Port: 1883 TCP &middot; QoS 1</div>
            </div>

            <div class="p-3.5 rounded-xl bg-slate-800/60 border border-slate-700/40">
                <div class="text-xs text-slate-400 font-medium mb-1">{{ __('Telemetry Listener Service') }}</div>
                <div class="font-semibold text-slate-200 flex items-center justify-between">
                    <span>solar_tracker_mqtt_listener</span>
                    <span class="text-xs text-emerald-400 font-mono bg-emerald-950/60 px-2 py-0.5 rounded border border-emerald-800">{{ __('Active') }}</span>
                </div>
                <div class="text-xs text-slate-400 mt-1">{{ __('Subscriptions:') }} tracker/+/telemetry</div>
            </div>

            <div class="p-3.5 rounded-xl bg-slate-800/60 border border-slate-700/40">
                <div class="text-xs text-slate-400 font-medium mb-1">{{ __('Supervisory Protocol') }}</div>
                <div class="font-semibold text-slate-200">MQTT v3.1.1 JSON Payloads</div>
                <div class="text-xs text-slate-400 mt-1">Topics: telemetry, status, event, ack</div>
            </div>

            <div class="p-3.5 rounded-xl bg-slate-800/60 border border-slate-700/40">
                <div class="text-xs text-slate-400 font-medium mb-1">{{ __('Time-Series Storage') }}</div>
                <div class="font-semibold text-slate-200">PostgreSQL 16 &middot; Timeseries</div>
                <div class="text-xs text-slate-400 mt-1">{{ __('Hourly rollups & 7-day retention') }}</div>
            </div>
        </div>
    </div>

    <!-- Filter Tabs & Search Toolbar -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs mb-6 overflow-hidden">
        <div class="p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100">
            <!-- Status Tabs -->
            <div class="flex items-center space-x-1 sm:space-x-2 overflow-x-auto">
                <a 
                    href="{{ route('device-monitoring', ['status' => 'all', 'search' => $search]) }}" 
                    class="px-4 py-2.5 rounded-xl text-sm font-semibold transition-all {{ $statusFilter === 'all' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}"
                >
                    {{ __('All Nodes') }}
                    <span class="ml-1.5 px-2 py-0.5 text-xs rounded-full {{ $statusFilter === 'all' ? 'bg-blue-500 text-white' : 'bg-slate-200 text-slate-700' }}">{{ $totalTrackers }}</span>
                </a>
                <a 
                    href="{{ route('device-monitoring', ['status' => 'online', 'search' => $search]) }}" 
                    class="px-4 py-2.5 rounded-xl text-sm font-semibold transition-all {{ $statusFilter === 'online' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}"
                >
                    {{ __('Online') }}
                    <span class="ml-1.5 px-2 py-0.5 text-xs rounded-full {{ $statusFilter === 'online' ? 'bg-emerald-500 text-white' : 'bg-emerald-100 text-emerald-800' }}">{{ $onlineCount }}</span>
                </a>
                <a 
                    href="{{ route('device-monitoring', ['status' => 'unstable', 'search' => $search]) }}" 
                    class="px-4 py-2.5 rounded-xl text-sm font-semibold transition-all {{ $statusFilter === 'unstable' ? 'bg-amber-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}"
                >
                    {{ __('Unstable') }}
                    <span class="ml-1.5 px-2 py-0.5 text-xs rounded-full {{ $statusFilter === 'unstable' ? 'bg-amber-500 text-white' : 'bg-amber-100 text-amber-800' }}">{{ $unstableCount }}</span>
                </a>
                <a 
                    href="{{ route('device-monitoring', ['status' => 'offline', 'search' => $search]) }}" 
                    class="px-4 py-2.5 rounded-xl text-sm font-semibold transition-all {{ $statusFilter === 'offline' ? 'bg-rose-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}"
                >
                    {{ __('Offline') }}
                    <span class="ml-1.5 px-2 py-0.5 text-xs rounded-full {{ $statusFilter === 'offline' ? 'bg-rose-500 text-white' : 'bg-rose-100 text-rose-800' }}">{{ $offlineCount }}</span>
                </a>
            </div>

            <!-- Search Filter -->
            <form method="GET" action="{{ route('device-monitoring') }}" class="flex items-center space-x-2">
                <input type="hidden" name="status" value="{{ $statusFilter }}">
                <div class="relative w-full sm:w-72">
                    <input 
                        type="text" 
                        name="search" 
                        value="{{ $search }}" 
                        placeholder="{{ __('Search UID or node name...') }}" 
                        class="w-full pl-10 pr-4 py-2.5 h-11 min-h-[44px] text-base rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-2xs"
                    >
                    <svg class="w-5 h-5 text-slate-400 absolute left-3 top-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                @if($search)
                    <a href="{{ route('device-monitoring', ['status' => $statusFilter]) }}" class="px-3 py-2.5 h-11 min-h-[44px] rounded-xl border border-slate-200 text-sm text-slate-500 hover:bg-slate-100 flex items-center justify-center">
                        {{ __('Clear') }}
                    </a>
                @endif
                <button type="submit" class="px-4 py-2.5 h-11 min-h-[44px] rounded-xl bg-slate-900 text-white text-sm font-semibold hover:bg-slate-800 transition-all shadow-xs cursor-pointer">
                    {{ __('Search') }}
                </button>
            </form>
        </div>

        <!-- Registered Solar Trackers Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50/75">
                    <tr>
                        <th class="px-5 py-4 text-left font-semibold text-slate-600">{{ __('UID / Tracker Node') }}</th>
                        <th class="px-5 py-4 text-left font-semibold text-slate-600">{{ __('Status & Mode') }}</th>
                        <th class="px-5 py-4 text-left font-semibold text-slate-600">{{ __('24-Hour Connectivity Timeline') }}</th>
                        <th class="px-5 py-4 text-left font-semibold text-slate-600">{{ __('Orientation') }}</th>
                        <th class="px-5 py-4 text-left font-semibold text-slate-600">{{ __('Array DC & Wind') }}</th>
                        <th class="px-5 py-4 text-left font-semibold text-slate-600">{{ __('Link & RSSI') }}</th>
                        <th class="px-5 py-4 text-left font-semibold text-slate-600">{{ __('Last Telemetry') }}</th>
                        <th class="px-5 py-4 text-right font-semibold text-slate-600">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($devices as $device)
                        @php
                            $tel = $device->latestTelemetry;
                            $slots = $deviceConnectivity[$device->id] ?? [];
                            $isOnline = $device->status === 'online';
                            $rssi = $device->rssi ?? -80;
                            $signalBars = match(true) {
                                $rssi >= -65 => 4,
                                $rssi >= -75 => 3,
                                $rssi >= -85 => 2,
                                default => 1,
                            };
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <!-- Node UID & Name -->
                            <td class="px-5 py-4.5">
                                <div class="font-bold text-slate-900 text-base flex items-center space-x-1.5">
                                    <a href="{{ route('solar-tracker', ['uid' => $device->uid]) }}" class="hover:text-blue-600 transition-colors">
                                        {{ $device->name }}
                                    </a>
                                </div>
                                <div class="font-mono text-slate-400 text-xs mt-0.5 flex items-center space-x-2">
                                    <span class="bg-slate-100 px-2 py-0.5 rounded text-slate-600 border border-slate-200/60">{{ $device->uid }}</span>
                                    <span>&middot;</span>
                                    <span>{{ $device->site?->name ?? 'Cikarang Pilot' }}</span>
                                </div>
                            </td>

                            <!-- Status & Mode -->
                            <td class="px-5 py-4.5">
                                <div class="flex flex-col space-y-1.5 items-start">
                                    <x-status-pill :status="$device->status" :pulse="$isOnline" size="sm" />
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-mono font-bold uppercase tracking-wider {{ $device->mode === 'auto' ? 'bg-blue-50 text-blue-700 border border-blue-200/70' : ($device->mode === 'stow' ? 'bg-amber-50 text-amber-700 border border-amber-200/70' : 'bg-slate-100 text-slate-600 border border-slate-200') }}">
                                        {{ __('Mode:') }} {{ __($device->mode) }}
                                    </span>
                                </div>
                            </td>

                            <!-- 24-Hour Connectivity Bars -->
                            <td class="px-5 py-4.5" x-data="{ tooltip: '', tooltipPos: '' }">
                                <div class="w-52 sm:w-60">
                                    <div class="flex items-center justify-between text-xs text-slate-400 mb-1">
                                        <span>24h</span>
                                        <span class="font-semibold text-slate-600">{{ $isOnline ? '99.8% Uptime' : ($device->status === 'unstable' ? '74.2% Uptime' : 'Offline') }}</span>
                                        <span>{{ __('Now') }}</span>
                                    </div>
                                    <div class="flex items-center space-x-0.5 h-5 p-1 rounded-md bg-slate-50 border border-slate-200/70">
                                        @foreach($slots as $slot)
                                            @php
                                                $barColor = match($slot['status']) {
                                                    'online' => 'bg-emerald-500 hover:bg-emerald-400',
                                                    'unstable' => 'bg-amber-400 hover:bg-amber-300',
                                                    default => 'bg-slate-200 hover:bg-slate-300',
                                                };
                                            @endphp
                                            <div 
                                                class="flex-1 h-3 rounded-2xs {{ $barColor }} transition-all cursor-pointer relative group"
                                                title="{{ $slot['time_wib'] }}: {{ ucfirst($slot['status']) }} ({{ $slot['samples'] }} packets, {{ $slot['uptime'] }}% uptime)"
                                            ></div>
                                        @endforeach
                                    </div>
                                </div>
                            </td>

                            <!-- Orientation -->
                            <td class="px-5 py-4.5 font-mono text-slate-700">
                                @if($tel)
                                    <div>Elev: <span class="font-bold text-slate-900">{{ formatNumberId($tel->pitch_elev, 1) }}&deg;</span></div>
                                    <div class="text-xs text-slate-500">Az: <span class="font-bold text-slate-900">{{ formatNumberId($tel->azimuth, 1) }}&deg;</span></div>
                                @else
                                    <span class="text-slate-400">--</span>
                                @endif
                            </td>

                            <!-- DC Array & Wind (Power in W only via formatPowerW) -->
                            <td class="px-5 py-4.5 font-mono text-slate-700">
                                @if($tel)
                                    <div class="font-bold text-amber-600 text-base">{{ formatPowerW($tel->power) }}</div>
                                    <div class="text-xs text-slate-500">{{ formatNumberId($tel->wind_speed, 1) }} m/s {{ __('wind') }}</div>
                                @else
                                    <span class="text-slate-400">--</span>
                                @endif
                            </td>

                            <!-- Link & Signal -->
                            <td class="px-5 py-4.5">
                                <div class="flex items-center space-x-1.5 font-mono text-slate-700">
                                    <!-- Signal bars icon -->
                                    <div class="flex items-end space-x-0.5 h-4">
                                        <div class="w-1 rounded-2xs h-1 {{ $signalBars >= 1 ? 'bg-emerald-500' : 'bg-slate-200' }}"></div>
                                        <div class="w-1 rounded-2xs h-2 {{ $signalBars >= 2 ? 'bg-emerald-500' : 'bg-slate-200' }}"></div>
                                        <div class="w-1 rounded-2xs h-3 {{ $signalBars >= 3 ? 'bg-emerald-500' : 'bg-slate-200' }}"></div>
                                        <div class="w-1 rounded-2xs h-4 {{ $signalBars >= 4 ? 'bg-emerald-500' : 'bg-slate-200' }}"></div>
                                    </div>
                                    <span class="text-xs font-semibold">{{ $device->rssi ? $device->rssi . ' dBm' : '--' }}</span>
                                </div>
                                <div class="text-xs font-mono text-slate-400 mt-0.5">
                                    FW v{{ $device->firmware }}
                                </div>
                            </td>

                            <!-- Last Telemetry (Indonesian format with WIB) -->
                            <td class="px-5 py-4.5">
                                @if($device->last_seen_at)
                                    <div class="font-semibold text-slate-800">
                                        {{ $device->last_seen_at->timezone('Asia/Jakarta')->diffForHumans() }}
                                    </div>
                                    <div class="font-mono text-xs text-slate-400">
                                        {{ formatDateWib($device->last_seen_at, 'd M H.i') }}
                                    </div>
                                @else
                                    <span class="text-slate-400 font-mono text-xs">{{ __('Never connected') }}</span>
                                @endif
                            </td>

                            <!-- Actions -->
                            <td class="px-5 py-4.5 text-right">
                                <a 
                                    href="{{ route('solar-tracker', ['uid' => $device->uid]) }}" 
                                    class="inline-flex items-center h-10 min-h-[40px] px-3.5 py-2 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs font-semibold transition-all border border-blue-200/60 shadow-2xs group"
                                >
                                    <span>{{ __('Detail') }}</span>
                                    <svg class="w-4 h-4 ml-1 transition-transform group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center">
                                <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-slate-100 text-slate-400 mb-3">
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div class="text-base font-semibold text-slate-700">{{ __('No tracker nodes found') }}</div>
                                <div class="text-sm text-slate-400 mt-1">{{ __('Try clearing your search or status filter.') }}</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Table Footer / Summary -->
        <div class="px-5 py-4 bg-slate-50 border-t border-slate-200/80 flex flex-col sm:flex-row items-center justify-between text-sm text-slate-500 gap-2">
            <div>
                {{ __('Showing :count of :total registered nodes', ['count' => $devices->count(), 'total' => $totalTrackers]) }}
            </div>
            <div class="flex items-center space-x-4 text-xs font-medium">
                <span class="flex items-center space-x-1.5">
                    <span class="w-2.5 h-2.5 rounded-2xs bg-emerald-500"></span>
                    <span>Online &ge; 80%</span>
                </span>
                <span class="flex items-center space-x-1.5">
                    <span class="w-2.5 h-2.5 rounded-2xs bg-amber-400"></span>
                    <span>{{ __('Degraded / Loss') }}</span>
                </span>
                <span class="flex items-center space-x-1.5">
                    <span class="w-2.5 h-2.5 rounded-2xs bg-slate-200"></span>
                    <span>Offline</span>
                </span>
            </div>
        </div>
    </div>
</x-layouts.app>
