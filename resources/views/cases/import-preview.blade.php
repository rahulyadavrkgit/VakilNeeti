@extends('layouts.app')

@section('title', 'Review Official Court Record')
@section('page-heading', 'Review Court Record')

@section('content')
<div class="max-w-6xl mx-auto py-8 px-4 space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-slate-900">Review Official Court Record</h1>
        <p class="mt-2 text-sm text-slate-600">Check and correct the details. Only confirmed details are saved.</p>
    </div>

    @if ($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-red-700">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <div class="rounded-xl border border-slate-200 bg-white p-5">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <p class="text-xs font-medium uppercase text-slate-500">Official source category</p>
                <p class="mt-1 font-medium text-slate-900">{{ $courtSource }}</p>
            </div>
            <div>
                <p class="text-xs font-medium uppercase text-slate-500">Import reference</p>
                <p class="mt-1 font-medium text-slate-900">#{{ $import->id }}</p>
            </div>
        </div>
        @if ($sourceUrl)
            <a href="{{ $sourceUrl }}" target="_blank" rel="noopener noreferrer"
               class="mt-3 inline-block text-sm text-[#16776e] hover:underline">
                Open original official record
            </a>
        @endif
    </div>

    <form method="POST" action="{{ route('cases.import.save', $import) }}"
          class="space-y-6">
        @csrf

        <section class="rounded-xl border border-slate-200 bg-white p-5">
            <h2 class="mb-4 text-lg font-semibold text-slate-900">Case Information</h2>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                @foreach ([
                    'case_title' => 'Case Title',
                    'case_number' => 'Case Number',
                    'cnr_number' => 'CNR Number',
                    'case_type' => 'Case Type',
                    'court_name' => 'Court Name',
                    'court_complex' => 'Court Complex / Establishment',
                    'filing_number' => 'Filing Number',
                    'case_status' => 'Case Status',
                    'judge_name' => 'Judge / Coram',
                ] as $field => $label)
                    <div class="{{ in_array($field, ['case_title', 'court_name']) ? 'md:col-span-2' : '' }}">
                        <label for="{{ $field }}" class="mb-2 block text-sm font-medium text-slate-700">
                            {{ $label }}
                        </label>
                        <input
                            id="{{ $field }}"
                            type="text"
                            name="{{ $field }}"
                            value="{{ old($field, $field === 'court_name' ? ($extracted[$field] ?? $courtSource) : ($extracted[$field] ?? '')) }}"
                            {{ $field === 'case_title' ? 'required' : '' }}
                            maxlength="255"
                            class="w-full rounded-lg border-slate-300 focus:border-[#16776e] focus:ring-[#16776e]">
                    </div>
                @endforeach

                <div>
                    <label for="filing_date" class="mb-2 block text-sm font-medium text-slate-700">Filing Date</label>
                    <input id="filing_date" type="date" name="filing_date"
                           value="{{ old('filing_date', $extracted['filing_date'] ?? '') }}"
                           class="w-full rounded-lg border-slate-300">
                </div>
            </div>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-5">
            <h2 class="mb-4 text-lg font-semibold text-slate-900">Parties and Advocate</h2>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                @foreach ([
                    'petitioner' => 'Petitioner / Plaintiff / Applicant',
                    'respondent' => 'Respondent / Defendant',
                    'advocate' => 'Advocate / Counsel',
                ] as $field => $label)
                    <div class="{{ $field === 'advocate' ? 'md:col-span-2' : '' }}">
                        <label for="{{ $field }}" class="mb-2 block text-sm font-medium text-slate-700">
                            {{ $label }}
                        </label>
                        <input id="{{ $field }}" type="text" name="{{ $field }}"
                               value="{{ old($field, $extracted[$field] ?? '') }}"
                               maxlength="1000"
                               class="w-full rounded-lg border-slate-300">
                    </div>
                @endforeach
            </div>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-5">
            <h2 class="mb-4 text-lg font-semibold text-slate-900">Hearing Information</h2>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label for="hearing_date" class="mb-2 block text-sm font-medium text-slate-700">
                        Previous / Actual Hearing Date
                    </label>
                    <input id="hearing_date" type="date" name="hearing_date"
                           value="{{ old('hearing_date', $extracted['hearing_date'] ?? '') }}"
                           class="w-full rounded-lg border-slate-300">
                </div>

                <div>
                    <label for="next_hearing_date" class="mb-2 block text-sm font-medium text-slate-700">
                        Next Hearing Date
                    </label>
                    <input id="next_hearing_date" type="date" name="next_hearing_date"
                           value="{{ old('next_hearing_date', $extracted['next_hearing_date'] ?? '') }}"
                           class="w-full rounded-lg border-slate-300">
                </div>

                @foreach ([
                    'hearing_time' => 'Hearing Time',
                    'court_room' => 'Court Room / Hall',
                    'hearing_stage' => 'Hearing Stage',
                ] as $field => $label)
                    <div>
                        <label for="{{ $field }}" class="mb-2 block text-sm font-medium text-slate-700">
                            {{ $label }}
                        </label>
                        <input id="{{ $field }}" type="text" name="{{ $field }}"
                               value="{{ old($field, $extracted[$field] ?? '') }}"
                               maxlength="255"
                               class="w-full rounded-lg border-slate-300">
                    </div>
                @endforeach

                <div class="md:col-span-2">
                    <label for="proceedings" class="mb-2 block text-sm font-medium text-slate-700">
                        Proceedings / Last Order Summary
                    </label>
                    <textarea id="proceedings" name="proceedings" rows="4"
                              maxlength="10000"
                              class="w-full rounded-lg border-slate-300">{{ old('proceedings', $extracted['proceedings'] ?? '') }}</textarea>
                </div>
            </div>

            <p class="mt-3 text-xs text-slate-500">You can save a next hearing date without the previous one. Don’t enter estimated dates.</p>
        </section>

        <div class="flex flex-wrap gap-3">
            <button type="submit"
                    data-confirm="Add this reviewed official record to My Case Diary?"
                    class="rounded-lg bg-[#16776e] px-5 py-3 font-medium text-white hover:bg-[#125f58]">
                Add to My Case Diary
            </button>

            <a href="{{ route('cases.import') }}"
               class="rounded-lg border border-slate-300 px-5 py-3 hover:bg-slate-50">
                Cancel / New Import
            </a>

            <a href="{{ route('cases.import.history') }}"
               class="rounded-lg border border-slate-300 px-5 py-3 hover:bg-slate-50">
                Import History
            </a>
        </div>
    </form>

    <section class="rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="mb-3 font-semibold text-slate-900">Original Official Record</h2>
        <pre class="whitespace-pre-wrap break-words rounded-lg bg-slate-50 p-4 text-sm text-slate-700">{{ $officialRecord }}</pre>
    </section>

</div>
@endsection