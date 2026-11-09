@props([
    'title',
    'subtitle' => null,
    'badge' => null,
    'badgeType' => 'info',
])

<div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
        <div class="flex items-center space-x-3">
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">{{ __($title) }}</h1>
            @if($badge)
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border bg-blue-50 text-blue-700 border-blue-200">
                    {{ $badge }}
                </span>
            @endif
        </div>
        @if($subtitle)
            <p class="text-sm text-slate-500 mt-1">{{ __($subtitle) }}</p>
        @endif
    </div>

    @if(isset($actions))
        <div class="flex items-center flex-wrap gap-2.5">
            {{ $actions }}
        </div>
    @endif
</div>