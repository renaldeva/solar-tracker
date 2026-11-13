<x-layouts.app :title="__('Pengaturan')">
    <!-- Page Header -->
    <x-page-header 
        :title="__('Pengaturan Sistem & Konfigurasi Fasilitas')" 
        :subtitle="__('Kelola koordinat instalasi fasilitas, target hasil panen surya harian, dan interlock keamanan.')"
        :badge="__('Konfigurasi SCADA')"
    />

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

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
        <!-- Site Information Card -->
        <x-card :title="__('Informasi Fasilitas & Target')" :subtitle="__('Parameter instalasi fisik & referensi astronomis')">
            <form method="POST" action="{{ route('settings.site') }}" class="space-y-4 text-xs">
                @csrf
                <div>
                    <label class="block font-semibold text-muted uppercase tracking-wider mb-1">{{ __('Nama Fasilitas / Lokasi') }} *</label>
                    <input type="text" name="name" value="{{ old('name', $site->name ?? '') }}" required class="w-full px-3 py-2 rounded-md border border-line bg-bg text-ink focus:outline-none focus:border-accent">
                </div>

                <div>
                    <label class="block font-semibold text-muted uppercase tracking-wider mb-1">{{ __('Lokasi Geografis') }} *</label>
                    <input type="text" name="location" value="{{ old('location', $site->location ?? '') }}" required class="w-full px-3 py-2 rounded-md border border-line bg-bg text-ink focus:outline-none focus:border-accent">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-muted uppercase tracking-wider mb-1">{{ __('Garis Lintang (° U/S)') }} *</label>
                        <input type="number" step="0.0001" name="latitude" value="{{ old('latitude', $site->latitude ?? -6.2944) }}" required class="w-full px-3 py-2 rounded-md border border-line bg-bg text-ink font-mono focus:outline-none focus:border-accent">
                    </div>
                    <div>
                        <label class="block font-semibold text-muted uppercase tracking-wider mb-1">{{ __('Garis Bujur (° T/B)') }} *</label>
                        <input type="number" step="0.0001" name="longitude" value="{{ old('longitude', $site->longitude ?? 107.1539) }}" required class="w-full px-3 py-2 rounded-md border border-line bg-bg text-ink font-mono focus:outline-none focus:border-accent">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-muted uppercase tracking-wider mb-1">{{ __('Zona Waktu Lokal') }} *</label>
                        <input type="text" name="timezone" value="{{ old('timezone', $site->timezone ?? 'Asia/Jakarta') }}" required class="w-full px-3 py-2 rounded-md border border-line bg-bg text-ink font-mono focus:outline-none focus:border-accent">
                    </div>
                    <div>
                        <label class="block font-semibold text-muted uppercase tracking-wider mb-1">{{ __('Target Energi Harian (kWh)') }} *</label>
                        <input type="number" step="0.5" name="daily_energy_target_kwh" value="{{ old('daily_energy_target_kwh', $site->daily_energy_target_kwh ?? 50.0) }}" required class="w-full px-3 py-2 rounded-md border border-line bg-bg text-ink font-mono focus:outline-none focus:border-accent">
                    </div>
                </div>

                <div class="pt-3 border-t border-line flex justify-end">
                    <button type="submit" class="px-4 py-2 rounded-md bg-accent hover:bg-blue-600 text-ink text-xs font-medium transition-colors cursor-pointer">
                        {{ __('Simpan Konfigurasi Fasilitas') }}
                    </button>
                </div>
            </form>
        </x-card>

        <!-- Operational Thresholds & Retention Card -->
        <x-card :title="__('Batas Operasional & Retensi Data')" :subtitle="__('Batas kecepatan angin kencang dan kebijakan penyimpanan data historis')">
            <form method="POST" action="{{ route('settings.thresholds') }}" class="space-y-4 text-xs">
                @csrf
                <div>
                    <label class="block font-semibold text-muted uppercase tracking-wider mb-1">{{ __('Kecepatan Mode Aman Angin (m/s)') }} *</label>
                    <div class="flex items-center space-x-2">
                        <input type="number" step="0.5" name="wind_stow_threshold_ms" value="{{ old('wind_stow_threshold_ms', $thresholds['wind_stow_threshold_ms']) }}" required class="w-full px-3 py-2 rounded-md border border-line bg-bg text-ink font-mono focus:outline-none focus:border-accent">
                        <span class="font-semibold text-muted shrink-0">m/s</span>
                    </div>
                    <span class="text-[11px] text-muted mt-1 block">{{ __('Memicu mode aman datar aerodinamis otomatis dan interlock perintah manual') }}</span>
                </div>

                <div>
                    <label class="block font-semibold text-muted uppercase tracking-wider mb-1">{{ __('Retensi Telemetri Mentah 5 Detik (Hari)') }} *</label>
                    <div class="flex items-center space-x-2">
                        <input type="number" name="retention_raw_days" value="{{ old('retention_raw_days', $thresholds['retention_raw_days']) }}" required class="w-full px-3 py-2 rounded-md border border-line bg-bg text-ink font-mono focus:outline-none focus:border-accent">
                        <span class="font-semibold text-muted shrink-0">{{ __('Hari') }}</span>
                    </div>
                    <span class="text-[11px] text-muted mt-1 block">{{ __('Paket sensor frekuensi tinggi dibersihkan oleh tracker:purge-retention (default: 30 hari)') }}</span>
                </div>

                <div>
                    <label class="block font-semibold text-muted uppercase tracking-wider mb-1">{{ __('Retensi Agregat Per Jam (Hari)') }} *</label>
                    <div class="flex items-center space-x-2">
                        <input type="number" name="retention_hourly_days" value="{{ old('retention_hourly_days', $thresholds['retention_hourly_days']) }}" required class="w-full px-3 py-2 rounded-md border border-line bg-bg text-ink font-mono focus:outline-none focus:border-accent">
                        <span class="font-semibold text-muted shrink-0">{{ __('Hari') }}</span>
                    </div>
                    <span class="text-[11px] text-muted mt-1 block">{{ __('Statistik agregat jangka panjang disimpan di telemetry_hourly (default: 730 hari / 2 tahun)') }}</span>
                </div>

                <div class="pt-3 border-t border-line flex justify-end">
                    <button type="submit" class="px-4 py-2 rounded-md bg-accent hover:bg-blue-600 text-ink text-xs font-medium transition-colors cursor-pointer">
                        {{ __('Simpan Keamanan & Retensi') }}
                    </button>
                </div>
            </form>
        </x-card>
    </div>

    <!-- Safety Architecture Banner -->
    <div class="bg-panel border border-line rounded-md p-4">
        <h3 class="text-sm font-semibold text-ink flex items-center space-x-2">
            <svg class="w-4 h-4 text-ok" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
            <span>{{ __('Pemberitahuan Arsitektur Keamanan Edge-First') }}</span>
        </h3>
        <p class="text-xs text-muted mt-1 leading-relaxed">
            {{ __('Aturan keselamatan divalidasi di sisi server sebelum pengiriman MQTT, dan secara independen di dalam firmware edge ESP32. Jika terjadi gangguan jaringan, kontroler ESP32 terus menjalankan pelacakan astronomis otonom lokal dan segera memicu mode aman aerodinamis saat hembusan angin kencang terdeteksi.') }}
        </p>
    </div>
</x-layouts.app>
