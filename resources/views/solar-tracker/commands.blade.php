{{-- Supervisory Actuator Control Panel & Command Dispatch --}}
<div class="mb-6" id="supervisory-control-section">
    <x-card :title="__('Supervisory Actuator Control Panel')" :subtitle="__('Kontrol manual dengan interlock keselamatan, pergantian mode operasi, dan kalibrasi hardware')">
        {{-- Safety & Role Status Banner --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3 rounded mb-4 text-xs font-mono border {{ $isWindAlert ? 'bg-danger/10 border-danger/30 text-danger' : 'bg-bg/50 border-line text-muted' }}">
            <div class="flex items-center gap-2.5">
                @if($isWindAlert)
                    <span class="w-2 h-2 rounded-full bg-danger animate-ping shrink-0"></span>
                    <div>
                        <strong class="font-bold text-danger">{{ __('Interlock Keselamatan Aktif:') }}</strong> 
                        {{ __('Kecepatan angin (:speed m/s) ≥ :limit m/s. Gerak manual dikunci otomatis.', ['speed' => formatNumberId($telemetry?->wind_speed ?? 0, 1), 'limit' => formatNumberId($windStowThreshold, 1)]) }}
                    </div>
                @else
                    <span class="w-2 h-2 rounded-full bg-ok shrink-0"></span>
                    <div>
                        <strong class="font-bold text-ink">{{ __('Interlock Perangkat Keras Nominal:') }}</strong> 
                        {{ __('Kecepatan angin dalam batas aman (< :limit m/s). Aktuator siap menerima perintah.', ['limit' => formatNumberId($windStowThreshold, 1)]) }}
                    </div>
                @endif
            </div>

            <div class="shrink-0">
                @if(auth()->user()->canControl())
                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[11px] font-mono font-medium border border-accent/30 bg-accent/10 text-accent">
                        <span class="w-1.5 h-1.5 rounded-full bg-accent"></span>
                        <span>{{ __('Operator / Admin Terverifikasi') }}</span>
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[11px] font-mono font-medium border border-line bg-bg text-muted">
                        <span class="w-1.5 h-1.5 rounded-full bg-muted"></span>
                        <span>{{ __('Mode Pemirsa (Hanya-Lihat)') }}</span>
                    </span>
                @endif
            </div>
        </div>

        {{-- Command Execution Progress Card (Live Timeline) --}}
        <div id="commandExecutionCard" class="hidden mb-4 p-4 rounded bg-bg text-ink border border-line">
            <div class="flex items-center justify-between pb-2.5 border-b border-line">
                <div class="flex items-center gap-2 font-mono">
                    <span id="cmdSpinner" class="w-3.5 h-3.5 rounded-full border-2 border-accent border-t-transparent animate-spin"></span>
                    <span class="text-xs font-bold uppercase tracking-wider text-accent" id="cmdTitle">{{ __('Perintah Dikirim') }}</span>
                    <span class="text-[11px] text-muted" id="cmdUuidLabel"></span>
                </div>
                <button type="button" onclick="document.getElementById('commandExecutionCard').classList.add('hidden')" class="text-muted hover:text-ink text-sm cursor-pointer">&times;</button>
            </div>

            {{-- Visual Execution Steps --}}
            <div class="grid grid-cols-4 gap-2 my-3 text-center text-xs font-mono">
                <div id="step-sent" class="p-2 rounded bg-panel border border-line text-muted font-medium">
                    1. {{ __('Antrean') }}
                </div>
                <div id="step-accepted" class="p-2 rounded bg-panel border border-line text-muted font-medium">
                    2. {{ __('ACK Device') }}
                </div>
                <div id="step-executing" class="p-2 rounded bg-panel border border-line text-muted font-medium">
                    3. {{ __('Bergerak') }}
                </div>
                <div id="step-completed" class="p-2 rounded bg-panel border border-line text-muted font-medium">
                    4. {{ __('Tercapai') }}
                </div>
            </div>

            <div id="cmdMessageBanner" class="text-xs text-muted font-mono bg-panel p-2.5 rounded border border-line">
                {{ __('Menunggu konfirmasi perangkat melalui broker MQTT Mosquitto...') }}
            </div>
        </div>

        {{-- Supervisory Control Buttons Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 {{ auth()->user()->canControl() ? '' : 'opacity-60 pointer-events-none' }}">
            {{-- 1. Operational Mode Switching --}}
            <div class="p-4 rounded border border-line bg-bg/50 space-y-3">
                <div class="flex items-center justify-between">
                    <span class="font-bold text-xs font-mono text-ink">1. {{ __('Mode Operasional') }}</span>
                    <span class="text-[10px] font-mono uppercase px-2 py-0.5 rounded border border-line bg-panel text-muted" id="activeModeBadge">
                        {{ strtoupper($device->mode ?? 'auto') }}
                    </span>
                </div>

                <div class="space-y-2">
                    <button 
                        type="button" 
                        onclick="dispatchTrackerCommand('set_mode', { mode: 'auto' })"
                        class="w-full py-2 px-3 rounded text-xs font-mono font-medium flex items-center justify-between border transition-colors cursor-pointer {{ ($device->mode ?? 'auto') === 'auto' ? 'bg-accent/20 text-accent border-accent/40 font-bold' : 'bg-panel hover:bg-line/30 text-ink border-line' }}"
                    >
                        <span>{{ __('Auto Tracking (SPA)') }}</span>
                        <span class="text-[10px] text-muted">Otonom</span>
                    </button>

                    <button 
                        type="button" 
                        onclick="dispatchTrackerCommand('set_mode', { mode: 'manual' })"
                        class="w-full py-2 px-3 rounded text-xs font-mono font-medium flex items-center justify-between border transition-colors cursor-pointer {{ ($device->mode ?? '') === 'manual' ? 'bg-warn/20 text-warn border-warn/40 font-bold' : 'bg-panel hover:bg-line/30 text-ink border-line' }}"
                    >
                        <span>{{ __('Manual Override') }}</span>
                        <span class="text-[10px] text-muted">Operator</span>
                    </button>

                    <button 
                        type="button" 
                        onclick="dispatchTrackerCommand('stow', {})"
                        class="w-full py-2 px-3 rounded text-xs font-mono font-medium flex items-center justify-between border transition-colors cursor-pointer {{ ($device->mode ?? '') === 'stow' ? 'bg-line/60 text-ink border-line font-bold' : 'bg-panel hover:bg-line/30 text-ink border-line' }}"
                    >
                        <span>{{ __('Protective Stow (0°)') }}</span>
                        <span class="text-[10px] text-muted">Aerodinamis</span>
                    </button>
                </div>
            </div>

            {{-- 2. Manual Move Coordinates --}}
            <div class="p-4 rounded border border-line bg-bg/50 space-y-3">
                <div class="flex items-center justify-between">
                    <span class="font-bold text-xs font-mono text-ink">2. {{ __('Koordinat Gerak Manual') }}</span>
                    <span class="text-[10px] text-muted font-mono">Batas: 10–85° / 60–300°</span>
                </div>

                <div class="space-y-2.5">
                    <div>
                        <label class="block text-xs font-mono text-muted mb-1 flex items-center justify-between">
                            <span>{{ __('Target Elevasi (Pitch)') }}</span>
                            <span class="text-[10px] text-muted/70">10.0° – 85.0°</span>
                        </label>
                        <div class="relative">
                            <input 
                                type="number" 
                                id="inputTargetElev" 
                                step="0.5" 
                                min="10" 
                                max="85" 
                                value="{{ number_format($elevTarget ?: 35.0, 1, '.', '') }}"
                                class="w-full h-9 px-3 rounded border border-line bg-panel text-ink text-xs font-mono font-semibold focus:border-accent focus:outline-none"
                            >
                            <span class="absolute right-3 top-2 text-xs text-muted font-mono">&deg;</span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-mono text-muted mb-1 flex items-center justify-between">
                            <span>{{ __('Target Azimut (Heading)') }}</span>
                            <span class="text-[10px] text-muted/70">60.0° – 300.0°</span>
                        </label>
                        <div class="relative">
                            <input 
                                type="number" 
                                id="inputTargetAz" 
                                step="0.5" 
                                min="60" 
                                max="300" 
                                value="{{ number_format($azTarget ?: 120.0, 1, '.', '') }}"
                                class="w-full h-9 px-3 rounded border border-line bg-panel text-ink text-xs font-mono font-semibold focus:border-accent focus:outline-none"
                            >
                            <span class="absolute right-3 top-2 text-xs text-muted font-mono">&deg;</span>
                        </div>
                    </div>

                    <button 
                        type="button" 
                        onclick="dispatchMoveCommand()"
                        class="w-full h-9 py-1.5 px-3 rounded border border-accent/40 bg-accent/15 hover:bg-accent/25 text-accent font-mono font-semibold text-xs transition-colors flex items-center justify-center gap-1.5 cursor-pointer mt-1"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                        <span>{{ __('Kirim Posisi Target') }}</span>
                    </button>
                </div>
            </div>

            {{-- 3. Immediate Safety Overrides --}}
            <div class="p-4 rounded border border-line bg-bg/50 space-y-3">
                <div class="flex items-center justify-between">
                    <span class="font-bold text-xs font-mono text-ink">3. {{ __('Interlock & Keselamatan') }}</span>
                    <span class="text-[10px] font-mono text-danger font-semibold">{{ __('Prioritas Utama') }}</span>
                </div>

                <div class="space-y-2">
                    <button 
                        type="button" 
                        onclick="dispatchTrackerCommand('stop', {})"
                        class="w-full py-2 px-3 rounded border border-danger/40 bg-danger/15 hover:bg-danger/25 text-danger font-mono font-bold text-xs transition-colors flex items-center justify-center gap-2 cursor-pointer"
                    >
                        <svg class="w-4 h-4 text-danger" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 9h6v6H9z" />
                        </svg>
                        <span>{{ __('EMERGENCY STOP (HALT)') }}</span>
                    </button>

                    <button 
                        type="button" 
                        onclick="dispatchTrackerCommand('stow', {})"
                        class="w-full py-2 px-3 rounded border border-warn/40 bg-warn/15 hover:bg-warn/25 text-warn font-mono font-semibold text-xs transition-colors flex items-center justify-center gap-2 cursor-pointer"
                    >
                        <svg class="w-4 h-4 text-warn" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                        </svg>
                        <span>{{ __('Force Safe Stow') }}</span>
                    </button>

                    <button 
                        type="button" 
                        onclick="dispatchTrackerCommand('calibrate', {})"
                        class="w-full py-2 px-3 rounded border border-line bg-panel hover:bg-line/30 text-ink font-mono font-medium text-xs transition-colors flex items-center justify-center gap-2 cursor-pointer"
                    >
                        <svg class="w-4 h-4 text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <span>{{ __('Rekalibrasi BNO055 IMU') }}</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- 4. Recent Command Execution Log Table (5 baris + user) --}}
        <div class="mt-5 pt-4 border-t border-line">
            <div class="flex items-center justify-between mb-2.5">
                <span class="text-xs font-mono font-semibold text-ink uppercase tracking-wider">{{ __('Riwayat Perintah Terakhir') }}</span>
                <button type="button" onclick="refreshCommandsLog()" class="text-xs font-mono text-accent hover:underline cursor-pointer">
                    {{ __('Segarkan Log') }} &rarr;
                </button>
            </div>

            <div class="overflow-x-auto rounded border border-line">
                <table class="min-w-full divide-y divide-line text-xs font-mono">
                    <thead class="bg-bg/60 text-muted">
                        <tr>
                            <th class="px-3.5 py-2 text-left font-semibold">{{ __('Waktu (WIB)') }}</th>
                            <th class="px-3.5 py-2 text-left font-semibold">{{ __('Jenis Perintah') }}</th>
                            <th class="px-3.5 py-2 text-left font-semibold">{{ __('Parameter') }}</th>
                            <th class="px-3.5 py-2 text-left font-semibold">{{ __('Status') }}</th>
                            <th class="px-3.5 py-2 text-left font-semibold">{{ __('Oleh') }}</th>
                            <th class="px-3.5 py-2 text-right font-semibold">{{ __('Durasi') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line bg-panel text-ink" id="recentCommandsTableBody">
                        @forelse($recentCommands as $cmd)
                            <tr class="hover:bg-bg/40 transition-colors">
                                <td class="px-3.5 py-2 text-muted whitespace-nowrap">
                                    {{ formatDateWib($cmd->issued_at, 'd M, H.i.s') }}
                                </td>
                                <td class="px-3.5 py-2 font-semibold text-ink whitespace-nowrap">
                                    {{ $cmd->type }}
                                </td>
                                <td class="px-3.5 py-2 text-muted max-w-xs truncate">
                                    {{ !empty($cmd->params) ? json_encode($cmd->params) : '--' }}
                                </td>
                                <td class="px-3.5 py-2 whitespace-nowrap">
                                    @if($cmd->status === 'completed')
                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-mono border border-ok/30 bg-ok/10 text-ok">
                                            <span class="w-1.5 h-1.5 rounded-full bg-ok"></span>
                                            <span>{{ __('Completed') }}</span>
                                        </span>
                                    @elseif($cmd->status === 'rejected' || $cmd->status === 'failed')
                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-mono border border-danger/30 bg-danger/10 text-danger" title="{{ $cmd->reason }}">
                                            <span class="w-1.5 h-1.5 rounded-full bg-danger"></span>
                                            <span>{{ __($cmd->status) }}</span>
                                        </span>
                                    @elseif($cmd->status === 'timeout')
                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-mono border border-warn/30 bg-warn/10 text-warn">
                                            <span class="w-1.5 h-1.5 rounded-full bg-warn"></span>
                                            <span>{{ __('Timeout') }}</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-mono border border-accent/30 bg-accent/10 text-accent">
                                            <span class="w-1.5 h-1.5 rounded-full bg-accent"></span>
                                            <span>{{ __($cmd->status) }}</span>
                                        </span>
                                    @endif
                                </td>
                                <td class="px-3.5 py-2 text-muted whitespace-nowrap">
                                    {{ $cmd->user?->name ?? 'System' }}
                                </td>
                                <td class="px-3.5 py-2 text-right text-muted whitespace-nowrap">
                                    {{ ($cmd->completed_at && $cmd->issued_at) ? $cmd->issued_at->diffInSeconds($cmd->completed_at) . 's' : '--' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-3.5 py-4 text-center text-muted font-sans">
                                    {{ __('Tidak ada perintah yang dikeluarkan baru-baru ini.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </x-card>
</div>

