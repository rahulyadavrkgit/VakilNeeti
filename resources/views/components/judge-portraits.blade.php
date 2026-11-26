@props(['names', 'district' => null, 'fallback' => false])

@php
    $photos = collect(preg_split('/\s*[,;·\r\n]+\s*|\s+(?:&|and)\s+/iu', $names, -1, PREG_SPLIT_NO_EMPTY))
        ->mapWithKeys(fn (string $name) => [(string) \App\Models\CourtBench::photoFor(trim($name), $district) => \App\Models\CourtBench::initials($name)])
        ->except(['']);
@endphp

{{-- A photo that does not load gives way to the judge's initials (resources/js/ui-actions.js). --}}
@forelse($photos as $photo => $initials)
    <img src="{{ $photo }}" alt="" loading="lazy" width="28" height="28" data-initials="{{ $initials }}" class="size-7 shrink-0 rounded-full border border-slate-200 object-cover">
@empty
    @if($fallback)
        <x-icon name="gavel" :size="12" class="shrink-0" />
    @endif
@endforelse
