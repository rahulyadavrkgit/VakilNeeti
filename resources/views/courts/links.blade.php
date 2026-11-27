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
            @if($intro)<p class="mt-1 text-sm text-slate-600">{{ $intro }}</p>@endif
        </div>
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

    {{-- Simple outlined cards, in two groups: what opens in VakilNiti, then the court's own website. --}}
    @foreach(collect($links)->groupBy(fn (array $link): string => ($link['internal'] ?? false) ? 'In VakilNiti' : 'On the court\'s website') as $group => $groupLinks)
        <section class="space-y-2">
            <h2 class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ $group }}</h2>
            <nav class="grid gap-2 sm:grid-cols-2" aria-label="{{ $heading }} · {{ $group }}">
                @foreach($groupLinks as $link)
                    <a href="{{ $link['url'] }}" rel="noopener noreferrer"
                       class="group flex min-h-16 items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 transition hover:border-teal-400 focus-visible:border-teal-500">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-teal-50 text-[#16776e]">
                            <x-icon :name="$link['icon']" :size="20" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block font-semibold text-slate-900 group-hover:text-[#16776e]">{{ $link['title'] }}</span>
                            <span class="block text-sm text-slate-600">{{ $link['description'] }}</span>
                        </span>
                        @if($link['internal'] ?? false)
                            <x-icon name="chevron" :size="18" class="shrink-0 -rotate-90 text-slate-400 group-hover:text-[#16776e]" />
                        @else
                            <x-icon name="external" :size="16" class="shrink-0 text-slate-400 group-hover:text-[#16776e]" />
                        @endif
                    </a>
                @endforeach
            </nav>
        </section>
    @endforeach

    <p class="text-xs text-slate-500">Links go to {{ $source }}.</p>
</div>
@endsection
