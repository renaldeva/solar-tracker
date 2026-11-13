<x-layouts.app title="{{ __('Dual-Axis Solar Tracker') }}">
    {{-- 1. Header, Device Selector, & Large Alignment Status Card --}}
    @include('solar-tracker.header')

    {{-- 2. 4 Core KPI Cards: Elevasi, Azimut, Angin, Output Listrik --}}
    @include('solar-tracker.telemetry-overview')

    {{-- 3. Kinematics Orientation: Elevation Pitch & 360° Azimuth Compass --}}
    @include('solar-tracker.kinematics')

    {{-- 4. Form Kirim Perintah (Kartu Terpisah Bersih) --}}
    @include('solar-tracker.commands')

    <!-- 5. Bagian "Lihat Detail" (Kalibrasi IMU, GPS, RTC, Riwayat Telemetri) -->
    <div x-data="{ showTechDetail: false }" class="mb-6">
        <button 
            type="button" 
            @click="showTechDetail = !showTechDetail" 
            class="w-full flex items-center justify-between p-4 rounded-2xl border border-line bg-panel hover:bg-panel-alt text-sm font-semibold text-ink transition-colors cursor-pointer shadow-2xs"
        >
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-xl bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0 border border-blue-100 dark:border-blue-800/60">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <span>{{ __('Lihat Detail Teknis (Kalibrasi IMU, GPS, RTC, dan Riwayat Data)') }}</span>
            </div>
            <svg class="w-5 h-5 text-muted transform transition-transform duration-200" :class="showTechDetail ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <div x-show="showTechDetail" x-cloak class="mt-4 space-y-6">
            @include('solar-tracker.subsystems')
            @include('solar-tracker.history')
        </div>
    </div>

    @push('scripts')
    <script>
        const DEVICE_UID = '{{ $device->uid }}';
        const CSRF_TOKEN = '{{ csrf_token() }}';
        let currentPollingInterval = null;

        function dispatchMoveCommand() {
            const elevInput = document.getElementById('inputTargetElev');
            const azInput = document.getElementById('inputTargetAz');

            const elev = parseFloat(elevInput.value);
            const az = parseFloat(azInput.value);

            if (isNaN(elev) || elev < 10 || elev > 85) {
                alert(window.__ ? window.__('Target elevation must be between 10.0° and 85.0°.') : 'Target elevation must be between 10.0° and 85.0°.');
                return;
            }

            if (isNaN(az) || az < 60 || az > 300) {
                alert(window.__ ? window.__('Target azimuth must be between 60.0° and 300.0°.') : 'Target azimuth must be between 60.0° and 300.0°.');
                return;
            }

            dispatchTrackerCommand('move_to', { elev: elev, az: az });
        }

        async function dispatchTrackerCommand(type, params) {
            const card = document.getElementById('commandExecutionCard');
            const title = document.getElementById('cmdTitle');
            const uuidLabel = document.getElementById('cmdUuidLabel');
            const banner = document.getElementById('cmdMessageBanner');
            const spinner = document.getElementById('cmdSpinner');

            // Reset UI step indicators
            if (card) card.classList.remove('hidden');
            if (title) {
                title.textContent = window.__ ? window.__("Mengirim perintah ':type'...", { type: type }) : `Mengirim '${type}'...`;
                title.className = 'text-xs font-bold uppercase tracking-wider text-blue-600';
            }
            if (uuidLabel) uuidLabel.textContent = '';
            if (spinner) spinner.classList.remove('hidden');
            if (banner) {
                banner.textContent = window.__ ? window.__('Mengirimkan payload perintah melalui broker MQTT...') : 'Mengirimkan payload perintah melalui broker MQTT...';
                banner.className = 'text-xs text-muted bg-panel p-3 rounded-lg border border-line';
            }

            setStepActive('step-sent');
            setStepIdle('step-accepted');
            setStepIdle('step-executing');
            setStepIdle('step-completed');

            try {
                const response = await fetch(`/tracker/${DEVICE_UID}/commands`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        type: type,
                        params: params
                    })
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    if (title) title.textContent = window.__ ? window.__('Perintah Diantrekan: :type', { type: type }) : `Perintah Diantrekan: ${type}`;
                    if (uuidLabel) uuidLabel.textContent = `UUID: ${data.command_id.substring(0, 8)}...`;
                    if (banner) banner.textContent = data.message;
                    
                    // Begin polling status
                    startStatusPolling(data.command_id);
                } else {
                    if (spinner) spinner.classList.add('hidden');
                    if (title) {
                        title.textContent = window.__ ? window.__('Perintah Ditolak Interlock') : 'Perintah Ditolak Interlock';
                        title.className = 'text-xs font-bold uppercase tracking-wider text-rose-600';
                    }
                    if (banner) {
                        banner.textContent = data.message || (window.__ ? window.__('Penolakan interlock keselamatan atau hak akses.') : 'Penolakan interlock keselamatan atau hak akses.');
                        banner.className = 'text-xs text-rose-700 bg-rose-50 p-3 rounded-lg border border-rose-200';
                    }
                    setStepError('step-sent');
                    refreshCommandsLog();
                }
            } catch (err) {
                console.error(err);
                if (spinner) spinner.classList.add('hidden');
                if (title) {
                    title.textContent = window.__ ? window.__('Kesalahan Jaringan') : 'Kesalahan Jaringan';
                    title.className = 'text-xs font-bold uppercase tracking-wider text-rose-600';
                }
                if (banner) {
                    banner.textContent = window.__ ? window.__('Gagal berkomunikasi dengan backend aplikasi web.') : 'Gagal berkomunikasi dengan backend aplikasi web.';
                    banner.className = 'text-xs text-rose-700 bg-rose-50 p-3 rounded-lg border border-rose-200';
                }
            }
        }

        function startStatusPolling(commandId) {
            if (currentPollingInterval) {
                clearInterval(currentPollingInterval);
            }

            let pollCount = 0;
            currentPollingInterval = setInterval(async () => {
                pollCount++;
                try {
                    const res = await fetch(`/commands/${commandId}/status`);
                    if (!res.ok) return;

                    const data = await res.json();
                    updateExecutionTimeline(data);

                    if (data.is_terminal || pollCount > 30) {
                        clearInterval(currentPollingInterval);
                        refreshCommandsLog();
                    }
                } catch (e) {
                    console.error('Polling error', e);
                }
            }, 1000);
        }

        function updateExecutionTimeline(cmd) {
            const title = document.getElementById('cmdTitle');
            const banner = document.getElementById('cmdMessageBanner');
            const spinner = document.getElementById('cmdSpinner');

            if (cmd.status === 'accepted') {
                setStepActive('step-accepted');
                setStepActive('step-executing');
                if (title) title.textContent = window.__ ? window.__('Perintah Diterima & Aktuator Bergerak') : 'Perintah Diterima & Aktuator Bergerak';
                if (banner) banner.textContent = window.__ ? window.__('ESP32 mengonfirmasi perintah pada :time. Motor BTS7960 diaktifkan.', { time: cmd.accepted_at }) : `ESP32 mengonfirmasi perintah pada ${cmd.accepted_at}. Motor BTS7960 diaktifkan.`;
            } else if (cmd.status === 'completed') {
                if (spinner) spinner.classList.add('hidden');
                setStepActive('step-accepted');
                setStepActive('step-executing');
                setStepSuccess('step-completed');
                if (title) {
                    title.textContent = window.__ ? window.__('Eksekusi Selesai Berhasil') : 'Eksekusi Selesai Berhasil';
                    title.className = 'text-xs font-bold uppercase tracking-wider text-emerald-600';
                }
                if (banner) {
                    banner.textContent = window.__ ? window.__('Pelacak menyelesaikan target stroke pada :time. Aktuator menahan posisi.', { time: cmd.completed_at }) : `Pelacak menyelesaikan target stroke pada ${cmd.completed_at}. Aktuator menahan posisi.`;
                    banner.className = 'text-xs text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/40 p-3 rounded-lg border border-emerald-200 dark:border-emerald-800/60';
                }
            } else if (cmd.status === 'rejected' || cmd.status === 'failed') {
                if (spinner) spinner.classList.add('hidden');
                if (title) {
                    title.textContent = window.__ ? window.__('Perintah :status', { status: cmd.status.toUpperCase() }) : `Perintah ${cmd.status.toUpperCase()}`;
                    title.className = 'text-xs font-bold uppercase tracking-wider text-rose-600 dark:text-rose-400';
                }
                if (banner) {
                    banner.textContent = window.__ ? window.__('Alasan: :reason', { reason: cmd.reason || (window.__('Kondisi keselamatan perangkat keras memicu penolakan.')) }) : `Alasan: ${cmd.reason || 'Kondisi keselamatan perangkat keras memicu penolakan.'}`;
                    banner.className = 'text-xs text-rose-700 dark:text-rose-300 bg-rose-50 dark:bg-rose-950/40 p-3 rounded-lg border border-rose-200 dark:border-rose-800/60';
                }
                setStepError('step-executing');
            } else if (cmd.status === 'timeout') {
                if (spinner) spinner.classList.add('hidden');
                if (title) {
                    title.textContent = window.__ ? window.__('Perintah Waktu Habis') : 'Perintah Waktu Habis';
                    title.className = 'text-xs font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400';
                }
                if (banner) {
                    banner.textContent = window.__ ? window.__('Perangkat tidak melaporkan penyelesaian dalam batas waktu keselamatan.') : 'Perangkat tidak melaporkan penyelesaian dalam batas waktu keselamatan.';
                    banner.className = 'text-xs text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-950/40 p-3 rounded-lg border border-amber-200 dark:border-amber-800/60';
                }
            }
        }

        function setStepActive(id) {
            const el = document.getElementById(id);
            if (!el) return;
            el.className = 'p-2.5 rounded-lg bg-blue-50 dark:bg-blue-950/40 border border-blue-300 dark:border-blue-800/60 text-blue-700 dark:text-blue-300 font-semibold animate-pulse text-xs';
        }

        function setStepSuccess(id) {
            const el = document.getElementById(id);
            if (!el) return;
            el.className = 'p-2.5 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-300 dark:border-emerald-800/60 text-emerald-700 dark:text-emerald-300 font-bold text-xs';
        }

        function setStepError(id) {
            const el = document.getElementById(id);
            if (!el) return;
            el.className = 'p-2.5 rounded-lg bg-rose-50 dark:bg-rose-950/40 border border-rose-300 dark:border-rose-800/60 text-rose-700 dark:text-rose-300 font-bold text-xs';
        }

        function setStepIdle(id) {
            const el = document.getElementById(id);
            if (!el) return;
            el.className = 'p-2.5 rounded-lg bg-panel border border-line text-muted font-medium text-xs';
        }

        async function refreshCommandsLog() {
            try {
                const res = await fetch(`/tracker/${DEVICE_UID}/commands`);
                if (!res.ok) return;

                const data = await res.json();
                const tbody = document.getElementById('recentCommandsTableBody');
                if (!tbody) return;

                if (!data.commands || data.commands.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="6" class="px-3.5 py-4 text-center text-muted font-sans text-xs">${window.__ ? window.__('Tidak ada perintah yang dikeluarkan baru-baru ini.') : 'Tidak ada perintah yang dikeluarkan baru-baru ini.'}</td></tr>`;
                    return;
                }

                tbody.innerHTML = data.commands.map(cmd => {
                    let statusBadge = `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold border border-blue-200 dark:border-blue-800/60 bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300"><span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span>${cmd.status}</span>`;
                    if (cmd.status === 'completed') {
                        statusBadge = `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold border border-emerald-200 dark:border-emerald-800/60 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300"><span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>${window.__ ? window.__('Completed') : 'Completed'}</span>`;
                    } else if (cmd.status === 'rejected' || cmd.status === 'failed') {
                        statusBadge = `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold border border-rose-200 dark:border-rose-800/60 bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300" title="${cmd.reason || ''}"><span class="w-1.5 h-1.5 rounded-full bg-rose-600"></span>${cmd.status}</span>`;
                    } else if (cmd.status === 'timeout') {
                        statusBadge = `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold border border-amber-200 dark:border-amber-800/60 bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300"><span class="w-1.5 h-1.5 rounded-full bg-amber-600"></span>${window.__ ? window.__('Timeout') : 'Timeout'}</span>`;
                    }

                    return `
                        <tr class="hover:bg-panel-alt transition-colors">
                            <td class="px-3.5 py-2.5 text-muted whitespace-nowrap font-mono">${cmd.issued_at || '--'}</td>
                            <td class="px-3.5 py-2.5 font-semibold text-ink whitespace-nowrap">${cmd.type}</td>
                            <td class="px-3.5 py-2.5 text-muted max-w-xs truncate font-mono text-[11px]">${cmd.params ? JSON.stringify(cmd.params) : '--'}</td>
                            <td class="px-3.5 py-2.5 whitespace-nowrap">${statusBadge}</td>
                            <td class="px-3.5 py-2.5 text-ink whitespace-nowrap">${cmd.issued_by || 'System'}</td>
                            <td class="px-3.5 py-2.5 text-right text-muted whitespace-nowrap font-mono">${cmd.duration_s !== null ? cmd.duration_s + 's' : '--'}</td>
                        </tr>
                    `;
                }).join('');
            } catch (e) {
                console.error('Gagal memuat ulang log perintah', e);
            }
        }
    </script>
    @endpush
</x-layouts.app>
