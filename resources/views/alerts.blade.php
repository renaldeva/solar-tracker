<x-layouts.app :title="__('Peringatan')">
    <x-page-header 
        :title="__('Alerts & Diagnostics')" 
        :subtitle="__('Evaluasi kondisi real-time, interlock keselamatan perangkat keras, dan diagnostik sensor.')"
        :badge="__('SCADA Interlocks')"
    >
        <x-slot:actions>
            <div class="flex items-center space-x-2">
                <span class="inline-flex items-center px-2.5 py-1 rounded text-xs font-mono font-medium border border-ok/30 text-ok bg-ok/10">
                    <span class="w-1.5 h-1.5 rounded-full bg-ok mr-1.5 animate-pulse"></span>
                    {{ __('ENGINE AKTIF (Histeresis Auto-Resolve)') }}
                </span>
            </div>
        </x-slot:actions>
    </x-page-header>

    @if(session('success'))
        <div class="mb-5 p-3 rounded-md bg-ok/10 border border-ok/30 text-ok text-sm flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>{{ session('success') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-ok hover:opacity-75 cursor-pointer">&times;</button>
        </div>
    @endif

    {{-- Toast feedback container --}}
    <div id="alertToast" class="hidden mb-5 p-3 rounded-md text-sm flex items-center justify-between border transition-all">
        <div class="flex items-center space-x-2" id="alertToastContent"></div>
        <button onclick="document.getElementById('alertToast').classList.add('hidden')" class="text-muted hover:text-ink cursor-pointer">&times;</button>
    </div>

    {{-- KPI Cards --}}
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

    {{-- Filter Toolbar --}}
    <div class="bg-panel border border-line rounded-md p-4 mb-6">
        <form method="GET" action="{{ route('alerts') }}" class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            {{-- Status Tabs --}}
            <div class="flex items-center space-x-1 overflow-x-auto">
                <a href="{{ route('alerts', array_merge(request()->except('status', 'page'), ['status' => 'all'])) }}" 
                   class="px-3 py-1.5 rounded-md text-xs font-medium transition-colors {{ ($filters['status'] ?? 'all') === 'all' ? 'bg-accent text-ink' : 'bg-bg text-muted hover:text-ink border border-line' }}">
                    {{ __('Semua') }}
                </a>
                <a href="{{ route('alerts', array_merge(request()->except('status', 'page'), ['status' => 'active'])) }}" 
                   class="px-3 py-1.5 rounded-md text-xs font-medium transition-colors {{ ($filters['status'] ?? '') === 'active' ? 'bg-accent text-ink' : 'bg-bg text-muted hover:text-ink border border-line' }}">
                    {{ __('Aktif') }}
                </a>
                <a href="{{ route('alerts', array_merge(request()->except('status', 'page'), ['status' => 'open'])) }}" 
                   class="px-3 py-1.5 rounded-md text-xs font-medium transition-colors {{ ($filters['status'] ?? '') === 'open' ? 'bg-accent text-ink' : 'bg-bg text-muted hover:text-ink border border-line' }}">
                    {{ __('Terbuka') }}
                </a>
                <a href="{{ route('alerts', array_merge(request()->except('status', 'page'), ['status' => 'acknowledged'])) }}" 
                   class="px-3 py-1.5 rounded-md text-xs font-medium transition-colors {{ ($filters['status'] ?? '') === 'acknowledged' ? 'bg-accent text-ink' : 'bg-bg text-muted hover:text-ink border border-line' }}">
                    {{ __('Diakui') }}
                </a>
                <a href="{{ route('alerts', array_merge(request()->except('status', 'page'), ['status' => 'resolved'])) }}" 
                   class="px-3 py-1.5 rounded-md text-xs font-medium transition-colors {{ ($filters['status'] ?? '') === 'resolved' ? 'bg-accent text-ink' : 'bg-bg text-muted hover:text-ink border border-line' }}">
                    {{ __('Selesai') }}
                </a>
            </div>

            {{-- Severity, Device, and Search Controls --}}
            <div class="flex flex-wrap items-center gap-2">
                <input type="hidden" name="status" value="{{ $filters['status'] ?? 'all' }}">

                {{-- Severity Filter --}}
                <select name="severity" onchange="this.form.submit()" class="h-9 px-2.5 py-1 bg-bg border border-line rounded-md text-ink text-xs font-medium focus:outline-none focus:border-accent">
                    <option value="all" {{ ($filters['severity'] ?? 'all') === 'all' ? 'selected' : '' }}>{{ __('Semua Tingkat') }}</option>
                    <option value="critical" {{ ($filters['severity'] ?? '') === 'critical' ? 'selected' : '' }}>{{ __('Hanya Kritis') }}</option>
                    <option value="warning" {{ ($filters['severity'] ?? '') === 'warning' ? 'selected' : '' }}>{{ __('Hanya Peringatan') }}</option>
                    <option value="info" {{ ($filters['severity'] ?? '') === 'info' ? 'selected' : '' }}>{{ __('Hanya Info') }}</option>
                </select>

                {{-- Device Filter --}}
                <select name="device_id" onchange="this.form.submit()" class="h-9 px-2.5 py-1 bg-bg border border-line rounded-md text-ink text-xs font-medium focus:outline-none focus:border-accent">
                    <option value="all" {{ ($filters['device_id'] ?? 'all') === 'all' ? 'selected' : '' }}>{{ __('Semua Node Tracker') }}</option>
                    @foreach($devices as $dev)
                        <option value="{{ $dev->id }}" {{ (string)($filters['device_id'] ?? '') === (string)$dev->id ? 'selected' : '' }}>
                            {{ $dev->uid }} ({{ $dev->name }})
                        </option>
                    @endforeach
                </select>

                {{-- Text Search --}}
                <div class="relative">
                    <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="{{ __('Cari kode, pesan...') }}" 
                           class="w-48 h-9 pl-8 pr-3 py-1 bg-bg border border-line rounded-md text-ink placeholder-muted focus:outline-none focus:border-accent text-xs">
                    <svg class="w-3.5 h-3.5 text-muted absolute left-2.5 top-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>

                <button type="submit" class="h-9 px-3 py-1 bg-accent hover:bg-blue-600 text-ink text-xs font-medium rounded-md transition-colors cursor-pointer">
                    {{ __('Filter') }}
                </button>

                @if(!empty($filters['q']) || ($filters['severity'] ?? 'all') !== 'all' || ($filters['device_id'] ?? 'all') !== 'all' || ($filters['status'] ?? 'all') !== 'all')
                    <a href="{{ route('alerts') }}" class="h-9 px-2.5 py-1 border border-line text-muted hover:text-ink text-xs rounded-md transition-colors flex items-center">
                        {{ __('Reset') }}
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Alerts Table Panel --}}
    <div class="bg-panel border border-line rounded-md overflow-hidden mb-6">
        <div class="p-4 border-b border-line flex flex-col sm:flex-row sm:items-center justify-between gap-2">
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
                <thead class="bg-bg/40 text-muted uppercase text-xs tracking-wider border-b border-line">
                    <tr>
                        <th class="py-2.5 px-4 font-medium">{{ __('Tingkat') }}</th>
                        <th class="py-2.5 px-4 font-medium">{{ __('Kode & Pesan') }}</th>
                        <th class="py-2.5 px-4 font-medium">{{ __('Node Perangkat') }}</th>
                        <th class="py-2.5 px-4 font-medium">{{ __('Waktu Terpicu') }}</th>
                        <th class="py-2.5 px-4 font-medium">{{ __('Status') }}</th>
                        <th class="py-2.5 px-4 font-medium text-right">{{ __('Aksi') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line" id="alertsTableBody">
                    @forelse($alerts as $alert)
                        <tr class="hover:bg-line/20 transition-colors" id="alert-row-{{ $alert->id }}">
                            {{-- Severity Badge (dot + text) --}}
                            <td class="py-3 px-4 align-top whitespace-nowrap">
                                @if($alert->severity === 'critical')
                                    <span class="inline-flex items-center space-x-1.5 text-danger font-mono text-xs">
                                        <span class="w-2 h-2 rounded-full bg-danger {{ $alert->status === 'open' ? 'animate-pulse' : '' }}"></span>
                                        <span>KRITIS</span>
                                    </span>
                                @elseif($alert->severity === 'warning')
                                    <span class="inline-flex items-center space-x-1.5 text-warn font-mono text-xs">
                                        <span class="w-2 h-2 rounded-full bg-warn"></span>
                                        <span>PERINGATAN</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center space-x-1.5 text-accent font-mono text-xs">
                                        <span class="w-2 h-2 rounded-full bg-accent"></span>
                                        <span>INFO</span>
                                    </span>
                                @endif
                            </td>

                            {{-- Code & Message --}}
                            <td class="py-3 px-4 align-top">
                                <div class="flex items-center space-x-2">
                                    <span class="font-mono text-xs text-ink px-1.5 py-0.5 rounded border border-line bg-bg">
                                        {{ $alert->code }}
                                    </span>
                                </div>
                                <div class="text-ink text-sm mt-1 leading-snug">
                                    {{ $alert->display_message }}
                                </div>
                                @if(!empty($alert->metadata))
                                    <div class="flex flex-wrap gap-1 mt-1.5">
                                        @foreach($alert->metadata as $mKey => $mVal)
                                            @if(is_scalar($mVal))
                                                <span class="inline-flex items-center text-[11px] bg-bg text-muted border border-line px-1.5 py-0.5 rounded font-mono">
                                                    {{ $mKey }}: {{ is_bool($mVal) ? ($mVal ? 'true' : 'false') : $mVal }}
                                                </span>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif
                            </td>

                            {{-- Device & Site --}}
                            <td class="py-3 px-4 align-top whitespace-nowrap">
                                <div class="font-medium text-ink">
                                    <a href="{{ route('solar-tracker', ['uid' => $alert->device?->uid]) }}" class="hover:text-accent transition-colors">
                                        {{ $alert->device?->uid ?? 'N/A' }}
                                    </a>
                                </div>
                                <div class="text-xs text-muted mt-0.5">
                                    {{ $alert->device?->site?->name ?? 'Default Site' }}
                                </div>
                            </td>

                            {{-- Time --}}
                            <td class="py-3 px-4 align-top whitespace-nowrap font-mono text-xs">
                                <div class="text-ink">
                                    {{ formatDateWib($alert->opened_at, 'd M Y, H:i') }}
                                </div>
                                <div class="text-muted mt-0.5">
                                    {{ $alert->opened_at?->diffForHumans() }}
                                </div>
                            </td>

                            {{-- Status Cell --}}
                            <td class="py-3 px-4 align-top whitespace-nowrap" id="status-cell-{{ $alert->id }}">
                                @if($alert->status === 'open')
                                    <span class="inline-flex items-center space-x-1.5 text-danger font-mono text-xs">
                                        <span class="w-1.5 h-1.5 rounded-full bg-danger"></span>
                                        <span>Terbuka</span>
                                    </span>
                                @elseif($alert->status === 'acknowledged')
                                    <div>
                                        <span class="inline-flex items-center space-x-1.5 text-purple-400 font-mono text-xs">
                                            <span class="w-1.5 h-1.5 rounded-full bg-purple-400"></span>
                                            <span>Diakui</span>
                                        </span>
                                        <div class="text-xs text-muted mt-0.5">
                                            {{ __('oleh :name', ['name' => $alert->acknowledgedBy?->name ?? 'Operator']) }}
                                        </div>
                                    </div>
                                @else
                                    <div>
                                        <span class="inline-flex items-center space-x-1.5 text-ok font-mono text-xs">
                                            <span class="w-1.5 h-1.5 rounded-full bg-ok"></span>
                                            <span>Selesai</span>
                                        </span>
                                        <div class="text-xs text-muted mt-0.5 font-mono">
                                            {{ formatTimeWib($alert->resolved_at) }}
                                        </div>
                                    </div>
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td class="py-3 px-4 align-top text-right whitespace-nowrap" id="action-cell-{{ $alert->id }}">
                                @if(auth()->user()->canControl())
                                    <div class="inline-flex items-center space-x-1.5">
                                        @if($alert->status === 'open')
                                            <button type="button" 
                                                    onclick="handleAlertAction('acknowledge', {{ $alert->id }})"
                                                    class="h-8 px-2.5 py-1 bg-purple-500/10 hover:bg-purple-500/20 text-purple-300 border border-purple-500/40 rounded text-xs font-medium transition-colors cursor-pointer">
                                                {{ __('Acknowledge') }}
                                            </button>
                                        @endif

                                        @if($alert->status !== 'resolved')
                                            <button type="button" 
                                                    onclick="handleAlertAction('resolve', {{ $alert->id }})"
                                                    class="h-8 px-2.5 py-1 bg-ok/10 hover:bg-ok/20 text-ok border border-ok/40 rounded text-xs font-medium transition-colors cursor-pointer">
                                                {{ __('Resolve') }}
                                            </button>
                                        @else
                                            <span class="text-xs text-muted font-mono inline-flex items-center">
                                                <span class="w-1.5 h-1.5 rounded-full bg-ok mr-1"></span>
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
                                <div class="text-sm font-medium text-ink">{{ __('Semua Sistem Normal') }}</div>
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
            <div class="p-3 border-t border-line bg-bg/40">
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
                            <span class="inline-flex items-center space-x-1.5 text-purple-400 font-mono text-xs">
                                <span class="w-1.5 h-1.5 rounded-full bg-purple-400"></span>
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
                                    class="h-8 px-2.5 py-1 bg-ok/10 hover:bg-ok/20 text-ok border border-ok/40 rounded text-xs font-medium transition-colors cursor-pointer">
                                Resolve
                            </button>
                        </div>
                    `;
                }
            } else if (action === 'resolve') {
                if (statusCell) {
                    statusCell.innerHTML = `
                        <div>
                            <span class="inline-flex items-center space-x-1.5 text-ok font-mono text-xs">
                                <span class="w-1.5 h-1.5 rounded-full bg-ok"></span>
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
                        <span class="text-xs text-muted font-mono inline-flex items-center">
                            <span class="w-1.5 h-1.5 rounded-full bg-ok mr-1"></span>
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

            toast.className = `mb-5 p-3 rounded-md text-sm flex items-center justify-between border ${
                type === 'success' 
                    ? 'bg-ok/10 border-ok/30 text-ok' 
                    : 'bg-danger/10 border-danger/30 text-danger'
            }`;

            content.innerHTML = `
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${type === 'success' ? 'M5 13l4 4L19 7' : 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'}" />
                </svg>
                <span>${message}</span>
            `;

            toast.classList.remove('hidden');
            setTimeout(() => {
                toast.classList.add('hidden');
            }, 5000);
        }
    </script>
    @endpush
</x-layouts.app>
