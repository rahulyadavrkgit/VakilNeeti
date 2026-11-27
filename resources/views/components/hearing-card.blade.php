@props(['event'])

@php
    $details = \App\Services\HearingShareText::fromEvent($event);
    $causeList = $event['cause_list'] ?? null;
    $provider = $causeList['provider'] ?? null;
    $highCourt = ($event['court_level'] ?? null) === 'high' || str_contains((string) $provider, 'high-court');
    $place = $causeList['place'] ?? ['item' => $event['item_number'] ?? null, 'total' => $event['list_total'] ?? null, 'supplementary' => null];
    $isDistrict = ($event['court_level'] ?? null) === 'district' || ($causeList['provider'] ?? null) === 'district-court';
    $listPdf = ! $isDistrict ? ($causeList['source_url'] ?? null) : null;
    $orderUrl = ! empty($event['order']) && $event['case_id']
        ? route('cases.documents.download', [$event['case_id'], $event['order'], 'preview' => 1])
        : null;
    $room = trim((string) ($details['room'] ?? ''));
    $roomNumber = preg_replace('/^(court|room)\s*(no\.?\s*)?/i', '', $room);
    $judge = $details['judge'] ?? null;
    $vcLink = $details['vc_link'] ?? null;
    $date = \Illuminate\Support\Carbon::parse($event['date']);
    $liveBoard = ! $highCourt && $date->isToday() && $room !== '' && ! $isDistrict ? \App\Services\LiveCourtBoard::boardFor($event['court'] ?? null) : null;
    $liveRoom = $liveBoard ? \App\Services\LiveCourtBoard::roomKey($liveBoard, $room) : null;
    $shareText = \App\Services\HearingShareText::single($details);
@endphp

