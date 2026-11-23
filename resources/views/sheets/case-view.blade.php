{{-- Pop-up: a case at a glance, with the next date, where it is heard, and the client to call. --}}
@php
    $nextDate = $next['date'] ?? null;
    $nextHearing = $next['hearing'] ?? null;
    $clients = $case->parties->pluck('client')->filter()->unique('id');
@endphp
<div class="flex flex-wrap items-center gap-1.5">
    <h2 data-sheet-heading class="text-lg font-semibold leading-snug text-slate-900">{{ $case->case_title }}</h2>
    <x-case-side :side="$case->appearing_for" :parties="$case->appearing_for_parties" :edit-url="route('cases.edit', $case)" />
</div>
<p class="mt-0.5 text-sm text-slate-600">{{ collect([$case->case_number, $case->cnr_number ? 'CNR '.$case->cnr_number : null, ucfirst((string) $case->case_status)])->filter()->implode(' · ') }}</p>

<div class="mt-4 rounded-xl border p-3 {{ $nextDate?->isToday() ? 'border-red-200 bg-red-50' : ($nextDate ? 'border-slate-200 bg-slate-50' : 'border-amber-200 bg-amber-50') }}">
    @if($nextDate)
        <p class="text-xs font-bold uppercase tracking-wide {{ $nextDate->isToday() ? 'text-red-700' : 'text-slate-500' }}">Next date</p>
        <p class="mt-0.5 font-semibold text-slate-900">
            {{ $nextDate->isToday() ? 'Today' : ($nextDate->isTomorrow() ? 'Tomorrow' : $nextDate->format('D, d M Y')) }}@if($nextHearing?->hearing_time) · {{ substr((string) $nextHearing->hearing_time, 0, 5) }}@endif
        </p>
        @if($nextHearing?->hearing_stage)
            <p class="text-sm text-slate-600">{{ $nextHearing->hearing_stage }}</p>
        @endif
    @else
        <p class="font-semibold text-amber-900">No next date yet</p>
    @endif
</div>

<x-court-info class="mt-3" :court="$case->court_name" :designation="$case->court_name" :complex="$case->court_complex"
              :room="$nextHearing?->court_room ?: $case->court_room" :judge="$nextHearing?->judge_name ?: $case->judge_name" :vc-link="$nextHearing?->vc_link ?: $case->vc_link"
              :share="['title' => $case->case_title, 'case_number' => $case->case_number, 'side' => $case->appearing_for, 'parties' => $case->appearing_for_parties, 'date' => $nextDate?->toDateString(), 'time' => $nextHearing?->hearing_time ? substr((string) $nextHearing->hearing_time, 0, 5) : null, 'details' => $nextHearing?->hearing_stage]" />

@if($last)
    <p class="mt-3 text-sm text-slate-600"><span class="font-semibold text-slate-800">Last:</span> {{ $last->hearing_date->format('d M Y') }}{{ $last->hearing_stage ? ' · '.$last->hearing_stage : '' }}</p>
@endif

@if($clients->isNotEmpty())
    <ul class="mt-3 space-y-2">
        @foreach($clients as $client)
            <li class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-slate-200 px-3 py-2">
                <a href="{{ route('clients.show', $client) }}" data-sheet="{{ route('sheets.client-card', $client) }}" class="font-semibold text-slate-900 hover:text-[#16776e]">{{ $client->displayName() }}</a>
                <span class="flex gap-1.5">
                    @if($client->mobile)
                        <a href="tel:{{ $client->mobile }}" class="inline-flex min-h-9 items-center rounded-full border border-slate-200 px-3 text-sm font-semibold text-slate-700 hover:border-[#16776e]">Call</a>
                    @endif
                    @if($client->whatsappNumber())
                        <a href="https://wa.me/{{ $client->whatsappNumber() }}?text={{ rawurlencode($case->clientUpdateMessage($client->displayName(), auth()->user()->name)) }}" target="_blank" rel="noopener noreferrer" data-direct-share
                           class="inline-flex min-h-9 items-center rounded-full bg-[#25d366] px-3 text-sm font-semibold text-white">WhatsApp</a>
                    @endif
                </span>
            </li>
        @endforeach
    </ul>
@endif

<div class="mt-5 flex flex-wrap gap-2">
    <a href="{{ route('hearings.create', ['case' => $case->id]) }}" data-sheet="{{ route('sheets.next-date', ['case' => $case->id]) }}"
       class="inline-flex min-h-11 flex-1 items-center justify-center gap-1.5 rounded-xl border border-[#16776e] px-4 text-sm font-semibold text-[#16776e] hover:bg-teal-50">
        <x-icon name="calendar" :size="16" /> Next date
    </a>
    <a href="{{ route('cases.show', $case) }}" class="inline-flex min-h-11 flex-1 items-center justify-center rounded-xl bg-[#16776e] px-4 text-sm font-semibold text-white hover:bg-[#125f58]">Open case</a>
</div>
