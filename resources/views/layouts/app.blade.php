<!DOCTYPE html>
@php
    // Appearance: the signed-in person's choice from Settings; guests choose on this device.
    $themeChoice = auth()->user()?->preference('theme') ?? 'system';
@endphp
<html lang="en-IN" data-theme="{{ $themeChoice }}" @auth data-theme-saved @endauth>
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
        $seoQuery = array_filter(request()->only(['level', 'state', 'court', 'page']), fn ($value) => is_string($value) && $value !== '' && $value !== '1');
        $seoCanonical = url()->current().($seoQuery ? '?'.http_build_query($seoQuery) : '');
        $seoImage = asset('images/og-image.png');
    @endphp
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
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
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="VakilNiti: legal practice workspace for Indian advocates">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:image" content="{{ $seoImage }}">
    <meta name="twitter:title" content="{{ $seoTitle }}">
    <meta name="twitter:description" content="{{ $seoDescription }}">
    <meta name="theme-color" content="#142235">
    @auth
        <meta name="vn-search-sheet" content="{{ route('sheets.search') }}">
        <meta name="vn-search-page" content="{{ route('search') }}">
    @endauth
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo-mark.svg') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}" sizes="32x32 48x48">
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
                    ['@type' => 'Organization', '@id' => url('/').'#organization', 'name' => 'VakilNiti', 'url' => url('/'), 'logo' => asset('images/app-icon-512.png')],
                ],
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}
        </script>
    @endif
    @stack('structured-data')
    <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">

