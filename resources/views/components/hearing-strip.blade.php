@props(['always' => false, 'date' => null, 'showDate' => true, 'showRoom' => true, 'court' => null, 'room' => null, 'item' => null, 'listTotal' => null, 'causeList' => null, 'caseNumber' => null, 'tone' => 'light', 'orderUrl' => null, 'orderDate' => null, 'district' => false])

@php
    $day = $date ? \Illuminate\Support\Carbon::parse($date) : null;
    $provider = $causeList['provider'] ?? null;
    $districtBoard = $district || $provider === 'district-court';
    $room = $causeList['court'] ?? $room;
    $myItem = $causeList['item'] ?? $item;
    $listTotal = $causeList['total'] ?? $listTotal;
    $facts = $causeList['facts'] ?? [];
    $listLabel = isset($causeList['list']) ? preg_replace('/\s*·\s*\d{2}-\d{2}-\d{4}$/u', '', $causeList['list']) : null;
    $supportedBoard = $provider && isset(\App\Services\LiveCourtBoard::COURTS[$provider]) ? $provider : \App\Services\LiveCourtBoard::boardFor($court);
    $liveBoard = $day?->isToday() && $room && ! $districtBoard ? $supportedBoard : null;
    $liveRoom = $liveBoard ? \App\Services\LiveCourtBoard::roomKey($liveBoard, $room) : null;
    $dark = $tone === 'dark';
@endphp

@if($myItem || $listTotal || $causeList || $liveRoom || $orderUrl || ($always && $room))
    <div {{ $attributes->class(['mt-2 space-y-1.5 rounded-xl border px-3 py-2 text-sm', 'border-white/15 bg-white/5 text-white' => $dark, 'border-slate-200 bg-slate-50 text-slate-800' => !$dark]) }}>
        @if($showDate && $day)
            <p class="font-semibold">{{ $day->format('l, d F Y') }}</p>
        @endif
        @if(!$districtBoard && $causeList)
            <p class="flex flex-wrap items-center gap-x-2 text-xs">
                <span class="font-semibold">{{ $causeList['court_short'] }} cause list</span>
                @if($listLabel && ! str_contains(strtolower($listLabel), 'supplementary'))<span>{{ $listLabel }}</span>@endif
                @if(!empty($causeList['source_url']))
                    <a href="{{ $causeList['source_url'] }}" target="_blank" rel="noopener" class="underline">Source PDF</a>
                @endif
            </p>
        @endif
        <dl class="flex flex-wrap gap-x-4 gap-y-1 text-xs">
            @if($showRoom && !$districtBoard && $room)
                <div class="flex gap-1"><dt>Court room</dt><dd class="font-semibold">{{ preg_replace('/^(court|room)\s*(no\.?)?\s*/i', '', $room) }}</dd></div>
            @endif
            @if($myItem)
                <div class="flex gap-1"><dt>My item</dt><dd class="font-semibold">{{ $myItem }}</dd></div>
            @endif
            @if($listTotal && ($districtBoard || !$facts))
                <div class="flex gap-1"><dt>Cases in list</dt><dd class="font-semibold">{{ $listTotal }}</dd></div>
            @endif
            @if($liveRoom)
                <div class="flex gap-1"><dt>Now</dt>
                    <dd><a href="{{ route('courts.live-board', [$liveBoard, 'room' => $room]) }}" data-sheet-frame data-live-chip data-live-compact
                        data-source="{{ route('courts.live-board.data', $liveBoard) }}" data-room="{{ $liveRoom }}" data-case-number="{{ $caseNumber }}"
                        class="font-semibold underline"><span data-live-chip-text>Live board</span></a></dd>
                </div>
            @endif
        </dl>
        @if(!$districtBoard && $facts)
            <ul class="flex flex-wrap gap-x-3 gap-y-1 text-xs">
                @foreach(array_unique($facts) as $fact)<li>{{ $fact }}</li>@endforeach
            </ul>
        @endif
        @if($orderUrl)
            <a href="{{ $orderUrl }}" class="inline-flex min-h-11 items-center text-xs font-semibold underline">Latest saved order @if($orderDate) · {{ $orderDate }}@endif</a>
        @endif
    </div>
@endif
