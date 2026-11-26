{{--
    A court's live display board inside VakilNiti, built from the data the court publishes for its own public board
    (App\Services\LiveCourtBoard). A page of its own, without the menu, so the board viewer can show it in a frame;
    it refreshes itself every 10 seconds. The signed-in person's courts today are highlighted.
--}}
@php
    $statusText = ['running' => 'Running', 'not_in_session' => 'Not in session', 'exhausted' => 'Board over'];
    $updated = $board['updated_at'] ? \Illuminate\Support\Carbon::parse($board['updated_at'])->timezone(config('app.timezone')) : null;
@endphp
<!DOCTYPE html>
<html lang="en-IN" data-theme="{{ auth()->user()?->preference('theme') ?? 'system' }}" @auth data-theme-saved @endauth>
<head>
    @include('layouts.partials.theme-script')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>{{ $board['short'] }} live board | VakilNiti</title>
    <meta name="description" content="{{ $board['name'] }} live display board: the item running in every court, refreshed every 10 seconds.">
    <meta name="theme-color" content="#142235">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo-mark.svg') }}">
    @vite(['resources/css/app.css'])
    <style nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
        body { background: #f6f5f1; color: #172536; font-family: var(--font-sans), sans-serif; font-variant-numeric: tabular-nums; }
        html.dark body { background: #0d1524; color: #e3e9f1; }
        .vn-board-item { font-family: "Arial Black", Gadget, var(--font-sans), sans-serif; font-weight: 900; }
    </style>
</head>
<body class="min-h-screen">
<main class="mx-auto max-w-7xl p-3 sm:p-4" data-live-board="{{ $board['court'] }}" data-live-source="{{ route('courts.live-board.data', $board['court']) }}">
    <header class="flex flex-wrap items-center justify-between gap-2">
        <div class="min-w-0">
            <a href="{{ route('courts.display-boards') }}" data-live-back hidden class="mb-1 inline-flex min-h-9 items-center text-sm font-semibold text-[#16776e] hover:underline">&larr; VakilNiti display boards</a>
            <h1 class="flex items-center gap-2 text-lg font-semibold text-slate-900 sm:text-xl">
                <span class="relative flex h-2.5 w-2.5" aria-hidden="true"><span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-red-400 opacity-60 motion-reduce:animate-none"></span><span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-red-500"></span></span>
                {{ $board['short'] }} · Live board
            </h1>
            <p class="text-xs text-slate-500">
                <time datetime="{{ today()->toDateString() }}">{{ today()->format('l, d F Y') }}</time>
                <span aria-hidden="true">&middot;</span>
                <span data-live-updated>{{ $updated ? 'Updated '.$updated->format('H:i:s') : 'Waiting for the court' }}</span>
                <span data-live-stale @if($board['ok']) hidden @endif class="ml-1 font-semibold text-amber-700">· Court site not answering, showing the last known board</span>
            </p>
        </div>
        <label class="inline-flex min-h-10 cursor-pointer items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700">
            <input type="checkbox" data-live-sitting-only class="rounded border-slate-300 text-[#16776e] focus:ring-[#16776e]">
            Sitting courts only
        </label>
    </header>

    @if($myRooms !== [])
        <p class="mt-2 rounded-xl bg-teal-50 px-3 py-2 text-sm text-teal-900 ring-1 ring-inset ring-teal-200">
            <strong>Your courts today:</strong>
            {{ collect(array_keys($myRooms))->map(fn ($room) => collect($board['rooms'])->firstWhere('room', $room)['label'] ?? 'Court '.$room)->implode(', ') }}
            are outlined below.
        </p>
    @endif

    {{-- Coloured like the court's own display board: black, yellow lines, the court number on blue, the item in red. --}}
    <ul class="vn-court-board mt-3 grid grid-cols-2 gap-1 rounded-xl bg-black p-1 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6" data-live-rooms>
        @forelse($board['rooms'] as $room)
            @php
                $mine = $myRooms[$room['room']] ?? null;
                $isRunning = $room['status'] === 'running';
            @endphp
            <li data-room="{{ $room['room'] }}" data-label="{{ $room['label'] }}" data-status="{{ $room['status'] }}"
                @if($mine !== null) data-mine data-my-cases="{{ collect($mine)->pluck('case_number')->filter()->implode('|') }}" @endif
                class="flex min-w-0 flex-col overflow-hidden border border-[#ffd400] bg-black text-white {{ $mine !== null ? 'outline outline-[3px] outline-[#3ee07f]' : '' }}">
                <div class="flex items-center justify-between gap-2 bg-[#0066ff] px-2.5 py-1">
                    <span class="text-xs font-extrabold uppercase tracking-wide text-white">{{ $room['label'] }}</span>
                    @if($mine !== null)
                        <span class="rounded-full bg-[#3ee07f] px-2 py-0.5 text-[10px] font-black uppercase text-black">Yours</span>
                    @endif
                </div>
                <div class="flex min-w-0 flex-1 flex-col px-2.5 pb-2.5 pt-1.5">
                    <p class="vn-board-item text-4xl leading-none tabular-nums {{ $isRunning ? 'text-[#ff2b2b]' : 'text-white/30' }}" data-item>{{ $room['item'] ?? '–' }}</p>
                    <p class="mt-1 text-xs font-semibold {{ $isRunning ? 'text-[#ffd400]' : 'text-white/50' }}" data-status-text>{{ $statusText[$room['status']] ?? '' }}</p>
                    <p class="mt-1 break-words text-xs font-semibold text-white" data-case @if(! $room['case_number']) hidden @endif>{{ $room['case_number'] }}</p>
                    <p class="line-clamp-2 break-words text-xs text-white/70" data-parties @if(! $room['parties']) hidden @endif>{{ $room['parties'] }}</p>
                    <p class="mt-1 break-words text-xs italic text-[#ffd400]" data-message @if(! $room['message']) hidden @endif>{{ $room['message'] }}</p>
                    <p class="mt-1 text-xs font-black text-[#3ee07f]" data-now-mine hidden>Your case is being heard now</p>
                    @if($mine)
                        <p class="mt-auto pt-2 text-xs text-[#3ee07f]">
                            @foreach($mine as $case)
                                <span class="block truncate">{{ $case['title'] }}{{ $case['time'] ? ' · '.$case['time'] : '' }}</span>
                            @endforeach
                        </p>
                    @endif
                </div>
            </li>
        @empty
            <li class="col-span-full rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-600">
                <p>The {{ $board['short'] }} board can't be reached from VakilNiti right now. Open the court's own board instead; it works from your device.</p>
                <a href="{{ $board['source'] }}" target="_blank" rel="noopener noreferrer"
                   class="mt-3 flex min-h-11 w-full items-center justify-center rounded-xl bg-[#16776e] px-4 font-semibold text-white hover:bg-[#125f58] sm:inline-flex sm:w-auto">Open the court's board</a>
            </li>
        @endforelse
    </ul>

    <footer class="mt-3 text-xs leading-relaxed text-slate-500">
        {{ $board['legend'] }} From the court's own public board at
        <a href="{{ $board['source'] }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-[#16776e] hover:underline">{{ parse_url($board['source'], PHP_URL_HOST) }}</a>.
        There may be a short delay; confirm the exact position at the court.
    </footer>
</main>

<script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
    (function () {
        const root = document.querySelector('[data-live-board]');
        const statusText = @json($statusText);
        const sittingOnly = root.querySelector('[data-live-sitting-only]');
        // Opened on its own (from a hearing's live chip), not inside the board viewer: offer the way back.
        root.querySelector('[data-live-back]').hidden = window.self !== window.top;
        // "SLP(C) No. 39493/2026" and "SLP (C) 39493 of 2026" both become "39493/2026".
        const caseKey = (text) => {
            const match = String(text || '').match(/(\d+)\s*(?:\/|of)\s*(\d{4})/i);

            return match ? match[1] + '/' + match[2] : null;
        };

        try {
            sittingOnly.checked = localStorage.getItem('vn-live-sitting-only') === '1';
        } catch (error) {}

        // Courts ticked in VakilNiti's board panel ("Court 5", "R1"), saved for this board: "R-1", "Court 5" and "5" all match.
        const roomKey = (text) => String(text || '').toUpperCase().replace(/^\s*(COURT|ROOM)\s*(NO\.?)?/, '').replace(/[^A-Z0-9]/g, '');
        let onlyRooms = [];
        const setOnlyRooms = (value) => {
            onlyRooms = String(value || '').split(/[\s,;]+/).map(roomKey).filter(Boolean);
        };

        if (window.self !== window.top) {
            try {
                setOnlyRooms(JSON.parse(localStorage.getItem('vn-board-rooms:' + root.dataset.liveBoard) || '""'));
            } catch (error) {}
            window.addEventListener('message', (event) => {
                if (event.origin === window.location.origin && event.data?.type === 'vn-board-rooms') {
                    setOnlyRooms(event.data.rooms);
                    applySittingOnly();
                }
            });
        }

        const applySittingOnly = () => {
            root.querySelectorAll('[data-room]').forEach((tile) => {
                // Chosen by number ("52") or by the name on the board ("R1", "Court 5").
                const notChosen = onlyRooms.length > 0 && !onlyRooms.includes(roomKey(tile.dataset.room)) && !onlyRooms.includes(roomKey(tile.dataset.label));
                tile.hidden = notChosen || (sittingOnly.checked && tile.dataset.status === 'not_in_session' && !tile.hasAttribute('data-mine'));
            });
        };

        const setText = (tile, selector, value) => {
            const element = tile.querySelector(selector);
            element.textContent = value || '';
            element.hidden = !value;
        };

        const render = (board) => {
            board.rooms.forEach((room) => {
                const tile = root.querySelector('[data-room="' + room.room + '"]');

                if (!tile) {
                    return;
                }

                const running = room.status === 'running';
                tile.dataset.status = room.status;
                const item = tile.querySelector('[data-item]');
                item.textContent = room.item || '–';
                item.classList.toggle('text-[#ff2b2b]', running);
                item.classList.toggle('text-white/30', !running);
                const status = tile.querySelector('[data-status-text]');
                status.textContent = statusText[room.status] || '';
                status.classList.toggle('text-[#ffd400]', running);
                status.classList.toggle('text-white/50', !running);
                setText(tile, '[data-case]', room.case_number);
                setText(tile, '[data-parties]', room.parties);
                setText(tile, '[data-message]', room.message);

                const mine = (tile.dataset.myCases || '').split('|').map(caseKey).filter(Boolean);
                tile.querySelector('[data-now-mine]').hidden = !(running && mine.includes(caseKey(room.case_number)));
            });

            if (board.updated_at) {
                root.querySelector('[data-live-updated]').textContent = 'Updated ' + new Date(board.updated_at).toLocaleTimeString('en-IN', { hour12: false });
            }

            root.querySelector('[data-live-stale]').hidden = board.ok;
            applySittingOnly();
        };

        const refresh = () => {
            if (document.hidden) {
                return;
            }

            fetch(root.dataset.liveSource, { headers: { Accept: 'application/json' } })
                .then((response) => response.ok ? response.json() : null)
                .then((board) => board && render(board))
                .catch(() => {
                    root.querySelector('[data-live-stale]').hidden = false;
                });
        };

        sittingOnly.addEventListener('change', () => {
            try {
                localStorage.setItem('vn-live-sitting-only', sittingOnly.checked ? '1' : '0');
            } catch (error) {}
            applySittingOnly();
        });

        applySittingOnly();
        render({ rooms: [], ok: {{ $board['ok'] ? 'true' : 'false' }}, updated_at: @json($board['updated_at']) });
        root.querySelectorAll('[data-room]').forEach((tile) => {
            const mine = (tile.dataset.myCases || '').split('|').map(caseKey).filter(Boolean);
            const running = tile.dataset.status === 'running';
            tile.querySelector('[data-now-mine]').hidden = !(running && mine.includes(caseKey(tile.querySelector('[data-case]').textContent)));
        });
        setInterval(refresh, 10000);
        document.addEventListener('visibilitychange', refresh);
    })();
</script>
</body>
</html>
