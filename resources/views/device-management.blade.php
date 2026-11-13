<x-layouts.app :title="__('Manajemen Perangkat')">
    <!-- Page Header & Action Button -->
    <x-page-header 
        :title="__('Manajemen Perangkat & Node Telemetri')" 
        :subtitle="__('Daftarkan perangkat pelacak surya, konfigurasikan batas gerak fisik, kelola mode aman angin, dan provisi klien MQTT.')"
        :badge="__('Registri Perangkat Keras')"
    >
        <x-slot:actions>
            <div class="flex items-center space-x-2">
                <button 
                    type="button" 
                    onclick="document.getElementById('addDeviceModal').classList.remove('hidden')"
                    class="inline-flex items-center h-10 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold transition-colors shadow-xs cursor-pointer"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>{{ __('Daftarkan Pelacak') }}</span>
                </button>
            </div>
        </x-slot:actions>
    </x-page-header>

    @if(session('status'))
        <div class="mb-5 p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 text-xs text-emerald-800 dark:text-emerald-300 flex items-center justify-between shadow-xs">
            <div class="flex items-center space-x-2.5">
                <svg class="w-4 h-4 shrink-0 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span class="font-medium">{{ session('status') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-emerald-700 dark:text-emerald-300 hover:opacity-75 cursor-pointer text-lg leading-none">&times;</button>
        </div>
    @endif

    @if($errors->any())
        <div class="mb-5 p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/60 text-xs text-rose-800 dark:text-rose-300 shadow-xs">
            <div class="font-semibold mb-1">{{ __('Mohon perbaiki kesalahan berikut:') }}</div>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Status Banner: "Apakah semuanya baik-baik saja?" -->
    <div class="mb-6 p-4 rounded-2xl bg-panel border border-line shadow-xs flex items-center justify-between">
        <div class="flex items-center space-x-3.5">
            <div class="w-3.5 h-3.5 rounded-full bg-emerald-500 ring-4 ring-emerald-50 dark:ring-emerald-950/50"></div>
            <div>
                <h2 class="text-sm font-semibold text-ink">
                    Registri Node Pelacak Sumbu Ganda Aktif
                </h2>
                <p class="text-xs text-muted mt-0.5">
                    Terdapat <strong class="text-ink">{{ $devices->count() }} unit</strong> pelacak terkonfigurasi pada sistem SCADA.
                </p>
            </div>
        </div>
        <div class="hidden sm:flex items-center space-x-2">
            <span class="px-3 py-1 rounded-xl text-xs font-mono font-semibold bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800/60">
                {{ $devices->count() }} UNIT
            </span>
        </div>
    </div>

    <!-- Device Registry Table Panel (Smart Nursery Style) -->
    <div class="bg-panel border border-line rounded-2xl overflow-hidden mb-6 shadow-xs">
        <div class="p-4 sm:p-5 border-b border-line flex items-center justify-between bg-panel-alt/60">
            <div>
                <h3 class="text-sm font-semibold text-ink">{{ __('Node Pelacak Sumbu Ganda Terkonfigurasi') }}</h3>
                <p class="text-xs text-muted mt-0.5">{{ __('Pengaturan batas gerak sudut, batas angin kencang, dan provisi MQTT.') }}</p>
            </div>
            <span class="text-xs text-muted font-mono font-medium">{{ $devices->count() }} {{ __('Unit Terdaftar') }}</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-panel-alt text-muted uppercase text-[11px] font-semibold tracking-wider border-b border-line">
                    <tr>
                        <th class="py-3 px-4">{{ __('UID Perangkat') }}</th>
                        <th class="py-3 px-4">{{ __('Nama') }}</th>
                        <th class="py-3 px-4">{{ __('Lokasi Fasilitas') }}</th>
                        <th class="py-3 px-4">{{ __('Status') }}</th>
                        <th class="py-3 px-4">{{ __('Batas Elevasi') }}</th>
                        <th class="py-3 px-4">{{ __('Batas Azimut') }}</th>
                        <th class="py-3 px-4">{{ __('Batas Aman Angin') }}</th>
                        <th class="py-3 px-4">{{ __('Label Sensor DC') }}</th>
                        <th class="py-3 px-4 text-right">{{ __('Aksi') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line font-mono text-xs">
                    @forelse($devices as $device)
                        @php
                            $settings = $device->settings ?? \App\Models\Device::defaultSettings();
                        @endphp
                        <tr class="hover:bg-panel-alt/50 transition-colors">
                            <td class="py-3 px-4 font-bold text-ink">{{ $device->uid }}</td>
                            <td class="py-3 px-4 font-sans text-ink font-semibold">{{ $device->name }}</td>
                            <td class="py-3 px-4 font-sans text-muted">{{ $device->site?->name ?? __('Fasilitas Default') }}</td>
                            <td class="py-3 px-4">
                                <x-status-pill :status="$device->status" />
                            </td>
                            <td class="py-3 px-4 text-ink">
                                {{ app()->getLocale() === 'id' ? formatNumberId($settings['elev_min'] ?? 10, 0) : ($settings['elev_min'] ?? 10) }}&deg; &ndash; {{ app()->getLocale() === 'id' ? formatNumberId($settings['elev_max'] ?? 85, 0) : ($settings['elev_max'] ?? 85) }}&deg;
                            </td>
                            <td class="py-3 px-4 text-ink">
                                {{ app()->getLocale() === 'id' ? formatNumberId($settings['az_min'] ?? 60, 0) : ($settings['az_min'] ?? 60) }}&deg; &ndash; {{ app()->getLocale() === 'id' ? formatNumberId($settings['az_max'] ?? 300, 0) : ($settings['az_max'] ?? 300) }}&deg;
                            </td>
                            <td class="py-3 px-4 text-ink font-semibold">
                                {{ app()->getLocale() === 'id' ? formatNumberId($settings['wind_stow_ms'] ?? 15.0, 1) : number_format($settings['wind_stow_ms'] ?? 15.0, 1) }} m/s
                            </td>
                            <td class="py-3 px-4 font-sans text-muted">
                                {{ __($settings['measurement_label'] ?? 'Solar array') }}
                            </td>
                            <td class="py-3 px-4 font-sans text-right space-x-1.5 whitespace-nowrap">
                                <button 
                                    type="button" 
                                    onclick="showMqttModal('{{ $device->uid }}', '{{ $device->mqtt_username ?? 'device_'.$device->uid }}')"
                                    class="inline-flex items-center px-2.5 py-1 rounded-lg border border-line bg-panel hover:bg-panel-alt text-ink text-xs font-semibold transition-colors shadow-2xs"
                                    title="{{ __('Lihat Topik MQTT') }}"
                                >
                                    {{ __('Info MQTT') }}
                                </button>
                                <button 
                                    type="button" 
                                    onclick="openEditDeviceModal(@js($device))"
                                    class="inline-flex items-center px-2.5 py-1 rounded-lg border border-blue-200 dark:border-blue-800/60 text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-950/40 hover:bg-blue-100 dark:hover:bg-blue-900/40 text-xs font-semibold transition-colors shadow-2xs"
                                >
                                    {{ __('Konfigurasi') }}
                                </button>
                                <form method="POST" action="{{ route('device-management.destroy', $device) }}" class="inline-block" onsubmit="return confirm('{{ __('Apakah Anda yakin ingin menghapus pelacak') }} {{ $device->uid }}? {{ __('Semua data telemetri historis akan tetap tersimpan.') }}');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex items-center px-2.5 py-1 rounded-lg border border-rose-200 dark:border-rose-800/60 text-rose-700 dark:text-rose-300 bg-rose-50 dark:bg-rose-950/40 hover:bg-rose-100 dark:hover:bg-rose-900/40 text-xs font-semibold transition-colors cursor-pointer shadow-2xs">
                                        {{ __('Hapus') }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="p-8 text-center text-muted font-sans text-sm">
                                <div class="w-12 h-12 rounded-2xl bg-panel-alt text-muted mx-auto flex items-center justify-center mb-3">
                                    <svg class="w-6 h-6 text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                </div>
                                <div class="text-sm font-semibold text-ink">{{ __('Belum ada pelacak surya terdaftar. Klik "Daftarkan Pelacak" di atas untuk menambahkan perangkat.') }}</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Provisioning Guide Card -->
    <div class="bg-panel border border-line rounded-2xl p-5 mb-6 shadow-xs">
        <h3 class="text-sm font-semibold text-ink flex items-center space-x-2">
            <div class="w-7 h-7 rounded-lg bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                </svg>
            </div>
            <span>{{ __('Petunjuk Provisi Perangkat Keras ESP32') }}</span>
        </h3>
        <p class="text-xs text-muted mt-2">
            {{ __('Setelah mendaftarkan pelacak, buat kredensial unik perangkat dan tambahkan izin ACL ke broker Mosquitto:') }}
        </p>
        <div class="mt-3 bg-panel-alt border border-line rounded-xl p-3.5 font-mono text-xs text-ink overflow-x-auto">
            <span class="text-muted">{{ __('# Jalankan dari host atau di dalam container:') }}</span><br>
            <span class="text-emerald-600 dark:text-emerald-400 font-bold">./scripts/mqtt-add-device.sh</span> &lt;device_uid&gt;<br><br>
            <span class="text-muted">{{ __('# Contoh:') }}</span><br>
            <span class="text-emerald-600 dark:text-emerald-400 font-bold">./scripts/mqtt-add-device.sh</span> <span class="text-blue-600 dark:text-blue-400 font-bold">ST-DEMO-002</span>
        </div>
    </div>

    <!-- Modal: Register Tracker -->
    <div id="addDeviceModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-panel rounded-2xl max-w-xl w-full border border-line overflow-hidden shadow-xl">
            <div class="px-6 py-4.5 border-b border-line flex items-center justify-between bg-panel-alt/60">
                <div>
                    <h3 class="text-sm font-bold text-ink">{{ __('Daftarkan Pelacak Surya Baru') }}</h3>
                    <p class="text-xs text-muted mt-0.5">{{ __('Tambahkan node edge ESP32 ke sistem pemantauan') }}</p>
                </div>
                <button type="button" onclick="document.getElementById('addDeviceModal').classList.add('hidden')" class="text-muted hover:text-ink cursor-pointer">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form method="POST" action="{{ route('device-management.store') }}" class="p-6 space-y-4 text-xs">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <label class="block font-semibold text-muted uppercase tracking-wider mb-1.5">{{ __('UID Perangkat') }} *</label>
                        <input type="text" name="uid" placeholder="misal: ST-DEMO-002" required class="w-full text-xs font-mono uppercase bg-panel border border-line rounded-xl px-3 py-2 text-ink focus:outline-none focus:border-blue-500 shadow-2xs">
                    </div>
                    <div>
                        <label class="block font-semibold text-muted uppercase tracking-wider mb-1.5">{{ __('Nama Pelacak') }} *</label>
                        <input type="text" name="name" placeholder="misal: Tracker Array 02" required class="w-full text-xs bg-panel border border-line rounded-xl px-3 py-2 text-ink focus:outline-none focus:border-blue-500 shadow-2xs">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <label class="block font-semibold text-muted uppercase tracking-wider mb-1.5">{{ __('Lokasi Fasilitas') }} *</label>
                        <div class="relative">
                            <select name="site_id" required class="appearance-none w-full text-xs bg-panel border border-line rounded-xl pl-3 pr-8 py-2 text-ink focus:outline-none focus:border-blue-500 shadow-2xs">
                                @foreach($sites as $site)
                                    <option value="{{ $site->id }}">{{ $site->name }}</option>
                                @endforeach
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-muted">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block font-semibold text-muted uppercase tracking-wider mb-1.5">{{ __('Label Pengukuran DC') }}</label>
                        <input type="text" name="measurement_label" value="{{ __('Panel surya') }}" required class="w-full text-xs bg-panel border border-line rounded-xl px-3 py-2 text-ink focus:outline-none focus:border-blue-500 shadow-2xs">
                    </div>
                </div>

                <div class="pt-3 border-t border-line">
                    <span class="text-xs font-bold text-ink">{{ __('Batas Gerak Fisik & Mode Aman Angin') }}</span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                    <div>
                        <label class="block text-[11px] text-muted mb-1">{{ __('Elev Min (°)') }}</label>
                        <input type="number" step="0.5" name="elev_min" value="10.0" required class="w-full text-xs font-mono bg-panel border border-line rounded-xl px-2.5 py-1.5 text-ink focus:outline-none focus:border-blue-500 shadow-2xs">
                    </div>
                    <div>
                        <label class="block text-[11px] text-muted mb-1">{{ __('Elev Maks (°)') }}</label>
                        <input type="number" step="0.5" name="elev_max" value="85.0" required class="w-full text-xs font-mono bg-panel border border-line rounded-xl px-2.5 py-1.5 text-ink focus:outline-none focus:border-blue-500 shadow-2xs">
                    </div>
                    <div>
                        <label class="block text-[11px] text-muted mb-1">{{ __('Az Min (°)') }}</label>
                        <input type="number" step="0.5" name="az_min" value="60.0" required class="w-full text-xs font-mono bg-panel border border-line rounded-xl px-2.5 py-1.5 text-ink focus:outline-none focus:border-blue-500 shadow-2xs">
                    </div>
                    <div>
                        <label class="block text-[11px] text-muted mb-1">{{ __('Az Maks (°)') }}</label>
                        <input type="number" step="0.5" name="az_max" value="300.0" required class="w-full text-xs font-mono bg-panel border border-line rounded-xl px-2.5 py-1.5 text-ink focus:outline-none focus:border-blue-500 shadow-2xs">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                    <div>
                        <label class="block text-[11px] text-muted mb-1">{{ __('Batas Aman Angin (m/s)') }}</label>
                        <input type="number" step="0.5" name="wind_stow_ms" value="15.0" required class="w-full text-xs font-mono bg-panel border border-line rounded-xl px-2.5 py-1.5 text-ink focus:outline-none focus:border-blue-500 shadow-2xs">
                    </div>
                    <div>
                        <label class="block text-[11px] text-muted mb-1">{{ __('Interval Telemetri (detik)') }}</label>
                        <input type="number" name="telemetry_interval_s" value="5" required class="w-full text-xs font-mono bg-panel border border-line rounded-xl px-2.5 py-1.5 text-ink focus:outline-none focus:border-blue-500 shadow-2xs">
                    </div>
                    <div>
                        <label class="block text-[11px] text-muted mb-1">{{ __('Target Harian (kWh)') }}</label>
                        <input type="number" step="0.5" name="daily_energy_target_kwh" value="50.0" required class="w-full text-xs font-mono bg-panel border border-line rounded-xl px-2.5 py-1.5 text-ink focus:outline-none focus:border-blue-500 shadow-2xs">
                    </div>
                </div>

                <div class="pt-4 border-t border-line flex items-center justify-end space-x-2">
                    <button type="button" onclick="document.getElementById('addDeviceModal').classList.add('hidden')" class="px-4 py-2 rounded-xl border border-line text-muted hover:text-ink text-xs font-medium transition-colors bg-panel cursor-pointer">
                        {{ __('Batal') }}
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold transition-colors shadow-xs cursor-pointer">
                        {{ __('Simpan Pelacak') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Edit Device -->
    <div id="editDeviceModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-panel rounded-2xl max-w-xl w-full border border-line overflow-hidden shadow-xl">
            <div class="px-6 py-4.5 border-b border-line flex items-center justify-between bg-panel-alt/60">
                <div>
                    <h3 class="text-sm font-bold text-ink">{{ __('Konfigurasi Pelacak:') }} <span id="editModalUid" class="font-mono text-blue-600 dark:text-blue-400"></span></h3>
                    <p class="text-xs text-muted mt-0.5">{{ __('Perbarui parameter kinematik dan pengaturan sensor') }}</p>
                </div>
                <button type="button" onclick="document.getElementById('editDeviceModal').classList.add('hidden')" class="text-muted hover:text-ink cursor-pointer">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form id="editDeviceForm" method="POST" action="" class="p-6 space-y-4 text-xs">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <label class="block font-semibold text-muted uppercase tracking-wider mb-1.5">{{ __('Nama Pelacak') }} *</label>
                        <input type="text" id="editName" name="name" required class="w-full text-xs bg-panel border border-line rounded-xl px-3 py-2 text-ink focus:outline-none focus:border-blue-500 shadow-2xs">
                    </div>
                    <div>
                        <label class="block font-semibold text-muted uppercase tracking-wider mb-1.5">{{ __('Lokasi Fasilitas') }} *</label>
                        <div class="relative">
                            <select id="editSiteId" name="site_id" required class="appearance-none w-full text-xs bg-panel border border-line rounded-xl pl-3 pr-8 py-2 text-ink focus:outline-none focus:border-blue-500 shadow-2xs">
                                @foreach($sites as $site)
                                    <option value="{{ $site->id }}">{{ $site->name }}</option>
                                @endforeach
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-muted">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <label class="block font-semibold text-muted uppercase tracking-wider mb-1.5">{{ __('Label Pengukuran DC') }}</label>
                        <input type="text" id="editLabel" name="measurement_label" required class="w-full text-xs bg-panel border border-line rounded-xl px-3 py-2 text-ink focus:outline-none focus:border-blue-500 shadow-2xs">
                    </div>
                    <div>
                        <label class="block font-semibold text-muted uppercase tracking-wider mb-1.5">{{ __('Target Harian (kWh)') }}</label>
                        <input type="number" step="0.5" id="editTarget" name="daily_energy_target_kwh" required class="w-full text-xs font-mono bg-panel border border-line rounded-xl px-3 py-2 text-ink focus:outline-none focus:border-blue-500 shadow-2xs">
                    </div>
                </div>

                <div class="pt-3 border-t border-line">
                    <span class="text-xs font-bold text-ink">{{ __('Batas Gerak Fisik & Mode Aman Angin') }}</span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                    <div>
                        <label class="block text-[11px] text-muted mb-1">{{ __('Elev Min (°)') }}</label>
                        <input type="number" step="0.5" id="editElevMin" name="elev_min" required class="w-full text-xs font-mono bg-panel border border-line rounded-xl px-2.5 py-1.5 text-ink focus:outline-none focus:border-blue-500 shadow-2xs">
                    </div>
                    <div>
                        <label class="block text-[11px] text-muted mb-1">{{ __('Elev Maks (°)') }}</label>
                        <input type="number" step="0.5" id="editElevMax" name="elev_max" required class="w-full text-xs font-mono bg-panel border border-line rounded-xl px-2.5 py-1.5 text-ink focus:outline-none focus:border-blue-500 shadow-2xs">
                    </div>
                    <div>
                        <label class="block text-[11px] text-muted mb-1">{{ __('Az Min (°)') }}</label>
                        <input type="number" step="0.5" id="editAzMin" name="az_min" required class="w-full text-xs font-mono bg-panel border border-line rounded-xl px-2.5 py-1.5 text-ink focus:outline-none focus:border-blue-500 shadow-2xs">
                    </div>
                    <div>
                        <label class="block text-[11px] text-muted mb-1">{{ __('Az Maks (°)') }}</label>
                        <input type="number" step="0.5" id="editAzMax" name="az_max" required class="w-full text-xs font-mono bg-panel border border-line rounded-xl px-2.5 py-1.5 text-ink focus:outline-none focus:border-blue-500 shadow-2xs">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                    <div>
                        <label class="block text-[11px] text-muted mb-1">{{ __('Batas Aman Angin (m/s)') }}</label>
                        <input type="number" step="0.5" id="editWindStow" name="wind_stow_ms" required class="w-full text-xs font-mono bg-panel border border-line rounded-xl px-2.5 py-1.5 text-ink focus:outline-none focus:border-blue-500 shadow-2xs">
                    </div>
                    <div>
                        <label class="block text-[11px] text-muted mb-1">{{ __('Interval Telemetri (detik)') }}</label>
                        <input type="number" id="editRate" name="telemetry_interval_s" required class="w-full text-xs font-mono bg-panel border border-line rounded-xl px-2.5 py-1.5 text-ink focus:outline-none focus:border-blue-500 shadow-2xs">
                    </div>
                </div>

                <div class="pt-4 border-t border-line flex items-center justify-end space-x-2">
                    <button type="button" onclick="document.getElementById('editDeviceModal').classList.add('hidden')" class="px-4 py-2 rounded-xl border border-line text-muted hover:text-ink text-xs font-medium transition-colors bg-panel cursor-pointer">
                        {{ __('Batal') }}
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold transition-colors shadow-xs cursor-pointer">
                        {{ __('Perbarui Konfigurasi') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: MQTT Topics & Provisioning Info -->
    <div id="mqttInfoModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-panel rounded-2xl max-w-lg w-full border border-line overflow-hidden shadow-xl">
            <div class="px-6 py-4.5 border-b border-line flex items-center justify-between bg-panel-alt/60">
                <div>
                    <h3 class="text-sm font-bold text-ink">{{ __('Topik MQTT & Kontrak Broker') }}</h3>
                    <p class="text-xs text-muted mt-0.5">{{ __('Node:') }} <span id="modalTopicUid" class="font-mono text-blue-600 dark:text-blue-400 font-bold"></span></p>
                </div>
                <button type="button" onclick="document.getElementById('mqttInfoModal').classList.add('hidden')" class="text-muted hover:text-ink cursor-pointer">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="p-6 space-y-3.5 text-xs">
                <div>
                    <span class="text-muted font-medium">{{ __('Alamat Broker:') }}</span>
                    <span class="font-mono bg-panel-alt border border-line px-2.5 py-1 rounded-lg text-ink font-semibold ml-1">127.0.0.1:1883</span>
                </div>
                <div>
                    <span class="text-muted font-medium">{{ __('Nama Pengguna MQTT:') }}</span>
                    <span id="modalTopicUser" class="font-mono bg-panel-alt border border-line px-2.5 py-1 rounded-lg text-ink font-semibold ml-1"></span>
                </div>

                <div class="pt-2 border-t border-line space-y-2 font-mono text-[11px]">
                    <div class="p-3 rounded-xl border border-blue-200 dark:border-blue-800/60 bg-blue-50/50 dark:bg-blue-950/40">
                        <div class="text-[10px] text-muted uppercase font-sans font-bold">{{ __('Telemetri (Publish)') }}</div>
                        <div id="topicTelemetry" class="text-blue-600 dark:text-blue-400 font-bold mt-0.5"></div>
                    </div>
                    <div class="p-3 rounded-xl border border-emerald-200 dark:border-emerald-800/60 bg-emerald-50/50 dark:bg-emerald-950/40">
                        <div class="text-[10px] text-muted uppercase font-sans font-bold">{{ __('Status Tersimpan & LWT (Publish)') }}</div>
                        <div id="topicStatus" class="text-emerald-700 dark:text-emerald-400 font-bold mt-0.5"></div>
                    </div>
                    <div class="p-3 rounded-xl border border-purple-200 dark:border-purple-800/60 bg-purple-50/50 dark:bg-purple-950/40">
                        <div class="text-[10px] text-muted uppercase font-sans font-bold">{{ __('Perintah (Subscribe)') }}</div>
                        <div id="topicCmd" class="text-purple-700 dark:text-purple-400 font-bold mt-0.5"></div>
                    </div>
                    <div class="p-3 rounded-xl border border-amber-200 dark:border-amber-800/60 bg-amber-50/50 dark:bg-amber-950/40">
                        <div class="text-[10px] text-muted uppercase font-sans font-bold">{{ __('Konfirmasi Perintah (Publish)') }}</div>
                        <div id="topicAck" class="text-amber-700 dark:text-amber-400 font-bold mt-0.5"></div>
                    </div>
                </div>

                <div class="pt-3 border-t border-line">
                    <button type="button" onclick="document.getElementById('mqttInfoModal').classList.add('hidden')" class="w-full py-2.5 bg-panel-alt hover:bg-slate-200 dark:hover:bg-slate-700 text-ink border border-line font-semibold rounded-xl text-xs transition-colors cursor-pointer">
                        {{ __('Tutup') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function showMqttModal(uid, username) {
            document.getElementById('modalTopicUid').innerText = uid;
            document.getElementById('modalTopicUser').innerText = username;
            document.getElementById('topicTelemetry').innerText = `tracker/${uid}/telemetry`;
            document.getElementById('topicStatus').innerText = `tracker/${uid}/status`;
            document.getElementById('topicCmd').innerText = `tracker/${uid}/cmd`;
            document.getElementById('topicAck').innerText = `tracker/${uid}/ack`;
            document.getElementById('mqttInfoModal').classList.remove('hidden');
        }

        function openEditDeviceModal(device) {
            const settings = device.settings || {};
            document.getElementById('editModalUid').innerText = device.uid;
            document.getElementById('editDeviceForm').action = `/device-management/${device.id}`;
            document.getElementById('editName').value = device.name || '';
            document.getElementById('editSiteId').value = device.site_id || '';
            document.getElementById('editLabel').value = settings.measurement_label || 'Solar array';
            document.getElementById('editTarget').value = settings.daily_energy_target_kwh || 50.0;
            document.getElementById('editElevMin').value = settings.elev_min !== undefined ? settings.elev_min : 10.0;
            document.getElementById('editElevMax').value = settings.elev_max !== undefined ? settings.elev_max : 85.0;
            document.getElementById('editAzMin').value = settings.az_min !== undefined ? settings.az_min : 60.0;
            document.getElementById('editAzMax').value = settings.az_max !== undefined ? settings.az_max : 300.0;
            document.getElementById('editWindStow').value = settings.wind_stow_ms !== undefined ? settings.wind_stow_ms : 15.0;
            document.getElementById('editRate').value = settings.telemetry_interval_s !== undefined ? settings.telemetry_interval_s : 5;
            document.getElementById('editDeviceModal').classList.remove('hidden');
        }
    </script>
    @endpush
</x-layouts.app>
