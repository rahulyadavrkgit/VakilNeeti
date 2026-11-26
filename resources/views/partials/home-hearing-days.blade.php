{{--
    Home: hearing days, each with its Share button and the hearings in it. The first part comes with the page;
    the rest loads as the list is scrolled (DashboardController@moreHearings, resources/js/ui-actions.js).
--}}
@php $firstLoad ??= true; @endphp
@forelse($hearingDays as $day => $events)
    @php
        $dayDate = \Illuminate\Support\Carbon::parse($day);
        $relativeDay = $dayDate->isToday() ? __('Today') : ($dayDate->isTomorrow() ? __('Tomorrow') : null);
        $shareDay = ($relativeDay ?? $dayDate->translatedFormat('l')).' · '.$dayDate->translatedFormat('j M');
    @endphp
    <section aria-labelledby="hearing-day-{{ $day }}" class="space-y-2">
        <div class="flex min-w-0 items-center justify-between gap-2">
            <div class="min-w-0">
                <h2 id="hearing-day-{{ $day }}" class="text-sm font-bold text-slate-900">
                    {{ $relativeDay ?? $dayDate->translatedFormat('l') }} <time datetime="{{ $day }}" class="ml-1 font-medium text-slate-600">{{ $dayDate->translatedFormat('j M Y') }}</time>
                </h2>
                <p class="text-xs text-slate-600">
                    {{ $relativeDay ? $dayDate->translatedFormat('l').' · ' : '' }}{{ trans_choice(':count hearing|:count hearings', $events->count(), ['count' => $events->count()]) }}
                </p>
            </div>
            <x-day-share :heading="__('Hearings').' · '.$shareDay" :events="$events" />
        </div>
        <div class="grid gap-2">
            @foreach($events as $event)
                <x-hearing-card :event="$event" />
            @endforeach
        </div>
    </section>
@empty
    @if($firstLoad)
        <p class="rounded-xl border border-slate-200 bg-white p-5 text-sm text-slate-600">
            {{ $hearingSearch !== '' ? __('No upcoming hearings match ":search".', ['search' => $hearingSearch]) : __('No upcoming hearings.') }}
        </p>
    @endif
@endforelse
@if($moreHearingsUrl)
    <div data-load-more="{{ $moreHearingsUrl }}" class="py-3 text-center text-sm text-slate-600" aria-live="polite">
        <a href="{{ $moreHearingsUrl }}" class="vn-link font-semibold">{{ __('More hearings') }}</a>
    </div>
@elseif($hearingDays->isNotEmpty())
    <p class="py-3 text-center text-xs text-slate-500">{{ __('That is the last hearing in your diary.') }}</p>
@endif
