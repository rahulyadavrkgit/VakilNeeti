{{-- Add case, step 3: the details for the chosen way of searching. --}}
@php
    $input = 'mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-base sm:text-sm';
    $label = 'block text-sm font-semibold text-slate-700';
@endphp
@include('sheets.partials.add-case-top', ['back' => route('sheets.add-case.method', ['level' => $level, 'court' => $court])])

<p class="text-center text-sm font-semibold text-[#142235]">{{ __('Search by :method', ['method' => strtolower(__($methodLabel))]) }}</p>

<form method="GET" action="{{ route('sheets.add-case.continue', ['method' => $method]) }}" data-sheet-step class="mx-auto mt-4 max-w-md space-y-3.5">
    <input type="hidden" name="level" value="{{ $level }}">
    <input type="hidden" name="court" value="{{ $court }}">

    <label class="{{ $label }}">
        {{ __('Court') }}
        <input type="text" value="{{ $court }}" readonly tabindex="-1" class="{{ $input }} bg-slate-50 text-slate-600">
    </label>

    @foreach($fields as $name => $field)
        <label class="{{ $label }}">
            {{ __($field['label']) }} @unless($field['required'])<span class="font-normal text-slate-400">({{ __('optional') }})</span>@endunless
            @if($field['type'] === 'year')
                <select name="{{ $name }}" @required($field['required']) class="{{ $input }}">
                    <option value="">{{ __($field['placeholder']) }}</option>
                    @for($y = (int) date('Y'); $y >= 1950; $y--)
                        <option value="{{ $y }}" @selected((string) $field['value'] === (string) $y)>{{ $y }}</option>
                    @endfor
                </select>
            @else
                <input type="text" name="{{ $name }}" value="{{ $field['value'] }}" maxlength="120" @required($field['required'])
                       placeholder="{{ __($field['placeholder']) }}" @if($loop->first) autofocus @endif class="{{ $input }}">
            @endif
        </label>
    @endforeach

    <button type="submit" class="mt-1 inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-[#16776e] px-4 text-sm font-semibold text-white shadow-sm hover:bg-[#125f58]">{{ __('Continue') }}</button>
</form>
