<x-layouts.app title="{{ __('Alerts & Diagnostics') }}">
    <x-page-header 
        :title="__('Alerts & Diagnostics')" 
        :subtitle="__('Real-time condition evaluation, hardware safety interlocks, and sensor health diagnostics.')"
        :badge="__('SCADA Interlocks')"
    >
        <x-slot:actions>
            <div class="flex items-center space-x-2">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 mr-2 animate-pulse"></span>
                    {{ __('Engine Active (Auto-Resolve Hysteresis)') }}
                </span>
            </div>
        </x-slot:actions>
    </x-page-header>

    @if(session('success'))
        <div class="mb-5 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>{{ session('success') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-900 cursor-pointer text-base">&times;</button>
        </div>
    @endif

    {{-- Toast feedback container --}}
    <div id="alertToast" class="hidden mb-5 p-4 rounded-xl text-sm flex items-center justify-between transition-all">
        <div class="flex items-center space-x-2" id="alertToastContent"></div>
        <button onclick="document.getElementById('alertToast').classList.add('hidden')" class="text-slate-500 hover:text-slate-800 cursor-pointer text-base">&times;</button>
    </div>

    {{-- KPI Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <x-kpi-card 
            :title="__('Active Alerts')" 
            :value="$kpis['active_total']" 
            badge="ACTIVE" 
            :badgeType="$kpis['active_total'] > 0 ? 'warning' : 'neutral'"
            :subtitle="__('Open or acknowledged')"
        />

        <x-kpi-card 
            :title="__('Critical Priority')" 
            :value="$kpis['critical_open']" 
            badge="CRITICAL" 
            :badgeType="$kpis['critical_open'] > 0 ? 'danger' : 'neutral'"
            :subtitle="__('Protective stow / actuator stops')"
        />

        <x-kpi-card 
            :title="__('Warnings')" 
            :value="$kpis['warning_open']" 
            badge="WARNING" 
            :badgeType="$kpis['warning_open'] > 0 ? 'warning' : 'neutral'"
            :subtitle="__('IMU uncal, comms, RTC drift')"
        />

        <x-kpi-card 
            :title="__('Resolved (24h)')" 
            :value="$kpis['resolved_24h']" 
            badge="RESOLVED" 
            badgeType="success"
            :subtitle="__('Cleared automatically or by staff')"
        />
    </div>

    {{-- Filter Toolbar --}}
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs p-5 mb-6">
        <form method="GET" action="{{ route('alerts') }}" class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            {{-- Status Tabs --}}
            <div class="flex items-center space-x-1 p-1 bg-slate-100 rounded-lg text-sm font-medium">
                <a href="{{ route('alerts', array_merge(request()->except('status', 'page'), ['status' => 'all'])) }}" 
                   class="px-3.5 py-2 rounded-md transition-colors {{ ($filters['status'] ?? 'all') === 'all' ? 'bg-white text-slate-800 shadow-xs font-semibold' : 'text-slate-600 hover:text-slate-900' }}">
                    {{ __('All') }}
                </a>
                <a href="{{ route('alerts', array_merge(request()->except('status', 'page'), ['status' => 'active'])) }}" 
                   class="px-3.5 py-2 rounded-md transition-colors {{ ($filters['status'] ?? '') === 'active' ? 'bg-white text-slate-800 shadow-xs font-semibold' : 'text-slate-600 hover:text-slate-900' }}">
                    {{ __('Active') }}
                </a>
                <a href="{{ route('alerts', array_merge(request()->except('status', 'page'), ['status' => 'open'])) }}" 
                   class="px-3.5 py-2 rounded-md transition-colors {{ ($filters['status'] ?? '') === 'open' ? 'bg-white text-slate-800 shadow-xs font-semibold' : 'text-slate-600 hover:text-slate-900' }}">
                    {{ __('Open') }}
                </a>
                <a href="{{ route('alerts', array_merge(request()->except('status', 'page'), ['status' => 'acknowledged'])) }}" 
                   class="px-3.5 py-2 rounded-md transition-colors {{ ($filters['status'] ?? '') === 'acknowledged' ? 'bg-white text-slate-800 shadow-xs font-semibold' : 'text-slate-600 hover:text-slate-900' }}">
                    {{ __('Ack\'ed') }}
                </a>
                <a href="{{ route('alerts', array_merge(request()->except('status', 'page'), ['status' => 'resolved'])) }}" 
                   class="px-3.5 py-2 rounded-md transition-colors {{ ($filters['status'] ?? '') === 'resolved' ? 'bg-white text-slate-800 shadow-xs font-semibold' : 'text-slate-600 hover:text-slate-900' }}">
                    {{ __('Resolved') }}
                </a>
            </div>

            {{-- Severity, Device, and Search Controls --}}
            <div class="flex flex-wrap items-center gap-3 text-sm">
                <input type="hidden" name="status" value="{{ $filters['status'] ?? 'all' }}">

                {{-- Severity Filter --}}
                <select name="severity" onchange="this.form.submit()" class="h-11 min-h-[44px] px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-lg text-slate-700 font-medium focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <option value="all" {{ ($filters['severity'] ?? 'all') === 'all' ? 'selected' : '' }}>{{ __('All Severities') }}</option>
                    <option value="critical" {{ ($filters['severity'] ?? '') === 'critical' ? 'selected' : '' }}>{{ __('Critical Only') }}</option>
                    <option value="warning" {{ ($filters['severity'] ?? '') === 'warning' ? 'selected' : '' }}>{{ __('Warning Only') }}</option>
                    <option value="info" {{ ($filters['severity'] ?? '') === 'info' ? 'selected' : '' }}>{{ __('Info Only') }}</option>
                </select>

                {{-- Device Filter --}}
                <select name="device_id" onchange="this.form.submit()" class="h-11 min-h-[44px] px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-lg text-slate-700 font-medium focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <option value="all" {{ ($filters['device_id'] ?? 'all') === 'all' ? 'selected' : '' }}>{{ __('All Tracker Nodes') }}</option>
                    @foreach($devices as $dev)
                        <option value="{{ $dev->id }}" {{ (string)($filters['device_id'] ?? '') === (string)$dev->id ? 'selected' : '' }}>
                            {{ $dev->uid }} ({{ $dev->name }})
                        </option>
                    @endforeach
                </select>

                {{-- Text Search --}}
                <div class="relative">
                    <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="{{ __('Search code, message...') }}" 
                           class="w-56 h-11 min-h-[44px] pl-10 pr-4 py-2 bg-slate-50 border border-slate-300 rounded-lg text-slate-700 placeholder-slate-400 focus:ring-2 focus:ring-blue-500 focus:outline-none text-sm">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>

                <button type="submit" class="h-11 min-h-[44px] px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white font-medium rounded-lg transition-colors cursor-pointer">
                    {{ __('Filter') }}
                </button>

                @if(!empty($filters['q']) || ($filters['severity'] ?? 'all') !== 'all' || ($filters['device_id'] ?? 'all') !== 'all' || ($filters['status'] ?? 'all') !== 'all')
                    <a href="{{ route('alerts') }}" class="px-3 py-2 text-slate-500 hover:text-slate-800 underline font-medium text-sm">
                        {{ __('Reset') }}
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Alerts Table Card --}}
    <x-card :title="__('System Alerts Log')" :subtitle="__('Comprehensive history of SCADA interlocks, hardware faults, and condition transitions.')">
        <div class="overflow-x-auto -mx-6 -my-6">
            <table class="w-full text-left text-sm border-collapse">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-200/80 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                        <th class="py-4 px-5">{{ __('Severity') }}</th>
                        <th class="py-4 px-5">{{ __('Alert Code & Message') }}</th>
                        <th class="py-4 px-5">{{ __('Tracker Node') }}</th>
                        <th class="py-4 px-5">{{ __('Triggered Time (WIB)') }}</th>
                        <th class="py-4 px-5">{{ __('Status') }}</th>
                        <th class="py-4 px-5 text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100" id="alertsTableBody">
                    @forelse($alerts as $alert)
                        <tr class="hover:bg-slate-50/60 transition-colors" id="alert-row-{{ $alert->id }}">
                            {{-- Severity Badge --}}
                            <td class="py-4 px-5 align-top whitespace-nowrap">
                                @if($alert->severity === 'critical')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                        <span class="w-2 h-2 rounded-full bg-rose-500 mr-1.5 {{ $alert->status === 'open' ? 'animate-ping' : '' }}"></span>
                                        {{ __('CRITICAL') }}
                                    </span>
                                @elseif($alert->severity === 'warning')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                        <span class="w-2 h-2 rounded-full bg-amber-500 mr-1.5"></span>
                                        {{ __('WARNING') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                        <span class="w-2 h-2 rounded-full bg-blue-500 mr-1.5"></span>
                                        {{ __('INFO') }}
                                    </span>
                                @endif
                            </td>

                            {{-- Code & Message (Render translated message at render time) --}}
                            <td class="py-4 px-5 align-top">
                                <div class="flex items-center space-x-2">
                                    <span class="font-mono font-bold text-slate-800 text-xs tracking-tight bg-slate-100 px-2 py-0.5 rounded border border-slate-200">
                                        {{ $alert->code }}
                                    </span>
                                </div>
                                <div class="text-slate-700 text-sm mt-1 leading-snug">
                                    {{ $alert->display_message }}
                                </div>
                                @if(!empty($alert->metadata))
                                    <div class="flex flex-wrap gap-1 mt-1.5">
                                        @foreach($alert->metadata as $mKey => $mVal)
                                            @if(is_scalar($mVal))
                                                <span class="inline-flex items-center text-xs bg-slate-50 text-slate-500 border border-slate-200 px-1.5 py-0.5 rounded font-mono">
                                                    {{ $mKey }}: {{ is_bool($mVal) ? ($mVal ? 'true' : 'false') : $mVal }}
                                                </span>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif
                            </td>

                            {{-- Device & Site --}}
                            <td class="py-4 px-5 align-top whitespace-nowrap">
                                <div class="font-semibold text-slate-800">
                                    <a href="{{ route('solar-tracker', ['uid' => $alert->device?->uid]) }}" class="hover:text-blue-600 hover:underline">
                                        {{ $alert->device?->uid ?? 'N/A' }}
                                    </a>
                                </div>
                                <div class="text-xs text-slate-400 mt-0.5">
                                    {{ $alert->device?->site?->name ?? 'Default Site' }}
                                </div>
                            </td>

                            {{-- Time (Indonesian format with WIB) --}}
                            <td class="py-4 px-5 align-top whitespace-nowrap">
                                <div class="font-medium text-slate-700">
                                    {{ formatDateWib($alert->opened_at, 'd F Y, H.i') }}
                                </div>
                                <div class="text-xs text-slate-400 mt-0.5">
                                    {{ $alert->opened_at?->diffForHumans() }}
                                </div>
                            </td>

                            {{-- Status Badge --}}
                            <td class="py-4 px-5 align-top whitespace-nowrap" id="status-cell-{{ $alert->id }}">
                                @if($alert->status === 'open')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                        {{ __('Open') }}
                                    </span>
                                @elseif($alert->status === 'acknowledged')
                                    <div>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-semibold bg-purple-50 text-purple-700 border border-purple-200">
                                            {{ __('Acknowledged') }}
                                        </span>
                                        <div class="text-xs text-slate-400 mt-0.5">
                                            {{ __('by :name', ['name' => $alert->acknowledgedBy?->name ?? 'Operator']) }}
                                        </div>
                                    </div>
                                @else
                                    <div>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            {{ __('Resolved') }}
                                        </span>
                                        <div class="text-xs text-slate-400 mt-0.5 font-mono">
                                            {{ formatTimeWib($alert->resolved_at) }}
                                        </div>
                                    </div>
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td class="py-4 px-5 align-top text-right whitespace-nowrap" id="action-cell-{{ $alert->id }}">
                                @if(auth()->user()->canControl())
                                    <div class="inline-flex items-center space-x-2">
                                        @if($alert->status === 'open')
                                            <button type="button" 
                                                    onclick="handleAlertAction('acknowledge', {{ $alert->id }})"
                                                    class="h-10 min-h-[40px] px-3.5 py-1.5 bg-purple-50 hover:bg-purple-100 text-purple-700 border border-purple-200 rounded-lg text-xs font-semibold transition-colors cursor-pointer">
                                                {{ __('Acknowledge') }}
                                            </button>
                                        @endif

                                        @if($alert->status !== 'resolved')
                                            <button type="button" 
                                                    onclick="handleAlertAction('resolve', {{ $alert->id }})"
                                                    class="h-10 min-h-[40px] px-3.5 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 rounded-lg text-xs font-semibold transition-colors cursor-pointer">
                                                {{ __('Resolve') }}
                                            </button>
                                        @else
                                            <span class="text-xs text-slate-400 font-medium inline-flex items-center">
                                                <svg class="w-4 h-4 text-emerald-500 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                                {{ __('Closed') }}
                                            </span>
                                        @endif
                                    </div>
                                @else
                                    <span class="inline-flex items-center text-xs text-slate-400 italic">
                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                        </svg>
                                        {{ __('Read-only') }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-10 text-center bg-slate-50/50">
                                <svg class="w-12 h-12 text-emerald-500 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <div class="text-base font-semibold text-slate-700">{{ __('All Systems Nominal') }}</div>
                                <div class="text-sm text-slate-400 mt-1 max-w-sm mx-auto">
                                    {{ __('No alerts matching the selected filter criteria. Hardware interlocks are idle.') }}
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($alerts->hasPages())
            <x-slot:footer>
                <div class="flex items-center justify-between">
                    <div>
                        {{ __('Showing :from to :to of :total alerts', ['from' => $alerts->firstItem(), 'to' => $alerts->lastItem(), 'total' => $alerts->total()]) }}
                    </div>
                    <div>
                        {{ $alerts->links() }}
                    </div>
                </div>
            </x-slot:footer>
        @endif
    </x-card>

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
                    showToast(data.message || (window.__ ? window.__('Action failed.') : 'Action failed.'), 'danger');
                }
            } catch (err) {
                console.error(err);
                showToast(window.__ ? window.__('Network error while processing alert action.') : 'Network error while processing alert action.', 'danger');
            }
        }

        function updateRowVisuals(alertId, action, data) {
            const statusCell = document.getElementById(`status-cell-${alertId}`);
            const actionCell = document.getElementById(`action-cell-${alertId}`);

            const ackLabel = window.__ ? window.__('Acknowledged') : 'Acknowledged';
            const resLabel = window.__ ? window.__('Resolved') : 'Resolved';
            const closedLabel = window.__ ? window.__('Closed') : 'Closed';
            const resolveBtnText = window.__ ? window.__('Resolve') : 'Resolve';

            if (action === 'acknowledge') {
                if (statusCell) {
                    statusCell.innerHTML = `
                        <div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-semibold bg-purple-50 text-purple-700 border border-purple-200">
                                ${ackLabel}
                            </span>
                            <div class="text-xs text-slate-400 mt-0.5">
                                ${data.acknowledged_by || 'Operator'}
                            </div>
                        </div>
                    `;
                }
                if (actionCell) {
                    actionCell.innerHTML = `
                        <div class="inline-flex items-center space-x-2">
                            <button type="button" 
                                    onclick="handleAlertAction('resolve', ${alertId})"
                                    class="h-10 min-h-[40px] px-3.5 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 rounded-lg text-xs font-semibold transition-colors cursor-pointer">
                                ${resolveBtnText}
                            </button>
                        </div>
                    `;
                }
            } else if (action === 'resolve') {
                if (statusCell) {
                    statusCell.innerHTML = `
                        <div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                ${resLabel}
                            </span>
                            <div class="text-xs text-slate-400 mt-0.5 font-mono">
                                ${data.resolved_at || 'WIB'}
                            </div>
                        </div>
                    `;
                }
                if (actionCell) {
                    actionCell.innerHTML = `
                        <span class="text-xs text-slate-400 font-medium inline-flex items-center">
                            <svg class="w-4 h-4 text-emerald-500 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            ${closedLabel}
                        </span>
                    `;
                }
            }
        }

        function showToast(message, type) {
            const toast = document.getElementById('alertToast');
            const content = document.getElementById('alertToastContent');
            if (!toast || !content) return;

            toast.className = `mb-5 p-4 rounded-xl text-sm flex items-center justify-between border ${
                type === 'success' 
                    ? 'bg-emerald-50 border-emerald-200 text-emerald-800' 
                    : 'bg-rose-50 border-rose-200 text-rose-800'
            }`;

            content.innerHTML = `
                <svg class="w-5 h-5 ${type === 'success' ? 'text-emerald-600' : 'text-rose-600'} shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
