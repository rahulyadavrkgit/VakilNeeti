@props(['always' => false, 'date' => null, 'showDate' => true, 'showRoom' => true, 'court' => null, 'room' => null, 'item' => null, 'listTotal' => null, 'causeList' => null, 'caseNumber' => null, 'tone' => 'light', 'orderUrl' => null, 'orderDate' => null, 'district' => false, 'highCourt' => false, 'waiting' => true])

{{--
    A hearing's place in the court's list, in one line: "My item 97 of 114 · Cause list (PDF) · Now item 40".
    The court, room and judge are in the chips above it (x-court-info), so they are not repeated here.
--}}
@php
    $day = $date ? \Illuminate\Support\Carbon::parse($date) : null;
    $provider = $causeList['provider'] ?? null;
    $highCourt = $highCourt || str_contains((string) $provider, 'high-court');
    $districtBoard = $district || $provider === 'district-court';
    $room = $causeList['court'] ?? $room;
    $myItem = $causeList['item'] ?? $item;
    $listTotal = $causeList['place']['total'] ?? $causeList['total'] ?? $listTotal;
    // Board facts ("114 cases on this board", supplementary list numbers) say the total in their own words.
    $facts = array_values(array_unique($causeList['facts'] ?? []));
    $showTotal = $listTotal && ($districtBoard || ! $facts);
    $supportedBoard = $provider && isset(\App\Services\LiveCourtBoard::COURTS[$provider]) ? $provider : \App\Services\LiveCourtBoard::boardFor($court);
    $liveBoard = $day?->isToday() && $room && ! $districtBoard ? $supportedBoard : null;
    $liveRoom = $liveBoard ? \App\Services\LiveCourtBoard::roomKey($liveBoard, $room) : null;
    $listPdf = ! $districtBoard ? ($causeList['source_url'] ?? null) : null;
    $dark = $tone === 'dark';
    // Courts publish their lists a day ahead, so the "not out yet" note is for today and tomorrow only.
    $waitingNote = ! $highCourt && $day && $waiting && ! $myItem && ($day->isToday() || $day->isTomorrow());
@endphp

@if($highCourt || $districtBoard || $myItem || $listTotal || $causeList || $liveRoom || $orderUrl || ($day && ($showDate || $waitingNote)) || ($always && $room))
    <div {{ $attributes->class(['mt-2 rounded-xl border px-3 py-2 text-xs', 'border-white/15 bg-white/5 text-white' => $dark, 'border-slate-200 bg-slate-50 text-slate-800' => !$dark]) }}>
        @if($showDate && $day)
            <p class="mb-1 text-sm font-semibold">{{ $day->format('l, d F Y') }}</p>
        @endif
        @if($highCourt)
            <x-high-court-hearing-facts :date="$day" :court="$court" :room="$room" :item="$myItem" :total="$listTotal"
                :supplementary="$causeList['room_supplementary'] ?? $causeList['place']['supplementary'] ?? null" :provider="$provider" :case-number="$caseNumber" />
        @endif
        <p class="flex flex-wrap items-center gap-x-3 gap-y-1">
            @if(! $highCourt && $showRoom && $room)
                <span>Court room: <strong>{{ preg_replace('/^(court|room)\s*(no\.?)?\s*/i', '', $room) }}</strong></span>
            @endif
            @if(! $highCourt && $myItem)
                <span>My item: <strong class="text-sm">{{ $myItem }}</strong>@if($showTotal) of <strong>{{ $listTotal }}</strong>@endif</span>
            @elseif(! $highCourt && $showTotal)
                <span>Cases in list: <strong>{{ $listTotal }}</strong></span>
            @elseif($waitingNote)
                <span>My item: <strong>*</strong></span>
            @endif
            @if($districtBoard)
                <span>Curr. item: <strong>N/A</strong></span>
            @endif
            @foreach($facts as $fact)
                <span>{{ $fact }}</span>
            @endforeach
            @if(! $highCourt && $liveRoom)
                <span>Now <a href="{{ route('courts.live-board', [$liveBoard, 'room' => $room]) }}" data-sheet-frame data-live-chip data-live-compact
                    data-source="{{ route('courts.live-board.data', $liveBoard) }}" data-room="{{ $liveRoom }}" data-case-number="{{ $caseNumber }}"
                    class="font-semibold underline"><span data-live-chip-text>Live board</span></a></span>
            @endif
            @if($listPdf)
                <a href="{{ $listPdf }}" target="_blank" rel="noopener" class="vn-file-link">Cause list</a>
            @endif
            @if($orderUrl)
                <a href="{{ $orderUrl }}" class="vn-file-link">Latest order @if($orderDate)· {{ $orderDate }}@endif</a>
            @endif
        </p>
    </div>
@endif
