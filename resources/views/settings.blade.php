<x-layouts.app :title="__('System Settings')">
    <!-- Page Header -->
    <x-page-header 
        :title="__('System Settings & Site Configuration')" 
        :subtitle="__('Kelola koordinat instalasi fasilitas, target hasil panen surya harian, dan interlock keamanan.')"
        :badge="__('System Settings')"
    />

    @if(session('status'))
        <div class="mb-5 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-xs text-emerald-800 flex items-center justify-between shadow-xs">
            <div class="flex items-center space-x-2.5">
                <svg class="w-4 h-4 shrink-0 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span class="font-medium">{{ session('status') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-emerald-700 hover:opacity-75 cursor-pointer text-lg leading-none">&times;</button>
        </div>
    @endif

    @if($errors->any())
        <div class="mb-5 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-xs text-rose-800 shadow-xs">
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
                    Parameter Sistem & Lokasi Fasilitas Siap
                </h2>
                <p class="text-xs text-muted mt-0.5">
                    Koordinat aktif: <strong class="text-ink">{{ $site->name ?? 'Cikarang Pilot' }}</strong> ({{ $site->latitude ?? '-6.2944' }}°, {{ $site->longitude ?? '107.1539' }}° &middot; {{ $site->timezone ?? 'Asia/Jakarta' }}).
                </p>
            </div>
        </div>
        <div class="hidden sm:flex items-center space-x-2">
            <span class="px-3 py-1 rounded-xl text-xs font-mono font-semibold bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800/60">
                SCADA READY
            </span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-6">
        <!-- Site Information Card -->
        <x-card :title="__('Informasi Fasilitas & Target')" :subtitle="__('Parameter instalasi fisik & referensi astronomis')">
            <form method="POST" action="{{ route('settings.site') }}" class="space-y-4 text-xs">
                @csrf
                <div>
                    <label class="block font-semibold text-muted uppercase tracking-wider mb-1.5">{{ __('Nama Fasilitas / Lokasi') }} *</label>
                    <input type="text" name="name" value="{{ old('name', $site->name ?? '') }}" required class="w-full px-3.5 py-2.5 rounded-xl border border-line bg-panel text-ink focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 dark:focus:ring-blue-900/40 shadow-2xs">
                </div>

                <div>
                    <label class="block font-semibold text-muted uppercase tracking-wider mb-1.5">{{ __('Lokasi Geografis') }} *</label>
                    <input type="text" name="location" value="{{ old('location', $site->location ?? '') }}" required class="w-full px-3.5 py-2.5 rounded-xl border border-line bg-panel text-ink focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 dark:focus:ring-blue-900/40 shadow-2xs">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <label class="block font-semibold text-muted uppercase tracking-wider mb-1.5">{{ __('Garis Lintang (° U/S)') }} *</label>
                        <input type="number" step="0.0001" name="latitude" value="{{ old('latitude', $site->latitude ?? -6.2944) }}" required class="w-full px-3.5 py-2.5 rounded-xl border border-line bg-panel text-ink font-mono focus:outline-none focus:border-blue-500 shadow-2xs">
                    </div>
                    <div>
                        <label class="block font-semibold text-muted uppercase tracking-wider mb-1.5">{{ __('Garis Bujur (° T/B)') }} *</label>
                        <input type="number" step="0.0001" name="longitude" value="{{ old('longitude', $site->longitude ?? 107.1539) }}" required class="w-full px-3.5 py-2.5 rounded-xl border border-line bg-panel text-ink font-mono focus:outline-none focus:border-blue-500 shadow-2xs">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <label class="block font-semibold text-muted uppercase tracking-wider mb-1.5">{{ __('Zona Waktu Lokal') }} *</label>
                        <input type="text" name="timezone" value="{{ old('timezone', $site->timezone ?? 'Asia/Jakarta') }}" required class="w-full px-3.5 py-2.5 rounded-xl border border-line bg-panel text-ink font-mono focus:outline-none focus:border-blue-500 shadow-2xs">
                    </div>
                    <div>
                        <label class="block font-semibold text-muted uppercase tracking-wider mb-1.5">{{ __('Target Energi Harian (kWh)') }} *</label>
                        <input type="number" step="0.5" name="daily_energy_target_kwh" value="{{ old('daily_energy_target_kwh', $site->daily_energy_target_kwh ?? 50.0) }}" required class="w-full px-3.5 py-2.5 rounded-xl border border-line bg-panel text-ink font-mono focus:outline-none focus:border-blue-500 shadow-2xs">
                    </div>
                </div>

                <div class="pt-3 border-t border-line flex justify-end">
                    <button type="submit" class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold transition-colors shadow-xs cursor-pointer">
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
                    <label class="block font-semibold text-muted uppercase tracking-wider mb-1.5">{{ __('Kecepatan Mode Aman Angin (m/s)') }} *</label>
                    <div class="flex items-center space-x-2">
                        <input type="number" step="0.5" name="wind_stow_threshold_ms" value="{{ old('wind_stow_threshold_ms', $thresholds['wind_stow_threshold_ms']) }}" required class="w-full px-3.5 py-2.5 rounded-xl border border-line bg-panel text-ink font-mono focus:outline-none focus:border-blue-500 shadow-2xs">
                        <span class="font-semibold text-muted shrink-0 text-xs">m/s</span>
                    </div>
                    <span class="text-[11px] text-muted mt-1 block">{{ __('Memicu mode aman datar aerodinamis otomatis dan interlock perintah manual') }}</span>
                </div>

                <div>
                    <label class="block font-semibold text-muted uppercase tracking-wider mb-1.5">{{ __('Retensi Telemetri Mentah 5 Detik (Hari)') }} *</label>
                    <div class="flex items-center space-x-2">
                        <input type="number" name="retention_raw_days" value="{{ old('retention_raw_days', $thresholds['retention_raw_days']) }}" required class="w-full px-3.5 py-2.5 rounded-xl border border-line bg-panel text-ink font-mono focus:outline-none focus:border-blue-500 shadow-2xs">
                        <span class="font-semibold text-muted shrink-0 text-xs">{{ __('Hari') }}</span>
                    </div>
                    <span class="text-[11px] text-muted mt-1 block">{{ __('Paket sensor frekuensi tinggi dibersihkan oleh tracker:purge-retention (default: 30 hari)') }}</span>
                </div>

                <div>
                    <label class="block font-semibold text-muted uppercase tracking-wider mb-1.5">{{ __('Retensi Agregat Per Jam (Hari)') }} *</label>
                    <div class="flex items-center space-x-2">
                        <input type="number" name="retention_hourly_days" value="{{ old('retention_hourly_days', $thresholds['retention_hourly_days']) }}" required class="w-full px-3.5 py-2.5 rounded-xl border border-line bg-panel text-ink font-mono focus:outline-none focus:border-blue-500 shadow-2xs">
                        <span class="font-semibold text-muted shrink-0 text-xs">{{ __('Hari') }}</span>
                    </div>
                    <span class="text-[11px] text-muted mt-1 block">{{ __('Statistik agregat jangka panjang disimpan di telemetry_hourly (default: 730 hari / 2 tahun)') }}</span>
                </div>

                <div class="pt-3 border-t border-line flex justify-end">
                    <button type="submit" class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold transition-colors shadow-xs cursor-pointer">
                        {{ __('Simpan Keamanan & Retensi') }}
                    </button>
                </div>
            </form>
        </x-card>
    </div>

    <!-- Safety Architecture Banner -->
    <div class="bg-panel border border-line rounded-2xl p-5 shadow-xs">
        <h3 class="text-sm font-semibold text-ink flex items-center space-x-2.5">
            <div class="w-7 h-7 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
            </div>
            <span>{{ __('Pemberitahuan Arsitektur Keamanan Edge-First') }}</span>
        </h3>
        <p class="text-xs text-muted mt-2 leading-relaxed">
            {{ __('Aturan keselamatan divalidasi di sisi server sebelum pengiriman MQTT, dan secara independen di dalam firmware edge ESP32. Jika terjadi gangguan jaringan, kontroler ESP32 terus menjalankan pelacakan astronomis otonom lokal dan segera memicu mode aman aerodinamis saat hembusan angin kencang terdeteksi.') }}
        </p>
    </div>
        <p class="text-xs text-muted mt-2 leading-relaxed">
            {{ __('Aturan keselamatan divalidasi di sisi server sebelum pengiriman MQTT, dan secara independen di dalam firmware edge ESP32. Jika terjadi gangguan jaringan, kontroler ESP32 terus menjalankan pelacakan astronomis otonom lokal dan segera memicu mode aman aerodinamis saat hembusan angin kencang terdeteksi.') }}
        </p>
    </div>
</x-layouts.app>
