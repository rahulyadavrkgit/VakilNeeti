<section id="case-connected" data-tab-panel role="tabpanel" aria-labelledby="tab-case-connected" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
    <h3 class="text-lg font-semibold">Connected matters</h3>
    @if($journey['connected'])
        <ul class="mt-1.5 divide-y divide-slate-100">
            @foreach($journey['connected'] as $connected)
                <li class="flex items-center justify-between gap-3 py-2">
                    <a href="{{ route('cases.show', $connected['case']) }}" class="min-w-0 truncate text-sm font-semibold text-[#16776e] hover:underline">
                        {{ $connected['case']->case_number ?: $connected['case']->case_title }}
                        <span class="font-normal text-slate-500">· {{ $connected['case']->court_name }}</span>
                    </a>
                    <form method="POST" action="{{ route('cases.links.destroy', [$case, $connected['link_id']]) }}" data-confirm="Unlink this connected matter?" class="print:hidden">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-xs text-red-700 hover:underline">Unlink</button>
                    </form>
                </li>
            @endforeach
        </ul>
    @else
        <p class="mt-2 text-sm text-slate-600">No connected matters yet. Use Court journey to link a case.</p>
    @endif
</section>