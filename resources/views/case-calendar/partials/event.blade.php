@php
    $eventClass = match ($event['type']) {
        'hearing', 'listed' => 'border-teal-600 bg-teal-50 text-teal-950',
        'next_hearing' => 'border-indigo-500 bg-indigo-50 text-indigo-950',
        'appointment' => 'border-sky-500 bg-sky-50 text-sky-950',
        default => 'border-amber-500 bg-amber-50 text-amber-950',
    };
    $struck = $event['is_done'] && $event['type'] === 'task';
@endphp

@if(($variant ?? null) === 'week')
    {{-- Week columns: time, two lines of title, our side and the room; pointing at it shows everything. --}}
    @php
        $weekRoom = trim((string) ($event['court_room'] ?? ''));
        $weekSide = \App\Models\LegalCase::sideLabel($event['appearing_for'] ?? null, $event['appearing_for_parties'] ?? null);
    @endphp
    <span data-event-peek class="block">
        <a href="{{ $event['url'] }}" class="block rounded-lg border-l-4 px-2 py-1.5 text-xs transition hover:shadow-sm {{ $eventClass }} {{ $struck ? 'line-through opacity-70' : '' }}">
            <span class="flex items-center justify-between gap-1 font-bold">
                <span>{{ $event['time'] ?: $event['label'] }}</span>
                @if($weekRoom !== '')<span class="truncate rounded bg-white/70 px-1 text-[11px] font-semibold">{{ preg_match('/^(court|room)\b/i', $weekRoom) ? $weekRoom : 'R '.$weekRoom }}</span>@endif
            </span>
            <span class="mt-0.5 line-clamp-2 font-semibold leading-snug">{{ $event['title'] }}</span>
            @if($weekSide)
                <span class="mt-1 inline-block rounded-full px-1.5 text-[11px] font-bold {{ \App\Models\LegalCase::isMovingSide($event['appearing_for'] ?? null) ? 'bg-teal-100 text-teal-900' : 'bg-amber-100 text-amber-900' }}">For {{ $weekSide }}</span>
            @endif
        </a>
        <template data-event-card>@include('case-calendar.partials.event-card', ['event' => $event])</template>
    </span>
@elseif($compact ?? false)
    {{-- Pointing at the event shows the hover card (resources/js/event-peek.js); tapping opens it. --}}
    <span data-event-peek class="block">
        <a href="{{ $event['url'] }}"
           class="block truncate rounded border-l-4 px-1.5 py-0.5 text-xs font-medium {{ $eventClass }} {{ $struck ? 'line-through opacity-70' : '' }}">
            @if($event['time'])<span class="font-semibold">{{ $event['time'] }}</span> @endif{{ $event['title'] }}
        </a>
        <template data-event-card>@include('case-calendar.partials.event-card', ['event' => $event])</template>
    </span>
@elseif(($event['group'] ?? null) === 'hearing')
    {{-- A hearing looks the same everywhere: the Home card (room, item, Supple, Join VC, Share). --}}
    <x-hearing-card :event="$event" />
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
        <x-court-info class="mt-1" :live="false" :court="$event['court']" :complex="$event['court_complex'] ?? null" :room="$event['cause_list']['court'] ?? $event['court_room'] ?? null" :judge="$event['cause_list']['judges'] ?? $event['judge_name'] ?? null" :vc-link="$event['cause_list']['vc_link'] ?? $event['vc_link'] ?? null" :share="['title' => $event['title'], 'case_number' => $event['case_number'], 'side' => $event['appearing_for'] ?? null, 'parties' => $event['appearing_for_parties'] ?? null, 'date' => $event['date'], 'time' => $event['time'], 'details' => $event['details']]" />
        @if(($event['group'] ?? null) === 'hearing')
            <x-hearing-strip :show-room="false" :date="$event['date']" :court="$event['court']" :room="$event['court_room'] ?? null" :item="$event['item_number'] ?? null" :list-total="$event['list_total'] ?? null" :cause-list="$event['cause_list'] ?? null" :case-number="$event['case_number']" :district="($event['court_level'] ?? null) === 'district'" :high-court="($event['court_level'] ?? null) === 'high'" :order-url="!empty($event['order']) ? route('cases.documents.download', [$event['case_id'], $event['order'], 'preview' => 1]) : null" :order-date="($event['order'] ?? null)?->created_at?->format('d M Y')" />
        @endif
        @if($event['client_name'])
            <span class="block text-xs opacity-80">Client: {{ $event['client_name'] }}</span>
        @endif
        @if($event['details'])
            <span class="mt-1 block text-xs">{{ \Illuminate\Support\Str::limit($event['details'], 160) }}</span>
        @endif
    </div>
@endif
