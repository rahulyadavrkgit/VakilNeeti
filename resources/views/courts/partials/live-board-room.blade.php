{{-- One court on the live board, coloured like the Delhi High Court's own board for every court: black, a yellow
     line, the court number on blue, the running item in red. The same in light and dark mode. Your courts get a green
     outline; the copy button copies the court, the item now and the case. --}}
<li data-room="{{ $room['room'] ?? '' }}" data-label="{{ $room['label'] ?? '' }}" data-status="{{ $room['status'] ?? '' }}"
    @if($mine) data-mine data-my-cases="{{ collect($mine)->pluck('case_number')->filter()->implode('|') }}" @endif
    @if(isset($room['room']) && $room['room'] === $focusedRoom) data-focused @endif
    @if($showMyCourts && ! $mine) hidden @endif
    class="group flex min-w-0 flex-col overflow-hidden border border-[#ffd400] bg-black text-white data-mine:outline-[3px] data-mine:outline-[#3ee07f] data-mine:outline data-mine:-outline-offset-[3px] data-focused:border-2 data-focused:border-white">
    <div class="flex items-center justify-between gap-1.5 bg-[#0066ff] py-0.5 pl-2.5 pr-0.5">
        <span data-tile-label class="min-w-0 truncate text-xs font-extrabold uppercase tracking-wide text-white">{{ $room['label'] ?? '' }}</span>
        <span class="flex shrink-0 items-center gap-1">
            <span data-tile-yours @if(! $mine) hidden @endif class="rounded-full bg-[#3ee07f] px-2 py-0.5 text-[10px] font-black uppercase text-black">Yours</span>
            <button type="button" data-tile-copy aria-label="Copy {{ $room['label'] ?? 'this court' }}" title="Copy court, item and case"
                    class="inline-flex size-8 items-center justify-center rounded-md text-white/85 hover:bg-white/15 hover:text-white [&.is-copied]:text-[#3ee07f]">
                <x-icon name="copy" :size="15" />
            </button>
        </span>
    </div>
    <div class="flex min-w-0 flex-1 flex-col px-2.5 pb-2.5 pt-1.5">
        <div data-live-details>
            <p class="vn-board-item text-4xl leading-none tabular-nums text-white/30 group-data-[status=running]:text-[#ff2b2b]" data-item
               @if($board['ok'] && ($room['status'] ?? null) === 'running' && filled($room['item'])) data-live-current @endif>{{ $room['item'] ?? '–' }}</p>
            <p class="mt-1 text-xs font-semibold text-white/55 group-data-[status=running]:text-[#ffd400]" data-status-text>{{ $statusText[$room['status'] ?? ''] ?? '' }}</p>
            <p class="mt-1 wrap-anywhere text-xs font-semibold text-white" data-case @if(empty($room['case_number'])) hidden @endif>{{ $room['case_number'] ?? '' }}</p>
            <p class="line-clamp-2 wrap-anywhere text-xs text-white/75" data-parties @if(empty($room['parties'])) hidden @endif>{{ $room['parties'] ?? '' }}</p>
            <p class="mt-1 wrap-anywhere text-xs italic text-[#ffd400]" data-message @if(empty($room['message'])) hidden @endif>{{ $room['message'] ?? '' }}</p>
            <p class="mt-1 text-xs font-black text-[#3ee07f]" data-now-mine hidden>Your case is being heard now</p>
        </div>
        <p data-tile-matters @if(! $mine) hidden @endif class="mt-auto pt-1.5 text-xs text-[#3ee07f]">
            @foreach($mine as $case)
                <span class="block truncate">{{ $case['title'] }}{{ $case['time'] ? ' · '.$case['time'] : '' }}</span>
            @endforeach
        </p>
    </div>
</li>
