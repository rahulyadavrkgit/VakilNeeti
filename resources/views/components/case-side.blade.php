@props(['side' => null, 'parties' => null, 'size' => 'sm'])

{{-- "For Petitioner" pill: teal for the side that brought the matter, gold for the answering side. --}}
@php
    $sideLabel = \App\Models\LegalCase::sideLabel($side, $parties);
@endphp
@if($sideLabel)
    <span {{ $attributes->merge(['class' => 'inline-flex shrink-0 items-center gap-1 whitespace-nowrap rounded-full font-semibold ring-1 ring-inset '
        .($size === 'lg' ? 'px-3 py-1 text-sm ' : 'px-2 py-0.5 text-[11px] ')
        .(\App\Models\LegalCase::isMovingSide($side) ? 'bg-teal-50 text-[#12645d] ring-teal-200' : 'bg-amber-50 text-amber-800 ring-amber-200')]) }}
          title="We appear for the {{ strtolower($sideLabel) }}">
        <span class="font-medium opacity-75">For</span> {{ $sideLabel }}
    </span>
@endif
