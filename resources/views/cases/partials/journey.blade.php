{{-- The same matter across courts (District → High Court → Supreme Court) and connected matters. --}}
@php
    $steps = [
        ...array_map(fn ($step) => $step + ['current' => false], $journey['lower']),
        ['link_id' => null, 'case' => $case, 'current' => true],
        ...array_map(fn ($step) => $step + ['current' => false], $journey['higher']),
    ];
    $steps = collect($steps)->sortBy(fn (array $step): int => match ($step['case']->court_level) {
        'supreme' => 0,
        'high' => 1,
        'district' => 2,
        default => 3,
    })->values()->all();
    $levelTone = fn (string $level) => match ($level) {
        'Supreme Court' => 'bg-[#142235] text-white',
        'High Court' => 'bg-[#16776e] text-white',
        default => 'bg-slate-200 text-slate-800',
    };
    // Where the matter is now: the highest court in the chain that is still running.
    $nowIndex = collect($steps)->keys()->filter(fn ($index) => ! $steps[$index]['case']->closed_at && ! in_array($steps[$index]['case']->case_status, ['disposed', 'closed', 'withdrawn', 'transferred'], true))->first();
@endphp

<section id="case-journey" data-tab-panel role="tabpanel" aria-labelledby="tab-case-journey" class="scroll-mt-32 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="flex flex-wrap items-baseline justify-between gap-2">
        <h3 class="text-lg font-semibold text-slate-900">Case journey</h3>
        @if($case->matter_id)
            <span class="text-xs font-semibold text-slate-500" title="One number for this matter in every court it reaches">Matter <span class="font-bold tabular-nums text-slate-800">{{ $case->matter_id }}</span></span>
        @endif
    </div>

    @if(count($steps) > 1)
        <ol class="mt-4 grid gap-2 md:grid-flow-col md:auto-cols-fr">
            @foreach($steps as $step)
                @php $stepCase = $step['case']; @endphp
                <li class="relative min-w-0 rounded-xl border p-3 {{ $step['current'] ? 'border-[#16776e] bg-teal-50/60 ring-1 ring-[#16776e]' : 'border-slate-200' }}">
                    <div class="flex items-center justify-between gap-2">
                        <span class="flex min-w-0 items-center gap-1.5">
                            @if($stepCase->courtLabel())
                                <x-court-badge :level="$stepCase->court_level" :state="$stepCase->state_code" :district="$stepCase->district" />
                            @else
                                <span class="rounded-full px-2 py-0.5 text-xs font-bold {{ $levelTone($stepCase->courtLevel()) }}">{{ $stepCase->courtLevel() }}</span>
                            @endif
                            @if(count($steps) > 1 && $loop->index === $nowIndex)
                                <span class="shrink-0 rounded-full border border-[#16776e] px-2 py-0.5 text-xs font-bold text-[#16776e]">Now here</span>
                            @endif
                        </span>
                        @if($step['link_id'])
                            <form method="POST" action="{{ route('cases.links.destroy', [$case, $step['link_id']]) }}" data-confirm="Unlink this case? Both cases stay saved." class="print:hidden">
                                @csrf
                                @method('DELETE')
                                <button type="submit" aria-label="Unlink {{ $stepCase->case_title }}" class="rounded px-1.5 text-slate-400 hover:bg-red-50 hover:text-red-700">&times;</button>
                            </form>
                        @else
                            <span class="text-xs font-bold uppercase text-[#16776e]">This case</span>
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
