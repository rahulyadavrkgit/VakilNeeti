@props([
    'profile' => null,
    'stateGroups' => [],
    'required' => true,
    'studentOptional' => false,
])

@php
    $field = 'mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5';
    $selectedState = old('bar_state', $profile?->bar_state);
    $selectedYear = (int) old('bar_registration_year', $profile?->bar_registration_year);
@endphp

<div data-bar-registration class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[2fr_1fr_1.4fr_1fr]">
    <label class="text-sm font-medium text-slate-700 sm:col-span-2 lg:col-span-1">
        Bar Council of <span class="text-red-600" @if($studentOptional) data-student-bar-required @endif @unless($required) hidden @endunless>*</span>
        <select name="bar_state" data-bar-state data-bar-codes="{{ json_encode(\App\Models\State::BAR_COUNCIL_CODES) }}"
                @if($required) required @endif @if($studentOptional) data-student-optional @endif class="{{ $field }}">
            <option value="">Choose State</option>
            <x-state-options :groups="$stateGroups" :selected="$selectedState" />
        </select>
        @error('bar_state') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
    </label>

    <label class="text-sm font-medium text-slate-700">
        State code
        <input name="bar_state_code" value="{{ old('bar_state_code', $profile?->bar_state_code) }}" data-bar-code maxlength="10"
               placeholder="e.g. UP" autocapitalize="characters" @if($required) required @endif @if($studentOptional) data-student-optional @endif
               class="{{ $field }} uppercase">
        @error('bar_state_code') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
    </label>

    <label class="text-sm font-medium text-slate-700">
        Enrolment number
        @if($studentOptional)<span data-student-bar-optional class="text-slate-500" hidden>(optional for students)</span>@endif
        <input name="bar_number" value="{{ old('bar_number', $profile?->bar_number) }}" maxlength="20" inputmode="text"
               placeholder="e.g. 1234" autocapitalize="characters" @if($required) required @endif @if($studentOptional) data-student-optional @endif
               class="{{ $field }} uppercase">
        @error('bar_number') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
    </label>

    <label class="text-sm font-medium text-slate-700">
        Year
        <select name="bar_registration_year" @if($required) required @endif @if($studentOptional) data-student-optional @endif class="{{ $field }}">
            <option value="">Year</option>
            @for($year = now()->year; $year >= 1950; $year--)
                <option value="{{ $year }}" @selected($selectedYear === $year)>{{ $year }}</option>
            @endfor
        </select>
        @error('bar_registration_year') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
    </label>

    <p class="text-xs text-slate-500 sm:col-span-2 lg:col-span-4">
        Written as on your enrolment certificate, e.g. <span class="font-semibold">UP/1234/2017</span>. The State code is filled in for you, so change it if yours is different. eCourts uses these three parts to find your cases.
    </p>
</div>
