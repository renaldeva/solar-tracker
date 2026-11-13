@props([
    'status' => 'offline',
    'label' => null,
    'pulse' => false,
    'size' => 'md', // sm, md
])

@php
    $normalized = strtolower(trim((string) $status));

    $config = match ($normalized) {
        'online', 'active', 'ok', 'completed', 'accepted', 'resolved' => [
            'bg' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'dot' => 'bg-emerald-500',
            'defaultLabel' => ucfirst($normalized),
            'pulse' => $pulse,
        ],
        'unstable', 'warning', 'stow', 'idle' => [
            'bg' => 'bg-amber-50 text-amber-700 border-amber-200',
            'dot' => 'bg-amber-500',
            'defaultLabel' => ucfirst($normalized),
            'pulse' => false,
        ],
        'offline', 'fault', 'critical', 'failed', 'rejected', 'error' => [
            'bg' => 'bg-rose-50 text-rose-700 border-rose-200',
            'dot' => 'bg-rose-500',
            'defaultLabel' => ucfirst($normalized),
            'pulse' => false,
        ],
        'auto' => [
            'bg' => 'bg-blue-50 text-blue-700 border-blue-200',
            'dot' => 'bg-blue-600',
            'defaultLabel' => 'AUTO',
            'pulse' => false,
        ],
        'manual' => [
            'bg' => 'bg-slate-100 text-slate-700 border-slate-200',
            'dot' => 'bg-slate-500',
            'defaultLabel' => 'MANUAL',
            'pulse' => false,
        ],
        'info', 'queued', 'sent' => [
            'bg' => 'bg-blue-50 text-blue-700 border-blue-200',
            'dot' => 'bg-blue-600',
            'defaultLabel' => ucfirst($normalized),
            'pulse' => false,
        ],
        default => [
            'bg' => 'bg-slate-50 text-slate-600 border-slate-200',
            'dot' => 'bg-slate-400',
            'defaultLabel' => ucfirst($normalized),
            'pulse' => false,
        ],
    };

    $displayLabel = $label ?? $config['defaultLabel'];
    $sizeClass = $size === 'sm' ? 'px-2 py-0.5 text-[11px]' : 'px-2.5 py-1 text-xs';
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 font-medium rounded-full border {$config['bg']} {$sizeClass}"]) }}>
    <span class="relative flex h-2 w-2 shrink-0">
        @if($config['pulse'])
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75 {{ $config['dot'] }}"></span>
        @endif
        <span class="relative inline-flex rounded-full h-2 w-2 {{ $config['dot'] }}"></span>
    </span>
    <span>{{ $displayLabel }}</span>
</span>
