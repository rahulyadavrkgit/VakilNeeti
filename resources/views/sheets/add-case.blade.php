{{--
    Pop-up: "Add case". Search any court and go on to import its cases (Find & import, with the court filled in),
    or look up one case by CNR, import an eCourts app list or calendar, or type the case in.
--}}
@php
    $isTribunal = fn (array $court): bool => $court['level'] === 'tribunal';
    $courtLink = fn (array $court): string => $isTribunal($court)
        ? route('cases.manual')
        : route('cases.find', ['method' => 'website', 'level' => $court['level'], 'court_name' => $court['name']]);
    $nextStep = fn (array $court): string => $isTribunal($court)
        ? route('sheets.case', ['court_level' => 'tribunal', 'court_name' => $court['name']])
        : route('sheets.add-case.method', ['level' => $court['level'], 'court' => $court['name']]);
    $levelBadge = fn (string $level): string => match ($level) {
        'supreme_court' => 'bg-[#142235] text-[#e6b84a]',
        'high_court' => 'bg-[#16776e] text-white',
        'tribunal' => 'bg-amber-50 text-amber-800 ring-1 ring-inset ring-amber-200',
        default => 'bg-sky-50 text-sky-800 ring-1 ring-inset ring-sky-200',
    };
    $levelName = fn (string $level): string => match ($level) { 'supreme_court' => 'SC', 'high_court' => 'HC', 'tribunal' => 'Tribunal', default => 'District' };
    $row = 'flex min-h-11 items-center gap-3 rounded-lg px-3 py-2 text-sm text-slate-800 hover:bg-teal-50';
    $other = 'flex min-h-11 items-center gap-3 rounded-xl border border-slate-200 px-3.5 text-sm font-semibold text-slate-800 hover:border-[#16776e] hover:text-[#16776e]';
@endphp
@include('sheets.partials.add-case-top')
<p class="text-center text-sm font-semibold text-[#142235]">{{ __('Select a court to add a case') }}</p>

<label class="mt-4 flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 focus-within:border-[#16776e] focus-within:ring-2 focus-within:ring-[#16776e]/15">
    <x-icon name="search" :size="18" class="shrink-0 text-slate-400" />
    <span class="sr-only">{{ __('Search courts') }}</span>
    <input type="search" data-filter-list="add-case-courts" autofocus autocomplete="off"
           placeholder="{{ __('Supreme Court, a High Court, a district or a tribunal…') }}" class="min-h-11 w-full border-0 bg-transparent px-0 text-base outline-none focus:ring-0 sm:text-sm">
</label>

<div id="add-case-courts" class="mt-2 max-h-72 overflow-y-auto overscroll-contain rounded-xl border border-slate-200 p-1">
    @if($yourCourts->isNotEmpty())
        <p class="px-3 pb-1 pt-2 text-xs font-bold uppercase tracking-wide text-slate-500">{{ __('Your courts') }}</p>
        @foreach($yourCourts as $court)
            <a href="{{ $courtLink($court) }}" data-sheet="{{ $nextStep($court) }}" data-filter-item class="{{ $row }}">
                <span class="w-14 shrink-0 rounded-full px-1.5 py-0.5 text-center text-[11px] font-bold {{ $levelBadge($court['level']) }}">{{ $levelName($court['level']) }}</span>
                <span class="min-w-0 flex-1 truncate font-semibold">{{ $court['name'] }}</span>
                <span class="shrink-0 text-xs text-slate-500">{{ $court['hint'] }}</span>
            </a>
        @endforeach
        <p class="px-3 pb-1 pt-3 text-xs font-bold uppercase tracking-wide text-slate-500">{{ __('All courts') }}</p>
    @endif
    @foreach($courts as $court)
        <a href="{{ $courtLink($court) }}" data-sheet="{{ $nextStep($court) }}" data-filter-item class="{{ $row }}">
            <span class="w-14 shrink-0 rounded-full px-1.5 py-0.5 text-center text-[11px] font-bold {{ $levelBadge($court['level']) }}">{{ $levelName($court['level']) }}</span>
            <span class="min-w-0 flex-1 truncate">{{ $court['name'] }}</span>
            @if($court['hint'])<span class="hidden shrink-0 truncate text-xs text-slate-500 sm:block sm:max-w-40">{{ $court['hint'] }}</span>@endif
        </a>
    @endforeach
    <p data-filter-empty hidden class="px-3 py-4 text-center text-sm text-slate-500">{{ __('No court matches. Try the district or State name, or add the case manually.') }}</p>
</div>

<p class="mt-5 text-xs font-bold uppercase tracking-wide text-slate-500">{{ __('Other ways') }}</p>
<div class="mt-2 grid gap-2 sm:grid-cols-2">
    <a href="{{ route('cases.create') }}" class="{{ $other }}"><x-icon name="search" :size="18" class="text-[#16776e]" />{{ __('CNR number search') }}</a>
    <a href="{{ route('cases.find', ['method' => 'app']) }}" class="{{ $other }}"><x-icon name="download" :size="18" class="text-[#16776e]" />{{ __('Import from eCourts app') }}</a>
    <a href="{{ route('cases.find', ['method' => 'calendar']) }}" class="{{ $other }}"><x-icon name="calendar" :size="18" class="text-[#16776e]" />{{ __('Import a calendar file') }}</a>
    <a href="{{ route('cases.manual') }}" data-sheet="{{ route('sheets.case') }}" class="{{ $other }}"><x-icon name="plus" :size="18" class="text-[#16776e]" />{{ __('Add case manually') }}</a>
</div>
