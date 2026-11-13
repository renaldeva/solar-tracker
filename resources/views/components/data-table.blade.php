@props([
    'headers' => [],
    'emptyMessage' => 'No records found.',
    'emptyIcon' => null,
])

<div class="overflow-x-auto rounded-md border border-line bg-panel">
    <table class="min-w-full divide-y divide-line">
        @if(count($headers) > 0)
            <thead class="bg-bg/60">
                <tr>
                    @foreach($headers as $header)
                        <th scope="col" class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-muted font-mono">
                            {{ $header }}
                        </th>
                    @endforeach
                </tr>
            </thead>
        @endif
        <tbody class="divide-y divide-line bg-panel text-[14px] text-ink">
            {{ $slot }}
        </tbody>
    </table>

    @if(isset($empty) && $empty)
        <div class="p-8 text-center">
            @if($emptyIcon)
                <div class="mx-auto w-10 h-10 rounded bg-bg text-muted flex items-center justify-center mb-2">
                    {!! $emptyIcon !!}
                </div>
            @endif
            <p class="text-sm font-medium text-muted">{{ $emptyMessage }}</p>
        </div>
    @endif

    @if(isset($pagination))
        <div class="px-4 py-3 bg-bg/40 border-t border-line">
            {{ $pagination }}
        </div>
    @endif
</div>
