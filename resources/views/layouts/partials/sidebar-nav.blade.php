@php
    $isActive = fn (string ...$patterns): bool => request()->is(...$patterns);

    $groups = [
        'workspace' => $isActive('/', 'search', 'case-dashboard*', 'cases*', 'case-calendar*', 'hearing-history*', 'hearings*', 'tasks*', 'clients*', 'client-management*', 'billing*', 'invoices*', 'payments*', 'notifications*'),
        'courts' => $isActive('courts*', 'court-directory*', 'court-catalogue*', 'court-integrations*', 'court-management*'),
        'reference' => $isActive('bare-acts*', 'new-criminal-laws*', 'court-fee*', 'miscellaneous-forms*', 'police-administration*', 'bar-associations*', 'pdf-annotator*', 'dashboard-studio*', 'bar-directory*'),
        'settings' => $isActive('profile*', 'firm-profile*', 'tds*', 'professional-tax*'),
    ];

    // Open the group for the current page; on a page outside every group, open Workspace.
    if (! in_array(true, $groups, true)) {
        $groups['workspace'] = true;
    }

    $link = fn (bool $active): string => 'vn-nav-link'.($active ? ' vn-active' : '');
@endphp

<div class="px-3 pt-4">
    <label class="flex items-center gap-2 rounded-xl bg-white/10 px-3 text-slate-200 focus-within:bg-white/15">
        <x-icon name="search" :size="16" class="text-slate-400" />
        <span class="sr-only">Find in menu</span>
        <input type="search" data-filter-list="vn-nav" data-filter-also="a.vn-nav-link" placeholder="Find in menu"
               class="min-h-10 w-full border-0 bg-transparent px-0 text-sm text-white placeholder:text-slate-400 focus:ring-0">
    </label>
</div>

