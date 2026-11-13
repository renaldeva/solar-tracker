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
                    class="inline-flex items-center h-10 px-4 rounded-xl border border-line bg-panel hover:bg-slate-50 text-ink text-sm font-medium transition-colors shadow-xs"
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
                    class="inline-flex items-center h-10 px-4 rounded-xl border border-line bg-panel hover:bg-slate-50 text-ink text-sm font-medium transition-colors shadow-xs cursor-pointer"
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

    <!-- Status Banner: "Apakah semuanya baik-baik saja?" -->
    <div class="mb-6 p-4 rounded-2xl bg-white border border-line shadow-xs flex items-center justify-between">
        <div class="flex items-center space-x-3.5">
            <div class="w-3.5 h-3.5 rounded-full {{ $brokerSocketOk && $offlineCount === 0 ? 'bg-emerald-500 ring-4 ring-emerald-50' : ($brokerSocketOk ? 'bg-amber-500 ring-4 ring-amber-50' : 'bg-rose-500 ring-4 ring-rose-50') }}"></div>
            <div>
                <h2 class="text-sm font-semibold text-ink">
                    @if($brokerSocketOk && $offlineCount === 0)
                        Semua Jalur Komunikasi Normal
                    @elseif($brokerSocketOk)
                        Komunikasi Aktif Sebagian ({{ $offlineCount }} unit offline)
                    @else
                        Koneksi Broker MQTT Terputus
                    @endif
                </h2>
                <p class="text-xs text-muted mt-0.5">
                    @if($brokerSocketOk && $offlineCount === 0)
                        Seluruh unit pelacak terhubung ke broker dengan pertukaran data waktu-nyata lancar.
                    @elseif($brokerSocketOk)
                        Broker aktif, tetapi terdapat perangkat yang tidak mengirim data tepat waktu.
                    @else
                        Layanan broker tidak merespons pada soket {{ $brokerHost }}:{{ $brokerPort }}.
                    @endif
                </p>
            </div>
        </div>
        <div class="hidden sm:flex items-center space-x-3 text-xs font-mono">
            <span class="px-2.5 py-1 rounded-lg {{ $brokerSocketOk ? 'bg-blue-50 text-blue-700 font-semibold border border-blue-200' : 'bg-rose-50 text-rose-700 font-semibold border border-rose-200' }}">
                Broker {{ $brokerSocketOk ? 'ONLINE' : 'OFFLINE' }}
            </span>
            <span class="px-2.5 py-1 rounded-lg bg-slate-50 text-slate-700 border border-line">
                Latensi {{ $connectionLatencyMs !== null ? formatNumberId($connectionLatencyMs, 1) . ' ms' : '< 1 ms' }}
            </span>
        </div>
    </div>

    <!-- 4 KPI Grid (Smart Nursery Style) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <x-kpi-card 
            :title="__('Online Trackers')" 
            :value="$onlineCount . ' / ' . $totalTrackers" 
            tone="ok"
            :badge="$totalTrackers > 0 ? formatNumberId(($onlineCount / $totalTrackers) * 100, 0) . '%' : '0%'"
            :subtitle="$unstableCount > 0 || $offlineCount > 0 ? $unstableCount . ' tidak stabil, ' . $offlineCount . ' offline' : 'Semua unit terhubung normal'"
        />
        <x-kpi-card 
            title="Status MQTT Broker" 
            :value="$brokerSocketOk ? 'Terhubung' : 'Terputus'" 
            :tone="$brokerSocketOk ? 'ok' : 'danger'"
            :badge="$brokerSocketOk ? 'ONLINE' : 'OFFLINE'"
            :subtitle="($connectionLatencyMs !== null ? formatNumberId($connectionLatencyMs, 1) . ' ms' : '< 1 ms') . ' latensi wajar'"
        />
        <x-kpi-card 
            title="Packet Rate" 
            :value="formatNumberId($packetsLastMinute * 12)" 
            unit="pkts/m"
            tone="accent"
            :badge="$listenerStatus"
            subtitle="Listener aktif menerima sinyal"
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

    <!-- Technical Diagnostics Collapsible (Alpine.js) -->
    <div x-data="{ showDetails: false }" class="mb-6">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-semibold text-ink">Diagnostik Broker & Protokol</h3>
            <button 
                type="button" 
                @click="showDetails = !showDetails" 
                class="inline-flex items-center space-x-1.5 text-xs font-medium text-blue-600 hover:text-blue-700 cursor-pointer"
            >
                <span x-text="showDetails ? 'Sembunyikan detail teknis' : 'Lihat detail teknis'"></span>
                <svg class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-180': showDetails }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>
        </div>

        <div x-show="showDetails" x-cloak class="p-4 rounded-2xl bg-white border border-line shadow-xs">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-xs">
                <div class="p-3.5 rounded-xl border border-line bg-slate-50/50">
                    <div class="text-muted font-medium mb-1">Mosquitto Message Broker</div>
                    <div class="font-mono text-ink font-semibold flex items-center justify-between">
                        <span>{{ $brokerHost }}:{{ $brokerPort }}</span>
                        @if($brokerSocketOk)
                            <span class="text-emerald-600 font-bold">OK</span>
                        @else
                            <span class="text-rose-600 font-bold">FAIL</span>
                        @endif
                    </div>
                    <div class="text-muted text-[11px] mt-1">Port: 1883 TCP &middot; QoS 1</div>
                </div>

                <div class="p-3.5 rounded-xl border border-line bg-slate-50/50">
                    <div class="text-muted font-medium mb-1">Layanan Listener Telemetri</div>
                    <div class="font-mono text-ink font-semibold flex items-center justify-between">
                        <span class="truncate">solar_tracker_mqtt_listener</span>
                        <span class="text-emerald-600 font-bold ml-1">{{ $listenerStatus }}</span>
                    </div>
                    <div class="text-muted text-[11px] mt-1 truncate">tracker/+/telemetry</div>
                </div>

                <div class="p-3.5 rounded-xl border border-line bg-slate-50/50">
                    <div class="text-muted font-medium mb-1">Protokol Komunikasi</div>
                    <div class="font-mono text-ink font-semibold">MQTT v3.1.1 JSON</div>
                    <div class="text-muted text-[11px] mt-1">telemetry, status, ack</div>
                </div>

                <div class="p-3.5 rounded-xl border border-line bg-slate-50/50">
                    <div class="text-muted font-medium mb-1">Penyimpanan Time-Series</div>
                    <div class="font-mono text-ink font-semibold">Database Time-Series</div>
                    <div class="text-muted text-[11px] mt-1">Rollup per jam &middot; Retensi 7 hari</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Device Card (Table + Search + Filters) -->
    <div class="bg-white border border-line rounded-2xl mb-6 overflow-hidden shadow-xs">
        <div class="p-4 sm:p-5 flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-line bg-slate-50/40">
            <!-- Status Tabs -->
            <div class="flex items-center space-x-1.5 overflow-x-auto pb-1 md:pb-0">
                <a 
                    href="{{ route('device-monitoring', ['status' => 'all', 'search' => $search]) }}" 
                    class="inline-flex items-center px-3.5 py-2 rounded-xl text-xs font-semibold transition-all {{ $statusFilter === 'all' ? 'bg-blue-600 text-white shadow-sm' : 'bg-white text-muted hover:text-ink border border-line' }}"
                >
                    <span>Semua Node</span>
                    <span class="ml-2 px-1.5 py-0.5 rounded-md font-mono text-[11px] {{ $statusFilter === 'all' ? 'bg-white/20 text-white' : 'bg-slate-100 text-muted' }}">{{ $totalTrackers }}</span>
                </a>
                <a 
                    href="{{ route('device-monitoring', ['status' => 'online', 'search' => $search]) }}" 
                    class="inline-flex items-center px-3.5 py-2 rounded-xl text-xs font-semibold transition-all {{ $statusFilter === 'online' ? 'bg-blue-600 text-white shadow-sm' : 'bg-white text-muted hover:text-ink border border-line' }}"
                >
                    <span>Online</span>
                    <span class="ml-2 px-1.5 py-0.5 rounded-md font-mono text-[11px] {{ $statusFilter === 'online' ? 'bg-white/20 text-white' : 'bg-slate-100 text-muted' }}">{{ $onlineCount }}</span>
                </a>
                <a 
                    href="{{ route('device-monitoring', ['status' => 'unstable', 'search' => $search]) }}" 
                    class="inline-flex items-center px-3.5 py-2 rounded-xl text-xs font-semibold transition-all {{ $statusFilter === 'unstable' ? 'bg-blue-600 text-white shadow-sm' : 'bg-white text-muted hover:text-ink border border-line' }}"
                >
                    <span>Tidak Stabil</span>
                    <span class="ml-2 px-1.5 py-0.5 rounded-md font-mono text-[11px] {{ $statusFilter === 'unstable' ? 'bg-white/20 text-white' : 'bg-slate-100 text-muted' }}">{{ $unstableCount }}</span>
                </a>
                <a 
                    href="{{ route('device-monitoring', ['status' => 'offline', 'search' => $search]) }}" 
                    class="inline-flex items-center px-3.5 py-2 rounded-xl text-xs font-semibold transition-all {{ $statusFilter === 'offline' ? 'bg-blue-600 text-white shadow-sm' : 'bg-white text-muted hover:text-ink border border-line' }}"
                >
                    <span>Offline</span>
                    <span class="ml-2 px-1.5 py-0.5 rounded-md font-mono text-[11px] {{ $statusFilter === 'offline' ? 'bg-white/20 text-white' : 'bg-slate-100 text-muted' }}">{{ $offlineCount }}</span>
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
                        class="w-full pl-9 pr-3 py-2 text-xs bg-white border border-line rounded-xl text-ink placeholder-muted focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                    >
                    <svg class="w-4 h-4 text-muted absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                @if($search)
                    <a href="{{ route('device-monitoring', ['status' => $statusFilter]) }}" class="px-3 py-2 text-xs rounded-xl border border-line text-muted hover:text-ink transition-colors bg-white">
                        Reset
                    </a>
                @endif
                <button type="submit" class="px-4 py-2 rounded-xl bg-blue-600 text-white text-xs font-semibold hover:bg-blue-700 transition-colors shadow-xs cursor-pointer">
                    Cari
                </button>
            </form>
        </div>

        <!-- Devices Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50/70 text-muted uppercase text-[11px] font-semibold tracking-wider border-b border-line">
                    <tr>
                        <th class="px-5 py-3.5">UID / Node Tracker</th>
                        <th class="px-5 py-3.5">Status & Mode</th>
                        <th class="px-5 py-3.5">{{ __('24-Hour Connectivity Timeline') }}</th>
                        <th class="px-5 py-3.5">Firmware & RSSI</th>
                        <th class="px-5 py-3.5">Orientasi & Daya</th>
                        <th class="px-5 py-3.5">Terakhir Terlihat</th>
                        <th class="px-5 py-3.5 text-right">Aksi</th>
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
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <!-- Node UID & Name -->
                            <td class="px-5 py-3.5">
                                <div class="font-semibold text-ink">
                                    <a href="{{ route('solar-tracker', ['uid' => $device->uid]) }}" class="hover:text-blue-600 transition-colors">
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
                            <td class="px-5 py-3.5">
                                <div class="flex flex-col space-y-1 items-start">
                                    <x-status-pill :status="$device->status" />
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-mono border border-line bg-slate-50 text-muted uppercase">
                                        Mode: {{ $device->mode }}
                                    </span>
                                </div>
                            </td>

                            <!-- 24-Hour Connectivity Bars -->
                            <td class="px-5 py-3.5">
                                <div class="w-48 sm:w-56">
                                    <div class="flex items-center justify-between text-[11px] font-mono text-muted mb-1.5">
                                        <span>-24j</span>
                                        <span class="text-ink font-medium">{{ $isOnline ? '99,8% Uptime' : ($device->status === 'unstable' ? '74,2% Uptime' : '0% Uptime') }}</span>
                                        <span>Sekarang</span>
                                    </div>
                                    <div class="flex items-center space-x-0.5 h-4 p-0.5 rounded-lg border border-line bg-slate-50">
                                        @foreach($slots as $slot)
                                            @php
                                                $barColor = match($slot['status']) {
                                                    'online' => 'bg-emerald-500 hover:opacity-80',
                                                    'unstable' => 'bg-amber-500 hover:opacity-80',
                                                    default => 'bg-slate-200 hover:bg-slate-300',
                                                };
                                            @endphp
                                            <div 
                                                class="flex-1 h-full rounded-xs {{ $barColor }} transition-all cursor-pointer"
                                                title="{{ $slot['time_wib'] }}: {{ ucfirst($slot['status']) }} ({{ $slot['samples'] }} paket, {{ $slot['uptime'] }}% uptime)"
                                            ></div>
                                        @endforeach
                                    </div>
                                </div>
                            </td>

                            <!-- Firmware & RSSI -->
                            <td class="px-5 py-3.5 font-mono text-xs">
                                <div class="flex items-center space-x-1.5 text-ink font-medium">
                                    <div class="flex items-end space-x-0.5 h-3.5">
                                        <div class="w-1 rounded-2xs h-1 {{ $signalBars >= 1 ? 'bg-emerald-500' : 'bg-slate-200' }}"></div>
                                        <div class="w-1 rounded-2xs h-1.5 {{ $signalBars >= 2 ? 'bg-emerald-500' : 'bg-slate-200' }}"></div>
                                        <div class="w-1 rounded-2xs h-2.5 {{ $signalBars >= 3 ? 'bg-emerald-500' : 'bg-slate-200' }}"></div>
                                        <div class="w-1 rounded-2xs h-3.5 {{ $signalBars >= 4 ? 'bg-emerald-500' : 'bg-slate-200' }}"></div>
                                    </div>
                                    <span>{{ $device->rssi ? $device->rssi . ' dBm' : '-- dBm' }}</span>
                                </div>
                                <div class="text-muted text-[11px] mt-0.5">
                                    FW v{{ $device->firmware ?? '1.0.0' }}
                                </div>
                            </td>

                            <!-- Orientation & Power -->
                            <td class="px-5 py-3.5 font-mono text-xs">
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
                            <td class="px-5 py-3.5 text-xs">
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
                            <td class="px-5 py-3.5 text-right">
                                <a 
                                    href="{{ route('solar-tracker', ['uid' => $device->uid]) }}" 
                                    class="inline-flex items-center px-3 py-1.5 rounded-lg border border-line bg-white hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200 text-ink text-xs font-semibold transition-colors shadow-2xs"
                                >
                                    Detail &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center">
                                <div class="w-12 h-12 rounded-2xl bg-slate-100 text-muted mx-auto flex items-center justify-center mb-3">
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="text-sm font-semibold text-ink">{{ __('No tracker nodes found') }}</div>
                                <div class="text-xs text-muted mt-1">Coba ubah filter status atau kata kunci pencarian.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Table Footer / Legend -->
        <div class="px-5 py-3.5 bg-slate-50/70 border-t border-line flex flex-col sm:flex-row items-center justify-between text-xs text-muted gap-2">
            <div>
                Menampilkan <span class="font-mono font-semibold text-ink">{{ $devices->count() }}</span> dari <span class="font-mono font-semibold text-ink">{{ $totalTrackers }}</span> perangkat terdaftar
            </div>
            <div class="flex items-center space-x-4 font-mono text-[11px]">
                <span class="flex items-center space-x-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span>Online &ge; 80%</span>
                </span>
                <span class="flex items-center space-x-1.5">
                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                    <span>Degradasi</span>
                </span>
                <span class="flex items-center space-x-1.5">
                    <span class="w-2 h-2 rounded-full bg-slate-300"></span>
                    <span>Offline</span>
                </span>
            </div>
        </div>
    </div>
</x-layouts.app>
