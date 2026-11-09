@props([
    'title' => null,
    'subtitle' => null,
    'icon' => null,
])

<div {{ $attributes->merge(['class' => 'bg-white rounded-xl border border-slate-200/80 shadow-xs overflow-hidden']) }}>
    @if($title || isset($header) || isset($actions))
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <div class="flex items-center space-x-2.5">
                @if($icon)
                    <div class="p-1.5 rounded-lg bg-blue-50 text-blue-600">
                        {!! $icon !!}
                    </div>
                @endif
                <div>
                    @if($title)
                        <h3 class="text-sm font-semibold text-slate-800 tracking-tight">{{ __($title) }}</h3>
                    @endif
                    @if($subtitle)
                        <p class="text-xs text-slate-500 mt-0.5">{{ __($subtitle) }}</p>
                    @endif
                    @if(isset($header))
                        {{ $header }}
                    @endif
                </div>
            </div>
            @if(isset($actions))
                <div class="flex items-center space-x-2">
                    {{ $actions }}
                </div>
            @endif
        </div>
    @endif

    <div class="p-5">
        {{ $slot }}
    </div>

    @if(isset($footer))
        <div class="px-5 py-3.5 bg-slate-50/60 border-t border-slate-100 rounded-b-xl text-xs text-slate-500">
            {{ $footer }}
        </div>
    @endif
</div>