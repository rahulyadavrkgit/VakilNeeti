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

@include('cases.partials.management-nav')
<div class="mx-auto max-w-6xl space-y-4">

    @if(session('success'))
        <div role="status" class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">{{ session('success') }}</div>
    @endif

    {{-- Header: what this is, how many, and the two things people do here most. --}}
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">My cases</h1>
            <p class="mt-0.5 text-sm text-slate-500">
                {{ $cases->total() }} {{ \Illuminate\Support\Str::plural('case', $cases->total()) }}{{ $hasFilters ? ' match your search' : ' in your diary' }}
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('cases.find') }}" class="inline-flex min-h-11 items-center gap-1.5 rounded-xl border border-[#16776e] bg-white px-4 text-sm font-semibold text-[#16776e] hover:bg-teal-50">
                <x-icon name="download" :size="18" /> Import from eCourts
            </a>
            <a href="{{ route('cases.manual') }}" class="inline-flex min-h-11 items-center gap-1.5 rounded-xl bg-[#16776e] px-4 text-sm font-semibold text-white shadow-sm hover:bg-[#125f58]">
                <x-icon name="plus" :size="18" /> Add case
            </a>
            <details class="vn-profile-menu relative">
                <summary class="inline-flex min-h-11 cursor-pointer list-none items-center gap-1 rounded-xl border border-slate-300 bg-white px-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">More <x-icon name="chevron" :size="16" /></summary>
                <div class="absolute right-0 z-30 mt-2 w-56 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 text-sm shadow-lg">
                    <a href="{{ route('cases.create') }}" class="block px-4 py-2.5 text-slate-700 hover:bg-slate-50">Import one case by CNR</a>
                    <a href="{{ route('cases.export', request()->query()) }}" class="block px-4 py-2.5 text-slate-700 hover:bg-slate-50">Export these cases (CSV)</a>
                    <a href="{{ route('cases.calendar') }}" class="block px-4 py-2.5 text-slate-700 hover:bg-slate-50">Open the calendar</a>
                </div>
            </details>
        </div>
    </div>

    {{-- One search box, where to look, and one-tap filters. --}}
    <x-panel>
        <form method="GET" action="{{ route('cases.index') }}" role="search" class="space-y-3">
            @if($activeHearing !== 'all')
                <input type="hidden" name="hearing_filter" value="{{ $activeHearing }}">
            @endif
            <div class="flex flex-col gap-2 sm:flex-row">
                <label class="flex flex-1 items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 focus-within:border-[#16776e] focus-within:ring-2 focus-within:ring-[#16776e]/15">
                    <x-icon name="search" :size="18" class="shrink-0 text-slate-400" />
                    <span class="sr-only">Search your cases</span>
                    <input id="search" type="search" name="search" value="{{ request('search') }}" maxlength="100"
                           placeholder="Title, party, case no., CNR, court, judge or room"
                           class="min-h-11 w-full border-0 bg-transparent px-0 text-sm focus:ring-0">
                </label>
                <div class="flex gap-2">
                    <label class="sr-only" for="search_type">Search in</label>
                    <select id="search_type" name="search_type" class="{{ $field }} min-h-11 sm:w-40">
                        @foreach(\App\Models\LegalCase::SEARCH_FIELDS as $searchKey => $searchLabel)
                            <option value="{{ $searchKey }}" @selected(request('search_type', 'all') === $searchKey)>{{ $searchKey === 'all' ? 'In everything' : $searchLabel }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="min-h-11 shrink-0 rounded-xl bg-[#142235] px-5 text-sm font-semibold text-white hover:bg-[#20334a]">Search</button>
                </div>
            </div>

            <nav aria-label="Hearing date" class="flex flex-wrap gap-1.5">
                @foreach($hearingChips as $chipKey => $chipLabel)
                    @php $chipCount = $hearingCounts[$chipKey] ?? 0; @endphp
                    <a href="{{ $chipUrl($chipKey) }}" @if($activeHearing === $chipKey) aria-current="page" @endif @isset($chipHints[$chipKey]) title="{{ $chipHints[$chipKey] }}" @endisset
                       class="inline-flex min-h-11 shrink-0 items-center gap-1.5 rounded-full px-3.5 text-sm font-semibold transition {{ $activeHearing === $chipKey ? ($chipKey === 'past' ? 'bg-red-600 text-white' : 'bg-[#16776e] text-white') : 'border border-slate-200 bg-white text-slate-700 hover:border-[#16776e]' }} {{ $chipCount === 0 && $activeHearing !== $chipKey ? 'opacity-60' : '' }}">
                        {{ $chipLabel }}
                        <span class="rounded-full px-1.5 text-xs {{ $activeHearing === $chipKey ? 'bg-white/20' : ($chipKey === 'past' && $chipCount ? 'bg-red-100 text-red-700' : 'bg-slate-100 text-slate-600') }}">{{ $chipCount }}</span>
                    </a>
                @endforeach
            </nav>

            @if(count($yourCourts) > 1)
                {{-- One tap to the cases of one court: Delhi HC, Jalaun, Noida… --}}
                @php $activeCourt = strtoupper((string) request('court')); @endphp
                <nav aria-label="Court" class="-mx-1 flex gap-1.5 overflow-x-auto px-1 pb-0.5">
                    <a href="{{ route('cases.index', array_filter(request()->except('page', 'court'))) }}" @if($activeCourt === '') aria-current="page" @endif
                       class="inline-flex min-h-11 shrink-0 items-center rounded-full px-3.5 text-sm font-semibold {{ $activeCourt === '' ? 'bg-[#142235] text-white' : 'border border-slate-200 bg-white text-slate-700 hover:border-[#16776e]' }}">All courts</a>
                    @foreach($yourCourts as $courtGroup)
                        @php $isActiveCourt = $activeCourt === $courtGroup['key']; @endphp
                        <a href="{{ route('cases.index', array_filter(array_merge(request()->except('page'), ['court' => $isActiveCourt ? null : $courtGroup['key']]))) }}" @if($isActiveCourt) aria-current="page" @endif
                           title="{{ \App\Services\CourtIdentity::stateName($courtGroup['state']) }}"
                           class="inline-flex min-h-11 shrink-0 items-center gap-1.5 rounded-full px-3.5 text-sm font-semibold {{ $isActiveCourt ? 'bg-[#142235] text-white' : 'border border-slate-200 bg-white text-slate-700 hover:border-[#16776e]' }}">
                            {{ \App\Services\CourtIdentity::label($courtGroup['level'], $courtGroup['state'], $courtGroup['district']) ?? $courtGroup['key'] }}
                            <span class="rounded-full px-1.5 text-xs {{ $isActiveCourt ? 'bg-white/20' : 'bg-slate-100 text-slate-600' }}">{{ $courtGroup['count'] }}</span>
                        </a>
                    @endforeach
                </nav>
            @endif

            <div class="flex flex-wrap items-center gap-2">
                <label class="sr-only" for="case_status">Status</label>
                <select id="case_status" name="case_status" data-autosubmit class="{{ $field }} min-h-10 w-auto">
                    <option value="">Any status</option>
                    @foreach(\App\Models\LegalCase::STATUSES as $status)
                        <option value="{{ $status }}" @selected(request('case_status') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
                @if($tags->isNotEmpty())
                    <label class="sr-only" for="tag">Tag</label>
                    <select id="tag" name="tag" data-autosubmit class="{{ $field }} min-h-10 w-auto">
                        <option value="">Any tag</option>
                        @foreach($tags as $tagOption)
                            <option value="{{ $tagOption->id }}" @selected((string) request('tag') === (string) $tagOption->id)>{{ $tagOption->name }}</option>
                        @endforeach
                    </select>
                @endif
                <span class="ml-auto flex flex-wrap items-center gap-2">
                    @if($hasFilters)
                        <a href="{{ route('cases.index') }}" class="text-sm font-semibold text-[#16776e] hover:underline">Clear all</a>
                    @endif
                    @if($shareText)
                        {{-- Send exactly the cases shown: each with its next date, our side, court, room, judge and VC link. --}}
                        <button type="button" data-copy="{{ $shareText }}" title="Copy this list" aria-label="Copy this list of {{ $cases->total() }} cases"
                                class="inline-flex min-h-10 items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 text-sm font-semibold text-slate-700 hover:border-[#16776e] hover:text-[#16776e]">
                            <x-icon name="copy" :size="16" /><span data-copy-label>Copy list</span>
                        </button>
                        <a href="https://wa.me/?text={{ rawurlencode($shareText) }}" target="_blank" rel="noopener noreferrer" data-direct-share
                           data-share-text="{{ $shareText }}" data-share-title="Cases"
                           class="inline-flex min-h-10 items-center gap-1.5 rounded-lg bg-[#16776e] px-3 text-sm font-semibold text-white hover:bg-[#125f58]">
                            <x-icon name="share" :size="16" /> Share {{ min($cases->total(), 100) }} {{ \Illuminate\Support\Str::plural('case', min($cases->total(), 100)) }}
                        </a>
                    @endif
                </span>
            </div>
        </form>
    </x-panel>

    @if(! $hasFilters && ($hearingCounts['past'] > 0 || $hearingCounts['no_upcoming'] > 0))
        <aside aria-label="Suggested next step" class="flex flex-wrap items-center gap-x-3 gap-y-1 rounded-xl border border-teal-200 bg-teal-50 p-3 text-sm">
            <span class="font-semibold text-slate-900">Suggested next step</span>
            @if($hearingCounts['past'] > 0)
                <a href="{{ $chipUrl('past') }}" class="inline-flex min-h-11 items-center font-semibold text-teal-900 underline underline-offset-2">Review cases with past dates ({{ $hearingCounts['past'] }})</a>
            @else
                <a href="{{ $chipUrl('no_upcoming') }}" class="inline-flex min-h-11 items-center font-semibold text-teal-900 underline underline-offset-2">Check cases without a date ({{ $hearingCounts['no_upcoming'] }})</a>
            @endif
        </aside>
    @endif

    {{-- The cases. --}}
    <ul class="space-y-3" aria-label="Cases">
        @forelse($cases as $case)
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
                $nextHearing = $upcomingDate['hearing'] ?? null;
                $tileDate = $upcomingDate['date'] ?? $pastDate['date'] ?? null;
                $tileClass = match (true) {
                    $upcomingDate && $tileDate->isToday() => 'bg-[#16776e] text-white',
                    $upcomingDate && $tileDate->isTomorrow() => 'bg-amber-100 text-amber-900',
                    (bool) $upcomingDate => 'bg-teal-50 text-teal-900',
                    (bool) $pastDate => 'bg-red-50 text-red-800 ring-1 ring-inset ring-red-200',
                    default => 'bg-slate-100 text-slate-500',
                };
                $statusClass = match ($case->case_status) {
                    'pending' => 'bg-amber-50 text-amber-800 ring-amber-200',
                    'disposed', 'closed' => 'bg-rose-50 text-rose-700 ring-rose-200',
                    default => 'bg-slate-100 text-slate-700 ring-slate-200',
                };
            @endphp
            <li class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-teal-300 hover:shadow">
                <div class="flex gap-3 sm:gap-4">
                    {{-- Next hearing at a glance. --}}
                    <a href="{{ route('cases.show', $case) }}" class="flex w-16 shrink-0 flex-col items-center justify-center rounded-xl px-1 py-2 text-center {{ $tileClass }}"
                       title="{{ $upcomingDate ? 'Next hearing' : ($pastDate ? 'Last date has passed: check the case status' : 'No hearing date recorded') }}">
                        @if($tileDate)
                            <span class="text-xs font-bold uppercase">{{ $upcomingDate && $tileDate->isToday() ? 'Today' : ($upcomingDate && $tileDate->isTomorrow() ? 'Tmrw' : $tileDate->format('M')) }}</span>
                            <span class="text-xl font-extrabold leading-tight">{{ $tileDate->format('d') }}</span>
                            <span class="text-[11px] font-semibold">{{ $upcomingDate ? ($upcomingDate['time'] ? substr((string) $upcomingDate['time'], 0, 5) : $tileDate->format('Y')) : 'Review' }}</span>
                        @else
                            <x-icon name="calendar" :size="20" />
                            <span class="mt-0.5 text-[11px] font-semibold">No date</span>
                        @endif
                    </a>

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-start justify-between gap-x-3 gap-y-1">
                            <div class="flex min-w-0 flex-wrap items-center gap-1.5">
                                <a href="{{ route('cases.show', $case) }}" data-sheet="{{ route('sheets.case-view', $case) }}" class="min-w-0 break-words text-base font-semibold leading-snug text-slate-900 hover:text-[#16776e]">{{ $case->case_title ?: 'Untitled case' }}</a>
                                <x-case-side :side="$case->appearing_for" :parties="$case->appearing_for_parties" :edit-url="route('cases.edit', $case)" />
                            </div>
                            <span class="shrink-0 rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset {{ $statusClass }}">{{ ucfirst($case->case_status ?: 'unknown') }}</span>
                        </div>

                        <p class="mt-1 flex flex-wrap items-center gap-x-1.5 gap-y-1 text-xs text-slate-500">
                            <x-court-badge :level="$case->court_level" :state="$case->state_code" :district="$case->district" />
                            {{ collect([
                                $case->case_number,
                                $case->cnr_number ? 'CNR '.$case->cnr_number : null,
                                $case->file_number ? 'File '.$case->file_number : null,
                                $case->parties->count() ? $case->parties->count().' '.\Illuminate\Support\Str::plural('party', $case->parties->count()) : null,
                                $case->hearings_count ? $case->hearings_count.' '.\Illuminate\Support\Str::plural('hearing', $case->hearings_count) : null,
                            ])->filter()->implode(' · ') ?: 'No case number yet' }}
                        </p>

                        <x-court-info class="mt-2" :court="$case->court_name" :designation="$case->court_name" :complex="$case->court_complex"
                                      :room="$nextHearing?->court_room ?: $case->court_room" :judge="$nextHearing?->judge_name ?: $case->judge_name" :vc-link="$nextHearing?->vc_link ?: $case->vc_link"
                                      :share="['title' => $case->case_title, 'case_number' => $case->case_number, 'side' => $case->appearing_for, 'parties' => $case->appearing_for_parties, 'date' => $upcomingDate ? $tileDate->toDateString() : null, 'time' => $upcomingDate['time'] ?? null, 'details' => $nextHearing?->hearing_stage]" />

                        <div class="mt-2.5 flex flex-wrap items-center gap-x-3 gap-y-2">
                            @foreach($case->tags as $caseTag)
                                <a href="{{ route('cases.index', ['tag' => $caseTag->id]) }}" class="rounded-full bg-[#e8f4f2] px-2 py-0.5 text-xs font-medium text-[#16776e] hover:bg-[#d9efeb]">{{ $caseTag->name }}</a>
                            @endforeach
                            <span class="ml-auto flex items-center gap-1">
                                <a href="{{ route('cases.show', $case) }}" class="inline-flex min-h-9 items-center rounded-lg px-3 text-sm font-semibold text-[#16776e] hover:bg-teal-50">Open</a>
                                <a href="{{ route('cases.edit', $case) }}" class="inline-flex min-h-9 items-center rounded-lg px-3 text-sm font-semibold text-slate-600 hover:bg-slate-100">Edit</a>
                                <form method="POST" action="{{ route('cases.destroy', $case) }}" data-confirm="Delete this case from your diary?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex min-h-9 items-center rounded-lg px-3 text-sm font-semibold text-red-700 hover:bg-red-50">Delete</button>
                                </form>
                            </span>
                        </div>
                        @unless($upcomingDate)
                            {{-- No date ahead (passed or never set): add it here without leaving the list. --}}
                            <x-quick-next-date class="mt-2" :case-id="$case->id" />
                        @endunless
                    </div>
                </div>
            </li>
        @empty
            <li class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center">
                @if($hasFilters)
                    <p class="font-semibold text-slate-800">No case matches your search.</p>
                    <p class="mt-1 text-sm text-slate-500">Try another word, look "In everything", or <a href="{{ route('cases.index') }}" class="font-semibold text-[#16776e] hover:underline">clear the filters</a>.</p>
                @else
                    <p class="font-semibold text-slate-800">Your case diary is empty.</p>
                    <p class="mt-1 text-sm text-slate-500">
                        <a href="{{ route('cases.find') }}" class="font-semibold text-[#16776e] hover:underline">Import your cases from eCourts</a>
                        or <a href="{{ route('cases.manual') }}" class="font-semibold text-[#16776e] hover:underline">add one yourself</a>.
                    </p>
                @endif
            </li>
        @endforelse
    </ul>

    @if($cases->hasPages())
        <div>{{ $cases->links() }}</div>
    @endif
</div>
@endsection
