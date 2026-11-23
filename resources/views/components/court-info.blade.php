@props(['court' => null, 'room' => null, 'judge' => null, 'vcLink' => null])

{{-- Where a matter is heard, at a glance: court, room, judge and the VC link. Wraps on small screens instead of cutting off. --}}
@php
    $room = trim((string) $room);
    $roomLabel = $room !== '' && ! preg_match('/^(court|room)\b/i', $room) ? 'Room '.$room : $room;
@endphp
@if($court || $room !== '' || $judge || $vcLink)
    <span {{ $attributes->merge(['class' => 'flex min-w-0 flex-wrap items-center gap-1 text-[11px]']) }}>
        @if($court)
            <span class="inline-flex min-w-0 max-w-full items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 font-medium text-slate-700">
                <x-icon name="building" :size="12" class="shrink-0 text-slate-400" /><span class="truncate">{{ $court }}</span>
            </span>
        @endif
        @if($room !== '')
            <span class="inline-flex shrink-0 items-center rounded-full bg-[#142235] px-2 py-0.5 font-semibold text-white">{{ $roomLabel }}</span>
        @endif
        @if($judge)
            <span class="inline-flex min-w-0 max-w-full items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 font-semibold text-amber-900 ring-1 ring-inset ring-amber-200">
                <x-icon name="gavel" :size="12" class="shrink-0" /><span class="truncate">{{ $judge }}</span>
            </span>
        @endif
        @if($vcLink)
            <a href="{{ $vcLink }}" target="_blank" rel="noopener noreferrer" data-direct-share
               class="inline-flex shrink-0 items-center gap-1 rounded-full bg-[#16776e] px-2 py-0.5 font-semibold text-white hover:bg-[#125f58]">
                <x-icon name="screen" :size="12" /> Join VC
            </a>
        @endif
    </span>
@endif
