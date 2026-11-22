@extends('layouts.app')

@section('title', 'Supreme Court')
@section('page-heading', 'Supreme Court')

@php
    $icons = [
        'list' => '<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>',
        'board' => '<rect x="3" y="4" width="18" height="12" rx="2"/><path d="M8 20h8M12 16v4"/>',
        'screen' => '<rect x="2" y="3" width="20" height="14" rx="2"/><path d="M7 8h4M7 12h10"/>',
        'pass' => '<rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="12" r="2"/><path d="M14 10h4M14 14h3"/>',
        'calendar' => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
    ];
@endphp

@section('content')
<div class="mx-auto max-w-4xl space-y-5">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Supreme Court of India</h1>
            <p class="mt-1 text-sm text-slate-600">Official Supreme Court services. Each opens the court's own website in a new tab.</p>
        </div>
        <a href="{{ route('court-directory.display-boards') }}" class="text-sm font-semibold text-[#16776e] hover:underline">All court display boards &rarr;</a>
    </div>

    <nav class="grid gap-3 sm:grid-cols-2" aria-label="Supreme Court services">
        @foreach($links as $link)
            <a href="{{ $link['url'] }}" target="_blank" rel="noopener noreferrer" data-direct-share
               class="group flex items-center gap-4 rounded-2xl bg-[#142235] px-5 py-4 text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-[#16776e] hover:shadow-md focus-visible:outline-offset-4">
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white/10 text-[#8ed2c8] group-hover:text-white">
                    <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $icons[$link['icon']] !!}</svg>
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block text-base font-bold uppercase tracking-wide">{{ $link['title'] }}</span>
                    <span class="mt-0.5 block text-sm text-slate-300 group-hover:text-teal-50">{{ $link['description'] }}</span>
                </span>
                <span class="text-lg text-slate-400 group-hover:text-white" aria-hidden="true">&nearr;</span>
            </a>
        @endforeach
    </nav>

    <p class="text-xs text-slate-500">
        Links go to sci.gov.in and suswagatam.sci.gov.in. VakilNiti does not copy or store anything from these pages.
        For case details, see the <a href="{{ route('court-directory.index', ['level' => 'supreme']) }}" class="font-semibold text-[#16776e] hover:underline">Supreme Court directory</a>.
    </p>
</div>
@endsection
