@extends('layouts.app')

@php
    $isEditing = $case->exists;
    $field = 'mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5';
@endphp

@section('title', $isEditing ? 'Edit case' : 'Add case')
@section('page-heading', $isEditing ? 'Edit case' : 'Add case')

@section('content')
<form method="POST" action="{{ $isEditing ? route('cases.update', $case) : route('cases.store') }}"
      class="mx-auto max-w-4xl space-y-6">
    @csrf
    @if($isEditing) @method('PUT') @endif

    @if($errors->any())
        <div role="alert" class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
            @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif

    <x-panel class="p-5 sm:p-6">
        <h2 class="text-lg font-semibold text-slate-900">Case</h2>
        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <label class="text-sm font-medium text-slate-700 sm:col-span-2">
                Case title <span class="text-red-600">*</span>
                <input name="case_title" value="{{ old('case_title', $case->case_title) }}" required maxlength="255"
                       placeholder="e.g. Sharma vs State" class="{{ $field }}">
            </label>
            <label class="text-sm font-medium text-slate-700">
                Case number
                <input name="case_number" value="{{ old('case_number', $case->case_number) }}" maxlength="255" placeholder="e.g. HMA/2424/2024" class="{{ $field }}">
            </label>
            <label class="text-sm font-medium text-slate-700">
                CNR number
                <input name="cnr_number" value="{{ old('cnr_number', $case->cnr_number) }}" maxlength="255" class="{{ $field }} uppercase">
            </label>
            <label class="text-sm font-medium text-slate-700">
                Case type
                <input name="case_type" value="{{ old('case_type', $case->case_type) }}" maxlength="255" placeholder="e.g. Civil Suit" class="{{ $field }}">
            </label>
            <label class="text-sm font-medium text-slate-700">
                Status <span class="text-red-600">*</span>
                <select name="case_status" required class="{{ $field }}">
                    @foreach(\App\Models\LegalCase::STATUSES as $status)
                        <option value="{{ $status }}" @selected(old('case_status', $case->case_status) === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm font-medium text-slate-700">
                We appear for
                <select name="appearing_for" class="{{ $field }}">
                    <option value="">Not set</option>
                    @foreach(\App\Models\LegalCase::SIDES as $sideValue => $sideLabel)
                        <option value="{{ $sideValue }}" @selected(old('appearing_for', $case->appearing_for) === $sideValue)>{{ $sideLabel }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm font-medium text-slate-700">
                Party no(s). or names <span class="font-normal text-slate-400">(if more than one on that side)</span>
                <input name="appearing_for_parties" value="{{ old('appearing_for_parties', $case->appearing_for_parties) }}" maxlength="255" placeholder="e.g. 2, 3 or 1-4" class="{{ $field }}">
            </label>
            <label class="text-sm font-medium text-slate-700">
                Stage
                <input name="stage" value="{{ old('stage', $case->stage) }}" maxlength="255" placeholder="e.g. Evidence" class="{{ $field }}">
            </label>
        </div>
    </x-panel>

    <x-panel class="p-5 sm:p-6">
        <h2 class="text-lg font-semibold text-slate-900">Court</h2>
        <div class="mt-4">
            @include('cases.partials.court-picker', ['case' => $case, 'field' => $field, 'label' => 'block text-sm font-medium text-slate-700'])
            @error('court_name') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
        </div>
        @php
            $venueFields = ['court_complex', 'court_room', 'vc_link', 'judge_name'];
            $venueOpen = collect($venueFields)->contains(fn ($name) => filled(old($name, $case->{$name})) || $errors->has($name));
        @endphp
        <details class="mt-3" @if($venueOpen) open @endif>
            <summary class="min-h-11 cursor-pointer py-3 text-sm font-semibold text-slate-700">Venue and hearing details</summary>
            <div class="grid gap-4 sm:grid-cols-2">
            <label class="text-sm font-medium text-slate-700">
                Court complex
                <input name="court_complex" value="{{ old('court_complex', $case->court_complex) }}" maxlength="255" class="{{ $field }}">
            </label>
            <label class="text-sm font-medium text-slate-700">
                Court room
                <input name="court_room" value="{{ old('court_room', $case->court_room) }}" maxlength="100" placeholder="e.g. Room 214, Court No. 5" class="{{ $field }}">
            </label>
            <label class="text-sm font-medium text-slate-700">
                VC link <span class="font-normal text-slate-400">(video hearing)</span>
                <input type="url" name="vc_link" value="{{ old('vc_link', $case->vc_link) }}" maxlength="500" placeholder="https://…" inputmode="url" class="{{ $field }}">
            </label>
            <label class="text-sm font-medium text-slate-700">
                Judge
                <input name="judge_name" value="{{ old('judge_name', $case->judge_name) }}" maxlength="255" class="{{ $field }}">
            </label>
            </div>
        </details>
    </x-panel>

    {{-- Rarely needed details stay folded away; they open when one of them already has a value or an error. --}}
    @php
        $moreFields = ['file_number', 'filing_number', 'filing_date', 'disposed_on', 'petitioner_advocate', 'respondent_advocate', 'case_description'];
        $moreOpen = collect($moreFields)->contains(fn ($name) => filled(old($name, $case->{$name})) || $errors->has($name));
    @endphp
    <details class="group rounded-xl border border-slate-200 bg-white shadow-sm" @if($moreOpen) open @endif>
        <summary class="flex min-h-14 cursor-pointer list-none items-center justify-between gap-3 px-5 sm:px-6">
            <span class="font-semibold text-slate-900">More details <span class="font-normal text-slate-500">(filing, advocates, notes)</span></span>
            <x-icon name="chevron" class="text-slate-400 transition group-open:rotate-180" />
        </summary>
        <div class="grid gap-4 px-5 pb-5 sm:grid-cols-2 sm:px-6 sm:pb-6">
            <label class="text-sm font-medium text-slate-700">
                File No. <span class="font-normal text-slate-400">(your office)</span>
                <input name="file_number" value="{{ old('file_number', $case->file_number) }}" maxlength="100" class="{{ $field }}">
            </label>
            <label class="text-sm font-medium text-slate-700">
                Filing number
                <input name="filing_number" value="{{ old('filing_number', $case->filing_number) }}" maxlength="255" class="{{ $field }}">
            </label>
            <label class="text-sm font-medium text-slate-700">
                Filing date
                <input type="date" name="filing_date" value="{{ old('filing_date', $case->filing_date?->format('Y-m-d')) }}" class="{{ $field }}">
            </label>
            <label class="text-sm font-medium text-slate-700">
                Disposed on
                <input type="date" name="disposed_on" value="{{ old('disposed_on', $case->disposed_on?->format('Y-m-d')) }}" class="{{ $field }}">
            </label>
            <label class="text-sm font-medium text-slate-700">
                Petitioner's advocate
                <input name="petitioner_advocate" value="{{ old('petitioner_advocate', $case->petitioner_advocate) }}" maxlength="255" class="{{ $field }}">
            </label>
            <label class="text-sm font-medium text-slate-700">
                Respondent's advocate
                <input name="respondent_advocate" value="{{ old('respondent_advocate', $case->respondent_advocate) }}" maxlength="255" class="{{ $field }}">
            </label>
            <label class="text-sm font-medium text-slate-700 sm:col-span-2">
                Description
                <textarea name="case_description" rows="3" class="{{ $field }}">{{ old('case_description', $case->case_description) }}</textarea>
            </label>
        </div>
    </details>

    <div class="flex justify-end gap-3">
        <a href="{{ $isEditing ? route('cases.show', $case) : route('cases.index') }}" class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 font-semibold text-slate-700 hover:bg-slate-50">Cancel</a>
        <button type="submit" class="rounded-xl bg-[#238b80] px-6 py-2.5 font-semibold text-white hover:bg-[#16776e]">
            {{ $isEditing ? 'Save changes' : 'Add case' }}
        </button>
    </div>
</form>
@endsection
