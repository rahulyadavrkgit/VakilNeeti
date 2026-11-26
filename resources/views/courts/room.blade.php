@extends('layouts.app')

@php
    $judges = $bench->judgeList();
    $firstJudge = $judges[0]['name'] ?? null;
    $roomNumber = $bench->roomParts()['number'];
    $roomPlace = $bench->roomParts()['place'];
    $heading = collect([$roomNumber ? 'Room '.$roomNumber : ($bench->court_room ?: null), $firstJudge])->filter()->implode(' · ') ?: $court['court'];
    $description = collect([
        $firstJudge,
        $judges[0]['designation'] ?? null,
        $roomNumber ? 'Room '.$roomNumber : null,
        $court['court'],
        $bench->vc_link ? 'video conference (VC) link' : null,
        $bench->vc_meeting_id ? 'Webex meeting ID '.$bench->vc_meeting_id : null,
    ])->filter()->implode(', ');
@endphp

@section('title', $heading.' · '.$court['court'])
@section('page-heading', $roomNumber ? 'Room '.$roomNumber : 'Court room')
@section('meta_description', $description.'.')

@section('content')
<div class="mx-auto max-w-3xl space-y-4">
    @include('courts.partials.breadcrumb', ['current' => $heading])

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <div class="flex items-start gap-3">
            @if($judges[0]['photo'] ?? null)
                <img src="{{ $judges[0]['photo'] }}" alt="" width="56" height="64" data-initials="{{ \App\Models\CourtBench::initials($judges[0]['name']) }}" class="h-16 w-14 shrink-0 rounded-lg border-2 border-white object-cover shadow-sm ring-1 ring-slate-200">
            @endif
            <div class="min-w-0 flex-1">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-600">{{ $court['court'] }}</p>
                <h1 class="mt-1 break-words text-2xl font-semibold uppercase text-slate-900">{{ $firstJudge ?: ($bench->court_room ?: $court['court']) }}</h1>
                @if($judges[0]['designation'] ?? null)
                    <p class="text-xs uppercase leading-relaxed text-slate-600">{{ $judges[0]['designation'] }}</p>
                @endif
            </div>
            @if($roomNumber)
                <span class="inline-flex min-h-10 max-w-28 items-center rounded-lg border border-teal-200 bg-teal-50 px-2.5 text-sm font-semibold text-teal-800">Court {{ $roomNumber }}</span>
            @endif
        </div>
        @if($roomPlace || $bench->bench || $bench->location)
            <p class="mt-1 text-sm text-slate-600">{{ collect([$bench->bench, $roomPlace, $bench->location])->filter()->implode(' · ') }}</p>
        @endif

        @if(count($judges) > 1)
            <ul class="mt-4 space-y-2">
                @foreach(array_slice($judges, 1) as $judge)
                    <li class="flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2">
                        @if($judge['photo'])
                            <img src="{{ $judge['photo'] }}" alt="" width="40" height="48" data-initials="{{ \App\Models\CourtBench::initials($judge['name']) }}" class="h-12 w-10 shrink-0 rounded-lg border-2 border-white object-cover shadow-sm ring-1 ring-slate-200">
                        @endif
                        <div class="min-w-0">
                            <p class="font-semibold uppercase text-slate-900">{{ $judge['name'] }}</p>
                            @if($judge['designation'])
                                <p class="text-xs uppercase leading-relaxed text-slate-600">{{ $judge['designation'] }}</p>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif

        <x-court-room-contact :bench="$bench" />
    </section>

    <a href="{{ $listUrl }}" class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white p-4 text-sm shadow-sm hover:border-[#16776e]">
        <span class="font-semibold text-slate-900">All rooms of {{ $court['court'] }}</span>
        <span class="shrink-0 font-semibold text-[#16776e]">Open &rarr;</span>
    </a>

    @if($neighbours->isNotEmpty())
        <section aria-labelledby="other-rooms" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <h2 id="other-rooms" class="text-sm font-bold uppercase tracking-wide text-slate-500">Other rooms here</h2>
            <ul class="mt-2 divide-y divide-slate-100">
                @foreach($neighbours as $other)
                    <li>
                        <a href="{{ $other->pageUrl() }}" class="flex items-center justify-between gap-3 py-2 text-sm hover:text-[#16776e]">
                            <span class="min-w-0 truncate font-medium text-slate-800">{{ $other->judgeList()[0]['name'] ?? $other->court_room }}</span>
                            <span class="shrink-0 text-slate-500">{{ $other->roomParts()['number'] ? 'Room '.$other->roomParts()['number'] : '' }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <p class="text-xs text-slate-500">From the court's judges &amp; VC list. Judges move on transfer; check the court's own list before a hearing.</p>
</div>
@endsection
