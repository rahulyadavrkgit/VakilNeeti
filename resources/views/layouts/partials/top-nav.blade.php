{{--
    Computers: one row in the header. Home, Cases, Clients and Billing open their page directly (a section's other
    pages are in the bar under the header, layouts/partials/section-nav); only Courts and More open a second menu
    (click, or hover with a mouse). The calendar is an icon beside the bell. Phones use the bottom bar and the Menu sheet.
--}}
@php
    $isActive = fn (string ...$patterns): bool => request()->is(...$patterns);
    $signedIn = auth()->check();
    $owner = $signedIn && ! auth()->user()->isTeamMember();

    // [label, url, active?] or [label, null, active?, [[label, url, active?], …]] for a section with a second menu.
    $items = array_values(array_filter([
        [__('Home'), route('dashboard'), $isActive('/')],
        $signedIn ? [__('Cases'), route('cases.index'), $isActive('cases*', 'tasks*', 'case-calendar*', 'hearings*')] : null,
        $signedIn ? [__('Clients'), route('clients.index'), $isActive('clients*', 'client-management*')] : null,
        $owner ? [__('Billing'), route('billing.index'), $isActive('billing*', 'invoices*', 'payments*', 'tds*', 'professional-tax*')] : null,
        [__('Courts'), null, $isActive('courts*', 'court-catalogue*', 'tribunals*', 'court-integrations*'), [
            [__('Supreme Court'), route('courts.supreme-court'), $isActive('courts/supreme-court*')],
            [__('Delhi High Court'), route('courts.delhi-high-court'), $isActive('courts/delhi-high-court*')],
            [__('High Courts'), route('courts.high-courts'), $isActive('courts/high-courts*')],
            [__('Delhi District Courts'), route('courts.delhi-district-courts'), $isActive('courts/delhi-district-courts*')],
            [__('Tribunals'), route('tribunals.index'), $isActive('tribunals*', 'court-catalogue*')],
            [__('All courts'), route('courts.index'), $isActive('courts', 'courts/ecourts-services')],
        ]],
        [__('More'), null, $isActive('bare-acts*', 'new-criminal-laws*', 'court-fee*', 'miscellaneous-forms*', 'police-administration*', 'bar-associations*', 'pdf-annotator*', 'dashboard-studio*', 'bar-directory*'), [
            [__('Bare Acts'), route('bare-acts.index'), $isActive('bare-acts*')],
            [__('New Criminal Laws'), url('/new-criminal-laws'), $isActive('new-criminal-laws*')],
            [__('Court fee'), url('/court-fee'), $isActive('court-fee*')],
            [__('Forms'), route('miscellaneous-forms.index'), $isActive('miscellaneous-forms*')],
            [__('Police administration'), url('/police-administration'), $isActive('police-administration*')],
            [__('Bar associations'), route('bar-associations.index'), $isActive('bar-associations*')],
            [__('PDF annotator'), url('/pdf-annotator'), $isActive('pdf-annotator*')],
            [__('Law data dashboards'), route('dashboard-studio.index'), $isActive('dashboard-studio*')],
        ]],
    ]));
    // Flat items; the current section is marked by a gold bar along the header's bottom edge.
    $top = 'relative inline-flex h-16 items-center gap-1 whitespace-nowrap px-2.5 text-sm font-semibold transition xl:px-3.5 xl:text-[15px]';
    $topActive = 'text-white after:absolute after:inset-x-2 after:bottom-0 after:h-[3px] after:rounded-t-full after:bg-[#e6b84a]';
    $topIdle = 'text-white/75 hover:text-white';
@endphp

<nav class="vn-top-nav hidden h-16 min-w-0 flex-1 items-stretch md:flex" aria-label="{{ __('Main menu') }}" data-top-nav>
    @foreach($items as $item)
        @php [$label, $url, $active] = $item; @endphp
        @if($url)
            <a href="{{ $url }}" @if($active) aria-current="page" @endif
               class="{{ $top }} {{ $active ? $topActive : $topIdle }}">{{ $label }}</a>
        @else
            <details class="relative" data-top-menu>
                <summary class="{{ $top }} cursor-pointer list-none {{ $active ? $topActive : $topIdle }}" @if($active) aria-current="true" @endif>
                    {{ $label }}<x-icon name="chevron" :size="16" class="opacity-70" />
                </summary>
                <div class="absolute left-0 top-full z-50 w-60 rounded-b-xl rounded-tr-xl border border-white/10 bg-[#142235] p-1.5 shadow-xl shadow-black/25">
                    @foreach($item[3] as [$subLabel, $subUrl, $subActive])
                        <a href="{{ $subUrl }}" @if($subActive) aria-current="page" @endif
                           class="flex min-h-10 items-center rounded-lg px-3 text-sm font-semibold {{ $subActive ? 'bg-[#16776e] text-white' : 'text-white/85 hover:bg-white/10 hover:text-white' }}">{{ $subLabel }}</a>
                    @endforeach
                </div>
            </details>
        @endif
    @endforeach
</nav>

<script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
    // One second menu open at a time; a mouse opens it on hover, and a click outside or Escape closes it.
    (function () {
        const menus = [...document.querySelectorAll('[data-top-menu]')];
        const canHover = window.matchMedia('(hover: hover) and (pointer: fine)');
        let closeTimer;

        const closeOthers = (keep) => menus.forEach((menu) => { if (menu !== keep) menu.open = false; });

        menus.forEach((menu) => {
            let hoverOpenedAt = 0;

            menu.addEventListener('toggle', () => { if (menu.open) closeOthers(menu); });
            menu.addEventListener('pointerenter', (event) => {
                if (canHover.matches && event.pointerType === 'mouse') {
                    window.clearTimeout(closeTimer);
                    closeOthers(menu);
                    if (!menu.open) {
                        menu.open = true;
                        hoverOpenedAt = Date.now();
                    }
                }
            });
            // The click that follows a hover keeps the menu open instead of closing it again.
            menu.querySelector('summary').addEventListener('click', (event) => {
                if (menu.open && Date.now() - hoverOpenedAt < 800) {
                    event.preventDefault();
                }
            });
            menu.addEventListener('pointerleave', (event) => {
                if (canHover.matches && event.pointerType === 'mouse') {
                    closeTimer = window.setTimeout(() => { if (!menu.contains(document.activeElement)) menu.open = false; }, 180);
                }
            });
        });

        document.addEventListener('click', (event) => menus.forEach((menu) => { if (!menu.contains(event.target)) menu.open = false; }));
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                const open = menus.find((menu) => menu.open);
                if (open) { open.open = false; open.querySelector('summary').focus(); }
            }
        });
    }());
</script>
