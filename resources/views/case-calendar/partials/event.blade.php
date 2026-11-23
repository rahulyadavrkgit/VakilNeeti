@php
    $eventClass = match ($event['type']) {
        'hearing' => 'border-teal-600 bg-teal-50 text-teal-950',
        'next_hearing' => 'border-indigo-500 bg-indigo-50 text-indigo-950',
        'appointment' => 'border-sky-500 bg-sky-50 text-sky-950',
        default => 'border-amber-500 bg-amber-50 text-amber-950',
    };
    $struck = $event['is_done'] && $event['type'] === 'task';
@endphp

@if($compact ?? false)
    {{-- Pointing at the event shows the hover card (resources/js/event-peek.js); tapping opens it. --}}
    <span data-event-peek class="block">
        <a href="{{ $event['url'] }}"
           class="block truncate rounded border-l-4 px-1.5 py-0.5 text-xs font-medium {{ $eventClass }} {{ $struck ? 'line-through opacity-70' : '' }}">
            @if($event['time'])<span class="font-semibold">{{ $event['time'] }}</span> @endif{{ $event['title'] }}
        </a>
        <template data-event-card>@include('case-calendar.partials.event-card', ['event' => $event])</template>
    </span>
@else
    <div class="rounded-lg border-l-4 px-3 py-2 text-sm {{ $eventClass }}">
        <span class="flex flex-wrap items-center justify-between gap-2">
            <span class="text-xs font-bold uppercase tracking-wide opacity-80">{{ $event['label'] }}@if($event['time']) · {{ $event['time'] }}@endif</span>
            @if($struck)
                <span class="rounded-full bg-white/70 px-2 py-0.5 text-xs font-semibold">Done</span>
            @endif
        </span>
        <span class="mt-0.5 flex flex-wrap items-center gap-1.5">
            <a href="{{ $event['url'] }}" class="font-semibold hover:underline {{ $struck ? 'line-through' : '' }}">{{ $event['title'] }}</a>
            <x-case-side :side="$event['appearing_for'] ?? null" :parties="$event['appearing_for_parties'] ?? null" />
        </span>
        @if($event['case_number'])
            <span class="block text-xs opacity-80">{{ $event['case_number'] }}</span>
        @endif
        <x-court-info class="mt-1" :court="$event['court']" :room="$event['court_room'] ?? null" :judge="$event['judge_name'] ?? null" :vc-link="$event['vc_link'] ?? null" />
        @if($event['client_name'])
            <span class="block text-xs opacity-80">Client: {{ $event['client_name'] }}</span>
        @endif
        @if($event['details'])
            <span class="mt-1 block text-xs">{{ \Illuminate\Support\Str::limit($event['details'], 160) }}</span>
        @endif
    </div>
@endif
