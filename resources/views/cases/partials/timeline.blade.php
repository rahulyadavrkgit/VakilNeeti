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

    $listingOn = $case->causeListAppearances->filter(fn ($listing) => $listing->courtDocument?->listing_date)
        ->sortBy(fn ($listing) => [$listing->listPriority(), $listing->id])
        ->keyBy(fn ($listing) => $listing->courtDocument->listing_date->toDateString());
    $savedDays = $entries->pluck('date')->map(fn ($date) => $date->toDateString());
    foreach ($listingOn as $day => $listing) {
        if (!$savedDays->contains($day)) {
            $entries->push(['date' => $listing->courtDocument->listing_date, 'kind' => 'listed', 'listing' => $listing]);
        }
    }

    foreach ($case->documents as $document) {
        $entries->push(['date' => $document->created_at, 'kind' => 'document', 'document' => $document]);
    }

    $days = $entries
        ->unique(fn (array $entry): string => $entry['kind'] === 'next' ? 'next:'.$entry['date']->toDateString()
            : $entry['kind'].':'.(data_get($entry, 'hearing.id') ?? data_get($entry, 'listing.id') ?? data_get($entry, 'document.id')))
        ->groupBy(fn (array $entry) => $entry['date']->toDateString())
        ->sortKeysDesc();
    if ($panelNext) {
        $nextDay = $panelNext['date']->toDateString();
        $nextDayEntries = $days->pull($nextDay, collect())->reject(fn (array $entry): bool => match ($entry['kind']) {
            'hearing' => $entry['hearing']->id === $datedHearing?->id,
            'next' => true,
            'listed' => $panelListing !== null,
            default => false,
        });
        $days->prepend($nextDayEntries->prepend(['kind' => 'upcoming']), $nextDay);
    }

    $card = 'rounded-lg border border-slate-200 bg-white p-3';
@endphp

