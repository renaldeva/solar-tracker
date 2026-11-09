@props([
    'headers' => [],
    'emptyMessage' => 'No records found.',
    'emptyIcon' => null,
])

<div class="overflow-x-auto rounded-xl border border-slate-200/80 bg-white shadow-xs">
    <table class="min-w-full divide-y divide-slate-200">
        @if(count($headers) > 0)
            <thead class="bg-slate-50/75">
                <tr>
                    @foreach($headers as $header)
                        <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                            {{ $header }}
                        </th>
                    @endforeach
                </tr>
            @</thead>
        @endif
        <tbody class="divide-y divide-slate-100 bg-white text-sm text-slate-700">
            {{ $slot }}
        </tbody>
    </table>

    @if(isset($empty) && $empty)
        <div class="p-12 text-center">
            <div class="mx-auto w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mb-3">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                </svg>
            </div>
            <p class="text-sm font-medium text-slate-600">{{ $emptyMessage }}</p>
        </div>
    @endif

    @if(isset($pagination))
        <div class="px-5 py-3.5 bg-slate-50/60 border-t border-slate-200">
            {{ $pagination }}
        </div>
    @endif
</div>