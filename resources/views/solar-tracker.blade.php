<x-layouts.app title="{{ __('Dual-Axis Solar Tracker') }}">
    {{-- 1. Header, Device Selector, & High Wind Warning Banner --}}
    @include('solar-tracker.header')

    {{-- 2. Kinematics Orientation: Elevation Pitch & 360° Azimuth Compass --}}
    @include('solar-tracker.kinematics')

    {{-- 3. Core Telemetry Overview: Alignment, DC Output, Actuators, Wind & Temperature --}}
    @include('solar-tracker.telemetry-overview')

    {{-- 4. Subsystems: BNO055 IMU Calibration & GPS / RTC Time Reference --}}
    @include('solar-tracker.subsystems')

    {{-- 5. Supervisory Actuator Control Panel & Command Dispatch --}}
    @include('solar-tracker.commands')

    {{-- 6. Recent Telemetry Event Log Table --}}
    @include('solar-tracker.history')

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
                title.className = 'text-xs font-bold uppercase tracking-wider text-accent font-mono';
            }
            if (uuidLabel) uuidLabel.textContent = '';
            if (spinner) spinner.classList.remove('hidden');
            if (banner) {
                banner.textContent = window.__ ? window.__('Mengirimkan payload perintah melalui broker MQTT Mosquitto...') : 'Mengirimkan payload perintah melalui broker MQTT Mosquitto...';
                banner.className = 'text-xs text-muted font-mono bg-panel p-2.5 rounded border border-line';
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
                        title.className = 'text-xs font-bold uppercase tracking-wider text-danger font-mono';
                    }
                    if (banner) {
                        banner.textContent = data.message || (window.__ ? window.__('Penolakan interlock keselamatan atau hak akses.') : 'Penolakan interlock keselamatan atau hak akses.');
                        banner.className = 'text-xs text-danger font-mono bg-danger/10 p-2.5 rounded border border-danger/30';
                    }
                    setStepError('step-sent');
                    refreshCommandsLog();
                }
            } catch (err) {
                console.error(err);
                if (spinner) spinner.classList.add('hidden');
                if (title) {
                    title.textContent = window.__ ? window.__('Kesalahan Jaringan') : 'Kesalahan Jaringan';
                    title.className = 'text-xs font-bold uppercase tracking-wider text-danger font-mono';
                }
                if (banner) {
                    banner.textContent = window.__ ? window.__('Gagal berkomunikasi dengan backend aplikasi web.') : 'Gagal berkomunikasi dengan backend aplikasi web.';
                    banner.className = 'text-xs text-danger font-mono bg-danger/10 p-2.5 rounded border border-danger/30';
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
                    title.className = 'text-xs font-bold uppercase tracking-wider text-ok font-mono';
                }
                if (banner) {
                    banner.textContent = window.__ ? window.__('Pelacak menyelesaikan target stroke pada :time. Aktuator menahan posisi.', { time: cmd.completed_at }) : `Pelacak menyelesaikan target stroke pada ${cmd.completed_at}. Aktuator menahan posisi.`;
                    banner.className = 'text-xs text-ok font-mono bg-ok/10 p-2.5 rounded border border-ok/30';
                }
            } else if (cmd.status === 'rejected' || cmd.status === 'failed') {
                if (spinner) spinner.classList.add('hidden');
                if (title) {
                    title.textContent = window.__ ? window.__('Perintah :status', { status: cmd.status.toUpperCase() }) : `Perintah ${cmd.status.toUpperCase()}`;
                    title.className = 'text-xs font-bold uppercase tracking-wider text-danger font-mono';
                }
                if (banner) {
                    banner.textContent = window.__ ? window.__('Alasan: :reason', { reason: cmd.reason || (window.__('Kondisi keselamatan perangkat keras memicu penolakan.')) }) : `Alasan: ${cmd.reason || 'Kondisi keselamatan perangkat keras memicu penolakan.'}`;
                    banner.className = 'text-xs text-danger font-mono bg-danger/10 p-2.5 rounded border border-danger/30';
                }
                setStepError('step-executing');
            } else if (cmd.status === 'timeout') {
                if (spinner) spinner.classList.add('hidden');
                if (title) {
                    title.textContent = window.__ ? window.__('Perintah Waktu Habis') : 'Perintah Waktu Habis';
                    title.className = 'text-xs font-bold uppercase tracking-wider text-warn font-mono';
                }
                if (banner) {
                    banner.textContent = window.__ ? window.__('Perangkat tidak melaporkan penyelesaian dalam jendela batas waktu keselamatan.') : 'Perangkat tidak melaporkan penyelesaian dalam jendela batas waktu keselamatan.';
                    banner.className = 'text-xs text-warn font-mono bg-warn/10 p-2.5 rounded border border-warn/30';
                }
            }
        }

        function setStepActive(id) {
            const el = document.getElementById(id);
            if (!el) return;
            el.className = 'p-2 rounded bg-accent/20 border border-accent/60 text-accent font-semibold animate-pulse font-mono text-xs';
        }

        function setStepSuccess(id) {
            const el = document.getElementById(id);
            if (!el) return;
            el.className = 'p-2 rounded bg-ok/20 border border-ok/60 text-ok font-bold font-mono text-xs';
        }

        function setStepError(id) {
            const el = document.getElementById(id);
            if (!el) return;
            el.className = 'p-2 rounded bg-danger/20 border border-danger/60 text-danger font-bold font-mono text-xs';
        }

        function setStepIdle(id) {
            const el = document.getElementById(id);
            if (!el) return;
            el.className = 'p-2 rounded bg-panel border border-line text-muted font-medium font-mono text-xs';
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
                    let statusBadge = `<span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-mono border border-accent/30 bg-accent/10 text-accent"><span class="w-1.5 h-1.5 rounded-full bg-accent"></span>${cmd.status}</span>`;
                    if (cmd.status === 'completed') {
                        statusBadge = `<span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-mono border border-ok/30 bg-ok/10 text-ok"><span class="w-1.5 h-1.5 rounded-full bg-ok"></span>${window.__ ? window.__('Completed') : 'Completed'}</span>`;
                    } else if (cmd.status === 'rejected' || cmd.status === 'failed') {
                        statusBadge = `<span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-mono border border-danger/30 bg-danger/10 text-danger" title="${cmd.reason || ''}"><span class="w-1.5 h-1.5 rounded-full bg-danger"></span>${cmd.status}</span>`;
                    } else if (cmd.status === 'timeout') {
                        statusBadge = `<span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-mono border border-warn/30 bg-warn/10 text-warn"><span class="w-1.5 h-1.5 rounded-full bg-warn"></span>${window.__ ? window.__('Timeout') : 'Timeout'}</span>`;
                    }

                    return `
                        <tr class="hover:bg-bg/40 transition-colors">
                            <td class="px-3.5 py-2 text-muted whitespace-nowrap">${cmd.issued_at || '--'}</td>
                            <td class="px-3.5 py-2 font-semibold text-ink whitespace-nowrap">${cmd.type}</td>
                            <td class="px-3.5 py-2 text-muted max-w-xs truncate">${cmd.params ? JSON.stringify(cmd.params) : '--'}</td>
                            <td class="px-3.5 py-2 whitespace-nowrap">${statusBadge}</td>
                            <td class="px-3.5 py-2 text-muted whitespace-nowrap">${cmd.issued_by || 'System'}</td>
                            <td class="px-3.5 py-2 text-right text-muted whitespace-nowrap">${cmd.duration_s !== null ? cmd.duration_s + 's' : '--'}</td>
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
