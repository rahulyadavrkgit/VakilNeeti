@extends('layouts.app')

@section('title', __('Home'))
@section('page-heading', __('Home'))

@section('content')
<div class="mx-auto max-w-[1500px] space-y-6">
    {{-- One tap to the things an advocate does every day. --}}
    @php
        $quickActions = auth()->check()
            ? [
                [__('Today'), 'calendar', route('cases.calendar', ['view' => 'day'])],
                [__('+ Next date'), 'plus', route('hearings.create'), route('sheets.next-date')],
                [__('My cases'), 'briefcase', route('cases.index')],
                [__('Live boards'), 'board', route('courts.display-boards')],
            ]
            : [
                [__('Supreme Court'), 'pillars', route('courts.supreme-court')],
                [__('Delhi HC'), 'building', route('courts.delhi-high-court')],
                [__('District courts'), 'gavel', route('courts.delhi-district-courts')],
                [__('Display boards'), 'board', route('courts.display-boards')],
                [__('Court fee'), 'calculator', route('court-fee.index')],
                [__('Bare Acts'), 'book', route('bare-acts.index')],
            ];
    @endphp
    @guest
        @include('landing')
    @endguest
    <header class="rounded-3xl bg-[#142235] p-4 text-white shadow-lg sm:p-5">
        @auth
            <h1 class="text-xl font-semibold tracking-tight sm:text-2xl">{{ \Illuminate\Support\Str::of(auth()->user()->name)->before(' ') }}, {{ now()->translatedFormat('l, d M') }}</h1>
        @else
            <h2 class="text-xl font-semibold tracking-tight sm:text-2xl">{{ __('Free court tools · no sign-in needed') }}</h2>
        @endauth
        @if($today ?? null)
            {{-- The day at a glance. Red always means today; amber means something needs doing. --}}
            <div class="mt-3 flex flex-wrap gap-2 text-sm">
                <a href="{{ route('cases.calendar', ['view' => 'day']) }}" class="inline-flex min-h-9 items-center gap-1.5 rounded-full px-3 font-semibold {{ $today['hearings'] ? 'bg-red-600 text-white' : 'bg-white/10 text-white/80' }}">
                    <span class="tabular-nums">{{ $today['hearings'] }}</span> {{ __('today') }}
                </a>
                <a href="{{ route('cases.calendar', ['view' => 'day', 'date' => today()->addDay()->toDateString()]) }}" class="inline-flex min-h-9 items-center gap-1.5 rounded-full bg-white/10 px-3 font-semibold text-white hover:bg-white/15">
                    <span class="tabular-nums">{{ $today['tomorrow'] }}</span> {{ __('tomorrow') }}
                </a>
                <a href="{{ route('tasks.index') }}" class="inline-flex min-h-9 items-center gap-1.5 rounded-full px-3 font-semibold {{ $today['tasks'] ? 'bg-[#e6b84a] text-[#142235]' : 'bg-white/10 text-white/80' }}">
                    <span class="tabular-nums">{{ $today['tasks'] }}</span> {{ trans_choice('task due|tasks due', $today['tasks']) }}
                </a>
            </div>
            @if($today['listed']->isNotEmpty())
                {{-- Read from the Delhi High Court's published cause lists: where each matter is, and its item number. --}}
                <div class="mt-2 rounded-xl bg-white/[0.06] px-3 py-2 text-sm" data-cause-list-today>
                    <p class="font-semibold text-white">{{ __('In the courts\' cause lists') }}</p>
                    <ul class="mt-1 space-y-1.5">
                        @foreach($today['listed']->take(5) as $listing)
                            @php $listedOn = $listing->courtDocument->listing_date; @endphp
                            <li class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                <span class="rounded-full px-2 py-0.5 text-xs font-bold {{ $listedOn->isToday() ? 'bg-red-600 text-white' : 'bg-white/10 text-white/80' }}">{{ $listedOn->isToday() ? __('Today') : __('Tomorrow') }}</span>
                                <span class="font-semibold tabular-nums text-white">{{ $listing->courtShortName() }} · @if($listing->court_room){{ __('Court') }} {{ $listing->court_room }} · @endif {{ __('Item') }} {{ $listing->item_number }}</span>
                                @if($listing->boardFacts())<span class="text-xs text-white/80">{{ implode(' · ', $listing->boardFacts()) }}</span>@endif
                                <a href="{{ route('cases.show', $listing->legal_case_id) }}#cause-list" class="order-last min-w-0 basis-full truncate text-white/80 sm:order-none sm:basis-0 sm:flex-1 hover:text-white hover:underline">{{ $listing->legalCase?->case_title ?: $listing->case_reference }}</a>
                                @if($listing->vcLink())
                                    <a href="{{ $listing->vcLink() }}" target="_blank" rel="noopener" class="ml-auto inline-flex min-h-8 shrink-0 items-center gap-1 rounded-full bg-[#16776e] px-3 text-xs font-bold text-white hover:bg-[#12635c] sm:ml-0"><x-icon name="video" :size="14" /> {{ __('Join VC') }}</a>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @if($today['cleared'])
                <p class="mt-2 inline-flex items-center gap-2 rounded-xl bg-emerald-500/15 px-3 py-2 text-sm font-semibold text-[#8ed2c8]">
                    <x-icon name="check" :size="16" /> {{ __('Day cleared · every matter today has its next date') }}
                </p>
            @elseif($today['needs_next_date'] !== [])
                <div class="mt-2 rounded-xl bg-white/[0.06] px-3 py-2 text-sm">
                    <p class="font-semibold text-[#e6b84a]">{{ trans_choice(':count matter today without a next date|:count matters today without a next date', count($today['needs_next_date']), ['count' => count($today['needs_next_date'])]) }}</p>
                    <ul class="mt-1 space-y-0.5">
                        @foreach(array_slice($today['needs_next_date'], 0, 3) as $matter)
                            <li class="flex items-center justify-between gap-2">
                                <a href="{{ $matter['url'] }}" data-sheet="{{ route('sheets.case-view', $matter['case_id']) }}" class="min-w-0 truncate text-white/80 hover:text-white hover:underline">{{ $matter['title'] }}</a>
                                <a href="{{ route('hearings.create', ['case' => $matter['case_id']]) }}" data-sheet="{{ route('sheets.next-date', ['case' => $matter['case_id']]) }}" class="inline-flex min-h-8 shrink-0 items-center rounded-full bg-[#e6b84a] px-3 text-xs font-bold text-[#142235]">{{ __('+ Date') }}</a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        @endif
        @if($nextHearing ?? null)
            @php $nextDate = \Illuminate\Support\Carbon::parse($nextHearing['date']); $nextDetails = \App\Services\HearingShareText::fromEvent($nextHearing); @endphp
            <section aria-labelledby="next-hearing-heading" class="mt-3 rounded-2xl bg-white p-3.5 text-slate-900 shadow-sm sm:p-4">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <p id="next-hearing-heading" class="text-xs font-bold uppercase tracking-wide {{ $nextDate->isToday() ? 'text-red-600' : 'text-[#16776e]' }}">
                        {{ __('Next hearing') }} · {{ $nextDate->translatedFormat('l, d F Y') }}@if($nextHearing['time']) · {{ $nextHearing['time'] }}@endif
                    </p>
                    <a href="{{ $nextHearing['url'] }}" class="text-xs font-semibold text-[#16776e] hover:underline">{{ __('Open case') }} &rarr;</a>
                </div>
                <p class="mt-1 flex flex-wrap items-center gap-1.5 font-semibold leading-snug">
                    <a href="{{ $nextHearing['url'] }}" class="hover:text-[#16776e]">{{ $nextHearing['title'] }}</a>
                    <x-case-side :side="$nextHearing['appearing_for'] ?? null" :parties="$nextHearing['appearing_for_parties'] ?? null" :edit-url="$nextHearing['case_id'] ? route('cases.edit', $nextHearing['case_id']) : null" />
                </p>
                @if($nextHearing['case_number'] || $nextHearing['details'])
                    <p class="mt-0.5 flex min-w-0 items-center gap-1.5 text-xs text-slate-500"><x-court-badge :level="$nextHearing['court_level'] ?? null" :state="$nextHearing['state_code'] ?? null" :district="$nextHearing['district'] ?? null" class="shrink-0" /><span class="truncate">{{ collect([$nextHearing['case_number'], $nextHearing['details']])->filter()->implode(' · ') }}</span></p>
                @endif
                <x-court-info class="mt-2" :live="false" :court="$nextHearing['court']" :complex="$nextHearing['court_complex'] ?? null" :room="$nextDetails['room']" :judge="$nextDetails['judge']" :vc-link="$nextDetails['vc_link']"
                              :share="$nextDetails" />
                <x-hearing-strip :show-room="false" :show-date="false" :date="$nextHearing['date']" :court="$nextHearing['court']" :room="$nextDetails['room']" :item="$nextHearing['item_number'] ?? null" :list-total="$nextHearing['list_total'] ?? null" :cause-list="$nextHearing['cause_list'] ?? null" :case-number="$nextHearing['case_number']" :district="($nextHearing['court_level'] ?? null) === 'district'" :order-url="!empty($nextHearing['order']) ? route('cases.documents.download', [$nextHearing['case_id'], $nextHearing['order'], 'preview' => 1]) : null" :order-date="($nextHearing['order'] ?? null)?->created_at?->format('d M Y')" />
            </section>
        @endif
        <nav aria-label="{{ __('Quick actions') }}" class="mt-3 grid gap-2 {{ auth()->check() ? 'grid-cols-4' : 'grid-cols-3 sm:grid-cols-6' }}">
            @foreach($quickActions as $action)
                @php [$actionLabel, $actionIcon, $actionUrl] = $action; @endphp
                <a href="{{ $actionUrl }}" @isset($action[3]) data-sheet="{{ $action[3] }}" @endisset class="flex min-h-[72px] flex-col items-center justify-center gap-1.5 rounded-2xl bg-white/[0.07] px-1 text-center text-xs font-semibold text-white ring-1 ring-white/10 transition hover:bg-white/15 sm:text-sm">
                    <x-icon :name="$actionIcon" :size="22" class="text-[#e6b84a]" />
                    {{ $actionLabel }}
                </a>
            @endforeach
        </nav>
    </header>

    @auth
        @if($profile ?? null)
            <section class="flex flex-wrap items-center gap-4 rounded-2xl border border-teal-200 bg-teal-50/70 p-4 sm:p-5" aria-label="{{ __('Profile completion') }}">
                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-white text-sm font-bold text-[#16776e] ring-4 ring-teal-100">{{ $profile['percent'] }}%</div>
                {{-- At least 12rem wide, so on a phone the buttons move to their own row instead of squeezing the text. --}}
                <div class="min-w-[12rem] flex-1">
                    <p class="font-semibold text-slate-900">{{ __('Your profile is :percent% complete', ['percent' => $profile['percent']]) }}</p>
                    <div class="mt-2 h-2 max-w-md overflow-hidden rounded-full bg-white">
                        <div class="h-full rounded-full bg-[#16776e]" style="width: {{ $profile['percent'] }}%"></div>
                    </div>
                    <ul class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-sm">
                        @foreach(array_slice($profile['missing'], 0, 3) as $item)
                            <li><a href="{{ $item['url'] }}" class="font-medium text-[#16776e] hover:underline">{{ __($item['label']) }} &rarr;</a></li>
                        @endforeach
                    </ul>
                </div>
                <div class="flex w-full items-center gap-1 sm:w-auto">
                    <a href="{{ route('profile.setup.start') }}" class="inline-flex min-h-10 flex-1 items-center justify-center whitespace-nowrap rounded-lg bg-[#16776e] px-4 text-sm font-semibold text-white hover:bg-[#125f58] sm:flex-none">{{ __('Complete my profile') }} &rarr;</a>
                    <form method="POST" action="{{ route('home.profile-banner.hide') }}">
                        @csrf
                        <button type="submit" class="rounded-lg px-3 py-2 text-sm font-semibold text-slate-500 hover:bg-white hover:text-slate-700">{{ __('Hide') }}</button>
                    </form>
                </div>
            </section>
        @endif

        @unless($hasCases ?? true)
            <section class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <div>
                    <p class="font-semibold text-slate-900">{{ __('Bring your cases in from eCourts') }}</p>
                </div>
                <a href="{{ route('cases.find') }}" class="inline-flex min-h-10 items-center rounded-lg bg-[#16776e] px-4 text-sm font-semibold text-white hover:bg-[#125f58]">{{ __('Find my cases') }}</a>
            </section>
        @endunless

        <div class="grid grid-cols-1 items-start gap-5 lg:grid-cols-2">
            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5" aria-labelledby="upcoming-hearings-heading">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 id="upcoming-hearings-heading" class="text-lg font-semibold text-slate-900">{{ __('Upcoming hearings') }}</h2>
                    <div class="flex gap-2">
                        <a href="{{ route('cases.calendar', ['view' => 'week']) }}" class="inline-flex min-h-9 items-center rounded-lg border border-slate-200 px-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('Calendar') }}</a>
                        <a href="{{ route('hearings.create') }}" data-sheet="{{ route('sheets.next-date') }}" class="inline-flex min-h-9 items-center rounded-lg bg-[#16776e] px-3 text-sm font-semibold text-white hover:bg-[#125f58]">{{ __('+ Add hearing') }}</a>
                    </div>
                </div>

                <form method="GET" action="{{ route('dashboard') }}" class="mt-3" role="search">
                    <label for="hearing-search" class="sr-only">{{ __('Search hearings') }}</label>
                    <input id="hearing-search" type="search" name="hearing_search" value="{{ $hearingSearch }}" maxlength="100"
                           placeholder="{{ __('Search by case, case number or court') }}"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#16776e] focus:ring-[#16776e]">
                </form>

                <ul class="mt-3 divide-y divide-slate-100">
                    @forelse($upcomingHearings as $event)
                        @php $eventDate = \Illuminate\Support\Carbon::parse($event['date']); $eventDetails = \App\Services\HearingShareText::fromEvent($event); @endphp
                        <li class="space-y-1.5 py-3">
                            <time datetime="{{ $eventDate->toDateString() }}" class="block text-xs font-semibold {{ $eventDate->isToday() ? 'text-red-700' : 'text-teal-800' }}">
                                {{ $eventDate->translatedFormat('l, d F Y') }}@if($event['time']), {{ $event['time'] }}@endif
                            </time>
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <a href="{{ $event['url'] }}" @if($event['case_id']) data-sheet="{{ route('sheets.case-view', $event['case_id']) }}" @endif class="min-w-0 break-words text-sm font-semibold text-slate-900 hover:text-[#16776e]">{{ $event['title'] }}</a>
                                    <x-case-side :side="$event['appearing_for'] ?? null" :parties="$event['appearing_for_parties'] ?? null" :edit-url="$event['case_id'] ? route('cases.edit', $event['case_id']) : null" />
                                </div>
                                <p class="flex min-w-0 items-center gap-1.5 text-xs text-slate-500">
                                    <x-court-badge :level="$event['court_level'] ?? null" :state="$event['state_code'] ?? null" :district="$event['district'] ?? null" class="shrink-0" />
                                    <span class="truncate">{{ collect([$event['case_number'], $event['details']])->filter()->implode(' · ') ?: __($event['label']) }}</span>
                                </p>
                                <x-court-info class="mt-1.5" :live="false" :court="$event['court']" :complex="$event['court_complex'] ?? null" :room="$eventDetails['room']" :judge="$eventDetails['judge']" :vc-link="$eventDetails['vc_link']"
                                              :share="$eventDetails" />
                                <x-hearing-strip :show-room="false" :show-date="false" :date="$event['date']" :court="$event['court']" :room="$eventDetails['room']" :item="$event['item_number'] ?? null" :list-total="$event['list_total'] ?? null" :cause-list="$event['cause_list'] ?? null" :case-number="$event['case_number']" :district="($event['court_level'] ?? null) === 'district'" :order-url="!empty($event['order']) ? route('cases.documents.download', [$event['case_id'], $event['order'], 'preview' => 1]) : null" :order-date="($event['order'] ?? null)?->created_at?->format('d M Y')" />
                            </div>
                        </li>
                    @empty
                        <li class="py-6 text-center text-sm text-slate-500">
                            {{ $hearingSearch !== '' ? __('No hearings in the next 30 days match ":search".', ['search' => $hearingSearch]) : __('No hearings in the next 30 days.') }}
                        </li>
                    @endforelse
                </ul>
            </section>

            <div class="space-y-5">
            @if(auth()->user()->preference('home_display_boards'))
                @include('courts.partials.board-picker')
            @endif

            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5" aria-labelledby="agenda-heading">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 id="agenda-heading" class="text-lg font-semibold text-slate-900">{{ __('Tasks & appointments') }}</h2>
                    <a href="{{ route('tasks.create') }}" data-sheet="{{ route('sheets.task') }}" class="inline-flex min-h-9 items-center rounded-lg border border-[#16776e] px-3 text-sm font-semibold text-[#16776e] hover:bg-teal-50">{{ __('+ Task') }}</a>
                </div>

                @if($overdueTaskCount)
                    <a href="{{ route('tasks.index') }}" class="mt-3 block rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-100">
                        {{ trans_choice(':count overdue task|:count overdue tasks', $overdueTaskCount, ['count' => $overdueTaskCount]) }} &rarr;
                    </a>
                @endif

                <ul class="mt-3 divide-y divide-slate-100">
                    @forelse($agenda as $event)
                        @php $eventDate = \Illuminate\Support\Carbon::parse($event['date']); @endphp
                        <li>
                            <a href="{{ $event['url'] }}" class="flex items-start gap-3 py-2.5 hover:bg-slate-50">
                                <span class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full {{ $event['group'] === 'task' ? 'bg-amber-500' : 'bg-sky-500' }}" aria-hidden="true"></span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-semibold text-slate-900">{{ $event['title'] }}</span>
                                    <span class="block truncate text-xs text-slate-500">
                                        {{ __($event['label']) }} · {{ $eventDate->isToday() ? __('Today') : ($eventDate->isTomorrow() ? __('Tomorrow') : $eventDate->translatedFormat('D, d M')) }}{{ $event['time'] ? ' '.$event['time'] : '' }}
                                        {{ $event['case_number'] ? ' · '.$event['case_number'] : '' }}
                                    </span>
                                </span>
                            </a>
                        </li>
                    @empty
                        <li class="py-6 text-center text-sm text-slate-500">{{ __('Nothing due in the next 7 days.') }}</li>
                    @endforelse
                </ul>
            </section>
            </div>
        </div>
    @endauth

    @guest
        @include('courts.partials.board-picker')
    @endguest

    <section aria-labelledby="explore-heading">
        <x-collection-toolbar target="home-modules" placeholder="Search modules" label="Explore" class="mb-3" />
        <h2 id="explore-heading" class="sr-only">Explore VakilNiti</h2>
        <nav id="home-modules" class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6" aria-label="Modules">
            <x-module-tile :href="route('cases.index')" icon="briefcase" title="Case Management" description="Cases, hearings, tasks" badge="New" />
            <x-module-tile :href="url('/pdf-annotator')" icon="pdf" title="PDF Annotator" description="Highlight, search, draw" badge="New" />
            <x-module-tile :href="route('courts.supreme-court')" icon="pillars" title="Supreme Court" description="Court list, boards, e-pass" />
            <x-module-tile :href="route('courts.delhi-high-court')" icon="building" title="Delhi High Court" description="Roster, cause list, orders" />
            <x-module-tile :href="route('courts.delhi-district-courts')" icon="gavel" title="Delhi District Courts" description="All seven complexes" />
            <x-module-tile :href="route('courts.display-boards')" icon="board" title="Display Boards" description="Live courtroom boards" />
            <x-module-tile :href="route('cases.calendar')" icon="calendar" title="Court Calendar" description="Hearings by day, week, month" />
            <x-module-tile :href="route('courts.ecourts-services')" icon="search" title="eCourts Services" description="Case status, ePay, eFiling" />
            <x-module-tile :href="route('clients.index')" icon="people" title="Clients & Billing" description="Clients, invoices, payments" />
            <x-module-tile :href="url('/police-administration')" icon="shield" title="Police Administration" description="Stations and portals" />
            <x-module-tile :href="url('/new-criminal-laws')" icon="law" title="New Criminal Laws" description="BNS, BNSS, BSA" />
            <x-module-tile :href="route('bare-acts.index')" icon="book" title="Bare Acts" description="Central Acts and codes" />
            <x-module-tile :href="route('miscellaneous-forms.index')" icon="form" title="Misc. Forms" description="Court, RTI, affidavits" />
            <x-module-tile :href="url('/court-fee')" icon="calculator" title="Court Fee" description="Calculator and schedules" />
            <x-module-tile :href="route('bar-associations.index')" icon="bar" title="Bar Associations" description="Bars and Bar Councils" />
            <x-module-tile :href="route('courts.index')" icon="map" title="All Courts" description="High Courts, districts, tribunals" />
            <x-module-tile :href="route('dashboard-studio.index')" icon="chart" title="Law Data" description="Build data dashboards" />
            <x-module-tile :href="route('profile.index')" icon="settings" title="Profile" description="Practice and invoice settings" />
            <p data-filter-empty hidden class="col-span-full rounded-2xl border border-slate-200 bg-white p-4 text-sm text-slate-500">No module matches your search.</p>
        </nav>
    </section>
</div>
@endsection

