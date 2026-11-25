@extends('layouts.app')

@section('title', 'High Courts of India')
@section('page-heading', 'High Courts')
@section('meta_description', 'All 25 High Courts of India with their seat, the States they cover and official website, plus case status, cause lists and e-filing for every High Court.')

@section('content')
<div class="mx-auto max-w-5xl space-y-5">
    <x-page-header title="High Courts" subtitle="All 25 High Courts and their official websites." />

    <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
        <a href="{{ $caseStatus }}" target="_blank" rel="noopener noreferrer" class="flex min-h-12 items-center justify-center gap-2 rounded-2xl bg-[#16776e] px-4 text-sm font-semibold text-white hover:bg-[#125f58]">
            <x-icon name="search" :size="18" /> Case status &amp; cause lists
        </a>
        <a href="{{ $efiling }}" target="_blank" rel="noopener noreferrer" class="flex min-h-12 items-center justify-center gap-2 rounded-2xl border border-[#16776e] px-4 text-sm font-semibold text-[#16776e] hover:bg-teal-50">
            <x-icon name="upload" :size="18" /> e-Filing
        </a>
        <a href="{{ route('courts.display-boards') }}" class="flex min-h-12 items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-800 hover:border-[#16776e]">
            <x-icon name="board" :size="18" /> High Court display boards
        </a>
    </div>

    <label class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 shadow-sm focus-within:border-[#16776e] sm:max-w-sm">
        <x-icon name="search" :size="16" class="text-slate-400" />
        <span class="sr-only">Search High Courts</span>
        <input type="search" data-filter-list="high-court-list" placeholder="Search by court, State or city" class="min-h-11 w-full border-0 bg-transparent px-0 text-sm focus:ring-0">
    </label>

    <ul id="high-court-list" class="grid gap-2 sm:grid-cols-2">
        @foreach($courts as $court)
            <li data-filter-item class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                <div class="min-w-0">
                    <h2 class="text-lg font-semibold text-slate-900">{{ $court['name'] }}</h2>
                    <p class="text-sm text-slate-600">{{ $court['seat'] }} · {{ $court['covers'] }}</p>
                </div>
                @if($court['seat'] === 'New Delhi')
                    <a href="{{ route('courts.delhi-high-court') }}" class="inline-flex min-h-10 shrink-0 items-center rounded-xl bg-[#16776e] px-3 text-sm font-semibold text-white hover:bg-[#125f58]">Open</a>
                @else
                    <a href="{{ $court['url'] }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-10 shrink-0 items-center rounded-xl border border-[#16776e] px-3 text-sm font-semibold text-[#16776e] hover:bg-teal-50">Website</a>
                @endif
            </li>
        @endforeach
        <li data-filter-empty hidden class="col-span-full rounded-2xl border border-slate-200 bg-white p-4 text-sm text-slate-500">No High Court matches.</li>
    </ul>
</div>
@endsection
