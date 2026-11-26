@extends('layouts.app')

@section('title', 'Courts of India')
@section('page-heading', 'Courts')
@section('meta_description', 'Courts of India in one place: Supreme Court, Delhi High Court, Delhi District Courts, live display boards, eCourts services and tribunals.')

@section('content')
<div class="mx-auto max-w-5xl space-y-6">
    <x-page-header title="Courts" :subtitle="auth()->check() ? 'Your courts first, then court services.' : 'Court services, display boards and directories.'" />

    @auth
        <section aria-labelledby="my-courts-heading" class="space-y-3">
            <div class="flex flex-wrap items-end justify-between gap-2">
                <h2 id="my-courts-heading" class="text-lg font-semibold text-slate-900">
                    Your courts
                    @if($myCourts->isNotEmpty())
                        <span class="ml-1 rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600">{{ $myCourts->count() }}</span>
                    @endif
                </h2>
                @if($myCourts->isNotEmpty())
                    <a href="{{ route('cases.calendar') }}" class="text-sm font-semibold text-[#16776e] hover:underline">Open calendar</a>
                @endif
            </div>

            @if($myCourts->isEmpty())
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-5 text-sm text-slate-600">
                    The courts of your open cases will show here, with the next date, room, judge and VC link.
                    <a href="{{ route('cases.create') }}" class="mt-3 flex min-h-11 w-full items-center justify-center rounded-xl bg-[#16776e] px-4 font-semibold text-white hover:bg-[#125f58] sm:inline-flex sm:w-auto">Add a case</a>
                </div>
            @else
                <ul class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    @foreach($myCourts as $court)
                        @php
                            $isToday = $court['today'] > 0;
                            $nextLabel = $court['next_date']
                                ? ($court['next_date']->isToday() ? 'Today' : ($court['next_date']->isTomorrow() ? 'Tomorrow' : $court['next_date']->format('D, d M')))
                                : null;
                        @endphp
                        <li class="flex min-w-0 flex-col gap-3 rounded-2xl border bg-white p-4 shadow-sm {{ $isToday ? 'border-[#16776e] ring-1 ring-[#16776e]/30' : 'border-slate-200' }}">
                            <div class="flex min-w-0 items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <h3 class="break-words font-semibold text-slate-900">{{ $court['court'] }}</h3>
                                    @if($court['complex'])
                                        <p class="mt-0.5 break-words text-xs text-slate-500">{{ $court['complex'] }}</p>
                                    @endif
                                </div>
                                @if($nextLabel)
                                    <span class="shrink-0 rounded-xl px-2.5 py-1 text-center text-xs font-semibold {{ $isToday ? 'bg-[#16776e] text-white' : 'bg-slate-100 text-slate-700' }}">
                                        {{ $nextLabel }}
                                        <span class="block text-xs font-medium {{ $isToday ? 'text-teal-50' : 'text-slate-500' }}">{{ $court['next_count'] }} {{ \Illuminate\Support\Str::plural('case', $court['next_count']) }}</span>
                                    </span>
                                @else
                                    <span class="shrink-0 rounded-xl bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-800 ring-1 ring-inset ring-amber-200">No date</span>
                                @endif
                            </div>

                            <x-court-info :room="$court['room']" :judge="$court['judge']" :vc-link="$court['vc_link']" :designation="$court['court']" :complex="$court['complex']" :live="$court['today'] > 0" />

                            <div class="mt-auto flex flex-wrap items-center gap-2 border-t border-slate-100 pt-3">
                                <a href="{{ $court['cases_url'] }}" class="inline-flex min-h-10 flex-1 items-center justify-center rounded-xl border border-[#16776e] px-3 text-sm font-semibold text-[#16776e] hover:bg-teal-50 sm:flex-none">
                                    {{ $court['cases'] }} open {{ \Illuminate\Support\Str::plural('case', $court['cases']) }}
                                </a>
                                @if($court['official_url'])
                                    <a href="{{ $court['official_url'] }}" class="inline-flex min-h-10 flex-1 items-center justify-center rounded-xl border border-slate-200 px-3 text-sm font-semibold text-slate-700 hover:border-[#16776e] hover:text-[#16776e] sm:flex-none">
                                        Court services
                                    </a>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    @endauth

    <section aria-labelledby="court-services-heading" class="space-y-3">
        <h2 id="court-services-heading" class="text-lg font-semibold text-slate-900">Court services</h2>

        <x-collection-toolbar target="courts-list" placeholder="Search courts and services" />

        <nav id="courts-list" class="grid grid-cols-2 gap-3 sm:grid-cols-3" aria-label="Court services">
            <x-module-tile :href="route('courts.supreme-court')" icon="pillars" title="Supreme Court" description="Court list, display boards, e-pass, calendar" />
            <x-module-tile :href="route('courts.delhi-high-court')" icon="building" title="Delhi High Court" description="Display board, roster, cause list, judgements" />
            <x-module-tile :href="route('courts.high-courts')" icon="building" title="All High Courts" description="All 25, with case status and e-filing" />
            <x-module-tile :href="route('courts.delhi-district-courts')" icon="gavel" title="Delhi District Courts" description="All seven court complexes" />
            <x-module-tile :href="route('tribunals.index')" icon="scale" title="Tribunals" description="Appellate courts and tribunals" />
            <x-module-tile :href="route('courts.ecourts-services')" icon="search" title="eCourts services" description="Case status, ePay, eFiling, NJDG" />
            <p data-filter-empty hidden class="col-span-full rounded-2xl border border-slate-200 bg-white p-4 text-sm text-slate-500">Nothing matches your search.</p>
        </nav>
    </section>
</div>
@endsection
