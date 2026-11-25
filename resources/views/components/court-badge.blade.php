@props(['level' => null, 'state' => null, 'district' => null, 'href' => null])
{{-- Which court a case is in, the same everywhere: navy and gold = Supreme Court, teal = High Court, blue = district, violet = tribunal. --}}
@php
    $courtLabel = \App\Services\CourtIdentity::label($level, $state, $district);
    $courtTone = match ($level) {
        'supreme' => 'bg-[#142235] text-[#e6b84a]',
        'high' => 'bg-[#16776e] text-white',
        'tribunal' => 'bg-violet-50 text-violet-800 ring-1 ring-inset ring-violet-200',
        default => 'bg-sky-50 text-sky-800 ring-1 ring-inset ring-sky-200',
    };
@endphp
@if($courtLabel)
    <{{ $href ? 'a' : 'span' }} @if($href) href="{{ $href }}" @endif
        {{ $attributes->merge(['class' => "inline-flex max-w-full items-center gap-1 truncate rounded-full px-2 py-0.5 text-xs font-bold {$courtTone}".($href ? ' hover:opacity-90' : '')]) }}
        title="{{ $courtLabel }}{{ $district ? ' ('.\App\Services\CourtIdentity::stateName($state).')' : '' }}">{{ $courtLabel }}</{{ $href ? 'a' : 'span' }}>
@endif
