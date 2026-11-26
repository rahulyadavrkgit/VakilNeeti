@extends('layouts.app')

@section('title', __('Home'))
@section('page-heading', __('Home'))

@section('content')
<div class="mx-auto max-w-[1500px] space-y-6">
    @guest
        @include('landing')

        {{-- Visitors: one tap to the free court tools. --}}
        <header class="rounded-3xl bg-[#142235] p-4 text-white shadow-lg sm:p-5">
            <h2 class="text-xl font-semibold tracking-tight sm:text-2xl">{{ __('Free court tools · no sign-in needed') }}</h2>
            <nav aria-label="{{ __('Quick actions') }}" class="mt-3 grid grid-cols-3 gap-2 sm:grid-cols-6">
                @foreach([
                    [__('Supreme Court'), 'pillars', route('courts.supreme-court')],
                    [__('Delhi HC'), 'building', route('courts.delhi-high-court')],
                    [__('District courts'), 'gavel', route('courts.delhi-district-courts')],
                    [__('Display boards'), 'board', route('courts.display-boards')],
                    [__('Court fee'), 'calculator', route('court-fee.index')],
                    [__('Bare Acts'), 'book', route('bare-acts.index')],
                ] as [$actionLabel, $actionIcon, $actionUrl])
                    <a href="{{ $actionUrl }}" class="flex min-h-[72px] flex-col items-center justify-center gap-1.5 rounded-2xl bg-white/[0.07] px-1 text-center text-xs font-semibold text-white ring-1 ring-white/10 transition hover:bg-white/15 sm:text-sm">
                        <x-icon :name="$actionIcon" :size="22" class="text-[#e6b84a]" />
                        {{ $actionLabel }}
                    </a>
                @endforeach
            </nav>
        </header>

        @include('courts.partials.board-picker')
    @endguest

    @auth
        @php $boardsOnHome = (bool) auth()->user()->preference('home_display_boards'); @endphp

        {{-- Left (the size of the visitors' card): the day and the hearings after it. Right: the live display boards, then tasks. --}}
        <div class="grid grid-cols-1 items-start gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.15fr)]">
            <div class="min-w-0 space-y-5">
                <header class="rounded-3xl bg-[#142235] p-4 text-white shadow-lg sm:p-5">
                    <h1 class="text-xl font-semibold tracking-tight sm:text-2xl">{{ \Illuminate\Support\Str::of(auth()->user()->name)->before(' ') }}, {{ now()->translatedFormat('l, d M') }}</h1>
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
                    @endif
                    @if($todayHearings->isNotEmpty())
                        {{-- Every hearing today together, each in the same layout; one button shares the whole day. --}}
                        @php $todayLabel = __('Today').' · '.today()->format('jS M'); @endphp
                        <section aria-labelledby="next-hearing-heading" class="mt-3 space-y-2">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p id="next-hearing-heading" class="text-sm font-bold text-white">{{ $todayLabel }} <span class="font-normal text-white/70">· {{ trans_choice(':count hearing|:count hearings', $todayHearings->count(), ['count' => $todayHearings->count()]) }}</span></p>
                                <x-day-share tone="dark" :heading="__('Hearings').' · '.$todayLabel" :events="$todayHearings" />
                            </div>
                            @foreach($todayHearings as $event)
                                <x-hearing-card :event="$event" />
                            @endforeach
                        </section>
                    @elseif($nextHearing ?? null)
                        @php
                            $nextDate = \Illuminate\Support\Carbon::parse($nextHearing['date']);
                            $nextLabel = __('Next hearing').' · '.($nextDate->isTomorrow() ? __('Tomorrow').' ' : $nextDate->format('D').' ').$nextDate->format('jS M');
                        @endphp
                        <x-hearing-card class="mt-3" :event="$nextHearing" :label="$nextLabel" label-id="next-hearing-heading" />
                    @endif
                </header>

                <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5" aria-labelledby="upcoming-hearings-heading">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h2 id="upcoming-hearings-heading" class="text-lg font-semibold text-slate-900">{{ ($nextHearing ?? null) || $todayHearings->isNotEmpty() ? __('After that') : __('Upcoming hearings') }}</h2>
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
    
                    {{-- By day, each day with its own Share button; each hearing in the same layout as the top card. --}}
                    <div class="mt-3 space-y-4">
                        @forelse($upcomingByDay as $day => $events)
                            @php
                                $dayDate = \Illuminate\Support\Carbon::parse($day);
                                $dayLabel = ($dayDate->isToday() ? __('Today') : ($dayDate->isTomorrow() ? __('Tomorrow') : $dayDate->translatedFormat('l'))).' · '.$dayDate->format('jS M');
                            @endphp
                            <section aria-label="{{ $dayLabel }}">
                                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-1.5">
                                    <h3 class="text-sm font-bold {{ $dayDate->isToday() ? 'text-red-700' : 'text-teal-800' }}">{{ $dayLabel }} <span class="font-normal text-slate-500">· {{ trans_choice(':count hearing|:count hearings', $events->count(), ['count' => $events->count()]) }}</span></h3>
                                    <x-day-share :heading="__('Hearings').' · '.$dayLabel" :events="$events" />
                                </div>
                                <div class="mt-2 space-y-2">
                                    @foreach($events as $event)
                                        <x-hearing-card :event="$event" />
                                    @endforeach
                                </div>
                            </section>
                        @empty
                            <p class="py-6 text-center text-sm text-slate-500">
                                {{ $hearingSearch !== '' ? __('No hearings in the next 30 days match ":search".', ['search' => $hearingSearch]) : (($nextHearing ?? null) || $todayHearings->isNotEmpty() ? __('No other hearings in the next 30 days.') : __('No hearings in the next 30 days.')) }}
                            </p>
                        @endforelse
                    </div>
                </section>
            </div>

            <div class="min-w-0 space-y-5">
                @if($boardsOnHome)
                    @include('courts.partials.board-picker')
                @endif
                @include('partials.home-agenda')
            </div>
        </div>

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
    @endauth


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

