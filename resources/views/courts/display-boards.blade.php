@extends('layouts.app')

@section('title', 'Court display boards')
@section('page-heading', 'Display boards')
@section('meta_description', 'Live court display boards in one place: Supreme Court of India, Delhi High Court and Allahabad High Court. See which item is running in each courtroom.')

@section('content')
<div class="mx-auto max-w-5xl space-y-5">
    @include('courts.partials.breadcrumb', ['current' => 'Display boards'])

    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Court display boards</h1>
            <p class="mt-1 text-sm text-slate-600">See which item is running in each courtroom, live from the court's official board. Each opens in a new tab.</p>
        </div>
        <label class="flex w-full items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 shadow-sm focus-within:border-[#16776e] sm:w-72">
            <x-icon name="search" class="text-slate-400" />
            <span class="sr-only">Search courts</span>
            <input type="search" data-filter-list="display-boards" placeholder="Search courts" class="min-h-11 w-full border-0 bg-transparent text-sm focus:ring-0">
        </label>
    </div>

    <div id="display-boards" class="grid gap-4 md:grid-cols-2">
        @foreach($courts as $court)
            <section data-filter-item class="flex flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <h2 class="text-lg font-semibold text-slate-900">{{ $court['court'] }}</h2>
                    <span class="shrink-0 rounded-full bg-teal-50 px-2.5 py-0.5 text-xs font-semibold text-teal-800">{{ $court['level'] }}</span>
                </div>
                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach($court['boards'] as $board)
                        <a href="{{ $board['url'] }}" target="_blank" rel="noopener noreferrer" data-direct-share
                           class="inline-flex min-h-11 items-center gap-2 rounded-lg {{ $loop->first ? 'bg-[#16776e] text-white hover:bg-[#125f58]' : 'border border-[#16776e] text-[#16776e] hover:bg-teal-50' }} px-4 text-sm font-semibold">
                            {{ $board['title'] }} <x-icon name="external" :size="16" />
                        </a>
                    @endforeach
                </div>
                <p class="mt-3 truncate text-xs text-slate-400">{{ parse_url($court['boards'][0]['url'], PHP_URL_HOST) }}</p>
            </section>
        @endforeach
        <p data-filter-empty hidden class="rounded-2xl border border-slate-200 bg-white p-5 text-sm text-slate-500 md:col-span-2">No court matches your search.</p>
    </div>

    <p class="text-xs text-slate-500">
        Only boards published on the courts' own websites are listed. For a District court's daily list, use
        <a href="{{ route('court-integrations.ecourts.search') }}" class="font-semibold text-[#16776e] hover:underline">Official Court Search</a>.
    </p>
</div>
@endsection
