{{-- Pop-up: the side we appear for in this case (saved by cases.side; the party on our side becomes the client). --}}
@php
    $field = 'mt-1 w-full rounded-xl border border-slate-300 bg-transparent px-3 py-2.5 text-base sm:text-sm';
@endphp
<h2 data-sheet-heading class="text-lg font-semibold text-slate-900">{{ __('We appear for') }}</h2>
<p class="mt-0.5 text-sm text-slate-600">{{ $case->case_title }}</p>

<form method="POST" action="{{ route('cases.side', $case) }}" data-sheet-form data-sheet-success="{{ __('Side saved') }}" class="mt-4 space-y-3">
    @csrf
    @method('PATCH')
    <fieldset>
        <legend class="sr-only">{{ __('Side') }}</legend>
        <div class="grid grid-cols-2 gap-1.5 sm:grid-cols-3">
            @foreach($sides as $value => $text)
                <label class="flex min-h-11 cursor-pointer items-center justify-center rounded-xl border border-slate-300 px-2 text-center text-sm font-semibold text-slate-700 has-[:checked]:border-[#16776e] has-[:checked]:bg-teal-50 has-[:checked]:text-[#16776e]">
                    <input type="radio" name="appearing_for" value="{{ $value }}" class="sr-only" @checked($case->appearing_for === $value) required>
                    {{ __($text) }}
                </label>
            @endforeach
        </div>
    </fieldset>
    <label class="block text-sm font-semibold text-slate-700">
        {{ __('Which parties') }} <span class="font-normal text-slate-500">({{ __('optional: a name, or numbers like 2, 3') }})</span>
        <input type="text" name="appearing_for_parties" value="{{ $case->appearing_for_parties }}" maxlength="255"
               placeholder="{{ $titleSides['moving'] ?? __('Ram Kumar') }}" class="{{ $field }}">
    </label>
    <div class="flex justify-end pt-1">
        <button type="submit" class="min-h-11 rounded-xl bg-[#16776e] px-5 text-sm font-semibold text-white hover:bg-[#125f58]">{{ __('Save') }}</button>
    </div>
</form>
