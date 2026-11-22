@extends('layouts.app')

@section('title', 'Home')
@section('page-heading', 'Home')

@section('content')
<div class="mx-auto max-w-[1500px] space-y-6">
    <header class="relative overflow-hidden rounded-3xl bg-[#142235] p-5 text-white shadow-lg sm:p-7">
        <div class="pointer-events-none absolute -right-12 -top-24 h-64 w-64 rounded-full border border-white/10"></div>
        <div class="relative flex flex-wrap items-center justify-between gap-4">
            <h1 class="text-2xl font-semibold tracking-tight sm:text-3xl">
                @auth Welcome, {{ \Illuminate\Support\Str::of(auth()->user()->name)->before(' ') }} @else Your legal workspace @endauth
            </h1>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('case-dashboard.index') }}" class="inline-flex min-h-11 items-center rounded-xl bg-[#238b80] px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#16776e]">Case dashboard</a>
                <a href="{{ route('cases.index') }}" class="inline-flex min-h-11 items-center rounded-xl border border-white/20 bg-white/5 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-white/10">My cases</a>
            </div>
        </div>
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
                <form method="POST" action="{{ route('home.profile-banner.hide') }}">
                    @csrf
                    <button type="submit" class="rounded-lg px-3 py-2 text-sm font-semibold text-slate-500 hover:bg-white hover:text-slate-700">Hide</button>
                </form>
            </section>
        @endif

        @unless($hasCases ?? true)
            <section class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <div>
                    <p class="font-semibold text-slate-900">Bring your cases in from eCourts</p>
                    <p class="text-sm text-slate-600">Search by your bar code on the official site, paste the results and choose the cases to add.</p>
                </div>
                <a href="{{ route('cases.find') }}" class="inline-flex min-h-10 items-center rounded-lg bg-[#16776e] px-4 text-sm font-semibold text-white hover:bg-[#125f58]">Find my cases</a>
            </section>
        @endunless

        <div class="grid gap-5 lg:grid-cols-[minmax(0,3fr)_minmax(0,2fr)]">
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
                        <li>
                            <a href="{{ $event['url'] }}" class="flex items-center gap-3 py-2.5 hover:bg-slate-50">
                                <span class="flex w-14 shrink-0 flex-col items-center rounded-lg py-1 {{ $eventDate->isToday() ? 'bg-[#16776e] text-white' : 'bg-teal-50 text-teal-900' }}">
                                    <span class="text-[11px] font-bold uppercase">{{ $eventDate->isToday() ? 'Today' : ($eventDate->isTomorrow() ? 'Tmrw' : $eventDate->format('M')) }}</span>
                                    <span class="text-lg font-bold leading-tight">{{ $eventDate->format('d') }}</span>
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-semibold text-slate-900">{{ $event['title'] }}</span>
                                    <span class="block truncate text-xs text-slate-500">{{ collect([$event['time'], $event['case_number'], $event['court']])->filter()->implode(' · ') ?: $event['label'] }}</span>
                                    @if($event['details'])
                                        <span class="block truncate text-xs text-slate-600">{{ $event['details'] }}</span>
                                    @endif
                                </span>
                            </a>
                        </li>
                    @empty
                        <li class="py-6 text-center text-sm text-slate-500">
                            {{ $hearingSearch !== '' ? 'No hearings in the next 30 days match "'.$hearingSearch.'".' : 'No hearings in the next 30 days.' }}
                        </li>
                    @endforelse
                </ul>
            </section>

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
    @endauth

    <section aria-labelledby="explore-heading">
        <h2 id="explore-heading" class="sr-only">Explore VakilNiti</h2>
        <nav class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6" aria-label="Modules">
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
        </nav>
    </section>
    @php
        $sections = [
            [
                'title' => 'India Law & Justice Data',
                'links' => [
                    ['label' => 'Create Law Data Dashboards', 'url' => route('dashboard-studio.index')],
                ],
            ],
            [
                'title' => 'Case Management System',
                'links' => [
                    ['label' => 'My Case Dashboard', 'url' => route('case-dashboard.index')],
                    ['label' => 'My Cases', 'url' => route('cases.index')],
                    ['label' => 'Calendar & Hearings', 'url' => route('cases.calendar')],
                    ['label' => 'Hearing History', 'url' => route('hearings.history')],
                    ['label' => 'Tasks & Reminders', 'url' => route('tasks.index')],
                ],
            ],
            [
                'title' => 'Courts',
                'links' => [
                    ['label' => 'Court Directory', 'url' => route('court-directory.index')],
                    ['label' => 'Supreme Court of India', 'url' => route('courts.supreme-court')],
                    ['label' => 'Delhi High Court', 'url' => route('courts.delhi-high-court')],
                    ['label' => 'Delhi District Courts', 'url' => route('courts.delhi-district-courts')],
                    ['label' => 'Court Display Boards', 'url' => route('courts.display-boards')],
                    ['label' => 'eCourts Services', 'url' => route('courts.ecourts-services')],
                    ['label' => 'High Courts', 'url' => route('court-directory.index', ['level' => 'high-court'])],
                    ['label' => 'District & Subordinate Courts', 'url' => route('court-directory.index', ['level' => 'district'])],
                    ['label' => 'Appellate Courts & Tribunals', 'url' => route('court-catalogue.index')],
                    ['label' => 'Official Court Search', 'url' => route('court-integrations.ecourts.search')],
                ],
            ],
            [
                'title' => 'Clients & Billing',
                'links' => [
                    ['label' => 'All Clients', 'url' => route('clients.index')],
                    ['label' => 'Invoices & Payments', 'url' => route('billing.index')],
                ],
            ],
            [
                'title' => 'Police Administration',
                'links' => [
                    ['label' => 'Police Stations', 'url' => url('/police-administration#police-stations')],
                    ['label' => 'State Police Departments', 'url' => url('/police-administration#state-police')],
                    ['label' => 'Police Districts & Ranges', 'url' => url('/police-administration#police-districts')],
                    ['label' => 'Official Police Portals', 'url' => url('/police-administration#official-portals')],
                ],
            ],
            [
                'title' => 'Bar Associations',
                'links' => [
                    ['label' => 'National Associations', 'url' => route('bar-associations.index') . '#national'],
                    ['label' => 'State & High Court Associations', 'url' => route('bar-associations.index') . '#state-high-court'],
                    ['label' => 'District Associations', 'url' => route('bar-associations.index') . '#district'],
                    ['label' => 'Bar Councils', 'url' => route('bar-associations.index') . '#bar-councils'],
                ],
            ],
            [
                'title' => 'Court Fee',
                'links' => [
                    ['label' => 'Court Fee Calculator', 'url' => url('/court-fee#calculator')],
                    ['label' => 'Court Fee Schedules', 'url' => url('/court-fee#fee-schedules')],
                    ['label' => 'Rules & Exemptions', 'url' => url('/court-fee#rules')],
                ],
            ],
            [
                'title' => 'Bare Acts',
                'links' => [
                    ['label' => 'Overview', 'url' => route('bare-acts.index') . '#overview'],
                    ['label' => 'Central Acts', 'url' => route('bare-acts.index') . '#central-acts'],
                    ['label' => 'Criminal Law Codes', 'url' => route('bare-acts.index') . '#criminal-laws'],
                    ['label' => 'Official Sources', 'url' => route('bare-acts.index') . '#sources'],
                ],
            ],
            [
                'title' => 'New Criminal Laws',
                'links' => [
                    ['label' => 'Open New Criminal Laws', 'url' => url('/new-criminal-laws')],
                ],
            ],
            [
                'title' => 'Miscellaneous Forms',
                'links' => [
                    ['label' => 'Court Forms', 'url' => route('miscellaneous-forms.index') . '#court-forms'],
                    ['label' => 'Civil & Criminal Forms', 'url' => route('miscellaneous-forms.index') . '#civil-criminal'],
                    ['label' => 'Affidavits & Applications', 'url' => route('miscellaneous-forms.index') . '#affidavits'],
                    ['label' => 'Police & RTI Forms', 'url' => route('miscellaneous-forms.index') . '#police-rti'],
                ],
            ],
            [
                'title' => 'PDF Annotator',
                'links' => [
                    ['label' => 'Open PDF Annotator', 'url' => url('/pdf-annotator')],
                ],
            ],
            [
                'title' => 'Profile Settings',
                'links' => [
                    ['label' => 'Open Profile Settings', 'url' => route('profile.index')],
                    ['label' => 'Personal Profile', 'url' => route('profile.personal.index')],
                    ['label' => 'Professional Profile', 'url' => route('profile.professional.index')],
                    ['label' => 'Firm Profile', 'url' => route('firm-profile.index')],
                    ['label' => 'Contact Information', 'url' => route('profile.contacts.index')],
                    ['label' => 'Practice States & Jurisdictions', 'url' => route('profile.practice-states.index')],
                    ['label' => 'Invoice & GST Settings', 'url' => route('profile.invoice-settings.index')],
                    ['label' => 'Tax & Compliance', 'url' => route('profile.tax-compliance')],
                ],
            ],
        ];
    @endphp

    <details class="group rounded-2xl border border-slate-200 bg-white shadow-sm">
        <summary class="flex cursor-pointer list-none items-center justify-between px-5 py-4 text-lg font-semibold text-slate-900">
            All links
            <x-icon name="chevron" class="text-slate-400 transition group-open:rotate-180" />
        </summary>
    <section class="grid gap-4 border-t border-slate-100 p-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach($sections as $section)
            <article class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-[#b8dfd9] hover:shadow-md">
                <h2 class="text-lg font-semibold text-slate-900">{{ $section['title'] }}</h2>
                <ul class="mt-3 space-y-1 border-t border-slate-100 pt-3">
                    @foreach($section['links'] as $link)
                        <li>
                            <a href="{{ $link['url'] }}" class="inline-flex min-h-9 items-center text-sm font-semibold text-blue-700 hover:underline">
                                {{ $link['label'] }} &rarr;
                            </a>
                        </li>
                    @endforeach
                </ul>
            </article>
        @endforeach
    </section>
    </details>
</div>
@endsection

