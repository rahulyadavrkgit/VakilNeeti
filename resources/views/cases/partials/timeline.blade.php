{{--
    The case's life on one line, newest first: upcoming dates, hearings (room, item, judges, what happened),
    the court's cause-list listings and documents, each under its date.
--}}
@php
    $hearingDays = $case->hearings->filter(fn ($hearing) => $hearing->hearing_date)->map(fn ($hearing) => $hearing->hearing_date->toDateString());
    $entries = collect();
    $undated = $case->hearings->filter(fn ($hearing) => ! $hearing->hearing_date && ! $hearing->next_hearing_date);

    foreach ($case->hearings as $hearing) {
        if ($hearing->hearing_date) {
            $entries->push(['date' => $hearing->hearing_date, 'kind' => 'hearing', 'hearing' => $hearing]);
        }

        // A next date fixed at a hearing, unless a hearing was already recorded for that day.
        if ($hearing->next_hearing_date && ! $hearingDays->contains($hearing->next_hearing_date->toDateString())) {
            $entries->push(['date' => $hearing->next_hearing_date, 'kind' => 'next', 'hearing' => $hearing]);
        }
    }

    foreach ($case->causeListAppearances as $listing) {
        if ($listing->courtDocument?->listing_date) {
            $entries->push(['date' => $listing->courtDocument->listing_date, 'kind' => 'listed', 'listing' => $listing]);
        }
    }

    foreach ($case->documents as $document) {
        $entries->push(['date' => $document->created_at, 'kind' => 'document', 'document' => $document]);
    }

    $days = $entries
        ->groupBy(fn (array $entry) => $entry['date']->toDateString())
        ->sortKeysDesc();
    // The item number and bench from the cause list, for the hearing on the same day.
    $listingOn = $case->causeListAppearances->keyBy(fn ($listing) => $listing->courtDocument?->listing_date?->toDateString());
    $card = 'rounded-xl border border-slate-200 bg-white p-3.5 shadow-sm';
@endphp

