@props(['heading', 'events' => null, 'text' => null, 'group' => null, 'tone' => 'light'])

{{--
    Share every hearing of one day at once, next to the day's date: "Hearings · Today, Thu 26 Nov".
    Give `events` (CalendarEventService hearings) for a ready message, or leave both out and mark the cards
    with data-day-item (My cases builds the message from the cards on the page, see resources/js/ui-actions.js).
--}}
@php
    $text ??= $events !== null ? \App\Services\HearingShareText::forEvents($heading, $events) : '';
@endphp
<a href="{{ $text !== '' ? 'https://wa.me/?text='.rawurlencode($text) : '#' }}" target="_blank" rel="noopener noreferrer" data-direct-share
   data-share-text="{{ $text }}" data-share-title="{{ $heading }}" @if($text === '' && $group !== null) data-share-day="{{ $group }}" @endif
   title="Share all hearings of this day" aria-label="Share all hearings: {{ $heading }}"
   {{ $attributes->class(['inline-flex h-8 shrink-0 items-center gap-1 rounded-full px-2.5 text-xs font-semibold transition',
        'border border-white/20 text-white/85 hover:bg-white/10' => $tone === 'dark',
        'border border-slate-200 bg-white text-slate-600 hover:border-[#16776e] hover:text-[#16776e]' => $tone !== 'dark']) }}>
    <x-icon name="share" :size="14" /> <span>Share day</span>
</a>
