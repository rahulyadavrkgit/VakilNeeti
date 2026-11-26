@php
    $isActive = fn (string ...$patterns): bool => request()->is(...$patterns);

    // Few, plain entries: each section's own pages are reached from tabs inside it.
    $groups = [
        'workspace' => $isActive('/', 'admin', 'admin/*', 'search', 'cases*', 'case-calendar*', 'hearings*', 'tasks*', 'clients*', 'billing*', 'invoices*', 'payments*', 'notifications*', 'profile*', 'firm-profile*', 'tds*', 'professional-tax*'),
        'courts' => $isActive('courts*', 'tribunals*', 'court-integrations*'),
        'library' => $isActive('bare-acts*', 'new-criminal-laws*', 'court-fee*', 'miscellaneous-forms*'),
        'more' => $isActive('police-administration*', 'bar-associations*', 'pdf-annotator*', 'dashboard-studio*', 'bar-directory*'),
    ];

    // Open the group for the current page; on a page outside every group, open Workspace.
    if (! in_array(true, $groups, true)) {
        $groups['workspace'] = true;
    }

    $link = fn (bool $active): string => 'vn-nav-link'.($active ? ' vn-active' : '');
@endphp

<div data-nav-search class="px-3 pt-4">
    <label class="flex items-center gap-2 rounded-xl bg-white/10 px-3 text-white/80 focus-within:bg-white/15">
        <x-icon name="search" :size="16" class="text-slate-400" />
        <span class="sr-only">{{ __('Find in menu') }}</span>
        <input type="search" data-filter-list="vn-nav" data-filter-also="a.vn-nav-link" placeholder="{{ __('Find in menu') }}"
               class="min-h-10 w-full border-0 bg-transparent px-0 text-base text-white md:text-sm placeholder:text-slate-400 focus:ring-0">
    </label>
</div>

<nav id="vn-nav" class="flex-1 overflow-y-auto px-3 py-4" aria-label="{{ __('Sections') }}">

    {{-- WORKSPACE --}}
    <details class="vn-nav-group" data-filter-item data-nav-group="workspace" @if($groups['workspace']) open @endif>
        <summary class="vn-nav-group-title"><span>{{ __('Workspace') }}</span><x-icon name="chevron" :size="16" class="vn-nav-chevron" /></summary>
        <div class="vn-nav-items">
            <a href="{{ route('dashboard') }}" class="{{ $link($isActive('/')) }}"><x-icon name="home" :size="18" />{{ __('Home') }}</a>
            @auth
                <a href="{{ route('cases.index') }}" class="{{ $link($isActive('cases*', 'tasks*')) }}"><x-icon name="briefcase" :size="18" />{{ __('Cases') }}</a>
                <a href="{{ route('cases.calendar') }}" class="{{ $link($isActive('case-calendar*', 'hearings*')) }}"><x-icon name="calendar" :size="18" />{{ __('Calendar') }}</a>
                <a href="{{ route('clients.index') }}" class="{{ $link($isActive('clients*')) }}"><x-icon name="people" :size="18" />{{ __('Clients') }}</a>
                @unless(auth()->user()->isTeamMember())
                    <a href="{{ route('billing.index') }}" class="{{ $link($isActive('billing*', 'invoices*', 'payments*', 'tds*', 'professional-tax*')) }}"><x-icon name="receipt" :size="18" />{{ __('Billing') }}</a>
                @endunless
                <a href="{{ route('profile.index') }}" class="{{ $link($isActive('profile*', 'firm-profile*', 'admin', 'admin/*')) }}"><x-icon name="settings" :size="18" />{{ __('Settings') }}</a>
            @else
                <p class="px-3 py-2 text-xs leading-relaxed text-slate-400">
                    <a href="{{ route('login') }}" data-vn-auth-open="login" class="font-semibold text-[#8ed2c8] hover:underline">{{ __('Log in') }}</a>
                    {{ __('to use your case diary, calendar, clients and billing.') }}
                </p>
            @endauth
        </div>
    </details>

    {{-- COURTS --}}
    <details class="vn-nav-group" data-filter-item data-nav-group="courts" @if($groups['courts']) open @endif>
        <summary class="vn-nav-group-title"><span>{{ __('Courts') }}</span><x-icon name="chevron" :size="16" class="vn-nav-chevron" /></summary>
        <div class="vn-nav-items">
            <a href="{{ route('courts.supreme-court') }}" class="{{ $link($isActive('courts/supreme-court*') || request('level') === 'supreme') }}"><x-icon name="pillars" :size="18" />{{ __('Supreme Court') }}</a>
            <a href="{{ route('courts.delhi-high-court') }}" class="{{ $link($isActive('courts/delhi-high-court*')) }}"><x-icon name="building" :size="18" />{{ __('Delhi High Court') }}</a>
            <a href="{{ route('courts.high-courts') }}" class="{{ $link($isActive('courts/high-courts*')) }}"><x-icon name="building" :size="18" />{{ __('High Courts') }}</a>
            <a href="{{ route('courts.delhi-district-courts') }}" class="{{ $link($isActive('courts/delhi-district-courts*')) }}"><x-icon name="gavel" :size="18" />{{ __('Delhi District Courts') }}</a>
            <a href="{{ route('tribunals.index') }}" class="{{ $link($isActive('tribunals*')) }}"><x-icon name="scale" :size="18" />{{ __('Tribunals') }}</a>
            <a href="{{ route('courts.index') }}" class="{{ $link($isActive('courts', 'courts/ecourts-services')) }}"><x-icon name="list" :size="18" />{{ __('All courts') }}</a>
        </div>
    </details>

    {{-- LIBRARY --}}
    <details class="vn-nav-group" data-filter-item data-nav-group="library" @if($groups['library']) open @endif>
        <summary class="vn-nav-group-title"><span>{{ __('Library') }}</span><x-icon name="chevron" :size="16" class="vn-nav-chevron" /></summary>
        <div class="vn-nav-items">
            <a href="{{ route('bare-acts.index') }}" class="{{ $link($isActive('bare-acts*')) }}"><x-icon name="book" :size="18" />{{ __('Bare Acts') }}</a>
            <a href="{{ url('/new-criminal-laws') }}" class="{{ $link($isActive('new-criminal-laws*')) }}"><x-icon name="law" :size="18" />{{ __('New Criminal Laws') }}</a>
            <a href="{{ url('/court-fee') }}" class="{{ $link($isActive('court-fee*')) }}"><x-icon name="calculator" :size="18" />{{ __('Court fee') }}</a>
            <a href="{{ route('miscellaneous-forms.index') }}" class="{{ $link($isActive('miscellaneous-forms*')) }}"><x-icon name="form" :size="18" />{{ __('Forms') }}</a>
        </div>
    </details>

    {{-- MORE: tools used now and then. --}}
    <details class="vn-nav-group" data-filter-item data-nav-group="more" @if($groups['more']) open @endif>
        <summary class="vn-nav-group-title"><span>{{ __('More') }}</span><x-icon name="chevron" :size="16" class="vn-nav-chevron" /></summary>
        <div class="vn-nav-items">
            <a href="{{ url('/police-administration') }}" class="{{ $link($isActive('police-administration*')) }}"><x-icon name="shield" :size="18" />{{ __('Police administration') }}</a>
            <a href="{{ route('bar-associations.index') }}" class="{{ $link($isActive('bar-associations*')) }}"><x-icon name="bar" :size="18" />{{ __('Bar associations') }}</a>
            <a href="{{ url('/pdf-annotator') }}" class="{{ $link($isActive('pdf-annotator*')) }}"><x-icon name="pdf" :size="18" />{{ __('PDF annotator') }}</a>
            <a href="{{ route('dashboard-studio.index') }}" class="{{ $link($isActive('dashboard-studio*')) }}"><x-icon name="chart" :size="18" />{{ __('Law data dashboards') }}</a>
        </div>
    </details>

    <p data-filter-empty hidden class="px-3 py-2 text-xs text-slate-400">{{ __('Nothing in the menu matches.') }}</p>
