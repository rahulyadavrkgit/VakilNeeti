@extends('layouts.app')

@section('title', $court['court'].': '.$court['list'])
@section('meta_description', $court['list'].' of the '.$court['court'].': benches, judges, court room, roster, VC link and meeting ID in one searchable page.')
@section('page-heading', $court['list'])

@php
    $chip = 'inline-flex min-h-9 shrink-0 items-center gap-1.5 rounded-full px-3.5 text-sm font-semibold transition';
    $visibleRooms = $groups->sum(fn ($rooms) => $rooms->count());
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

        <label class="sticky top-[calc(var(--vn-header-height)+0.5rem)] z-10 flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 shadow-sm focus-within:border-[#16776e]">
            <x-icon name="search" :size="18" class="text-slate-400" />
            <span class="sr-only">Search judge, court no. or matter</span>
            <input type="search" data-filter-list="court-rooms" placeholder="Judge, room number or roster" class="min-h-12 w-full border-0 bg-transparent px-0 focus:ring-0">
        </label>

        <div id="court-rooms" class="space-y-4">
            <p data-filter-count role="status" class="text-sm font-medium text-slate-600">{{ $visibleRooms }} {{ $visibleRooms === 1 ? 'room' : 'rooms' }}</p>
            @foreach($groups as $groupName => $benches)
                <section data-filter-group>
                    @if($groupName !== '')
                        <h2 class="mb-2 flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-slate-600">{{ $groupName }} <span class="rounded-full bg-slate-200 px-2 text-xs text-slate-700">{{ $benches->count() }}</span></h2>
                    @endif
                    <div class="grid gap-3 md:grid-cols-2">
                        @foreach($benches as $bench)
                            @php
                                $room = $bench->roomParts();
                                $judges = $bench->judgeList();
                                $roomUrl = $bench->pageUrl();
                                $searchTerms = collect([$bench->court_room, $bench->bench, $bench->location])
                                    ->merge(collect($judges)->flatMap(fn ($judge) => [$judge['name'], $judge['designation']]))
                                    ->merge($bench->roster ?? [])->filter()->implode(' ');
                            @endphp
                            <article data-filter-item data-filter-room="{{ $room['number'] }}" data-filter-text="{{ $searchTerms }}"
                                     class="flex min-w-0 flex-col rounded-xl border border-slate-200 bg-white p-3 shadow-sm transition hover:border-teal-300 focus-within:border-teal-500 sm:p-4">
                                <div class="flex min-w-0 items-start gap-2.5">
                                    <div class="min-w-0 flex-1 space-y-2">
                                        @forelse($judges as $judge)
                                            <div class="flex min-w-0 items-start gap-2.5">
                                                @if($judge['photo'])
                                                    <img src="{{ $judge['photo'] }}" alt="" width="56" height="64" loading="lazy" class="h-16 w-14 shrink-0 rounded-lg border-2 border-white object-cover shadow-sm ring-1 ring-slate-200">
                                                @endif
                                                <div class="min-w-0">
                                                    <a href="{{ $roomUrl }}" class="vn-link block wrap-anywhere text-base font-bold uppercase leading-snug">{{ $judge['name'] }}</a>
                                                    @if($judge['designation'])<p class="mt-0.5 wrap-anywhere text-xs uppercase leading-relaxed text-slate-600">{{ $judge['designation'] }}</p>@endif
                                                </div>
                                            </div>
                                        @empty
                                            <a href="{{ $roomUrl }}" class="vn-link block wrap-anywhere font-semibold">{{ $bench->court_room ?: 'Room details' }}</a>
                                        @endforelse
                                    </div>
                                    @if($room['number'])
                                        <a href="{{ $roomUrl }}" aria-label="Open Court {{ $room['number'] }}"
                                           class="flex min-h-11 max-w-24 shrink-0 flex-col items-center justify-center rounded-lg border border-teal-200 bg-teal-50 px-2 py-1.5 text-center text-teal-800 hover:border-teal-500">
                                            <span class="text-xs font-medium">Room</span>
                                            <span class="wrap-anywhere text-base font-bold tabular-nums">{{ $room['number'] }}</span>
                                        </a>
                                    @endif
                                </div>
                                @if($bench->bench || $bench->location || $room['place'])
                                    <p class="mt-2 flex items-start gap-1.5 text-xs text-slate-600">
                                        <x-icon name="map" :size="14" class="shrink-0" />
                                        <span class="min-w-0 wrap-anywhere">{{ collect([$bench->bench, $bench->location, $room['place']])->filter()->unique()->implode(' · ') }}</span>
                                    </p>
                                @endif

                                <x-court-room-contact :bench="$bench" />

                                @if($bench->roster)
                                    <details class="group mt-2 rounded-lg border border-slate-200 bg-slate-50 px-2.5 text-sm">
                                        <summary class="flex min-h-10 cursor-pointer list-none items-center justify-between font-semibold text-slate-800 [&::-webkit-details-marker]:hidden">
                                            Roster · {{ count($bench->roster) }} categories
                                            <x-icon name="chevron" :size="16" class="text-slate-400 transition group-open:rotate-180" />
                                        </summary>
                                        <ol class="list-decimal space-y-1 pb-3 pl-5 text-slate-700">
                                            @foreach($bench->roster as $matter)
                                                <li>{{ $matter }}</li>
                                            @endforeach
                                        </ol>
                                    </details>
                                @endif

                                <div class="mt-auto pt-2">
                                    <a href="{{ $causeList }}" rel="noopener noreferrer" class="vn-link inline-flex min-h-9 items-center gap-1 text-sm font-semibold">
                                        Cause list <span aria-hidden="true">&rarr;</span>
                                    </a>
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
