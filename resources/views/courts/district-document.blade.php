@extends('layouts.app')

@section('title', $label.' · '.$district['district'].' District, Delhi')
@section('page-heading', $label)
@section('meta_description', 'The latest '.strtolower($label).' of '.$district['district'].' District, Delhi ('.$district['complex'].'), from the court\'s own website.')
@section('back-url', $backUrl)

@php
    $pdfUrl = route('courts.delhi-district-courts.document.pdf', [$slug, $kind]);
    $shareText = $found
        ? collect(['*'.$label.' · '.$district['district'].' District, Delhi*', $found['title'], $found['date'] ? 'Uploaded '.\Illuminate\Support\Carbon::parse($found['date'])->format('d M Y') : null, 'PDF: '.$found['url']])->filter()->implode("\n")
        : null;
@endphp

@section('content')
{{-- The district's latest roster, read here: back to the complex, share or download, and the court's own list for older ones. --}}
<div class="mx-auto max-w-4xl space-y-3">
    <div class="flex flex-wrap items-center gap-2">
        <x-back-link :fallback="$backUrl" />
        <span class="text-sm text-slate-500">{{ $district['complex'] }} · {{ $district['district'] }}</span>
    </div>

    <section class="rounded-xl border border-slate-200 bg-white p-4">
        <h1 class="text-xl font-semibold text-slate-900">{{ $label }} · {{ $district['district'] }}</h1>
        @if($found)
            <p class="mt-1 text-sm text-slate-700">{{ $found['title'] }}</p>
            @if($found['date'])
                <p class="mt-0.5 text-xs text-slate-500">Uploaded {{ \Illuminate\Support\Carbon::parse($found['date'])->format('d M Y') }} on the court's website</p>
            @endif
            <div class="mt-3 flex flex-wrap items-center gap-2">
                <a href="https://wa.me/?text={{ rawurlencode($shareText) }}" target="_blank" rel="noopener noreferrer" data-direct-share data-share-now
                   data-share-text="{{ $shareText }}" data-share-title="{{ $label }} · {{ $district['district'] }}"
                   class="vn-primary-button inline-flex min-h-11 items-center gap-1.5 rounded-lg px-4 text-sm font-semibold">
                    <x-icon name="share" :size="16" /> Share
                </a>
                <a href="{{ $pdfUrl }}" download class="inline-flex min-h-11 items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-800 hover:border-teal-500">
                    <x-icon name="download" :size="16" /> Download
                </a>
                <a href="{{ $listPage }}" target="_blank" rel="noopener noreferrer" class="vn-link ml-auto inline-flex min-h-11 items-center text-sm font-semibold">Older rosters on the court's site</a>
            </div>
        @else
            <p class="mt-2 text-sm text-slate-600">The court's website is not answering just now, or has no {{ strtolower($label) }} listed.</p>
            <a href="{{ $listPage }}" target="_blank" rel="noopener noreferrer" class="vn-link mt-2 inline-flex min-h-11 items-center text-sm font-semibold">Open the court's page</a>
        @endif
    </section>

    @if($found)
        {{-- The PDF drawn page by page (resources/js/pdf-viewer.js), so it reads the same on every phone. --}}
        <section data-pdf-viewer data-src="{{ $pdfUrl }}" aria-label="{{ $found['title'] }}" class="space-y-2">
            <p data-pdf-status class="rounded-xl border border-slate-200 bg-white p-4 text-center text-sm text-slate-500">Opening the PDF…</p>
            <div data-pdf-pages class="space-y-2"></div>
            <p data-pdf-failed hidden class="rounded-xl border border-slate-200 bg-white p-4 text-center text-sm text-slate-600">
                The PDF could not be shown here. <a href="{{ $found['url'] }}" target="_blank" rel="noopener noreferrer" class="vn-link font-semibold">Open it on the court's website</a>.
            </p>
        </section>
    @endif
</div>
@endsection
