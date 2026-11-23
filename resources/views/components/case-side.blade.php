@props(['side' => null, 'parties' => null, 'size' => 'md', 'editUrl' => null])

{{--
    "For Petitioner" pill: teal for the side that brought the matter, gold for the answering side.
    Without a side, and with `edit-url`, a dashed "Set our side" link invites setting it.
--}}
@php
    $sideLabel = \App\Models\LegalCase::sideLabel($side, $parties);
    $sizing = match ($size) {
        'lg' => 'gap-1.5 px-3 py-1 text-sm',
        'sm' => 'gap-1 px-2 py-0.5 text-xs',
        default => 'gap-1 px-2.5 py-0.5 text-xs',
    };
    $iconSize = $size === 'lg' ? 16 : 13;
@endphp
@if($sideLabel)
    <span {{ $attributes->merge(['class' => 'inline-flex shrink-0 items-center whitespace-nowrap rounded-full font-bold ring-1 ring-inset '.$sizing.' '
        .(\App\Models\LegalCase::isMovingSide($side) ? 'bg-teal-50 text-[#12645d] ring-teal-300' : 'bg-amber-50 text-amber-800 ring-amber-300')]) }}
          title="We appear for the {{ strtolower($sideLabel) }}">
        <x-icon name="scale" :size="$iconSize" /><span class="font-medium opacity-75">For</span> {{ $sideLabel }}
    </span>
@elseif($editUrl)
    <a href="{{ $editUrl }}" {{ $attributes->merge(['class' => 'inline-flex shrink-0 items-center whitespace-nowrap rounded-full border border-dashed border-slate-300 font-semibold text-slate-500 hover:border-[#16776e] hover:text-[#16776e] '.$sizing]) }}
       title="Record which side you appear for">
        <x-icon name="scale" :size="$iconSize" /> Set our side
    </a>
@endif
