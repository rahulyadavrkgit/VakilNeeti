<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'VakilNeeti') | VakilNeeti</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body { background: #f1f5f9; color: #1e293b; }

        .vn-sidebar { background: #0f172a; color: #f8fafc; }

        .vn-nav-heading {
            color: #94a3b8;
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
            color: #e2e8f0 !important;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
        }

        .vn-nav-link:hover {
            background: #334155;
            color: #fff !important;
        }

        .vn-nav-link.vn-active {
            background: #1d4ed8;
            color: #fff !important;
            font-weight: 700;
            box-shadow: inset 3px 0 0 #93c5fd;
        }

        .vn-nav-child {
            margin-left: 12px;
            padding-left: 10px;
            border-left: 1px solid #475569;
        }

        .vn-nav-child .vn-nav-link {
            font-size: 12px;
            color: #cbd5e1 !important;
        }

        .vn-nav-section {
            padding-bottom: 15px;
            margin-bottom: 15px;
            border-bottom: 1px solid #334155;
        }

        .vn-content .text-slate-500 { color: #475569 !important; }
        .vn-content .text-slate-600 { color: #334155 !important; }
        .vn-content .text-slate-400 { color: #475569 !important; }

        .vn-content h2,
        .vn-content h3 { color: #0f172a; }

        .vn-sidebar a:focus-visible,
        .vn-content a:focus-visible {
            outline: 3px solid #60a5fa;
            outline-offset: 2px;
        }

        @media (max-width: 767px) {
            .vn-sidebar { width: 230px; }
            .vn-main-content { padding: 14px; }
            .vn-main-header { padding-left: 14px; padding-right: 14px; }
        }
    </style>
</head>

@php
    $activeFirm = \Illuminate\Support\Facades\DB::table('firm_profiles')
        ->where('is_active', true)
        ->first();

    $practiceStates = [];
$districtPracticeStates = [];

if ($activeFirm) {
    $practiceRows = \Illuminate\Support\Facades\DB::table('firm_practice_states')
        ->where('firm_profile_id', $activeFirm->id)
        ->where('is_active', true)
        ->orderBy('state_name')
        ->get();

    $practiceStates = $practiceRows->pluck('state_name')->all();

    foreach ($practiceRows as $practiceRow) {
        $state = \Illuminate\Support\Facades\DB::table('states')
            ->where('name', $practiceRow->state_name)
            ->first();

        if (!$state) {
            continue;
        }

        $mode = $practiceRow->district_selection_mode ?: 'all';

        // High Court-only states do not appear in District Courts.
        if ($mode === 'none') {
            continue;
        }

        $districtQuery = \Illuminate\Support\Facades\DB::table('judicial_districts')
            ->where('state_id', $state->id)
            ->where('is_active', true)
            ->whereNull('deleted_at');

        if ($mode === 'selected') {
            $selectedIds = \Illuminate\Support\Facades\DB::table('firm_practice_districts')
                ->where('firm_practice_state_id', $practiceRow->id)
                ->where('is_active', true)
                ->pluck('judicial_district_id')
                ->all();

            if (empty($selectedIds)) {
                continue;
            }

            $districtQuery->whereIn('id', $selectedIds);
        }

        $districts = $districtQuery
            ->orderBy('district_name')
            ->get(['id', 'district_name', 'district_code']);

        if ($districts->isNotEmpty()) {
            $districtPracticeStates[] = [
                'state_name' => $practiceRow->state_name,
                'districts' => $districts,
            ];
        }
    }
}@endphp

<body class="min-h-screen">

<div class="min-h-screen flex">

    <aside class="vn-sidebar w-72 shrink-0 flex flex-col">

        <div class="px-6 py-6 border-b border-slate-700">
            <h1 class="text-2xl font-bold tracking-wide text-white">VakilNeeti</h1>
            <p class="text-xs text-slate-300 mt-1">Legal Practice Management</p>
        </div>

        @php
    $vnActiveSection = null;

            if (
                request()->routeIs('dashboard') ||
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

    {{-- DASHBOARDS --}}
    <section class="vn-nav-section">
        <button type="button"
            data-vn-toggle="dashboards"
            aria-expanded="{{ $vnActiveSection === 'dashboards' ? 'true' : 'false' }}"
            onclick="vnOpenSection('dashboards', '/')"
            class="vn-nav-heading w-full cursor-pointer flex items-center justify-between text-left">
            <span>Dashboards</span>
            <span class="text-xs">&#9662;</span>
        </button>

        <div id="vn-submenu-dashboards" data-vn-submenu
            class="mt-2 space-y-1"
            @if($vnActiveSection !== 'dashboards') hidden @endif>
            <a href="{{ route('dashboard') }}"
               class="vn-nav-link {{ request()->routeIs('dashboard') ? 'vn-active' : '' }}">
                Practice Dashboard
            </a>
            <a href="{{ route('dashboard-studio.index') }}"
               class="vn-nav-link {{ request()->is('dashboard-studio*') ? 'vn-active' : '' }}">
                Dashboard Studio
            </a>
        </div>
    </section>

    {{-- COURTS --}}
    <section class="vn-nav-section">
        <button type="button"
            data-vn-toggle="courts"
            aria-expanded="{{ $vnActiveSection === 'courts' ? 'true' : 'false' }}"
            onclick="vnOpenSection('courts', '/court-management')"
            class="vn-nav-heading w-full cursor-pointer flex items-center justify-between text-left">
            <span>Courts</span>
            <span class="text-xs">&#9662;</span>
        </button>

        <div id="vn-submenu-courts" data-vn-submenu
            class="mt-2 space-y-1"
            @if($vnActiveSection !== 'courts') hidden @endif>

            <a href="{{ url('/court-directory') }}?level=supreme"
               class="vn-nav-link {{ request('level') === 'supreme' ? 'vn-active' : '' }}">
                Supreme Court of India
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
            onclick="vnOpenSection('cases', '/case-dashboard')"
            class="vn-nav-heading w-full cursor-pointer flex items-center justify-between text-left">
            <span>Case Management System</span>
            <span class="text-xs">&#9662;</span>
        </button>

        <div id="vn-submenu-cases" data-vn-submenu
            class="mt-2 space-y-1"
            @if($vnActiveSection !== 'cases') hidden @endif>

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
            onclick="vnOpenSection('clients', '/client-management')"
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
        onclick="vnOpenSection('police', '/police-administration')"
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
        onclick="vnOpenSection('bars', '/bar-associations')"
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
        onclick="vnOpenSection('court-fee', '/court-fee')"
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
            onclick="vnOpenSection('bare-acts', '/bare-acts')"
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
        onclick="vnOpenSection('forms', '/miscellaneous-forms')"
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
    {{-- PROFILE SETTINGS --}}
    <section class="vn-nav-section">
        <button type="button"
            data-vn-toggle="profile"
            aria-expanded="{{ $vnActiveSection === 'profile' ? 'true' : 'false' }}"
            onclick="vnOpenSection('profile', '/profile')"
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
                    request()->is('profile/personal*') ||
                    request()->is('profile/professional*') ||
                    request()->is('firm-profile*') ||
                    request()->is('profile/contacts*')
                ) open @endif>

                <summary class="vn-nav-link cursor-pointer list-none flex items-center justify-between">
                    <span>Profile Information</span>
                    <span class="text-xs">&#9662;</span>
                </summary>

                <div class="vn-nav-child mt-1">
                    <a href="{{ route('profile.personal.index') }}"
                       class="vn-nav-link {{ request()->is('profile/personal*') ? 'vn-active' : '' }}">
                        Personal Profile
                    </a>

                    <a href="{{ route('profile.professional.index') }}"
                       class="vn-nav-link {{ request()->is('profile/professional*') ? 'vn-active' : '' }}">
                        Professional Profile
                    </a>

                    <a href="{{ route('firm-profile.index') }}"
                       class="vn-nav-link {{ request()->is('firm-profile*') ? 'vn-active' : '' }}">
                        Firm / Law Office / Company Profile
                    </a>

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

<script>
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
</script>

        <div class="border-t border-slate-700 px-6 py-4">
            <p class="text-xs font-semibold text-slate-200">VakilNeeti</p>
            <p class="text-xs text-slate-400 mt-1">Legal Practice Workspace</p>
        </div>

    </aside>

    <div class="flex-1 min-w-0 flex flex-col">

        <header class="vn-main-header min-h-16 bg-white border-b border-slate-200 flex items-center justify-between px-6 py-3">
            <h2 class="text-lg font-bold text-slate-900">
                @yield('page-heading', 'Dashboard')
            </h2>

            <div class="flex items-center gap-4">
                <div class="text-sm font-medium text-slate-600">
                    {{ now()->format('d M Y') }}
                </div>

                @guest
                    <a href="{{ route('admin.login') }}"
                       class="rounded-lg border border-blue-200 bg-white px-4 py-2 text-sm font-semibold text-blue-700 hover:bg-blue-50">
                        Administrator sign in
                    </a>
                @endguest

                @if(auth()->check() && auth()->user()->is_admin)
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit"
                            class="rounded-lg border border-red-200 bg-white px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">
                            Logout
                        </button>
                    </form>
                @endif
            </div>
        </header>

        <main class="vn-content vn-main-content flex-1 p-6">
            @if(session('auth_login_success'))
                <div role="status" class="mb-5 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">
                    {{ session('auth_login_success') }}
                </div>
            @endif
            @yield('content')
        </main>

        <footer class="border-t border-slate-200 bg-white px-6 py-4 text-xs text-slate-600">
            &copy; {{ date('Y') }} VakilNeeti. All rights reserved.
        </footer>

    </div>
</div>

@stack('scripts')

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

<script>
    (function () {
        const dialog = document.getElementById('vn-webview-dialog');
        const frame = document.getElementById('vn-webview-frame');
        const hostLabel = document.getElementById('vn-webview-host');
        const newTab = document.getElementById('vn-webview-new-tab');

        document.querySelectorAll('a[href]').forEach(function (link) {
            const href = link.getAttribute('href');
            if (!/^https?:\/\//i.test(href)) return;

            link.addEventListener('click', function (event) {
                event.preventDefault();
                const url = new URL(href, window.location.origin);
                const allowedHost = url.hostname && !['localhost', '127.0.0.1', '::1'].includes(url.hostname);
                if (!allowedHost) {
                    window.open(url.toString(), '_blank', 'noopener,noreferrer');
                    return;
                }

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