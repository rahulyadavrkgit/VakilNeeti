{{--
    Add case, step 4: search eCourts with the details entered, then paste the results here to import them.
    The search runs on the eCourts site (it asks for a captcha); the pasted table is read by cases.find.preview.
--}}
@php
    $backQuery = ['method' => $method, 'level' => $level, 'court' => $court] + request()->except(['level', 'court']);
@endphp
@include('sheets.partials.add-case-top', ['back' => route('sheets.add-case.search', $backQuery)])

<p class="text-center text-sm font-semibold text-[#142235]">{{ __('Search by :method', ['method' => strtolower(__($methodLabel))]) }}</p>

<div class="mx-auto mt-4 max-w-md space-y-4">
    {{-- What to enter on eCourts, each with a copy button. --}}
    <dl class="divide-y divide-slate-100 rounded-xl border border-slate-200 bg-slate-50 text-sm">
        <div class="flex items-center gap-3 px-3.5 py-2.5">
            <dt class="w-28 shrink-0 text-slate-500">{{ __('Court') }}</dt>
            <dd class="min-w-0 flex-1 font-semibold text-slate-900">{{ $court }}</dd>
        </div>
        @foreach($entries as $entry)
            <div class="flex items-center gap-3 px-3.5 py-1.5">
                <dt class="w-28 shrink-0 text-slate-500">{{ __($entry['label']) }}</dt>
                <dd class="min-w-0 flex-1 truncate font-semibold text-slate-900">{{ $entry['value'] }}</dd>
                <button type="button" data-copy="{{ $entry['value'] }}" class="inline-flex min-h-9 shrink-0 items-center gap-1 rounded-lg px-2 text-xs font-semibold text-[#16776e] hover:bg-teal-50">
                    <x-icon name="copy" :size="14" /><span data-copy-label>{{ __('Copy') }}</span>
                </button>
            </div>
        @endforeach
    </dl>

    <ol class="space-y-1.5 text-sm text-slate-700">
        <li><span class="font-semibold text-slate-900">1.</span> {{ __('Open eCourts and choose ":method".', ['method' => __($methodLabel)]) }}</li>
        <li><span class="font-semibold text-slate-900">2.</span> {{ __('Fill in the details above, type the captcha and search.') }}</li>
        <li><span class="font-semibold text-slate-900">3.</span> {{ __('Copy the results table and paste it below.') }}</li>
    </ol>

    <a href="{{ $portal['url'] }}" target="_blank" rel="noopener noreferrer"
       class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-xl border border-[#16776e] bg-white px-4 text-sm font-semibold text-[#16776e] hover:bg-teal-50">
        <x-icon name="external" :size="16" /> {{ __('Open eCourts · :portal', ['portal' => $portal['label']]) }}
    </a>

    <form method="POST" action="{{ route('cases.find.preview') }}" class="space-y-3">
        @csrf
        <input type="hidden" name="level" value="{{ $level }}">
        <input type="hidden" name="court_name" value="{{ $court }}">
        <label class="block text-sm font-semibold text-slate-700">
            {{ __('Paste the eCourts results') }}
            <textarea name="results" rows="5" required maxlength="200000" placeholder="{{ __('Select the whole results table on eCourts, copy, and paste here.') }}"
                      class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 font-mono text-xs"></textarea>
        </label>
        <button type="submit" class="inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-[#16776e] px-4 text-sm font-semibold text-white shadow-sm hover:bg-[#125f58]">{{ __('Continue') }}</button>
    </form>
</div>
