@props(['event', 'label' => null, 'labelTone' => 'teal', 'labelId' => null])

{{--
    One hearing, laid out the way advocates read it (Home):
        PRAHLAD vs STATE OF U.P.  [Set our side]
        [Allahabad HC]
        Case No - BAIL/5221/2010 · 10:30
        Justice Vani Ranjan Agrawal
        ITEM 97 @ 114 · Supple-2 56-70(15) · Court Room 90 · [PDF Cause list]
        [Join VC] [VC link]                                        [Share] (on hover)
    `event` is a CalendarEventService hearing; room, judge and VC link are filled from the court lists where missing.
--}}
@php
    $details = \App\Services\HearingShareText::fromEvent($event);
    $causeList = $event['cause_list'] ?? null;
    $place = $causeList['place'] ?? ['item' => $event['item_number'] ?? null, 'total' => $event['list_total'] ?? null, 'supplementary' => null];
    $isDistrict = ($event['court_level'] ?? null) === 'district' || ($causeList['provider'] ?? null) === 'district-court';
    $listPdf = ! $isDistrict ? ($causeList['source_url'] ?? null) : null;
    $room = trim((string) ($details['room'] ?? ''));
    $roomText = $room !== '' ? 'Court Room '.preg_replace('/^(court|room)\s*(no\.?)?\s*/i', '', $room) : null;
    $judge = $details['judge'] ?? null;
    $vcLink = $details['vc_link'] ?? null;
    $date = \Illuminate\Support\Carbon::parse($event['date']);
    // A bench or court name adds something next to the badge; "Allahabad High Court" would only repeat it.
    $bench = trim((string) ($event['court'] ?? ''));
    $bench = preg_match('/^(the\s+)?(supreme court(\s+of\s+india)?|high court(\s+of\s+[\p{L} &.]+)?|[\p{L} &.]+\s+high court)$/iu', $bench) ? null : ($bench ?: null);
    $liveBoard = $date->isToday() && $room !== '' && ! $isDistrict ? \App\Services\LiveCourtBoard::boardFor($event['court'] ?? null) : null;
    $liveRoom = $liveBoard ? \App\Services\LiveCourtBoard::roomKey($liveBoard, $room) : null;
    $shareText = \App\Services\HearingShareText::single($details);
    $waiting = ! $place['item'] && ($date->isToday() || $date->isTomorrow());
@endphp


{{-- The whole card opens the case (the title's link covers it); buttons and links inside sit above it. --}}
<article {{ $attributes->class(['group relative min-w-0 rounded-2xl border border-slate-200 bg-white p-3.5 text-slate-900 shadow-sm transition hover:border-teal-300 sm:p-4']) }}>
    @if($label)
        <p @if($labelId) id="{{ $labelId }}" @endif class="mb-1 text-xs font-bold uppercase tracking-wide {{ $labelTone === 'red' ? 'text-red-600' : 'text-[#16776e]' }}">{{ $label }}</p>
    @endif

    <div class="flex items-start justify-between gap-2">
        <p class="flex min-w-0 flex-wrap items-center gap-1.5 font-semibold leading-snug">
            <a href="{{ $event['url'] }}" class="min-w-0 break-words before:absolute before:inset-0 before:rounded-2xl before:content-[''] hover:text-[#16776e]">{{ $event['title'] }}</a>
            <span class="relative z-10"><x-case-side :side="$event['appearing_for'] ?? null" :parties="$event['appearing_for_parties'] ?? null" :case-id="$event['case_id']" :edit-url="$event['case_id'] ? route('cases.edit', $event['case_id']) : null" /></span>
        </p>
        <a href="{{ $event['url'] }}" class="relative z-10 shrink-0 whitespace-nowrap pt-0.5 text-xs font-semibold text-[#16776e] hover:underline">{{ __('Open case') }} &rarr;</a>
    </div>

    <p class="mt-1 flex min-w-0 flex-wrap items-center gap-x-2 gap-y-1 text-sm">
        <x-court-badge :level="$event['court_level'] ?? null" :state="$event['state_code'] ?? null" :district="$event['district'] ?? null" class="shrink-0" />
        @if($event['case_number'])<span class="font-semibold tabular-nums">{{ $event['case_number'] }}</span>@endif
        <span class="text-slate-600">{{ $event['details'] ?: __('Hearing') }}@if($event['time']) · {{ $event['time'] }}@endif</span>
        @if($bench)<span class="min-w-0 truncate text-xs text-slate-500">{{ $bench }}</span>@endif
    </p>

    <div class="mt-1 space-y-0.5 text-sm">
        @if($judge)
            <p class="text-slate-700">{{ $judge }}</p>
        @endif
        @if($place['item'] || $roomText || $listPdf || $liveRoom)
            <p class="flex flex-wrap items-center gap-x-3 gap-y-1 font-semibold">
                @if($place['item'])
                    <span class="tabular-nums">ITEM {{ $place['item'] }}@if($place['total']) <span class="font-normal text-slate-500">@</span> {{ $place['total'] }}@endif</span>
                @endif
                @if($place['supplementary'])<span class="tabular-nums">{{ $place['supplementary'] }}</span>@endif
                @if($roomText)<span>{{ $roomText }}</span>@endif
                @if($liveRoom)
                    <a href="{{ route('courts.live-board', [$liveBoard, 'room' => $room]) }}" data-sheet-frame data-live-chip data-source="{{ route('courts.live-board.data', $liveBoard) }}" data-room="{{ $liveRoom }}" data-case-number="{{ $event['case_number'] }}"
                       class="relative z-10 inline-flex items-center gap-1 rounded-full bg-red-50 px-2 py-0.5 text-xs font-semibold text-red-700 ring-1 ring-inset ring-red-200 data-[mine-now]:bg-red-600 data-[mine-now]:text-white">
                        <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true"></span><span data-live-chip-text>Live board</span>
                    </a>
                @endif
                @if($listPdf)<a href="{{ $listPdf }}" target="_blank" rel="noopener" class="vn-file-link relative z-10 text-sm">Cause list</a>@endif
                @if(! empty($event['order']) && $event['case_id'])
                    <a href="{{ route('cases.documents.download', [$event['case_id'], $event['order'], 'preview' => 1]) }}" class="vn-file-link relative z-10 text-sm">Latest order</a>
                @endif
            </p>
        @endif
        @if($waiting)
            <p class="text-xs text-slate-500">The item number will show when the court's list is out.</p>
        @endif
    </div>

    {{-- Bottom row: Join VC and Copy VC link always (greyed out until the court has a VC link); Share at the right. --}}
    <div class="mt-2.5 flex items-center gap-1.5 text-xs">
        <x-vc-buttons :link="$vcLink" class="relative z-10" />
        <a href="https://wa.me/?text={{ rawurlencode($shareText) }}" target="_blank" rel="noopener noreferrer" data-direct-share
           data-share-text="{{ $shareText }}" data-share-title="{{ $event['title'] }}" title="Share this hearing" aria-label="Share {{ $event['title'] }}"
           class="relative z-10 ml-auto inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 transition hover:border-[#16776e] hover:text-[#16776e] [@media(hover:hover)]:opacity-0 [@media(hover:hover)]:group-hover:opacity-100 [@media(hover:hover)]:group-focus-within:opacity-100">
            <x-icon name="share" :size="15" />
        </a>
    </div>
</article>
