{{-- The same matter across courts (District → High Court → Supreme Court) and connected matters. --}}
@php
    $steps = [
        ...array_map(fn ($step) => $step + ['current' => false], $journey['lower']),
        ['link_id' => null, 'case' => $case, 'current' => true],
        ...array_map(fn ($step) => $step + ['current' => false], $journey['higher']),
    ];
    $levelTone = fn (string $level) => match ($level) {
        'Supreme Court' => 'bg-[#142235] text-white',
        'High Court' => 'bg-[#16776e] text-white',
        default => 'bg-slate-200 text-slate-800',
    };
@endphp

<section id="case-journey" class="scroll-mt-32 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
    <h3 class="text-lg font-semibold text-slate-900">Case journey</h3>

    @if(count($steps) > 1)
        <ol class="mt-4 grid gap-2 md:grid-flow-col md:auto-cols-fr">
            @foreach($steps as $step)
                @php $stepCase = $step['case']; @endphp
                <li class="relative min-w-0 rounded-xl border p-3 {{ $step['current'] ? 'border-[#16776e] bg-teal-50/60 ring-1 ring-[#16776e]' : 'border-slate-200' }}">
                    <div class="flex items-center justify-between gap-2">
                        <span class="rounded-full px-2 py-0.5 text-[11px] font-bold {{ $levelTone($stepCase->courtLevel()) }}">{{ $stepCase->courtLevel() }}</span>
                        @if($step['link_id'])
                            <form method="POST" action="{{ route('cases.links.destroy', [$case, $step['link_id']]) }}" data-confirm="Unlink this case? Both cases stay saved." class="print:hidden">
                                @csrf
                                @method('DELETE')
                                <button type="submit" aria-label="Unlink {{ $stepCase->case_title }}" class="rounded px-1.5 text-slate-400 hover:bg-red-50 hover:text-red-700">&times;</button>
                            </form>
                        @else
                            <span class="text-[11px] font-bold uppercase text-[#16776e]">This case</span>
                        @endif
                    </div>
                    @if($step['current'])
                        <p class="mt-1.5 truncate text-sm font-semibold text-slate-900">{{ $stepCase->case_number ?: $stepCase->case_title }}</p>
                    @else
                        <a href="{{ route('cases.show', $stepCase) }}" class="mt-1.5 block truncate text-sm font-semibold text-[#16776e] hover:underline">{{ $stepCase->case_number ?: $stepCase->case_title }}</a>
                    @endif
                    <p class="truncate text-xs text-slate-500">{{ $stepCase->court_name ?: 'Court not added' }}</p>
                    <p class="mt-1 text-xs font-semibold text-slate-600">{{ ucfirst($stepCase->case_status ?: 'unknown') }}@if($stepCase->appearingForLabel()) · For {{ $stepCase->appearingForLabel() }}@endif</p>
                </li>
            @endforeach
        </ol>
    @endif

    @if($journey['connected'])
        <p class="mt-4 text-xs font-bold uppercase tracking-wide text-slate-500">Connected matters</p>
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
    @endif

    @if($errors->has('linked_case_id') || $errors->has('link_type'))
        <p role="alert" class="mt-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">{{ $errors->first('linked_case_id') ?: $errors->first('link_type') }}</p>
    @endif

    @if($linkableCases->isNotEmpty())
        <form method="POST" action="{{ route('cases.links.store', $case) }}" class="mt-4 grid gap-2 border-t border-slate-100 pt-4 sm:grid-cols-[minmax(0,2fr)_minmax(0,1.4fr)_auto] print:hidden">
            @csrf
            <label>
                <span class="sr-only">Case to link</span>
                <select name="linked_case_id" required class="min-h-11 w-full rounded-lg border-slate-300 bg-white text-sm">
                    <option value="">Link one of your cases…</option>
                    @foreach($linkableCases as $linkable)
                        <option value="{{ $linkable->id }}">{{ \Illuminate\Support\Str::limit($linkable->case_title, 48) }}{{ $linkable->case_number ? ' · '.$linkable->case_number : '' }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span class="sr-only">How it is related</span>
                <select name="link_type" required class="min-h-11 w-full rounded-lg border-slate-300 bg-white text-sm">
                    @foreach(\App\Http\Controllers\CaseLinkController::CHOICES as $choiceValue => $choiceLabel)
                        <option value="{{ $choiceValue }}">{{ $choiceLabel }}</option>
                    @endforeach
                </select>
            </label>
            <button type="submit" class="min-h-11 rounded-lg bg-[#16776e] px-4 text-sm font-semibold text-white hover:bg-[#125f58]">Link</button>
        </form>
    @endif
    <a href="{{ route('cases.manual') }}" class="mt-2 inline-block text-xs font-semibold text-[#16776e] hover:underline print:hidden">+ Add the other court's case first</a>
</section>
