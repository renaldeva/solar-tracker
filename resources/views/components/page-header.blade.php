@props([
    'title',
    'subtitle' => null,
    'badge' => null,
    'badgeType' => 'info',
])

@php
    $badgeStyle = match ($badgeType) {
        'success' => 'bg-ok/10 text-ok border-ok/30',
        'warning' => 'bg-warn/10 text-warn border-warn/30',
        'danger' => 'bg-danger/10 text-danger border-danger/30',
        'neutral' => 'bg-line/20 text-muted border-line',
        default => 'bg-accent/10 text-accent border-accent/30',
    };
@endphp

<div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
        <div class="flex items-center gap-3">
            <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-ink">{{ __($title) }}</h1>
            @if($badge)
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono font-medium border {{ $badgeStyle }}">
                    {{ $badge }}
                </span>
            @endif
        </div>
        @if($subtitle)
            <p class="text-xs sm:text-sm text-muted mt-1">{{ __($subtitle) }}</p>
        @endif
    </div>

    @if(isset($actions))
        <div class="flex items-center flex-wrap gap-2">
            {{ $actions }}
        </div>
    @endif
</div>
