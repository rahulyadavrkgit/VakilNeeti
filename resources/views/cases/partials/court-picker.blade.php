{{--
    Where the case is heard, asked step by step: court level → State → district → court / bench.
    With a CNR the court and state are read from it, so only the bench is asked. "Your courts" fills all at one tap.
    Driven by resources/js/court-picker.js (delegated, so it also works inside pop-up sheets).
--}}
@php
    $case ??= null;
    $preset ??= [];
    $field ??= 'mt-1 w-full rounded-xl border border-slate-300 bg-transparent px-3 py-2.5 text-base sm:text-sm';
    $label ??= 'block text-sm font-semibold text-slate-700';
    $level = old('court_level', $case?->court_level ?? ($preset['court_level'] ?? ''));
    $state = old('state_code', $case?->state_code ?? '');
    $district = old('district', $case?->district ?? '');
    $fromCnr = $case?->cnr_number && $case?->court_code && $case->court_code !== 'SC';
    // The advocate's own courts and court names, most used first ("remember my courts").
    $myCourts = \App\Models\LegalCase::query()->whereNotNull('court_level')
        ->selectRaw('court_level, state_code, district, count(*) as cases_count')
        ->groupBy('court_level', 'state_code', 'district')
        ->orderByDesc('cases_count')->limit(6)->get()
        ->sortBy(fn ($court) => array_search($court->court_level, array_keys(\App\Services\CourtIdentity::LEVELS), true));
    $myCourtNames = \App\Models\LegalCase::query()->whereNotNull('court_name')
        ->selectRaw('court_name, count(*) as uses')->groupBy('court_name')->orderByDesc('uses')->limit(40)->pluck('court_name');
    $listId = 'court-names-'.\Illuminate\Support\Str::random(5);
    $show = fn (array $levels): string => in_array($level, $levels, true) ? '' : 'hidden';
@endphp

<div data-court-picker data-districts-url="{{ url('/api/locations/court-districts') }}" class="space-y-3">
    @if($fromCnr)
        <p class="flex flex-wrap items-center gap-2 rounded-xl bg-slate-50 px-3 py-2 text-sm text-slate-700">
            <x-court-badge :level="$case->court_level" :state="$case->state_code" :district="$case->district" />
            <span>From the CNR · court code <strong class="tabular-nums">{{ $case->court_code }}</strong></span>
        </p>
    @else
        @if($myCourts->isNotEmpty())
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Your courts</p>
                <div class="mt-1.5 flex flex-wrap gap-1.5">
                    @foreach($myCourts as $myCourt)
                        @php $myLabel = \App\Services\CourtIdentity::label($myCourt->court_level, $myCourt->state_code, $myCourt->district); @endphp
                        @if($myLabel)
                            <button type="button" data-court-choice data-level="{{ $myCourt->court_level }}" data-state="{{ $myCourt->state_code }}" data-district="{{ $myCourt->district }}"
                                    class="min-h-11 rounded-full border border-slate-200 px-3 text-sm font-semibold text-slate-700 hover:border-[#16776e] hover:text-[#16776e] aria-pressed:border-[#16776e] aria-pressed:bg-teal-50 aria-pressed:text-[#16776e]"
                                    aria-pressed="{{ $level === $myCourt->court_level && $state === (string) $myCourt->state_code && $district === (string) $myCourt->district ? 'true' : 'false' }}">{{ $myLabel }}</button>
                        @endif
                    @endforeach
                </div>
            </div>
        @endif

        <fieldset>
            <legend class="{{ $label }}">Court level</legend>
            <div class="mt-1 grid grid-cols-2 gap-1.5 sm:grid-cols-4">
                @foreach(\App\Services\CourtIdentity::LEVELS as $value => $text)
                    <label class="flex min-h-11 cursor-pointer has-[:focus-visible]:outline-2 has-[:focus-visible]:outline-teal-600 items-center justify-center rounded-xl border border-slate-300 px-2 text-center text-sm font-semibold text-slate-700 has-[:checked]:border-[#16776e] has-[:checked]:bg-teal-50 has-[:checked]:text-[#16776e]">
                        <input type="radio" name="court_level" value="{{ $value }}" data-court-level class="sr-only" @checked($level === $value)>
                        {{ $text }}
                    </label>
                @endforeach
            </div>
        </fieldset>

        <div class="grid gap-3 sm:grid-cols-2">
            <label class="{{ $label }} {{ $show(['high', 'district', 'tribunal']) }}" data-for-level="high district tribunal">
                State / UT
                <select name="state_code" data-court-state class="{{ $field }}">
                    <option value="">Choose…</option>
                    @foreach(\App\Services\CourtIdentity::states() as $code => $name)
                        <option value="{{ $code }}" @selected($state === $code)>{{ $name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="{{ $label }} {{ $show(['district']) }}" data-for-level="district">
                District
                <select name="district" data-court-district data-selected="{{ $district }}" class="{{ $field }}">
                    <option value="">{{ $state ? 'Choose…' : 'Choose the State first' }}</option>
                    @if($district)
                        <option value="{{ $district }}" selected>{{ $district }}</option>
                    @endif
                </select>
            </label>
            <label class="{{ $label }} {{ $show(['supreme']) }}" data-for-level="supreme">
                Diary number <span class="font-normal text-slate-500">(optional)</span>
                <input name="diary_number" value="{{ old('diary_number', $case?->diary_number) }}" maxlength="30" placeholder="47715/2026" class="{{ $field }}">
            </label>
        </div>
    @endif

    <label class="{{ $label }}">
        <span data-court-name-label>{{ in_array($level, ['supreme', 'high'], true) ? 'Bench / court no.' : 'Court / bench' }}</span>
        <span class="font-normal text-slate-500" data-court-name-hint>{{ in_array($level, ['supreme', 'high'], true) ? '(optional)' : '' }}</span>
        <input name="court_name" value="{{ old('court_name', $case?->court_name ?? ($preset['court_name'] ?? null)) }}" maxlength="255" list="{{ $listId }}" autocomplete="off"
               placeholder="e.g. Chief Judicial Magistrate, Civil Judge (J.D.), Court No. 16" class="{{ $field }}">
        <datalist id="{{ $listId }}">
            @foreach($myCourtNames as $courtName)
                <option value="{{ $courtName }}"></option>
            @endforeach
        </datalist>
    </label>
</div>