<article {{ $attributes->class(['relative min-w-0 rounded-xl border border-slate-200 bg-white text-slate-900 shadow-sm transition hover:border-teal-300']) }}>
    <div class="p-3 sm:p-4">
        <h3>
            <a href="{{ $event['url'] }}" class="flex items-start justify-between gap-2 font-bold leading-snug before:absolute before:inset-0 before:rounded-xl before:content-['']">
                <span class="min-w-0 wrap-anywhere text-slate-900">{{ $event['title'] }}</span>
                <x-icon name="chevron" :size="17" class="mt-0.5 -rotate-90 shrink-0 text-slate-500" />
            </a>
        </h3>
        <div class="mt-1 flex flex-wrap items-baseline gap-x-2 gap-y-0.5 text-sm">
            @if($event['case_number'])
                {{-- Tap to copy the case number (for e-filing or the court's site); the rest of the card opens the case. --}}
                <button type="button" data-copy="{{ $event['case_number'] }}" data-copy-quiet title="Copy case number" aria-label="Copy case number {{ $event['case_number'] }}"
                        class="relative z-10 inline-flex min-h-8 items-center gap-1 rounded font-semibold tabular-nums hover:text-[#16776e] [&.is-copied]:text-[#16776e]">
                    {{ $event['case_number'] }}<x-icon name="copy" :size="13" class="text-slate-400" />
                </button>
            @endif
            <span class="text-slate-600">{{ $event['details'] ?: __('Hearing') }}@if($event['time']) · {{ $event['time'] }}@endif</span>
        </div>
        <div class="mt-1.5 space-y-0.5 text-sm text-slate-700">
            @if(filled($event['court'] ?? null))
                <p class="wrap-anywhere">{{ $event['court'] }}</p>
            @else
                <x-court-badge :level="$event['court_level'] ?? null" :state="$event['state_code'] ?? null" :district="$event['district'] ?? null" />
            @endif
            @if($judge)<p class="wrap-anywhere text-slate-600">{{ $judge }}</p>@endif
        </div>
        <div class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1">
            <x-case-side :side="$event['appearing_for'] ?? null" :parties="$event['appearing_for_parties'] ?? null" :case-id="$event['case_id']" :edit-url="$event['case_id'] ? route('cases.edit', $event['case_id']) : null" class="relative z-10 max-w-full whitespace-normal!" />
            @if($orderUrl)<a href="{{ $orderUrl }}" class="vn-file-link relative z-10 min-h-8 text-sm">Latest order</a>@endif
            @if($listPdf)
                <a href="{{ $listPdf }}" target="_blank" rel="noopener" class="vn-file-link relative z-10 min-h-8 text-sm">Cause list</a>
            @elseif($courtListUrl = \App\Services\SupremeCourtBenches::linkFor($event['court'] ?? null, $room, $event['date']))
                <a href="{{ $courtListUrl }}" target="_blank" rel="noopener noreferrer" data-direct-share class="vn-link relative z-10 inline-flex min-h-8 items-center text-sm font-semibold">{{ $date->isToday() ? "Today's cause list" : 'Cause list' }}</a>
            @endif
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 rounded-b-xl border-t border-slate-100 bg-slate-50 px-3 py-1 sm:px-4">
        @if($highCourt)
            <x-high-court-hearing-facts class="flex-[1_1_16rem] py-1.5" :date="$date" :court="$event['court']" :room="$room" :item="$place['item']"
                :total="$place['total']" :supplementary="$causeList['room_supplementary'] ?? $place['supplementary']" :provider="$provider" :case-number="$event['case_number']" />
        @else
            <div class="flex min-w-0 flex-[1_1_10rem] flex-wrap items-center gap-x-3 gap-y-1 py-1 text-xs text-slate-700">
                @if($roomNumber)<span>Room: <strong class="wrap-anywhere">{{ $roomNumber }}</strong></span>@endif
                @if($place['item'])
                    <span>My item: <strong class="tabular-nums">{{ $place['item'] }}</strong>@if($place['total']) of <strong class="tabular-nums">{{ $place['total'] }}</strong>@endif</span>
                @elseif($place['total'])
                    <span>Cases in list: <strong class="tabular-nums">{{ $place['total'] }}</strong></span>
                @else
                    <span>My item: <strong>*</strong></span>
                @endif
                @if($isDistrict)<span>Curr. item: <strong>N/A</strong></span>@endif
                @if($place['supplementary'])<span class="tabular-nums">Supple.: <strong>{{ trim(preg_replace('/^supple-?\s*/i', '', $place['supplementary'])) }}</strong></span>@endif
                @if($liveRoom)
                    <a href="{{ route('courts.live-board', [$liveBoard, 'room' => $room]) }}" data-sheet-frame data-live-chip data-source="{{ route('courts.live-board.data', $liveBoard) }}" data-room="{{ $liveRoom }}" data-case-number="{{ $event['case_number'] }}"
                       class="vn-link relative z-10 inline-flex min-h-8 items-center gap-1 rounded-full font-semibold data-[mine-now]:bg-red-600 data-[mine-now]:text-white!">
                        <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true"></span><span data-live-chip-text>Live board</span>
                    </a>
                @endif
            </div>
        @endif
        <div class="ml-auto flex flex-wrap items-center justify-end gap-1.5">
            {{-- Always in the same place on every card; greyed out when the court has no VC link. --}}
            <x-vc-buttons :link="$vcLink" class="relative z-10 max-w-full flex-wrap" />
            <a href="https://wa.me/?text={{ rawurlencode($shareText) }}" target="_blank" rel="noopener noreferrer" data-direct-share
               data-share-text="{{ $shareText }}" data-share-title="{{ $event['title'] }}" title="Share this hearing" aria-label="Share {{ $event['title'] }}"
               class="vn-link relative z-10 ml-auto inline-flex min-h-9 items-center gap-1.5 rounded-md px-1.5 text-xs font-semibold">
                <x-icon name="share" :size="15" /> Share
            </a>
        </div>
    </div>
</article>
