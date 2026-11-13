@props([
    'headers' => [],
    'emptyMessage' => 'Tidak ada data ditemukan.',
    'emptyIcon' => null,
])

<div class="overflow-x-auto rounded-2xl border border-line bg-white">
    <table class="min-w-full divide-y divide-line">
        @if(count($headers) > 0)
            <thead class="bg-slate-50/80">
                <tr>
                    @foreach($headers as $header)
                        <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted font-sans">
                            {{ $header }}
                        </th>
                    @endforeach
                </tr>
            </thead>
        @endif
        <tbody class="divide-y divide-line bg-white text-[14px] text-ink">
            {{ $slot }}
        </tbody>
    </table>

    @if(isset($empty) && $empty)
        <div class="p-8 text-center">
            @if($emptyIcon)
                <div class="mx-auto w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center mb-2">
                    {!! $emptyIcon !!}
                </div>
            @endif
            <p class="text-sm font-medium text-muted">{{ $emptyMessage }}</p>
        </div>
    @endif

    @if(isset($pagination))
        <div class="px-5 py-3.5 bg-slate-50/60 border-t border-line">
            {{ $pagination }}
        </div>
    @endif
</div>
