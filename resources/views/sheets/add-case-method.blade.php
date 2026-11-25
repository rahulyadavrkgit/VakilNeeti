{{-- Add case, step 2: how to find the case in the chosen court. --}}
@include('sheets.partials.add-case-top', ['back' => route('sheets.add-case')])

<p class="text-center text-sm font-semibold text-[#142235]">{{ __('Find your case in') }} <span class="text-[#16776e]">{{ $court }}</span> {{ __('using') }}</p>

<div class="mx-auto mt-5 grid max-w-md grid-cols-2 gap-3 sm:grid-cols-3">
    @foreach($methods as $key => $searchMethod)
        <a href="{{ route('cases.find', ['method' => 'website', 'level' => $level, 'court_name' => $court]) }}"
           data-sheet="{{ route('sheets.add-case.search', ['method' => $key, 'level' => $level, 'court' => $court]) }}"
           class="group flex flex-col overflow-hidden rounded-xl border border-slate-200 bg-white text-center transition hover:border-[#16776e] hover:shadow-md focus-visible:border-[#16776e]">
            <span class="flex flex-1 items-center justify-center py-5">
                <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-teal-50 text-[#16776e] transition group-hover:bg-[#16776e] group-hover:text-white">
                    <x-icon :name="$searchMethod['icon']" :size="26" />
                </span>
            </span>
            <span class="border-t border-slate-100 bg-slate-50 px-2 py-2.5 text-sm font-semibold text-slate-800 group-hover:bg-teal-50 group-hover:text-[#16776e]">{{ __($searchMethod['label']) }}</span>
        </a>
    @endforeach
</div>

<p class="mt-5 text-center text-sm">
    <a href="{{ route('cases.manual') }}" data-sheet="{{ route('sheets.case') }}" class="font-semibold text-[#16776e] hover:underline">{{ __('Add this case manually instead') }}</a>
</p>
