@props(['court' => null, 'room' => null, 'judge' => null, 'vcLink' => null, 'complex' => null, 'designation' => null, 'share' => null, 'live' => null])

{{--
    Where a matter is heard, at a glance: court, room, judge and the VC link. Wraps on small screens instead of cutting off.
    With the case's court designation and establishment (`designation`, `complex`), whatever the case lacks is filled
    from the official court lists (App\Services\CourtBenchMatcher); the case's own entries always win.
    For a hearing today in a court with a live board in VakilNiti (Supreme Court, Delhi High Court), a chip shows the item
    running in that room now (filled by resources/js/ui-actions.js). `live` forces it on or off; by default it follows `share.date`.
    With `share` (title, case_number, side, parties, date, time, details), it also offers Copy VC link and Share,
    whose message carries the same court, room, judge and VC link (App\Services\HearingShareText).
--}}
@php
    $fromList = [];
    if (($complex || $designation) && (! trim((string) $room) || ! $judge || ! $vcLink)) {
        $bench = app(\App\Services\CourtBenchMatcher::class)->find($designation ?? $court, $complex, $judge);
        if ($bench) {
            $listJudge = $bench->judgeList()[0]['name'] ?? null;
            if (! trim((string) $room) && $bench->court_room) { $room = $bench->court_room; $fromList[] = 'room'; }
            if (! $judge && $listJudge) { $judge = $listJudge; $fromList[] = 'judge'; }
            if (! $vcLink && $bench->vc_link) { $vcLink = $bench->vc_link; $fromList[] = 'vc'; }
        }
    }
    $listNote = 'From the official court list';
    $room = trim((string) $room);
    $roomLabel = $room !== '' && ! preg_match('/^(court|room)\b/i', $room) ? 'Room '.$room : $room;
    $isToday = $live ?? (is_array($share) && ! empty($share['date']) && rescue(fn () => \Illuminate\Support\Carbon::parse($share['date'])->isToday(), false, false));
    $liveBoard = $isToday && $room !== '' ? \App\Services\LiveCourtBoard::boardFor($designation ?? $court) : null;
    $liveRoom = $liveBoard ? \App\Services\LiveCourtBoard::roomKey($liveBoard, $room) : null;
    $shareText = is_array($share)
        ? \App\Services\HearingShareText::single($share + ['court' => $court, 'room' => $room, 'judge' => $judge, 'vc_link' => $vcLink])
        : null;
    $iconButton = 'inline-flex h-8 min-w-8 shrink-0 items-center justify-center gap-1 rounded-full border border-slate-200 bg-white px-2 font-semibold text-slate-600 hover:border-[#16776e] hover:text-[#16776e] [&.is-copied]:border-[#16776e] [&.is-copied]:text-[#16776e]';
@endphp
@if($court || $room !== '' || $judge || $vcLink || $shareText)
    <span {{ $attributes->merge(['class' => 'flex min-w-0 flex-wrap items-center gap-1 text-[11px]']) }}>
        @if($court)
            <span class="inline-flex min-w-0 max-w-full items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 font-medium text-slate-700">
                <x-icon name="building" :size="12" class="shrink-0 text-slate-400" /><span class="truncate">{{ $court }}</span>
            </span>
        @endif
        @if($room !== '')
            <span class="inline-flex shrink-0 items-center rounded-full bg-[#142235] px-2 py-0.5 font-semibold text-white" @if(in_array('room', $fromList, true)) title="{{ $listNote }}" @endif>{{ $roomLabel }}</span>
        @endif
        @if($judge)
            <span class="inline-flex min-w-0 max-w-full items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 font-semibold text-amber-900 ring-1 ring-inset ring-amber-200" @if(in_array('judge', $fromList, true)) title="{{ $listNote }}" @endif>
                <x-icon name="gavel" :size="12" class="shrink-0" /><span class="truncate">{{ $judge }}</span>
            </span>
        @endif
        @if($liveRoom)
            <a href="{{ route('courts.live-board', [$liveBoard, 'room' => $room]) }}" data-live-chip data-source="{{ route('courts.live-board.data', $liveBoard) }}" data-room="{{ $liveRoom }}" data-case-number="{{ is_array($share) ? ($share['case_number'] ?? '') : '' }}"
               title="Live display board for this court" class="inline-flex shrink-0 items-center gap-1 rounded-full bg-red-50 px-2 py-0.5 font-semibold text-red-700 ring-1 ring-inset ring-red-200 hover:bg-red-100 data-[mine-now]:bg-red-600 data-[mine-now]:text-white">
                <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true"></span><span data-live-chip-text>Live board</span>
            </a>
        @endif
        @if($vcLink)
            <a href="{{ $vcLink }}" target="_blank" rel="noopener noreferrer" data-direct-share @if(in_array('vc', $fromList, true)) title="{{ $listNote }}" @endif
               class="inline-flex shrink-0 items-center gap-1 rounded-full bg-[#16776e] px-2 py-0.5 font-semibold text-white hover:bg-[#125f58]">
                <x-icon name="screen" :size="12" /> Join VC
            </a>
        @endif
        @if($shareText)
            <span class="inline-flex shrink-0 items-center gap-1">
                @if($vcLink)
                    <button type="button" data-copy="{{ $vcLink }}" title="Copy VC link" aria-label="Copy VC link" class="{{ $iconButton }}">
                        <x-icon name="copy" :size="14" /><span data-copy-label class="sr-only sm:not-sr-only">VC link</span>
                    </button>
                @endif
                <a href="https://wa.me/?text={{ rawurlencode($shareText) }}" target="_blank" rel="noopener noreferrer" data-direct-share
                   data-share-text="{{ $shareText }}" data-share-title="{{ $share['title'] ?? 'Case' }}" title="Share case details" aria-label="Share case details" class="{{ $iconButton }}">
                    <x-icon name="share" :size="14" /><span class="sr-only sm:not-sr-only">Share</span>
                </a>
            </span>
        @endif
    </span>
@endif
