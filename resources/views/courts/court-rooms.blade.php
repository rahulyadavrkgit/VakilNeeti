@extends('layouts.app')

@section('title', $court['court'].': '.$court['list'])
@section('meta_description', $court['list'].' of the '.$court['court'].': benches, judges, court room, roster, VC link and meeting ID in one searchable page.')
@section('page-heading', $court['list'])

@php
    $cell = 'min-w-0 rounded-xl bg-slate-50 px-3 py-2';
    $cellLabel = 'text-[11px] font-bold uppercase tracking-wide text-slate-500';
    $chip = 'inline-flex min-h-9 shrink-0 items-center gap-1.5 rounded-full px-3.5 text-sm font-semibold transition';
@endphp

@section('content')
<div class="mx-auto max-w-5xl space-y-4">
    @include('courts.partials.breadcrumb', ['current' => $court['court'].' · '.$court['list']])

    <div class="flex flex-wrap items-end justify-between gap-2">
        <div>
            <p class="text-sm font-semibold text-[#16776e]">{{ $court['court'] }}</p>
            <h1 class="text-2xl font-bold text-slate-900">{{ $court['list'] }}</h1>
            @if($source)
                <p class="mt-1 text-xs text-slate-500">{{ $source }}</p>
            @endif
        </div>
        <a href="{{ route($court['parent_route']) }}" class="text-sm font-semibold text-[#16776e] hover:underline">All {{ $courtKey === 'supreme-court' ? 'Supreme Court' : ($courtKey === 'delhi-high-court' ? 'High Court' : 'district') }} services &rarr;</a>
    </div>

    @if($total > 0)
        @if(count($sections) > 1)
            <nav aria-label="Sections" class="-mx-1 flex gap-1.5 overflow-x-auto px-1 pb-1">
                <a href="{{ url()->current() }}" aria-current="{{ $activeSection ? 'false' : 'page' }}"
                   class="{{ $chip }} {{ $activeSection ? 'border border-slate-200 bg-white text-slate-700 hover:border-[#16776e]' : 'bg-[#142235] text-white' }}">All <span class="opacity-70">{{ $total }}</span></a>
                @foreach($sections as $sectionName => $sectionCount)
                    <a href="{{ url()->current() }}?section={{ \Illuminate\Support\Str::slug($sectionName) }}" aria-current="{{ $activeSection === $sectionName ? 'page' : 'false' }}"
                       class="{{ $chip }} {{ $activeSection === $sectionName ? 'bg-[#142235] text-white' : 'border border-slate-200 bg-white text-slate-700 hover:border-[#16776e]' }}">{{ $sectionName }} <span class="opacity-70">{{ $sectionCount }}</span></a>
                @endforeach
            </nav>
        @endif

        <label class="sticky top-[4.5rem] z-10 flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 shadow-sm focus-within:border-[#16776e]">
            <x-icon name="search" :size="18" class="text-slate-400" />
            <span class="sr-only">Search judge, court no. or matter</span>
            <input type="search" data-filter-list="court-rooms" placeholder="Search judge, court no. or matter (e.g. bail, GST)" class="min-h-12 w-full border-0 bg-transparent px-0 focus:ring-0">
        </label>

        <div id="court-rooms" class="space-y-5">
            @foreach($groups as $groupName => $benches)
                <section data-filter-item>
                    @if($groupName !== '')
                        <h2 class="mb-2 flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-slate-600">{{ $groupName }} <span class="rounded-full bg-slate-200 px-2 text-xs text-slate-700">{{ $benches->count() }}</span></h2>
                    @endif
                    <div class="grid gap-3 md:grid-cols-2">
                        @foreach($benches as $bench)
                            <article data-filter-item class="flex flex-col rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                                @php $room = $bench->roomParts(); @endphp
                                <div class="flex items-start gap-3">
                                    <div class="flex h-14 min-w-16 shrink-0 flex-col items-center justify-center rounded-xl bg-[#142235] px-2 text-white">
                                        <span class="text-[10px] font-semibold uppercase tracking-wide text-white/70">Court</span>
                                        <span class="whitespace-nowrap font-extrabold leading-none {{ mb_strlen((string) $room['number']) > 4 ? 'text-sm' : 'text-lg' }}">{{ $room['number'] ?? '—' }}</span>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        @if($bench->bench || $bench->location)
                                            <p class="text-xs font-bold uppercase tracking-wide text-[#16776e]">{{ collect([$bench->bench, $bench->location])->filter()->implode(' · ') }}</p>
                                        @endif
                                        @forelse($bench->judgeList() as $judge)
                                            <div class="flex items-center gap-2">
                                                @if($judge['photo'])
                                                    <img src="{{ $judge['photo'] }}" alt="" loading="lazy" class="h-12 w-10 shrink-0 rounded-lg object-cover ring-1 ring-slate-200">
                                                @endif
                                                <p class="leading-snug">
                                                    <span class="font-semibold text-slate-900">{{ $judge['name'] }}</span>
                                                    @if($judge['designation'])<span class="block text-xs font-medium text-slate-500">{{ $judge['designation'] }}</span>@endif
                                                </p>
                                            </div>
                                        @empty
                                            <p class="font-semibold text-slate-900">{{ $bench->court_room }}</p>
                                        @endforelse
                                        @if($room['place'])
                                            <p class="mt-1 flex items-center gap-1 text-xs font-medium text-slate-600">
                                                <x-icon name="map" :size="12" class="shrink-0 text-slate-400" /> {{ $room['place'] }}
                                            </p>
                                        @endif
                                    </div>
                                </div>

                                @if($bench->vc_meeting_id || $bench->email)
                                    <div class="mt-3 grid grid-cols-2 gap-2">
                                        @if($bench->vc_meeting_id)
                                            <div class="{{ $cell }}">
                                                <p class="{{ $cellLabel }}">VC meeting ID</p>
                                                <p class="mt-0.5 break-words text-sm font-semibold text-slate-900">{{ $bench->vc_meeting_id }}</p>
                                            </div>
                                        @endif
                                        @if($bench->email)
                                            <div class="{{ $cell }} {{ $bench->vc_meeting_id ? '' : 'col-span-2' }}">
                                                <p class="{{ $cellLabel }}">Email</p>
                                                <p class="mt-0.5 break-words text-sm">
                                                    @foreach(preg_split('/\s*[\/,;]\s*/', $bench->email) as $emailAddress)
                                                        <a href="mailto:{{ $emailAddress }}" data-direct-share class="font-semibold text-[#16776e] hover:underline">{{ $emailAddress }}</a>@if(! $loop->last)<span class="text-slate-400"> / </span>@endif
                                                    @endforeach
                                                </p>
                                            </div>
                                        @endif
                                    </div>
                                @endif

                                @if($bench->roster)
                                    <details class="group mt-3 rounded-xl bg-slate-50 px-3 py-2 text-sm">
                                        <summary class="flex cursor-pointer list-none items-center justify-between font-semibold text-slate-800">
                                            Roster · {{ count($bench->roster) }} categories
                                            <x-icon name="chevron" :size="16" class="text-slate-400 transition group-open:rotate-180" />
                                        </summary>
                                        <ol class="mt-2 list-decimal space-y-1 pl-5 text-slate-700">
                                            @foreach($bench->roster as $matter)
                                                <li>{{ $matter }}</li>
                                            @endforeach
                                        </ol>
                                    </details>
                                @endif

                                <div class="mt-auto flex flex-wrap items-center gap-2 pt-3">
                                    @if($bench->vc_link)
                                        <a href="{{ $bench->vc_link }}" target="_blank" rel="noopener noreferrer" data-direct-share
                                           class="inline-flex min-h-10 items-center gap-1.5 rounded-lg bg-[#142235] px-3.5 text-sm font-semibold text-white hover:bg-[#20334a]">
                                            <x-icon name="screen" :size="16" /> Join VC
                                        </a>
                                        <button type="button" data-copy="{{ $bench->vc_link }}" class="min-h-10 rounded-lg border border-slate-300 px-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">Copy link</button>
                                    @else
                                        <span class="inline-flex min-h-10 items-center rounded-lg border border-dashed border-slate-300 px-3 text-xs font-semibold text-slate-400">VC link coming soon</span>
                                    @endif
                                    <a href="{{ $causeList }}" rel="noopener noreferrer" class="ml-auto inline-flex min-h-10 items-center text-sm font-semibold text-[#16776e] hover:underline">Cause list</a>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endforeach
            <p data-filter-empty hidden class="rounded-2xl border border-slate-200 bg-white p-4 text-sm text-slate-500">No judge, court or matter matches your search.</p>
        </div>
    @else
        <p class="rounded-2xl border border-dashed border-slate-300 bg-white p-6 text-center text-sm text-slate-500">This list is being prepared and will appear here soon.</p>
    @endif
</div>
@endsection
