<x-layouts.app :title="__('Manajemen Perangkat')">
    <!-- Page Header & Action Button -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-bold uppercase tracking-wider bg-blue-50 text-blue-700 border border-blue-200">
                    {{ __('Registri Perangkat Keras') }}
                </span>
                <span class="text-sm font-medium text-slate-400">{{ __('Pelacak Sumbu Ganda ESP32') }}</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900 mt-1">{{ __('Manajemen Perangkat & Node Telemetri') }}</h1>
            <p class="text-sm text-slate-500 mt-1">{{ __('Daftarkan perangkat pelacak surya, konfigurasikan batas gerak fisik, kelola mode aman angin, dan provisi klien MQTT.') }}</p>
        </div>

        <div class="flex items-center space-x-2">
            <button 
                type="button" 
                onclick="document.getElementById('addDeviceModal').classList.remove('hidden')"
                class="inline-flex items-center px-4 py-2.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-xs transition-colors h-11 min-h-[44px]"
            >
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                {{ __('Daftarkan Pelacak') }}
            </button>
        </div>
    </div>

    @if(session('status'))
        <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-sm font-medium text-emerald-800 flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>{{ session('status') }}</span>
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-sm font-medium text-red-800">
            <div class="font-bold mb-1">{{ __('Mohon perbaiki kesalahan berikut:') }}</div>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Device Registry Table -->
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-2xs overflow-hidden mb-6">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-base font-bold text-slate-900">{{ __('Node Pelacak Sumbu Ganda Terkonfigurasi') }}</h3>
            <span class="text-sm text-slate-500 font-mono">{{ $devices->count() }} {{ __('Unit Terkonfigurasi') }}</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50/80 text-slate-500 font-semibold border-b border-slate-100 text-xs uppercase">
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
                <tbody class="divide-y divide-slate-100 font-mono text-xs sm:text-sm">
                    @forelse($devices as $device)
                        @php
                            $settings = $device->settings ?? \App\Models\Device::defaultSettings();
                        @endphp
                        <tr class="hover:bg-slate-50/60 transition-colors h-[52px]">
                            <td class="py-3 px-4 font-bold text-slate-900">{{ $device->uid }}</td>
                            <td class="py-3 px-4 font-sans font-medium text-slate-800">{{ $device->name }}</td>
                            <td class="py-3 px-4 font-sans text-slate-600">{{ $device->site?->name ?? __('Fasilitas Default') }}</td>
                            <td class="py-3 px-4 font-sans">
                                <span class="px-2.5 py-1 rounded text-xs font-bold uppercase
                                    {{ $device->status === 'online' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : ($device->status === 'unstable' ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-slate-100 text-slate-600 border border-slate-200') }}">
                                    {{ match($device->status) { 'online' => 'Online', 'offline' => 'Offline', 'unstable' => __('Tidak Stabil'), default => ucfirst($device->status) } }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-slate-700">
                                {{ app()->getLocale() === 'id' ? formatNumberId($settings['elev_min'] ?? 10, 0) : ($settings['elev_min'] ?? 10) }}&deg; &ndash; {{ app()->getLocale() === 'id' ? formatNumberId($settings['elev_max'] ?? 85, 0) : ($settings['elev_max'] ?? 85) }}&deg;
                            </td>
                            <td class="py-3 px-4 text-slate-700">
                                {{ app()->getLocale() === 'id' ? formatNumberId($settings['az_min'] ?? 60, 0) : ($settings['az_min'] ?? 60) }}&deg; &ndash; {{ app()->getLocale() === 'id' ? formatNumberId($settings['az_max'] ?? 300, 0) : ($settings['az_max'] ?? 300) }}&deg;
                            </td>
                            <td class="py-3 px-4 text-slate-700 font-bold">
                                {{ app()->getLocale() === 'id' ? formatNumberId($settings['wind_stow_ms'] ?? 15.0, 1) : number_format($settings['wind_stow_ms'] ?? 15.0, 1) }} m/s
                            </td>
                            <td class="py-3 px-4 font-sans text-slate-600">
                                {{ __($settings['measurement_label'] ?? 'Solar array') }}
                            </td>
                            <td class="py-3 px-4 font-sans text-right space-x-2 whitespace-nowrap">
                                <button 
                                    type="button" 
                                    onclick="showMqttModal('{{ $device->uid }}', '{{ $device->mqtt_username ?? 'device_'.$device->uid }}')"
                                    class="inline-flex items-center px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold"
                                    title="{{ __('Lihat Topik MQTT') }}"
                                >
                                    {{ __('Info MQTT') }}
                                </button>
                                <button 
                                    type="button" 
                                    onclick="openEditDeviceModal(@js($device))"
                                    class="inline-flex items-center px-3 py-1.5 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs font-semibold"
                                >
                                    {{ __('Konfigurasi') }}
                                </button>
                                <form method="POST" action="{{ route('device-management.destroy', $device) }}" class="inline-block" onsubmit="return confirm('{{ __('Apakah Anda yakin ingin menghapus pelacak') }} {{ $device->uid }}? {{ __('Semua data telemetri historis akan tetap tersimpan.') }}');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex items-center px-3 py-1.5 rounded-lg bg-red-50 hover:bg-red-100 text-red-700 text-xs font-semibold">
                                        {{ __('Hapus') }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="p-8 text-center text-slate-400 font-sans text-sm">
                                {{ __('Belum ada pelacak surya terdaftar. Klik "Daftarkan Pelacak" di atas untuk menambahkan perangkat.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Provisioning Guide Card -->
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-2xs p-6 mb-6">
        <h3 class="text-base font-bold text-slate-900 flex items-center space-x-2">
            <svg class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
            </svg>
            <span>{{ __('Petunjuk Provisi Perangkat Keras ESP32') }}</span>
        </h3>
        <p class="text-sm text-slate-500 mt-1">
            {{ __('Setelah mendaftarkan pelacak, buat kredensial unik perangkat dan tambahkan izin ACL ke broker Mosquitto:') }}
        </p>
        <div class="mt-4 bg-slate-900 text-slate-200 rounded-xl p-4 font-mono text-sm overflow-x-auto">
            <span class="text-slate-400">{{ __('# Jalankan dari host atau di dalam container:') }}</span><br>
            <span class="text-emerald-400">./scripts/mqtt-add-device.sh</span> &lt;device_uid&gt;<br><br>
            <span class="text-slate-400">{{ __('# Contoh:') }}</span><br>
            <span class="text-emerald-400">./scripts/mqtt-add-device.sh</span> <span class="text-amber-300">ST-DEMO-002</span>
        </div>
    </div>

    <!-- Modal: Register Tracker -->
    <div id="addDeviceModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-xl w-full shadow-2xl border border-slate-200 overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-bold text-slate-900">{{ __('Daftarkan Pelacak Surya Baru') }}</h3>
                    <p class="text-xs sm:text-sm text-slate-400">{{ __('Tambahkan node edge ESP32 ke sistem pemantauan') }}</p>
                </div>
                <button type="button" onclick="document.getElementById('addDeviceModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form method="POST" action="{{ route('device-management.store') }}" class="p-6 space-y-4">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">{{ __('UID Perangkat') }} *</label>
                        <input type="text" name="uid" placeholder="misal: ST-DEMO-002" required class="w-full text-sm font-mono uppercase bg-slate-50 border border-slate-300 rounded-lg px-3.5 py-2.5 focus:ring-2 focus:ring-blue-500 h-11 min-h-[44px]">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">{{ __('Nama Pelacak') }} *</label>
                        <input type="text" name="name" placeholder="misal: Tracker Array 02" required class="w-full text-sm bg-slate-50 border border-slate-300 rounded-lg px-3.5 py-2.5 focus:ring-2 focus:ring-blue-500 h-11 min-h-[44px]">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">{{ __('Lokasi Fasilitas') }} *</label>
                        <select name="site_id" required class="w-full text-sm bg-slate-50 border border-slate-300 rounded-lg px-3.5 py-2.5 focus:ring-2 focus:ring-blue-500 h-11 min-h-[44px]">
                            @foreach($sites as $site)
                                <option value="{{ $site->id }}">{{ $site->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">{{ __('Label Pengukuran DC') }}</label>
                        <input type="text" name="measurement_label" value="{{ __('Panel surya') }}" required class="w-full text-sm bg-slate-50 border border-slate-300 rounded-lg px-3.5 py-2.5 focus:ring-2 focus:ring-blue-500 h-11 min-h-[44px]">
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-100">
                    <span class="text-sm font-bold text-slate-800">{{ __('Batas Gerak Fisik & Mode Aman Angin') }}</span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">{{ __('Elev Min (°)') }}</label>
                        <input type="number" step="0.5" name="elev_min" value="10.0" required class="w-full text-sm font-mono bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 h-11 min-h-[44px]">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">{{ __('Elev Maks (°)') }}</label>
                        <input type="number" step="0.5" name="elev_max" value="85.0" required class="w-full text-sm font-mono bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 h-11 min-h-[44px]">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">{{ __('Az Min (°)') }}</label>
                        <input type="number" step="0.5" name="az_min" value="60.0" required class="w-full text-sm font-mono bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 h-11 min-h-[44px]">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">{{ __('Az Maks (°)') }}</label>
                        <input type="number" step="0.5" name="az_max" value="300.0" required class="w-full text-sm font-mono bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 h-11 min-h-[44px]">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">{{ __('Batas Aman Angin (m/s)') }}</label>
                        <input type="number" step="0.5" name="wind_stow_ms" value="15.0" required class="w-full text-sm font-mono bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 h-11 min-h-[44px]">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">{{ __('Interval Telemetri (detik)') }}</label>
                        <input type="number" name="telemetry_interval_s" value="5" required class="w-full text-sm font-mono bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 h-11 min-h-[44px]">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">{{ __('Target Harian (kWh)') }}</label>
                        <input type="number" step="0.5" name="daily_energy_target_kwh" value="50.0" required class="w-full text-sm font-mono bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 h-11 min-h-[44px]">
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-end space-x-3">
                    <button type="button" onclick="document.getElementById('addDeviceModal').classList.add('hidden')" class="px-5 py-2.5 rounded-lg border border-slate-300 text-sm font-semibold text-slate-700 hover:bg-slate-50 h-11 min-h-[44px]">
                        {{ __('Batal') }}
                    </button>
                    <button type="submit" class="px-5 py-2.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-xs h-11 min-h-[44px]">
                        {{ __('Simpan Pelacak') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Edit Device -->
    <div id="editDeviceModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-xl w-full shadow-2xl border border-slate-200 overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-bold text-slate-900">{{ __('Konfigurasi Pelacak:') }} <span id="editModalUid" class="font-mono text-blue-600"></span></h3>
                    <p class="text-xs sm:text-sm text-slate-400">{{ __('Perbarui parameter kinematik dan pengaturan sensor') }}</p>
                </div>
                <button type="button" onclick="document.getElementById('editDeviceModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form id="editDeviceForm" method="POST" action="" class="p-6 space-y-4">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">{{ __('Nama Pelacak') }} *</label>
                        <input type="text" id="editName" name="name" required class="w-full text-sm bg-slate-50 border border-slate-300 rounded-lg px-3.5 py-2.5 focus:ring-2 focus:ring-blue-500 h-11 min-h-[44px]">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">{{ __('Lokasi Fasilitas') }} *</label>
                        <select id="editSiteId" name="site_id" required class="w-full text-sm bg-slate-50 border border-slate-300 rounded-lg px-3.5 py-2.5 focus:ring-2 focus:ring-blue-500 h-11 min-h-[44px]">
                            @foreach($sites as $site)
                                <option value="{{ $site->id }}">{{ $site->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">{{ __('Label Pengukuran DC') }}</label>
                        <input type="text" id="editLabel" name="measurement_label" required class="w-full text-sm bg-slate-50 border border-slate-300 rounded-lg px-3.5 py-2.5 focus:ring-2 focus:ring-blue-500 h-11 min-h-[44px]">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">{{ __('Target Harian (kWh)') }}</label>
                        <input type="number" step="0.5" id="editTarget" name="daily_energy_target_kwh" required class="w-full text-sm font-mono bg-slate-50 border border-slate-300 rounded-lg px-3.5 py-2.5 focus:ring-2 focus:ring-blue-500 h-11 min-h-[44px]">
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-100">
                    <span class="text-sm font-bold text-slate-800">{{ __('Batas Gerak Fisik & Mode Aman Angin') }}</span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">{{ __('Elev Min (°)') }}</label>
                        <input type="number" step="0.5" id="editElevMin" name="elev_min" required class="w-full text-sm font-mono bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 h-11 min-h-[44px]">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">{{ __('Elev Maks (°)') }}</label>
                        <input type="number" step="0.5" id="editElevMax" name="elev_max" required class="w-full text-sm font-mono bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 h-11 min-h-[44px]">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">{{ __('Az Min (°)') }}</label>
                        <input type="number" step="0.5" id="editAzMin" name="az_min" required class="w-full text-sm font-mono bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 h-11 min-h-[44px]">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">{{ __('Az Maks (°)') }}</label>
                        <input type="number" step="0.5" id="editAzMax" name="az_max" required class="w-full text-sm font-mono bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 h-11 min-h-[44px]">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">{{ __('Batas Aman Angin (m/s)') }}</label>
                        <input type="number" step="0.5" id="editWindStow" name="wind_stow_ms" required class="w-full text-sm font-mono bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 h-11 min-h-[44px]">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">{{ __('Interval Telemetri (detik)') }}</label>
                        <input type="number" id="editRate" name="telemetry_interval_s" required class="w-full text-sm font-mono bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 h-11 min-h-[44px]">
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-end space-x-3">
                    <button type="button" onclick="document.getElementById('editDeviceModal').classList.add('hidden')" class="px-5 py-2.5 rounded-lg border border-slate-300 text-sm font-semibold text-slate-700 hover:bg-slate-50 h-11 min-h-[44px]">
                        {{ __('Batal') }}
                    </button>
                    <button type="submit" class="px-5 py-2.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-xs h-11 min-h-[44px]">
                        {{ __('Perbarui Konfigurasi') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: MQTT Topics & Provisioning Info -->
    <div id="mqttInfoModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-lg w-full shadow-2xl border border-slate-200 overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-bold text-slate-900">{{ __('Topik MQTT & Kontrak Broker') }}</h3>
                    <p class="text-xs sm:text-sm text-slate-400">{{ __('Node:') }} <span id="modalTopicUid" class="font-mono text-blue-600 font-bold"></span></p>
                </div>
                <button type="button" onclick="document.getElementById('mqttInfoModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="p-6 space-y-4 text-sm">
                <div>
                    <span class="text-slate-500 font-semibold">{{ __('Alamat Broker:') }}</span>
                    <span class="font-mono bg-slate-100 px-2.5 py-1 rounded text-slate-800 ml-1">127.0.0.1:1883</span>
                </div>
                <div>
                    <span class="text-slate-500 font-semibold">{{ __('Nama Pengguna MQTT:') }}</span>
                    <span id="modalTopicUser" class="font-mono bg-slate-100 px-2.5 py-1 rounded text-slate-800 ml-1"></span>
                </div>

                <div class="pt-3 border-t border-slate-100 space-y-2.5 font-mono text-xs">
                    <div class="p-3 rounded-lg bg-slate-50 border border-slate-200">
                        <div class="text-xs text-slate-400 uppercase font-sans font-bold">{{ __('Telemetri (Publish)') }}</div>
                        <div id="topicTelemetry" class="text-blue-600 font-bold mt-0.5"></div>
                    </div>
                    <div class="p-3 rounded-lg bg-slate-50 border border-slate-200">
                        <div class="text-xs text-slate-400 uppercase font-sans font-bold">{{ __('Status Tersimpan & LWT (Publish)') }}</div>
                        <div id="topicStatus" class="text-emerald-600 font-bold mt-0.5"></div>
                    </div>
                    <div class="p-3 rounded-lg bg-slate-50 border border-slate-200">
                        <div class="text-xs text-slate-400 uppercase font-sans font-bold">{{ __('Perintah (Subscribe)') }}</div>
                        <div id="topicCmd" class="text-purple-600 font-bold mt-0.5"></div>
                    </div>
                    <div class="p-3 rounded-lg bg-slate-50 border border-slate-200">
                        <div class="text-xs text-slate-400 uppercase font-sans font-bold">{{ __('Konfirmasi Perintah (Publish)') }}</div>
                        <div id="topicAck" class="text-amber-600 font-bold mt-0.5"></div>
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-100">
                    <button type="button" onclick="document.getElementById('mqttInfoModal').classList.add('hidden')" class="w-full py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold rounded-lg text-sm h-11 min-h-[44px]">
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
