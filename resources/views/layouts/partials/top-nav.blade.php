{{--
    Computers: one row of direct links; the sections with several pages open a second menu below
    (click, or hover with a mouse). Phones use the bottom bar and the Menu sheet (sidebar-nav).
--}}
@php
    $isActive = fn (string ...$patterns): bool => request()->is(...$patterns);
    $signedIn = auth()->check();
    $owner = $signedIn && ! auth()->user()->isTeamMember();

    // [label, url, active?] or [label, null, active?, [[label, url, active?], …]] for a section with a second menu.
    $items = array_values(array_filter([
        [__('Home'), route('dashboard'), $isActive('/')],
        $signedIn ? [__('Cases'), null, $isActive('cases*', 'case-dashboard*', 'hearing-history*', 'today', 'tasks*'), [
            [__('My cases'), route('cases.index'), $isActive('cases', 'cases/*') && ! $isActive('cases/find*', 'cases/import*')],
            [__('Court day'), route('court-day'), $isActive('today')],
            [__('Tasks'), route('tasks.index'), $isActive('tasks*')],
            [__('Hearing history'), route('hearings.history'), $isActive('hearing-history*')],
            [__('Find & import'), route('cases.find'), $isActive('cases/find*', 'cases/import*')],
        ]] : null,
        $signedIn ? [__('Calendar'), route('cases.calendar'), $isActive('case-calendar*', 'hearings*')] : null,
        $signedIn ? [__('Clients'), route('clients.index'), $isActive('clients*', 'client-management*')] : null,
        $owner ? [__('Billing'), null, $isActive('billing*', 'invoices*', 'payments*', 'tds*', 'professional-tax*'), [
            [__('Overview'), route('billing.index'), $isActive('billing*')],
            [__('Invoices'), route('invoices.index'), $isActive('invoices*')],
            [__('Payments'), route('payments.index'), $isActive('payments*')],
            [__('TDS'), route('tds.index'), $isActive('tds*')],
            [__('Professional tax'), route('professional-tax.index'), $isActive('professional-tax*')],
        ]] : null,
        [__('Courts'), null, $isActive('courts*', 'court-catalogue*', 'tribunals*', 'court-integrations*'), [
            [__('All courts'), route('courts.index'), $isActive('courts', 'courts/ecourts-services')],
            [__('Supreme Court'), route('courts.supreme-court'), $isActive('courts/supreme-court*')],
            [__('Delhi High Court'), route('courts.delhi-high-court'), $isActive('courts/delhi-high-court*')],
            [__('High Courts'), route('courts.high-courts'), $isActive('courts/high-courts*')],
            [__('Delhi District Courts'), route('courts.delhi-district-courts'), $isActive('courts/delhi-district-courts*')],
            [__('Display boards'), route('courts.display-boards'), $isActive('courts/display-boards', 'courts/live-board*')],
            [__('Tribunals'), route('tribunals.index'), $isActive('tribunals*', 'court-catalogue*')],
        ]],
        [__('More'), null, $isActive('bare-acts*', 'new-criminal-laws*', 'court-fee*', 'miscellaneous-forms*', 'police-administration*', 'bar-associations*', 'pdf-annotator*', 'dashboard-studio*', 'bar-directory*', 'admin', 'admin/*'), array_values(array_filter([
            [__('Bare Acts'), route('bare-acts.index'), $isActive('bare-acts*')],
            [__('New Criminal Laws'), url('/new-criminal-laws'), $isActive('new-criminal-laws*')],
            [__('Court fee'), url('/court-fee'), $isActive('court-fee*')],
            [__('Forms'), route('miscellaneous-forms.index'), $isActive('miscellaneous-forms*')],
            [__('Police administration'), url('/police-administration'), $isActive('police-administration*')],
            [__('Bar associations'), route('bar-associations.index'), $isActive('bar-associations*')],
            [__('PDF annotator'), url('/pdf-annotator'), $isActive('pdf-annotator*')],
            [__('Law data dashboards'), route('dashboard-studio.index'), $isActive('dashboard-studio*')],
            $signedIn && auth()->user()->is_admin ? [__('Account administration'), route('admin.overview'), $isActive('admin', 'admin/*')] : null,
        ]))],
    ]));
    $top = 'inline-flex min-h-11 items-center gap-1 whitespace-nowrap rounded-lg px-3 text-[15px] font-semibold transition';
@endphp

<nav class="vn-top-nav hidden min-w-0 flex-1 items-center gap-0.5 md:flex" aria-label="{{ __('Main menu') }}" data-top-nav>
    @foreach($items as $item)
        @php [$label, $url, $active] = $item; @endphp
        @if($url)
            <a href="{{ $url }}" @if($active) aria-current="page" @endif
               class="{{ $top }} {{ $active ? 'bg-white/12 text-white' : 'text-white/80 hover:bg-white/8 hover:text-white' }}">{{ $label }}</a>
        @else
            <details class="relative" data-top-menu>
                <summary class="{{ $top }} cursor-pointer list-none {{ $active ? 'bg-white/12 text-white' : 'text-white/80 hover:bg-white/8 hover:text-white' }}" @if($active) aria-current="true" @endif>
                    {{ $label }}<x-icon name="chevron" :size="16" class="opacity-70" />
                </summary>
                <div class="absolute left-0 top-full z-50 mt-1.5 w-60 rounded-xl border border-white/10 bg-[#142235] p-1.5 shadow-xl shadow-black/25">
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
