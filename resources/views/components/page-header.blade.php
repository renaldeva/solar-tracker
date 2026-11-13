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
        'success' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-800/60',
        'warning' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-400 dark:border-amber-800/60',
        'danger' => 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-400 dark:border-rose-800/60',
        'neutral' => 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700',
        default => 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/40 dark:text-blue-400 dark:border-blue-800/60',
    };

    $bannerConfig = match ($statusTone) {
        'warn' => [
            'bg' => 'bg-amber-50 border-amber-200 text-amber-900 dark:bg-amber-950/40 dark:border-amber-800/60 dark:text-amber-200',
            'dot' => 'bg-amber-500',
            'icon' => 'text-amber-600 dark:text-amber-400',
        ],
        'danger' => [
            'bg' => 'bg-rose-50 border-rose-200 text-rose-900 dark:bg-rose-950/40 dark:border-rose-800/60 dark:text-rose-200',
            'dot' => 'bg-rose-500',
            'icon' => 'text-rose-600 dark:text-rose-400',
        ],
        'info' => [
            'bg' => 'bg-blue-50 border-blue-200 text-blue-900 dark:bg-blue-950/40 dark:border-blue-800/60 dark:text-blue-200',
            'dot' => 'bg-blue-500',
            'icon' => 'text-blue-600 dark:text-blue-400',
        ],
        default => [
            'bg' => 'bg-emerald-50 border-emerald-200 text-emerald-900 dark:bg-emerald-950/40 dark:border-emerald-800/60 dark:text-emerald-200',
            'dot' => 'bg-emerald-500',
            'icon' => 'text-emerald-600 dark:text-emerald-400',
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

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-xl sm:text-2xl font-bold text-ink tracking-tight">
                    {{ __($title) }}
                </h1>
                @if($badge)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $badgeStyle }}">
                        {{ $badge }}
                    </span>
                @endif
            </div>
            @if($subtitle)
                <p class="text-xs sm:text-sm text-muted mt-1 font-normal">
                    {{ __($subtitle) }}
                </p>
            @endif
        </div>

        @if(isset($actions))
            <div class="flex items-center gap-2.5 shrink-0">
                {{ $actions }}
            </div>
        @endif
    </div>
</div>
