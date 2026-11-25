@props(['level' => null, 'state' => null, 'district' => null, 'href' => null])
{{-- Which court a case is in, the same everywhere: navy = Supreme Court, teal = High Court, grey = district, amber = tribunal. --}}
@php
    $courtLabel = \App\Services\CourtIdentity::label($level, $state, $district);
    $courtTone = match ($level) {
        'supreme' => 'bg-[#142235] text-white',
        'high' => 'bg-[#16776e] text-white',
        'tribunal' => 'bg-amber-100 text-amber-900',
        default => 'bg-slate-100 text-slate-700 ring-1 ring-inset ring-slate-200',
    };
@endphp
@if($courtLabel)
    <{{ $href ? 'a' : 'span' }} @if($href) href="{{ $href }}" @endif
        {{ $attributes->merge(['class' => "inline-flex max-w-full items-center gap-1 truncate rounded-full px-2 py-0.5 text-xs font-bold {$courtTone}".($href ? ' hover:opacity-90' : '')]) }}
        title="{{ $courtLabel }}{{ $district ? ' ('.\App\Services\CourtIdentity::stateName($state).')' : '' }}">{{ $courtLabel }}</{{ $href ? 'a' : 'span' }}>
@endif
