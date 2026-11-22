@props([
    'stateField' => 'state',
    'country' => 'India',
    'state' => null,
    'district' => null,
    'city' => null,
    'withCountry' => false,
    'required' => false,
    'fieldClass' => 'mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5',
    'labelClass' => 'text-sm font-medium text-slate-700',
])

@php
    $stateGroups = \App\Models\State::dropdownGroups();
    $country = $country ?: 'India';
    $isIndia = $country === 'India';
    $idPrefix = 'loc-'.\Illuminate\Support\Str::random(6);
@endphp

{{-- Country → State/UT → District → City dropdowns, driven by resources/js/location-picker.js. --}}
<div data-location-picker
     data-districts-url="{{ url('/api/court-directory/states') }}"
     data-cities-url="{{ url('/api/locations/districts') }}"
     {{ $attributes->merge(['class' => 'grid gap-4 sm:grid-cols-2']) }}>

    @if($withCountry)
        <div class="{{ $labelClass }}">
            <label for="{{ $idPrefix }}-country">Country</label>
            <select data-location-country-select aria-label="Country" hidden class="{{ $fieldClass }}"></select>
            <input id="{{ $idPrefix }}-country" name="country" value="{{ $country }}" maxlength="100"
                   data-location-country-input placeholder="Type the country" class="{{ $fieldClass }}">
            @error('country') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
        </div>
    @endif

    <div class="{{ $labelClass }}">
        <label for="{{ $idPrefix }}-state">State / Union Territory @if($required)<span class="text-red-600">*</span>@endif</label>
        <select id="{{ $idPrefix }}-state" name="{{ $stateField }}" data-location-state @required($required) @disabled(! $isIndia) @if(! $isIndia) hidden @endif
                class="{{ $fieldClass }}">
            <x-state-options :groups="$stateGroups" :selected="$isIndia ? $state : null" />
        </select>
        @if($withCountry)
            <input name="{{ $stateField }}" value="{{ $isIndia ? '' : $state }}" maxlength="120" data-location-state-input
                   placeholder="Type the state / province" @disabled($isIndia) @if($isIndia) hidden @endif class="{{ $fieldClass }}">
        @endif
        @error($stateField) <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
    </div>

    <div class="{{ $labelClass }}">
        <label for="{{ $idPrefix }}-district">District @if($required)<span class="text-red-600">*</span>@endif</label>
        <select data-location-district-select aria-label="District" hidden class="{{ $fieldClass }}"></select>
        <input id="{{ $idPrefix }}-district" name="district" value="{{ $district }}" maxlength="120" @required($required)
               data-location-district-input placeholder="Type the district" class="{{ $fieldClass }}">
        <span data-location-district-hint class="mt-1 block text-xs font-normal text-slate-500" hidden></span>
        @error('district') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
    </div>

    <div class="{{ $labelClass }}">
        <label for="{{ $idPrefix }}-city">City / Town @if($required)<span class="text-red-600">*</span>@endif</label>
        <select data-location-city-select aria-label="City or town" hidden class="{{ $fieldClass }}"></select>
        <input id="{{ $idPrefix }}-city" name="city" value="{{ $city }}" maxlength="120" @required($required)
               data-location-city-input placeholder="Type the city or town" autocomplete="address-level2" class="{{ $fieldClass }}">
        @error('city') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
    </div>

    {{ $slot }}
</div>
