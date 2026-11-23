@props(['caseId', 'return' => 'back', 'open' => false, 'label' => 'Add next date'])

{{-- A small "Add next date" form that saves a hearing without leaving the page (date, optional time and purpose). --}}
<details {{ $attributes->merge(['class' => 'group']) }} @if($open) open @endif>
    <summary class="inline-flex min-h-9 cursor-pointer list-none items-center gap-1.5 rounded-lg border border-[#16776e] bg-white px-3 text-sm font-semibold text-[#16776e] hover:bg-teal-50">
        <x-icon name="calendar" :size="16" /> {{ $label }}
    </summary>
    <form method="POST" action="{{ route('hearings.store') }}" class="mt-2 grid gap-2 rounded-xl border border-teal-200 bg-teal-50/60 p-3 sm:grid-cols-[minmax(0,1fr)_7rem_minmax(0,1.5fr)_auto] sm:items-end">
        @csrf
        <input type="hidden" name="case_id" value="{{ $caseId }}">
        <input type="hidden" name="return" value="{{ $return }}">
        <label class="block text-xs font-semibold text-slate-600">
            Date
            <input type="date" name="hearing_date" required min="{{ today()->toDateString() }}" class="mt-1 w-full rounded-lg border border-slate-300 px-2.5 py-2 text-sm">
        </label>
        <label class="block text-xs font-semibold text-slate-600">
            Time
            <input type="time" name="hearing_time" class="mt-1 w-full rounded-lg border border-slate-300 px-2.5 py-2 text-sm">
        </label>
        <label class="block text-xs font-semibold text-slate-600">
            Purpose
            <input type="text" name="hearing_stage" maxlength="255" placeholder="e.g. Arguments, Evidence" class="mt-1 w-full rounded-lg border border-slate-300 px-2.5 py-2 text-sm">
        </label>
        <button type="submit" class="min-h-10 rounded-lg bg-[#16776e] px-4 text-sm font-semibold text-white hover:bg-[#125f58]">Save date</button>
    </form>
</details>
