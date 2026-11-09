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
])

@php
    $badgeStyles = [
        'success' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'warning' => 'bg-amber-50 text-amber-700 border-amber-200',
        'danger' => 'bg-rose-50 text-rose-700 border-rose-200',
        'info' => 'bg-blue-50 text-blue-700 border-blue-200',
        'neutral' => 'bg-slate-100 text-slate-700 border-slate-200',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'bg-white rounded-xl border border-slate-200/80 shadow-xs p-5 transition-all duration-200 hover:shadow-md']) }}>
    <div class="flex items-start justify-between gap-1.5 mb-2.5">
        <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider leading-tight">{{ __($title) }}</span>
        @if($icon)
            <div class="w-8 h-8 rounded-lg bg-blue-50/80 text-blue-600 flex items-center justify-center shrink-0">
                {!! $icon !!}
            </div>
        @elseif($badge)
            <span class="shrink-0 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium border {{ $badgeStyles[$badgeType] ?? $badgeStyles['neutral'] }}">
                {{ $badge }}
            </span>
        @endif
    </div>

    <div class="flex items-baseline space-x-1.5">
        <div class="text-2xl font-bold text-slate-800 tracking-tight">{{ $value }}</div>
        @if($unit)
            <span class="text-xs font-semibold text-slate-400">{{ $unit }}</span>
        @endif
    </div>

    @if($subtitle || $trend)
        <div class="mt-2.5 flex items-center text-xs">
            @if($trend)
                <span class="inline-flex items-center font-semibold mr-1.5 {{ $trendUp ? 'text-emerald-600' : 'text-rose-600' }}">
                    <svg class="w-3.5 h-3.5 mr-0.5 {{ $trendUp ? '' : 'rotate-180' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                    </svg>
                    {{ $trend }}
                </span>
            @endif
            @if($subtitle)
                <span class="text-slate-500">{{ __($subtitle) }}</span>
            @endif
        </div>
    @endif
</div>