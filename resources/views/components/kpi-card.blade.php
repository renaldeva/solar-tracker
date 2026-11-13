@props([
    'title',
    'value',
    'unit' => '',
    'subtitle' => null,
    'icon' => null,
    'badge' => null,
    'badgeType' => 'info', // success, warning, danger, info, neutral
    'trend' => null,
    'trendUp' => true,
    'tone' => null, // ok, warn, danger, accent, neutral
])

@php
    $effectiveTone = $tone ?? match ($badgeType) {
        'success' => 'ok',
        'warning' => 'warn',
        'danger' => 'danger',
        'neutral' => 'neutral',
        default => 'accent',
    };

    $toneConfig = match ($effectiveTone) {
        'ok' => [
            'border' => 'border-ok/30',
            'bg' => 'bg-ok/10',
            'text' => 'text-ok',
            'dot' => 'bg-ok',
        ],
        'warn' => [
            'border' => 'border-warn/30',
            'bg' => 'bg-warn/10',
            'text' => 'text-warn',
            'dot' => 'bg-warn',
        ],
        'danger' => [
            'border' => 'border-danger/30',
            'bg' => 'bg-danger/10',
            'text' => 'text-danger',
            'dot' => 'bg-danger',
        ],
        'neutral' => [
            'border' => 'border-line',
            'bg' => 'bg-line/20',
            'text' => 'text-muted',
            'dot' => 'bg-muted',
        ],
        default => [
            'border' => 'border-accent/30',
            'bg' => 'bg-accent/10',
            'text' => 'text-accent',
            'dot' => 'bg-accent',
        ],
    };
@endphp

<div {{ $attributes->merge(['class' => 'bg-panel rounded-md border border-line p-4 flex flex-col justify-between']) }}>
    <div class="flex items-start justify-between gap-2 mb-2">
        <span class="text-xs font-medium text-muted uppercase tracking-wider truncate">{{ __($title) }}</span>
        @if($badge)
            <span class="shrink-0 inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[11px] font-mono font-medium border {{ $toneConfig['border'] }} {{ $toneConfig['bg'] }} {{ $toneConfig['text'] }}">
                <span class="w-1.5 h-1.5 rounded-full {{ $toneConfig['dot'] }}"></span>
                <span>{{ $badge }}</span>
            </span>
        @elseif($icon)
            <div class="text-muted shrink-0">
                {!! $icon !!}
            </div>
        @endif
    </div>

    <div class="flex items-baseline gap-1 my-1">
        <span class="text-3xl font-bold font-mono tabular-nums text-ink tracking-tight">{{ $value }}</span>
        @if($unit)
            <span class="text-xs font-mono text-muted font-normal">{{ $unit }}</span>
        @endif
    </div>

    @if($subtitle || $trend)
        <div class="mt-2 pt-2 border-t border-line/50 flex items-center text-xs text-muted">
            @if($trend)
                <span class="inline-flex items-center font-mono font-medium mr-2 {{ $trendUp ? 'text-ok' : 'text-danger' }}">
                    <span class="mr-1">{{ $trendUp ? '▲' : '▼' }}</span>
                    {{ $trend }}
                </span>
            @endif
            @if($subtitle)
                <span class="truncate">{{ __($subtitle) }}</span>
            @endif
        </div>
    @endif
</div>
