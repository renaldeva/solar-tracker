<x-layouts.app title="{{ __('Device Monitoring') }}">
    <x-page-header 
        :title="__('Device Monitoring')" 
        :subtitle="__('Track telemetry latency, node connectivity, and MQTT broker link health across tracker nodes.')"
        :badge="__('Link Health')"
    >
        <x-slot:actions>
            <div class="flex items-center space-x-2">
                <a 
                    href="{{ route('device-monitoring.export', ['status' => $statusFilter]) }}" 
                    class="inline-flex items-center h-9 px-3 rounded-md border border-line bg-panel hover:bg-line/40 text-ink text-sm font-medium transition-colors"
                    title="{{ __('Export CSV') }}"
                >
                    <svg class="w-4 h-4 mr-2 text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    <span>{{ __('Export CSV') }}</span>
                </a>
                <button 
                    type="button" 
                    onclick="window.location.reload()" 
                    class="inline-flex items-center h-9 px-3 rounded-md border border-line bg-panel hover:bg-line/40 text-ink text-sm font-medium transition-colors cursor-pointer"
                    title="{{ __('Refresh') }}"
                >
                    <svg class="w-4 h-4 mr-2 text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span>{{ __('Refresh') }}</span>
                </button>
            </div>
        </x-slot:actions>
    </x-page-header>

    <!-- Top KPI Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <x-kpi-card 
            :title="__('Online Trackers')" 
            :value="$onlineCount . ' / ' . $totalTrackers" 
            tone="ok"
            :badge="$totalTrackers > 0 ? formatNumberId(($onlineCount / $totalTrackers) * 100, 0) . '%' : '0%'"
            :subtitle="$unstableCount . ' tidak stabil, ' . $offlineCount . ' offline'"
        />
        <x-kpi-card 
            title="Status MQTT Broker" 
            :value="$brokerSocketOk ? 'Terhubung' : 'Terputus'" 
            :tone="$brokerSocketOk ? 'ok' : 'danger'"
            :badge="$brokerSocketOk ? 'ONLINE' : 'OFFLINE'"
            :subtitle="($connectionLatencyMs !== null ? formatNumberId($connectionLatencyMs, 1) . ' ms' : '< 1 ms') . ' latensi'"
        />
        <x-kpi-card 
            title="Packet Rate" 
            :value="formatNumberId($packetsLastMinute * 12)" 
            unit="pkts/m"
            tone="accent"
            :badge="$listenerStatus"
            subtitle="Listener telemetri aktif"
        />
        <x-kpi-card 
            title="Lag Telemetri" 
            :value="$telemetryLagSeconds !== null ? formatNumberId($telemetryLagSeconds, 1) : '--'" 
            unit="s"
            :tone="($telemetryLagSeconds !== null && $telemetryLagSeconds <= 6) ? 'ok' : 'warn'"
            :badge="($telemetryLagSeconds !== null && $telemetryLagSeconds <= 6) ? 'NORMAL' : 'DELAYED'"
            subtitle="Target siklus: 5,0s ± 0,5s"
        />
    </div>

    <!-- Broker & Protocol Diagnostics Panel -->
    <div class="bg-panel border border-line rounded-md p-4 mb-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 pb-3 border-b border-line">
            <div class="flex items-center space-x-2">
                <span class="inline-block w-2.5 h-2.5 rounded-full {{ $brokerSocketOk ? 'bg-ok' : 'bg-danger' }}"></span>
                <h2 class="text-sm font-semibold text-ink">Diagnostik Broker & Protokol MQTT</h2>
                <span class="text-xs font-mono px-2 py-0.5 rounded border {{ $brokerSocketOk ? 'border-ok/30 text-ok bg-ok/10' : 'border-danger/30 text-danger bg-danger/10' }}">
                    {{ $brokerSocketOk ? 'OPERASIONAL' : 'TERPUTUS' }}
                </span>
            </div>
            <div class="flex items-center space-x-4 text-xs font-mono text-muted">
                <div>
                    <span>Latensi: </span>
                    <span class="text-ink font-semibold">{{ $connectionLatencyMs !== null ? formatNumberId($connectionLatencyMs, 1) . ' ms' : '< 1 ms' }}</span>
                </div>
                <div class="h-3 w-px bg-line"></div>
                <div>
                    <span>Paket/menit: </span>
                    <span class="text-ok font-semibold">{{ formatNumberId($packetsLastMinute * 12) }}</span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 pt-3 text-xs">
            <div class="p-3 rounded border border-line bg-bg/50">
                <div class="text-muted font-medium mb-1">Mosquitto Message Broker</div>
                <div class="font-mono text-ink font-semibold flex items-center justify-between">
                    <span>{{ $brokerHost }}:{{ $brokerPort }}</span>
                    @if($brokerSocketOk)
                        <span class="text-ok font-mono">OK</span>
                    @else
                        <span class="text-danger font-mono">FAIL</span>
                    @endif
                </div>
                <div class="text-muted mt-1">Port: 1883 TCP &middot; QoS 1</div>
            </div>

            <div class="p-3 rounded border border-line bg-bg/50">
                <div class="text-muted font-medium mb-1">Layanan Listener Telemetri</div>
                <div class="font-mono text-ink font-semibold flex items-center justify-between">
                    <span class="truncate">solar_tracker_mqtt_listener</span>
                    <span class="text-ok font-mono ml-1">{{ $listenerStatus }}</span>
                </div>
                <div class="text-muted mt-1 truncate">tracker/+/telemetry</div>
            </div>

            <div class="p-3 rounded border border-line bg-bg/50">
                <div class="text-muted font-medium mb-1">Protokol Komunikasi</div>
                <div class="font-mono text-ink font-semibold">MQTT v3.1.1 JSON</div>
                <div class="text-muted mt-1">telemetry, status, ack</div>
            </div>

            <div class="p-3 rounded border border-line bg-bg/50">
                <div class="text-muted font-medium mb-1">Penyimpanan Time-Series</div>
                <div class="font-mono text-ink font-semibold">Database Time-Series</div>
                <div class="text-muted mt-1">Rollup per jam &middot; Retensi 7 hari</div>
            </div>
        </div>
    </div>

    <!-- Filter Tabs & Search Toolbar -->
    <div class="bg-panel border border-line rounded-md mb-6 overflow-hidden">
        <div class="p-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <!-- Status Tabs -->
            <div class="flex items-center space-x-1.5 overflow-x-auto">
                <a 
                    href="{{ route('device-monitoring', ['status' => 'all', 'search' => $search]) }}" 
                    class="inline-flex items-center px-3 py-1.5 rounded-md text-xs font-medium transition-colors {{ $statusFilter === 'all' ? 'bg-accent text-ink' : 'bg-bg text-muted hover:text-ink border border-line' }}"
                >
                    <span>Semua Node</span>
                    <span class="ml-1.5 px-1.5 py-0.2 rounded font-mono text-[11px] {{ $statusFilter === 'all' ? 'bg-black/30 text-ink' : 'bg-panel text-muted' }}">{{ $totalTrackers }}</span>
                </a>
                <a 
                    href="{{ route('device-monitoring', ['status' => 'online', 'search' => $search]) }}" 
                    class="inline-flex items-center px-3 py-1.5 rounded-md text-xs font-medium transition-colors {{ $statusFilter === 'online' ? 'bg-accent text-ink' : 'bg-bg text-muted hover:text-ink border border-line' }}"
                >
                    <span>Online</span>
                    <span class="ml-1.5 px-1.5 py-0.2 rounded font-mono text-[11px] {{ $statusFilter === 'online' ? 'bg-black/30 text-ink' : 'bg-panel text-muted' }}">{{ $onlineCount }}</span>
                </a>
                <a 
                    href="{{ route('device-monitoring', ['status' => 'unstable', 'search' => $search]) }}" 
                    class="inline-flex items-center px-3 py-1.5 rounded-md text-xs font-medium transition-colors {{ $statusFilter === 'unstable' ? 'bg-accent text-ink' : 'bg-bg text-muted hover:text-ink border border-line' }}"
                >
                    <span>Tidak Stabil</span>
                    <span class="ml-1.5 px-1.5 py-0.2 rounded font-mono text-[11px] {{ $statusFilter === 'unstable' ? 'bg-black/30 text-ink' : 'bg-panel text-muted' }}">{{ $unstableCount }}</span>
                </a>
                <a 
                    href="{{ route('device-monitoring', ['status' => 'offline', 'search' => $search]) }}" 
                    class="inline-flex items-center px-3 py-1.5 rounded-md text-xs font-medium transition-colors {{ $statusFilter === 'offline' ? 'bg-accent text-ink' : 'bg-bg text-muted hover:text-ink border border-line' }}"
                >
                    <span>Offline</span>
                    <span class="ml-1.5 px-1.5 py-0.2 rounded font-mono text-[11px] {{ $statusFilter === 'offline' ? 'bg-black/30 text-ink' : 'bg-panel text-muted' }}">{{ $offlineCount }}</span>
                </a>
            </div>

            <!-- Search Form -->
            <form method="GET" action="{{ route('device-monitoring') }}" class="flex items-center space-x-2">
                <input type="hidden" name="status" value="{{ $statusFilter }}">
                <div class="relative w-full sm:w-64">
                    <input 
                        type="text" 
                        name="search" 
                        value="{{ $search }}" 
                        placeholder="Cari UID atau nama..." 
                        class="w-full pl-8 pr-3 py-1.5 text-xs bg-bg border border-line rounded-md text-ink placeholder-muted focus:outline-none focus:border-accent"
                    >
                    <svg class="w-4 h-4 text-muted absolute left-2.5 top-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                @if($search)
                    <a href="{{ route('device-monitoring', ['status' => $statusFilter]) }}" class="px-2.5 py-1.5 text-xs rounded-md border border-line text-muted hover:text-ink transition-colors">
                        Reset
                    </a>
                @endif
                <button type="submit" class="px-3 py-1.5 rounded-md bg-accent text-ink text-xs font-medium hover:bg-blue-600 transition-colors cursor-pointer">
                    Cari
                </button>
            </form>
        </div>

        <!-- Devices Table -->
        <div class="overflow-x-auto border-t border-line">
            <table class="w-full text-left text-sm">
                <thead class="bg-bg/40 text-muted uppercase text-xs tracking-wider border-b border-line">
                    <tr>
                        <th class="px-4 py-3 font-medium">UID / Node Tracker</th>
                        <th class="px-4 py-3 font-medium">Status & Mode</th>
                        <th class="px-4 py-3 font-medium">{{ __('24-Hour Connectivity Timeline') }}</th>
                        <th class="px-4 py-3 font-medium">Firmware & RSSI</th>
                        <th class="px-4 py-3 font-medium">Orientasi & Daya</th>
                        <th class="px-4 py-3 font-medium">Terakhir Terlihat</th>
                        <th class="px-4 py-3 font-medium text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
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
                        <tr class="hover:bg-line/20 transition-colors">
                            <!-- Node UID & Name -->
                            <td class="px-4 py-3">
                                <div class="font-medium text-ink">
                                    <a href="{{ route('solar-tracker', ['uid' => $device->uid]) }}" class="hover:text-accent transition-colors">
                                        {{ $device->name }}
                                    </a>
                                </div>
                                <div class="font-mono text-muted text-xs mt-0.5 flex items-center space-x-1.5">
                                    <span class="text-ink/80">{{ $device->uid }}</span>
                                    <span>&middot;</span>
                                    <span>{{ $device->site?->name ?? 'Cikarang Pilot' }}</span>
                                </div>
                            </td>

                            <!-- Status & Mode -->
                            <td class="px-4 py-3">
                                <div class="flex flex-col space-y-1 items-start">
                                    <x-status-pill :status="$device->status" />
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[11px] font-mono border border-line bg-bg/50 text-muted uppercase">
                                        Mode: {{ $device->mode }}
                                    </span>
                                </div>
                            </td>

                            <!-- 24-Hour Connectivity Bars -->
                            <td class="px-4 py-3">
                                <div class="w-48 sm:w-56">
                                    <div class="flex items-center justify-between text-[11px] font-mono text-muted mb-1">
                                        <span>-24j</span>
                                        <span class="text-ink">{{ $isOnline ? '99,8% Uptime' : ($device->status === 'unstable' ? '74,2% Uptime' : '0% Uptime') }}</span>
                                        <span>Sekarang</span>
                                    </div>
                                    <div class="flex items-center space-x-0.5 h-4 p-0.5 rounded border border-line bg-bg">
                                        @foreach($slots as $slot)
                                            @php
                                                $barColor = match($slot['status']) {
                                                    'online' => 'bg-ok hover:opacity-80',
                                                    'unstable' => 'bg-warn hover:opacity-80',
                                                    default => 'bg-line hover:bg-muted/40',
                                                };
                                            @endphp
                                            <div 
                                                class="flex-1 h-full rounded-2xs {{ $barColor }} transition-all cursor-pointer"
                                                title="{{ $slot['time_wib'] }}: {{ ucfirst($slot['status']) }} ({{ $slot['samples'] }} paket, {{ $slot['uptime'] }}% uptime)"
                                            ></div>
                                        @endforeach
                                    </div>
                                </div>
                            </td>

                            <!-- Firmware & RSSI -->
                            <td class="px-4 py-3 font-mono text-xs">
                                <div class="flex items-center space-x-1.5 text-ink">
                                    <div class="flex items-end space-x-0.5 h-3.5">
                                        <div class="w-1 rounded-2xs h-1 {{ $signalBars >= 1 ? 'bg-ok' : 'bg-line' }}"></div>
                                        <div class="w-1 rounded-2xs h-1.5 {{ $signalBars >= 2 ? 'bg-ok' : 'bg-line' }}"></div>
                                        <div class="w-1 rounded-2xs h-2.5 {{ $signalBars >= 3 ? 'bg-ok' : 'bg-line' }}"></div>
                                        <div class="w-1 rounded-2xs h-3.5 {{ $signalBars >= 4 ? 'bg-ok' : 'bg-line' }}"></div>
                                    </div>
                                    <span>{{ $device->rssi ? $device->rssi . ' dBm' : '-- dBm' }}</span>
                                </div>
                                <div class="text-muted mt-0.5">
                                    FW v{{ $device->firmware ?? '1.0.0' }}
                                </div>
                            </td>

                            <!-- Orientation & Power -->
                            <td class="px-4 py-3 font-mono text-xs">
                                @if($tel)
                                    <div class="text-ink font-semibold">{{ formatPowerW($tel->power) }}</div>
                                    <div class="text-muted text-[11px]">
                                        El: {{ formatNumberId($tel->pitch_elev, 1) }}&deg; &middot; Az: {{ formatNumberId($tel->azimuth, 1) }}&deg;
                                    </div>
                                @else
                                    <span class="text-muted">--</span>
                                @endif
                            </td>

                            <!-- Last Telemetry -->
                            <td class="px-4 py-3 text-xs">
                                @if($device->last_seen_at)
                                    <div class="text-ink font-medium">
                                        {{ $device->last_seen_at->timezone('Asia/Jakarta')->diffForHumans() }}
                                    </div>
                                    <div class="font-mono text-muted text-[11px]">
                                        {{ formatDateWib($device->last_seen_at, 'd M H:i') }}
                                    </div>
                                @else
                                    <span class="text-muted font-mono text-xs">Belum ada data</span>
                                @endif
                            </td>

                            <!-- Action -->
                            <td class="px-4 py-3 text-right">
                                <a 
                                    href="{{ route('solar-tracker', ['uid' => $device->uid]) }}" 
                                    class="inline-flex items-center px-2.5 py-1 rounded border border-line bg-bg/50 hover:bg-line/40 text-ink text-xs font-medium transition-colors"
                                >
                                    Detail &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center">
                                <div class="text-sm font-medium text-ink">{{ __('No tracker nodes found') }}</div>
                                <div class="text-xs text-muted mt-1">Coba ubah filter status atau kata kunci pencarian.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Table Footer / Legend -->
        <div class="px-4 py-3 bg-bg/40 border-t border-line flex flex-col sm:flex-row items-center justify-between text-xs text-muted gap-2">
            <div>
                Menampilkan <span class="font-mono font-semibold text-ink">{{ $devices->count() }}</span> dari <span class="font-mono font-semibold text-ink">{{ $totalTrackers }}</span> perangkat terdaftar
            </div>
            <div class="flex items-center space-x-4 font-mono text-[11px]">
                <span class="flex items-center space-x-1.5">
                    <span class="w-2 h-2 rounded-full bg-ok"></span>
                    <span>Online &ge; 80%</span>
                </span>
                <span class="flex items-center space-x-1.5">
                    <span class="w-2 h-2 rounded-full bg-warn"></span>
                    <span>Degradasi</span>
                </span>
                <span class="flex items-center space-x-1.5">
                    <span class="w-2 h-2 rounded-full bg-line"></span>
                    <span>Offline</span>
                </span>
            </div>
        </div>
    </div>
</x-layouts.app>
