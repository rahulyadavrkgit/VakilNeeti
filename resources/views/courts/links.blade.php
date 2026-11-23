@extends('layouts.app')

@section('title', $title)
@section('page-heading', $heading)
@section('meta_description', $metaDescription)

@section('content')
<div class="mx-auto max-w-5xl space-y-5">
    @include('courts.partials.breadcrumb', ['current' => $heading])

    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">{{ $title }}</h1>
            <p class="mt-1 text-sm text-slate-600">{{ $intro }}</p>
        </div>
        <a href="{{ route('courts.display-boards') }}" class="text-sm font-semibold text-[#16776e] hover:underline">All display boards &rarr;</a>
    </div>

    @if($showCnrSearch ?? false)
        <form method="GET" action="{{ auth()->check() ? route('cases.index') : route('login') }}" role="search"
              class="flex flex-wrap items-end gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <label class="min-w-[220px] flex-1 text-sm font-medium text-slate-700">
                Find a case in your diary by CNR number
                <input name="search" maxlength="16" placeholder="e.g. DLHC010215262023" autocapitalize="characters"
                       class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 font-mono uppercase">
            </label>
            <button type="submit" class="inline-flex min-h-11 items-center gap-2 rounded-lg bg-[#16776e] px-4 text-sm font-semibold text-white hover:bg-[#125f58]">
                <x-icon name="search" :size="18" /> Search my cases
            </button>
            <a href="{{ route('cases.find') }}" class="inline-flex min-h-11 items-center text-sm font-semibold text-[#16776e] hover:underline">Import cases from the eCourts app &rarr;</a>
        </form>
    @endif

    <nav class="grid gap-3 sm:grid-cols-2" aria-label="{{ $heading }} services">
        @foreach($links as $link)
            <a href="{{ $link['url'] }}" rel="noopener noreferrer"
               class="group flex items-center gap-4 rounded-2xl bg-[#142235] px-5 py-4 text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-[#16776e] hover:shadow-md focus-visible:outline-offset-4">
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white/10 text-[#8ed2c8] group-hover:text-white">
                    <x-icon :name="$link['icon']" :size="24" />
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block text-base font-bold uppercase tracking-wide">{{ $link['title'] }}</span>
                    <span class="mt-0.5 block text-sm text-slate-300 group-hover:text-teal-50">{{ $link['description'] }}</span>
                </span>
                @if($link['internal'] ?? false)
                    <x-icon name="chevron" :size="18" class="-rotate-90 text-slate-400 group-hover:text-white" />
                @else
                    <x-icon name="external" :size="18" class="text-slate-400 group-hover:text-white" />
                @endif
            </a>
        @endforeach
    </nav>

    <p class="text-xs text-slate-500">Official links go to {{ $source }}.</p>
</div>
@endsection
