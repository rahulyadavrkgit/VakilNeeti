{{-- Pop-up: add the next date to a case. Saves through hearings.store, with the same checks as the full form. --}}
@php
    $field = 'mt-1 w-full rounded-xl border border-slate-300 bg-transparent px-3 py-2.5 text-base sm:text-sm';
    $label = 'block text-sm font-semibold text-slate-700';
@endphp
<h2 data-sheet-heading class="text-lg font-semibold text-slate-900">Add next date</h2>
@if($case)
    <p class="mt-0.5 text-sm text-slate-600">{{ $case->case_title }}@if($case->case_number) · {{ $case->case_number }}@endif</p>
@endif

<form method="POST" action="{{ route('hearings.store') }}" data-sheet-form data-sheet-success="Next date saved" class="mt-4 space-y-3">
    @csrf
    <input type="hidden" name="return" value="back">
    @if($case)
        <input type="hidden" name="case_id" value="{{ $case->id }}">
    @else
        <label class="{{ $label }}">
            Case
            <select name="case_id" required autofocus class="{{ $field }}">
                <option value="">Choose a case</option>
                @foreach($cases as $option)
                    <option value="{{ $option->id }}">{{ $option->case_title }}{{ $option->case_number ? ' · '.$option->case_number : '' }}</option>
                @endforeach
            </select>
        </label>
    @endif
    <div class="grid grid-cols-[minmax(0,1fr)_8rem] gap-3">
        <label class="{{ $label }}">
            Date
            <input type="date" name="hearing_date" required min="{{ today()->toDateString() }}" @if($case) autofocus @endif class="{{ $field }}">
        </label>
        <label class="{{ $label }}">
            Time
            <input type="time" name="hearing_time" class="{{ $field }}">
        </label>
    </div>
    <label class="{{ $label }}">
        Purpose
        <input type="text" name="hearing_stage" maxlength="255" placeholder="Arguments, Evidence, Orders…" class="{{ $field }}">
    </label>
    <p class="text-xs text-slate-500">Room, judge and VC link come from the case and the official court list.</p>
    <div class="flex items-center justify-between gap-3 pt-1">
        <a href="{{ route('hearings.create', array_filter(['case' => $case?->id])) }}" class="text-sm font-semibold text-[#16776e] hover:underline">More details</a>
        <button type="submit" class="min-h-11 rounded-xl bg-[#16776e] px-5 text-sm font-semibold text-white hover:bg-[#125f58]">Save date</button>
    </div>
</form>
