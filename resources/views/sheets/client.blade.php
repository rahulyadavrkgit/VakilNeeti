{{-- Pop-up: a quick client (name and phone). Saves through clients.store. --}}
@php
    $field = 'mt-1 w-full rounded-xl border border-slate-300 bg-transparent px-3 py-2.5 text-base sm:text-sm';
    $label = 'block text-sm font-semibold text-slate-700';
@endphp
<h2 data-sheet-heading class="text-lg font-semibold text-slate-900">{{ __('New client') }}</h2>

<form method="POST" action="{{ route('clients.store') }}" data-sheet-form data-sheet-success="{{ __('Client added') }}" class="mt-4 space-y-3">
    @csrf
    <input type="hidden" name="client_type" value="individual">
    <div class="grid grid-cols-2 gap-3">
        <label class="{{ $label }}">
            {{ __('First name') }}
            <input type="text" name="first_name" required maxlength="255" autofocus class="{{ $field }}">
        </label>
        <label class="{{ $label }}">
            {{ __('Last name') }}
            <input type="text" name="last_name" maxlength="255" class="{{ $field }}">
        </label>
    </div>
    <label class="{{ $label }}">
        {{ __('Mobile') }}
        <input type="tel" name="mobile" maxlength="20" inputmode="tel" placeholder="{{ __('98xxxxxxxx') }}" class="{{ $field }}">
    </label>
    <label class="{{ $label }}">
        {{ __('Email') }} <span class="font-normal text-slate-500">({{ __('optional') }})</span>
        <input type="email" name="email" maxlength="255" class="{{ $field }}">
    </label>
    <div class="flex items-center justify-between gap-3 pt-1">
        <a href="{{ route('clients.create') }}" class="text-sm font-semibold text-[#16776e] hover:underline">{{ __('Company or more details') }}</a>
        <button type="submit" class="min-h-11 rounded-xl bg-[#16776e] px-5 text-sm font-semibold text-white hover:bg-[#125f58]">{{ __('Add client') }}</button>
    </div>
</form>
