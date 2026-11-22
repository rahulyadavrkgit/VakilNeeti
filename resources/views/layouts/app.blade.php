<!DOCTYPE html>
<html lang="en-IN">
<head>
    @php
        // Search engines: public pages are indexed with a clean canonical address; private pages are not.
        $seoIsHome = request()->routeIs('dashboard');
        $seoPageTitle = trim($__env->yieldContent('title'));
        $seoTitle = $seoIsHome || $seoPageTitle === ''
            ? 'VakilNiti: legal practice workspace for Indian advocates'
            : $seoPageTitle.' | VakilNiti';
        $seoDescription = \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/', ' ', $__env->yieldContent('meta_description')) ?: 'Case diary with hearings and reminders, clients and GST invoices, plus Supreme Court, High Court and District Court services, display boards, bare acts and court fee tools for Indian advocates.'), 160, '…');
        $seoIndexable = \App\Http\Middleware\EnsurePrivatePracticeAccess::isPublic(request())
            && ! request()->is('login', 'register*', 'admin*', 'webview*', 'api/*')
            && trim($__env->yieldContent('robots')) === '';
        $seoQuery = array_filter(request()->only(['level', 'state', 'page']), fn ($value) => is_string($value) && $value !== '' && $value !== '1');
        $seoCanonical = url()->current().($seoQuery ? '?'.http_build_query($seoQuery) : '');
        $seoImage = asset('images/VakilNiti-logo.png');
    @endphp
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $seoTitle }}</title>
    <meta name="description" content="{{ $seoDescription }}">
    <meta name="robots" content="{{ $seoIndexable ? 'index, follow, max-image-preview:large' : 'noindex, nofollow' }}">
    @if($seoIndexable)
        <link rel="canonical" href="{{ $seoCanonical }}">
    @endif
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="VakilNiti">
    <meta property="og:locale" content="en_IN">
    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:description" content="{{ $seoDescription }}">
    <meta property="og:url" content="{{ $seoCanonical }}">
    <meta property="og:image" content="{{ $seoImage }}">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="{{ $seoTitle }}">
    <meta name="twitter:description" content="{{ $seoDescription }}">
    <meta name="theme-color" content="#142235">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="VakilNiti">
    @if($seoIsHome)
        <script type="application/ld+json" nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
            {!! json_encode([
                '@context' => 'https://schema.org',
                '@graph' => [
                    ['@type' => 'WebSite', '@id' => url('/').'#website', 'name' => 'VakilNiti', 'url' => url('/'), 'inLanguage' => 'en-IN', 'description' => $seoDescription],
                    ['@type' => 'Organization', '@id' => url('/').'#organization', 'name' => 'VakilNiti', 'url' => url('/'), 'logo' => $seoImage],
                ],
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}
        </script>
    @endif
    @stack('structured-data')
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
        .vn-main-content { width: 100%; max-width: 1800px; margin-inline: auto; overflow-x: clip; overflow-wrap: break-word; }
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
            /* 16px fields stop iPhones zooming in when a field is tapped. */
            .vn-main-content input:not([type=checkbox]):not([type=radio]):not([type=file]), .vn-main-content select, .vn-main-content textarea { font-size: 16px; }
            .vn-main-header { gap: 6px; }
            .vn-main-header .vn-profile-menu > summary { width: 38px; height: 38px; }
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
        /* Sidebar menu: groups with icons (layouts/partials/sidebar-nav). */
        @media (min-width: 768px) { .vn-sidebar { position: sticky; top: 0; height: 100vh; } }
        .vn-nav-group { border-radius: 12px; }
        .vn-nav-group + .vn-nav-group { margin-top: 6px; }
        .vn-nav-group-title { display: flex; align-items: center; justify-content: space-between; min-height: 36px; padding: 6px 12px; border-radius: 9px; color: #91c9c1; font-size: 11px; font-weight: 700; letter-spacing: .09em; text-transform: uppercase; cursor: pointer; list-style: none; }
        .vn-nav-group-title::-webkit-details-marker, .vn-nav-sub > summary::-webkit-details-marker { display: none; }
        .vn-nav-group-title:hover { background: rgba(255,255,255,.06); }
        .vn-nav-chevron { transition: transform .18s ease; }
        details[open] > summary .vn-nav-chevron { transform: rotate(180deg); }
        .vn-nav-items { padding: 2px 0 8px; }
        .vn-sidebar .vn-nav-link { gap: 10px; min-height: 40px; margin: 1px 0; padding: 8px 12px; font-size: 13.5px; font-weight: 550; color: #dbe5ef !important; list-style: none; cursor: pointer; }
        .vn-sidebar .vn-nav-link svg { color: #8ea4ba; }
        .vn-sidebar .vn-nav-link:hover svg, .vn-sidebar .vn-nav-link.vn-active svg { color: #ffffff; }
        .vn-sidebar .vn-nav-child { margin: 2px 0 4px 21px; padding-left: 10px; border-left: 1px solid #34485f; }
        .vn-sidebar .vn-nav-child .vn-nav-link { min-height: 34px; font-size: 12.5px; }
        .vn-sidebar [hidden] { display: none !important; }
        /* Collections switched to list view with the grid/list toggle (components/collection-toolbar). */
        .vn-list-view { grid-template-columns: repeat(1, minmax(0, 1fr)) !important; gap: 8px !important; }
        @media (min-width: 640px) { .vn-list-view { grid-template-columns: repeat(2, minmax(0, 1fr)) !important; } }
        @media (min-width: 1280px) { .vn-list-view { grid-template-columns: repeat(3, minmax(0, 1fr)) !important; } }
        .vn-list-view > a { flex-direction: row !important; justify-content: flex-start !important; gap: 12px !important; padding: 10px 14px !important; text-align: left !important; }
        .vn-list-view [data-tile-icon] { width: 40px; height: 40px; border-radius: 12px; }
        .vn-list-view [data-tile-text] { flex: 1; gap: 2px; }
        .vn-list-view [data-tile-description] { display: block !important; }
        .vn-list-view [data-tile-badge] { position: static !important; order: 9; }
        [data-filter-item][hidden] { display: none !important; }
        /* Embedded display boards: drag the bottom edge to resize; full screen fallback for phones. */
        .vn-board-box { resize: vertical; min-height: 240px; }
        .vn-board-fullscreen { position: fixed !important; inset: 0; z-index: 80; height: 100dvh !important; border-radius: 0 !important; resize: none; }
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

        <div class="border-b border-white/10 px-5 py-4">
            <a href="{{ route('dashboard') }}" class="inline-flex rounded-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-amber-300">
                <span class="inline-flex items-center rounded-lg bg-white px-3 py-2">
                    <img src="{{ asset('images/VakilNiti-logo.png') }}"
                         alt="VakilNiti"
                         class="h-10 w-auto max-w-[190px] object-contain">
                </span>
            </a>
        </div>

        @include('layouts.partials.sidebar-nav')

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
                    <form method="GET" action="{{ route('search') }}" role="search" class="hidden lg:block">
                        <label class="flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 focus-within:border-[#16776e] focus-within:bg-white">
                            <x-icon name="search" :size="16" class="text-slate-400" />
                            <span class="sr-only">Search cases, clients and invoices</span>
                            <input type="search" name="q" value="{{ request()->routeIs('search') ? request('q') : '' }}" placeholder="Search cases, clients, CNR…"
                                   class="h-10 w-56 border-0 bg-transparent px-0 text-sm focus:ring-0 xl:w-72">
                        </label>
                    </form>
                    <a href="{{ route('search') }}" aria-label="Search" class="hidden h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-slate-50 text-slate-700 hover:bg-slate-100 sm:flex lg:hidden">
                        <x-icon name="search" :size="19" />
                    </a>

                    <details class="vn-profile-menu relative">
                        <summary aria-label="Add new" class="flex h-10 w-10 cursor-pointer list-none items-center justify-center rounded-full bg-[#16776e] text-2xl font-light leading-none text-white hover:bg-[#125f58]">+</summary>
                        <div class="absolute right-0 z-40 mt-2 w-56 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 text-sm shadow-lg">
                            <p class="px-4 py-2 text-xs font-bold uppercase tracking-wide text-slate-400">Add new</p>
                            <a href="{{ route('hearings.create') }}" class="flex items-center gap-3 px-4 py-2.5 text-slate-700 hover:bg-slate-50"><x-icon name="calendar" :size="18" class="text-[#16776e]" />Hearing</a>
                            <a href="{{ route('tasks.create') }}" class="flex items-center gap-3 px-4 py-2.5 text-slate-700 hover:bg-slate-50"><x-icon name="check" :size="18" class="text-[#16776e]" />Task</a>
                            <a href="{{ route('cases.manual') }}" class="flex items-center gap-3 px-4 py-2.5 text-slate-700 hover:bg-slate-50"><x-icon name="briefcase" :size="18" class="text-[#16776e]" />Case</a>
                            <a href="{{ route('cases.find') }}" class="flex items-center gap-3 px-4 py-2.5 text-slate-700 hover:bg-slate-50"><x-icon name="download" :size="18" class="text-[#16776e]" />Import cases</a>
                            <a href="{{ route('clients.create') }}" class="flex items-center gap-3 px-4 py-2.5 text-slate-700 hover:bg-slate-50"><x-icon name="people" :size="18" class="text-[#16776e]" />Client</a>
                            <a href="{{ route('invoices.create') }}" class="flex items-center gap-3 px-4 py-2.5 text-slate-700 hover:bg-slate-50"><x-icon name="receipt" :size="18" class="text-[#16776e]" />Invoice</a>
                        </div>
                    </details>

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

        <footer class="border-t border-slate-200 bg-white px-6 py-5 text-xs leading-relaxed text-slate-600">
            <div class="mx-auto max-w-5xl space-y-2">
                <p>
                    <strong class="font-semibold text-slate-700">Disclaimer:</strong>
                    Information on VakilNiti is for general information and educational purposes only and is not legal advice.
                    We keep it accurate and up to date to the best of our knowledge, but laws, court lists and links change often,
                    so please check the official court or government source before relying on it. Court and government websites
                    linked here belong to their respective owners; VakilNiti is not affiliated with or endorsed by them.
                </p>
                <p class="flex flex-wrap gap-x-4 gap-y-1">
                    <span>&copy; {{ date('Y') }} VakilNiti. All rights reserved.</span>
                    <a href="{{ route('courts.index') }}" class="hover:text-[#16776e] hover:underline">Courts</a>
                    <a href="{{ route('courts.display-boards') }}" class="hover:text-[#16776e] hover:underline">Display boards</a>
                    <a href="{{ route('bare-acts.index') }}" class="hover:text-[#16776e] hover:underline">Bare Acts</a>
                </p>
            </div>
        </footer>

    </div>
</div>

<nav class="vn-mobile-nav" aria-label="Quick navigation">
    <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'is-current' : '' }}"><x-icon name="home" :size="21" class="vn-mobile-icon" /><span>Home</span></a>
    <a href="{{ route('cases.index') }}" class="{{ request()->is('cases*') || request()->is('case-dashboard*') ? 'is-current' : '' }}"><x-icon name="briefcase" :size="21" class="vn-mobile-icon" /><span>Cases</span></a>
    <a href="{{ route('cases.calendar') }}" class="{{ request()->is('case-calendar*') ? 'is-current' : '' }}"><x-icon name="calendar" :size="21" class="vn-mobile-icon" /><span>Calendar</span></a>
    <a href="{{ route('courts.index') }}" class="{{ request()->is('courts*', 'court-directory*') ? 'is-current' : '' }}"><x-icon name="pillars" :size="21" class="vn-mobile-icon" /><span>Courts</span></a>
    <button type="button" id="vn-mobile-more" aria-label="Open all modules"><x-icon name="list" :size="21" class="vn-mobile-icon" /><span>Menu</span></button>
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
