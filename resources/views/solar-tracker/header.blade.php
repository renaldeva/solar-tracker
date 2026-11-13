{{-- Header & Device Selector Panel --}}
<x-page-header 
    :title="__('Dual-Axis Solar Tracker')" 
    :subtitle="__('Orientasi sudut panel real-time, output daya listrik, dan sistem kendali pelacak.')"
    badge="{{ $device->name }}"
>
    <x-slot:actions>
        <div class="flex items-center flex-wrap gap-2.5">
            <!-- Device Selector Dropdown -->
            <div class="relative">
                <select 
                    onchange="window.location.href = '{{ route('solar-tracker') }}?uid=' + this.value"
                    class="h-10 text-xs sm:text-sm font-medium py-1.5 pl-3 pr-8 rounded-xl border border-line bg-white text-ink focus:border-blue-500 focus:outline-none cursor-pointer shadow-2xs"
                >
                    @foreach($devices as $d)
                        <option value="{{ $d->uid }}" {{ $d->uid === $device->uid ? 'selected' : '' }}>
                            {{ $d->name }} ({{ $d->uid }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Status Pill & Mode Badge -->
            <x-status-pill :status="$device->status" :pulse="$device->status === 'online'" size="md" />

            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium border border-line bg-white text-slate-700 shadow-2xs">
                <span class="w-1.5 h-1.5 rounded-full {{ $device->mode === 'auto' ? 'bg-blue-600' : 'bg-amber-500' }}"></span>
                <span>{{ __('Mode:') }} <strong class="text-ink uppercase">{{ $device->mode }}</strong></span>
            </span>
        </div>
    </x-slot:actions>
</x-page-header>

{{-- Kartu Besar: "Panel sejajar / tidak sejajar dengan matahari" memakai $isAligned dan $totalDeviation --}}
<div class="mb-6 p-5 rounded-2xl border transition-all {{ $isAligned ? 'bg-emerald-50/80 border-emerald-200 text-emerald-950' : 'bg-amber-50/80 border-amber-200 text-amber-950' }}">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
            <div class="w-10 h-10 rounded-2xl flex items-center justify-center shrink-0 shadow-xs {{ $isAligned ? 'bg-emerald-500 text-white' : 'bg-amber-500 text-white' }}">
                @if($isAligned)
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                @else
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                @endif
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-base sm:text-lg font-bold tracking-tight">
                        {{ $isAligned ? __('Panel Sejajar dengan Matahari') : __('Panel Menyesuaikan Posisi') }}
                    </h2>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $isAligned ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                        {{ $isAligned ? __('SEJAJAR') : __('MENYESUAIKAN') }}
                    </span>
                </div>
                <p class="text-xs sm:text-sm text-slate-600 mt-0.5">
                    {{ $isAligned 
                        ? __('Deviasi total :dev° (di bawah ambang batas :thresh°). Panel menerima penyinaran matahari optimal.', ['dev' => number_format($totalDeviation, 1, ',', '.'), 'thresh' => number_format($threshold, 1, ',', '.')])
                        : __('Deviasi total :dev° (ambang batas :thresh°). Aktuator sedang bergerak menyelaraskan orientasi ke posisi matahari.', ['dev' => number_format($totalDeviation, 1, ',', '.'), 'thresh' => number_format($threshold, 1, ',', '.')]) }}
                </p>
            </div>
        </div>

        <div class="flex items-center gap-3 self-end sm:self-auto shrink-0 bg-white/80 backdrop-blur-xs px-4 py-2 rounded-xl border border-line">
            <div class="text-right">
                <div class="text-[10px] font-semibold text-muted uppercase">{{ __('Deviasi Total') }}</div>
                <div class="text-sm font-bold font-mono tabular-nums {{ $isAligned ? 'text-emerald-700' : 'text-amber-700' }}">
                    {{ number_format($totalDeviation, 1, ',', '.') }}°
                </div>
            </div>
        </div>
    </div>
</div>

{{-- High Wind Alarm Banner --}}
@if($isWindAlert)
    <div class="mb-6 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-950 flex items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <span class="p-2 bg-rose-500 rounded-xl text-white shrink-0">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </span>
            <div>
                <div class="font-bold text-sm text-rose-700 uppercase">{{ __('PERINGATAN ANGIN KENCANG: MODE AMAN (STOW) AKTIF') }}</div>
                <div class="text-xs text-slate-600 mt-0.5">
                    {{ __('Kecepatan angin saat ini :speed m/s, melebihi batas aman :limit m/s. Aktuator dikunci pada posisi mendatar darurat.', ['speed' => formatNumberId($telemetry?->wind_speed ?? 0, 1), 'limit' => formatNumberId($windStowThreshold, 1)]) }}
                </div>
            </div>
        </div>
        <span class="text-xs font-bold px-3 py-1 bg-rose-100 border border-rose-200 text-rose-800 rounded-full shrink-0">
            {{ __('TERKUNCI AMAN') }}
        </span>
    </div>
@endif
