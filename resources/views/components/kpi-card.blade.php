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
            'border' => 'border-emerald-200 dark:border-emerald-800/60',
            'bg' => 'bg-emerald-50 dark:bg-emerald-950/40',
            'text' => 'text-emerald-700 dark:text-emerald-400',
            'dot' => 'bg-emerald-500',
            'iconBg' => 'bg-emerald-50 text-emerald-600 border-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-800/60',
        ],
        'warn' => [
            'border' => 'border-amber-200 dark:border-amber-800/60',
            'bg' => 'bg-amber-50 dark:bg-amber-950/40',
            'text' => 'text-amber-700 dark:text-amber-400',
            'dot' => 'bg-amber-500',
            'iconBg' => 'bg-amber-50 text-amber-600 border-amber-100 dark:bg-amber-950/40 dark:text-amber-400 dark:border-amber-800/60',
        ],
        'danger' => [
            'border' => 'border-rose-200 dark:border-rose-800/60',
            'bg' => 'bg-rose-50 dark:bg-rose-950/40',
            'text' => 'text-rose-700 dark:text-rose-400',
            'dot' => 'bg-rose-500',
            'iconBg' => 'bg-rose-50 text-rose-600 border-rose-100 dark:bg-rose-950/40 dark:text-rose-400 dark:border-rose-800/60',
        ],
        'neutral' => [
            'border' => 'border-slate-200 dark:border-slate-700',
            'bg' => 'bg-slate-50 dark:bg-slate-800/40',
            'text' => 'text-slate-600 dark:text-slate-400',
            'dot' => 'bg-slate-400',
            'iconBg' => 'bg-slate-50 text-slate-500 border-slate-100 dark:bg-slate-800/40 dark:text-slate-400 dark:border-slate-700',
        ],
        default => [
            'border' => 'border-blue-200 dark:border-blue-800/60',
            'bg' => 'bg-blue-50 dark:bg-blue-950/40',
            'text' => 'text-blue-700 dark:text-blue-400',
            'dot' => 'bg-blue-600 dark:bg-blue-500',
            'iconBg' => 'bg-blue-50 text-blue-600 border-blue-100 dark:bg-blue-950/40 dark:text-blue-400 dark:border-blue-800/60',
        ],
    };
@endphp

<div {{ $attributes->merge(['class' => 'bg-panel rounded-2xl border border-line p-5 flex flex-col justify-between hover:border-accent-mid transition-colors']) }}>
    <!-- Header: Label kecil di kiri atas, icon di kotak bersudut bulat di kanan atas -->
    <div class="flex items-start justify-between gap-2 mb-2">
        <span class="text-xs font-semibold text-muted uppercase tracking-wider truncate">{{ __($title) }}</span>
        
        <div class="flex items-center gap-1.5 shrink-0">
            @if($badge)
                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-medium border {{ $toneConfig['border'] }} {{ $toneConfig['bg'] }} {{ $toneConfig['text'] }}">
                    <span class="w-1.5 h-1.5 rounded-full {{ $toneConfig['dot'] }}"></span>
                    <span>{{ $badge }}</span>
                </span>
            @endif

            @if($icon)
                <div class="w-8 h-8 rounded-xl border flex items-center justify-center shrink-0 {{ $toneConfig['iconBg'] }}">
                    <div class="w-4 h-4 flex items-center justify-center">
                        {!! $icon !!}
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Nilai KPI besar ~32px dengan satuan kecil -->
    <div class="my-1">
        <div class="flex items-baseline gap-1">
            <span class="text-2xl sm:text-3xl font-bold text-ink font-mono tabular-nums tracking-tight">
                {{ $value }}
            </span>
            @if($unit)
                <span class="text-sm font-semibold text-muted font-mono">
                    {{ $unit }}
                </span>
            @endif
        </div>
    </div>

    <!-- Keterangan bahasa awam di bawah & sparkline/slot -->
    <div class="mt-2 pt-2 border-t border-line flex items-center justify-between text-xs text-muted">
        @if($subtitle)
            <div class="truncate text-muted font-normal">
                {{ __($subtitle) }}
            </div>
        @endif

        @if($trend)
            <div class="inline-flex items-center gap-1 font-mono font-medium shrink-0 ml-auto {{ $trendUp ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                <svg class="w-3.5 h-3.5 {{ $trendUp ? '' : 'rotate-180' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                </svg>
                <span>{{ $trend }}</span>
            </div>
        @endif
    </div>

    @if($slot->isNotEmpty())
        <div class="mt-2.5">
            {{ $slot }}
        </div>
    @endif
</div>
