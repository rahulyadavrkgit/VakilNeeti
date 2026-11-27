{{--
    A court's live display board inside VakilNiti, built from the data the court publishes for its own public board
    (App\Services\LiveCourtBoard). A page of its own, without the menu, so the board viewer can show it in a frame;
    it refreshes itself every 10 seconds. The signed-in workspace's hearing rooms are highlighted.
--}}
@php
    $statusText = ['running' => 'Running', 'not_in_session' => 'Not in session', 'exhausted' => 'Board over', 'unavailable' => 'Live item unavailable'];
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
        body { background: var(--vn-canvas); color: var(--vn-text); font-family: var(--font-sans), sans-serif; font-variant-numeric: tabular-nums; }
        .vn-board-item { font-family: "Arial Black", Gadget, var(--font-sans), sans-serif; font-weight: 900; }
        .vn-copied-toast { position: fixed; left: 50%; bottom: calc(1rem + env(safe-area-inset-bottom)); transform: translateX(-50%); z-index: 50; max-width: calc(100vw - 2rem);
            border-radius: 9999px; background: #142235; color: #fff; padding: .55rem 1rem; font-size: .85rem; font-weight: 600; box-shadow: 0 10px 25px rgb(0 0 0 / .25);
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    </style>
</head>
<body class="min-h-screen">
<main class="mx-auto max-w-7xl p-3 sm:p-4" data-live-board="{{ $board['court'] }}" data-live-source="{{ route('courts.live-board.data', $board['court']) }}">
    <header class="flex flex-wrap items-center justify-between gap-2">
        <div class="min-w-0">
            <a href="{{ route('courts.display-boards') }}" data-live-back hidden class="vn-link mb-1 inline-flex min-h-9 items-center text-sm font-semibold">&larr; VakilNiti display boards</a>
            <h1 class="flex items-center gap-2 text-lg font-semibold text-slate-900 sm:text-xl">
                <span class="relative flex h-2.5 w-2.5" aria-hidden="true"><span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-red-400 opacity-60 motion-reduce:animate-none"></span><span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-red-500"></span></span>
                {{ $board['short'] }} · Live board
            </h1>
            <p data-live-details class="text-xs text-slate-500">
                <time datetime="{{ today()->toDateString() }}">{{ today()->format('l, d F Y') }}</time>
                <span aria-hidden="true">&middot;</span>
                <span data-live-updated>{{ $updated ? 'Updated '.$updated->format('H:i:s') : 'Waiting for the court' }}</span>
                <span data-live-stale @if($board['ok']) hidden @endif class="ml-1 font-semibold text-amber-700">· Live feed unavailable</span>
            </p>
        </div>
        {{-- Two tick boxes, alike: only the courts with your hearings today, and only the courts sitting now. --}}
        <div class="flex flex-wrap items-center gap-2">
            @auth
                <label class="inline-flex min-h-10 cursor-pointer items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 has-checked:border-teal-500 has-checked:bg-teal-50 has-checked:text-teal-800">
                    <input type="checkbox" data-live-my-courts @checked($showMyCourts) aria-controls="board-rooms" class="rounded border-slate-300 text-[#16776e] focus:ring-[#16776e]">
                    My courts
                </label>
            @endauth
            <label data-live-sitting-wrap class="inline-flex min-h-10 cursor-pointer items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 has-checked:border-teal-500 has-checked:bg-teal-50 has-checked:text-teal-800">
                <input type="checkbox" data-live-sitting-only aria-controls="board-rooms" class="rounded border-slate-300 text-[#16776e] focus:ring-[#16776e]">
                Sitting courts
            </label>
        </div>
    </header>

    <label data-live-room-search-wrap hidden class="mt-3 flex max-w-sm items-center gap-2 rounded-lg border border-slate-300 bg-white px-3">
        <x-icon name="search" :size="17" class="shrink-0 text-slate-500" />
        <span class="sr-only">Filter by court number</span>
        <input type="search" data-live-room-search autocomplete="off" placeholder="{{ $board['court'] === 'allahabad-high-court' ? 'Court numbers, e.g. 1, 2, CJ' : 'Court numbers, e.g. 1, 2, R1' }}"
               class="min-h-11 w-full min-w-0 border-0 bg-transparent px-0 focus:ring-0">
    </label>

    {{-- Coloured like the Delhi High Court's own board, for every court, in light and dark mode alike. --}}
    <ul id="board-rooms" class="vn-court-board mt-3 grid grid-cols-2 gap-1 rounded-xl bg-black p-1 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6" data-live-rooms>
        @foreach($board['rooms'] as $room)
            @include('courts.partials.live-board-room', ['room' => $room, 'mine' => $myRooms[$room['room']] ?? []])
        @endforeach
        @if($board['rooms'] === [])
            <li data-live-unavailable class="col-span-full rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-600">
                <p>Live items are temporarily unavailable for {{ $board['short'] }}.</p>
                <a href="{{ $board['source'] }}" target="_blank" rel="noopener noreferrer"
                    class="vn-primary-button mt-3 flex min-h-11 w-full items-center justify-center rounded-xl px-4 font-semibold sm:inline-flex sm:w-auto">Open the court's board</a>
            </li>
        @endif
    </ul>
    <template data-live-room-template>
        @include('courts.partials.live-board-room', ['room' => [], 'mine' => []])
    </template>
    <p data-live-rooms-empty hidden role="status" class="mt-3 rounded-lg border border-slate-200 bg-white p-3 text-sm text-slate-600"></p>

    <footer class="mt-3 text-xs leading-relaxed text-slate-500">
        {{ $board['legend'] }} From the court's own public board at
        <a href="{{ $board['source'] }}" target="_blank" rel="noopener noreferrer" class="vn-link font-semibold">{{ parse_url($board['source'], PHP_URL_HOST) }}</a>.
        There may be a short delay; confirm the exact position at the court.
    </footer>
</main>

<script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
    (function () {
        const root = document.querySelector('[data-live-board]');
        const statusText = @json($statusText);
        const myRooms = {{ \Illuminate\Support\Js::from($myRooms) }};
        const myRoomLabels = {{ \Illuminate\Support\Js::from($myRoomLabels) }};
        const focusedRoom = @json($focusedRoom);
        const isToday = true;
        const myCourtsBox = root.querySelector('[data-live-my-courts]');
        const sittingOnly = root.querySelector('[data-live-sitting-only]');
        const sittingWrap = root.querySelector('[data-live-sitting-wrap]');
        const roomSearch = root.querySelector('[data-live-room-search]');
        const roomSearchWrap = root.querySelector('[data-live-room-search-wrap]');
        const rooms = root.querySelector('[data-live-rooms]');
        const roomTemplate = root.querySelector('[data-live-room-template]');
        const staleMessage = root.querySelector('[data-live-stale]');
        const emptyMessage = root.querySelector('[data-live-rooms-empty]');
        const standalone = window.self === window.top;
        let myCourts = @json((bool) $showMyCourts);
        // Opened on its own (from a hearing's live chip), not inside the board viewer: offer the way back, to the page
        // it was opened from on VakilNiti, otherwise to the display boards.
        const backLink = root.querySelector('[data-live-back]');
        backLink.hidden = !standalone;
        let cameFrom = null;
        try {
            cameFrom = document.referrer ? new URL(document.referrer) : null;
        } catch (error) {}
        if (cameFrom && cameFrom.origin === window.location.origin && cameFrom.pathname !== window.location.pathname) {
            backLink.textContent = '← Back';
            backLink.addEventListener('click', (event) => {
                if (window.history.length > 1) {
                    event.preventDefault();
                    window.history.back();
                }
            });
        }
        // "SLP(C) No. 39493/2026" and "SLP (C) 39493 of 2026" both become "39493/2026".
        const caseKey = (text) => {
            const match = String(text || '').match(/(\d+)\s*(?:\/|of)\s*(\d{4})/i);

            return match ? match[1] + '/' + match[2] : null;
        };

        try {
            sittingOnly.checked = localStorage.getItem('vn-live-sitting-only') === '1';
            myCourts ||= Boolean(myCourtsBox && localStorage.getItem('vn-live-my-courts') === '1');
            if (myCourtsBox) {
                myCourtsBox.checked = myCourts;
            }
        } catch (error) {}

        const roomKey = (text) => String(text || '').toUpperCase()
            .replace(/^(?:REGISTRAR(?:\s+COURT)?|RC)\s*[-.:]?\s*(?=\d)/, 'R')
            .replace(/^\s*(COURT|ROOM)\s*(NO\.?)?/, '').replace(/[^A-Z0-9]/g, '').replace(/^(R?)0+(?=\d)/, '$1');
        let onlyRooms = [];
        const setOnlyRooms = (value) => {
            onlyRooms = String(value || '').replace(/\b(?:registrar(?:\s+court)?|rc)\s*[-.:]?\s*(?=\d)/gi, 'R')
                .replace(/\b(?:court|room)(?:\s+no\.?)?\s*[:.-]?\s*/gi, '')
                .replace(/\br\s+(?=\d)/gi, 'r').split(/[\s,;]+/).map(roomKey).filter(Boolean);
        };

        if (!standalone) {
            try {
                setOnlyRooms(JSON.parse(localStorage.getItem('vn-board-rooms:' + root.dataset.liveBoard) || '""'));
            } catch (error) {}
            window.addEventListener('message', (event) => {
                if (event.origin === window.location.origin && event.data?.type === 'vn-board-rooms') {
                    setOnlyRooms(event.data.rooms);
                    applyFilters();
                }
            });
        }

        const applyFilters = () => {
            const schedule = false;

            const tiles = [...rooms.querySelectorAll('[data-room]')];
            if (myCourts) {
                const existing = new Set(tiles.map((tile) => tile.dataset.room));
                Object.entries(myRoomLabels).forEach(([room, label]) => {
                    if (!existing.has(room)) {
                        const tile = addRoom({ room, label });
                        tile.dataset.scheduleOnly = '';
                        tile.dataset.status = 'unavailable';
                        tile.querySelector('[data-status-text]').textContent = statusText.unavailable;
                        tiles.push(tile);
                    }
                });
            }

            tiles.forEach((tile) => {
                const notChosen = onlyRooms.length > 0 && !onlyRooms.includes(roomKey(tile.dataset.room)) && !onlyRooms.includes(roomKey(tile.dataset.label));
                tile.hidden = notChosen || (myCourts ? !tile.hasAttribute('data-mine') : tile.hasAttribute('data-schedule-only'))
                    || (!schedule && sittingOnly.checked && tile.dataset.status !== 'running');
            });
            const unavailable = rooms.querySelector('[data-live-unavailable]');
            if (unavailable) {
                unavailable.hidden = myCourts;
            }
            roomSearchWrap.hidden = !standalone || (!myCourts && tiles.length === 0);
            emptyMessage.hidden = (!myCourts && !tiles.length) || tiles.some((tile) => !tile.hidden);
            emptyMessage.textContent = myCourts && !Object.keys(myRooms).length
                ? 'No hearings of yours with a court room on this board today.'
                : 'No courts match your filters.';
        };

        const setText = (tile, selector, value) => {
            const element = tile.querySelector(selector);
            element.textContent = value || '';
            element.hidden = !value;
        };

        const addRoom = (room) => {
            const tile = roomTemplate.content.firstElementChild.cloneNode(true);
            const mine = myRooms[room.room] || [];
            tile.dataset.room = room.room;
            tile.dataset.label = room.label;
            setText(tile, '[data-tile-label]', room.label);
            tile.toggleAttribute('data-focused', room.room === focusedRoom);
            tile.toggleAttribute('data-mine', mine.length > 0);
            tile.dataset.myCases = mine.map((hearing) => hearing.case_number).filter(Boolean).join('|');
            tile.querySelector('[data-tile-yours]').hidden = mine.length === 0;
            const matters = tile.querySelector('[data-tile-matters]');
            matters.hidden = mine.length === 0;
            mine.forEach((hearing) => {
                const matter = document.createElement('span');
                matter.className = 'block truncate';
                matter.textContent = hearing.title + (hearing.time ? ' · ' + hearing.time : '');
                matters.append(matter);
            });
            rooms.append(tile);

            return tile;
        };

        const render = (board) => {
            const tiles = new Map([...rooms.querySelectorAll('[data-room]')].map((tile) => [tile.dataset.room, tile]));
            board.rooms.forEach((room) => {
                const tile = tiles.get(room.room) || addRoom(room);

                const running = board.ok && room.status === 'running';
                tile.removeAttribute('data-schedule-only');
                tile.dataset.label = room.label;
                tile.dataset.status = room.status;
                setText(tile, '[data-tile-label]', room.label);
                const item = tile.querySelector('[data-item]');
                item.textContent = room.item || '–';
                item.toggleAttribute('data-live-current', Boolean(running && room.item));
                const status = tile.querySelector('[data-status-text]');
                status.textContent = statusText[room.status] || '';
                setText(tile, '[data-case]', room.case_number);
                setText(tile, '[data-parties]', room.parties);
                setText(tile, '[data-message]', room.message);

                const mine = (tile.dataset.myCases || '').split('|').map(caseKey).filter(Boolean);
                tile.querySelector('[data-now-mine]').hidden = !(isToday && running && mine.includes(caseKey(room.case_number)));
            });

            if (board.updated_at) {
                root.querySelector('[data-live-updated]').textContent = 'Updated ' + new Date(board.updated_at).toLocaleTimeString('en-IN', { hour12: false });
            }

            if (board.rooms.length) {
                rooms.querySelector('[data-live-unavailable]')?.remove();
            }

            staleMessage.hidden = board.ok;
            applyFilters();
        };

        let refreshing = false;
        const refresh = () => {
            if (document.hidden || refreshing || !isToday) {
                return;
            }

            refreshing = true;
            const controller = new AbortController();
            const timeout = setTimeout(() => controller.abort(), 12000);
            fetch(root.dataset.liveSource, {
                cache: 'no-store',
                headers: { Accept: 'application/json' },
                signal: controller.signal,
            })
                .then((response) => {
                    if (!response.ok) {
                        throw new Error('Live board unavailable');
                    }

                    return response.json();
                })
                .then(render)
                .catch(() => {
                    staleMessage.hidden = false;
                    rooms.querySelectorAll('[data-now-mine]:not([hidden])').forEach((message) => { message.hidden = true; });
                    rooms.querySelectorAll('[data-item][data-live-current]').forEach((item) => { item.removeAttribute('data-live-current'); });
                })
                .finally(() => {
                    clearTimeout(timeout);
                    refreshing = false;
                });
        };

        sittingOnly.addEventListener('change', () => {
            try {
                localStorage.setItem('vn-live-sitting-only', sittingOnly.checked ? '1' : '0');
            } catch (error) {}
            applyFilters();
        });

        myCourtsBox?.addEventListener('change', () => {
            myCourts = myCourtsBox.checked;
            try {
                localStorage.setItem('vn-live-my-courts', myCourts ? '1' : '0');
            } catch (error) {}

            if (!myCourts) {
                const url = new URL(window.location.href);
                url.searchParams.delete('my');
                window.history.replaceState(window.history.state, '', url);
            }

            applyFilters();
        });

        roomSearch.addEventListener('input', () => {
            setOnlyRooms(roomSearch.value);
            applyFilters();
        });

        // Copy a court: "Delhi HC Court 23 · Item A72 · W.P.(C) 123/2026 · A vs B", with a short "Copied" note.
        let toastTimer = null;
        const toast = (text) => {
            let note = document.querySelector('.vn-copied-toast');
            if (!note) {
                note = document.createElement('div');
                note.className = 'vn-copied-toast';
                note.setAttribute('role', 'status');
                document.body.append(note);
            }
            note.textContent = text;
            note.hidden = false;
            clearTimeout(toastTimer);
            toastTimer = setTimeout(() => { note.hidden = true; }, 2200);
        };
        rooms.addEventListener('click', async (event) => {
            const button = event.target.closest('[data-tile-copy]');
            if (!button) {
                return;
            }

            const tile = button.closest('[data-room]');
            const field = (selector) => (tile.querySelector(selector)?.hidden ? '' : tile.querySelector(selector)?.textContent.trim());
            const item = field('[data-item]');
            const text = [@json($board['short']) + ' ' + tile.dataset.label, item && item !== '–' ? 'Item ' + item : field('[data-status-text]'), field('[data-case]'), field('[data-parties]')]
                .filter(Boolean).join(' · ');

            try {
                await navigator.clipboard.writeText(text);
                button.classList.add('is-copied');
                setTimeout(() => button.classList.remove('is-copied'), 1500);
                toast('Copied: ' + text);
            } catch (error) {
                window.prompt('Copy this:', text);
            }
        });

        applyFilters();
        refresh();
        setInterval(refresh, 10000);
        document.addEventListener('visibilitychange', refresh);
        window.addEventListener('pageshow', (event) => {
            if (event.persisted) {
                refresh();
            }
        });
        window.addEventListener('online', refresh);
    })();
</script>
</body>
</html>
