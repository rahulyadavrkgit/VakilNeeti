@extends('layouts.app')

@section('title', __('Court day'))
@section('page-heading', __('Court day'))

@section('content')
<div class="mx-auto max-w-3xl space-y-5" data-court-day data-saved-at="{{ $savedAt->toIso8601String() }}">
    <x-page-header :title="__('Court day')" :subtitle="__('Matters for today and tomorrow on one page.')" />

    <p data-offline-note hidden class="flex items-start gap-2 rounded-xl border border-amber-300 bg-amber-50 px-3 py-2 text-sm font-semibold text-amber-900">
        <x-icon name="offline" :size="16" class="mt-0.5" />
        <span>{{ __('Offline. This page was last loaded at :time. Reconnect to refresh.', ['time' => $savedAt->translatedFormat('g:i a, d M')]) }}</span>
    </p>

    @if(session('success'))<p role="status" class="rounded-xl bg-teal-50 p-3 text-sm">{{ session('success') }}</p>@endif
    @error('message')<p role="alert" class="rounded-xl bg-red-50 p-3 text-sm">{{ $message }}</p>@enderror
    <form method="GET" class="flex flex-wrap items-end gap-2">
        <label class="text-sm font-medium">Hearing date
            <input required type="date" name="date" value="{{ $selectedDate?->toDateString() ?? today()->toDateString() }}" class="mt-1 block min-h-11 rounded-lg border-slate-300 bg-white px-3">
        </label>
        <button class="min-h-11 rounded-lg bg-teal-700 px-4 text-sm font-semibold text-white">Show date</button>
        @if($selectedDate)<a href="{{ route('court-day') }}" class="inline-flex min-h-11 items-center text-sm underline">Today and tomorrow</a>@endif
    </form>
    @foreach($days as $day)
        @php $isToday = $day['date']->isToday(); @endphp
        <section aria-labelledby="day-{{ $loop->index }}" class="space-y-2">
            <h2 id="day-{{ $loop->index }}" class="flex items-baseline gap-2 text-sm font-bold uppercase tracking-wide {{ $isToday ? 'text-red-600' : 'text-[#16776e]' }}">
                {{ $isToday ? __('Today') : ($day['date']->isTomorrow() ? __('Tomorrow') : __('Selected date')) }}
                <span class="font-semibold normal-case tracking-normal text-slate-500">{{ $day['date']->translatedFormat('l, d F Y') }}</span>
                <span class="ml-auto font-semibold normal-case tracking-normal text-slate-500">{{ trans_choice(':count matter|:count matters', $day['hearings']->count(), ['count' => $day['hearings']->count()]) }}</span>
            </h2>

            @forelse($day['hearings'] as $hearing)
                @php
                    $listing = $hearing['cause_list'] ?? null;
                    $vcLink = $listing['vc_link'] ?? $hearing['vc_link'];
                @endphp
                <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm {{ $isToday ? 'border-l-4 border-l-red-600' : '' }}">
                    <div class="flex items-start gap-3">
                        <div class="w-14 shrink-0 text-center">
                            @if($hearing['time'])
                                <p class="text-lg font-extrabold tabular-nums leading-tight text-slate-900">{{ \Illuminate\Support\Carbon::parse($hearing['time'])->format('g:i') }}</p>
                            @else
                                <p class="pt-1 text-xs font-semibold uppercase leading-tight text-slate-500">{{ __('No time') }}</p>
                            @endif
                            @if($hearing['time'])
                                <p class="text-xs font-semibold uppercase text-slate-500">{{ \Illuminate\Support\Carbon::parse($hearing['time'])->format('a') }}</p>
                            @endif
                        </div>
                        <div class="min-w-0 flex-1 space-y-1">
                            <a href="{{ $hearing['url'] }}" class="block text-base font-bold text-slate-900 hover:text-[#16776e] hover:underline">{{ $hearing['title'] }}</a>
                            <p class="flex flex-wrap items-center gap-1.5 text-sm text-slate-600">
                                <x-court-badge :level="$hearing['court_level'] ?? null" :state="$hearing['state_code'] ?? null" :district="$hearing['district'] ?? null" />
                                {{ $hearing['case_number'] }}
                            </p>
                            @if(isset($hearing['date_source']) && !$listing)
                                <p class="text-xs text-slate-600">{{ $hearing['date_source'] }} @if($hearing['source_updated_at'])&middot; Updated {{ $hearing['source_updated_at']->format('d M, g:i a') }}@endif</p>
                            @endif
                            <x-hearing-strip always :date="$hearing['date']" :court="$hearing['court']" :room="$hearing['court_room']" :item="$hearing['item_number'] ?? null" :list-total="$hearing['list_total'] ?? null"
                                             :cause-list="$listing" :case-number="$hearing['case_number']" :show-date="false"
                                             :district="($hearing['court_level'] ?? null) === 'district'" />
                            <p class="text-sm text-slate-700">
                                {{ collect([$hearing['court'], $hearing['court_complex']])->filter()->unique()->implode(' · ') }}
                            </p>
                            @if(($hearing['court_level'] ?? null) === 'district' && $hearing['court_room'])
                                <p class="text-sm text-slate-600">Room {{ $hearing['court_room'] }}</p>
                            @endif
                            @if($hearing['judge_name'] || !empty($listing['judges']))
                                <p class="text-sm text-slate-600">{{ $listing['judges'] ?? $hearing['judge_name'] }}</p>
                            @endif
                            @if($hearing['details'])
                                <p class="text-sm text-slate-700"><span class="font-semibold">{{ __('Purpose:') }}</span> {{ $hearing['details'] }}</p>
                            @endif
                        </div>
                    </div>

                    @if($vcLink || $hearing['clients']->isNotEmpty() || $hearing['case_id'])
                        <div class="mt-3 flex flex-wrap gap-2 border-t border-slate-100 pt-3">
                            @if($vcLink)
                                <a href="{{ $vcLink }}" target="_blank" rel="noopener" class="inline-flex min-h-10 items-center gap-1.5 rounded-full bg-[#16776e] px-4 text-sm font-bold text-white hover:bg-[#12635c]">
                                    <x-icon name="video" :size="16" /> {{ __('Join VC') }}
                                </a>
                            @endif
                            @if($hearing['case_id'])
                                <x-quick-next-date :case-id="$hearing['case_id']" class="min-h-11" />
                                @if($hearing['order'])
                                    <a class="inline-flex min-h-11 items-center rounded-lg border border-slate-200 px-3 text-sm font-semibold" href="{{ route('cases.documents.download', [$hearing['case_id'], $hearing['order'], 'preview' => 1]) }}" title="{{ $hearing['order']->original_name }}">Open order @if($hearing['order']->created_at)&middot; {{ $hearing['order']->created_at->format('d M Y') }}@endif</a>
                                @endif
                            @endif
                            @foreach($hearing['clients'] as $client)
                                @php $phone = $client->mobile ?: $client->phone; @endphp
                                @if($phone)
                                    <a href="tel:{{ preg_replace('/[^\d+]/', '', $phone) }}" class="inline-flex min-h-10 items-center gap-1.5 rounded-full border border-slate-200 px-4 text-sm font-semibold text-slate-700 hover:border-[#16776e] hover:text-[#16776e]">
                                        <x-icon name="phone" :size="16" /> {{ \Illuminate\Support\Str::limit($client->displayName(), 22) }}
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    @endif
                    @if($hearing['case_id'])
                        <x-case-report :case-id="$hearing['case_id']" page="court-day" />
                    @endif
                </article>
            @empty
                <p class="rounded-2xl border border-dashed border-slate-300 px-4 py-6 text-center text-sm text-slate-600">{{ __('No hearings on this date.') }}</p>
            @endforelse
        </section>
    @endforeach

    <p class="text-center text-xs text-slate-500">{{ __('Updated :time', ['time' => $savedAt->translatedFormat('g:i a, d M')]) }} · <a href="{{ route('cases.calendar', ['view' => 'day']) }}" class="font-semibold text-[#16776e] hover:underline">{{ __('Full calendar') }}</a></p>
</div>
@endsection
