<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <title>@yield('title', 'VakilNiti') | VakilNiti</title>
    <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
        try {
            if (localStorage.getItem('vn-sidebar-collapsed') === '1') {
                document.documentElement.classList.add('vn-sidebar-collapsed');
            }
        } catch (error) {}
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')

    <style>
        body {
            background: #f6f4ee;
            color: #1b1913;
            font-family: var(--font-sans), sans-serif;
        }

        .vn-sidebar {
            background: #15140f;
            color: #fbf8ef;
            border-right: 1px solid #342f22;
        }

        .vn-sidebar h1,
        .vn-content h1,
        .vn-content h2,
        .vn-content h3,
        #vn-auth-dialog h2 {
            font-family: var(--font-serif), serif;
            letter-spacing: -0.02em;
        }

        .vn-main-header {
            box-shadow: 0 1px 3px rgba(27, 25, 19, .06);
        }

        .vn-nav-heading {
            color: #c4a95c;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .09em;
            text-transform: uppercase;
            padding: 0 12px;
            margin-bottom: 9px;
        }

        .vn-nav-link {
            display: flex;
            align-items: center;
            min-height: 38px;
            padding: 9px 12px;
            margin: 3px 0;
            border-radius: 7px;
            color: #eee8d8 !important;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
        }

        .vn-nav-link:hover {
            background: #2e2a20;
            color: #fff !important;
        }

        .vn-nav-link.vn-active {
            background: #896615;
            color: #fff !important;
            font-weight: 700;
            box-shadow: inset 3px 0 0 #e7cb78;
        }

        .vn-nav-child {
            margin-left: 12px;
            padding-left: 10px;
            border-left: 1px solid #514a37;
        }

        .vn-nav-child .vn-nav-link {
            font-size: 12px;
            color: #ddd5c1 !important;
        }

        .vn-nav-section {
            padding-bottom: 15px;
            margin-bottom: 15px;
            border-bottom: 1px solid #342f22;
        }

        .vn-content .text-slate-500 { color: #655f52 !important; }
        .vn-content .text-slate-600 { color: #4a453a !important; }
        .vn-content .text-slate-400 { color: #756d5e !important; }

        .vn-content h2,
        .vn-content h3 { color: #171610; }

        .vn-content input:focus,
        .vn-content select:focus,
        .vn-content textarea:focus,
        #vn-auth-dialog input:focus,
        #vn-auth-dialog select:focus {
            border-color: #a98220;
            box-shadow: 0 0 0 3px rgba(169, 130, 32, .18);
            outline: none;
        }

        .vn-centered-dialog {
            position: fixed;
            top: 50%;
            left: 50%;
            margin: 0;
            transform: translate(-50%, -50%);
            max-height: 90vh;
        }

        .vn-sidebar a:focus-visible,
        .vn-content a:focus-visible {
            outline: 3px solid #d5b454;
            outline-offset: 2px;
        }

        /* VakilNiti workspace system: responsive shell and accessible navigation. */
        :root {
            --vn-ink: #142235;
            --vn-ink-soft: #20334a;
            --vn-accent: #167c73;
            --vn-accent-dark: #11645d;
            --vn-canvas: #f4f6f8;
            --vn-line: #e2e8ef;
        }
        body { background: var(--vn-canvas); color: #172536; }
        .vn-sidebar { background: var(--vn-ink); border-right-color: #2b3b50; }
        .vn-sidebar .vn-nav-heading { color: #91c9c1; }
        .vn-nav-section { border-bottom-color: #2a3b50; }
        .vn-nav-link { min-height: 42px; border-radius: 9px; color: #e4ebf3 !important; }
        .vn-nav-link:hover { background: #263950; }
        .vn-nav-link.vn-active { background: #176f68; box-shadow: inset 3px 0 0 #8ed2c8; }
        .vn-nav-child { border-left-color: #43556a; }
        .vn-main-header { position: sticky; top: 0; z-index: 25; background: rgba(255,255,255,.94); backdrop-filter: blur(14px); }
        .vn-content { min-width: 0; }
        .vn-main-content { width: 100%; max-width: 1800px; margin-inline: auto; }
        .vn-mobile-nav { display: none; }
        .vn-sidebar-backdrop { display: none; }
        .vn-menu-toggle { display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; flex: 0 0 40px; border: 1px solid #dbe3eb; border-radius: 12px; background: #fff; color: #172536; }
        .vn-profile-menu summary::-webkit-details-marker { display: none; }
        @media (min-width: 768px) {
            html.vn-sidebar-collapsed .vn-sidebar { display: none; }
        }
        .vn-nav-link, .vn-main-header button, .vn-main-header a { -webkit-tap-highlight-color: transparent; }
        .vn-sidebar a:focus-visible, .vn-content a:focus-visible, button:focus-visible { outline: 3px solid #55b9ad; outline-offset: 2px; }
        @media (max-width: 767px) {
            body { padding-bottom: calc(72px + env(safe-area-inset-bottom)); }
            .vn-sidebar { position: fixed; z-index: 60; inset: 0 auto 0 0; width: min(88vw, 330px); height: 100dvh; transform: translateX(-105%); transition: transform .24s ease; box-shadow: 20px 0 50px rgba(10,25,40,.22); }
            body.vn-menu-open .vn-sidebar { transform: translateX(0); }
            .vn-sidebar-backdrop { display: block; position: fixed; inset: 0; z-index: 55; padding: 0; border: 0; background: rgba(8,20,34,.52); opacity: 0; pointer-events: none; transition: opacity .2s ease; }
            body.vn-menu-open .vn-sidebar-backdrop { opacity: 1; pointer-events: auto; }
            .vn-menu-toggle { display: inline-flex; align-items: center; justify-content: center; width: 42px; height: 42px; flex: 0 0 42px; border: 1px solid #dbe3eb; border-radius: 12px; background: #fff; color: #172536; }
            .vn-main-header { min-height: 62px; padding: 10px 14px; gap: 10px; }
            .vn-main-header h2 { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 15px; }
            .vn-main-header .vn-header-date { display: none; }
            .vn-main-header form button { min-height: 40px; padding: 8px 11px; }
            .vn-main-content { padding: 16px 14px 22px; }
            .vn-mobile-nav { position: fixed; z-index: 45; display: grid; grid-template-columns: repeat(5, minmax(0,1fr)); inset: auto 0 0; padding: 8px 8px calc(8px + env(safe-area-inset-bottom)); border-top: 1px solid #dfe6ed; background: rgba(255,255,255,.97); backdrop-filter: blur(16px); box-shadow: 0 -8px 28px rgba(20,34,53,.08); }
            .vn-mobile-nav a, .vn-mobile-nav button { display: flex; min-width: 0; min-height: 48px; flex-direction: column; align-items: center; justify-content: center; gap: 3px; border: 0; border-radius: 10px; background: transparent; color: #657386; font-size: 10px; font-weight: 650; text-decoration: none; }
            .vn-mobile-nav .vn-mobile-icon { font-size: 18px; line-height: 1; }
            .vn-mobile-nav .is-current { color: #126c64; background: #e8f4f2; }
            footer { padding: 14px !important; text-align: center; }
        }
        @media (min-width: 768px) and (max-width: 1100px) {
            .vn-sidebar { width: 250px; }
            .vn-main-content { padding: 22px; }
        }
        @media print {
            .vn-sidebar, .vn-main-header, .vn-mobile-nav, .vn-sidebar-backdrop { display: none !important; }
            body { padding-bottom: 0 !important; background: #fff !important; }
            .vn-main-content { padding: 0 !important; }
        }
        @media (prefers-reduced-motion: reduce) {
            .vn-sidebar, .vn-sidebar-backdrop { transition: none !important; }
        }
    </style>
</head>

@php
    $currentUser = auth()->user();
    $isCompanyAccount = $currentUser?->account_type === 'company';
    $showAllProfileTypes = (bool) ($currentUser?->is_admin ?? false);


    $practiceStates = [];
    $districtPracticeStates = [];

    // Sidebar practice jurisdictions: four batched queries, however many states are saved.
    if ($currentUser) {
        $practiceRows = \Illuminate\Support\Facades\DB::table('firm_practice_states')
            ->where('user_id', $currentUser->id)
            ->where('is_active', true)
            ->orderBy('state_name')
            ->get();

        $practiceStates = $practiceRows->pluck('state_name')->all();

        // High Court-only states do not appear in District Courts.
        $districtRows = $practiceRows->reject(fn ($row) => ($row->district_selection_mode ?: 'all') === 'none');

        if ($districtRows->isNotEmpty()) {
            $statesByName = \Illuminate\Support\Facades\DB::table('states')
                ->whereIn('name', $districtRows->pluck('state_name'))
                ->get(['id', 'name'])
                ->keyBy('name');

            $selectedIdsByRow = \Illuminate\Support\Facades\DB::table('firm_practice_districts')
                ->whereIn('firm_practice_state_id', $districtRows->pluck('id'))
                ->where('user_id', $currentUser->id)
                ->where('is_active', true)
                ->get(['firm_practice_state_id', 'judicial_district_id'])
                ->groupBy('firm_practice_state_id')
                ->map(fn ($rows) => $rows->pluck('judicial_district_id')->map(fn ($id) => (int) $id)->all());

            $districtsByState = \Illuminate\Support\Facades\DB::table('judicial_districts')
                ->whereIn('state_id', $statesByName->pluck('id'))
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->orderBy('district_name')
                ->get(['id', 'state_id', 'district_name', 'district_code'])
                ->groupBy('state_id');

            foreach ($districtRows as $practiceRow) {
                $state = $statesByName->get($practiceRow->state_name);
                $districts = $state ? ($districtsByState->get($state->id) ?? collect()) : collect();

                if (($practiceRow->district_selection_mode ?: 'all') === 'selected') {
                    $selectedIds = $selectedIdsByRow->get($practiceRow->id, []);
                    $districts = $districts->filter(fn ($district) => in_array((int) $district->id, $selectedIds, true))->values();
                }

                if ($districts->isNotEmpty()) {
                    $districtPracticeStates[] = [
                        'state_name' => $practiceRow->state_name,
                        'districts' => $districts,
                    ];
                }
            }
        }
    }
@endphp

<body class="min-h-screen">

<button type="button" class="vn-sidebar-backdrop" id="vn-sidebar-backdrop" aria-label="Close navigation"></button>
<div class="min-h-screen flex">

    <aside id="vn-sidebar" class="vn-sidebar w-72 shrink-0 flex flex-col" aria-label="Main navigation">

        <div class="px-6 py-6 border-b border-slate-700">
            <a href="{{ route('dashboard') }}" class="inline-flex rounded-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-amber-300">
                <span class="inline-flex items-center rounded-lg bg-white px-3 py-2">
                    <img src="{{ asset('images/VakilNiti-logo.png') }}"
                         alt="VakilNiti"
                         class="h-12 w-auto max-w-[210px] object-contain">
                </span>
            </a>
        </div>

        @php
    $vnActiveSection = null;

            if (
                request()->is('dashboard-studio*')
            ) {
                $vnActiveSection = 'dashboards';
            } elseif (
        request()->is('court-management*') ||
        request()->is('court-directory*') ||
        request()->is('court-catalogue*') ||
        request()->is('court-integrations*')
    ) {
        $vnActiveSection = 'courts';
    } elseif (
        request()->is('case-dashboard*') ||
        request()->is('cases*') ||
        request()->is('case-calendar*') ||
        request()->is('hearing-history*') ||
        request()->is('tasks*')
    ) {
        $vnActiveSection = 'cases';
    } elseif (
        request()->is('client-management*') ||
        request()->is('clients*') ||
        request()->is('billing*')
    ) {
        $vnActiveSection = 'clients';
    } elseif (
        request()->is('police-administration*')
    ) {
        $vnActiveSection = 'police';
    } elseif (
        request()->is('bar-associations*')
    ) {
        $vnActiveSection = 'bars';
    } elseif (
        request()->is('court-fee*')
    ) {
        $vnActiveSection = 'court-fee';
    } elseif (
        request()->is('bare-acts*')
    ) {
        $vnActiveSection = 'bare-acts';
    } elseif (
        request()->is('new-criminal-laws*')
    ) {
        $vnActiveSection = 'criminal-laws';
    } elseif (
        request()->is('miscellaneous-forms*')
    ) {
        $vnActiveSection = 'forms';
    } elseif (
        request()->is('pdf-annotator*')
    ) {
        $vnActiveSection = 'pdf';
    } elseif (
        request()->is('profile*') ||
        request()->is('firm-profile*')
    ) {
        $vnActiveSection = 'profile';
    }
@endphp

<nav class="flex-1 overflow-y-auto px-3 py-5 space-y-2">

    {{-- COURTS --}}
    <section class="vn-nav-section">
        <button type="button"
            data-vn-toggle="courts"
            aria-expanded="{{ $vnActiveSection === 'courts' ? 'true' : 'false' }}"
            data-vn-open-section="courts" data-vn-open-url="/court-management"
            class="vn-nav-heading w-full cursor-pointer flex items-center justify-between text-left">
            <span>Courts</span>
            <span class="text-xs">&#9662;</span>
        </button>

        <div id="vn-submenu-courts" data-vn-submenu
            class="mt-2 space-y-1"
            @if($vnActiveSection !== 'courts') hidden @endif>

            <a href="{{ route('court-directory.supreme-court') }}"
               class="vn-nav-link {{ request('level') === 'supreme' || request()->is('court-directory/supreme-court') ? 'vn-active' : '' }}">
                Supreme Court of India
            </a>

            <a href="{{ route('court-directory.display-boards') }}"
               class="vn-nav-link {{ request()->is('court-directory/display-boards') ? 'vn-active' : '' }}">
                Display boards
            </a>

            <details class="mt-2"
                @if(request('level') === 'high-court') open @endif>
                <summary class="vn-nav-link cursor-pointer list-none flex items-center justify-between">
                    <span>High Courts</span>
                    <span class="text-xs">&#9662;</span>
                </summary>

                <div class="vn-nav-child mt-1">
                    @forelse($practiceStates as $practiceState)
                        <a href="{{ url('/court-directory') }}?level=high-court&state={{ urlencode($practiceState) }}"
                           class="vn-nav-link {{ request('level') === 'high-court' && request('state') === $practiceState ? 'vn-active' : '' }}">
                            {{ $practiceState }}
                        </a>
                    @empty
                        <p class="px-3 py-2 text-xs text-slate-400">
                            No practice states selected.
                        </p>
                    @endforelse
                </div>
            </details>

                        <details class="mt-1"
                @if(request('level') === 'district' || request()->is('court-directory/districts/*')) open @endif>
                <summary class="vn-nav-link cursor-pointer list-none flex items-center justify-between">
                    <span>District &amp; Subordinate Courts</span>
                    <span class="text-xs">&#9662;</span>
                </summary>

                <div class="vn-nav-child mt-1">
                    @forelse($districtPracticeStates as $districtState)
                        <details class="mt-1"
                            @if(request('level') === 'district' && request('state') === $districtState['state_name']) open @endif
                            @if(request()->is('court-directory/districts/*') && isset($districtState['districts']) && $districtState['districts']->contains('id', (int) request()->segment(3))) open @endif>
                            <summary class="vn-nav-link cursor-pointer list-none flex items-center justify-between">
                                <span>{{ $districtState['state_name'] }}</span>
                                <span class="text-xs">&#9662;</span>
                            </summary>

                            <div class="vn-nav-child mt-1">
                                @foreach($districtState['districts'] as $district)
                                    <a href="{{ route('court-directory.districts.show', $district->id) }}"
                                       class="vn-nav-link {{ request()->is('court-directory/districts/'.$district->id) ? 'vn-active' : '' }}">
                                        {{ $district->district_name }}
                                    </a>
                                @endforeach
                            </div>
                        </details>
                    @empty
                        <p class="px-3 py-2 text-xs text-slate-400">
                            No districts selected for district-court practice.
                        </p>
                    @endforelse
                </div>
            </details>

            <a href="{{ route('court-catalogue.index') }}"
               class="vn-nav-link {{ request()->is('court-catalogue*') ? 'vn-active' : '' }}">
                Appellate Courts &amp; Tribunals
            </a>

            <a href="{{ route('court-integrations.ecourts.search') }}"
               class="vn-nav-link {{ request()->is('court-integrations/ecourts/search') ? 'vn-active' : '' }}">
                Official Court Search
            </a>

            <a href="{{ route('court-directory.index') }}"
               class="vn-nav-link {{ request()->is('court-directory') && !request('level') ? 'vn-active' : '' }}">
                Official Court Directory
            </a>
        </div>
    </section>

    {{-- CASE MANAGEMENT SYSTEM --}}
    <section class="vn-nav-section">
        <button type="button"
            data-vn-toggle="cases"
            aria-expanded="{{ $vnActiveSection === 'cases' ? 'true' : 'false' }}"
            data-vn-open-section="cases" data-vn-open-url="/case-dashboard"
            class="vn-nav-heading w-full cursor-pointer flex items-center justify-between text-left">
            <span>Case Management System</span>
            <span class="text-xs">&#9662;</span>
        </button>

        <div id="vn-submenu-cases" data-vn-submenu
            class="mt-2 space-y-1"
            @if($vnActiveSection !== 'cases') hidden @endif>

            <a href="{{ route('case-dashboard.index') }}"
               class="vn-nav-link {{ request()->is('case-dashboard*') ? 'vn-active' : '' }}">
                My Case Dashboard
            </a>

            <a href="{{ route('cases.index') }}"
               class="vn-nav-link {{ request()->is('cases*') ? 'vn-active' : '' }}">
                My Cases &amp; Case Status
            </a>

            <a href="{{ route('cases.calendar') }}"
               class="vn-nav-link {{ request()->is('case-calendar*') ? 'vn-active' : '' }}">
                Calendar &amp; Hearings
            </a>

            <a href="{{ route('hearings.history') }}"
               class="vn-nav-link {{ request()->is('hearing-history*') ? 'vn-active' : '' }}">
                Hearing History
            </a>

            <a href="{{ url('/tasks') }}"
               class="vn-nav-link {{ request()->is('tasks*') ? 'vn-active' : '' }}">
                Tasks &amp; Reminders
            </a>
        </div>
    </section>

    {{-- CLIENT MANAGEMENT SYSTEM --}}
    <section class="vn-nav-section">
        <button type="button"
            data-vn-toggle="clients"
            aria-expanded="{{ $vnActiveSection === 'clients' ? 'true' : 'false' }}"
            data-vn-open-section="clients" data-vn-open-url="/client-management"
            class="vn-nav-heading w-full cursor-pointer flex items-center justify-between text-left">
            <span>Client Management System</span>
            <span class="text-xs">&#9662;</span>
        </button>

        <div id="vn-submenu-clients" data-vn-submenu
            class="mt-2 space-y-1"
            @if($vnActiveSection !== 'clients') hidden @endif>

            <a href="{{ route('clients.index') }}"
               class="vn-nav-link {{ request()->is('clients*') ? 'vn-active' : '' }}">
                All Clients
            </a>

            <a href="{{ route('billing.index') }}"
               class="vn-nav-link {{ request()->is('billing*') ? 'vn-active' : '' }}">
                Invoices &amp; Payments
            </a>
        </div>
    </section>


    <section class="vn-nav-section">
    <button type="button"
        data-vn-toggle="police"
        aria-expanded="{{ $vnActiveSection === 'police' ? 'true' : 'false' }}"
        data-vn-open-section="police" data-vn-open-url="/police-administration"
        class="vn-nav-heading w-full cursor-pointer flex items-center justify-between text-left">
        <span>Police Administration</span>
        <span class="text-xs">&#9662;</span>
    </button>

    <div id="vn-submenu-police" data-vn-submenu
        class="mt-2 space-y-1"
        @if($vnActiveSection !== 'police') hidden @endif>
        <a href="/police-administration#police-stations" class="vn-nav-link">Police Stations</a>
        <a href="/police-administration#state-police" class="vn-nav-link">State Police Departments</a>
        <a href="/police-administration#police-districts" class="vn-nav-link">Police Districts &amp; Ranges</a>
        <a href="/police-administration#official-portals" class="vn-nav-link">Official Police Portals</a>
    </div>
</section>
    <section class="vn-nav-section">
    <button type="button"
        data-vn-toggle="bars"
        aria-expanded="{{ $vnActiveSection === 'bars' ? 'true' : 'false' }}"
        data-vn-open-section="bars" data-vn-open-url="/bar-associations"
        class="vn-nav-heading w-full cursor-pointer flex items-center justify-between text-left">
        <span>Bar Associations</span>
        <span class="text-xs">&#9662;</span>
    </button>

    <div id="vn-submenu-bars" data-vn-submenu
        class="mt-2 space-y-1"
        @if($vnActiveSection !== 'bars') hidden @endif>
        <a href="{{ route('bar-associations.index') }}#national" class="vn-nav-link">National Associations</a>
        <a href="{{ route('bar-associations.index') }}#state-high-court" class="vn-nav-link">State &amp; High Court Associations</a>
        <a href="{{ route('bar-associations.index') }}#district" class="vn-nav-link">District Bar Associations</a>
        <a href="{{ route('bar-associations.index') }}#bar-councils" class="vn-nav-link">Bar Councils</a>
    </div>
</section>
    <section class="vn-nav-section">
    <button type="button"
        data-vn-toggle="court-fee"
        aria-expanded="{{ $vnActiveSection === 'court-fee' ? 'true' : 'false' }}"
        data-vn-open-section="court-fee" data-vn-open-url="/court-fee"
        class="vn-nav-heading w-full cursor-pointer flex items-center justify-between text-left">
        <span>Court Fee</span>
        <span class="text-xs">&#9662;</span>
    </button>

    <div id="vn-submenu-court-fee" data-vn-submenu
        class="mt-2 space-y-1"
        @if($vnActiveSection !== 'court-fee') hidden @endif>
        <a href="/court-fee#calculator" class="vn-nav-link">Court Fee Calculator</a>
        <a href="/court-fee#fee-schedules" class="vn-nav-link">Court Fee Schedules</a>
        <a href="/court-fee#rules" class="vn-nav-link">Rules &amp; Exemptions</a>
    </div>
</section>
    <section class="vn-nav-section">
        <button type="button"
            data-vn-toggle="bare-acts"
            aria-expanded="{{ $vnActiveSection === 'bare-acts' ? 'true' : 'false' }}"
            data-vn-open-section="bare-acts" data-vn-open-url="/bare-acts"
            class="vn-nav-heading w-full cursor-pointer flex items-center justify-between text-left">
            <span>Bare Acts</span>
            <span class="text-xs">&#9662;</span>
        </button>

        <div id="vn-submenu-bare-acts" data-vn-submenu
            class="mt-2 space-y-1"
            @if($vnActiveSection !== 'bare-acts') hidden @endif>

            <a href="{{ route('bare-acts.index') }}#overview" class="vn-nav-link">
                Overview
            </a>

            <a href="{{ route('bare-acts.index') }}#central-acts" class="vn-nav-link">
                Central Acts
            </a>

            <a href="{{ route('bare-acts.index') }}#criminal-laws" class="vn-nav-link">
                Criminal Law Codes
            </a>

            <a href="{{ route('bare-acts.index') }}#sources" class="vn-nav-link">
                Official Sources
            </a>
        </div>
    </section>
    <section class="vn-nav-section">
    <a href="/new-criminal-laws"
       class="vn-nav-heading w-full cursor-pointer flex items-center justify-between text-left {{ request()->is('new-criminal-laws*') ? 'vn-active' : '' }}">
        <span>New Criminal Laws</span>
    </a>
</section>
    <section class="vn-nav-section">
    <button type="button"
        data-vn-toggle="forms"
        aria-expanded="{{ $vnActiveSection === 'forms' ? 'true' : 'false' }}"
        data-vn-open-section="forms" data-vn-open-url="/miscellaneous-forms"
        class="vn-nav-heading w-full cursor-pointer flex items-center justify-between text-left">
        <span>Miscellaneous Forms</span>
        <span class="text-xs">&#9662;</span>
    </button>

    <div id="vn-submenu-forms" data-vn-submenu
        class="mt-2 space-y-1"
        @if($vnActiveSection !== 'forms') hidden @endif>
        <a href="{{ route('miscellaneous-forms.index') }}#court-forms" class="vn-nav-link">Court Forms</a>
        <a href="{{ route('miscellaneous-forms.index') }}#civil-criminal" class="vn-nav-link">Civil &amp; Criminal Forms</a>
        <a href="{{ route('miscellaneous-forms.index') }}#affidavits" class="vn-nav-link">Affidavits &amp; Applications</a>
        <a href="{{ route('miscellaneous-forms.index') }}#police-rti" class="vn-nav-link">Police &amp; RTI Forms</a>
    </div>
</section>
    <section class="vn-nav-section">
    <a href="/pdf-annotator"
       class="vn-nav-heading w-full cursor-pointer flex items-center justify-between text-left {{ request()->is('pdf-annotator*') ? 'vn-active' : '' }}">
        <span>PDF Annotator</span>
    </a>
</section>
    {{-- INDIA LAW AND JUSTICE DATA --}}
    <section class="vn-nav-section">
        <button type="button"
            data-vn-toggle="dashboards"
            aria-expanded="{{ $vnActiveSection === 'dashboards' ? 'true' : 'false' }}"
            data-vn-open-section="dashboards" data-vn-open-url="/dashboard-studio"
            class="vn-nav-heading w-full cursor-pointer flex items-center justify-between text-left">
            <span>India Law &amp; Justice Data</span>
            <span class="text-xs">&#9662;</span>
        </button>

        <div id="vn-submenu-dashboards" data-vn-submenu
            class="mt-2 space-y-1"
            @if($vnActiveSection !== 'dashboards') hidden @endif>
            <a href="{{ route('dashboard-studio.index') }}"
               class="vn-nav-link {{ request()->is('dashboard-studio*') ? 'vn-active' : '' }}">
                Create Law Data Dashboards
            </a>
        </div>
    </section>

    {{-- PROFILE SETTINGS --}}
    <section class="vn-nav-section">
        <button type="button"
            data-vn-toggle="profile"
            aria-expanded="{{ $vnActiveSection === 'profile' ? 'true' : 'false' }}"
            data-vn-open-section="profile" data-vn-open-url="/profile"
            class="vn-nav-heading w-full cursor-pointer flex items-center justify-between text-left">
            <span>Profile Settings</span>
            <span class="text-xs">&#9662;</span>
        </button>

        <div id="vn-submenu-profile" data-vn-submenu
            class="mt-2 space-y-1"
            @if($vnActiveSection !== 'profile') hidden @endif>

            {{-- PROFILE INFORMATION GROUP --}}
            <details class="mt-1"
                @if(
                    ($showAllProfileTypes && (
                        request()->is('profile/personal*') ||
                        request()->is('profile/professional*') ||
                        request()->is('profile/advocate*') ||
                        request()->is('firm-profile*')
                    )) ||
                    ($isCompanyAccount && request()->is('firm-profile*')) ||
                    (!$isCompanyAccount && request()->is('profile/advocate*')) ||
                    request()->is('profile/contacts*')
                ) open @endif>

                <summary class="vn-nav-link cursor-pointer list-none flex items-center justify-between">
                    <span>Profile Information</span>
                    <span class="text-xs">&#9662;</span>
                </summary>

                <div class="vn-nav-child mt-1">
                    @if($showAllProfileTypes)
                        <a href="{{ route('profile.personal.index') }}"
                           class="vn-nav-link {{ request()->is('profile/personal*') ? 'vn-active' : '' }}">
                            Personal Profile
                        </a>
                        <a href="{{ route('profile.advocate.index') }}"
                           class="vn-nav-link {{ request()->is('profile/advocate*') ? 'vn-active' : '' }}">
                            Advocate Profile
                        </a>
                        <a href="{{ route('profile.professional.index') }}"
                           class="vn-nav-link {{ request()->is('profile/professional*') ? 'vn-active' : '' }}">
                            Professional Profile
                        </a>
                        <a href="{{ route('firm-profile.index') }}"
                           class="vn-nav-link {{ request()->is('firm-profile*') ? 'vn-active' : '' }}">
                            Firm / Law Office / Company Profile
                        </a>
                    @elseif($isCompanyAccount)
                        <a href="{{ route('firm-profile.index') }}"
                           class="vn-nav-link {{ request()->is('firm-profile*') ? 'vn-active' : '' }}">
                            Firm / Law Office / Company Profile
                        </a>
                    @else
                        <a href="{{ route('profile.advocate.index') }}"
                           class="vn-nav-link {{ request()->is('profile/advocate*') ? 'vn-active' : '' }}">
                            Advocate Profile
                        </a>
                    @endif

                    <a href="{{ route('profile.contacts.index') }}"
                       class="vn-nav-link {{ request()->is('profile/contacts*') ? 'vn-active' : '' }}">
                        Contact Information
                    </a>
                </div>
            </details>

            {{-- PRACTICE JURISDICTIONS --}}
            <a href="{{ route('profile.practice-states.index') }}"
               class="vn-nav-link {{ request()->is('profile/practice-states*') ? 'vn-active' : '' }}">
                Practice States &amp; Jurisdictions
            </a>

            {{-- INVOICE AND TAX GROUP --}}
            <details class="mt-1"
                @if(
                    request()->is('profile/invoice-settings*') ||
                    request()->is('profile/tax-compliance*') ||
                    request()->is('tds*') ||
                    request()->is('professional-tax*')
                ) open @endif>

                <summary class="vn-nav-link cursor-pointer list-none flex items-center justify-between">
                    <span>Invoice &amp; Tax Settings</span>
                    <span class="text-xs">&#9662;</span>
                </summary>

                <div class="vn-nav-child mt-1">
                    <a href="{{ route('profile.invoice-settings.index') }}"
                       class="vn-nav-link {{ request()->is('profile/invoice-settings*') ? 'vn-active' : '' }}">
                        Invoice &amp; GST Settings
                    </a>

                    <a href="{{ route('profile.tax-compliance') }}"
                       class="vn-nav-link {{ request()->is('profile/tax-compliance*') || request()->is('tds*') || request()->is('professional-tax*') ? 'vn-active' : '' }}">
                        Tax &amp; Compliance
                    </a>
                </div>
            </details>

            {{-- SYSTEM PREFERENCES --}}
            <div class="vn-nav-link flex items-center justify-between">
                <span>System Preferences</span>
                <span class="text-xs text-slate-400">Planned</span>
            </div>

        </div>
    </section>

</nav>

<script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
    function vnOpenSection(section, dashboardPath) {
        const currentPath = window.location.pathname.replace(/\/+$/, '') || '/';
        const targetPath = dashboardPath.replace(/\/+$/, '') || '/';

        // From a subsection or another dashboard, navigate to this dashboard.
        if (currentPath !== targetPath) {
            window.location.href = dashboardPath;
            return;
        }

        // On its own dashboard, toggle the selected section.
        const selectedPanel = document.getElementById('vn-submenu-' + section);
        const currentlyOpen = selectedPanel && !selectedPanel.hidden;

        document.querySelectorAll('[data-vn-submenu]').forEach(function (panel) {
            panel.hidden = true;
        });

        document.querySelectorAll('[data-vn-toggle]').forEach(function (button) {
            button.setAttribute('aria-expanded', 'false');
        });

        if (!currentlyOpen && selectedPanel) {
            selectedPanel.hidden = false;

            const selectedButton = document.querySelector(
                '[data-vn-toggle="' + section + '"]'
            );

            if (selectedButton) {
                selectedButton.setAttribute('aria-expanded', 'true');
            }
        }
    }

    const vnSupportsHover = window.matchMedia('(hover: hover) and (pointer: fine)').matches;

    document.querySelectorAll('.vn-nav-section').forEach(function (container) {
        const toggle = container.querySelector('[data-vn-toggle]');

        if (toggle) {
            const submenu = document.getElementById('vn-submenu-' + toggle.dataset.vnToggle);
            let wasOpenBeforeReveal = null;

            function revealSubmenu() {
                if (wasOpenBeforeReveal === null) {
                    wasOpenBeforeReveal = !submenu.hidden;
                }

                submenu.hidden = false;
                toggle.setAttribute('aria-expanded', 'true');
            }

            function restoreSubmenu() {
                if (container.matches(':hover') || container.contains(document.activeElement)) {
                    return;
                }

                if (wasOpenBeforeReveal === false) {
                    submenu.hidden = true;
                    toggle.setAttribute('aria-expanded', 'false');
                }

                wasOpenBeforeReveal = null;
            }

            if (vnSupportsHover) {
                container.addEventListener('mouseenter', revealSubmenu);
            }

            container.addEventListener('mouseleave', restoreSubmenu);
            container.addEventListener('focusin', revealSubmenu);
            container.addEventListener('focusout', function (event) {
                if (!event.relatedTarget || !container.contains(event.relatedTarget)) {
                    window.requestAnimationFrame(restoreSubmenu);
                }
            });
        }

        container.querySelectorAll('details').forEach(function (details) {
            let wasOpenBeforeReveal = null;

            function revealDetails() {
                if (wasOpenBeforeReveal === null) {
                    wasOpenBeforeReveal = details.open;
                }

                details.open = true;
            }

            function restoreDetails() {
                if (details.matches(':hover') || details.contains(document.activeElement)) {
                    return;
                }

                if (wasOpenBeforeReveal === false) {
                    details.open = false;
                }

                wasOpenBeforeReveal = null;
            }

            if (vnSupportsHover) {
                details.addEventListener('mouseenter', revealDetails);
            }

            details.addEventListener('mouseleave', restoreDetails);
            details.addEventListener('focusin', revealDetails);
            details.addEventListener('focusout', function (event) {
                if (!event.relatedTarget || !details.contains(event.relatedTarget)) {
                    window.requestAnimationFrame(restoreDetails);
                }
            });
        });
    });
</script>


    </aside>

    <div class="flex-1 min-w-0 flex flex-col">

        <header class="vn-main-header min-h-16 bg-white border-b border-slate-200 flex items-center justify-between px-6 py-3">
            <button type="button" class="vn-menu-toggle" id="vn-menu-toggle" aria-label="Toggle sidebar" title="Show / hide menu" aria-controls="vn-sidebar" aria-expanded="false">
                <svg viewBox="0 0 24 24" width="21" height="21" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <div class="flex min-w-0 flex-1 items-center gap-3">
                @unless(request()->routeIs('dashboard'))
                    <x-back-link class="shrink-0" />
                @endunless
                <h2 class="min-w-0 truncate text-lg font-bold text-slate-900">
                    @yield('page-heading', 'VakilNiti')
                </h2>
            </div>

            <div class="flex items-center gap-3">
                <div class="vn-header-date text-sm font-medium text-slate-500">
                    {{ now()->format('d M Y') }}
                </div>

                @auth
                    <details class="vn-profile-menu relative">
                        <summary aria-label="Notifications{{ ($bellUnreadCount ?? 0) ? ', '.$bellUnreadCount.' unread' : '' }}"
                                 class="relative flex h-10 w-10 cursor-pointer list-none items-center justify-center rounded-full border border-slate-200 bg-slate-50 text-slate-700 hover:bg-slate-100">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                            @if($bellUnreadCount ?? 0)
                                <span class="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-red-600 px-1 text-[11px] font-bold text-white">{{ $bellUnreadCount > 9 ? '9+' : $bellUnreadCount }}</span>
                            @endif
                        </summary>
                        <div class="absolute right-0 z-40 mt-2 w-[min(22rem,calc(100vw-2rem))] overflow-hidden rounded-xl border border-slate-200 bg-white text-sm shadow-lg">
                            <div class="flex items-center justify-between gap-2 border-b border-slate-100 px-4 py-2.5">
                                <p class="font-semibold text-slate-900">Notifications</p>
                                @if($bellUnreadCount ?? 0)
                                    <form method="POST" action="{{ route('notifications.read-all') }}">
                                        @csrf
                                        <button type="submit" class="text-xs font-semibold text-[#16776e] hover:underline">Mark all read</button>
                                    </form>
                                @endif
                            </div>
                            <div class="max-h-96 overflow-y-auto">
                                @forelse($bellNotifications ?? [] as $notification)
                                    @include('notifications.partials.item', ['notification' => $notification])
                                @empty
                                    <p class="px-4 py-6 text-center text-slate-500">You're all caught up.</p>
                                @endforelse
                            </div>
                            <a href="{{ route('notifications.index') }}" class="block border-t border-slate-100 px-4 py-2.5 text-center text-xs font-semibold text-[#16776e] hover:bg-slate-50">View all</a>
                        </div>
                    </details>
                @endauth

                <details class="vn-profile-menu relative">
                    <summary aria-label="{{ auth()->check() ? 'Account menu' : 'Log in or create account' }}"
                             class="flex h-10 w-10 cursor-pointer list-none items-center justify-center rounded-full border border-slate-200 bg-slate-50 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                        @auth
                            {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(auth()->user()->name, 0, 1)) }}
                        @else
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
                        @endauth
                    </summary>
                    <div class="absolute right-0 z-40 mt-2 w-52 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 text-sm shadow-lg">
                        @auth
                            <p class="truncate px-4 py-2 font-semibold text-slate-900">{{ auth()->user()->name }}</p>
                            <a href="{{ route('profile.index') }}" class="block px-4 py-2 text-slate-700 hover:bg-slate-50">Profile</a>
                            <form method="POST" action="{{ auth()->user()->is_admin ? route('admin.logout') : route('user.logout') }}">
                                @csrf
                                <button type="submit" class="block w-full px-4 py-2 text-left text-red-700 hover:bg-red-50">Log out</button>
                            </form>
                        @else
                            <a href="{{ route('login') }}" data-vn-auth-open="login" class="block px-4 py-2 text-slate-700 hover:bg-slate-50">Log in</a>
                            <a href="{{ route('register') }}" data-vn-auth-open="register" class="block px-4 py-2 text-slate-700 hover:bg-slate-50">Create account</a>
                        @endauth
                    </div>
                </details>
            </div>
        </header>

        <main class="vn-content vn-main-content flex-1 p-6">
            @if(session('auth_login_success'))
                <div role="status" class="mb-5 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">
                    {{ session('auth_login_success') }}
                </div>
            @endif
            @if(request()->boolean('upload_too_large'))
                <div role="alert" class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
                    That file is larger than this server accepts (up to {{ \App\Http\Controllers\CaseFileImportController::uploadLimitMegabytes(1024) }} MB), so nothing was uploaded. Upload a smaller file, such as one calendar's .ics file instead of the whole .zip.
                </div>
            @endif
            @yield('content')
        </main>

        <footer class="border-t border-slate-200 bg-white px-6 py-4 text-xs text-slate-600">
            &copy; {{ date('Y') }} VakilNiti. All rights reserved.
        </footer>

    </div>
</div>

<nav class="vn-mobile-nav" aria-label="Quick navigation">
    <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'is-current' : '' }}"><span class="vn-mobile-icon">⌂</span><span>Home</span></a>
    <a href="{{ route('cases.index') }}" class="{{ request()->is('cases*') || request()->is('case-dashboard*') ? 'is-current' : '' }}"><span class="vn-mobile-icon">▤</span><span>Cases</span></a>
    <a href="{{ route('cases.calendar') }}" class="{{ request()->is('case-calendar*') ? 'is-current' : '' }}"><span class="vn-mobile-icon">▦</span><span>Calendar</span></a>
    <a href="{{ route('clients.index') }}" class="{{ request()->is('clients*') ? 'is-current' : '' }}"><span class="vn-mobile-icon">♙</span><span>Clients</span></a>
    <button type="button" id="vn-mobile-more" aria-label="Open all modules"><span class="vn-mobile-icon">☷</span><span>More</span></button>
</nav>

<script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
    (function () {
        const body = document.body;
        const toggle = document.getElementById('vn-menu-toggle');
        const backdrop = document.getElementById('vn-sidebar-backdrop');
        const more = document.getElementById('vn-mobile-more');
        const sidebar = document.getElementById('vn-sidebar');
        function setMenu(open) {
            body.classList.toggle('vn-menu-open', open);
            if (toggle) toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            if (toggle) toggle.setAttribute('aria-label', open ? 'Close navigation' : 'Open navigation');
        }
        if (toggle) toggle.addEventListener('click', () => {
            if (window.innerWidth >= 768) {
                const collapsed = document.documentElement.classList.toggle('vn-sidebar-collapsed');
                toggle.setAttribute('aria-label', collapsed ? 'Show sidebar' : 'Hide sidebar');
                try { localStorage.setItem('vn-sidebar-collapsed', collapsed ? '1' : '0'); } catch (error) {}

                return;
            }

            setMenu(!body.classList.contains('vn-menu-open'));
        });

        // Close the profile menu on outside click or Escape.
        document.addEventListener('click', (event) => {
            document.querySelectorAll('.vn-profile-menu[open]').forEach((menu) => {
                if (!menu.contains(event.target)) menu.removeAttribute('open');
            });
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') document.querySelectorAll('.vn-profile-menu[open]').forEach((menu) => menu.removeAttribute('open'));
        });
        if (backdrop) backdrop.addEventListener('click', () => setMenu(false));
        if (more) more.addEventListener('click', () => setMenu(true));
        if (sidebar) sidebar.querySelectorAll('a').forEach(link => link.addEventListener('click', () => setMenu(false)));
        document.addEventListener('keydown', event => { if (event.key === 'Escape') setMenu(false); });
        window.addEventListener('resize', () => { if (window.innerWidth >= 768) setMenu(false); });
    }());
</script>

@stack('scripts')

<dialog id="vn-auth-dialog" aria-labelledby="vn-auth-title"
        class="vn-centered-dialog w-[94vw] max-w-lg overflow-y-auto rounded-2xl border border-slate-200 p-0 shadow-2xl backdrop:bg-slate-900/60">
    <div class="p-6 sm:p-8">
        <div class="mb-5 flex items-start justify-between gap-4">
            <div>
                <h2 id="vn-auth-title" class="text-xl font-bold text-slate-900">Log in to VakilNiti</h2>
                <p id="vn-auth-description" class="mt-1 text-sm text-slate-600">Access your private workspace and work records.</p>
            </div>
            <button type="button" id="vn-auth-close" aria-label="Close account dialog"
                    class="rounded-lg px-3 py-1 text-xl text-slate-500 hover:bg-slate-100">&times;</button>
        </div>

        <section data-vn-auth-panel="login">
            @if(session('status'))
                <div role="status" class="mb-4 rounded-lg bg-green-50 p-3 text-sm text-green-800">{{ session('status') }}</div>
            @endif
            @if($errors->login->any())
                <div role="alert" class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                    @foreach($errors->login->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif
            @if(session('show_login_otp') || session('login_otp_mobile'))
                <p class="mb-4 text-sm text-slate-600">
                    Enter the four-digit OTP sent to {{ session('login_otp_mobile') }}.
                </p>
                <form method="POST" action="{{ route('login.verify.submit') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label for="vn-login-otp" class="mb-1 block text-sm font-medium text-slate-700">Phone OTP</label>
                        <input id="vn-login-otp" name="otp" inputmode="numeric" pattern="[0-9]{6}" maxlength="6"
                               required autocomplete="one-time-code"
                               class="w-full rounded-lg border border-slate-300 p-3 text-center text-xl tracking-widest">
                    </div>
                    <button type="submit" class="w-full rounded-lg bg-blue-700 px-4 py-3 font-semibold text-white hover:bg-blue-800">
                        Verify and log in
                    </button>
                </form>
                <form method="POST" action="{{ route('login.submit') }}" class="mt-4 text-center">
                    @csrf
                    <input type="hidden" name="mobile" value="{{ session('login_otp_mobile') }}">
                    <button type="submit" class="text-sm font-semibold text-blue-700 underline">Resend OTP</button>
                </form>
            @else
                <form method="POST" action="{{ route('login.submit') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label for="vn-login-mobile" class="mb-1 block text-sm font-medium text-slate-700">Phone number</label>
                        <input id="vn-login-mobile" type="tel" name="mobile" value="{{ old('mobile') }}" required
                               placeholder="10-digit Indian mobile number" autocomplete="tel"
                               class="w-full rounded-lg border border-slate-300 p-3">
                    </div>
                    <button type="submit" class="w-full rounded-lg bg-blue-700 px-4 py-3 font-semibold text-white hover:bg-blue-800">
                        Send OTP
                    </button>
                </form>
            @endif
            <p class="mt-5 text-center text-sm text-slate-600">
                New to VakilNiti?
                <a href="{{ route('register') }}" data-vn-auth-open="register" class="font-semibold text-blue-700 underline">Create an account</a>
            </p>
        </section>

        <section data-vn-auth-panel="register" hidden>
            @if($errors->registration->any())
                <div role="alert" class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                    @foreach($errors->registration->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif
            <form method="POST" action="{{ route('register.send-otp') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="vn-register-mobile" class="mb-1 block text-sm font-medium text-slate-700">Mobile number</label>
                    <input id="vn-register-mobile" type="tel" name="mobile" value="{{ old('mobile') }}"
                           placeholder="10-digit Indian mobile number or +91..." autocomplete="tel"
                           required class="w-full rounded-lg border border-slate-300 p-3">
                </div>
                <button type="submit" class="w-full rounded-lg bg-blue-700 px-4 py-3 font-semibold text-white hover:bg-blue-800">
                    Continue with phone OTP
                </button>
            </form>
            <p class="mt-5 text-center text-sm text-slate-600">
                Already registered?
                <a href="{{ route('login') }}" data-vn-auth-open="login" class="font-semibold text-blue-700 underline">Log in</a>
            </p>
        </section>

        <section data-vn-auth-panel="verify" hidden>
            @if(session('status'))
                <div role="status" class="mb-4 rounded-lg bg-green-50 p-3 text-sm text-green-800">{{ session('status') }}</div>
            @endif
            @if($errors->verification->any())
                <div role="alert" class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                    @foreach($errors->verification->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif
            <p class="mb-5 text-sm text-slate-600">Enter the four-digit OTP sent to your mobile number.</p>
            <form method="POST" action="{{ route('register.verify.submit') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="vn-register-otp" class="mb-1 block text-sm font-medium text-slate-700">Four-digit OTP</label>
                    <input id="vn-register-otp" name="otp" inputmode="numeric" pattern="[0-9]{6}" maxlength="6"
                           required autocomplete="one-time-code"
                           class="w-full rounded-lg border border-slate-300 p-3 text-center text-xl tracking-widest">
                </div>
                <button type="submit" class="w-full rounded-lg bg-blue-700 px-4 py-3 font-semibold text-white hover:bg-blue-800">
                    Verify and create account
                </button>
            </form>
            <form method="POST" action="{{ route('register.resend-otp') }}" class="mt-4 text-center">
                @csrf
                <button type="submit" class="text-sm font-semibold text-blue-700 underline">Resend OTP</button>
            </form>
            <p class="mt-5 text-center text-sm">
                <a href="{{ route('register') }}" data-vn-auth-open="register" class="font-semibold text-blue-700 underline">Start again</a>
            </p>
        </section>
    </div>
</dialog>

<script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
    (function () {
        const authDialog = document.getElementById('vn-auth-dialog');
        const authClose = document.getElementById('vn-auth-close');
        const authPanels = document.querySelectorAll('[data-vn-auth-panel]');
        const authTitle = document.getElementById('vn-auth-title');
        const authDescription = document.getElementById('vn-auth-description');
        const authModes = {
            login: {
                title: 'Log in to VakilNiti',
                description: 'Sign in securely with a phone OTP.'
            },
            register: {
                title: 'Create your VakilNiti account',
                description: 'Set up your individual workspace.'
            },
            verify: {
                title: 'Verify your account',
                description: 'Confirm your phone number to continue registration.'
            }
        };

        function showAuth(mode) {
            authPanels.forEach(function (panel) {
                panel.hidden = panel.dataset.vnAuthPanel !== mode;
            });
            authTitle.textContent = authModes[mode].title;
            authDescription.textContent = authModes[mode].description;

            if (!authDialog.open) {
                authDialog.showModal();
            }

            const firstField = document.querySelector(
                '[data-vn-auth-panel="' + mode + '"] input, [data-vn-auth-panel="' + mode + '"] select'
            );
            if (firstField) {
                firstField.focus();
            }
        }

        document.querySelectorAll('[data-vn-auth-open]').forEach(function (link) {
            link.addEventListener('click', function (event) {
                if (authDialog && typeof authDialog.showModal === 'function') {
                    event.preventDefault();
                    showAuth(link.dataset.vnAuthOpen);
                }
            });
        });

        authClose.addEventListener('click', function () {
            authDialog.close();
        });

        authDialog.addEventListener('click', function (event) {
            if (event.target === authDialog) {
                authDialog.close();
            }
        });

        @if(request()->routeIs('login') || session('show_login_modal') || $errors->login->any())
            showAuth('login');
        @elseif(request()->routeIs('register.verify') || session('show_verify_modal') || $errors->verification->any())
            showAuth('verify');
        @elseif(request()->routeIs('register') || session('show_register_modal') || $errors->registration->any())
            showAuth('register');
        @endif
    }());
</script>

<dialog id="vn-webview-dialog" class="h-[92vh] w-[94vw] max-w-[1500px] rounded-2xl border-0 p-0 shadow-2xl">
    <div class="flex h-full flex-col bg-slate-100">
        <header class="flex items-center justify-between gap-3 border-b border-slate-200 bg-white px-4 py-3">
            <div class="min-w-0">
                <p class="text-xs font-bold uppercase tracking-wider text-blue-700">External Website</p>
                <p id="vn-webview-host" class="truncate text-sm font-medium text-slate-700"></p>
            </div>
            <div class="flex shrink-0 gap-2">
                <a id="vn-webview-new-tab" href="#" target="_blank" rel="noopener noreferrer"
                   class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Open in browser
                </a>
                <button type="button" id="vn-webview-close"
                        class="rounded-lg bg-slate-800 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-700">
                    Close
                </button>
            </div>
        </header>
        <iframe id="vn-webview-frame" class="min-h-0 flex-1 bg-white" title="External website"
                referrerpolicy="strict-origin-when-cross-origin"
                sandbox="allow-same-origin allow-scripts allow-forms allow-popups allow-popups-to-escape-sandbox"></iframe>
    </div>
</dialog>

<script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
    (function () {
        const dialog = document.getElementById('vn-webview-dialog');
        const frame = document.getElementById('vn-webview-frame');
        const hostLabel = document.getElementById('vn-webview-host');
        const newTab = document.getElementById('vn-webview-new-tab');

        document.querySelectorAll('a[href]').forEach(function (link) {
            if (link.hasAttribute('data-direct-share')) return;

            const href = link.getAttribute('href');
            if (!/^https?:\/\//i.test(href)) return;

            link.addEventListener('click', function (event) {
                const url = new URL(href, window.location.origin);
                const currentHost = window.location.hostname.toLowerCase();
                const targetHost = url.hostname.toLowerCase();
                const localHosts = ['localhost', '127.0.0.1', '::1'];
                const sameSite = targetHost === currentHost ||
                    (localHosts.includes(targetHost) && localHosts.includes(currentHost));

                if (sameSite) {
                    if (url.origin !== window.location.origin || link.target.toLowerCase() === '_blank') {
                        event.preventDefault();
                        window.location.assign(url.toString());
                    }
                    return;
                }

                event.preventDefault();
                hostLabel.textContent = url.hostname;
                newTab.href = url.toString();
                frame.src = url.toString();
                if (typeof dialog.showModal === 'function') {
                    dialog.showModal();
                } else {
                    window.open(url.toString(), '_blank', 'noopener,noreferrer');
                }
            });
        });

        document.getElementById('vn-webview-close').addEventListener('click', function () {
            dialog.close();
            frame.src = 'about:blank';
        });

        dialog.addEventListener('click', function (event) {
            if (event.target === dialog) {
                dialog.close();
                frame.src = 'about:blank';
            }
        });
    }());
</script>

</body>
</html>
