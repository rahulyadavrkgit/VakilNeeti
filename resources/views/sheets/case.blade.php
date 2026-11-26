{{-- Pop-up: a quick case (title, number, court, our side). Saves through cases.store and opens the new case. --}}
@php
    $field = 'mt-1 w-full rounded-xl border border-slate-300 bg-transparent px-3 py-2.5 text-base sm:text-sm';
    $label = 'block text-sm font-semibold text-slate-700';
@endphp
<h2 data-sheet-heading class="text-lg font-semibold text-slate-900">{{ __('New case') }}</h2>

<form method="POST" action="{{ route('cases.store') }}" data-sheet-form data-sheet-follow data-sheet-success="{{ __('Case added') }}" class="mt-4 space-y-3">
    @csrf
    <input type="hidden" name="case_status" value="pending">
    <label class="{{ $label }}">
        {{ __('Title') }}
        <input type="text" name="case_title" required maxlength="255" autofocus placeholder="{{ __('Asha vs State') }}" class="{{ $field }}">
    </label>
    <div class="grid grid-cols-2 gap-3">
        <label class="{{ $label }}">
            {{ __('Case number') }}
            <input type="text" name="case_number" maxlength="255" placeholder="CS/12/2026" class="{{ $field }}">
        </label>
        <label class="{{ $label }}">
            {{ __('We appear for') }}
            <select name="appearing_for" class="{{ $field }}">
                <option value="">{{ __('Not set') }}</option>
                @foreach($sides as $value => $text)
                    <option value="{{ $value }}">{{ __($text) }}</option>
                @endforeach
            </select>
        </label>
    </div>
    @include('cases.partials.court-picker', ['case' => null, 'preset' => $preset ?? [], 'field' => $field, 'label' => $label])
    <div class="flex items-center justify-between gap-3 pt-1">
        <a href="{{ route('cases.find') }}" class="text-sm font-semibold text-[#16776e] hover:underline">{{ __('Import from eCourts') }}</a>
        <button type="submit" class="min-h-11 rounded-xl bg-[#16776e] px-5 text-sm font-semibold text-white hover:bg-[#125f58]">{{ __('Add case') }}</button>
    </div>
</form>
