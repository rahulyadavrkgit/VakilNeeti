@php
    $boards = collect(\App\Http\Controllers\CourtLinksController::boardOptions());
@endphp

<section data-board-picker class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5" aria-labelledby="live-boards-heading">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <h2 id="live-boards-heading" class="flex items-center gap-2 text-lg font-semibold text-slate-900">
            <span class="relative flex h-2.5 w-2.5" aria-hidden="true"><span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-red-400 opacity-60 motion-reduce:animate-none"></span><span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-red-500"></span></span>
            Live display boards
        </h2>
        <div class="flex items-center gap-1">
            <button type="button" data-board-manage-toggle data-board-body aria-expanded="false" class="rounded-lg px-2.5 py-1.5 text-sm font-semibold text-[#16776e] hover:bg-teal-50">Manage</button>
            <button type="button" data-board-hide aria-expanded="true" class="rounded-lg px-2.5 py-1.5 text-sm font-semibold text-slate-500 hover:bg-slate-100">Hide</button>
        </div>
    </div>

    <div data-board-body>
        {{-- Choose which boards appear in the list (saved on this device). --}}
        <div data-board-manage hidden class="mt-3 rounded-xl border border-teal-200 bg-teal-50/60 p-3">
            <p class="text-sm font-semibold text-slate-900">Choose your boards</p>
            <p class="text-xs text-slate-600">Saved on this device.</p>
            <div class="mt-2 grid gap-1 sm:grid-cols-2">
                @foreach($boards as $board)
                    <label class="flex min-h-10 items-center gap-2 rounded-lg bg-white px-3 text-sm text-slate-800">
                        <input type="checkbox" value="{{ $board['url'] }}" data-board-choice checked class="rounded border-slate-300 text-[#16776e] focus:ring-[#16776e]">
                        {{ $board['court'] }} · {{ $board['title'] }}
                    </label>
                @endforeach
            </div>
            <div class="mt-2 flex flex-wrap gap-3 text-sm">
                <button type="button" data-board-choose-all class="font-semibold text-[#16776e] hover:underline">Tick all</button>
                <button type="button" data-board-manage-done class="ml-auto rounded-lg bg-[#16776e] px-3 py-1.5 font-semibold text-white hover:bg-[#125f58]">Done</button>
            </div>
        </div>

        <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-[minmax(0,2fr)_minmax(0,3fr)]">
            <label class="flex min-w-0 items-center gap-2 rounded-lg border border-slate-300 px-3 focus-within:border-[#16776e]">
                <x-icon name="search" :size="16" class="text-slate-400" />
                <span class="sr-only">Search courts</span>
                <input type="search" data-board-search placeholder="Search court" class="min-h-11 w-full border-0 bg-transparent px-0 text-sm focus:ring-0">
            </label>
            <label class="min-w-0">
                <span class="sr-only">Choose a court display board</span>
                <select data-board-select class="min-h-11 w-full min-w-0 rounded-lg border border-slate-300 text-sm focus:border-[#16776e] focus:ring-[#16776e]">
                    @foreach($boards->groupBy('level') as $level => $levelBoards)
                        <optgroup label="{{ $level }}">
                            @foreach($levelBoards as $board)
                                <option value="{{ $board['url'] }}" data-embeddable="{{ $board['embeddable'] ? '1' : '0' }}" data-label="{{ $board['court'] }} · {{ $board['title'] }}">{{ $board['court'] }} · {{ $board['title'] }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
            </label>
        </div>

        {{-- Shown when the court allows its board inside other websites. --}}
        <div data-board-frame-wrap hidden class="mt-3">
            <div class="mb-2 flex flex-wrap items-center gap-1 text-xs">
                <span class="mr-1 text-slate-500">Size</span>
                @foreach(['s' => 'S', 'm' => 'M', 'l' => 'L'] as $boardSize => $boardSizeLabel)
                    <button type="button" data-board-size="{{ $boardSize }}" aria-pressed="{{ $boardSize === 'm' ? 'true' : 'false' }}" aria-label="Board size {{ $boardSizeLabel }}"
                            class="h-8 w-8 rounded-lg border border-slate-200 font-semibold text-slate-600 aria-pressed:border-[#16776e] aria-pressed:bg-teal-50 aria-pressed:text-[#16776e]">{{ $boardSizeLabel }}</button>
                @endforeach
                <button type="button" data-board-fullscreen class="ml-auto inline-flex h-8 items-center gap-1 rounded-lg border border-slate-200 px-2.5 font-semibold text-slate-700 hover:bg-slate-50">
                    <x-icon name="screen" :size="14" /> Full screen
                </button>
            </div>
            <div data-board-frame-box class="vn-board-box relative overflow-hidden rounded-xl border border-slate-200 bg-white" style="height: 480px">
                <iframe data-board-frame title="Court display board" referrerpolicy="no-referrer" class="h-full w-full"></iframe>
                <button type="button" data-board-exit hidden class="absolute right-3 top-3 z-10 rounded-full bg-[#142235] px-4 py-2 text-sm font-semibold text-white shadow-lg">Close full screen</button>
            </div>
        </div>

        {{-- Shown when the court does not allow it: the board opens in a window beside VakilNiti. --}}
        <div data-board-blocked class="mt-3 rounded-xl bg-slate-50 p-4">
            <p class="text-sm font-semibold text-slate-900" data-board-name>{{ $boards->first()['court'] ?? '' }} · {{ $boards->first()['title'] ?? '' }}</p>
            <p class="mt-1 text-xs leading-relaxed text-slate-600">Opens in a side window; VakilNiti stays open.</p>
            <div class="mt-3 flex flex-wrap gap-2" data-board-open-actions>
                <button type="button" data-board-open class="inline-flex min-h-11 flex-1 items-center justify-center gap-2 rounded-lg bg-[#16776e] px-4 text-sm font-semibold text-white hover:bg-[#125f58] sm:flex-none">
                    <x-icon name="board" :size="18" /> Open live board
                </button>
                <button type="button" data-board-open="full" class="inline-flex min-h-11 flex-1 items-center justify-center gap-2 rounded-lg border border-[#16776e] px-4 text-sm font-semibold text-[#16776e] hover:bg-teal-50 sm:flex-none">
                    <x-icon name="screen" :size="18" /> Full screen
                </button>
            </div>
            <a href="{{ route('courts.display-boards') }}" class="mt-3 inline-block text-xs font-semibold text-[#16776e] hover:underline">All display boards &rarr;</a>
        </div>
    </div>

    <p data-board-collapsed hidden class="mt-1 text-sm text-slate-500">Hidden on this device. Press Show to see the boards again.</p>
</section>
