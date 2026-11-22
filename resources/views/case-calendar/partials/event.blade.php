@php
    $eventClass = match ($event['type']) {
        'hearing' => 'border-teal-600 bg-teal-50 text-teal-950',
        'next_hearing' => 'border-indigo-500 bg-indigo-50 text-indigo-950',
        'appointment' => 'border-sky-500 bg-sky-50 text-sky-950',
        default => 'border-amber-500 bg-amber-50 text-amber-950',
    };
@endphp

@if($compact ?? false)
    <a href="{{ $event['url'] }}" title="{{ $event['label'] }}: {{ $event['title'] }}"
       class="block truncate rounded border-l-4 px-1.5 py-0.5 text-xs font-medium {{ $eventClass }} {{ $event['is_done'] && $event['type'] === 'task' ? 'line-through opacity-70' : '' }}">
        @if($event['time'])<span class="font-semibold">{{ $event['time'] }}</span> @endif{{ $event['title'] }}
    </a>
@else
    <a href="{{ $event['url'] }}" class="block rounded-lg border-l-4 px-3 py-2 text-sm transition hover:shadow-sm {{ $eventClass }}">
        <span class="flex flex-wrap items-center justify-between gap-2">
            <span class="text-xs font-bold uppercase tracking-wide opacity-80">{{ $event['label'] }}@if($event['time']) · {{ $event['time'] }}@endif</span>
            @if($event['type'] === 'task' && $event['is_done'])
                <span class="rounded-full bg-white/70 px-2 py-0.5 text-xs font-semibold">Done</span>
            @endif
        </span>
        <span class="mt-0.5 block font-semibold {{ $event['is_done'] && $event['type'] === 'task' ? 'line-through' : '' }}">{{ $event['title'] }}</span>
        @if($event['case_number'] || $event['court'])
            <span class="block text-xs opacity-80">{{ collect([$event['case_number'], $event['court']])->filter()->implode(' · ') }}</span>
        @endif
        @if($event['client_name'])
            <span class="block text-xs opacity-80">Client: {{ $event['client_name'] }}</span>
        @endif
        @if($event['details'])
            <span class="mt-1 block text-xs">{{ \Illuminate\Support\Str::limit($event['details'], 160) }}</span>
        @endif
    </a>
@endif
