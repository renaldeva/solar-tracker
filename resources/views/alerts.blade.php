<x-layouts.app :title="__('Peringatan')">
    <x-page-header 
        :title="__('Alerts & Diagnostics')" 
        :subtitle="__('Evaluasi kondisi real-time, interlock keselamatan perangkat keras, dan diagnostik sensor.')"
        :badge="__('SCADA Interlocks')"
    >
        <x-slot:actions>
            <div class="flex items-center space-x-2">
                <span class="inline-flex items-center px-3 py-1.5 rounded-xl text-xs font-mono font-semibold border border-emerald-200 text-emerald-700 bg-emerald-50 shadow-2xs">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-2 animate-pulse"></span>
                    {{ __('ENGINE AKTIF (Histeresis Auto-Resolve)') }}
                </span>
            </div>
        </x-slot:actions>
    </x-page-header>

    @if(session('success'))
        <div class="mb-5 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center justify-between shadow-xs">
            <div class="flex items-center space-x-2.5">
                <svg class="w-5 h-5 shrink-0 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span class="font-medium">{{ session('success') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-emerald-700 hover:opacity-75 cursor-pointer text-lg leading-none">&times;</button>
        </div>
    @endif

    {{-- Toast feedback container --}}
    <div id="alertToast" class="hidden mb-5 p-4 rounded-2xl text-sm flex items-center justify-between border shadow-xs transition-all">
        <div class="flex items-center space-x-2.5" id="alertToastContent"></div>
        <button onclick="document.getElementById('alertToast').classList.add('hidden')" class="text-muted hover:text-ink cursor-pointer text-lg leading-none">&times;</button>
    </div>

    <!-- Status Banner: "Apakah semuanya baik-baik saja?" -->
    <div class="mb-6 p-4 rounded-2xl bg-white border border-line shadow-xs flex items-center justify-between">
        <div class="flex items-center space-x-3.5">
            <div class="w-3.5 h-3.5 rounded-full {{ $kpis['critical_open'] > 0 ? 'bg-rose-500 ring-4 ring-rose-50' : ($kpis['active_total'] > 0 ? 'bg-amber-500 ring-4 ring-amber-50' : 'bg-emerald-500 ring-4 ring-emerald-50') }}"></div>
            <div>
                <h2 class="text-sm font-semibold text-ink">
                    @if($kpis['critical_open'] > 0)
                        Perhatian Kritis Diperlukan ({{ $kpis['critical_open'] }} Isu Kritis)
                    @elseif($kpis['active_total'] > 0)
                        Peringatan Aktif Memerlukan Tinjauan ({{ $kpis['active_total'] }} Peringatan)
                    @else
                        Semua Sistem Normal — Tidak Ada Peringatan Aktif
                    @endif
                </h2>
                <p class="text-xs text-muted mt-0.5">
                    @if($kpis['critical_open'] > 0)
                        Terdapat interlock keselamatan aktif. Aktuator mungkin ditahan pada posisi aman (stow).
                    @elseif($kpis['active_total'] > 0)
                        Sistem pelacak tetap beroperasi. Harap tinjau indikasi drift atau konektivitas sensor.
                    @else
                        Seluruh sensor PZEM, BNO055 IMU, anemometer, dan aktuator BTS7960 bekerja dalam batas normal.
                    @endif
                </p>
            </div>
        </div>
        <div class="hidden sm:flex items-center space-x-2">
            <span class="px-3 py-1 rounded-xl text-xs font-mono font-semibold {{ $kpis['critical_open'] > 0 ? 'bg-rose-50 text-rose-700 border border-rose-200' : ($kpis['active_total'] > 0 ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200') }}">
                {{ $kpis['critical_open'] > 0 ? 'KRITIS' : ($kpis['active_total'] > 0 ? 'PERINGATAN' : 'NOMINAL') }}
            </span>
        </div>
    </div>

    {{-- 4 KPI Cards (Smart Nursery Style) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <x-kpi-card 
            :title="__('Peringatan Aktif')" 
            :value="$kpis['active_total']" 
            :tone="$kpis['active_total'] > 0 ? 'warn' : 'default'"
            :badge="$kpis['active_total'] > 0 ? 'AKTIF' : 'NOMINAL'"
            :subtitle="__('Terbuka atau dalam penanganan')"
        />

        <x-kpi-card 
            :title="__('Prioritas Kritis')" 
            :value="$kpis['critical_open']" 
            :tone="$kpis['critical_open'] > 0 ? 'danger' : 'default'"
            :badge="$kpis['critical_open'] > 0 ? 'KRITIS' : 'AMAN'"
            :subtitle="__('Proteksi stow / aktuator henti')"
        />

        <x-kpi-card 
            :title="__('Peringatan Terbuka')" 
            :value="$kpis['warning_open']" 
            :tone="$kpis['warning_open'] > 0 ? 'warn' : 'default'"
            :badge="$kpis['warning_open'] > 0 ? 'PERINGATAN' : 'NORMAL'"
            :subtitle="__('IMU, komunikasi, drift RTC')"
        />

        <x-kpi-card 
            :title="__('Selesai (24 Jam)')" 
            :value="$kpis['resolved_24h']" 
            tone="ok"
            badge="TERATASI"
            :subtitle="__('Otomatis atau oleh operator')"
        />
    </div>

    {{-- Filter Card --}}
    <div class="bg-panel border border-line rounded-2xl p-4 sm:p-5 mb-6 shadow-xs">
        <form method="GET" action="{{ route('alerts') }}" class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            {{-- Status Tabs --}}
            <div class="flex items-center space-x-1.5 overflow-x-auto pb-1 md:pb-0">
                @php
                    $curStatus = $filters['status'] ?? 'all';
                @endphp
                <a href="{{ route('alerts', array_merge(request()->except('status', 'page'), ['status' => 'all'])) }}" 
                   class="px-3.5 py-2 rounded-xl text-xs font-semibold transition-all {{ $curStatus === 'all' ? 'bg-blue-600 text-white shadow-sm' : 'bg-panel text-muted hover:text-ink border border-line' }}">
                    {{ __('Semua') }}
                </a>
                <a href="{{ route('alerts', array_merge(request()->except('status', 'page'), ['status' => 'active'])) }}" 
                   class="px-3.5 py-2 rounded-xl text-xs font-semibold transition-all {{ $curStatus === 'active' ? 'bg-blue-600 text-white shadow-sm' : 'bg-panel text-muted hover:text-ink border border-line' }}">
                    {{ __('Aktif') }}
                </a>
                <a href="{{ route('alerts', array_merge(request()->except('status', 'page'), ['status' => 'open'])) }}" 
                   class="px-3.5 py-2 rounded-xl text-xs font-semibold transition-all {{ $curStatus === 'open' ? 'bg-blue-600 text-white shadow-sm' : 'bg-panel text-muted hover:text-ink border border-line' }}">
                    {{ __('Terbuka') }}
                </a>
                <a href="{{ route('alerts', array_merge(request()->except('status', 'page'), ['status' => 'acknowledged'])) }}" 
                   class="px-3.5 py-2 rounded-xl text-xs font-semibold transition-all {{ $curStatus === 'acknowledged' ? 'bg-blue-600 text-white shadow-sm' : 'bg-panel text-muted hover:text-ink border border-line' }}">
                    {{ __('Diakui') }}
                </a>
                <a href="{{ route('alerts', array_merge(request()->except('status', 'page'), ['status' => 'resolved'])) }}" 
                   class="px-3.5 py-2 rounded-xl text-xs font-semibold transition-all {{ $curStatus === 'resolved' ? 'bg-blue-600 text-white shadow-sm' : 'bg-panel text-muted hover:text-ink border border-line' }}">
                    {{ __('Selesai') }}
                </a>
            </div>

            {{-- Severity, Device, and Search Controls --}}
            <div class="flex flex-wrap items-center gap-2">
                <input type="hidden" name="status" value="{{ $filters['status'] ?? 'all' }}">

                {{-- Severity Filter --}}
                <div class="relative">
                    <select name="severity" onchange="this.form.submit()" class="appearance-none h-10 pl-3 pr-8 py-1.5 bg-panel border border-line rounded-xl text-ink text-xs font-semibold focus:outline-none focus:border-blue-500 shadow-2xs">
                        <option value="all" {{ ($filters['severity'] ?? 'all') === 'all' ? 'selected' : '' }}>{{ __('Semua Tingkat') }}</option>
                        <option value="critical" {{ ($filters['severity'] ?? '') === 'critical' ? 'selected' : '' }}>{{ __('Hanya Kritis') }}</option>
                        <option value="warning" {{ ($filters['severity'] ?? '') === 'warning' ? 'selected' : '' }}>{{ __('Hanya Peringatan') }}</option>
                        <option value="info" {{ ($filters['severity'] ?? '') === 'info' ? 'selected' : '' }}>{{ __('Hanya Info') }}</option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-muted">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                </div>

                {{-- Device Filter --}}
                <div class="relative">
                    <select name="device_id" onchange="this.form.submit()" class="appearance-none h-10 pl-3 pr-8 py-1.5 bg-panel border border-line rounded-xl text-ink text-xs font-semibold focus:outline-none focus:border-blue-500 shadow-2xs">
                        <option value="all" {{ ($filters['device_id'] ?? 'all') === 'all' ? 'selected' : '' }}>{{ __('Semua Node Tracker') }}</option>
                        @foreach($devices as $dev)
                            <option value="{{ $dev->id }}" {{ (string)($filters['device_id'] ?? '') === (string)$dev->id ? 'selected' : '' }}>
                                {{ $dev->uid }} ({{ $dev->name }})
                            </option>
                        @endforeach
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-muted">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                </div>

                {{-- Text Search --}}
                <div class="relative">
                    <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="{{ __('Cari kode, pesan...') }}" 
                           class="w-48 sm:w-56 h-10 pl-9 pr-3 py-1.5 bg-panel border border-line rounded-xl text-ink placeholder-muted focus:outline-none focus:border-blue-500 text-xs shadow-2xs">
                    <svg class="w-4 h-4 text-muted absolute left-3 top-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>

                <button type="submit" class="h-10 px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl transition-colors shadow-xs cursor-pointer">
                    {{ __('Filter') }}
                </button>

                @if(!empty($filters['q']) || ($filters['severity'] ?? 'all') !== 'all' || ($filters['device_id'] ?? 'all') !== 'all' || ($filters['status'] ?? 'all') !== 'all')
                    <a href="{{ route('alerts') }}" class="h-10 px-3 py-1.5 border border-line text-muted hover:text-ink text-xs font-medium rounded-xl transition-colors flex items-center bg-panel">
                        {{ __('Reset') }}
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Alerts Table Card --}}
    <div class="bg-panel border border-line rounded-2xl overflow-hidden mb-6 shadow-xs">
        <div class="p-4 sm:p-5 border-b border-line flex flex-col sm:flex-row sm:items-center justify-between gap-2 bg-panel-alt/60">
            <div>
                <h3 class="text-sm font-semibold text-ink">{{ __('Log Peringatan Sistem') }}</h3>
                <p class="text-xs text-muted mt-0.5">{{ __('Riwayat lengkap SCADA interlock, kendala perangkat keras, dan transisi kondisi.') }}</p>
            </div>
            <div class="text-xs font-mono text-muted">
                {{ __('Halaman :current dari :total (:count total peringatan)', [
                    'current' => $alerts->currentPage(),
                    'total' => $alerts->lastPage(),
                    'count' => $alerts->total()
                ]) }}
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-panel-alt text-muted uppercase text-[11px] font-semibold tracking-wider border-b border-line">
                    <tr>
                        <th class="py-3 px-4">{{ __('Tingkat') }}</th>
                        <th class="py-3 px-4">{{ __('Kode & Pesan') }}</th>
                        <th class="py-3 px-4">{{ __('Node Perangkat') }}</th>
                        <th class="py-3 px-4">{{ __('Waktu Terpicu') }}</th>
                        <th class="py-3 px-4">{{ __('Status') }}</th>
                        <th class="py-3 px-4 text-right">{{ __('Aksi') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line" id="alertsTableBody">
                    @forelse($alerts as $alert)
                        <tr class="hover:bg-panel-alt/50 transition-colors" id="alert-row-{{ $alert->id }}">
                            {{-- Severity Badge (dot + text) --}}
                            <td class="py-3.5 px-4 align-top whitespace-nowrap">
                                @if($alert->severity === 'critical')
                                    <span class="inline-flex items-center space-x-1.5 text-rose-600 dark:text-rose-400 font-mono text-xs font-semibold">
                                        <span class="w-2 h-2 rounded-full bg-rose-500 {{ $alert->status === 'open' ? 'animate-pulse' : '' }}"></span>
                                        <span>KRITIS</span>
                                    </span>
                                @elseif($alert->severity === 'warning')
                                    <span class="inline-flex items-center space-x-1.5 text-amber-600 dark:text-amber-400 font-mono text-xs font-semibold">
                                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                        <span>PERINGATAN</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center space-x-1.5 text-blue-600 dark:text-blue-400 font-mono text-xs font-semibold">
                                        <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                                        <span>INFO</span>
                                    </span>
                                @endif
                            </td>

                            {{-- Code & Message --}}
                            <td class="py-3.5 px-4 align-top">
                                <div class="flex items-center space-x-2">
                                    <span class="font-mono text-xs text-ink px-2 py-0.5 rounded-md border border-line bg-panel-alt font-semibold">
                                        {{ $alert->code }}
                                    </span>
                                </div>
                                <div class="text-ink text-sm mt-1 leading-snug font-medium">
                                    {{ $alert->display_message }}
                                </div>
                                @if(!empty($alert->metadata))
                                    <div class="flex flex-wrap gap-1 mt-1.5">
                                        @foreach($alert->metadata as $mKey => $mVal)
                                            @if(is_scalar($mVal))
                                                <span class="inline-flex items-center text-[11px] bg-panel-alt text-muted border border-line px-2 py-0.5 rounded-md font-mono">
                                                    {{ $mKey }}: {{ is_bool($mVal) ? ($mVal ? 'true' : 'false') : $mVal }}
                                                </span>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif
                            </td>

                            {{-- Device & Site --}}
                            <td class="py-3.5 px-4 align-top whitespace-nowrap">
                                <div class="font-semibold text-ink">
                                    <a href="{{ route('solar-tracker', ['uid' => $alert->device?->uid]) }}" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                                        {{ $alert->device?->uid ?? 'N/A' }}
                                    </a>
                                </div>
                                <div class="text-xs text-muted mt-0.5">
                                    {{ $alert->device?->site?->name ?? 'Default Site' }}
                                </div>
                            </td>

                            {{-- Time --}}
                            <td class="py-3.5 px-4 align-top whitespace-nowrap font-mono text-xs">
                                <div class="text-ink font-medium">
                                    {{ formatDateWib($alert->opened_at, 'd M Y, H:i') }}
                                </div>
                                <div class="text-muted text-[11px] mt-0.5">
                                    {{ $alert->opened_at?->diffForHumans() }}
                                </div>
                            </td>

                            {{-- Status Cell --}}
                            <td class="py-3.5 px-4 align-top whitespace-nowrap" id="status-cell-{{ $alert->id }}">
                                @if($alert->status === 'open')
                                    <span class="inline-flex items-center space-x-1.5 text-rose-600 dark:text-rose-400 font-mono text-xs font-semibold">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                        <span>Terbuka</span>
                                    </span>
                                @elseif($alert->status === 'acknowledged')
                                    <div>
                                        <span class="inline-flex items-center space-x-1.5 text-purple-600 dark:text-purple-400 font-mono text-xs font-semibold">
                                            <span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span>
                                            <span>Diakui</span>
                                        </span>
                                        <div class="text-xs text-muted mt-0.5">
                                            {{ __('oleh :name', ['name' => $alert->acknowledgedBy?->name ?? 'Operator']) }}
                                        </div>
                                    </div>
                                @else
                                    <div>
                                        <span class="inline-flex items-center space-x-1.5 text-emerald-600 dark:text-emerald-400 font-mono text-xs font-semibold">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            <span>Selesai</span>
                                        </span>
                                        <div class="text-xs text-muted mt-0.5 font-mono">
                                            {{ formatTimeWib($alert->resolved_at) }}
                                        </div>
                                    </div>
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td class="py-3.5 px-4 align-top text-right whitespace-nowrap" id="action-cell-{{ $alert->id }}">
                                @if(auth()->user()->canControl())
                                    <div class="inline-flex items-center space-x-1.5">
                                        @if($alert->status === 'open')
                                            <button type="button" 
                                                    onclick="handleAlertAction('acknowledge', {{ $alert->id }})"
                                                    class="h-8 px-3 py-1 bg-purple-50 dark:bg-purple-950/40 hover:bg-purple-100 dark:hover:bg-purple-900/40 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-800/60 rounded-lg text-xs font-semibold transition-colors cursor-pointer shadow-2xs">
                                                {{ __('Acknowledge') }}
                                            </button>
                                        @endif

                                        @if($alert->status !== 'resolved')
                                            <button type="button" 
                                                    onclick="handleAlertAction('resolve', {{ $alert->id }})"
                                                    class="h-8 px-3 py-1 bg-emerald-50 dark:bg-emerald-950/40 hover:bg-emerald-100 dark:hover:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/60 rounded-lg text-xs font-semibold transition-colors cursor-pointer shadow-2xs">
                                                {{ __('Resolve') }}
                                            </button>
                                        @else
                                            <span class="text-xs text-muted font-mono inline-flex items-center font-medium">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5"></span>
                                                {{ __('Selesai') }}
                                            </span>
                                        @endif
                                    </div>
                                @else
                                    <span class="inline-flex items-center text-xs text-muted font-mono italic">
                                        {{ __('Hanya Baca') }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-muted">
                                <div class="w-12 h-12 rounded-2xl bg-panel-alt text-muted mx-auto flex items-center justify-center mb-3">
                                    <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="text-sm font-semibold text-ink">{{ __('Semua Sistem Normal') }}</div>
                                <div class="text-xs text-muted mt-1 max-w-sm mx-auto">
                                    {{ __('Tidak ada peringatan aktif yang sesuai filter. Seluruh interlock aman.') }}
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($alerts->hasPages())
            <div class="p-4 border-t border-line bg-panel-alt/60">
                {{ $alerts->links() }}
            </div>
        @endif
    </div>

    @push('scripts')
    <script>
        const CSRF_TOKEN = '{{ csrf_token() }}';

        async function handleAlertAction(action, alertId) {
            const url = action === 'acknowledge' 
                ? `/alerts/${alertId}/acknowledge` 
                : `/alerts/${alertId}/resolve`;

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    showToast(data.message, 'success');
                    updateRowVisuals(alertId, action, data);
                } else {
                    showToast(data.message || 'Gagal memproses aksi peringatan.', 'danger');
                }
            } catch (err) {
                console.error(err);
                showToast('Terjadi kendala jaringan saat memproses peringatan.', 'danger');
            }
        }

        function updateRowVisuals(alertId, action, data) {
            const statusCell = document.getElementById(`status-cell-${alertId}`);
            const actionCell = document.getElementById(`action-cell-${alertId}`);

            if (action === 'acknowledge') {
                if (statusCell) {
                    statusCell.innerHTML = `
                        <div>
                            <span class="inline-flex items-center space-x-1.5 text-purple-600 font-mono text-xs font-semibold">
                                <span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span>
                                <span>Diakui</span>
                            </span>
                            <div class="text-xs text-muted mt-0.5">
                                oleh ${data.acknowledged_by || 'Operator'}
                            </div>
                        </div>
                    `;
                }
                if (actionCell) {
                    actionCell.innerHTML = `
                        <div class="inline-flex items-center space-x-1.5">
                            <button type="button" 
                                    onclick="handleAlertAction('resolve', ${alertId})"
                                    class="h-8 px-3 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 rounded-lg text-xs font-semibold transition-colors cursor-pointer shadow-2xs">
                                Resolve
                            </button>
                        </div>
                    `;
                }
            } else if (action === 'resolve') {
                if (statusCell) {
                    statusCell.innerHTML = `
                        <div>
                            <span class="inline-flex items-center space-x-1.5 text-emerald-600 font-mono text-xs font-semibold">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                <span>Selesai</span>
                            </span>
                            <div class="text-xs text-muted mt-0.5 font-mono">
                                ${data.resolved_at || 'WIB'}
                            </div>
                        </div>
                    `;
                }
                if (actionCell) {
                    actionCell.innerHTML = `
                        <span class="text-xs text-muted font-mono inline-flex items-center font-medium">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5"></span>
                            Selesai
                        </span>
                    `;
                }
            }
        }

        function showToast(message, type) {
            const toast = document.getElementById('alertToast');
            const content = document.getElementById('alertToastContent');
            if (!toast || !content) return;

            toast.className = `mb-5 p-4 rounded-2xl text-sm flex items-center justify-between border shadow-xs ${
                type === 'success' 
                    ? 'bg-emerald-50 border-emerald-200 text-emerald-800' 
                    : 'bg-rose-50 border-rose-200 text-rose-800'
            }`;

            content.innerHTML = `
                <svg class="w-5 h-5 shrink-0 ${type === 'success' ? 'text-emerald-600' : 'text-rose-600'}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${type === 'success' ? 'M5 13l4 4L19 7' : 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'}" />
                </svg>
                <span class="font-medium">${message}</span>
            `;

            toast.classList.remove('hidden');
            setTimeout(() => {
                toast.classList.add('hidden');
            }, 5000);
        }
    </script>
    @endpush
</x-layouts.app>
