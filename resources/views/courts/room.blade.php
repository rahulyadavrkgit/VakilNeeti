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

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-wide text-[#16776e]">{{ $court['court'] }}</p>
        <h1 class="mt-1 text-2xl font-semibold text-slate-900">{{ $heading }}</h1>
        @if($roomPlace || $bench->bench || $bench->location)
            <p class="mt-1 text-sm text-slate-600">{{ collect([$bench->bench, $roomPlace, $bench->location])->filter()->implode(' · ') }}</p>
        @endif

        @if($judges !== [])
            <ul class="mt-4 space-y-2">
                @foreach($judges as $judge)
                    <li class="rounded-xl border border-slate-200 px-3 py-2">
                        <p class="font-semibold text-slate-900">{{ $judge['name'] }}</p>
                        @if($judge['designation'])
                            <p class="text-sm text-slate-600">{{ $judge['designation'] }}</p>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif

        <dl class="mt-4 grid gap-2 text-sm sm:grid-cols-2">
            @if($bench->vc_meeting_id)
                <div class="rounded-xl bg-slate-50 px-3 py-2">
                    <dt class="text-slate-500">Webex meeting ID</dt>
                    <dd class="flex items-center gap-2 font-semibold tabular-nums text-slate-900">
                        {{ $bench->vc_meeting_id }}
                        <button type="button" data-copy="{{ preg_replace('/\D+/', '', $bench->vc_meeting_id) }}" aria-label="Copy meeting ID" class="inline-flex h-8 items-center gap-1 rounded-full border border-slate-200 bg-white px-2 text-xs font-semibold text-slate-600"><x-icon name="copy" :size="14" /><span data-copy-label>Copy</span></button>
                    </dd>
                </div>
            @endif
            @if($bench->email)
                <div class="rounded-xl bg-slate-50 px-3 py-2">
                    <dt class="text-slate-500">Court email</dt>
                    <dd class="break-words font-semibold"><a href="mailto:{{ $bench->email }}" class="text-[#16776e] hover:underline">{{ $bench->email }}</a></dd>
                </div>
            @endif
        </dl>

        @if($bench->vc_link)
            <div class="mt-4 grid gap-2 sm:grid-cols-2">
                <a href="{{ $bench->vc_link }}" target="_blank" rel="noopener noreferrer" class="flex min-h-12 items-center justify-center gap-2 rounded-xl bg-[#16776e] px-4 text-sm font-semibold text-white hover:bg-[#125f58]">
                    <x-icon name="screen" :size="18" /> Join VC
                </a>
                <button type="button" data-copy="{{ $bench->vc_link }}" class="flex min-h-12 items-center justify-center gap-2 rounded-xl border border-slate-300 px-4 text-sm font-semibold text-slate-800 hover:border-[#16776e]">
                    <x-icon name="copy" :size="18" /><span data-copy-label>Copy VC link</span>
                </button>
            </div>
        @endif
    </section>

    <a href="{{ $listUrl }}" class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white p-4 text-sm shadow-sm hover:border-[#16776e]">
        <span class="font-semibold text-slate-900">All rooms of {{ $court['court'] }}</span>
        <span class="shrink-0 font-semibold text-[#16776e]">Open &rarr;</span>
    </a>

    @if($neighbours->isNotEmpty())
        <section aria-labelledby="other-rooms" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
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

    <p class="text-xs text-slate-500">From the court's official judges &amp; VC list. Judges move on transfer; check the court's own list before a hearing.</p>
</div>
@endsection