<section id="case-history" data-tab-panel role="tabpanel" aria-labelledby="tab-case-history" class="scroll-mt-24 rounded-xl border border-slate-200 bg-slate-50 p-3 shadow-sm sm:p-4">
    <h3 class="text-lg font-semibold text-slate-900">Timeline</h3>

    @if(! $panelNext && $panelLast)
        <p class="mt-2 text-sm text-slate-600">No upcoming hearing. Last date {{ $panelLast['date']->format('d M Y') }}.</p>
    @endif

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
    @elseif($days->isNotEmpty())
        <ol class="mt-3 divide-y divide-slate-200">
            @foreach($days as $day => $dayEntries)
                @php
                    $date = \Illuminate\Support\Carbon::parse($day);
                    $isFuture = $date->isAfter(today());
                    $isNextDay = $panelNext && $date->isSameDay($panelNext['date']);
                @endphp
                <li class="grid grid-cols-[3.75rem_minmax(0,1fr)] gap-2 py-2 first:pt-0 last:pb-0 sm:grid-cols-[6rem_minmax(0,1fr)] sm:gap-3">
                    <time datetime="{{ $day }}" aria-label="{{ $date->format('l, d F Y') }}{{ $isNextDay && $panelTime ? ', '.$panelTime : '' }}" class="self-start rounded-lg px-1.5 py-2 text-center tabular-nums {{ $isNextDay ? 'bg-teal-100 text-[#16776e]' : 'bg-slate-100 text-slate-700' }}">
                        <span class="block text-xs font-medium sm:hidden">{{ $date->format('D') }}</span>
                        <span class="hidden text-xs font-medium sm:block">{{ $date->format('l') }}</span>
                        <span class="block text-sm font-bold">{{ $date->format('d M') }}</span>
                        <span class="block text-xs">{{ $date->format('Y') }}</span>
                    </time>

                    <div class="min-w-0 space-y-2">
                        @foreach($dayEntries as $entry)
                            <div>
                            @if($entry['kind'] === 'upcoming')
                                <article aria-label="Next hearing" class="{{ $card }} border-teal-200">
                                    <div class="flex flex-wrap items-baseline justify-between gap-1">
                                        <h4 class="font-semibold text-[#16776e]">{{ $date->isToday() ? 'Next hearing · Today' : 'Next hearing' }}</h4>
                                        @if($panelTime)<span class="text-xs font-medium text-slate-600">{{ $panelTime }}</span>@endif
                                    </div>
                                    @if($purpose)<p class="mt-1 text-sm text-slate-700">Purpose: {{ $purpose }}</p>@endif
                                    <div class="mt-1.5">
                                        <x-court-info :live="false" :court="$case->court_name" :designation="$case->court_name" :complex="$case->court_complex" :room="$courtRoom" :judge="$judge" :vc-link="$vcLink" :copy-vc="true" :judge-photos="true" :photo-district="$photoDistrict" />
                                    </div>
                                    <div @if($panelListing) id="cause-list" @endif class="scroll-mt-36">
                                        <x-hearing-strip :date="$panelNext['date']" :show-date="false" :show-room="false" :waiting="false" :court="$case->court_name" :room="$courtRoom"
                                            :item="$item" :list-total="$listTotal" :cause-list="$causeList" :district="$case->court_level === 'district'"
                                            :case-number="$case->case_number"
                                            :order-url="$latestOrder ? route('cases.documents.download', [$case, $latestOrder, 'preview' => 1]) : null"
                                            :order-date="$latestOrder?->created_at?->format('d M Y')" />
                                    </div>
                                    @if($datedHearing?->proceedings)<p class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ $datedHearing->proceedings }}</p>@endif
                                    @if($datedHearing?->next_hearing_date)
                                        <p class="mt-1 text-sm text-slate-600">Next date fixed: <strong>{{ $datedHearing->next_hearing_date->format('d M Y') }}</strong>{{ $datedHearing->next_action ? ' · '.$datedHearing->next_action : '' }}</p>
                                    @elseif($nextHearing?->next_action && $nextHearing->next_action !== $purpose)
                                        <p class="mt-1 text-sm text-slate-600">{{ $nextHearing->next_action }}</p>
                                    @endif
                                    @if($nextHearing?->isManual())<a href="{{ route('hearings.edit', $nextHearing) }}" class="mt-1 inline-flex text-sm font-semibold text-[#16776e] hover:underline">Edit hearing</a>@endif

                                    @php $listedOn = $causeListings->first()?->courtDocument?->listing_date; @endphp
                                    @if($savedDate && $listedOn && ! $savedDate['date']->isSameDay($listedOn))
                                        <p class="mt-2 rounded-lg bg-amber-50 p-2 text-sm text-amber-900">These dates differ. Saved date {{ $savedDate['date']->format('d M Y') }}. Cause list {{ $listedOn->format('d M Y') }}. Check the latest court listing before changing your saved date.</p>
                                    @endif
                                </article>
                            @elseif($entry['kind'] === 'hearing' || $entry['kind'] === 'next')
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
                                    @if($room || $listing?->item_number || (! $isNext && $hearing->item_number) || $hearing->hearing_time)
                                        <p class="mt-1.5 flex flex-wrap gap-x-4 gap-y-1 text-sm font-semibold text-slate-800">
                                            @if($room)<span>Court room: {{ $room }}</span>@endif
                                            @if($listing?->item_number)<span>Item No: {{ $listing->item_number }}</span>
                                            @elseif(! $isNext && $hearing->item_number)<span>Item No: {{ $hearing->item_number }}@if($hearing->list_total) of {{ $hearing->list_total }}@endif</span>@endif
                                            @if($listing?->boardFacts())<span class="font-normal text-slate-600">{{ implode(' · ', $listing->boardFacts()) }}</span>@endif
                                            @if(! $isNext && $hearing->hearing_time)<span>{{ \Illuminate\Support\Carbon::parse($hearing->hearing_time)->format('g:i a') }}</span>@endif
                                        </p>
                                    @endif
                                    @if($judges)
                                        <p class="mt-1 flex flex-wrap items-center gap-1 text-sm text-slate-600"><x-judge-portraits :names="$judges" :district="$photoDistrict" /> <span>Judge(s): {{ $judges }}</span></p>
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
                                        <a href="{{ route('hearings.create', ['case' => $case->id, 'return' => 'case']) }}" data-sheet="{{ route('sheets.next-date', ['case' => $case->id]) }}" class="mt-2 inline-flex text-sm font-semibold text-[#16776e] hover:underline">Record what happened and the next date</a>
                                    @endif
                                </article>
                            @elseif($entry['kind'] === 'listed')
                                @php $listing = $entry['listing']; @endphp
                                <article class="{{ $card }} border-teal-200 bg-teal-50/50">
                                    <p class="font-semibold text-[#16776e]">Listed in the cause list</p>
                                    {{-- One line: where and which item. The judge is with the hearing above. --}}
                                    <p class="mt-1 text-sm text-slate-700"><strong class="text-slate-900">@if($listing->court_room)Court {{ $listing->court_room }} · @endif Item {{ $listing->item_number }}</strong>@if($listing->boardFacts()) · {{ implode(' · ', $listing->boardFacts()) }}@endif</p>
                                    {{-- The court, item and date are above; the list itself is one PDF link. --}}
                                    <p class="mt-1.5 flex flex-wrap gap-3 text-sm">
                                        @if($listing->vcLink())<a href="{{ $listing->vcLink() }}" target="_blank" rel="noopener" class="font-semibold text-[#16776e] hover:underline">Join VC</a>@endif
                                        <a href="{{ $listing->courtDocument->source_url }}" target="_blank" rel="noopener" title="{{ $listing->courtDocument->document_label }}" class="vn-file-link">Cause list</a>
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
                            </div>
                        @endforeach
                    </div>
                </li>
            @endforeach
        </ol>
    @endif
</section>
