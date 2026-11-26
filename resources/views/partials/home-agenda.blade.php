{{-- Home: tasks and appointments in the next 7 days, and overdue tasks. --}}
<section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5" aria-labelledby="agenda-heading">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h2 id="agenda-heading" class="text-lg font-semibold text-slate-900">{{ __('Tasks & appointments') }}</h2>
        <a href="{{ route('tasks.create') }}" data-sheet="{{ route('sheets.task') }}" class="inline-flex min-h-9 items-center rounded-lg border border-[#16776e] px-3 text-sm font-semibold text-[#16776e] hover:bg-teal-50">{{ __('+ Task') }}</a>
    </div>

    @if($overdueTaskCount)
        <a href="{{ route('tasks.index') }}" class="mt-3 block rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-100">
            {{ trans_choice(':count overdue task|:count overdue tasks', $overdueTaskCount, ['count' => $overdueTaskCount]) }} &rarr;
        </a>
    @endif

    <ul class="mt-3 divide-y divide-slate-100">
        @forelse($agenda as $event)
            @php $eventDate = \Illuminate\Support\Carbon::parse($event['date']); @endphp
            <li>
                <a href="{{ $event['url'] }}" class="flex items-start gap-3 py-2.5 hover:bg-slate-50">
                    <span class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full {{ $event['group'] === 'task' ? 'bg-amber-500' : 'bg-sky-500' }}" aria-hidden="true"></span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm font-semibold text-slate-900">{{ $event['title'] }}</span>
                        <span class="block truncate text-xs text-slate-500">
                            {{ __($event['label']) }} · {{ $eventDate->isToday() ? __('Today') : ($eventDate->isTomorrow() ? __('Tomorrow') : $eventDate->translatedFormat('D, d M')) }}{{ $event['time'] ? ' '.$event['time'] : '' }}
                            {{ $event['case_number'] ? ' · '.$event['case_number'] : '' }}
                        </span>
                    </span>
                </a>
            </li>
        @empty
            <li class="py-6 text-center text-sm text-slate-500">{{ __('Nothing due in the next 7 days.') }}</li>
        @endforelse
    </ul>
</section>
