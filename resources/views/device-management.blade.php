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
                    class="inline-flex items-center h-9 px-3 rounded-md bg-accent text-ink text-sm font-medium hover:bg-blue-600 transition-colors cursor-pointer"
                >
                    <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>{{ __('Daftarkan Pelacak') }}</span>
                </button>
            </div>
        </x-slot:actions>
    </x-page-header>

    @if(session('status'))
        <div class="mb-5 p-3 rounded-md bg-ok/10 border border-ok/30 text-xs text-ok flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>{{ session('status') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-ok hover:opacity-75 cursor-pointer">&times;</button>
        </div>
    @endif

    @if($errors->any())
        <div class="mb-5 p-3 rounded-md bg-danger/10 border border-danger/30 text-xs text-danger">
            <div class="font-semibold mb-1">{{ __('Mohon perbaiki kesalahan berikut:') }}</div>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Device Registry Table Panel -->
    <div class="bg-panel border border-line rounded-md overflow-hidden mb-6">
        <div class="p-4 border-b border-line flex items-center justify-between">
            <div>
                <h3 class="text-sm font-semibold text-ink">{{ __('Node Pelacak Sumbu Ganda Terkonfigurasi') }}</h3>
                <p class="text-xs text-muted mt-0.5">{{ __('Pengaturan batas gerak sudut, batas angin kencang, dan provisi MQTT.') }}</p>
            </div>
            <span class="text-xs text-muted font-mono">{{ $devices->count() }} {{ __('Unit Terdaftar') }}</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-bg/40 text-muted uppercase text-xs tracking-wider border-b border-line">
                    <tr>
                        <th class="py-2.5 px-4 font-medium">{{ __('UID Perangkat') }}</th>
                        <th class="py-2.5 px-4 font-medium">{{ __('Nama') }}</th>
                        <th class="py-2.5 px-4 font-medium">{{ __('Lokasi Fasilitas') }}</th>
                        <th class="py-2.5 px-4 font-medium">{{ __('Status') }}</th>
                        <th class="py-2.5 px-4 font-medium">{{ __('Batas Elevasi') }}</th>
                        <th class="py-2.5 px-4 font-medium">{{ __('Batas Azimut') }}</th>
                        <th class="py-2.5 px-4 font-medium">{{ __('Batas Aman Angin') }}</th>
                        <th class="py-2.5 px-4 font-medium">{{ __('Label Sensor DC') }}</th>
                        <th class="py-2.5 px-4 font-medium text-right">{{ __('Aksi') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line font-mono text-xs">
                    @forelse($devices as $device)
                        @php
                            $settings = $device->settings ?? \App\Models\Device::defaultSettings();
                        @endphp
                        <tr class="hover:bg-line/20 transition-colors">
                            <td class="py-2.5 px-4 font-semibold text-ink">{{ $device->uid }}</td>
                            <td class="py-2.5 px-4 font-sans text-ink">{{ $device->name }}</td>
                            <td class="py-2.5 px-4 font-sans text-muted">{{ $device->site?->name ?? __('Fasilitas Default') }}</td>
                            <td class="py-2.5 px-4">
                                <x-status-pill :status="$device->status" />
                            </td>
                            <td class="py-2.5 px-4 text-ink">
                                {{ app()->getLocale() === 'id' ? formatNumberId($settings['elev_min'] ?? 10, 0) : ($settings['elev_min'] ?? 10) }}&deg; &ndash; {{ app()->getLocale() === 'id' ? formatNumberId($settings['elev_max'] ?? 85, 0) : ($settings['elev_max'] ?? 85) }}&deg;
                            </td>
                            <td class="py-2.5 px-4 text-ink">
                                {{ app()->getLocale() === 'id' ? formatNumberId($settings['az_min'] ?? 60, 0) : ($settings['az_min'] ?? 60) }}&deg; &ndash; {{ app()->getLocale() === 'id' ? formatNumberId($settings['az_max'] ?? 300, 0) : ($settings['az_max'] ?? 300) }}&deg;
                            </td>
                            <td class="py-2.5 px-4 text-ink font-semibold">
                                {{ app()->getLocale() === 'id' ? formatNumberId($settings['wind_stow_ms'] ?? 15.0, 1) : number_format($settings['wind_stow_ms'] ?? 15.0, 1) }} m/s
                            </td>
                            <td class="py-2.5 px-4 font-sans text-muted">
                                {{ __($settings['measurement_label'] ?? 'Solar array') }}
                            </td>
                            <td class="py-2.5 px-4 font-sans text-right space-x-1.5 whitespace-nowrap">
                                <button 
                                    type="button" 
                                    onclick="showMqttModal('{{ $device->uid }}', '{{ $device->mqtt_username ?? 'device_'.$device->uid }}')"
                                    class="inline-flex items-center px-2 py-1 rounded border border-line bg-bg hover:bg-line/40 text-ink text-xs font-medium transition-colors"
                                    title="{{ __('Lihat Topik MQTT') }}"
                                >
                                    {{ __('Info MQTT') }}
                                </button>
                                <button 
                                    type="button" 
                                    onclick="openEditDeviceModal(@js($device))"
                                    class="inline-flex items-center px-2 py-1 rounded border border-accent/40 text-accent bg-accent/10 hover:bg-accent/20 text-xs font-medium transition-colors"
                                >
                                    {{ __('Konfigurasi') }}
                                </button>
                                <form method="POST" action="{{ route('device-management.destroy', $device) }}" class="inline-block" onsubmit="return confirm('{{ __('Apakah Anda yakin ingin menghapus pelacak') }} {{ $device->uid }}? {{ __('Semua data telemetri historis akan tetap tersimpan.') }}');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex items-center px-2 py-1 rounded border border-danger/40 text-danger bg-danger/10 hover:bg-danger/20 text-xs font-medium transition-colors cursor-pointer">
                                        {{ __('Hapus') }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="p-8 text-center text-muted font-sans text-sm">
                                {{ __('Belum ada pelacak surya terdaftar. Klik "Daftarkan Pelacak" di atas untuk menambahkan perangkat.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Provisioning Guide Card -->
    <div class="bg-panel border border-line rounded-md p-4 mb-6">
        <h3 class="text-sm font-semibold text-ink flex items-center space-x-2">
            <svg class="w-4 h-4 text-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
            </svg>
            <span>{{ __('Petunjuk Provisi Perangkat Keras ESP32') }}</span>
        </h3>
        <p class="text-xs text-muted mt-1">
            {{ __('Setelah mendaftarkan pelacak, buat kredensial unik perangkat dan tambahkan izin ACL ke broker Mosquitto:') }}
        </p>
        <div class="mt-3 bg-bg border border-line rounded-md p-3 font-mono text-xs text-ink overflow-x-auto">
            <span class="text-muted">{{ __('# Jalankan dari host atau di dalam container:') }}</span><br>
            <span class="text-ok">./scripts/mqtt-add-device.sh</span> &lt;device_uid&gt;<br><br>
            <span class="text-muted">{{ __('# Contoh:') }}</span><br>
            <span class="text-ok">./scripts/mqtt-add-device.sh</span> <span class="text-accent">ST-DEMO-002</span>
        </div>
    </div>

    <!-- Modal: Register Tracker -->
    <div id="addDeviceModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/60 flex items-center justify-center p-4">
        <div class="bg-panel rounded-md max-w-xl w-full border border-line overflow-hidden">
            <div class="px-5 py-4 border-b border-line flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-ink">{{ __('Daftarkan Pelacak Surya Baru') }}</h3>
                    <p class="text-xs text-muted mt-0.5">{{ __('Tambahkan node edge ESP32 ke sistem pemantauan') }}</p>
                </div>
                <button type="button" onclick="document.getElementById('addDeviceModal').classList.add('hidden')" class="text-muted hover:text-ink">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form method="POST" action="{{ route('device-management.store') }}" class="p-5 space-y-4 text-xs">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-muted uppercase tracking-wider mb-1">{{ __('UID Perangkat') }} *</label>
                        <input type="text" name="uid" placeholder="misal: ST-DEMO-002" required class="w-full text-xs font-mono uppercase bg-bg border border-line rounded-md px-3 py-2 text-ink focus:outline-none focus:border-accent">
                    </div>
                    <div>
                        <label class="block font-semibold text-muted uppercase tracking-wider mb-1">{{ __('Nama Pelacak') }} *</label>
                        <input type="text" name="name" placeholder="misal: Tracker Array 02" required class="w-full text-xs bg-bg border border-line rounded-md px-3 py-2 text-ink focus:outline-none focus:border-accent">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-muted uppercase tracking-wider mb-1">{{ __('Lokasi Fasilitas') }} *</label>
                        <select name="site_id" required class="w-full text-xs bg-bg border border-line rounded-md px-3 py-2 text-ink focus:outline-none focus:border-accent">
                            @foreach($sites as $site)
                                <option value="{{ $site->id }}">{{ $site->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-muted uppercase tracking-wider mb-1">{{ __('Label Pengukuran DC') }}</label>
                        <input type="text" name="measurement_label" value="{{ __('Panel surya') }}" required class="w-full text-xs bg-bg border border-line rounded-md px-3 py-2 text-ink focus:outline-none focus:border-accent">
                    </div>
                </div>

                <div class="pt-2 border-t border-line">
                    <span class="text-xs font-semibold text-ink">{{ __('Batas Gerak Fisik & Mode Aman Angin') }}</span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                    <div>
                        <label class="block text-[11px] text-muted mb-1">{{ __('Elev Min (°)') }}</label>
                        <input type="number" step="0.5" name="elev_min" value="10.0" required class="w-full text-xs font-mono bg-bg border border-line rounded-md px-2.5 py-1.5 text-ink focus:outline-none focus:border-accent">
                    </div>
                    <div>
                        <label class="block text-[11px] text-muted mb-1">{{ __('Elev Maks (°)') }}</label>
                        <input type="number" step="0.5" name="elev_max" value="85.0" required class="w-full text-xs font-mono bg-bg border border-line rounded-md px-2.5 py-1.5 text-ink focus:outline-none focus:border-accent">
                    </div>
                    <div>
                        <label class="block text-[11px] text-muted mb-1">{{ __('Az Min (°)') }}</label>
                        <input type="number" step="0.5" name="az_min" value="60.0" required class="w-full text-xs font-mono bg-bg border border-line rounded-md px-2.5 py-1.5 text-ink focus:outline-none focus:border-accent">
                    </div>
                    <div>
                        <label class="block text-[11px] text-muted mb-1">{{ __('Az Maks (°)') }}</label>
                        <input type="number" step="0.5" name="az_max" value="300.0" required class="w-full text-xs font-mono bg-bg border border-line rounded-md px-2.5 py-1.5 text-ink focus:outline-none focus:border-accent">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                    <div>
                        <label class="block text-[11px] text-muted mb-1">{{ __('Batas Aman Angin (m/s)') }}</label>
                        <input type="number" step="0.5" name="wind_stow_ms" value="15.0" required class="w-full text-xs font-mono bg-bg border border-line rounded-md px-2.5 py-1.5 text-ink focus:outline-none focus:border-accent">
                    </div>
                    <div>
                        <label class="block text-[11px] text-muted mb-1">{{ __('Interval Telemetri (detik)') }}</label>
                        <input type="number" name="telemetry_interval_s" value="5" required class="w-full text-xs font-mono bg-bg border border-line rounded-md px-2.5 py-1.5 text-ink focus:outline-none focus:border-accent">
                    </div>
                    <div>
                        <label class="block text-[11px] text-muted mb-1">{{ __('Target Harian (kWh)') }}</label>
                        <input type="number" step="0.5" name="daily_energy_target_kwh" value="50.0" required class="w-full text-xs font-mono bg-bg border border-line rounded-md px-2.5 py-1.5 text-ink focus:outline-none focus:border-accent">
                    </div>
                </div>

                <div class="pt-3 border-t border-line flex items-center justify-end space-x-2">
                    <button type="button" onclick="document.getElementById('addDeviceModal').classList.add('hidden')" class="px-3 py-1.5 rounded-md border border-line text-muted hover:text-ink text-xs font-medium transition-colors">
                        {{ __('Batal') }}
                    </button>
                    <button type="submit" class="px-3 py-1.5 rounded-md bg-accent hover:bg-blue-600 text-ink text-xs font-medium transition-colors cursor-pointer">
                        {{ __('Simpan Pelacak') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Edit Device -->
    <div id="editDeviceModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/60 flex items-center justify-center p-4">
        <div class="bg-panel rounded-md max-w-xl w-full border border-line overflow-hidden">
            <div class="px-5 py-4 border-b border-line flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-ink">{{ __('Konfigurasi Pelacak:') }} <span id="editModalUid" class="font-mono text-accent"></span></h3>
                    <p class="text-xs text-muted mt-0.5">{{ __('Perbarui parameter kinematik dan pengaturan sensor') }}</p>
                </div>
                <button type="button" onclick="document.getElementById('editDeviceModal').classList.add('hidden')" class="text-muted hover:text-ink">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form id="editDeviceForm" method="POST" action="" class="p-5 space-y-4 text-xs">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-muted uppercase tracking-wider mb-1">{{ __('Nama Pelacak') }} *</label>
                        <input type="text" id="editName" name="name" required class="w-full text-xs bg-bg border border-line rounded-md px-3 py-2 text-ink focus:outline-none focus:border-accent">
                    </div>
                    <div>
                        <label class="block font-semibold text-muted uppercase tracking-wider mb-1">{{ __('Lokasi Fasilitas') }} *</label>
                        <select id="editSiteId" name="site_id" required class="w-full text-xs bg-bg border border-line rounded-md px-3 py-2 text-ink focus:outline-none focus:border-accent">
                            @foreach($sites as $site)
                                <option value="{{ $site->id }}">{{ $site->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-muted uppercase tracking-wider mb-1">{{ __('Label Pengukuran DC') }}</label>
                        <input type="text" id="editLabel" name="measurement_label" required class="w-full text-xs bg-bg border border-line rounded-md px-3 py-2 text-ink focus:outline-none focus:border-accent">
                    </div>
                    <div>
                        <label class="block font-semibold text-muted uppercase tracking-wider mb-1">{{ __('Target Harian (kWh)') }}</label>
                        <input type="number" step="0.5" id="editTarget" name="daily_energy_target_kwh" required class="w-full text-xs font-mono bg-bg border border-line rounded-md px-3 py-2 text-ink focus:outline-none focus:border-accent">
                    </div>
                </div>

                <div class="pt-2 border-t border-line">
                    <span class="text-xs font-semibold text-ink">{{ __('Batas Gerak Fisik & Mode Aman Angin') }}</span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                    <div>
                        <label class="block text-[11px] text-muted mb-1">{{ __('Elev Min (°)') }}</label>
                        <input type="number" step="0.5" id="editElevMin" name="elev_min" required class="w-full text-xs font-mono bg-bg border border-line rounded-md px-2.5 py-1.5 text-ink focus:outline-none focus:border-accent">
                    </div>
                    <div>
                        <label class="block text-[11px] text-muted mb-1">{{ __('Elev Maks (°)') }}</label>
                        <input type="number" step="0.5" id="editElevMax" name="elev_max" required class="w-full text-xs font-mono bg-bg border border-line rounded-md px-2.5 py-1.5 text-ink focus:outline-none focus:border-accent">
                    </div>
                    <div>
                        <label class="block text-[11px] text-muted mb-1">{{ __('Az Min (°)') }}</label>
                        <input type="number" step="0.5" id="editAzMin" name="az_min" required class="w-full text-xs font-mono bg-bg border border-line rounded-md px-2.5 py-1.5 text-ink focus:outline-none focus:border-accent">
                    </div>
                    <div>
                        <label class="block text-[11px] text-muted mb-1">{{ __('Az Maks (°)') }}</label>
                        <input type="number" step="0.5" id="editAzMax" name="az_max" required class="w-full text-xs font-mono bg-bg border border-line rounded-md px-2.5 py-1.5 text-ink focus:outline-none focus:border-accent">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <div>
                        <label class="block text-[11px] text-muted mb-1">{{ __('Batas Aman Angin (m/s)') }}</label>
                        <input type="number" step="0.5" id="editWindStow" name="wind_stow_ms" required class="w-full text-xs font-mono bg-bg border border-line rounded-md px-2.5 py-1.5 text-ink focus:outline-none focus:border-accent">
                    </div>
                    <div>
                        <label class="block text-[11px] text-muted mb-1">{{ __('Interval Telemetri (detik)') }}</label>
                        <input type="number" id="editRate" name="telemetry_interval_s" required class="w-full text-xs font-mono bg-bg border border-line rounded-md px-2.5 py-1.5 text-ink focus:outline-none focus:border-accent">
                    </div>
                </div>

                <div class="pt-3 border-t border-line flex items-center justify-end space-x-2">
                    <button type="button" onclick="document.getElementById('editDeviceModal').classList.add('hidden')" class="px-3 py-1.5 rounded-md border border-line text-muted hover:text-ink text-xs font-medium transition-colors">
                        {{ __('Batal') }}
                    </button>
                    <button type="submit" class="px-3 py-1.5 rounded-md bg-accent hover:bg-blue-600 text-ink text-xs font-medium transition-colors cursor-pointer">
                        {{ __('Perbarui Konfigurasi') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: MQTT Topics & Provisioning Info -->
    <div id="mqttInfoModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/60 flex items-center justify-center p-4">
        <div class="bg-panel rounded-md max-w-lg w-full border border-line overflow-hidden">
            <div class="px-5 py-4 border-b border-line flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-ink">{{ __('Topik MQTT & Kontrak Broker') }}</h3>
                    <p class="text-xs text-muted mt-0.5">{{ __('Node:') }} <span id="modalTopicUid" class="font-mono text-accent font-semibold"></span></p>
                </div>
                <button type="button" onclick="document.getElementById('mqttInfoModal').classList.add('hidden')" class="text-muted hover:text-ink">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="p-5 space-y-3 text-xs">
                <div>
                    <span class="text-muted font-medium">{{ __('Alamat Broker:') }}</span>
                    <span class="font-mono bg-bg border border-line px-2 py-0.5 rounded text-ink ml-1">127.0.0.1:1883</span>
                </div>
                <div>
                    <span class="text-muted font-medium">{{ __('Nama Pengguna MQTT:') }}</span>
                    <span id="modalTopicUser" class="font-mono bg-bg border border-line px-2 py-0.5 rounded text-ink ml-1"></span>
                </div>

                <div class="pt-2 border-t border-line space-y-2 font-mono text-[11px]">
                    <div class="p-2.5 rounded border border-line bg-bg">
                        <div class="text-[10px] text-muted uppercase font-sans font-bold">{{ __('Telemetri (Publish)') }}</div>
                        <div id="topicTelemetry" class="text-accent font-semibold mt-0.5"></div>
                    </div>
                    <div class="p-2.5 rounded border border-line bg-bg">
                        <div class="text-[10px] text-muted uppercase font-sans font-bold">{{ __('Status Tersimpan & LWT (Publish)') }}</div>
                        <div id="topicStatus" class="text-ok font-semibold mt-0.5"></div>
                    </div>
                    <div class="p-2.5 rounded border border-line bg-bg">
                        <div class="text-[10px] text-muted uppercase font-sans font-bold">{{ __('Perintah (Subscribe)') }}</div>
                        <div id="topicCmd" class="text-purple-400 font-semibold mt-0.5"></div>
                    </div>
                    <div class="p-2.5 rounded border border-line bg-bg">
                        <div class="text-[10px] text-muted uppercase font-sans font-bold">{{ __('Konfirmasi Perintah (Publish)') }}</div>
                        <div id="topicAck" class="text-warn font-semibold mt-0.5"></div>
                    </div>
                </div>

                <div class="pt-3 border-t border-line">
                    <button type="button" onclick="document.getElementById('mqttInfoModal').classList.add('hidden')" class="w-full py-2 bg-bg hover:bg-line/40 text-ink border border-line font-medium rounded-md text-xs transition-colors">
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
