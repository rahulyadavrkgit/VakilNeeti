@extends('layouts.app')

@php
    $isEditing = $hearing->exists;
    $field = 'mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5';
    $hearingDate = $hearing->hearing_date?->toDateString();
    // A next date equal to the hearing date was filled in automatically, so leave the box empty.
    $nextDate = $hearing->next_hearing_date?->toDateString() !== $hearingDate ? $hearing->next_hearing_date?->toDateString() : null;
    $hearingTime = $hearing->hearing_time ? substr((string) $hearing->hearing_time, 0, 5) : null;
@endphp

@section('title', $isEditing ? 'Edit hearing' : 'Add hearing')
@section('page-heading', $isEditing ? 'Edit hearing' : 'Add hearing')

@section('content')
<form method="POST" action="{{ $isEditing ? route('hearings.update', $hearing) : route('hearings.store') }}" class="mx-auto max-w-3xl space-y-6">
    @csrf
    @if($isEditing) @method('PUT') @endif
    <input type="hidden" name="return" value="{{ old('return', $returnTo) }}">

    @if($errors->any())
        <div role="alert" class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
            @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif

    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <h2 class="text-lg font-bold text-slate-900">Hearing</h2>

        @if($cases->isEmpty())
            <p class="mt-3 text-sm text-slate-600">Add a case first, then you can add its hearings.</p>
            <a href="{{ route('cases.manual') }}" class="mt-3 inline-flex rounded-lg bg-[#16776e] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#125f58]">+ Add case</a>
        @else
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <label class="text-sm font-medium text-slate-700 sm:col-span-2">
                    Case <span class="text-red-600">*</span>
                    <select name="case_id" required class="{{ $field }}">
                        <option value="">Choose a case</option>
                        @foreach($cases as $case)
                            <option value="{{ $case->id }}" @selected((int) old('case_id', $hearing->case_id) === $case->id)>
                                {{ $case->case_title }}{{ $case->case_number ? ' · '.$case->case_number : '' }}{{ $case->court_name ? ' · '.$case->court_name : '' }}
                            </option>
                        @endforeach
                    </select>
                </label>
                <label class="text-sm font-medium text-slate-700">
                    Hearing date <span class="text-red-600">*</span>
                    <input type="date" name="hearing_date" value="{{ old('hearing_date', $hearingDate) }}" required class="{{ $field }}">
                </label>
                <label class="text-sm font-medium text-slate-700">
                    Time
                    <input type="time" name="hearing_time" value="{{ old('hearing_time', $hearingTime) }}" class="{{ $field }}">
                </label>
                <label class="text-sm font-medium text-slate-700">
                    Purpose / stage
                    <input name="hearing_stage" value="{{ old('hearing_stage', $hearing->hearing_stage) }}" maxlength="255" placeholder="e.g. Arguments" class="{{ $field }}">
                </label>
                <label class="text-sm font-medium text-slate-700">
                    Court room
                    <input name="court_room" value="{{ old('court_room', $hearing->court_room) }}" maxlength="100" placeholder="e.g. Court No. 12" class="{{ $field }}">
                </label>
                <label class="text-sm font-medium text-slate-700 sm:col-span-2">
                    Judge
                    <input name="judge_name" value="{{ old('judge_name', $hearing->judge_name) }}" maxlength="255" class="{{ $field }}">
                </label>
            </div>
        @endif
    </section>

    @if($cases->isNotEmpty())
        <details class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6" @if(old('proceedings', $hearing->proceedings) || old('next_hearing_date', $nextDate)) open @endif>
            <summary class="cursor-pointer text-lg font-bold text-slate-900">Already happened? Add the outcome</summary>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <label class="text-sm font-medium text-slate-700 sm:col-span-2">
                    What happened
                    <textarea name="proceedings" rows="3" maxlength="5000" class="{{ $field }}">{{ old('proceedings', $hearing->proceedings) }}</textarea>
                </label>
                <label class="text-sm font-medium text-slate-700">
                    Next date
                    <input type="date" name="next_hearing_date" value="{{ old('next_hearing_date', $nextDate) }}" class="{{ $field }}">
                </label>
                <label class="text-sm font-medium text-slate-700">
                    Next step
                    <input name="next_action" value="{{ old('next_action', $hearing->next_action) }}" maxlength="255" placeholder="e.g. File reply" class="{{ $field }}">
                </label>
            </div>
        </details>

        <div class="flex flex-wrap justify-end gap-3">
            <a href="{{ $isEditing ? route('cases.show', $hearing->case_id) : (url()->previous() !== url()->current() ? url()->previous() : route('cases.calendar')) }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</a>
            <button type="submit" class="rounded-lg bg-[#16776e] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#125f58]">{{ $isEditing ? 'Save changes' : 'Save hearing' }}</button>
        </div>
    @endif
</form>

@if($isEditing)
    <form method="POST" action="{{ route('hearings.destroy', $hearing) }}" data-confirm="Delete this hearing? This cannot be undone." class="mx-auto mt-4 flex max-w-3xl justify-end">
        @csrf
        @method('DELETE')
        <button type="submit" class="text-sm font-semibold text-red-700 hover:underline">Delete hearing</button>
    </form>
@endif
@endsection
