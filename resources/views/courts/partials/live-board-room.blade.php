<li data-room="{{ $room['room'] ?? '' }}" data-label="{{ $room['label'] ?? '' }}" data-status="{{ $room['status'] ?? '' }}"
    @if($mine) data-mine data-my-cases="{{ collect($mine)->pluck('case_number')->filter()->implode('|') }}" @endif
    class="group flex min-w-0 flex-col overflow-hidden rounded-xl border border-slate-200 bg-white data-mine:ring-2 data-mine:ring-inset data-mine:ring-teal-500">
    <div class="flex items-center justify-between gap-2 border-b border-slate-200 bg-slate-50 px-2.5 py-1.5 group-data-[status=running]:bg-teal-50">
        <span data-tile-label class="text-sm font-bold text-slate-900">{{ $room['label'] ?? '' }}</span>
        <span data-tile-yours @if(! $mine) hidden @endif class="rounded-full bg-teal-100 px-2 py-0.5 text-xs font-semibold text-teal-800">Yours</span>
    </div>
    <div class="flex min-w-0 flex-1 flex-col px-2.5 pb-2.5 pt-1.5">
        <p class="text-3xl font-extrabold leading-none tabular-nums text-slate-400 group-data-[status=running]:text-teal-800" data-item>{{ $room['item'] ?? '–' }}</p>
        <p class="mt-1 text-xs font-semibold text-slate-600 group-data-[status=running]:text-teal-800" data-status-text>{{ $statusText[$room['status'] ?? ''] ?? '' }}</p>
        <p class="mt-1 wrap-anywhere text-xs font-semibold text-slate-900" data-case @if(empty($room['case_number'])) hidden @endif>{{ $room['case_number'] ?? '' }}</p>
        <p class="wrap-anywhere text-xs text-slate-700" data-parties @if(empty($room['parties'])) hidden @endif>{{ $room['parties'] ?? '' }}</p>
        <p class="mt-1 wrap-anywhere text-xs text-slate-600" data-message @if(empty($room['message'])) hidden @endif>{{ $room['message'] ?? '' }}</p>
        <p class="mt-1 text-xs font-bold text-teal-800" data-now-mine hidden>Your case is being heard now</p>
        <p data-tile-matters @if(! $mine) hidden @endif class="mt-auto pt-2 text-xs text-teal-800">
            @foreach($mine as $case)
                <span class="block truncate">{{ $case['title'] }}{{ $case['time'] ? ' · '.$case['time'] : '' }}</span>
            @endforeach
        </p>
    </div>
</li>