<nav id="vn-nav" class="flex-1 space-y-1 overflow-y-auto px-3 py-4" aria-label="Sections">

    {{-- WORKSPACE --}}
    <details class="vn-nav-group" data-filter-item data-nav-group="workspace" @if($groups['workspace']) open @endif>
        <summary class="vn-nav-group-title"><span>Workspace</span><x-icon name="chevron" :size="16" class="vn-nav-chevron" /></summary>
        <div class="vn-nav-items">
            <a href="{{ route('dashboard') }}" class="{{ $link($isActive('/')) }}"><x-icon name="home" :size="18" />Home</a>
            @auth
                <a href="{{ route('search') }}" class="{{ $link($isActive('search')) }}"><x-icon name="search" :size="18" />Search</a>
                <a href="{{ route('case-dashboard.index') }}" class="{{ $link($isActive('case-dashboard*')) }}"><x-icon name="dashboard" :size="18" />Case dashboard</a>
                <a href="{{ route('cases.index') }}" class="{{ $link($isActive('cases', 'cases/*') && ! $isActive('cases/find*', 'cases/import*', 'cases/create')) }}"><x-icon name="briefcase" :size="18" />My cases</a>
                <a href="{{ route('cases.find') }}" class="{{ $link($isActive('cases/find*', 'cases/import*', 'cases/create')) }}"><x-icon name="download" :size="18" />Find &amp; import cases</a>
                <a href="{{ route('cases.calendar') }}" class="{{ $link($isActive('case-calendar*', 'hearings*')) }}"><x-icon name="calendar" :size="18" />Calendar &amp; hearings</a>
                <a href="{{ route('hearings.history') }}" class="{{ $link($isActive('hearing-history*')) }}"><x-icon name="history" :size="18" />Hearing history</a>
                <a href="{{ route('tasks.index') }}" class="{{ $link($isActive('tasks*')) }}"><x-icon name="check" :size="18" />Tasks &amp; reminders</a>
                <a href="{{ route('clients.index') }}" class="{{ $link($isActive('clients*', 'client-management*')) }}"><x-icon name="people" :size="18" />Clients</a>
                <a href="{{ route('billing.index') }}" class="{{ $link($isActive('billing*', 'invoices*', 'payments*')) }}"><x-icon name="receipt" :size="18" />Billing</a>
            @else
                <p class="px-3 py-2 text-xs leading-relaxed text-slate-400">
                    <a href="{{ route('login') }}" data-vn-auth-open="login" class="font-semibold text-[#8ed2c8] hover:underline">Log in</a>
                    to use your case diary, calendar, clients and billing.
                </p>
            @endauth
        </div>
    </details>

    {{-- COURTS --}}
    <details class="vn-nav-group" data-filter-item data-nav-group="courts" @if($groups['courts']) open @endif>
        <summary class="vn-nav-group-title"><span>Courts</span><x-icon name="chevron" :size="16" class="vn-nav-chevron" /></summary>
        <div class="vn-nav-items">
            <a href="{{ route('courts.supreme-court') }}" class="{{ $link($isActive('courts/supreme-court') || request('level') === 'supreme') }}"><x-icon name="pillars" :size="18" />Supreme Court</a>
            <a href="{{ route('courts.delhi-high-court') }}" class="{{ $link($isActive('courts/delhi-high-court')) }}"><x-icon name="building" :size="18" />Delhi High Court</a>
            <a href="{{ route('courts.delhi-district-courts') }}" class="{{ $link($isActive('courts/delhi-district-courts')) }}"><x-icon name="gavel" :size="18" />Delhi District Courts</a>
            <a href="{{ route('courts.display-boards') }}" class="{{ $link($isActive('courts/display-boards')) }}"><x-icon name="board" :size="18" />Display boards</a>
            <a href="{{ route('courts.ecourts-services') }}" class="{{ $link($isActive('courts/ecourts-services')) }}"><x-icon name="search" :size="18" />eCourts services</a>

            <details class="vn-nav-sub" @if(request('level') === 'high-court') open @endif>
                <summary class="vn-nav-link"><x-icon name="building" :size="18" /><span class="flex-1">High Courts</span><x-icon name="chevron" :size="14" class="vn-nav-chevron" /></summary>
                <div class="vn-nav-child">
                    <a href="{{ route('court-directory.index', ['level' => 'high-court']) }}" class="{{ $link(request('level') === 'high-court' && ! request('state')) }}">All High Courts</a>
                    @foreach($practiceStates as $practiceState)
                        <a href="{{ route('court-directory.index', ['level' => 'high-court', 'state' => $practiceState]) }}"
                           class="{{ $link(request('level') === 'high-court' && request('state') === $practiceState) }}">{{ $practiceState }}</a>
                    @endforeach
                </div>
            </details>

            <details class="vn-nav-sub" @if(request('level') === 'district' || $isActive('court-directory/districts/*')) open @endif>
                <summary class="vn-nav-link"><x-icon name="map" :size="18" /><span class="flex-1">District courts</span><x-icon name="chevron" :size="14" class="vn-nav-chevron" /></summary>
                <div class="vn-nav-child">
                    <a href="{{ route('court-directory.index', ['level' => 'district']) }}" class="{{ $link(request('level') === 'district' && ! request('state')) }}">All districts</a>
                    @foreach($districtPracticeStates as $districtState)
                        <details class="vn-nav-sub"
                            @if((request('level') === 'district' && request('state') === $districtState['state_name']) || ($isActive('court-directory/districts/*') && $districtState['districts']->contains('id', (int) request()->segment(3)))) open @endif>
                            <summary class="vn-nav-link"><span class="flex-1">{{ $districtState['state_name'] }}</span><x-icon name="chevron" :size="14" class="vn-nav-chevron" /></summary>
                            <div class="vn-nav-child">
                                @foreach($districtState['districts'] as $district)
                                    <a href="{{ route('court-directory.districts.show', $district->id) }}"
                                       class="{{ $link($isActive('court-directory/districts/'.$district->id)) }}">{{ $district->district_name }}</a>
                                @endforeach
                            </div>
                        </details>
                    @endforeach
                    @auth
                        @if($districtPracticeStates === [])
                            <a href="{{ route('profile.practice-states.index') }}" class="vn-nav-link text-xs">Choose your practice districts &rarr;</a>
                        @endif
                    @endauth
                </div>
            </details>

            <a href="{{ route('court-catalogue.index') }}" class="{{ $link($isActive('court-catalogue*')) }}"><x-icon name="scale" :size="18" />Tribunals &amp; appellate courts</a>
            <a href="{{ route('court-integrations.ecourts.search') }}" class="{{ $link($isActive('court-integrations*')) }}"><x-icon name="search" :size="18" />Official court search</a>
            <a href="{{ route('courts.index') }}" class="{{ $link($isActive('courts') || ($isActive('court-directory') && ! request('level'))) }}"><x-icon name="list" :size="18" />All courts</a>
        </div>
    </details>

    {{-- LEGAL REFERENCE --}}
    <details class="vn-nav-group" data-filter-item data-nav-group="reference" @if($groups['reference']) open @endif>
        <summary class="vn-nav-group-title"><span>Legal reference</span><x-icon name="chevron" :size="16" class="vn-nav-chevron" /></summary>
        <div class="vn-nav-items">
            <a href="{{ route('bare-acts.index') }}" class="{{ $link($isActive('bare-acts*')) }}"><x-icon name="book" :size="18" />Bare Acts</a>
            <a href="{{ url('/new-criminal-laws') }}" class="{{ $link($isActive('new-criminal-laws*')) }}"><x-icon name="law" :size="18" />New Criminal Laws</a>
            <a href="{{ url('/court-fee') }}" class="{{ $link($isActive('court-fee*')) }}"><x-icon name="calculator" :size="18" />Court fee</a>
            <a href="{{ route('miscellaneous-forms.index') }}" class="{{ $link($isActive('miscellaneous-forms*')) }}"><x-icon name="form" :size="18" />Forms</a>
            <a href="{{ url('/police-administration') }}" class="{{ $link($isActive('police-administration*')) }}"><x-icon name="shield" :size="18" />Police administration</a>
            <a href="{{ route('bar-associations.index') }}" class="{{ $link($isActive('bar-associations*')) }}"><x-icon name="bar" :size="18" />Bar associations</a>
            <a href="{{ url('/pdf-annotator') }}" class="{{ $link($isActive('pdf-annotator*')) }}"><x-icon name="pdf" :size="18" />PDF annotator</a>
            <a href="{{ route('dashboard-studio.index') }}" class="{{ $link($isActive('dashboard-studio*')) }}"><x-icon name="chart" :size="18" />Law data dashboards</a>
        </div>
    </details>

    {{-- SETTINGS --}}
    @auth
        <details class="vn-nav-group" data-filter-item data-nav-group="settings" @if($groups['settings']) open @endif>
            <summary class="vn-nav-group-title"><span>Settings</span><x-icon name="chevron" :size="16" class="vn-nav-chevron" /></summary>
            <div class="vn-nav-items">
                <a href="{{ route('profile.index') }}" class="{{ $link($isActive('profile')) }}"><x-icon name="settings" :size="18" />All settings</a>
                @if($showAllProfileTypes || ! $isCompanyAccount)
                    <a href="{{ route('profile.advocate.index') }}" class="{{ $link($isActive('profile/advocate*')) }}"><x-icon name="user" :size="18" />Advocate profile</a>
                @endif
                @if($showAllProfileTypes)
                    <a href="{{ route('profile.personal.index') }}" class="{{ $link($isActive('profile/personal*')) }}"><x-icon name="user" :size="18" />Personal profile</a>
                    <a href="{{ route('profile.professional.index') }}" class="{{ $link($isActive('profile/professional*')) }}"><x-icon name="briefcase" :size="18" />Professional profile</a>
                @endif
                @if($showAllProfileTypes || $isCompanyAccount)
                    <a href="{{ route('firm-profile.index') }}" class="{{ $link($isActive('firm-profile*')) }}"><x-icon name="building" :size="18" />Firm / company profile</a>
                @endif
                <a href="{{ route('profile.contacts.index') }}" class="{{ $link($isActive('profile/contacts*')) }}"><x-icon name="people" :size="18" />Contact information</a>
                <a href="{{ route('profile.practice-states.index') }}" class="{{ $link($isActive('profile/practice-states*')) }}"><x-icon name="map" :size="18" />Practice States</a>
                <a href="{{ route('profile.invoice-settings.index') }}" class="{{ $link($isActive('profile/invoice-settings*')) }}"><x-icon name="receipt" :size="18" />Invoice &amp; GST</a>
                <a href="{{ route('profile.tax-compliance') }}" class="{{ $link($isActive('profile/tax-compliance*', 'tds*', 'professional-tax*')) }}"><x-icon name="wallet" :size="18" />Tax &amp; compliance</a>
            </div>
        </details>
    @endauth

    <p data-filter-empty hidden class="px-3 py-2 text-xs text-slate-400">Nothing in the menu matches.</p>
</nav>

<script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
    // Remember which menu groups the person opened or closed, so the menu stays as they left it.
    (function () {
        let saved = {};

        try {
            saved = JSON.parse(localStorage.getItem('vn-nav-groups') || '{}');
        } catch (error) {}

        document.querySelectorAll('[data-nav-group]').forEach(function (group) {
            const name = group.dataset.navGroup;
            const isCurrent = group.open;

            if (!isCurrent && saved[name] === true) {
                group.open = true;
            }

            group.addEventListener('toggle', function () {
                saved[name] = group.open;

                try {
                    localStorage.setItem('vn-nav-groups', JSON.stringify(saved));
                } catch (error) {}
            });
        });
    })();
</script>
