@props([
    'title' => null,
    'subtitle' => null,
    'icon' => null,
])

<div {{ $attributes->merge(['class' => 'bg-panel rounded-md border border-line overflow-hidden']) }}>
    @if($title || isset($header) || isset($actions))
        <div class="px-4 py-3 border-b border-line flex items-center justify-between gap-3">
            <div class="flex items-center gap-2.5 min-w-0">
                @if($icon)
                    <div class="text-accent shrink-0">
                        {!! $icon !!}
                    </div>
                @endif
                <div class="min-w-0">
                    @if($title)
                        <h3 class="text-sm font-semibold text-ink tracking-tight truncate">{{ __($title) }}</h3>
                    @endif
                    @if($subtitle)
                        <p class="text-xs text-muted mt-0.5 truncate">{{ __($subtitle) }}</p>
                    @endif
                    @if(isset($header))
                        {{ $header }}
                    @endif
                </div>
            </div>
            @if(isset($actions))
                <div class="flex items-center gap-2 shrink-0">
                    {{ $actions }}
                </div>
            @endif
        </div>
    @endif

    <div class="p-4 text-[14px] text-ink">
        {{ $slot }}
    </div>

    @if(isset($footer))
        <div class="px-4 py-2.5 bg-bg/50 border-t border-line text-xs text-muted">
            {{ $footer }}
        </div>
    @endif
</div>
