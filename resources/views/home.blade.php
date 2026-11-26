@extends('layouts.app')

@section('title', __('Home'))
@section('page-heading', __('Home'))

@section('content')
<div class="mx-auto max-w-[1500px] space-y-6">
    @guest
        @include('landing')

        {{-- Visitors: one tap to the free court tools. --}}
        <header class="rounded-3xl bg-[#142235] p-4 text-white shadow-lg sm:p-5">
            <h2 class="text-xl font-semibold tracking-tight sm:text-2xl">{{ __('Free court tools · no sign-in needed') }}</h2>
            <nav aria-label="{{ __('Quick actions') }}" class="mt-3 grid grid-cols-3 gap-2 sm:grid-cols-6">
                @foreach([
                    [__('Supreme Court'), 'pillars', route('courts.supreme-court')],
                    [__('Delhi HC'), 'building', route('courts.delhi-high-court')],
                    [__('District courts'), 'gavel', route('courts.delhi-district-courts')],
                    [__('Display boards'), 'board', route('courts.display-boards')],
                    [__('Court fee'), 'calculator', route('court-fee.index')],
                    [__('Bare Acts'), 'book', route('bare-acts.index')],
                ] as [$actionLabel, $actionIcon, $actionUrl])
                    <a href="{{ $actionUrl }}" class="flex min-h-[72px] flex-col items-center justify-center gap-1.5 rounded-2xl bg-white/[0.07] px-1 text-center text-xs font-semibold text-white ring-1 ring-white/10 transition hover:bg-white/15 sm:text-sm">
                        <x-icon :name="$actionIcon" :size="22" class="text-[#e6b84a]" />
                        {{ $actionLabel }}
                    </a>
                @endforeach
            </nav>
        </header>

        @include('courts.partials.board-picker')
    @endguest

    @auth
        @php
            $boardsOnHome = (bool) auth()->user()->preference('home_display_boards');
        @endphp

        <div class="grid items-start gap-3 lg:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)] lg:gap-5">
            <x-tabs label="Home sections" :tabs="[
                'home-hearings' => __('Hearings'),
                'home-boards' => __('Display boards'),
                'home-agenda' => __('Agenda'),
            ]" compact data-mobile-tabs class="lg:hidden" />

            <section id="home-hearings" data-tab-panel role="tabpanel" aria-labelledby="tab-home-hearings" class="min-w-0 scroll-mt-24 space-y-4">
                <div class="rounded-xl border border-slate-200 bg-white p-3 sm:p-4">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h1 class="text-xl font-bold tracking-tight text-slate-900">{{ __('Upcoming hearings') }}</h1>
                        <a href="{{ route('hearings.create') }}" data-sheet="{{ route('sheets.next-date') }}" class="vn-link inline-flex min-h-10 items-center gap-1 text-sm font-semibold">
                            <x-icon name="plus" :size="16" />{{ __('Add hearing') }}
                        </a>
                    </div>

                    <form method="GET" action="{{ route('dashboard') }}#home-hearings" class="mt-2 flex items-center gap-2" role="search">
                        <div class="flex min-w-0 flex-1 items-center gap-1 rounded-lg border border-slate-300 pr-2.5 focus-within:border-[#16776e]">
                            <button type="submit" aria-label="{{ __('Search hearings') }}" class="inline-flex size-11 shrink-0 items-center justify-center text-slate-600">
                                <x-icon name="search" :size="17" />
                            </button>
                            <label for="hearing-search" class="sr-only">{{ __('Search hearings') }}</label>
                            <input id="hearing-search" type="search" name="hearing_search" value="{{ $hearingSearch }}" maxlength="100"
                                   enterkeyhint="search" placeholder="{{ __('Case, number or court') }}"
                                   class="min-h-11 w-full min-w-0 border-0 bg-transparent px-0 text-sm focus:ring-0">
                            @if($hearingSearch !== '')
                                <a href="{{ route('dashboard') }}#home-hearings" class="vn-link shrink-0 text-xs font-semibold">{{ __('Clear') }}</a>
                            @endif
                        </div>
                        <a href="{{ route('cases.calendar', ['view' => 'week']) }}" aria-label="{{ __('Open calendar') }}" class="vn-link inline-flex size-11 shrink-0 items-center justify-center rounded-lg border border-slate-300 bg-white">
                            <x-icon name="calendar" :size="20" />
                        </a>
                    </form>

                    @if($today ?? null)
                        <div class="mt-2 hidden flex-wrap gap-x-4 gap-y-1 border-t border-slate-100 pt-2 text-sm font-medium lg:flex">
                            <a href="{{ route('cases.calendar', ['view' => 'day']) }}">{{ $today['hearings'] }} {{ __('today') }}</a>
                            <a href="{{ route('cases.calendar', ['view' => 'day', 'date' => today()->addDay()->toDateString()]) }}">{{ $today['tomorrow'] }} {{ __('tomorrow') }}</a>
                            <a href="{{ route('tasks.index') }}">{{ $today['tasks'] }} {{ trans_choice('task due|tasks due', $today['tasks']) }}</a>
                        </div>
                    @endif
                </div>

                @include('partials.home-hearing-days')
            </section>

            <div class="contents lg:block lg:min-w-0 lg:space-y-5">
                <div id="home-boards" data-tab-panel role="tabpanel" aria-labelledby="tab-home-boards" class="min-w-0 scroll-mt-24 {{ $boardsOnHome ? '' : 'lg:hidden' }}">
                    @if($boardsOnHome)
                        @include('courts.partials.board-picker')
                    @else
                        <div class="rounded-xl border border-slate-200 bg-white p-4 lg:hidden">
                            <a href="{{ route('courts.display-boards') }}" class="vn-link inline-flex min-h-11 items-center gap-2 font-semibold">
                                <x-icon name="board" :size="18" />{{ __('Open live display boards') }}
                            </a>
                        </div>
                    @endif
                </div>
                <div id="home-agenda" data-tab-panel role="tabpanel" aria-labelledby="tab-home-agenda" class="min-w-0 scroll-mt-24">
                    @include('partials.home-agenda')
                </div>
            </div>
        </div>

        @if($profile ?? null)
            <section class="flex flex-wrap items-center gap-4 rounded-2xl border border-teal-200 bg-teal-50/70 p-4 sm:p-5" aria-label="{{ __('Profile completion') }}">
                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-white text-sm font-bold text-[#16776e] ring-4 ring-teal-100">{{ $profile['percent'] }}%</div>
                {{-- At least 12rem wide, so on a phone the buttons move to their own row instead of squeezing the text. --}}
                <div class="min-w-[12rem] flex-1">
                    <p class="font-semibold text-slate-900">{{ __('Your profile is :percent% complete', ['percent' => $profile['percent']]) }}</p>
                    <div class="mt-2 h-2 max-w-md overflow-hidden rounded-full bg-white">
                        <div class="h-full rounded-full bg-[#16776e]" style="width: {{ $profile['percent'] }}%"></div>
                    </div>
                    <ul class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-sm">
                        @foreach(array_slice($profile['missing'], 0, 3) as $item)
                            <li><a href="{{ $item['url'] }}" class="font-medium text-[#16776e] hover:underline">{{ __($item['label']) }} &rarr;</a></li>
                        @endforeach
                    </ul>
                </div>
                <div class="flex w-full items-center gap-1 sm:w-auto">
                    <a href="{{ route('profile.setup.start') }}" class="inline-flex min-h-10 flex-1 items-center justify-center whitespace-nowrap rounded-lg bg-[#16776e] px-4 text-sm font-semibold text-white hover:bg-[#125f58] sm:flex-none">{{ __('Complete my profile') }} &rarr;</a>
                    <form method="POST" action="{{ route('home.profile-banner.hide') }}">
                        @csrf
                        <button type="submit" class="rounded-lg px-3 py-2 text-sm font-semibold text-slate-500 hover:bg-white hover:text-slate-700">{{ __('Hide') }}</button>
                    </form>
                </div>
            </section>
        @endif

        @unless($hasCases ?? true)
            <section class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <div>
                    <p class="font-semibold text-slate-900">{{ __('Bring your cases in from eCourts') }}</p>
                </div>
                <a href="{{ route('cases.find') }}" class="inline-flex min-h-10 items-center rounded-lg bg-[#16776e] px-4 text-sm font-semibold text-white hover:bg-[#125f58]">{{ __('Find my cases') }}</a>
            </section>
        @endunless
    @endauth


</div>
@endsection

