{{-- Header & Device Selector Panel --}}
<x-page-header 
    :title="__('Dual-Axis Solar Tracker')" 
    :subtitle="__('Orientasi absolut BNO055 IMU real-time, kelistrikan PZEM-017 DC, kinematika BTS7960, dan diagnostik sensor.')"
    badge="{{ $device->name }}"
>
    <x-slot:actions>
        <div class="flex items-center flex-wrap gap-2.5">
            <!-- Device Selector Dropdown -->
            <div class="relative">
                <select 
                    onchange="window.location.href = '{{ route('solar-tracker') }}?uid=' + this.value"
                    class="h-9 text-xs font-mono font-medium py-1 pl-3 pr-8 rounded border border-line bg-panel text-ink focus:border-accent focus:outline-none cursor-pointer"
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

            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded text-xs font-mono font-medium border border-line bg-bg text-muted">
                <span class="w-1.5 h-1.5 rounded-full {{ $device->mode === 'auto' ? 'bg-accent' : 'bg-warn' }}"></span>
                <span>{{ __('Mode:') }} <strong class="text-ink uppercase">{{ $device->mode }}</strong></span>
            </span>
        </div>
    </x-slot:actions>
</x-page-header>

{{-- High Wind Alarm Banner --}}
@if($isWindAlert)
    <div class="mb-6 p-4 rounded-md bg-danger/10 border border-danger/30 text-ink flex items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <span class="p-2 bg-danger/20 rounded text-danger shrink-0">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </span>
            <div>
                <div class="font-bold text-sm text-danger font-mono uppercase">{{ __('PERINGATAN ANGIN KENCANG: MODE AMAN (STOW) AKTIF') }}</div>
                <div class="text-xs text-muted mt-0.5">
                    {{ __('Kecepatan angin saat ini :speed m/s, melebihi batas aman :limit m/s. Aktuator terkunci pada posisi stow datar.', ['speed' => formatNumberId($telemetry?->wind_speed ?? 0, 1), 'limit' => formatNumberId($windStowThreshold, 1)]) }}
                </div>
            </div>
        </div>
        <span class="text-xs font-mono font-bold px-2.5 py-1 bg-danger/20 border border-danger/40 text-danger rounded shrink-0">
            {{ __('INTERLOCKED') }}
        </span>
    </div>
@endif

