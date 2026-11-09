<x-layouts.app :title="__('Pengaturan')">
    <!-- Page Header -->
    <x-page-header 
        :title="__('Pengaturan Sistem & Konfigurasi Fasilitas')" 
        :subtitle="__('Kelola koordinat instalasi fasilitas, target hasil panen surya harian, dan interlock keamanan.')"
        :badge="__('Konfigurasi SCADA')"
    />

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

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Site Information Card -->
        <x-card :title="__('Informasi Fasilitas & Target')" :subtitle="__('Parameter instalasi fisik & referensi astronomis')">
            <form method="POST" action="{{ route('settings.site') }}" class="space-y-4 text-sm">
                @csrf
                <div>
                    <label class="block font-semibold text-slate-700 mb-1.5">{{ __('Nama Fasilitas / Lokasi') }} *</label>
                    <input type="text" name="name" value="{{ old('name', $site->name ?? '') }}" required class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 bg-slate-50 text-slate-800 focus:ring-2 focus:ring-blue-500 h-11 min-h-[44px]">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1.5">{{ __('Lokasi Geografis') }} *</label>
                    <input type="text" name="location" value="{{ old('location', $site->location ?? '') }}" required class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 bg-slate-50 text-slate-800 focus:ring-2 focus:ring-blue-500 h-11 min-h-[44px]">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">{{ __('Garis Lintang (° U/S)') }} *</label>
                        <input type="number" step="0.0001" name="latitude" value="{{ old('latitude', $site->latitude ?? -6.2944) }}" required class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 bg-slate-50 text-slate-800 font-mono focus:ring-2 focus:ring-blue-500 h-11 min-h-[44px]">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">{{ __('Garis Bujur (° T/B)') }} *</label>
                        <input type="number" step="0.0001" name="longitude" value="{{ old('longitude', $site->longitude ?? 107.1539) }}" required class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 bg-slate-50 text-slate-800 font-mono focus:ring-2 focus:ring-blue-500 h-11 min-h-[44px]">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">{{ __('Zona Waktu Lokal') }} *</label>
                        <input type="text" name="timezone" value="{{ old('timezone', $site->timezone ?? 'Asia/Jakarta') }}" required class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 bg-slate-50 text-slate-800 font-mono focus:ring-2 focus:ring-blue-500 h-11 min-h-[44px]">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">{{ __('Target Energi Harian (kWh)') }} *</label>
                        <input type="number" step="0.5" name="daily_energy_target_kwh" value="{{ old('daily_energy_target_kwh', $site->daily_energy_target_kwh ?? 50.0) }}" required class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 bg-slate-50 text-slate-800 font-mono focus:ring-2 focus:ring-blue-500 h-11 min-h-[44px]">
                    </div>
                </div>

                <div class="pt-4 flex justify-end">
                    <button type="submit" class="px-5 py-2.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-xs transition-colors h-11 min-h-[44px]">
                        {{ __('Simpan Konfigurasi Fasilitas') }}
                    </button>
                </div>
            </form>
        </x-card>

        <!-- Operational Thresholds & Retention Card -->
        <x-card :title="__('Batas Operasional & Retensi Data')" :subtitle="__('Batas kecepatan angin kencang dan kebijakan penyimpanan data historis')">
            <form method="POST" action="{{ route('settings.thresholds') }}" class="space-y-4 text-sm">
                @csrf
                <div>
                    <label class="block font-semibold text-slate-700 mb-1.5">{{ __('Kecepatan Mode Aman Angin (m/s)') }} *</label>
                    <div class="flex items-center space-x-2">
                        <input type="number" step="0.5" name="wind_stow_threshold_ms" value="{{ old('wind_stow_threshold_ms', $thresholds['wind_stow_threshold_ms']) }}" required class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 bg-slate-50 text-slate-800 font-mono focus:ring-2 focus:ring-blue-500 h-11 min-h-[44px]">
                        <span class="font-semibold text-slate-500 shrink-0">m/s</span>
                    </div>
                    <span class="text-xs text-slate-400 mt-1 block">{{ __('Memicu mode aman datar aerodinamis otomatis dan interlock perintah manual') }}</span>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1.5">{{ __('Retensi Telemetri Mentah 5 Detik (Hari)') }} *</label>
                    <div class="flex items-center space-x-2">
                        <input type="number" name="retention_raw_days" value="{{ old('retention_raw_days', $thresholds['retention_raw_days']) }}" required class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 bg-slate-50 text-slate-800 font-mono focus:ring-2 focus:ring-blue-500 h-11 min-h-[44px]">
                        <span class="font-semibold text-slate-500 shrink-0">{{ __('Hari') }}</span>
                    </div>
                    <span class="text-xs text-slate-400 mt-1 block">{{ __('Paket sensor frekuensi tinggi dibersihkan oleh tracker:purge-retention (default: 30 hari)') }}</span>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1.5">{{ __('Retensi Agregat Per Jam (Hari)') }} *</label>
                    <div class="flex items-center space-x-2">
                        <input type="number" name="retention_hourly_days" value="{{ old('retention_hourly_days', $thresholds['retention_hourly_days']) }}" required class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 bg-slate-50 text-slate-800 font-mono focus:ring-2 focus:ring-blue-500 h-11 min-h-[44px]">
                        <span class="font-semibold text-slate-500 shrink-0">{{ __('Hari') }}</span>
                    </div>
                    <span class="text-xs text-slate-400 mt-1 block">{{ __('Statistik agregat jangka panjang disimpan di telemetry_hourly (default: 730 hari / 2 tahun)') }}</span>
                </div>

                <div class="pt-4 flex justify-end">
                    <button type="submit" class="px-5 py-2.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-xs transition-colors h-11 min-h-[44px]">
                        {{ __('Simpan Keamanan & Retensi') }}
                    </button>
                </div>
            </form>
        </x-card>
    </div>

    <!-- Safety Architecture Banner -->
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-2xs p-6">
        <h3 class="text-base font-bold text-slate-900 flex items-center space-x-2">
            <svg class="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
            <span>{{ __('Pemberitahuan Arsitektur Keamanan Edge-First') }}</span>
        </h3>
        <p class="text-sm text-slate-500 mt-1">
            {{ __('Aturan keselamatan divalidasi di sisi server sebelum pengiriman MQTT, dan secara independen di dalam firmware edge ESP32. Jika terjadi gangguan jaringan, kontroler ESP32 terus menjalankan pelacakan astronomis otonom lokal dan segera memicu mode aman aerodinamis saat hembusan angin kencang terdeteksi.') }}
        </p>
    </div>
</x-layouts.app>
