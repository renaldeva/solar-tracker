{{-- Form Kirim Perintah (Kartu Terpisah Bersih) --}}
<div class="mb-6" id="supervisory-control-section">
    <x-card :title="__('Form Kirim Perintah Pelacak')" :subtitle="__('Kendalikan orientasi tracker secara manual atau alihkan mode pelacakan')">
        {{-- Status Interlock Keselamatan --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3.5 rounded-xl mb-5 text-xs border {{ $isWindAlert ? 'bg-rose-50 dark:bg-rose-950/40 border-rose-200 dark:border-rose-800/60 text-rose-900 dark:text-rose-100' : 'bg-panel-alt border-line text-ink' }}">
            <div class="flex items-center gap-2.5">
                @if($isWindAlert)
                    <span class="w-2.5 h-2.5 rounded-full bg-rose-500 animate-ping shrink-0"></span>
                    <div>
                        <strong class="font-bold text-rose-700 dark:text-rose-300">{{ __('Interlock Keselamatan Aktif:') }}</strong> 
                        {{ __('Kecepatan angin (:speed m/s) ≥ :limit m/s. Gerak manual dinonaktifkan demi keamanan struktur.', ['speed' => formatNumberId($telemetry?->wind_speed ?? 0, 1), 'limit' => formatNumberId($windStowThreshold, 1)]) }}
                    </div>
                @else
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 shrink-0"></span>
                    <div>
                        <strong class="font-bold text-ink">{{ __('Kondisi Aman:') }}</strong> 
                        {{ __('Kecepatan angin dalam batas wajar (< :limit m/s). Aktuator siap menerima perintah.', ['limit' => formatNumberId($windStowThreshold, 1)]) }}
                    </div>
                @endif
            </div>

            <div class="shrink-0">
                @if(auth()->user()->canControl())
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold border border-blue-200 dark:border-blue-800/60 bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-600 dark:bg-blue-400"></span>
                        <span>{{ __('Operator / Admin') }}</span>
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium border border-line bg-panel-alt text-muted">
                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                        <span>{{ __('Mode Pemirsa (Hanya Lihat)') }}</span>
                    </span>
                @endif
            </div>
        </div>

        {{-- Command Execution Progress Card (Live Timeline) --}}
        <div id="commandExecutionCard" class="hidden mb-5 p-4 rounded-xl bg-panel-alt text-ink border border-line">
            <div class="flex items-center justify-between pb-3 border-b border-line">
                <div class="flex items-center gap-2.5">
                    <span id="cmdSpinner" class="w-4 h-4 rounded-full border-2 border-blue-600 dark:border-blue-400 border-t-transparent animate-spin"></span>
                    <span class="text-xs font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400" id="cmdTitle">{{ __('Perintah Sedang Dikirim') }}</span>
                    <span class="text-[11px] text-muted font-mono" id="cmdUuidLabel"></span>
                </div>
                <button type="button" onclick="document.getElementById('commandExecutionCard').classList.add('hidden')" class="text-muted hover:text-ink text-base cursor-pointer">&times;</button>
            </div>

            {{-- Visual Execution Steps --}}
            <div class="grid grid-cols-4 gap-2.5 my-3 text-center text-xs">
                <div id="step-sent" class="p-2.5 rounded-lg bg-panel border border-line text-muted font-medium">
                    1. {{ __('Antrean') }}
                </div>
                <div id="step-accepted" class="p-2.5 rounded-lg bg-panel border border-line text-muted font-medium">
                    2. {{ __('ACK Perangkat') }}
                </div>
                <div id="step-executing" class="p-2.5 rounded-lg bg-panel border border-line text-muted font-medium">
                    3. {{ __('Bergerak') }}
                </div>
                <div id="step-completed" class="p-2.5 rounded-lg bg-panel border border-line text-muted font-medium">
                    4. {{ __('Selesai') }}
                </div>
            </div>

            <div id="cmdMessageBanner" class="text-xs text-muted bg-panel p-3 rounded-lg border border-line">
                {{ __('Menunggu konfirmasi perangkat melalui broker MQTT...') }}
            </div>
        </div>

        {{-- Form Actions Grid (3 Kolom Jelas) --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 {{ auth()->user()->canControl() ? '' : 'opacity-60 pointer-events-none' }}">
            {{-- 1. Pilihan Mode Operasional --}}
            <div class="p-4 rounded-xl border border-line bg-panel-alt/60 space-y-3">
                <div class="flex items-center justify-between">
                    <span class="font-bold text-xs text-ink uppercase tracking-wider">1. {{ __('Pilihan Mode') }}</span>
                    <span class="text-[11px] font-semibold uppercase px-2 py-0.5 rounded-full border border-blue-200 dark:border-blue-800/60 bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300" id="activeModeBadge">
                        {{ strtoupper($device->mode ?? 'auto') }}
                    </span>
                </div>

                <div class="space-y-2">
                    <button 
                        type="button" 
                        onclick="dispatchTrackerCommand('set_mode', { mode: 'auto' })"
                        class="w-full py-2.5 px-3 rounded-xl text-xs font-semibold flex items-center justify-between border transition-all cursor-pointer {{ ($device->mode ?? 'auto') === 'auto' ? 'bg-blue-600 text-white border-blue-600 shadow-xs' : 'bg-panel hover:bg-panel-alt text-ink border-line' }}"
                    >
                        <span>{{ __('Auto Tracking (SPA)') }}</span>
                        <span class="text-[10px] opacity-80">Otonom</span>
                    </button>

                    <button 
                        type="button" 
                        onclick="dispatchTrackerCommand('set_mode', { mode: 'manual' })"
                        class="w-full py-2.5 px-3 rounded-xl text-xs font-semibold flex items-center justify-between border transition-all cursor-pointer {{ ($device->mode ?? '') === 'manual' ? 'bg-amber-500 text-white border-amber-500 shadow-xs' : 'bg-panel hover:bg-panel-alt text-ink border-line' }}"
                    >
                        <span>{{ __('Manual Override') }}</span>
                        <span class="text-[10px] opacity-80">Operator</span>
                    </button>

                    <button 
                        type="button" 
                        onclick="dispatchTrackerCommand('stow', {})"
                        class="w-full py-2.5 px-3 rounded-xl text-xs font-semibold flex items-center justify-between border transition-all cursor-pointer {{ ($device->mode ?? '') === 'stow' ? 'bg-slate-700 text-white border-slate-700 shadow-xs' : 'bg-panel hover:bg-panel-alt text-ink border-line' }}"
                    >
                        <span>{{ __('Protective Stow (0°)') }}</span>
                        <span class="text-[10px] opacity-80">Aerodinamis</span>
                    </button>
                </div>
            </div>

            {{-- 2. Input Sudut Gerak Manual --}}
            <div class="p-4 rounded-xl border border-line bg-panel-alt/60 space-y-3">
                <div class="flex items-center justify-between">
                    <span class="font-bold text-xs text-ink uppercase tracking-wider">2. {{ __('Arahkan Manual') }}</span>
                    <span class="text-[10px] text-muted">Rentang: 10–85° / 60–300°</span>
                </div>

                <div class="space-y-2.5">
                    <div>
                        <label class="block text-xs font-medium text-muted mb-1 flex items-center justify-between">
                            <span>{{ __('Target Elevasi (Pitch)') }}</span>
                            <span class="text-[10px] text-muted">10.0° – 85.0°</span>
                        </label>
                        <div class="relative">
                            <input 
                                type="number" 
                                id="inputTargetElev" 
                                step="0.5" 
                                min="10" 
                                max="85" 
                                value="{{ number_format($elevTarget ?: 35.0, 1, '.', '') }}"
                                class="w-full h-9 px-3 rounded-xl border border-line bg-panel text-ink text-xs font-mono font-semibold focus:border-blue-500 focus:outline-none"
                            >
                            <span class="absolute right-3 top-2 text-xs text-muted">&deg;</span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-muted mb-1 flex items-center justify-between">
                            <span>{{ __('Target Azimut (Heading)') }}</span>
                            <span class="text-[10px] text-muted">60.0° – 300.0°</span>
                        </label>
                        <div class="relative">
                            <input 
                                type="number" 
                                id="inputTargetAz" 
                                step="0.5" 
                                min="60" 
                                max="300" 
                                value="{{ number_format($azTarget ?: 120.0, 1, '.', '') }}"
                                class="w-full h-9 px-3 rounded-xl border border-line bg-panel text-ink text-xs font-mono font-semibold focus:border-blue-500 focus:outline-none"
                            >
                            <span class="absolute right-3 top-2 text-xs text-muted">&deg;</span>
                        </div>
                    </div>

                    <button 
                        type="button" 
                        onclick="dispatchMoveCommand()"
                        class="w-full h-9 py-1.5 px-3 rounded-xl border border-blue-600 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs transition-colors flex items-center justify-center gap-1.5 cursor-pointer mt-1 shadow-xs"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                        <span>{{ __('Kirim Sudut Target') }}</span>
                    </button>
                </div>
            </div>

            {{-- 3. Tombol Darurat & Kalibrasi --}}
            <div class="p-4 rounded-xl border border-line bg-panel-alt/60 space-y-3">
                <div class="flex items-center justify-between">
                    <span class="font-bold text-xs text-ink uppercase tracking-wider">3. {{ __('Tindakan Darurat') }}</span>
                    <span class="text-[10px] text-rose-600 dark:text-rose-400 font-semibold">{{ __('Prioritas') }}</span>
                </div>

                <div class="space-y-2">
                    <button 
                        type="button" 
                        onclick="dispatchTrackerCommand('stop', {})"
                        class="w-full py-2.5 px-3 rounded-xl border border-rose-300 dark:border-rose-800/60 bg-rose-50 dark:bg-rose-950/40 hover:bg-rose-100 dark:hover:bg-rose-900/40 text-rose-700 dark:text-rose-300 font-bold text-xs transition-colors flex items-center justify-center gap-2 cursor-pointer"
                    >
                        <svg class="w-4 h-4 text-rose-600 dark:text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 9h6v6H9z" />
                        </svg>
                        <span>{{ __('HENTIKAN SEGERA (HALT)') }}</span>
                    </button>

                    <button 
                        type="button" 
                        onclick="dispatchTrackerCommand('stow', {})"
                        class="w-full py-2.5 px-3 rounded-xl border border-amber-300 dark:border-amber-800/60 bg-amber-50 dark:bg-amber-950/40 hover:bg-amber-100 dark:hover:bg-amber-900/40 text-amber-800 dark:text-amber-300 font-semibold text-xs transition-colors flex items-center justify-center gap-2 cursor-pointer"
                    >
                        <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                        </svg>
                        <span>{{ __('Paksa Posisi Aman (Stow)') }}</span>
                    </button>

                    <button 
                        type="button" 
                        onclick="dispatchTrackerCommand('calibrate', {})"
                        class="w-full py-2.5 px-3 rounded-xl border border-line bg-panel hover:bg-panel-alt text-ink font-medium text-xs transition-colors flex items-center justify-center gap-2 cursor-pointer"
                    >
                        <svg class="w-4 h-4 text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <span>{{ __('Rekalibrasi IMU BNO055') }}</span>
                    </button>
                </div>
            </div>
        </div>
    </x-card>
</div>
