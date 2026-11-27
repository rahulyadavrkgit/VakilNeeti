@props(['date' => null, 'court' => null, 'room' => null, 'item' => null, 'total' => null, 'supplementary' => null, 'provider' => null, 'caseNumber' => null])

{{-- A High Court hearing's place in the list, in one line the way it is shared: "Room No.: 23  My item: 66 of 69  Curr. item: *  Supple.: 16 items · 70–85". --}}
@php
    $roomNumber = preg_replace('/^(?:court|room)\b\s*(?:no\.?\s*)?[:.-]?\s*/i', '', trim((string) $room));
    $supplement = trim(preg_replace('/^supple-?\s*/i', '', (string) $supplementary));
    $board = isset(\App\Services\LiveCourtBoard::COURTS[$provider]) ? $provider : \App\Services\LiveCourtBoard::boardFor($court);
    $liveRoom = $board && $date && \Illuminate\Support\Carbon::parse($date)->isToday()
        ? \App\Services\LiveCourtBoard::roomKey($board, $room)
        : null;
    $value = 'wrap-anywhere font-semibold tabular-nums text-slate-900';
@endphp

<dl {{ $attributes->class(['flex min-w-0 flex-wrap items-baseline gap-x-4 gap-y-1 text-xs text-slate-600']) }} data-high-court-listing>
    <div class="flex min-w-0 items-baseline gap-1">
        <dt>Room No.:</dt>
        <dd class="{{ $value }} text-sm">{{ filled($roomNumber) ? $roomNumber : '—' }}</dd>
    </div>
    <div class="flex min-w-0 items-baseline gap-1">
        <dt>My item:</dt>
        <dd class="{{ $value }} text-sm">{{ filled($item) ? $item : '*' }}@if(filled($item) && filled($total)) of {{ $total }}@endif</dd>
    </div>
    <div class="flex min-w-0 items-baseline gap-1">
        <dt>Curr. item:</dt>
        <dd class="{{ $value }} text-sm">
            @if($liveRoom)
                <a href="{{ route('courts.live-board', [$board, 'room' => $room]) }}" data-sheet-frame data-live-chip data-live-item-only
                   data-source="{{ route('courts.live-board.data', $board) }}" data-room="{{ $liveRoom }}" data-case-number="{{ $caseNumber }}" data-my-item="{{ $item }}"
                   title="Open the live board for Court {{ $roomNumber }}" class="vn-link"><span data-live-chip-text>*</span></a>
            @else
                *
            @endif
        </dd>
    </div>
    <div class="flex min-w-0 items-baseline gap-1">
        <dt>Supple.:</dt>
        <dd class="{{ $value }}">{{ filled($supplement) ? $supplement : '—' }}</dd>
    </div>
</dl>
