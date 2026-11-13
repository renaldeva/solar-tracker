@props([
    'name',
    'title' => null,
    'maxWidth' => 'md', // sm, md, lg, xl, 2xl
])

@php
    $maxWidthClass = match ($maxWidth) {
        'sm' => 'max-w-sm',
        'md' => 'max-w-md',
        'lg' => 'max-w-lg',
        'xl' => 'max-w-xl',
        '2xl' => 'max-w-2xl',
        default => 'max-w-md',
    };
@endphp

<div
    x-data="{ show: false }"
    x-show="show"
    x-on:open-modal.window="$event.detail === '{{ $name }}' ? show = true : null"
    x-on:close-modal.window="$event.detail === '{{ $name }}' ? show = false : null"
    x-on:keydown.escape.window="show = false"
    style="display: none;"
    class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0"
    aria-labelledby="modal-title"
    role="dialog"
    aria-modal="true"
>
    <!-- Backdrop -->
    <div
        x-show="show"
        x-transition:enter="ease-out duration-150"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-100"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-bg/80 transition-opacity"
        x-on:click="show = false"
    ></div>

    <!-- Modal Panel -->
    <div
        x-show="show"
        x-transition:enter="ease-out duration-150"
        x-transition:enter-start="opacity-0 translate-y-2 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="ease-in duration-100"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-2 sm:translate-y-0 sm:scale-95"
        class="relative mx-auto my-8 w-full {{ $maxWidthClass }} rounded-md bg-panel shadow-none border border-line overflow-hidden transform transition-all text-ink"
    >
        @if($title)
            <div class="px-5 py-3 border-b border-line flex items-center justify-between">
                <h3 class="text-sm font-semibold text-ink" id="modal-title">{{ __($title) }}</h3>
                <button
                    type="button"
                    x-on:click="show = false"
                    class="rounded p-1 text-muted hover:text-ink hover:bg-line/40 transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        @endif

        <div class="p-5 text-[14px] text-ink">
            {{ $slot }}
        </div>

        @if(isset($footer))
            <div class="px-5 py-3 bg-bg/50 border-t border-line flex items-center justify-end space-x-3">
                {{ $footer }}
            </div>
        @endif
    </div>
</div>
