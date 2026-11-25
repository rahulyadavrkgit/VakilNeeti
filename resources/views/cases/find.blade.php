@extends('layouts.app')

@section('title', 'Find my cases')
@section('page-heading', 'Find my cases')

@php
    $field = 'mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5';
    $barParts = array_filter([
        'State code' => $profile?->bar_state_code,
        'Number' => $profile?->bar_number,
        'Year' => $profile?->bar_registration_year,
    ]);
    $methods = [
        'app' => ['label' => 'eCourts app file', 'hint' => 'Fastest: all your cases at once'],
        'website' => ['label' => 'eCourts website', 'hint' => 'Search by bar code, then paste'],
        'calendar' => ['label' => 'Google Calendar', 'hint' => 'Hearings you already track'],
    ];
    $step = fn (int $number) => '<span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-[#16776e] text-sm text-white">'.$number.'</span>';
@endphp

@section('content')
<div class="mx-auto max-w-4xl space-y-5">
    @include('cases.partials.management-nav')
    <a class="inline-flex min-h-11 items-center text-sm font-semibold text-teal-800 underline" href="{{ route('cases.import.history') }}">Import history and retries</a>
    <p class="text-sm text-slate-600">
        <a href="{{ route('dashboard') }}" class="float-right ml-3 font-semibold text-[#16776e] hover:underline">Skip for now</a>
        Bring your cases in from files and searches you do yourself. VakilNiti never logs in to eCourts or Google for you, and you see every case before it is added.
    </p>

    @if($errors->any())
        <div role="alert" class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
            @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif

    <fieldset class="grid gap-2 sm:grid-cols-3">
        <legend class="mb-2 text-sm font-bold text-slate-900">Import from</legend>
        @foreach($methods as $key => $option)
            <label class="flex cursor-pointer flex-col rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm has-[:checked]:border-[#16776e] has-[:checked]:bg-teal-50">
                <span class="flex items-center gap-2 text-sm font-semibold text-slate-900">
                    <input type="radio" name="import_method" value="{{ $key }}" data-switch="method" @checked($method === $key) class="text-[#16776e] focus:ring-[#16776e]">
                    {{ $option['label'] }}
                </span>
                <span class="mt-0.5 pl-6 text-xs text-slate-500">{{ $option['hint'] }}</span>
            </label>
        @endforeach
    </fieldset>

    {{-- eCourts Services app export --}}
    <form method="POST" action="{{ route('cases.find.ecourts-app.preview', array_filter(['retry' => request('retry')])) }}" enctype="multipart/form-data" data-switch-panel="method:app" @if($method !== 'app') hidden @endif
          class="space-y-5 rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        @csrf
        <input type="hidden" name="method" value="app">
        <h2 class="text-lg font-bold text-slate-900">Upload your eCourts app export</h2>
        <ol class="space-y-3 text-sm text-slate-700">
            <li class="flex gap-3">{!! $step(1) !!}<span>In the official <span class="font-semibold">eCourts Services</span> app, open <span class="font-semibold">My Cases</span> and use its <span class="font-semibold">Export</span> option. It saves <span class="font-mono">myCases.txt</span> (District courts) and <span class="font-mono">hcMyCases.txt</span> (High Courts).</span></li>
            <li class="flex gap-3">{!! $step(2) !!}<span>Send the files to this device (email, WhatsApp or Drive) and choose them below. You can choose both at once.</span></li>
            <li class="flex gap-3">{!! $step(3) !!}<span>Check the list and add. To refresh hearing dates later, export again and upload: cases already added are updated.</span></li>
        </ol>
        <label class="block text-sm font-medium text-slate-700">
            Export files <span class="text-red-600">*</span>
            <input type="file" name="files[]" accept=".txt,.json,text/plain,application/json" multiple required
                   class="mt-1 block w-full rounded-lg border border-slate-300 bg-white text-sm file:mr-3 file:rounded-l-lg file:border-0 file:bg-teal-50 file:px-4 file:py-2.5 file:font-semibold file:text-[#16776e]">
            <span class="mt-1 block text-xs font-normal text-slate-500">Up to {{ \App\Http\Controllers\CaseFileImportController::uploadLimitMegabytes(5) }} MB each. The app files are usually well under 1 MB.</span>
        </label>
        <div class="flex justify-end">
            <button type="submit" class="rounded-lg bg-[#16776e] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#125f58]">Show cases in the file</button>
        </div>
    </form>

    {{-- eCourts website, search by bar code and paste --}}
    <form method="POST" action="{{ route('cases.find.preview', array_filter(['retry' => request('retry')])) }}" data-switch-panel="method:website" @if($method !== 'website') hidden @endif class="space-y-5">
        @csrf
        <input type="hidden" name="method" value="website">

        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h2 class="flex items-center gap-3 text-lg font-bold text-slate-900">{!! $step(1) !!} Choose the court</h2>
            <div class="mt-4 grid gap-2 sm:grid-cols-3">
                @foreach($portals as $key => $portal)
                    <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-800 has-[:checked]:border-[#16776e] has-[:checked]:bg-teal-50">
                        <input type="radio" name="level" value="{{ $key }}" data-switch="level" @checked($level === $key) class="text-[#16776e] focus:ring-[#16776e]">
                        {{ $portal['label'] }}
                    </label>
                @endforeach
            </div>
            <label class="mt-4 block text-sm font-medium text-slate-700">
                Court name <span class="font-normal text-slate-400">(used when the results don't name the court)</span>
                <input name="court_name" value="{{ $defaultCourt }}" maxlength="255" placeholder="e.g. District Court Lucknow" class="{{ $field }}">
            </label>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h2 class="flex items-center gap-3 text-lg font-bold text-slate-900">{!! $step(2) !!} Search on eCourts</h2>

            <div class="mt-4 rounded-lg bg-slate-50 p-4">
                <p class="text-sm font-semibold text-slate-800">Your bar code</p>
                @if(count($barParts) === 3)
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach($barParts as $label => $value)
                            <span class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm">
                                <span class="text-slate-500">{{ $label }}</span>
                                <span class="font-bold text-slate-900">{{ $value }}</span>
                                <button type="button" data-copy="{{ $value }}" class="text-xs font-semibold text-[#16776e] hover:underline">Copy</button>
                            </span>
                        @endforeach
                    </div>
                @else
                    <p class="mt-1 text-sm text-slate-600">
                        Add your Bar Council enrolment (State code, number and year) to see it here.
                        <a href="{{ route('profile.advocate.index') }}#bar-registration" class="font-semibold text-[#16776e] hover:underline">Add it in your profile &rarr;</a>
                    </p>
                @endif
            </div>

            @foreach($portals as $key => $portal)
                <div data-switch-panel="level:{{ $key }}" @if($level !== $key) hidden @endif class="mt-4">
                    <ol class="list-decimal space-y-1 pl-5 text-sm text-slate-700">
                        @foreach($portal['steps'] as $portalStep)
                            <li>{{ $portalStep }}</li>
                        @endforeach
                        <li>Select the whole results table, from the first case to the last, and copy it (Ctrl+C, or long-press on a phone).</li>
                    </ol>
                    <a href="{{ $portal['url'] }}" target="_blank" rel="noopener noreferrer" data-direct-share
                       class="mt-4 inline-flex min-h-11 items-center gap-2 rounded-lg bg-[#16776e] px-4 text-sm font-semibold text-white hover:bg-[#125f58]">
                        Open {{ $portal['label'] }} search &nearr;
                    </a>
                    <p class="mt-2 text-xs text-slate-500">Opens the official site in a new tab. Come back to this tab when you have copied the results.</p>
                </div>
            @endforeach
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h2 class="flex items-center gap-3 text-lg font-bold text-slate-900">{!! $step(3) !!} Paste the results</h2>
            <label class="mt-4 block text-sm font-medium text-slate-700">
                Results copied from eCourts <span class="text-red-600">*</span>
                <textarea name="results" rows="10" required maxlength="200000"
                          placeholder="1    O.S./123/2019    Ram Kumar vs Shyam Lal    View&#10;2    CRL. A/45/2021    State vs Mohan    View"
                          class="{{ $field }} font-mono text-xs">{{ old('results') }}</textarea>
            </label>
            <p class="mt-2 text-xs text-slate-500">You'll see the list first and choose which cases to add. Cases already in your diary are skipped.</p>
            <div class="mt-4 flex justify-end">
                <button type="submit" class="rounded-lg bg-[#16776e] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#125f58]">Show cases found</button>
            </div>
        </section>
    </form>

    {{-- Google Calendar export --}}
    <form method="POST" action="{{ route('cases.find.calendar.preview', array_filter(['retry' => request('retry')])) }}" enctype="multipart/form-data" data-switch-panel="method:calendar" @if($method !== 'calendar') hidden @endif
          class="space-y-5 rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        @csrf
        <input type="hidden" name="method" value="calendar">
        <h2 class="text-lg font-bold text-slate-900">Upload your Google Calendar</h2>
        <ol class="space-y-3 text-sm text-slate-700">
            <li class="flex gap-3">{!! $step(1) !!}<span>On a computer, open Google Calendar → <span class="font-semibold">Settings</span> → <span class="font-semibold">Import &amp; export</span> → <span class="font-semibold">Export</span>. Google downloads a .zip file.</span></li>
            <li class="flex gap-3">{!! $step(2) !!}<span>Upload the .zip, or a single calendar's .ics file from it.</span></li>
            <li class="flex gap-3">{!! $step(3) !!}<span>For each event choose its case, make a new case, or skip it. Events that mention a case number, CNR or case title are matched for you.</span></li>
        </ol>
        <label class="block text-sm font-medium text-slate-700">
            Calendar file <span class="text-red-600">*</span>
            <input type="file" name="calendar" accept=".ics,.zip,text/calendar,application/zip" required
                   class="mt-1 block w-full rounded-lg border border-slate-300 bg-white text-sm file:mr-3 file:rounded-l-lg file:border-0 file:bg-teal-50 file:px-4 file:py-2.5 file:font-semibold file:text-[#16776e]">
            <span class="mt-1 block text-xs font-normal text-slate-500">Up to {{ \App\Http\Controllers\CaseFileImportController::uploadLimitMegabytes(10) }} MB. If the .zip is bigger, open it and upload just your court calendar's .ics file.</span>
        </label>
        <div class="grid gap-4 sm:grid-cols-2">
            <label class="text-sm font-medium text-slate-700">
                Events from
                <input type="date" name="from" value="{{ old('from', today()->subYear()->toDateString()) }}" required class="{{ $field }}">
            </label>
            <label class="text-sm font-medium text-slate-700">
                Events until
                <input type="date" name="to" value="{{ old('to', today()->addYear()->toDateString()) }}" required class="{{ $field }}">
            </label>
        </div>
        <label class="flex items-center gap-2 text-sm text-slate-700">
            <input type="hidden" name="likely_only" value="0">
            <input type="checkbox" name="likely_only" value="1" @checked(old('likely_only', '1') === '1') class="rounded border-slate-300 text-[#16776e] focus:ring-[#16776e]">
            Show only events that look like court work (hide birthdays, meetings and other personal events)
        </label>
        <div class="flex justify-end">
            <button type="submit" class="rounded-lg bg-[#16776e] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#125f58]">Show calendar events</button>
        </div>
    </form>
</div>
@endsection