// Keep the browser's "install app" offer for our own Install buttons (resources/js/ui-actions.js).
        window.addEventListener('beforeinstallprompt', function (event) {
            event.preventDefault();
            window.vnInstallPrompt = event;
            window.dispatchEvent(new Event('vn-installable'));
        });
    </script>
    @include('layouts.partials.theme-script')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')

    <style>
        /* VakilNiti theme: navy ink, teal for actions, gold accents from the logo. */
        :root {
            --vn-ink: #142235;
            --vn-ink-soft: #20334a;
            --vn-accent: #16776e;
            --vn-accent-dark: #125f58;
            --vn-gold: #e6b84a;
            /* Warm paper rather than cold grey: easier on the eyes over a long working day. */
            --vn-canvas: #f6f5f1;
            --vn-line: #e2e8ef;
            --vn-text: #172536;
        }
        html.dark {
            --vn-canvas: #0d1524;
            --vn-line: #24344d;
            --vn-text: #e3e9f1;
        }
        /* Figures line up in columns: dates, item numbers, rooms, amounts. */
        body { background: var(--vn-canvas); color: var(--vn-text); font-family: var(--font-sans), sans-serif; font-variant-numeric: tabular-nums; -webkit-font-smoothing: antialiased; }
        /* Serif only for page titles; everything else in the device's own clean sans-serif. */
        .vn-content h1, #vn-auth-dialog h2 { font-family: var(--font-serif), serif; letter-spacing: -0.01em; }
        .vn-content h2, .vn-content h3 { color: var(--vn-ink); letter-spacing: -0.005em; }
        html.dark .vn-content h2, html.dark .vn-content h3 { color: #eef2f7; }
        html.dark .vn-sidebar { background: #0a111d; border-right-color: #1c2a40; }
        html.dark .vn-main-header { background: rgba(13, 21, 36, .92); }
        html.dark .vn-mobile-nav { background: rgba(13, 21, 36, .97); border-top-color: #1f2d44; box-shadow: 0 -8px 28px rgba(0,0,0,.35); }
        html.dark .vn-mobile-nav a, html.dark .vn-mobile-nav button { color: #93a1b4; }
        html.dark .vn-mobile-nav .is-current { color: #6fd0c3; background: #15302f; }
        html.dark .vn-footer { background-color: #0a111d; }
        html.dark .vn-event-peek { background: #141f31; border-color: #2a3b56; }
        html.dark img[data-invert-dark] { filter: invert(1) hue-rotate(180deg); }
        .vn-content input:focus, .vn-content select:focus, .vn-content textarea:focus, #vn-auth-dialog input:focus, #vn-auth-dialog select:focus {
            border-color: var(--vn-accent);
            box-shadow: 0 0 0 3px rgba(22, 119, 110, .18);
            outline: none;
        }
        .vn-centered-dialog { position: fixed; top: 50%; left: 50%; margin: 0; transform: translate(-50%, -50%); max-height: 90vh; }

        /* Side menu. */
        .vn-sidebar { background: var(--vn-ink); color: #f3f6fa; border-right: 1px solid #2b3b50; }
        .vn-nav-heading { color: #91c9c1; font-size: 12px; font-weight: 700; letter-spacing: .09em; text-transform: uppercase; padding: 0 12px; margin-bottom: 9px; }
        .vn-nav-link { display: flex; align-items: center; min-height: 42px; padding: 9px 12px; margin: 3px 0; border-radius: 9px; color: #e4ebf3 !important; font-size: 13px; font-weight: 500; text-decoration: none; }
        .vn-nav-link:hover { background: #263950; color: #fff !important; }
        .vn-nav-link.vn-active { background: #176f68; color: #fff !important; font-weight: 700; box-shadow: inset 3px 0 0 var(--vn-gold); }
        .vn-nav-child { margin-left: 12px; padding-left: 10px; border-left: 1px solid #43556a; }
        .vn-nav-section { padding-bottom: 15px; margin-bottom: 15px; border-bottom: 1px solid #2a3b50; }

        /* Page shell. */
        .vn-main-header { position: sticky; top: 0; z-index: 25; background: rgba(255,255,255,.94); backdrop-filter: blur(14px); }
        .vn-content { min-width: 0; }
        .vn-main-content { width: 100%; max-width: 1800px; margin-inline: auto; overflow-x: clip; overflow-wrap: break-word; }
        .vn-mobile-nav { display: none; }
        .vn-sidebar-backdrop { display: none; }
        /* Header: logo on phones, page title on desktop. */
        .vn-main-header { border-bottom: 1px solid var(--vn-line); box-shadow: 0 1px 2px rgba(20,34,53,.04); }
        .vn-header-brand, .vn-header-spacer { display: none; }
        .vn-header-title { display: flex; }
        .vn-profile-menu summary::-webkit-details-marker { display: none; }
        .vn-nav-link, .vn-main-header button, .vn-main-header a { -webkit-tap-highlight-color: transparent; }
        .vn-sidebar a:focus-visible, .vn-sidebar summary:focus-visible, .vn-content a:focus-visible, button:focus-visible { outline: 3px solid #55b9ad; outline-offset: 2px; }
        @media (max-width: 767px) {
            body { padding-bottom: calc(72px + env(safe-area-inset-bottom)); }
            .vn-sidebar { position: fixed; z-index: 60; inset: auto 0 0; width: 100%; max-height: min(80dvh, 42rem); padding-bottom: env(safe-area-inset-bottom); border-radius: 18px 18px 0 0; visibility: hidden; transform: translateY(105%); transition: transform .24s ease; box-shadow: 20px 0 50px rgba(10,25,40,.22); }
            body.vn-menu-open { overflow: hidden; }
            body.vn-menu-open .vn-sidebar { visibility: visible; transform: translateY(0); }
            .vn-sidebar-backdrop { display: block; visibility: hidden; position: fixed; inset: 0; z-index: 55; padding: 0; border: 0; background: rgba(8,20,34,.52); opacity: 0; pointer-events: none; transition: opacity .2s ease; }
            body.vn-menu-open .vn-sidebar-backdrop { visibility: visible; opacity: 1; pointer-events: auto; }
            /* Phones: Home shows the logo; other pages show Back and the page name, like a phone app. */
            .vn-header-title { gap: 8px; }
            .vn-main-header.is-home .vn-header-title { display: none; }
            .vn-main-header.is-home .vn-header-brand { display: inline-flex; }
            .vn-main-header.is-home .vn-header-spacer { display: block; }
            .vn-main-content { padding: 16px 14px 22px; }
            /* 16px fields stop iPhones zooming in when a field is tapped. */
            .vn-main-content input:not([type=checkbox]):not([type=radio]):not([type=file]), .vn-main-content select, .vn-main-content textarea { font-size: 16px; }
            .vn-mobile-nav { position: fixed; z-index: 45; display: grid; grid-template-columns: repeat(5, minmax(0,1fr)); inset: auto 0 0; padding: 8px 8px calc(8px + env(safe-area-inset-bottom)); border-top: 1px solid #dfe6ed; background: rgba(255,255,255,.97); backdrop-filter: blur(16px); box-shadow: 0 -8px 28px rgba(20,34,53,.08); }
            .vn-mobile-nav a, .vn-mobile-nav button { display: flex; min-width: 0; min-height: 48px; flex-direction: column; align-items: center; justify-content: center; gap: 3px; border: 0; border-radius: 10px; background: transparent; color: #657386; font-size: 10px; font-weight: 650; text-decoration: none; }
            .vn-mobile-nav .vn-mobile-icon { font-size: 18px; line-height: 1; }
            .vn-mobile-nav a, .vn-mobile-nav button { font-size: 11.5px; }
            /* Notch side insets (landscape iPhones) now that the page uses viewport-fit=cover. */
            body { padding-left: env(safe-area-inset-left); padding-right: env(safe-area-inset-right); }
            /* Header menus open as a full-width panel under the header, so none runs off the screen. */
            .vn-header-dropdown { position: fixed !important; left: max(.75rem, env(safe-area-inset-left)) !important; right: max(.75rem, env(safe-area-inset-right)) !important; top: 4.25rem; width: auto !important; margin-top: 0 !important; max-height: calc(100dvh - 4.25rem - 88px - env(safe-area-inset-bottom)); overflow-y: auto; }
            /* The page name is already in the header: hide repeats (breadcrumbs, a matching first heading). */
            .vn-main-content nav[aria-label="Breadcrumb"], .vn-dup-title { display: none !important; }
            .vn-mobile-nav .is-current { color: #126c64; background: #e8f4f2; }
        }
        @media (max-width: 359px) { .vn-header-brand [data-brand-word] { display: none; } }
        @media (min-width: 768px) and (max-width: 1100px) {
            .vn-main-content { padding: 18px; }
        }
        @media print {
            [data-tab-panel][hidden] { display: block !important; }
            .vn-sidebar, .vn-main-header, .vn-mobile-nav, .vn-sidebar-backdrop, .vn-footer { display: none !important; }
            body { padding-bottom: 0 !important; background: #fff !important; }
            .vn-main-content { padding: 0 !important; }
        }
        /*
            Computers: one navy header that stays at the top while the page scrolls: logo, menu, search and actions.
            The page name and Back sit at the top of the page itself (.vn-page-trail). Phones keep the white app bar,
            the bottom bar and the Menu sheet (the side menu, which computers do not need).
        */
        @media (min-width: 768px) {
            .vn-sidebar { display: none !important; }
            .vn-main-header { z-index: 42; background: var(--vn-ink); border-bottom-color: #22344a; backdrop-filter: none; box-shadow: 0 2px 10px rgba(10, 20, 34, .18); }
            html.dark .vn-main-header { background: #0a111d; border-bottom-color: #1c2a40; }
            .vn-header-title, .vn-header-spacer, .vn-main-header .vn-header-brand { display: none !important; }
        }
        @media (min-width: 768px) and (max-width: 1023px) {
            .vn-main-header [data-brand-word] { display: none; }
        }
        .vn-main-content:has(h1) .vn-trail-title { display: none; }
        .vn-main-content:has(h1) .vn-page-trail:not(:has([data-vn-back])) { display: none !important; }
        /* Under a section bar, its highlighted tab already names the page. */
        .vn-section-nav ~ .vn-main-content .vn-trail-title { display: none; }
        .vn-section-nav ~ .vn-main-content .vn-page-trail:not(:has([data-vn-back])) { display: none !important; }
        .vn-nav-group { border-radius: 12px; }
        @media (max-width: 767px) { .vn-nav-group + .vn-nav-group { margin-top: 6px; } }
        .vn-nav-group-title { display: flex; align-items: center; justify-content: space-between; min-height: 36px; padding: 6px 12px; border-radius: 9px; color: #91c9c1; font-size: 12px; font-weight: 700; letter-spacing: .09em; text-transform: uppercase; cursor: pointer; list-style: none; }
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
        /* Tab panels (components/tabs) open below the sticky header and tab bar. */
        [data-tab-panel] { scroll-margin-top: 8.5rem; }
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


@endphp

<body class="min-h-screen {{ request()->boolean('embed') ? 'vn-embed' : '' }}" data-signed-in="{{ auth()->check() ? 1 : 0 }}">

<button type="button" class="vn-sidebar-backdrop" id="vn-sidebar-backdrop" aria-label="{{ __('Close navigation') }}"></button>
<div class="min-h-screen flex flex-col">

    <aside id="vn-sidebar" class="vn-sidebar w-72 shrink-0 flex flex-col" aria-label="{{ __('Main navigation') }}">

        <div class="flex h-16 shrink-0 items-center border-b border-white/10 px-5">
            <a href="{{ route('dashboard') }}" aria-label="VakilNiti home" class="inline-flex rounded-lg">
                <x-brand tone="dark" :size="38" />
            </a>
        </div>

        {{-- Phones only: the grouped menu in the Menu sheet. Computers have the menu in the header. --}}
        <div class="flex min-h-0 flex-1 flex-col md:hidden">
            @include('layouts.partials.sidebar-nav')
        </div>

    </aside>

    <div class="flex-1 min-w-0 flex flex-col">

        @php
            $headerUserName = auth()->user()?->name ?? '';
            $headerInitial = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($headerUserName, 0, 1));
            // Header buttons: grey on the white phone bar, white on the navy computer header.
            $headerIconLook = 'h-11 w-11 md:h-10 md:w-10 shrink-0 cursor-pointer list-none items-center justify-center rounded-xl text-slate-600 transition hover:bg-slate-100 hover:text-slate-900 md:text-white/80 md:hover:bg-white/10 md:hover:text-white';
            $headerIcon = 'flex '.$headerIconLook;
            $calendarActive = request()->is('case-calendar*', 'hearings*');
            // Back only where it helps: one record (a case, a client, a board), a form, or a page that names its parent.
            // Section pages reached from the menu (Cases, Billing, Courts…) need none.
            $currentRoute = request()->route();
            $needsBack = ! request()->routeIs('dashboard') && (
                $__env->hasSection('back-url')
                || ($currentRoute && $currentRoute->parameters() !== [])
                || \Illuminate\Support\Str::endsWith((string) $currentRoute?->getName(), ['.show', '.create', '.edit', '.manual'])
            );
            $headerMenu = 'vn-header-dropdown absolute right-0 z-40 mt-2 overflow-hidden rounded-2xl border border-slate-200 bg-white text-sm shadow-xl shadow-slate-900/10';
            $headerMenuItem = 'flex items-center gap-3 px-4 py-2.5 text-slate-700 hover:bg-slate-50';
        @endphp
        <header class="vn-main-header {{ request()->routeIs('dashboard') ? 'is-home' : '' }}">
            <div class="flex h-16 items-center gap-1.5 px-3 sm:gap-2 sm:px-5 lg:px-6">
                {{-- Computers: the logo always leads the header. Phones: it shows on the home screen only. --}}
                <a href="{{ route('dashboard') }}" class="mr-2 hidden shrink-0 rounded-lg md:inline-flex lg:mr-3" aria-label="VakilNiti home">
                    <x-brand tone="dark" :size="34" />
                </a>
                <a href="{{ route('dashboard') }}" class="vn-header-brand shrink-0 rounded-lg" aria-label="VakilNiti home">
                    <x-brand :size="32" />
                </a>
                @include('layouts.partials.top-nav')

                <div class="vn-header-title min-w-0 flex-1 items-center gap-3">
                    @if($needsBack)
                        <x-back-link class="shrink-0" :fallback="trim($__env->yieldContent('back-url')) ?: route('dashboard')" />
                    @endif
                    <h2 class="min-w-0 truncate text-base font-bold text-slate-900">@yield('page-heading', 'VakilNiti')</h2>
                </div>
                <div class="vn-header-spacer flex-1"></div>

                @auth
                    <form method="GET" action="{{ route('search') }}" role="search" class="mr-1 hidden xl:block">
                        <label class="flex items-center gap-2 rounded-xl border border-white/15 bg-white/10 px-3 transition focus-within:border-white/40 focus-within:bg-white/15">
                            <x-icon name="search" :size="17" class="text-white/60" />
                            <span class="sr-only">{{ __('Search cases, clients and invoices') }}</span>
                            <input type="search" name="q" value="{{ request()->routeIs('search') ? request('q') : '' }}" placeholder="{{ __('Search…  (Ctrl K)') }}"
                                   class="h-10 w-44 border-0 bg-transparent px-0 text-sm text-white placeholder:text-white/55 focus:ring-0 2xl:w-64">
                        </label>
                    </form>
                    <a href="{{ route('search') }}" data-search-open aria-label="{{ __('Search') }}" title="{{ __('Search') }} (Ctrl K)" class="{{ $headerIcon }} xl:hidden {{ request()->routeIs('dashboard') ? '' : 'max-md:hidden' }}">
                        <x-icon name="search" :size="20" />
                    </a>

                    <details class="vn-profile-menu relative">
                        <summary aria-label="{{ __('Add new') }}" title="{{ __('Add new') }}" class="flex h-11 md:h-10 shrink-0 cursor-pointer list-none items-center justify-center gap-1.5 rounded-xl bg-[#16776e] px-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#125f58] sm:px-3.5 md:px-2.5 xl:px-3.5">
                            <x-icon name="plus" :size="20" /><span class="hidden sm:inline md:hidden xl:inline">{{ __('New') }}</span>
                        </summary>
                        <div class="{{ $headerMenu }} w-60 py-1.5">
                            <p class="px-4 pb-1 pt-2 text-xs font-bold uppercase tracking-wider text-slate-400">{{ __('Add new') }}</p>
                            <a href="{{ route('hearings.create') }}" data-sheet="{{ route('sheets.next-date') }}" class="{{ $headerMenuItem }}"><x-icon name="calendar" :size="18" class="text-[#16776e]" />{{ __('Hearing / next date') }}</a>
                            <a href="{{ route('cases.find') }}" data-sheet="{{ route('sheets.add-case') }}" class="{{ $headerMenuItem }}"><x-icon name="briefcase" :size="18" class="text-[#16776e]" />{{ __('Case') }}</a>
                            <a href="{{ route('tasks.create') }}" data-sheet="{{ route('sheets.task') }}" class="{{ $headerMenuItem }}"><x-icon name="check" :size="18" class="text-[#16776e]" />{{ __('Task') }}</a>
                            <a href="{{ route('clients.create') }}" data-sheet="{{ route('sheets.client') }}" class="{{ $headerMenuItem }}"><x-icon name="people" :size="18" class="text-[#16776e]" />{{ __('Client') }}</a>
                            <a href="{{ route('invoices.create') }}" class="{{ $headerMenuItem }}"><x-icon name="receipt" :size="18" class="text-[#16776e]" />{{ __('Invoice') }}</a>
                        </div>
                    </details>

                    {{-- The calendar one tap away. --}}
                    <a href="{{ route('cases.calendar') }}" aria-label="{{ __('Calendar') }}" title="{{ __('Calendar') }}" @if($calendarActive) aria-current="page" @endif
                       class="{{ $headerIcon }} {{ $calendarActive ? 'bg-teal-50 text-[#16776e] md:bg-white/12 md:text-white md:shadow-[inset_0_-2px_0_#e6b84a]' : '' }}">
                        <x-icon name="calendar" :size="20" />
                    </a>

                    {{-- Phones have Alerts in the bottom bar. --}}
                    <details class="vn-profile-menu relative max-md:hidden">
                        <summary aria-label="{{ __('Notifications') }}{{ ($bellUnreadCount ?? 0) ? ', '.$bellUnreadCount.' '.__('unread') : '' }}" title="{{ __('Notifications') }}" class="relative {{ $headerIcon }}">
                            <x-icon name="bell" :size="20" />
                            @if($bellUnreadCount ?? 0)
                                <span class="absolute right-0.5 top-0.5 flex h-[18px] min-w-[18px] items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-bold text-white ring-2 ring-white md:ring-[#142235]">{{ $bellUnreadCount > 9 ? '9+' : $bellUnreadCount }}</span>
                            @endif
                        </summary>
                        <div class="{{ $headerMenu }} w-[min(22rem,calc(100vw-1.5rem))]">
                            <div class="flex items-center justify-between gap-2 border-b border-slate-100 px-4 py-3">
                                <p class="font-semibold text-slate-900">{{ __('Notifications') }}</p>
                                @if($bellUnreadCount ?? 0)
                                    <form method="POST" action="{{ route('notifications.read-all') }}">
                                        @csrf
                                        <button type="submit" class="text-xs font-semibold text-[#16776e] hover:underline">{{ __('Mark all read') }}</button>
                                    </form>
                                @endif
                            </div>
                            <div class="max-h-96 overflow-y-auto">
                                @forelse($bellNotifications ?? [] as $notification)
                                    @include('notifications.partials.item', ['notification' => $notification])
                                @empty
                                    <p class="px-4 py-6 text-center text-slate-500">{{ __("You're all caught up.") }}</p>
                                @endforelse
                            </div>
                            <a href="{{ route('notifications.index') }}" class="block border-t border-slate-100 px-4 py-2.5 text-center text-xs font-semibold text-[#16776e] hover:bg-slate-50">{{ __('View all') }}</a>
                        </div>
                    </details>

                    <details class="vn-profile-menu relative">
                        <summary aria-label="{{ __('Account menu') }}" class="flex h-11 md:h-10 shrink-0 cursor-pointer list-none items-center gap-2 rounded-xl pl-1 pr-1 transition hover:bg-slate-100 md:hover:bg-white/10 2xl:pr-2.5">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-[#142235] text-sm font-bold text-[#e6b84a] md:bg-white/12 md:ring-1 md:ring-white/20">{{ $headerInitial }}</span>
                            <span class="hidden max-w-32 truncate text-sm font-semibold text-white 2xl:block">{{ \Illuminate\Support\Str::of($headerUserName)->before(' ') }}</span>
                            <x-icon name="chevron" :size="16" class="hidden text-white/60 2xl:block" />
                        </summary>
                        <div class="{{ $headerMenu }} w-64 py-1.5">
                            <div class="flex items-center gap-3 border-b border-slate-100 px-4 pb-3 pt-2">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#142235] font-bold text-[#e6b84a]">{{ $headerInitial }}</span>
                                <span class="min-w-0">
                                    <span class="block truncate font-semibold text-slate-900">{{ $headerUserName }}</span>
                                    <span class="block truncate text-xs text-slate-500">{{ auth()->user()->email ?? auth()->user()->mobile }}</span>
                                </span>
                            </div>
                            <a href="{{ route('profile.index') }}" class="{{ $headerMenuItem }}"><x-icon name="user" :size="18" class="text-slate-500" />{{ __('Settings') }}</a>
                            @if(auth()->user()->is_admin)
                                <a href="{{ route('admin.overview') }}" class="{{ $headerMenuItem }}"><x-icon name="shield" :size="18" class="text-slate-500" />{{ __('Account administration') }}</a>
                            @endif
                            @unless(auth()->user()->isTeamMember())
                                <a href="{{ route('firm-profile.index') }}" class="{{ $headerMenuItem }}"><x-icon name="building" :size="18" class="text-slate-500" />{{ __(auth()->user()->account_type === 'company' ? 'Firm profile' : 'Office & billing') }}</a>
                            @endunless
                            @include('layouts.partials.theme-switch')
                            <a href="{{ route('app.download') }}" class="{{ $headerMenuItem }}"><x-icon name="phone" :size="18" class="text-slate-500" />{{ __('Get the app') }}</a>
                            <form method="POST" action="{{ auth()->user()->is_admin ? route('admin.logout') : route('user.logout') }}" class="mt-1 border-t border-slate-100 pt-1">
                                @csrf
                                <button type="submit" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-red-700 hover:bg-red-50"><x-icon name="logout" :size="18" />{{ __('Log out') }}</button>
                            </form>
                        </div>
                    </details>
                @else
                    <button type="button" data-install-app hidden class="hidden h-10 items-center gap-2 rounded-xl px-3 text-sm font-semibold text-white/85 hover:bg-white/10 hover:text-white md:inline-flex">
                        <x-icon name="phone" :size="18" /> {{ __('Install app') }}
                    </button>
                    <a href="{{ route('app.download') }}" class="hidden h-10 items-center gap-2 rounded-xl px-3 text-sm font-semibold text-white/85 hover:bg-white/10 hover:text-white lg:inline-flex">
                        <x-icon name="phone" :size="18" /> {{ __('Get the app') }}
                    </a>
                    <button type="button" data-theme-cycle aria-label="{{ __('Switch light or dark') }}" title="{{ __('Light or dark') }}" class="{{ $headerIcon }}">
                        <x-icon name="moon" :size="19" />
                    </button>
                    <a href="{{ route('login') }}" data-vn-auth-open="login" class="inline-flex h-10 items-center whitespace-nowrap rounded-xl px-2.5 text-sm font-semibold text-slate-800 hover:bg-slate-100 sm:px-3 md:text-white md:hover:bg-white/10">{{ __('Log in') }}</a>
                    <a href="{{ route('register') }}" data-vn-auth-open="register" class="inline-flex h-10 items-center whitespace-nowrap rounded-xl bg-[#16776e] px-3 text-sm font-semibold text-white shadow-sm hover:bg-[#125f58] sm:px-3.5">{{ __('Sign up') }}</a>
                @endauth
            </div>
        </header>

        @unless(request()->boolean('embed'))
            @include('layouts.partials.section-nav')
        @endunless

        <main class="vn-content vn-main-content flex-1 p-5">
            {{--
                Computers: Back (only where needed) and the page name above the page. The name hides when the page
                has its own title, and the line disappears when it would be empty.
            --}}
            @unless(request()->routeIs('dashboard'))
                <div class="vn-page-trail -mt-2 mb-4 hidden w-full items-center gap-3 md:flex">
                    @if($needsBack)
                        <x-back-link class="shrink-0" :fallback="trim($__env->yieldContent('back-url')) ?: route('dashboard')" />
                    @endif
                    <span class="vn-trail-title min-w-0 truncate text-sm font-semibold text-slate-600">@yield('page-heading')</span>
                </div>
            @endunless
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
            <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
                // Line Back up with the page's own content column (pages use different widths), before the first paint.
                (function () {
                    const trail = document.querySelector('.vn-page-trail');
                    let column = trail ? trail.nextElementSibling : null;
                    while (column && (column.hasAttribute('role') || column.tagName === 'SCRIPT')) {
                        column = column.nextElementSibling;
                    }
                    if (!column) {
                        return;
                    }
                    const width = getComputedStyle(column).maxWidth;
                    if (width !== 'none') {
                        trail.style.maxWidth = width;
                        trail.style.marginInline = 'auto';
                    }
                }());
            </script>
        </main>

        {{-- Visitors only: once signed in, the header menu covers everything and the footer would only add length. --}}
        @guest
            @php
                $footerLink = 'text-[#d3dde8] transition hover:text-white hover:underline';
                $footerHeading = 'mb-2.5 text-xs font-bold uppercase tracking-[0.12em] text-[#e6b84a]';
            @endphp
            <footer class="vn-footer bg-[#142235] text-[#c3cfdc]" data-vn-footer>
                <div class="mx-auto max-w-6xl px-5 py-8 sm:px-8">
                    <div class="grid gap-8 sm:grid-cols-3 lg:grid-cols-[minmax(0,1.4fr)_repeat(3,minmax(0,1fr))_auto]">
                        <div class="sm:col-span-3 lg:col-span-1">
                            <a href="{{ route('dashboard') }}" aria-label="VakilNiti home" class="inline-flex rounded-lg">
                                <x-brand tone="dark" :size="34" />
                            </a>
                            <p class="mt-3 max-w-xs text-sm leading-relaxed">{{ __('Case diary, hearings, clients and invoices for advocates in India.') }}</p>
                        </div>

                        <nav class="contents" aria-label="{{ __('Footer') }}">
                            <div>
                                <p class="{{ $footerHeading }}">{{ __('Get started') }}</p>
                                <ul class="space-y-1.5 text-sm">
                                    <li><a href="{{ route('register') }}" data-vn-auth-open="register" class="{{ $footerLink }}">{{ __('Create an account') }}</a></li>
                                    <li><a href="{{ route('login') }}" data-vn-auth-open="login" class="{{ $footerLink }}">{{ __('Log in') }}</a></li>
                                    <li><a href="{{ route('app.download') }}" class="{{ $footerLink }}">{{ __('Install on your phone') }}</a></li>
                                </ul>
                            </div>
                            <div>
                                <p class="{{ $footerHeading }}">{{ __('Courts') }}</p>
                                <ul class="space-y-1.5 text-sm">
                                    <li><a href="{{ route('courts.index') }}" class="{{ $footerLink }}">{{ __('All courts') }}</a></li>
                                    <li><a href="{{ route('courts.supreme-court') }}" class="{{ $footerLink }}">{{ __('Supreme Court') }}</a></li>
                                    <li><a href="{{ route('courts.high-courts') }}" class="{{ $footerLink }}">{{ __('High Courts') }}</a></li>
                                    <li><a href="{{ route('courts.display-boards') }}" class="{{ $footerLink }}">{{ __('Display boards') }}</a></li>
                                </ul>
                            </div>
                            <div>
                                <p class="{{ $footerHeading }}">{{ __('Library') }}</p>
                                <ul class="space-y-1.5 text-sm">
                                    <li><a href="{{ route('bare-acts.index') }}" class="{{ $footerLink }}">{{ __('Bare Acts') }}</a></li>
                                    <li><a href="{{ route('new-criminal-laws.index') }}" class="{{ $footerLink }}">{{ __('New criminal laws') }}</a></li>
                                    <li><a href="{{ route('court-fee.index') }}" class="{{ $footerLink }}">{{ __('Court fee') }}</a></li>
                                    <li><a href="{{ route('miscellaneous-forms.index') }}" class="{{ $footerLink }}">{{ __('Forms') }}</a></li>
                                </ul>
                            </div>
                        </nav>

                        <div class="sm:col-span-3 lg:col-span-1">
                            <p class="{{ $footerHeading }}">{{ __('Get the app') }}</p>
                            @include('layouts.partials.app-store-buttons')
                        </div>
                    </div>

                    <div class="mt-7 flex flex-col gap-2 border-t border-white/10 pt-4 text-xs text-[#93a4b7] lg:flex-row lg:items-center lg:justify-between lg:gap-6">
                        <p>&copy; {{ date('Y') }} VakilNiti · <a href="{{ route('legal.privacy') }}" class="hover:text-white hover:underline">{{ __('Privacy') }}</a> · <a href="{{ route('legal.terms') }}" class="hover:text-white hover:underline">{{ __('Terms') }}</a></p>
                        <p class="leading-relaxed lg:text-right">{{ __('General information, not legal advice. Not affiliated with any court or government body.') }}</p>
                    </div>
                </div>
            </footer>
        @endguest

    </div>
</div>

<nav class="vn-mobile-nav" aria-label="{{ __('Quick navigation') }}">
    <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'is-current' : '' }}"><x-icon name="home" :size="21" class="vn-mobile-icon" /><span>{{ __('Home') }}</span></a>
    @auth
        <a href="{{ route('cases.index') }}" class="{{ request()->is('cases*', 'today', 'tasks*', 'case-calendar*', 'hearings*') ? 'is-current' : '' }}"><x-icon name="briefcase" :size="21" class="vn-mobile-icon" /><span>{{ __('Cases') }}</span></a>
    @endauth
    <a href="{{ route('courts.index') }}" class="{{ request()->is('courts*', 'tribunals*', 'court-catalogue*') ? 'is-current' : '' }}"><x-icon name="pillars" :size="21" class="vn-mobile-icon" /><span>{{ __('Courts') }}</span></a>
    @auth
        <a href="{{ route('notifications.index') }}" class="relative {{ request()->is('notifications*') ? 'is-current' : '' }}" aria-label="{{ __('Alerts') }}{{ ($bellUnreadCount ?? 0) ? ', '.$bellUnreadCount.' '.__('unread') : '' }}">
            <x-icon name="bell" :size="21" class="vn-mobile-icon" /><span>{{ __('Alerts') }}</span>
            @if($bellUnreadCount ?? 0)
                <span aria-hidden="true" class="absolute left-1/2 top-1 ml-1.5 flex h-[18px] min-w-[18px] items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-bold text-white ring-2 ring-white">{{ $bellUnreadCount > 9 ? '9+' : $bellUnreadCount }}</span>
            @endif
        </a>
    @else
        <a href="{{ route('bare-acts.index') }}" class="{{ request()->is('bare-acts*') ? 'is-current' : '' }}"><x-icon name="book" :size="21" class="vn-mobile-icon" /><span>{{ __('Bare Acts') }}</span></a>
        <a href="{{ route('login') }}" data-vn-auth-open="login"><x-icon name="user" :size="21" class="vn-mobile-icon" /><span>{{ __('Log in') }}</span></a>
    @endauth
    <button type="button" id="vn-mobile-more" aria-label="{{ __('Open the full menu') }}" aria-controls="vn-sidebar" aria-expanded="false"><x-icon name="list" :size="21" class="vn-mobile-icon" /><span>{{ __('Menu') }}</span></button>
</nav>

<script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
    (function () {
        const body = document.body;
        const backdrop = document.getElementById('vn-sidebar-backdrop');
        const more = document.getElementById('vn-mobile-more');
        const sidebar = document.getElementById('vn-sidebar');
        function setMenu(open) {
            const wasOpen = body.classList.contains('vn-menu-open');
            body.classList.toggle('vn-menu-open', open);
            if (more) more.setAttribute('aria-expanded', open ? 'true' : 'false');
            if (open && !wasOpen) sidebar?.querySelector('summary')?.focus({ preventScroll: true });
            if (!open && wasOpen && window.innerWidth < 768) more?.focus({ preventScroll: true });
        }
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

        // A page's own heading that repeats the header title is hidden on phones (.vn-dup-title).
        const headerTitle = document.querySelector('.vn-header-title h2')?.textContent.trim().toLowerCase();
        const pageTitle = document.querySelector('.vn-main-content h1');
        if (headerTitle && pageTitle && pageTitle.textContent.trim().toLowerCase() === headerTitle) pageTitle.classList.add('vn-dup-title');
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
            @php
                $loginCodeMobile = session('login_otp_mobile');
                $loginCodeEmail = $loginCodeMobile ? null : session('login_otp_email');
                $loginVia = session('login_via') ?? (old('mobile') ? 'mobile' : 'email');
            @endphp
            @if((session('show_login_otp') && ($loginCodeMobile || $loginCodeEmail)) || $loginCodeMobile || $loginCodeEmail)
                <p class="mb-4 text-sm text-slate-600">
                    @if($loginCodeMobile)
                        Enter the 4-digit code sent by SMS to <strong class="text-slate-900">{{ $loginCodeMobile }}</strong>.
                    @else
                        Enter the 6-digit code we emailed to <strong class="text-slate-900">{{ $loginCodeEmail }}</strong>. Check spam if it is not in your inbox.
                    @endif
                </p>
                <form method="POST" action="{{ route('login.verify.submit') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label for="vn-login-otp" class="mb-1 block text-sm font-medium text-slate-700">{{ $loginCodeMobile ? 'Code from the SMS' : 'Code from the email' }}</label>
                        <input id="vn-login-otp" name="otp" inputmode="numeric" pattern="[0-9]{{ '{'.($loginCodeMobile ? 4 : 6).'}' }}" maxlength="{{ $loginCodeMobile ? 4 : 6 }}"
                               required autocomplete="one-time-code"
                               class="w-full rounded-lg border border-slate-300 p-3 text-center text-xl tracking-widest">
                    </div>
                    <button type="submit" class="w-full rounded-lg bg-[#16776e] px-4 py-3 font-semibold text-white hover:bg-[#125f58]">
                        Verify and log in
                    </button>
                </form>
                <div class="mt-4 flex flex-wrap items-center justify-center gap-x-5 gap-y-2">
                    <form method="POST" action="{{ route('login.submit') }}">
                        @csrf
                        @if($loginCodeMobile)
                            <input type="hidden" name="mobile" value="{{ $loginCodeMobile }}">
                        @else
                            <input type="hidden" name="email" value="{{ $loginCodeEmail }}">
                        @endif
                        <button type="submit" class="text-sm font-semibold text-[#16776e] underline">Send a new code</button>
                    </form>
                    <form method="POST" action="{{ route('login.change') }}">
                        @csrf
                        <button type="submit" class="text-sm font-semibold text-slate-600 underline">{{ $loginCodeMobile ? 'Use another number or email' : 'Use another email or number' }}</button>
                    </form>
                </div>
            @else
                {{-- Log in with email (code by email) or with the mobile number on the account (code by SMS). --}}
                <div class="mb-4 grid grid-cols-3 rounded-xl bg-slate-100 p-1 text-sm font-semibold" role="tablist" aria-label="Log in with">
                    <button type="button" role="tab" data-login-via="email" aria-selected="{{ $loginVia === 'email' ? 'true' : 'false' }}"
                            class="min-h-10 rounded-lg text-slate-600 aria-selected:bg-white aria-selected:text-[#16776e] aria-selected:shadow-sm">Email</button>
                    <button type="button" role="tab" data-login-via="code" aria-selected="{{ $loginVia === 'code' ? 'true' : 'false' }}"
                            class="min-h-11 rounded-lg text-slate-600 aria-selected:bg-white aria-selected:text-[#16776e]">Email code</button>
                    <button type="button" role="tab" data-login-via="mobile" aria-selected="{{ $loginVia === 'mobile' ? 'true' : 'false' }}"
                            class="min-h-10 rounded-lg text-slate-600 aria-selected:bg-white aria-selected:text-[#16776e] aria-selected:shadow-sm">Mobile number</button>
                </div>
                <form method="POST" action="{{ route('login.password') }}" class="space-y-4" data-login-form="email" @if($loginVia !== 'email') hidden @endif>
                    @csrf
                    <label class="block text-sm font-medium">Email
                        <input name="email" type="email" autocomplete="username" value="{{ old('email') }}" required class="mt-1 w-full rounded-lg border border-slate-300 p-3">
                    </label>
                    <label class="block text-sm font-medium">Password
                        <input name="password" type="password" autocomplete="current-password" required class="mt-1 w-full rounded-lg border border-slate-300 p-3">
                    </label>
                    <button class="min-h-11 w-full rounded-lg bg-teal-700 font-semibold text-white">Sign in with password</button>
                    <p class="text-xs text-slate-600">Use Email code if you need to set or reset your password.</p>
                </form>
                <form method="POST" action="{{ route('login.submit') }}" class="space-y-4" data-login-form="code" @if($loginVia !== 'code') hidden @endif>
                    @csrf
                    <div>
                        <label for="vn-login-email" class="mb-1 block text-sm font-medium text-slate-700">Email</label>
                        <input id="vn-login-email" type="email" name="email" value="{{ old('email') }}" required
                               placeholder="you@example.com" autocomplete="email" inputmode="email"
                               class="w-full rounded-lg border border-slate-300 p-3">
                    </div>
                    <button type="submit" class="w-full rounded-lg bg-[#16776e] px-4 py-3 font-semibold text-white hover:bg-[#125f58]">
                        Email me a code
                    </button>
                </form>
                <form method="POST" action="{{ route('login.submit') }}" class="space-y-4" data-login-form="mobile" @if($loginVia !== 'mobile') hidden @endif>
                    @csrf
                    <div>
                        <label for="vn-login-mobile" class="mb-1 block text-sm font-medium text-slate-700">Mobile number</label>
                        <input id="vn-login-mobile" type="tel" name="mobile" value="{{ old('mobile') }}" required
                               placeholder="10-digit Indian mobile number" autocomplete="tel" inputmode="tel"
                               class="w-full rounded-lg border border-slate-300 p-3">
                    </div>
                    <button type="submit" class="w-full rounded-lg bg-[#16776e] px-4 py-3 font-semibold text-white hover:bg-[#125f58]">
                        Send code by SMS
                    </button>
                </form>
            @endif
            <p class="mt-5 text-center text-sm text-slate-600">
                New to VakilNiti?
                <a href="{{ route('register') }}" data-vn-auth-open="register" class="font-semibold text-[#16776e] underline">Create an account</a>
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
                    <label for="vn-register-email" class="mb-1 block text-sm font-medium text-slate-700">Email</label>
                    <input id="vn-register-email" type="email" name="email" value="{{ old('email') }}"
                           placeholder="you@example.com" autocomplete="email" inputmode="email"
                           required class="w-full rounded-lg border border-slate-300 p-3">
                </div>
                <button type="submit" class="w-full rounded-lg bg-[#16776e] px-4 py-3 font-semibold text-white hover:bg-[#125f58]">
                    Email me a code
                </button>
                <p class="text-center text-xs text-slate-500">
                    By continuing you agree to the <a href="{{ route('legal.terms') }}" class="font-semibold text-[#16776e] underline">Terms</a>
                    and <a href="{{ route('legal.privacy') }}" class="font-semibold text-[#16776e] underline">Privacy policy</a>.
                </p>
            </form>
            <p class="mt-5 text-center text-sm text-slate-600">
                Already registered?
                <a href="{{ route('login') }}" data-vn-auth-open="login" class="font-semibold text-[#16776e] underline">Log in</a>
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
            <p class="mb-5 text-sm text-slate-600">Enter the 6-digit code we emailed to you. Check spam if it is not in your inbox.</p>
            <form method="POST" action="{{ route('register.verify.submit') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="vn-register-otp" class="mb-1 block text-sm font-medium text-slate-700">Code from the email</label>
                    <input id="vn-register-otp" name="otp" inputmode="numeric" pattern="[0-9]{6}" maxlength="6"
                           required autocomplete="one-time-code"
                           class="w-full rounded-lg border border-slate-300 p-3 text-center text-xl tracking-widest">
                </div>
                <button type="submit" class="w-full rounded-lg bg-[#16776e] px-4 py-3 font-semibold text-white hover:bg-[#125f58]">
                    Verify and create account
                </button>
            </form>
            <form method="POST" action="{{ route('register.resend-otp') }}" class="mt-4 text-center">
                @csrf
                <button type="submit" class="text-sm font-semibold text-[#16776e] underline">Send a new code</button>
            </form>
            <p class="mt-5 text-center text-sm">
                <a href="{{ route('register') }}" data-vn-auth-open="register" class="font-semibold text-[#16776e] underline">Start again</a>
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
                description: 'We send you a one-time code. No password needed.'
            },
            register: {
                title: 'Create your VakilNiti account',
                description: 'Set up your individual workspace.'
            },
            verify: {
                title: 'Check your email',
                description: 'Confirm your email to continue.'
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

        document.querySelectorAll('[data-login-via]').forEach(function (tab) {
            tab.addEventListener('click', function () {
                document.querySelectorAll('[data-login-via]').forEach(function (other) {
                    other.setAttribute('aria-selected', other === tab ? 'true' : 'false');
                });
                document.querySelectorAll('[data-login-form]').forEach(function (form) {
                    form.hidden = form.dataset.loginForm !== tab.dataset.loginVia;
                    if (!form.hidden) {
                        form.querySelector('input:not([type=hidden])').focus();
                    }
                });
            });
        });

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
        // Official websites open inside VakilNiti when they allow it. Sites that forbid being
        // shown inside other websites (most court sites) open in a side window instead, so
        // VakilNiti stays open behind them. Links marked data-direct-share open normally.
        const dialog = document.getElementById('vn-webview-dialog');
        const frame = document.getElementById('vn-webview-frame');
        const hostLabel = document.getElementById('vn-webview-host');
        const newTab = document.getElementById('vn-webview-new-tab');
        const localHosts = ['localhost', '127.0.0.1', '::1'];
        const currentHost = window.location.hostname.toLowerCase();
        const embeddable = {};

        const externalUrl = function (link) {
            if (link.hasAttribute('data-direct-share')) return null;
            const href = link.getAttribute('href') || '';
            if (!/^https?:\/\//i.test(href)) return null;

            return new URL(href, window.location.origin);
        };

        const openSideWindow = function (url) {
            const width = Math.min(1180, window.screen.availWidth - 40);
            const height = Math.min(820, window.screen.availHeight - 60);
            const left = Math.max(0, window.screen.availWidth - width - 20);
            const opened = window.open(url, 'vn-court-site', 'popup=yes,width=' + width + ',height=' + height + ',left=' + left + ',top=40');

            if (opened) {
                opened.focus();
            }

            return Boolean(opened);
        };

        const openInDialog = function (url) {
            hostLabel.textContent = url.hostname;
            newTab.href = url.toString();
            frame.src = url.toString();
            dialog.showModal();
        };

        const links = Array.from(document.querySelectorAll('a[href]')).filter(externalUrl);
        const hosts = Array.from(new Set(links.map((link) => externalUrl(link).hostname.toLowerCase())))
            .filter((host) => host !== currentHost && !localHosts.includes(host));

        if (hosts.length) {
            const query = hosts.slice(0, 40).map((host) => 'hosts[]=' + encodeURIComponent(host)).join('&');
            fetch('{{ route('embed-check') }}?' + query, { headers: { Accept: 'application/json' } })
                .then((response) => (response.ok ? response.json() : {}))
                .then((answers) => Object.assign(embeddable, answers))
                .catch(() => {});
        }

        links.forEach(function (link) {
            link.addEventListener('click', function (event) {
                if (event.metaKey || event.ctrlKey || event.shiftKey) return;

                const url = externalUrl(link);
                const targetHost = url.hostname.toLowerCase();
                const sameSite = targetHost === currentHost || (localHosts.includes(targetHost) && localHosts.includes(currentHost));

                if (sameSite) {
                    if (url.origin !== window.location.origin || link.target.toLowerCase() === '_blank') {
                        event.preventDefault();
                        window.location.assign(url.toString());
                    }
                    return;
                }

                event.preventDefault();

                if (embeddable[targetHost] === true && typeof dialog.showModal === 'function') {
                    openInDialog(url);
                } else if (!openSideWindow(url.toString())) {
                    window.open(url.toString(), '_blank', 'noopener');
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
