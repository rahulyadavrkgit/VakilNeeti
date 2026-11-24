@extends('layouts.app')

@section('title', __('Court day'))
@section('page-heading', __('Court day'))

@section('content')
<div class="mx-auto max-w-3xl space-y-5" data-court-day data-saved-at="{{ $savedAt->toIso8601String() }}">
    <x-page-header :title="__('Court day')" :subtitle="__('Matters for today and tomorrow on one page. Open it once with internet and it works without signal in court.')" />

    <p data-offline-note hidden class="flex items-start gap-2 rounded-xl border border-amber-300 bg-amber-50 px-3 py-2 text-sm font-semibold text-amber-900">
        <x-icon name="offline" :size="16" class="mt-0.5" />
        <span>{{ __('No internet. This is the copy saved at :time. New dates added since then are not shown.', ['time' => $savedAt->translatedFormat('g:i a, d M')]) }}</span>
    </p>

    @foreach($days as $day)
        @php $isToday = $day['date']->isToday(); @endphp
        <section aria-labelledby="day-{{ $loop->index }}" class="space-y-2">
            <h2 id="day-{{ $loop->index }}" class="flex items-baseline gap-2 text-sm font-bold uppercase tracking-wide {{ $isToday ? 'text-red-600' : 'text-[#16776e]' }}">
                {{ $isToday ? __('Today') : __('Tomorrow') }}
                <span class="font-semibold normal-case tracking-normal text-slate-500">{{ $day['date']->translatedFormat('l, d M') }}</span>
                <span class="ml-auto font-semibold normal-case tracking-normal text-slate-500">{{ trans_choice(':count matter|:count matters', $day['hearings']->count(), ['count' => $day['hearings']->count()]) }}</span>
            </h2>

            @forelse($day['hearings'] as $hearing)
                @php
                    $listing = $hearing['listing'];
                    $vcLink = $hearing['vc_link'] ?: $listing?->vcLink();
                    $room = $listing?->court_room ? __('Court').' '.$listing->court_room : ($hearing['court_room'] ? __('Room').' '.$hearing['court_room'] : null);
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
                            @if($hearing['case_number'])
                                <p class="text-sm text-slate-600">{{ $hearing['case_number'] }}</p>
                            @endif
                            @if($room || $listing)
                                <p class="text-base font-bold tabular-nums text-[#16776e]">
                                    {{ $room }}@if($listing) · {{ __('Item') }} {{ $listing->item_number }}@endif
                                </p>
                            @endif
                            <p class="text-sm text-slate-700">
                                {{ collect([$hearing['court'], $hearing['court_complex']])->filter()->unique()->implode(' · ') }}
                            </p>
                            @if($hearing['judge_name'] || $listing?->judge_name)
                                <p class="text-sm text-slate-600">{{ $hearing['judge_name'] ?: \Illuminate\Support\Str::title(mb_strtolower($listing->judge_name)) }}</p>
                            @endif
                            @if($hearing['details'])
                                <p class="text-sm text-slate-700"><span class="font-semibold">{{ __('Purpose:') }}</span> {{ $hearing['details'] }}</p>
                            @endif
                        </div>
                    </div>

                    @if($vcLink || $hearing['clients']->isNotEmpty())
                        <div class="mt-3 flex flex-wrap gap-2 border-t border-slate-100 pt-3">
                            @if($vcLink)
                                <a href="{{ $vcLink }}" target="_blank" rel="noopener" class="inline-flex min-h-10 items-center gap-1.5 rounded-full bg-[#16776e] px-4 text-sm font-bold text-white hover:bg-[#12635c]">
                                    <x-icon name="video" :size="16" /> {{ __('Join VC') }}
                                </a>
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
                </article>
            @empty
                <p class="rounded-2xl border border-dashed border-slate-300 px-4 py-6 text-center text-sm text-slate-600">{{ $isToday ? __('No hearings today.') : __('No hearings tomorrow.') }}</p>
            @endforelse
        </section>
    @endforeach

    <p class="text-center text-xs text-slate-500">{{ __('Saved at :time', ['time' => $savedAt->translatedFormat('g:i a, d M')]) }} · <a href="{{ route('cases.calendar', ['view' => 'day']) }}" class="font-semibold text-[#16776e] hover:underline">{{ __('Full calendar') }}</a></p>
</div>
@endsection