</nav>

<script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
    // Remember which menu groups the person opened or closed, so the menu stays as they left it.
    (function () {
        let saved = {};

        try {
            saved = JSON.parse(localStorage.getItem('vn-nav-groups') || '{}');
        } catch (error) {}

        const groups = [...document.querySelectorAll('[data-nav-group]')];
        const desktop = window.matchMedia('(min-width: 768px)');
        const canHover = window.matchMedia('(hover: hover) and (pointer: fine)');
        const current = groups.find(group => group.open);
        let pinned = null;
        let closeTimer;

        const showGroup = active => {
            window.clearTimeout(closeTimer);
            groups.forEach(group => { group.open = group === active; });
        };
        const applyLayout = () => {
            pinned = null;
            window.clearTimeout(closeTimer);
            groups.forEach(group => {
                group.open = !desktop.matches && (group === current || saved[group.dataset.navGroup] === true);
            });
        };

        applyLayout();
        desktop.addEventListener('change', applyLayout);

        groups.forEach(group => {
            const summary = group.querySelector('summary');
            group.addEventListener('pointerenter', event => {
                if (desktop.matches && canHover.matches && event.pointerType === 'mouse') {
                    if (pinned !== group) pinned = null;
                    showGroup(group);
                }
            });

            group.addEventListener('pointerleave', event => {
                if (desktop.matches && canHover.matches && event.pointerType === 'mouse' && pinned !== group) {
                    closeTimer = window.setTimeout(() => {
                        if (!group.contains(document.activeElement)) group.open = false;
                    }, 180);
                }
            });

            summary.addEventListener('click', event => {
                if (desktop.matches) {
                    event.preventDefault();
                    pinned = pinned === group || (event.detail === 0 && group.open) ? null : group;
                    showGroup(pinned);
                }
            });

            group.addEventListener('keydown', event => {
                if (desktop.matches && event.key === 'Escape' && group.open) {
                    pinned = null;
                    showGroup(null);
                    summary.focus();
                }
            });

            group.addEventListener('toggle', () => {
                if (!desktop.matches) {
                    saved[group.dataset.navGroup] = group.open;
                    try {
                        localStorage.setItem('vn-nav-groups', JSON.stringify(saved));
                    } catch (error) {}
                }
            });
        });

        document.addEventListener('click', event => {
            if (desktop.matches && !event.target.closest('#vn-nav')) {
                pinned = null;
                showGroup(null);
            }
        });
    })();
</script>
