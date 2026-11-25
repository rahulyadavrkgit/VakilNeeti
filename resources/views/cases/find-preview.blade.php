@extends('layouts.app')

@section('title', 'Choose cases to add')
@section('page-heading', 'Choose cases to add')

@php
    $newCount = collect($rows)->where('already_added', false)->count();
    $cell = 'w-full rounded-md border border-slate-300 bg-white px-2 py-1.5 text-sm';
@endphp

@section('content')
<form method="POST" action="{{ route('cases.find.import') }}" id="find-cases-form" data-bulk-form class="mx-auto max-w-6xl space-y-4">
    @csrf
    @if($retry ?? null)<input type="hidden" name="retry" value="{{ $retry }}">@endif

    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-slate-600">
            Found <span class="font-semibold text-slate-900">{{ count($rows) }}</span> {{ \Illuminate\Support\Str::plural('case', count($rows)) }}
            in your {{ $portal['label'] }} results{{ $newCount < count($rows) ? ', '.(count($rows) - $newCount).' already in your cases' : '' }}.
            Check the titles and courts, then add.
        </p>
        <a href="{{ route('cases.find') }}" class="text-sm font-semibold text-[#16776e] hover:underline">&larr; Paste again</a>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="hidden grid-cols-[2.5rem_1.2fr_2fr_1.6fr] gap-3 border-b border-slate-200 bg-slate-50 px-4 py-2 text-xs font-bold uppercase text-slate-500 md:grid">
            <label class="flex items-center"><input type="checkbox" data-select-all="find-cases-form" @checked($newCount > 0) aria-label="Select all new cases" class="rounded border-slate-300 text-[#16776e]"></label>
            <span>Case number</span>
            <span>Title</span>
            <span>Court</span>
        </div>

        @foreach($rows as $index => $row)
            <div class="grid gap-2 border-b border-slate-100 px-4 py-3 last:border-0 md:grid-cols-[2.5rem_1.2fr_2fr_1.6fr] md:items-center md:gap-3 {{ $row['already_added'] ? 'bg-slate-50 opacity-70' : '' }}">
                <label class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                    @if($row['already_added'])
                        <span class="text-xs text-slate-500 md:hidden">Already added</span>
                        <span class="hidden text-slate-400 md:inline" title="Already in your cases">✓</span>
                    @else
                        <input type="hidden" name="cases[{{ $index }}][selected]" value="0">
                        <input type="checkbox" name="cases[{{ $index }}][selected]" value="1" checked data-select-item class="rounded border-slate-300 text-[#16776e]">
                        <span class="md:hidden">Add this case</span>
                    @endif
                </label>

                <div>
                    <p class="font-mono text-sm font-semibold text-slate-900">{{ $row['full_number'] }}</p>
                    @if($row['cnr'])<p class="font-mono text-xs text-slate-500">CNR {{ $row['cnr'] }}</p>@endif
                </div>

                @if($row['already_added'])
                    <p class="text-sm text-slate-700">{{ $row['title'] }}</p>
                    <p class="text-sm text-slate-500">{{ $row['court'] ?: '—' }} · already in your cases</p>
                @else
                    <label class="text-sm">
                        <span class="sr-only">Title for {{ $row['full_number'] }}</span>
                        <input name="cases[{{ $index }}][case_title]" value="{{ $row['title'] }}" maxlength="255" required class="{{ $cell }}">
                    </label>
                    <label class="text-sm">
                        <span class="sr-only">Court for {{ $row['full_number'] }}</span>
                        <input name="cases[{{ $index }}][court_name]" value="{{ $row['court'] ?? $defaultCourt }}" maxlength="255" placeholder="Court name" class="{{ $cell }}">
                    </label>
                    <input type="hidden" name="cases[{{ $index }}][case_number]" value="{{ $row['full_number'] }}">
                    <input type="hidden" name="cases[{{ $index }}][case_type]" value="{{ $row['case_type'] }}">
                    <input type="hidden" name="cases[{{ $index }}][cnr_number]" value="{{ $row['cnr'] }}">
                @endif
            </div>
        @endforeach
    </div>

    <div class="flex flex-wrap items-center justify-end gap-3">
        <a href="{{ route('cases.find') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</a>
        @if($newCount)
            <button type="submit" data-bulk-submit data-bulk-label="Add selected cases" class="rounded-lg bg-[#16776e] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#125f58] disabled:opacity-50">Add selected cases</button>
        @endif
    </div>
</form>
@endsection
