@extends('layouts.app')

@section('title', 'Home')
@section('page-heading', 'Home')

@section('content')
<div class="mx-auto max-w-[1500px] space-y-6">
    {{-- One tap to the things an advocate does every day. --}}
    @php
        $quickActions = auth()->check()
            ? [
                ['Today', 'calendar', route('cases.calendar', ['view' => 'day'])],
                ['+ Hearing', 'plus', route('hearings.create')],
                ['+ Case', 'briefcase', route('cases.manual')],
                ['Import cases', 'download', route('cases.find')],
                ['Court fee', 'calculator', route('court-fee.index')],
                ['My cases', 'list', route('cases.index')],
            ]
            : [
                ['Supreme Court', 'pillars', route('courts.supreme-court')],
                ['Delhi HC', 'building', route('courts.delhi-high-court')],
                ['District courts', 'gavel', route('courts.delhi-district-courts')],
                ['Display boards', 'board', route('courts.display-boards')],
                ['Court fee', 'calculator', route('court-fee.index')],
                ['Bare Acts', 'book', route('bare-acts.index')],
            ];
    @endphp
    <header class="rounded-3xl bg-[#142235] p-4 text-white shadow-lg sm:p-5">
        <h1 class="text-xl font-semibold tracking-tight sm:text-2xl">
            @auth {{ \Illuminate\Support\Str::of(auth()->user()->name)->before(' ') }}, {{ now()->format('l, d M') }} @else Your legal workspace @endauth
        </h1>
        @if($nextHearing ?? null)
            @php $nextDate = \Illuminate\Support\Carbon::parse($nextHearing['date']); @endphp
            <section aria-labelledby="next-hearing-heading" class="mt-3 rounded-2xl bg-white p-3.5 text-slate-900 shadow-sm sm:p-4">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <p id="next-hearing-heading" class="text-[11px] font-bold uppercase tracking-wide text-[#16776e]">
                        Next hearing · {{ $nextDate->isToday() ? 'Today' : ($nextDate->isTomorrow() ? 'Tomorrow' : $nextDate->format('D, d M')) }}@if($nextHearing['time']) · {{ $nextHearing['time'] }}@endif
                    </p>
                    <a href="{{ $nextHearing['url'] }}" class="text-xs font-semibold text-[#16776e] hover:underline">Open case &rarr;</a>
                </div>
                <p class="mt-1 flex flex-wrap items-center gap-1.5 font-semibold leading-snug">
                    <a href="{{ $nextHearing['url'] }}" class="hover:text-[#16776e]">{{ $nextHearing['title'] }}</a>
                    <x-case-side :side="$nextHearing['appearing_for'] ?? null" :parties="$nextHearing['appearing_for_parties'] ?? null" :edit-url="$nextHearing['case_id'] ? route('cases.edit', $nextHearing['case_id']) : null" />
                </p>
                @if($nextHearing['case_number'] || $nextHearing['details'])
                    <p class="mt-0.5 truncate text-xs text-slate-500">{{ collect([$nextHearing['case_number'], $nextHearing['details']])->filter()->implode(' · ') }}</p>
                @endif
                <x-court-info class="mt-2" :court="$nextHearing['court']" :complex="$nextHearing['court_complex'] ?? null" :room="$nextHearing['court_room'] ?? null" :judge="$nextHearing['judge_name'] ?? null" :vc-link="$nextHearing['vc_link'] ?? null"
                              :share="['title' => $nextHearing['title'], 'case_number' => $nextHearing['case_number'], 'side' => $nextHearing['appearing_for'] ?? null, 'parties' => $nextHearing['appearing_for_parties'] ?? null, 'date' => $nextHearing['date'], 'time' => $nextHearing['time'], 'details' => $nextHearing['details']]" />
            </section>
        @endif
        <nav aria-label="Quick actions" class="mt-3 grid grid-cols-3 gap-2 sm:grid-cols-6">
            @foreach($quickActions as [$actionLabel, $actionIcon, $actionUrl])
                <a href="{{ $actionUrl }}" class="flex min-h-[72px] flex-col items-center justify-center gap-1.5 rounded-2xl bg-white/[0.07] px-1 text-center text-xs font-semibold text-white ring-1 ring-white/10 transition hover:bg-white/15 sm:text-sm">
                    <x-icon :name="$actionIcon" :size="22" class="text-[#e6b84a]" />
                    {{ $actionLabel }}
                </a>
            @endforeach
        </nav>
    </header>

    @auth
        @if($profile ?? null)
            <section class="flex flex-wrap items-center gap-4 rounded-2xl border border-teal-200 bg-teal-50/70 p-4 sm:p-5" aria-label="Profile completion">
                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-white text-sm font-bold text-[#16776e] ring-4 ring-teal-100">{{ $profile['percent'] }}%</div>
                <div class="min-w-0 flex-1">
                    <p class="font-semibold text-slate-900">Your profile is {{ $profile['percent'] }}% complete</p>
                    <div class="mt-2 h-2 max-w-md overflow-hidden rounded-full bg-white">
                        <div class="h-full rounded-full bg-[#16776e]" style="width: {{ $profile['percent'] }}%"></div>
                    </div>
                    <ul class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-sm">
                        @foreach(array_slice($profile['missing'], 0, 3) as $item)
                            <li><a href="{{ $item['url'] }}" class="font-medium text-[#16776e] hover:underline">{{ $item['label'] }} &rarr;</a></li>
                        @endforeach
                    </ul>
                </div>
                <div class="flex items-center gap-1">
                    <a href="{{ route('profile.setup.start') }}" class="inline-flex min-h-10 items-center rounded-lg bg-[#16776e] px-4 text-sm font-semibold text-white hover:bg-[#125f58]">Complete my profile &rarr;</a>
                    <form method="POST" action="{{ route('home.profile-banner.hide') }}">
                        @csrf
                        <button type="submit" class="rounded-lg px-3 py-2 text-sm font-semibold text-slate-500 hover:bg-white hover:text-slate-700">Hide</button>
                    </form>
                </div>
            </section>
        @endif

        @unless($hasCases ?? true)
            <section class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <div>
                    <p class="font-semibold text-slate-900">Bring your cases in from eCourts</p>
                </div>
                <a href="{{ route('cases.find') }}" class="inline-flex min-h-10 items-center rounded-lg bg-[#16776e] px-4 text-sm font-semibold text-white hover:bg-[#125f58]">Find my cases</a>
            </section>
        @endunless

        <div class="grid grid-cols-1 items-start gap-5 lg:grid-cols-2">
            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5" aria-labelledby="upcoming-hearings-heading">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 id="upcoming-hearings-heading" class="text-lg font-semibold text-slate-900">Upcoming hearings</h2>
                    <div class="flex gap-2">
                        <a href="{{ route('cases.calendar', ['view' => 'week']) }}" class="inline-flex min-h-9 items-center rounded-lg border border-slate-200 px-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">Calendar</a>
                        <a href="{{ route('hearings.create') }}" class="inline-flex min-h-9 items-center rounded-lg bg-[#16776e] px-3 text-sm font-semibold text-white hover:bg-[#125f58]">+ Add hearing</a>
                    </div>
                </div>

                <form method="GET" action="{{ route('dashboard') }}" class="mt-3" role="search">
                    <label for="hearing-search" class="sr-only">Search hearings</label>
                    <input id="hearing-search" type="search" name="hearing_search" value="{{ $hearingSearch }}" maxlength="100"
                           placeholder="Search by case, case number or court"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#16776e] focus:ring-[#16776e]">
                </form>

                <ul class="mt-3 divide-y divide-slate-100">
                    @forelse($upcomingHearings as $event)
                        @php $eventDate = \Illuminate\Support\Carbon::parse($event['date']); @endphp
                        <li class="flex items-start gap-3 py-3">
                            <a href="{{ $event['url'] }}" class="flex w-14 shrink-0 flex-col items-center rounded-lg py-1 {{ $eventDate->isToday() ? 'bg-[#16776e] text-white' : 'bg-teal-50 text-teal-900' }}">
                                <span class="text-[11px] font-bold uppercase">{{ $eventDate->isToday() ? 'Today' : ($eventDate->isTomorrow() ? 'Tmrw' : $eventDate->format('M')) }}</span>
                                <span class="text-lg font-bold leading-tight">{{ $eventDate->format('d') }}</span>
                                @if($event['time'])<span class="text-[10px] font-semibold">{{ $event['time'] }}</span>@endif
                            </a>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <a href="{{ $event['url'] }}" class="min-w-0 break-words text-sm font-semibold text-slate-900 hover:text-[#16776e]">{{ $event['title'] }}</a>
                                    <x-case-side :side="$event['appearing_for'] ?? null" :parties="$event['appearing_for_parties'] ?? null" :edit-url="$event['case_id'] ? route('cases.edit', $event['case_id']) : null" />
                                </div>
                                <p class="truncate text-xs text-slate-500">{{ collect([$event['case_number'], $event['details']])->filter()->implode(' · ') ?: $event['label'] }}</p>
                                <x-court-info class="mt-1.5" :court="$event['court']" :complex="$event['court_complex'] ?? null" :room="$event['court_room'] ?? null" :judge="$event['judge_name'] ?? null" :vc-link="$event['vc_link'] ?? null"
                                              :share="['title' => $event['title'], 'case_number' => $event['case_number'], 'side' => $event['appearing_for'] ?? null, 'parties' => $event['appearing_for_parties'] ?? null, 'date' => $event['date'], 'time' => $event['time'], 'details' => $event['details']]" />
                            </div>
                        </li>
                    @empty
                        <li class="py-6 text-center text-sm text-slate-500">
                            {{ $hearingSearch !== '' ? 'No hearings in the next 30 days match "'.$hearingSearch.'".' : 'No hearings in the next 30 days.' }}
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
                    <h2 id="agenda-heading" class="text-lg font-semibold text-slate-900">Tasks &amp; appointments</h2>
                    <a href="{{ route('tasks.create') }}" class="inline-flex min-h-9 items-center rounded-lg border border-[#16776e] px-3 text-sm font-semibold text-[#16776e] hover:bg-teal-50">+ Task</a>
                </div>

                @if($overdueTaskCount)
                    <a href="{{ route('tasks.index') }}" class="mt-3 block rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-100">
                        {{ $overdueTaskCount }} overdue {{ \Illuminate\Support\Str::plural('task', $overdueTaskCount) }} &rarr;
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
                                        {{ $event['label'] }} · {{ $eventDate->isToday() ? 'Today' : ($eventDate->isTomorrow() ? 'Tomorrow' : $eventDate->format('D, d M')) }}{{ $event['time'] ? ' '.$event['time'] : '' }}
                                        {{ $event['case_number'] ? ' · '.$event['case_number'] : '' }}
                                    </span>
                                </span>
                            </a>
                        </li>
                    @empty
                        <li class="py-6 text-center text-sm text-slate-500">Nothing due in the next 7 days.</li>
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
            <x-module-tile :href="route('case-dashboard.index')" icon="briefcase" title="Case Management" description="Cases, hearings, tasks" badge="New" />
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

