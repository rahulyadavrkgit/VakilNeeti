@extends('layouts.app')

@section('title', 'Display boards')
@section('page-heading', 'Display boards')

@section('content')
<div class="mx-auto max-w-5xl space-y-5">
    <div>
        <h1 class="text-2xl font-semibold text-slate-900">Court display boards</h1>
        <p class="mt-1 text-sm text-slate-600">See which item is running in each courtroom, live from the court's official board. Each opens in a new tab.</p>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        @foreach($courts as $court)
            <section class="flex flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <h2 class="text-lg font-semibold text-slate-900">{{ $court['court'] }}</h2>
                    <span class="shrink-0 rounded-full bg-teal-50 px-2.5 py-0.5 text-xs font-semibold text-teal-800">{{ $court['level'] }}</span>
                </div>
                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach($court['boards'] as $board)
                        <a href="{{ $board['url'] }}" target="_blank" rel="noopener noreferrer" data-direct-share
                           class="inline-flex min-h-11 items-center gap-2 rounded-lg {{ $loop->first ? 'bg-[#16776e] text-white hover:bg-[#125f58]' : 'border border-[#16776e] text-[#16776e] hover:bg-teal-50' }} px-4 text-sm font-semibold">
                            {{ $board['title'] }} <span aria-hidden="true">&nearr;</span>
                        </a>
                    @endforeach
                </div>
                <p class="mt-3 truncate text-xs text-slate-400">{{ parse_url($court['boards'][0]['url'], PHP_URL_HOST) }}</p>
            </section>
        @endforeach
    </div>

    <p class="text-xs text-slate-500">
        Only boards published on the courts' own websites are listed. For a District court's daily list, use
        <a href="{{ route('court-integrations.ecourts.search') }}" class="font-semibold text-[#16776e] hover:underline">Official Court Search</a>.
    </p>
</div>
@endsection
