{{-- Top of each "Add case" step: Back to the step before, and the title (the sheet has its own close button). --}}
<div class="-mt-1 mb-4 flex items-center gap-2 border-b border-slate-200 pb-3 pr-10">
    @isset($back)
        <a href="{{ route('cases.find') }}" data-sheet="{{ $back }}" class="inline-flex min-h-10 items-center gap-1 rounded-lg px-2 text-sm font-semibold text-slate-600 hover:bg-slate-100 hover:text-slate-900">
            <x-icon name="back" :size="16" /> {{ __('Back') }}
        </a>
    @endisset
    <h2 data-sheet-heading class="flex-1 text-center text-base font-semibold text-slate-900 {{ isset($back) ? 'pr-16' : '' }}">{{ __('Add case') }}</h2>
</div>
