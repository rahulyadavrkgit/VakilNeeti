@extends('layouts.app')

@section('title', 'Get the VakilNiti app for Android and iPhone')
@section('page-heading', 'Get the app')
@section('meta_description', 'Install VakilNiti on your Android phone, iPhone, iPad or computer: your case diary, hearings, clients, invoices and court display boards one tap away.')

@php
    $playStoreUrl = config('services.mobile_apps.play_store');
    $appStoreUrl = config('services.mobile_apps.app_store');
    $step = 'flex gap-3';
    $stepNumber = 'flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-teal-50 text-sm font-bold text-[#16776e]';
@endphp

@push('styles')
    <style>
        .vn-install-yours { display: none; }
        .vn-install-card[data-current-platform] { order: -1; border-color: #16776e; box-shadow: 0 0 0 3px rgba(22, 119, 110, .14); }
        .vn-install-card[data-current-platform] .vn-install-yours { display: block; }
    </style>
@endpush

@section('content')
<div class="mx-auto max-w-5xl space-y-6">
    <header class="relative overflow-hidden rounded-3xl bg-[#142235] p-6 text-white shadow-lg sm:p-9">
        <div class="pointer-events-none absolute -right-16 -top-20 h-64 w-64 rounded-full border border-[#e6b84a]/25"></div>
        <div class="relative grid items-center gap-6 md:grid-cols-[minmax(0,1fr)_auto]">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.14em] text-[#e6b84a]">VakilNiti app</p>
                <h1 class="mt-2 text-2xl font-semibold leading-tight text-white sm:text-4xl">Your practice in your pocket</h1>
                <p class="mt-3 max-w-xl text-sm leading-relaxed text-[#c9d4e0] sm:text-base">
                    Open your case diary, today's hearings, clients, invoices and court display boards straight from your home screen, full screen and without typing the address.
                </p>
                <div class="mt-5 flex flex-wrap items-center gap-3">
                    <button type="button" data-install-app hidden class="inline-flex min-h-12 items-center gap-2 rounded-xl bg-[#e6b84a] px-5 text-sm font-bold text-[#142235] shadow-sm hover:bg-[#f0c75f]">
                        <x-icon name="download" :size="18" /> Install VakilNiti now
                    </button>
                    <p data-installed-note hidden class="rounded-xl bg-white/10 px-4 py-3 text-sm font-semibold text-white">You're using the installed app.</p>
                    @include('layouts.partials.app-store-buttons')
                </div>
            </div>
            <img src="{{ asset('images/logo-mark.svg') }}" alt="" width="148" height="148" class="hidden rounded-[34px] shadow-2xl ring-1 ring-white/10 md:block">
        </div>
    </header>

    @unless($playStoreUrl && $appStoreUrl)
        <p class="rounded-2xl border border-teal-200 bg-teal-50/70 p-4 text-sm leading-relaxed text-slate-700">
            <strong class="text-slate-900">No download from a store is needed.</strong>
            VakilNiti installs straight from this website in a few seconds, takes almost no space and always stays up to date.
        </p>
    @endunless

    <div class="grid gap-4 md:grid-cols-3">
        <section id="android" data-platform-hint="android" class="vn-install-card scroll-mt-24 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="flex items-center gap-2 text-lg font-semibold text-slate-900">
                <svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true"><path fill="#3ddc84" d="M17.6 9.48l1.84-3.18a.38.38 0 0 0-.66-.38l-1.87 3.23A11.4 11.4 0 0 0 12 8.12c-1.77 0-3.43.4-4.91 1.03L5.22 5.92a.38.38 0 0 0-.66.38L6.4 9.48A10.8 10.8 0 0 0 1 18h22a10.8 10.8 0 0 0-5.4-8.52zM7 15.25a1.25 1.25 0 1 1 0-2.5 1.25 1.25 0 0 1 0 2.5zm10 0a1.25 1.25 0 1 1 0-2.5 1.25 1.25 0 0 1 0 2.5z"/></svg>
                Android phone
            </h2>
            <p class="vn-install-yours mt-1 text-xs font-bold uppercase tracking-wide text-[#16776e]">This device</p>
            @if($playStoreUrl)
                <a href="{{ $playStoreUrl }}" target="_blank" rel="noopener" data-direct-share class="mt-4 inline-flex min-h-11 items-center rounded-xl bg-[#16776e] px-4 text-sm font-semibold text-white hover:bg-[#125f58]">Open in Google Play</a>
                <p class="mt-4 text-sm text-slate-600">Or install it from this website:</p>
            @endif
            <ol class="mt-4 space-y-3 text-sm text-slate-700">
                <li class="{{ $step }}"><span class="{{ $stepNumber }}">1</span><span>Open <strong>vakilniti.com</strong> in <strong>Chrome</strong>.</span></li>
                <li class="{{ $step }}"><span class="{{ $stepNumber }}">2</span><span>Tap <strong>Install</strong> when it appears, or tap the <strong>⋮</strong> menu.</span></li>
                <li class="{{ $step }}"><span class="{{ $stepNumber }}">3</span><span>Choose <strong>Install app</strong> (or <strong>Add to Home screen</strong>).</span></li>
            </ol>
        </section>

        <section id="iphone" data-platform-hint="ios" class="vn-install-card scroll-mt-24 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="flex items-center gap-2 text-lg font-semibold text-slate-900"><x-icon name="phone" :size="22" class="text-slate-700" /> iPhone &amp; iPad</h2>
            <p class="vn-install-yours mt-1 text-xs font-bold uppercase tracking-wide text-[#16776e]">This device</p>
            @if($appStoreUrl)
                <a href="{{ $appStoreUrl }}" target="_blank" rel="noopener" data-direct-share class="mt-4 inline-flex min-h-11 items-center rounded-xl bg-[#16776e] px-4 text-sm font-semibold text-white hover:bg-[#125f58]">Open in the App Store</a>
                <p class="mt-4 text-sm text-slate-600">Or install it from this website:</p>
            @endif
            <ol class="mt-4 space-y-3 text-sm text-slate-700">
                <li class="{{ $step }}"><span class="{{ $stepNumber }}">1</span><span>Open <strong>vakilniti.com</strong> in <strong>Safari</strong>.</span></li>
                <li class="{{ $step }}"><span class="{{ $stepNumber }}">2</span><span>Tap the <strong>Share</strong> button <span aria-hidden="true">(the square with an arrow)</span>.</span></li>
                <li class="{{ $step }}"><span class="{{ $stepNumber }}">3</span><span>Choose <strong>Add to Home Screen</strong>, then <strong>Add</strong>.</span></li>
            </ol>
        </section>

        <section id="computer" data-platform-hint="desktop" class="vn-install-card scroll-mt-24 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="flex items-center gap-2 text-lg font-semibold text-slate-900"><x-icon name="screen" :size="22" class="text-slate-700" /> Computer</h2>
            <p class="vn-install-yours mt-1 text-xs font-bold uppercase tracking-wide text-[#16776e]">This device</p>
            <ol class="mt-4 space-y-3 text-sm text-slate-700">
                <li class="{{ $step }}"><span class="{{ $stepNumber }}">1</span><span>Open <strong>vakilniti.com</strong> in <strong>Chrome</strong> or <strong>Edge</strong>.</span></li>
                <li class="{{ $step }}"><span class="{{ $stepNumber }}">2</span><span>Click the <strong>install</strong> icon at the right end of the address bar.</span></li>
                <li class="{{ $step }}"><span class="{{ $stepNumber }}">3</span><span>Click <strong>Install</strong>. VakilNiti opens in its own window.</span></li>
            </ol>
        </section>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <h2 class="text-lg font-semibold text-slate-900">What you get</h2>
        <ul class="mt-4 grid gap-4 text-sm text-slate-700 sm:grid-cols-2">
            <li class="flex gap-3"><x-icon name="calendar" :size="22" class="text-[#16776e]" /><span><strong class="block text-slate-900">Today's hearings first</strong>Your diary and reminders open in one tap.</span></li>
            <li class="flex gap-3"><x-icon name="board" :size="22" class="text-[#16776e]" /><span><strong class="block text-slate-900">Live display boards</strong>Check the Supreme Court and High Court boards from court.</span></li>
            <li class="flex gap-3"><x-icon name="receipt" :size="22" class="text-[#16776e]" /><span><strong class="block text-slate-900">Share invoices on WhatsApp</strong>Send the PDF to a client straight from your phone.</span></li>
            <li class="flex gap-3"><x-icon name="shield" :size="22" class="text-[#16776e]" /><span><strong class="block text-slate-900">Always up to date</strong>No updates to download; your data stays in your account.</span></li>
        </ul>
    </section>
</div>
@endsection
