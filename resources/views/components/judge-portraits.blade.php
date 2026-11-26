@props(['names', 'district' => null, 'fallback' => false])

@php
    $photos = collect(preg_split('/\s*[,;·\r\n]+\s*|\s+(?:&|and)\s+/iu', $names, -1, PREG_SPLIT_NO_EMPTY))
        ->map(fn (string $name) => \App\Models\CourtBench::photoFor(trim($name), $district))
        ->filter()
        ->unique();
@endphp

@forelse($photos as $photo)
    <img src="{{ $photo }}" alt="" loading="lazy" width="28" height="28" class="size-7 shrink-0 rounded-full border border-slate-200 object-cover">
@empty
    @if($fallback)
        <x-icon name="gavel" :size="12" class="shrink-0" />
    @endif
@endforelse