<section id="case-history" data-tab-panel role="tabpanel" aria-labelledby="tab-case-history" class="scroll-mt-24 rounded-xl border border-slate-200 bg-slate-50 p-3 shadow-sm sm:p-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h3 class="text-lg font-semibold text-slate-900">Timeline</h3>
        <a href="{{ route('hearings.create', ['case' => $case->id, 'return' => 'case']) }}" data-sheet="{{ route('sheets.next-date', ['case' => $case->id]) }}"
           class="inline-flex min-h-11 items-center gap-1.5 rounded-lg bg-[#16776e] px-3 text-sm font-semibold text-white hover:bg-[#125f58]">
            <x-icon name="plus" :size="16" /> Add date
        </a>
    </div>

    @if($undated->isNotEmpty())
        <div class="mt-3 space-y-2">
            @foreach($undated as $hearing)
                <article class="{{ $card }}">
                    <h4 class="font-semibold">Hearing date not available</h4>
                    <p class="text-sm">{{ $hearing->dateSourceLabel() }} @if($hearing->judge_name || $hearing->court_room)&middot; {{ $hearing->judge_name ?: $hearing->court_room }}@endif</p>
                    @if($hearing->proceedings)<p class="whitespace-pre-line text-sm">{{ $hearing->proceedings }}</p>@endif
                    @if($hearing->next_action)<p class="text-sm"><strong>Next action</strong> {{ $hearing->next_action }}</p>@endif
                    @if($hearing->isManual())<a class="inline-flex min-h-11 items-center text-sm underline" href="{{ route('hearings.edit', $hearing) }}">Edit hearing</a>@endif
                </article>
            @endforeach
        </div>
    @endif
    @if($days->isEmpty() && $undated->isEmpty())
        <p class="mt-4 rounded-lg bg-slate-50 p-5 text-center text-sm text-slate-500">No hearings or documents yet. Add the next date to start the timeline.</p>
    @else
        <ol class="relative mt-5 space-y-5 border-l-2 border-slate-200 pl-5 sm:ml-2">
            @foreach($days as $day => $dayEntries)
                @php
                    $date = \Illuminate\Support\Carbon::parse($day);
                    $isFuture = $date->isAfter(today());
                @endphp
                <li class="relative">
                    <span aria-hidden="true" class="absolute -left-[27px] top-1.5 h-3 w-3 rounded-full border-2 border-white {{ $date->isToday() ? 'bg-red-600' : ($isFuture ? 'bg-[#16776e]' : 'bg-[#142235]') }}"></span>
                    <p class="inline-flex rounded-full px-3 py-1 text-sm font-bold {{ $date->isToday() ? 'bg-red-600 text-white' : 'bg-[#142235] text-white' }}">
                        {{ $date->isToday() ? 'Today · ' : '' }}{{ $date->format("d M 'y") }}
                    </p>

                    <div class="mt-2 space-y-2">
                        @foreach($dayEntries as $entry)
                            @if($entry['kind'] === 'hearing' || $entry['kind'] === 'next')
                                @php
                                    $hearing = $entry['hearing'];
                                    $isNext = $entry['kind'] === 'next';
                                    $listing = $listingOn[$day] ?? null;
                                    $judges = $isNext ? null : ($hearing->judge_name ?: ($listing?->judge_name ? \Illuminate\Support\Str::title(mb_strtolower($listing->judge_name)) : null));
                                    $room = $isNext ? ($listing?->court_room ? 'Court '.$listing->court_room : null) : ($hearing->court_room ?: ($listing?->court_room ? 'Court '.$listing->court_room : null));
                                @endphp
                                <article class="{{ $card }}">
                                    <div class="flex flex-wrap items-start justify-between gap-2">
                                        <p class="font-semibold {{ $isFuture ? 'text-[#b7791f]' : 'text-slate-900' }}">
                                            {{ $isFuture ? ($isNext ? 'Next date' : 'Upcoming hearing') : ($isNext ? 'Date (outcome not recorded)' : 'Hearing') }}
                                        </p>
                                        <span class="flex items-center gap-3 text-xs text-slate-500">
                                            @if(! $isNext && $hearing->hearing_stage)
                                                <span class="font-semibold text-slate-700">{{ $hearing->hearing_stage }}</span>
                                            @endif
                                            <span>{{ $hearing->dateSourceLabel() }}</span>
                                            @if($hearing->isManual())
                                                <a href="{{ route('hearings.edit', $hearing) }}" class="font-semibold text-[#16776e] hover:underline">Edit</a>
                                            @endif
                                        </span>
                                    </div>
                                    @if($room || $listing?->item_number || $hearing->hearing_time)
                                        <p class="mt-1.5 flex flex-wrap gap-x-4 gap-y-1 text-sm font-semibold text-slate-800">
                                            @if($room)<span>Court room: {{ $room }}</span>@endif
                                            @if($listing?->item_number)<span>Item No: {{ $listing->item_number }}</span>@endif
                                            @if(! $isNext && $hearing->hearing_time)<span>{{ \Illuminate\Support\Carbon::parse($hearing->hearing_time)->format('g:i a') }}</span>@endif
                                        </p>
                                    @endif
                                    @if($judges)
                                        <p class="mt-1 text-sm text-slate-600">Judge(s): {{ $judges }}</p>
                                    @endif
                                    @if(! $isNext && $hearing->vc_link)
                                        <a href="{{ $hearing->vc_link }}" target="_blank" rel="noopener noreferrer" data-direct-share class="mt-1.5 inline-flex text-sm font-semibold text-[#16776e] hover:underline">Join VC for this hearing</a>
                                    @endif
                                    @if(! $isNext && $hearing->proceedings)
                                        <p class="mt-2 whitespace-pre-line border-t border-slate-100 pt-2 text-sm text-slate-700">{{ $hearing->proceedings }}</p>
                                    @endif
                                    @if(! $isNext && $hearing->next_hearing_date)
                                        <p class="mt-1.5 text-sm text-slate-600">Next date fixed: <strong class="text-slate-900">{{ $hearing->next_hearing_date->format('d M Y') }}</strong>{{ $hearing->next_action ? ' · '.$hearing->next_action : '' }}</p>
                                    @elseif($hearing->next_action)
                                        <p class="mt-1 text-sm text-slate-600">{{ $hearing->next_action }}</p>
                                    @endif
                                    @if(! $isFuture && $isNext)
                                        <a href="{{ route('hearings.create', ['case' => $case->id, 'return' => 'case']) }}" class="mt-2 inline-flex text-sm font-semibold text-[#16776e] hover:underline">Record what happened and the next date</a>
                                    @endif
                                </article>
                            @elseif($entry['kind'] === 'listed')
                                @php $listing = $entry['listing']; @endphp
                                <article class="{{ $card }} border-teal-200 bg-teal-50/50">
                                    <p class="font-semibold text-[#16776e]">Listed in the cause list</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-800">@if($listing->court_room)Court {{ $listing->court_room }} · @endif Item {{ $listing->item_number }}</p>
                                    @if($listing->judge_name)
                                        <p class="mt-0.5 text-sm text-slate-600">{{ \Illuminate\Support\Str::title(mb_strtolower($listing->judge_name)) }}</p>
                                    @endif
                                    <p class="mt-1.5 flex flex-wrap gap-3 text-sm">
                                        @if($listing->vcLink())<a href="{{ $listing->vcLink() }}" target="_blank" rel="noopener" class="font-semibold text-[#16776e] hover:underline">Join VC</a>@endif
                                        <a href="{{ $listing->courtDocument->source_url }}" target="_blank" rel="noopener" class="font-semibold text-[#16776e] hover:underline">{{ $listing->courtDocument->document_label ?: 'Cause list' }} (PDF)</a>
                                    </p>
                                </article>
                            @else
                                @php $document = $entry['document']; @endphp
                                <article class="{{ $card }} flex items-center gap-3">
                                    <x-icon name="pdf" :size="22" class="text-red-600" />
                                    <span class="min-w-0 flex-1 truncate font-semibold text-slate-900">{{ $document->original_name }}</span>
                                    <a href="{{ route('cases.documents.download', [$case, $document]) }}" class="shrink-0 text-sm font-semibold text-[#16776e] hover:underline">Open</a>
                                </article>
                            @endif
                        @endforeach
                    </div>
                </li>
            @endforeach
        </ol>
    @endif
</section>
