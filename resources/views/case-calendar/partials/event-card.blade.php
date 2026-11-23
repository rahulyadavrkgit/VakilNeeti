{{-- Key details of one calendar event: used in the hover card and the day view. --}}
@php
    $eventDay = \Illuminate\Support\Carbon::parse($event['date']);
@endphp
<div class="space-y-1.5 text-sm text-slate-700">
    <p class="text-[11px] font-bold uppercase tracking-wide text-slate-500">
        {{ $event['label'] }} · {{ $eventDay->isToday() ? 'Today' : ($eventDay->isTomorrow() ? 'Tomorrow' : $eventDay->format('D, d M Y')) }}@if($event['time']) · {{ $event['time'] }}@endif
    </p>
    <p class="font-semibold leading-snug text-slate-900">
        {{ $event['title'] }}
        <x-case-side :side="$event['appearing_for'] ?? null" :parties="$event['appearing_for_parties'] ?? null" class="ml-1 align-middle" />
    </p>
    @if($event['case_number'])
        <p class="text-xs text-slate-500">{{ $event['case_number'] }}</p>
    @endif
    <x-court-info :court="$event['court']" :complex="$event['court_complex'] ?? null" :room="$event['court_room'] ?? null" :judge="$event['judge_name'] ?? null" :vc-link="$event['vc_link'] ?? null" />
    @if($event['client_name'])
        <p class="text-xs text-slate-600">Client: {{ $event['client_name'] }}</p>
    @endif
    @if($event['details'])
        <p class="text-xs leading-relaxed text-slate-600">{{ \Illuminate\Support\Str::limit($event['details'], 220) }}</p>
    @endif
    @if($withOpenLink ?? true)
        <a href="{{ $event['url'] }}" class="inline-block pt-0.5 text-xs font-semibold text-[#16776e] hover:underline">{{ $event['case_id'] ? 'Open case' : 'Open' }} &rarr;</a>
    @endif
</div>
