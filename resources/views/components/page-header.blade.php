@props([
    'title',
    'subtitle' => null,
    'badge' => null,
    'badgeType' => 'info',
    'statusMessage' => null,
    'statusTone' => 'ok', // ok, warn, danger, info
])

@php
    $badgeStyle = match ($badgeType) {
        'success' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'warning' => 'bg-amber-50 text-amber-700 border-amber-200',
        'danger' => 'bg-rose-50 text-rose-700 border-rose-200',
        'neutral' => 'bg-slate-100 text-slate-700 border-slate-200',
        default => 'bg-blue-50 text-blue-700 border-blue-200',
    };

    $bannerConfig = match ($statusTone) {
        'warn' => [
            'bg' => 'bg-amber-50 border-amber-200 text-amber-900',
            'dot' => 'bg-amber-500',
            'icon' => 'text-amber-600',
        ],
        'danger' => [
            'bg' => 'bg-rose-50 border-rose-200 text-rose-900',
            'dot' => 'bg-rose-500',
            'icon' => 'text-rose-600',
        ],
        'info' => [
            'bg' => 'bg-blue-50 border-blue-200 text-blue-900',
            'dot' => 'bg-blue-500',
            'icon' => 'text-blue-600',
        ],
        default => [
            'bg' => 'bg-emerald-50 border-emerald-200 text-emerald-900',
            'dot' => 'bg-emerald-500',
            'icon' => 'text-emerald-600',
        ],
    };
@endphp

<div class="mb-6 space-y-3">
    @if($statusMessage)
        <div class="flex items-center gap-2.5 px-4 py-2.5 rounded-xl border text-sm font-medium {{ $bannerConfig['bg'] }}">
            <span class="w-2.5 h-2.5 rounded-full {{ $bannerConfig['dot'] }} shrink-0"></span>
            <span>{{ __($statusMessage) }}</span>
        </div>
    @endif

    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-ink">{{ __($title) }}</h1>
                @if($badge)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border {{ $badgeStyle }}">
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
</div>
