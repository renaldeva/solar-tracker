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
            'bg' => 'bg-ok/10 text-ok border-ok/30',
            'dot' => 'bg-ok',
            'defaultLabel' => ucfirst($normalized),
            'pulse' => $pulse,
        ],
        'unstable', 'warning', 'stow', 'idle' => [
            'bg' => 'bg-warn/10 text-warn border-warn/30',
            'dot' => 'bg-warn',
            'defaultLabel' => ucfirst($normalized),
            'pulse' => false,
        ],
        'offline', 'fault', 'critical', 'failed', 'rejected', 'error' => [
            'bg' => 'bg-danger/10 text-danger border-danger/30',
            'dot' => 'bg-danger',
            'defaultLabel' => ucfirst($normalized),
            'pulse' => false,
        ],
        'auto' => [
            'bg' => 'bg-accent/10 text-accent border-accent/30',
            'dot' => 'bg-accent',
            'defaultLabel' => 'AUTO',
            'pulse' => false,
        ],
        'manual' => [
            'bg' => 'bg-panel text-ink border-line',
            'dot' => 'bg-accent',
            'defaultLabel' => 'MANUAL',
            'pulse' => false,
        ],
        'info', 'queued', 'sent' => [
            'bg' => 'bg-accent/10 text-accent border-accent/30',
            'dot' => 'bg-accent',
            'defaultLabel' => ucfirst($normalized),
            'pulse' => false,
        ],
        default => [
            'bg' => 'bg-line/20 text-muted border-line',
            'dot' => 'bg-muted',
            'defaultLabel' => ucfirst($normalized),
            'pulse' => false,
        ],
    };

    $displayLabel = $label ?? $config['defaultLabel'];
    $sizeClass = $size === 'sm' ? 'px-2 py-0.5 text-[11px]' : 'px-2.5 py-1 text-xs';
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 font-mono font-medium rounded border {$config['bg']} {$sizeClass}"]) }}>
    <span class="relative flex h-2 w-2 shrink-0">
        @if($config['pulse'])
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75 {{ $config['dot'] }}"></span>
        @endif
        <span class="relative inline-flex rounded-full h-2 w-2 {{ $config['dot'] }}"></span>
    </span>
    <span>{{ $displayLabel }}</span>
</span>
