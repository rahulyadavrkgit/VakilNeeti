@php
    $field = 'mt-1 min-h-10 w-full min-w-0 rounded-lg border border-slate-300 bg-white px-2.5 text-base sm:text-sm';
@endphp
<h2 data-sheet-heading class="text-lg font-semibold text-slate-900">Court position and appearance</h2>
<p class="mt-1 break-words text-sm text-slate-600">{{ $case->case_title }}</p>

<form method="POST" action="{{ route('cases.side', $case) }}" data-sheet-form data-sheet-success="Court position and appearance saved" data-court-position class="mt-4 space-y-3">
    @csrf
    @method('PATCH')
    <div class="grid grid-cols-2 gap-3">
        <label class="min-w-0 text-sm font-semibold text-slate-700">
            We appear for
            <select name="appearing_for" class="{{ $field }}">
                <option value="" @disabled(filled($case->appearing_for))>Choose a party</option>
                @foreach($sides as $value => $label)
                    <option value="{{ $value }}" data-side="{{ \App\Models\LegalCase::isMovingSide($value) ? 'moving' : (\App\Models\LegalCase::isAnsweringSide($value) ? 'answering' : '') }}" @selected($case->appearing_for === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        @if(filled($case->court_name))
            <label class="min-w-0 text-sm font-semibold text-slate-700">
                Petitioner’s side
                <select name="petitioner_side" class="{{ $field }}">
                    <option value="" @disabled($petitionerSide !== null)>Choose a side</option>
                    @foreach(\App\Models\CourtStandSide::SIDES as $value => $label)
                        <option value="{{ $value }}" @selected($petitionerSide === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
        @endif
    </div>
    @if(filled($case->court_name))
        <p class="text-xs text-slate-500">Set the petitioner’s side as you face the judge. It applies to your cases in {{ $case->court_name }}.</p>
    @else
        <p class="text-xs text-slate-500">Add the court name in Edit case to set a courtroom position.</p>
    @endif
    <label class="block text-sm font-semibold text-slate-700">
        Party name or number <span class="font-normal text-slate-500">(optional)</span>
        <input name="appearing_for_parties" value="{{ $case->appearing_for_parties }}" maxlength="255"
               placeholder="{{ $titleSides['moving'] ?? 'Name or numbers like 2, 3' }}" class="{{ $field }}">
    </label>
    <div class="rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-700">
        <p class="font-semibold">Your position facing the judge</p>
        <output data-court-position-preview aria-live="polite">
            {{ $standSide ? $standSide['label'] : 'Choose your party and the petitioner’s side' }}
        </output>
    </div>
    <div class="flex justify-end">
        <button type="submit" class="vn-primary-button inline-flex min-h-9 items-center rounded-lg px-3.5 text-sm font-semibold">Save</button>
    </div>
</form>
