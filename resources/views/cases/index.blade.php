@extends('layouts.app')
@section('title', 'My Case Diary')
@section('page-heading', 'My cases')

@php
    $chipHints = [
        'today' => 'Cases with a hearing today',
        'tomorrow' => 'Cases with a hearing tomorrow',
        'next7' => 'Cases with a hearing from today to '.now()->addDays(6)->format('d M'),
        'past' => 'Every date has passed: check the case status and add the next date',
        'no_upcoming' => 'No hearing date recorded yet',
    ];
    $chipHints['next30'] = 'Cases with a hearing from today to '.now()->addDays(29)->format('d M');
    $activeHearing = $hearingFilter ?? 'all';
    $chipUrl = fn (string $key) => route('cases.index', array_filter(array_merge(request()->except('page', 'hearing_filter'), ['hearing_filter' => $key === 'all' ? null : $key])));
    $hasFilters = request()->filled('search') || request()->filled('case_status') || request()->filled('tag') || request()->filled('court') || $activeHearing !== 'all';
    $field = 'w-full rounded-xl border border-slate-300 bg-white px-3 text-sm focus:border-[#16776e] focus:ring-[#16776e]';
@endphp

@section('content')

<div class="mx-auto max-w-5xl space-y-3">

    @if(session('success'))
        <div role="status" class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">{{ session('success') }}</div>
    @endif

    {{-- Header: what this is, how many, and the two things people do here most. --}}
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-slate-900">My cases</h1>
            <p class="mt-0.5 text-sm text-slate-500">
                {{ $cases->total() }} {{ \Illuminate\Support\Str::plural('case', $cases->total()) }}{{ $hasFilters ? ' match your search' : ' in your diary' }}
            </p>
        </div>
        @php $menuItem = 'flex w-full items-center gap-2.5 px-4 py-2.5 text-left text-slate-700 hover:bg-slate-50'; @endphp
        <div class="flex items-center gap-2">
            <a href="{{ route('cases.find') }}" data-sheet="{{ route('sheets.add-case') }}" data-shortcut="n" aria-keyshortcuts="N"
               class="inline-flex min-h-10 items-center gap-1.5 rounded-xl bg-[#16776e] px-4 text-sm font-semibold text-white shadow-sm hover:bg-[#125f58]">
                <x-icon name="plus" :size="18" /> Add case
            </a>
            {{-- Everything else people do with the list sits behind one "⋯" button. --}}
            <details class="vn-profile-menu relative">
                <summary aria-label="More actions" title="More actions"
                         class="inline-flex min-h-10 min-w-10 cursor-pointer list-none items-center justify-center rounded-xl border border-slate-300 bg-white px-2.5 text-lg font-bold leading-none text-slate-700 hover:bg-slate-50">&middot;&middot;&middot;</summary>
                <div class="absolute right-0 z-30 mt-2 w-64 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 text-sm shadow-lg">
                    <a href="{{ route('cases.find') }}" class="{{ $menuItem }}"><x-icon name="download" :size="16" class="text-slate-400" /> Import from eCourts</a>
                    <a href="{{ route('cases.create') }}" class="{{ $menuItem }}"><x-icon name="search" :size="16" class="text-slate-400" /> Import one case by CNR</a>
                    @if($shareText)
                        {{-- Send exactly the cases shown: each with its next date, our side, court, room, judge and VC link. --}}
                        <div class="my-1 border-t border-slate-100"></div>
                        <a href="https://wa.me/?text={{ rawurlencode($shareText) }}" target="_blank" rel="noopener noreferrer" data-direct-share
                           data-share-text="{{ $shareText }}" data-share-title="Cases" class="{{ $menuItem }}">
                            <x-icon name="share" :size="16" class="text-slate-400" /> Share {{ min($cases->total(), 100) }} {{ \Illuminate\Support\Str::plural('case', min($cases->total(), 100)) }}
                        </a>
                        <button type="button" data-copy="{{ $shareText }}" class="{{ $menuItem }}">
                            <x-icon name="copy" :size="16" class="text-slate-400" /> <span data-copy-label>Copy this list</span>
                        </button>
                    @endif
                    <div class="my-1 border-t border-slate-100"></div>
                    <a href="{{ route('cases.export', request()->query()) }}" class="{{ $menuItem }}"><x-icon name="data" :size="16" class="text-slate-400" /> Export these cases (CSV)</a>
                    <a href="{{ route('cases.calendar') }}" class="{{ $menuItem }}"><x-icon name="calendar" :size="16" class="text-slate-400" /> Open the calendar</a>
                </div>
            </details>
        </div>
    </div>

    {{-- One search row (box, court, status), then one row of date chips. Lists apply as soon as one is picked. --}}
    <x-panel class="p-3 sm:p-4">
        <form method="GET" action="{{ route('cases.index') }}" role="search" class="space-y-3">
            @if($activeHearing !== 'all')
                <input type="hidden" name="hearing_filter" value="{{ $activeHearing }}">
            @endif
            @foreach(['list', 'arrange'] as $kept)
                @if(request()->filled($kept))
                    <input type="hidden" name="{{ $kept }}" value="{{ request($kept) }}">
                @endif
            @endforeach
            @if(request()->filled('search_type') && request('search_type') !== 'all')
                <input type="hidden" name="search_type" value="{{ request('search_type') }}">
            @endif
            <div class="flex flex-col gap-2 lg:flex-row">
                <label class="flex flex-1 items-center gap-2 rounded-xl border border-slate-300 bg-white pl-3 pr-1 focus-within:border-[#16776e] focus-within:ring-2 focus-within:ring-[#16776e]/15">
                    <x-icon name="search" :size="18" class="shrink-0 text-slate-400" />
                    <span class="sr-only">Search your cases</span>
                    <input id="search" type="search" name="search" value="{{ request('search') }}" maxlength="100"
                           placeholder="Party, case no., CNR, court or judge"
                           class="min-h-11 w-full border-0 bg-transparent px-0 text-sm focus:ring-0">
                    <button type="submit" class="inline-flex min-h-9 shrink-0 items-center rounded-lg bg-[#142235] px-3.5 text-sm font-semibold text-white hover:bg-[#20334a]">Search</button>
                </label>
                <div class="grid grid-cols-2 gap-2 sm:flex">
                    @if(count($yourCourts) > 1)
                        @php $activeCourt = strtoupper((string) request('court')); @endphp
                        <label class="sr-only" for="court">Court</label>
                        <select id="court" name="court" data-autosubmit class="{{ $field }} min-h-11 sm:w-48">
                            <option value="">All courts</option>
                            @foreach($yourCourts as $courtGroup)
                                <option value="{{ $courtGroup['key'] }}" @selected($activeCourt === $courtGroup['key'])>
                                    {{ \App\Services\CourtIdentity::label($courtGroup['level'], $courtGroup['state'], $courtGroup['district']) ?? $courtGroup['key'] }} ({{ $courtGroup['count'] }})
                                </option>
                            @endforeach
                        </select>
                    @endif
                    <label class="sr-only" for="case_status">Status</label>
                    <select id="case_status" name="case_status" data-autosubmit class="{{ $field }} min-h-11 sm:w-36">
                        <option value="">Any status</option>
                        @foreach(\App\Models\LegalCase::STATUSES as $status)
                            <option value="{{ $status }}" @selected(request('case_status') === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                    @if($tags->isNotEmpty())
                        <label class="sr-only" for="tag">Tag</label>
                        <select id="tag" name="tag" data-autosubmit class="{{ $field }} min-h-11 sm:w-36">
                            <option value="">Any tag</option>
                            @foreach($tags as $tagOption)
                                <option value="{{ $tagOption->id }}" @selected((string) request('tag') === (string) $tagOption->id)>{{ $tagOption->name }}</option>
                            @endforeach
                        </select>
                    @endif
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @if($list === 'disposed')
                    <span class="min-w-0 flex-1 text-sm text-slate-500">Disposed, withdrawn, transferred and closed cases.</span>
                @else
                <nav aria-label="Hearing date" class="-mx-1 flex min-w-0 flex-1 gap-1.5 overflow-x-auto px-1 pb-0.5 [scrollbar-width:none] sm:flex-wrap sm:overflow-visible">
                    @foreach($hearingChips as $chipKey => $chipLabel)
                        @php $chipCount = $hearingCounts[$chipKey] ?? 0; @endphp
                        <a href="{{ $chipUrl($chipKey) }}" @if($activeHearing === $chipKey) aria-current="page" @endif @isset($chipHints[$chipKey]) title="{{ $chipHints[$chipKey] }}" @endisset
                           class="inline-flex min-h-10 shrink-0 items-center gap-1.5 rounded-full px-3 text-sm font-semibold transition {{ $activeHearing === $chipKey ? ($chipKey === 'past' ? 'bg-red-600 text-white' : 'bg-[#16776e] text-white') : 'border border-slate-200 bg-white text-slate-700 hover:border-[#16776e]' }} {{ $chipCount === 0 && $activeHearing !== $chipKey ? 'opacity-60' : '' }}">
                            {{ $chipLabel }}
                            <span class="rounded-full px-1.5 text-xs tabular-nums {{ $activeHearing === $chipKey ? 'bg-white/20' : ($chipKey === 'past' && $chipCount ? 'bg-red-100 text-red-700' : 'bg-slate-100 text-slate-600') }}">{{ $chipCount }}</span>
                        </a>
                    @endforeach
                </nav>
                @endif
                @if($hasFilters)
                    <a href="{{ route('cases.index') }}" class="inline-flex min-h-10 shrink-0 items-center px-2 text-sm font-semibold text-[#16776e] hover:underline">Clear</a>
                @endif
            </div>
        </form>
    </x-panel>

    @if($list === 'active' && ! $hasFilters && ($hearingCounts['past'] > 0 || $hearingCounts['no_upcoming'] > 0))
        {{-- One slim line: the single most useful thing to do next. --}}
        @php
            [$nextStepUrl, $nextStepText] = $hearingCounts['past'] > 0
                ? [$chipUrl('past'), $hearingCounts['past'].' '.\Illuminate\Support\Str::plural('case', $hearingCounts['past']).' with a past date need the next date']
                : [$chipUrl('no_upcoming'), $hearingCounts['no_upcoming'].' '.\Illuminate\Support\Str::plural('case', $hearingCounts['no_upcoming']).' without a hearing date'];
        @endphp
        <a href="{{ $nextStepUrl }}" aria-label="Suggested next step: {{ $nextStepText }}"
           class="flex min-h-10 items-center gap-2 rounded-lg border-l-4 border-[#16776e] bg-teal-50 px-3 text-sm font-semibold text-teal-900 hover:bg-teal-100">
            <x-icon name="bell" :size="16" class="shrink-0" />
            <span class="min-w-0 flex-1 truncate">{{ $nextStepText }}</span>
            <span aria-hidden="true">&rarr;</span>
        </a>
    @endif

    @if($todayWithoutNextDate)
        {{-- Heard today and still without a next date: add it while the day is fresh. --}}
        <section aria-label="Today's matters without a next date" class="rounded-lg border-l-4 border-[#e6b84a] bg-amber-50 px-3 py-2 text-sm">
            <p class="font-semibold text-amber-900">{{ trans_choice(':count matter today without a next date|:count matters today without a next date', count($todayWithoutNextDate), ['count' => count($todayWithoutNextDate)]) }}</p>
            <ul class="mt-1 divide-y divide-amber-100">
                @foreach($todayWithoutNextDate as $matter)
                    <li class="flex items-center justify-between gap-2 py-1">
                        <a href="{{ $matter['url'] }}" data-sheet="{{ route('sheets.case-view', $matter['case_id']) }}" class="min-w-0 truncate text-slate-800 hover:underline">{{ $matter['title'] }}</a>
                        <a href="{{ route('hearings.create', ['case' => $matter['case_id']]) }}" data-sheet="{{ route('sheets.next-date', ['case' => $matter['case_id']]) }}" class="inline-flex min-h-8 shrink-0 items-center rounded-full bg-[#e6b84a] px-3 text-xs font-bold text-[#142235]">+ Date</a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- Running or closed cases, arranged by date or by court (district; High Court bench). --}}
    @php
        $keep = request()->except('page', 'list', 'arrange', 'hearing_filter');
        $listUrl = fn (string $key) => route('cases.index', array_filter(array_merge($keep, ['list' => $key === 'active' ? null : $key, 'arrange' => request('arrange')])));
        $arrangeUrl = fn (string $key) => route('cases.index', array_filter(array_merge(request()->except('page', 'arrange'), ['arrange' => $key === 'date' ? null : $key])));
        $segment = 'inline-flex min-h-10 items-center gap-1.5 px-3.5 text-sm font-semibold';
    @endphp
    <div class="flex flex-wrap items-center justify-between gap-2">
        <nav aria-label="Active or disposed" class="inline-flex overflow-hidden rounded-xl border border-slate-200 bg-white">
            @foreach(['active' => 'Active', 'disposed' => 'Disposed'] as $listKey => $listLabel)
                <a href="{{ $listUrl($listKey) }}" @if($list === $listKey) aria-current="page" @endif
                   class="{{ $segment }} {{ $loop->first ? '' : 'border-l border-slate-200' }} {{ $list === $listKey ? 'bg-[#142235] text-white' : 'text-slate-700 hover:bg-slate-50' }}">
                    {{ $listLabel }} <span class="rounded-full px-1.5 text-xs tabular-nums {{ $list === $listKey ? 'bg-white/20' : 'bg-slate-100 text-slate-600' }}">{{ $listCounts[$listKey] }}</span>
                </a>
            @endforeach
        </nav>
        <nav aria-label="Arrange cases" class="inline-flex items-center gap-2 text-sm text-slate-500">
            <span class="hidden sm:inline">Arrange</span>
            <span class="inline-flex overflow-hidden rounded-xl border border-slate-200 bg-white">
                @foreach(['date' => 'By date', 'court' => 'By court'] as $arrangeKey => $arrangeLabel)
                    <a href="{{ $arrangeUrl($arrangeKey) }}" @if($arrangeBy === $arrangeKey) aria-current="page" @endif
                       class="{{ $segment }} {{ $loop->first ? '' : 'border-l border-slate-200' }} {{ $arrangeBy === $arrangeKey ? 'bg-[#16776e] text-white' : 'text-slate-700 hover:bg-slate-50' }}">{{ $arrangeLabel }}</a>
                @endforeach
            </span>
        </nav>
    </div>

    {{-- The cases. --}}
    <ul class="space-y-2.5" aria-label="Cases">
        @forelse($cases as $case)
            @if(isset($groupHeadings[$case->id]))
                @php $groupLabel = $groupHeadings[$case->id]['label']; @endphp
                <li class="flex items-center justify-between gap-3 px-1 pt-2 first:pt-0">
                    <h2 class="text-sm font-bold text-slate-900">{{ $groupLabel }} <span class="font-semibold tabular-nums text-slate-500">· {{ $groupHeadings[$case->id]['count'] }} {{ \Illuminate\Support\Str::plural('case', $groupHeadings[$case->id]['count']) }}</span></h2>
                    {{-- Shares this group's cases on the page at once (their cards carry data-day-item). --}}
                    <x-day-share :heading="'Cases · '.$groupLabel" :group="$groupLabel" />
                </li>
            @endif
            @php
                $today = now()->toDateString();
                // Every date on the case's hearings: a hearing's own date (with its time) and the next date fixed at it.
                $caseDates = $case->hearings->flatMap(fn ($hearing) => collect([
                    $hearing->hearing_date ? ['date' => \Carbon\Carbon::parse($hearing->hearing_date), 'hearing' => $hearing, 'time' => $hearing->hearing_time] : null,
                    $hearing->next_hearing_date ? ['date' => \Carbon\Carbon::parse($hearing->next_hearing_date), 'hearing' => $hearing, 'time' => null] : null,
                ])->filter());
                foreach ($case->causeListAppearances as $appearance) {
                    if ($appearance->courtDocument?->listing_date) {
                        $caseDates->push(['date' => $appearance->courtDocument->listing_date, 'hearing' => null, 'time' => null]);
                    }
                }
                $filterStart = $activeHearing === 'tomorrow' ? now()->addDay()->toDateString() : $today;
                $upcomingDate = $caseDates
                    ->filter(fn ($entry) => $entry['date']->toDateString() >= $filterStart)
                    ->sortBy(fn ($entry) => $entry['date']->toDateString().' '.($entry['time'] ?? '99'))
                    ->first();
                $pastDate = $upcomingDate ? null : $caseDates->sortByDesc(fn ($entry) => $entry['date']->toDateString())->first();
                // A closed case (disposed, withdrawn…) shows its date of disposal, not hearing dates.
                $isClosed = $case->case_status && ! in_array($case->case_status, \App\Services\CaseListArrangement::ACTIVE_STATUSES, true);
                if ($isClosed) {
                    $upcomingDate = $pastDate = null;
                }
                $nextHearing = $upcomingDate['hearing'] ?? null;
                $tileDate = $isClosed ? $case->disposed_on : ($upcomingDate['date'] ?? $pastDate['date'] ?? null);
                $tileClass = match (true) {
                    $isClosed => 'bg-slate-100 text-slate-700',
                    $upcomingDate && $tileDate->isToday() => 'bg-[#16776e] text-white',
                    $upcomingDate && $tileDate->isTomorrow() => 'bg-amber-100 text-amber-900',
                    (bool) $upcomingDate => 'bg-teal-50 text-teal-900',
                    (bool) $pastDate => 'bg-red-50 text-red-800 ring-1 ring-inset ring-red-200',
                    default => 'bg-slate-100 text-slate-500',
                };
                // Running = teal (the case is live), closed = grey (done), per the app's colour meanings.
                $statusClass = $isClosed
                    ? 'bg-slate-100 text-slate-600 ring-slate-300'
                    : 'bg-teal-50 text-teal-800 ring-teal-200';
                $cardEdge = $isClosed ? 'border-l-slate-300' : 'border-l-[#16776e]';
            @endphp
            @php
                $rowListing = $upcomingDate ? $case->causeListAppearances->first(fn ($listing) => $listing->courtDocument?->listing_date?->isSameDay($tileDate)) : null;
                $rowCauseList = $rowListing?->stripData();
                $ownDate = $upcomingDate && $nextHearing?->hearing_date?->isSameDay($tileDate);
                $rowRoom = $rowListing?->court_room ?: ($nextHearing?->court_room ?: $case->court_room);
                $rowJudge = $rowCauseList['judges'] ?? ($nextHearing?->judge_name ?: $case->judge_name);
                $rowVc = $rowListing?->vcLink() ?: ($nextHearing?->vc_link ?: $case->vc_link);
                $hasListDetails = $rowCauseList || ($ownDate && ($nextHearing->item_number || $nextHearing->list_total)) || isset($orders[$case->id]);
                $caseShareItem = [
                    'title' => $case->case_title, 'case_number' => $case->case_number, 'side' => $case->appearing_for, 'parties' => $case->appearing_for_parties,
                    'date' => $upcomingDate ? $tileDate->toDateString() : null, 'time' => $upcomingDate['time'] ?? null,
                    'court' => $case->court_name, 'room' => $rowRoom, 'judge' => $rowJudge, 'vc_link' => $rowVc, 'details' => $nextHearing?->hearing_stage,
                    'item' => $rowCauseList['item'] ?? ($ownDate ? $nextHearing->item_number : null),
                    'list_total' => $rowCauseList['place']['total'] ?? ($rowCauseList['total'] ?? ($ownDate ? $nextHearing->list_total : null)),
                    'supplementary' => $rowCauseList['place']['supplementary'] ?? null,
                ];
                $caseShareText = \App\Services\HearingShareText::single($caseShareItem);
                $iconButton = 'inline-flex h-10 w-10 items-center justify-center rounded-lg sm:h-9 sm:w-9 text-slate-500 transition hover:bg-slate-100 hover:text-slate-900';
            @endphp
            <li data-day-item="{{ $groupLabel ?? '' }}" data-day-text="{{ \App\Services\HearingShareText::entry($caseShareItem) }}" data-card-href="{{ route('cases.show', $case) }}" class="group cursor-pointer rounded-xl border border-l-4 border-slate-200 {{ $cardEdge }} bg-white px-3.5 py-3.5 shadow-sm transition hover:border-teal-300 sm:px-5 sm:py-4">
                <div class="flex gap-3">
                    {{-- Next hearing (or date of disposal) at a glance. --}}
                    <a href="{{ route('cases.show', $case) }}" class="flex w-16 shrink-0 flex-col items-center justify-center self-start rounded-xl px-1 py-2.5 text-center {{ $tileClass }}"
                       title="{{ $isClosed ? ($tileDate ? 'Disposed on '.$tileDate->format('d M Y') : 'Date of disposal not recorded') : ($upcomingDate ? 'Next hearing' : ($pastDate ? 'Last date has passed: check the case status' : 'No hearing date recorded')) }}">
                        @if($isClosed && ! $tileDate)
                            <x-icon name="check" :size="18" />
                            <span class="mt-0.5 text-[11px] font-semibold">{{ ucfirst($case->case_status) }}</span>
                        @elseif($tileDate)
                            <span class="text-[11px] font-bold uppercase leading-none">{{ $upcomingDate && $tileDate->isToday() ? 'Today' : ($upcomingDate && $tileDate->isTomorrow() ? 'Tmrw' : $tileDate->format('M')) }}</span>
                            <span class="text-xl font-extrabold leading-tight">{{ $tileDate->format('d') }}</span>
                            <span class="text-[11px] font-semibold leading-none">{{ $isClosed ? $tileDate->format('Y') : ($upcomingDate ? ($upcomingDate['time'] ? substr((string) $upcomingDate['time'], 0, 5) : $tileDate->format('Y')) : 'Review') }}</span>
                        @else
                            <x-icon name="calendar" :size="18" />
                            <span class="mt-0.5 text-[11px] font-semibold">No date</span>
                        @endif
                    </a>

                    <div class="grid min-w-0 flex-1 grid-cols-[minmax(0,1fr)_auto] gap-x-2">
                        <div class="col-span-2 flex items-start justify-between gap-2">
                            <div class="flex min-w-0 flex-wrap items-center gap-1.5">
                                <a href="{{ route('cases.show', $case) }}" data-sheet="{{ route('sheets.case-view', $case) }}" class="min-w-0 break-words text-base font-semibold leading-snug text-slate-900 hover:text-[#16776e]">{{ $case->case_title ?: 'Untitled case' }}</a>
                                <x-case-side :side="$case->appearing_for" :parties="$case->appearing_for_parties" :edit-url="route('cases.edit', $case)" />
                            </div>
                            <span class="shrink-0 rounded-full px-2 py-0.5 text-xs font-semibold ring-1 ring-inset {{ $statusClass }}">{{ ucfirst($case->case_status ?: 'unknown') }}</span>
                        </div>

                        <div class="col-span-2 min-w-0 sm:col-span-1">
                        <p class="mt-1 flex flex-wrap items-center gap-x-1.5 gap-y-1 text-[13px] text-slate-500">
                            <x-court-badge :level="$case->court_level" :state="$case->state_code" :district="$case->district" />
                            {{ collect([
                                $case->case_number,
                                $case->cnr_number ? 'CNR '.$case->cnr_number : null,
                                $case->file_number ? 'File '.$case->file_number : null,
                                $case->parties->count() ? $case->parties->count().' '.\Illuminate\Support\Str::plural('party', $case->parties->count()) : null,
                                $case->hearings_count ? $case->hearings_count.' '.\Illuminate\Support\Str::plural('hearing', $case->hearings_count) : null,
                            ])->filter()->implode(' · ') ?: 'No case number yet' }}
                        </p>

                        {{-- The date that matters, in words: the next hearing (with its stage), the date that passed, or the disposal. --}}
                        <p class="mt-1.5 text-sm text-slate-700">
                            @if($isClosed)
                                <span class="font-semibold">{{ ucfirst($case->case_status) }}</span>{{ $tileDate ? ' on '.$tileDate->format('l, d M Y') : ' · date of disposal not recorded' }}
                            @elseif($upcomingDate)
                                <span class="font-semibold text-slate-900">Next hearing</span> {{ $tileDate->format('l, d M Y') }}{{ $upcomingDate['time'] ? ' · '.substr((string) $upcomingDate['time'], 0, 5) : '' }}{{ $nextHearing?->hearing_stage ? ' · '.$nextHearing->hearing_stage : '' }}
                            @elseif($pastDate)
                                <span class="font-semibold text-red-700">Last date {{ $tileDate->format('d M Y') }} has passed</span> · add the next date
                            @else
                                <span class="text-slate-500">No hearing date recorded yet</span>
                            @endif
                        </p>

                        <x-court-info class="mt-1.5" :live="false" :court="$case->court_name" :designation="$case->court_name" :complex="$case->court_complex"
                                      :room="$upcomingDate ? $rowRoom : $case->court_room" :judge="$upcomingDate ? $rowJudge : $case->judge_name" :vc-link="$upcomingDate ? $rowVc : $case->vc_link" />
                        @if($upcomingDate && $hasListDetails)
                            <x-hearing-strip :show-date="false" :show-room="false" :date="$tileDate->toDateString()" :court="$case->court_name" :room="$rowRoom"
                                             :item="$ownDate ? $nextHearing->item_number : null" :list-total="$ownDate ? $nextHearing->list_total : null"
                                             :cause-list="$rowCauseList" :case-number="$case->case_number"
                                             :district="$case->court_level === 'district'"
                                             :order-url="isset($orders[$case->id]) ? route('cases.documents.download', [$case, $orders[$case->id], 'preview' => 1]) : null"
                                             :order-date="($orders[$case->id] ?? null)?->created_at?->format('d M Y')" />
                        @endif

                        @if($case->tags->isNotEmpty())
                            <p class="mt-1.5 flex flex-wrap gap-1.5">
                                @foreach($case->tags as $caseTag)
                                    <a href="{{ route('cases.index', ['tag' => $caseTag->id]) }}" class="rounded-full bg-[#e8f4f2] px-2 py-0.5 text-xs font-medium text-[#16776e] hover:bg-[#d9efeb]">{{ $caseTag->name }}</a>
                                @endforeach
                            </p>
                        @endif
                        </div>

                        <div class="col-span-2 mt-1 flex justify-end sm:col-span-1 sm:mt-0 sm:self-end">
                            <span class="-mr-1.5 flex items-center transition-opacity [@media(hover:hover)]:opacity-40 [@media(hover:hover)]:group-hover:opacity-100 [@media(hover:hover)]:group-focus-within:opacity-100" role="group" aria-label="Actions for {{ $case->case_title ?: 'this case' }}">
                                @unless($upcomingDate || $isClosed)
                                    {{-- No date ahead (passed or never set): add it without leaving the list. --}}
                                    <a href="{{ route('hearings.create', ['case' => $case->id]) }}" data-sheet="{{ route('sheets.next-date', ['case' => $case->id]) }}"
                                       title="Add next date" aria-label="Add next date" class="{{ $iconButton }} text-[#16776e] hover:bg-teal-50 hover:text-[#16776e]"><x-icon name="calendar" :size="18" /></a>
                                @endunless
                                <details class="vn-profile-menu relative">
                                    <summary title="Tags" aria-label="Tags" class="{{ $iconButton }} cursor-pointer list-none"><x-icon name="tag" :size="18" /></summary>
                                    <div class="absolute right-0 z-30 mt-1 w-64 rounded-xl border border-slate-200 bg-white p-3 text-sm shadow-xl">
                                        <form method="POST" action="{{ route('cases.tags.attach', $case) }}" class="flex gap-1.5">
                                            @csrf
                                            <label class="sr-only" for="tag-{{ $case->id }}">New tag</label>
                                            <input id="tag-{{ $case->id }}" name="name" list="vn-case-tags" maxlength="50" required placeholder="Add a tag" class="{{ $field }} min-h-10">
                                            <button class="min-h-10 shrink-0 rounded-xl bg-[#16776e] px-3 text-sm font-semibold text-white hover:bg-[#125f58]">Add</button>
                                        </form>
                                        @foreach($case->tags as $caseTag)
                                            <form method="POST" action="{{ route('cases.tags.detach', [$case, $caseTag]) }}" class="mt-2 flex items-center justify-between gap-2">
                                                @csrf
                                                @method('DELETE')
                                                <span class="truncate text-slate-700">{{ $caseTag->name }}</span>
                                                <button class="text-xs font-semibold text-red-700 hover:underline" aria-label="Remove tag {{ $caseTag->name }}">Remove</button>
                                            </form>
                                        @endforeach
                                    </div>
                                </details>
                                <a href="{{ route('cases.download', $case) }}" title="Download case (PDF)" aria-label="Download case as PDF" class="{{ $iconButton }}"><x-icon name="download" :size="18" /></a>
                                <a href="https://wa.me/?text={{ rawurlencode($caseShareText) }}" target="_blank" rel="noopener noreferrer" data-direct-share
                                   data-share-text="{{ $caseShareText }}" data-share-title="{{ $case->case_title ?: 'Case' }}"
                                   title="Share" aria-label="Share case details" class="{{ $iconButton }}"><x-icon name="share" :size="18" /></a>
                                <a href="{{ route('cases.edit', $case) }}" title="Edit" aria-label="Edit case" class="{{ $iconButton }}"><x-icon name="form" :size="18" /></a>
                                <form method="POST" action="{{ route('cases.destroy', $case) }}" data-confirm="Delete this case from your diary?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" title="Delete" aria-label="Delete case" class="{{ $iconButton }} hover:bg-red-50 hover:text-red-700"><x-icon name="trash" :size="18" /></button>
                                </form>
                            </span>
                        </div>
                    </div>
                </div>
            </li>
        @empty
            <li class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center">
                @if($hasFilters)
                    <p class="font-semibold text-slate-800">No case matches your search.</p>
                    <p class="mt-1 text-sm text-slate-500">Try another word, or <a href="{{ route('cases.index') }}" class="font-semibold text-[#16776e] hover:underline">clear the filters</a>.</p>
                @else
                    <p class="font-semibold text-slate-800">Your case diary is empty.</p>
                    <p class="mt-1 text-sm text-slate-500">
                        <a href="{{ route('cases.find') }}" class="font-semibold text-[#16776e] hover:underline">Import your cases from eCourts</a>
                        or <a href="{{ route('cases.manual') }}" data-sheet="{{ route('sheets.add-case') }}" class="font-semibold text-[#16776e] hover:underline">add one yourself</a>.
                    </p>
                @endif
            </li>
        @endforelse
    </ul>
    <datalist id="vn-case-tags">@foreach($tags as $tagOption)<option value="{{ $tagOption->name }}"></option>@endforeach</datalist>

    @if($cases->hasPages())
        <div>{{ $cases->links() }}</div>
    @endif
</div>
@endsection
