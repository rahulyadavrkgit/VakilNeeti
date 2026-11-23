@props(['title', 'subtitle' => null])

{{--
    The top of a page, the same everywhere: title, a short line about it, and the page's main buttons on the right
    (they wrap below the title on phones). On phones the title is already in the app header, so it is hidden there.
--}}
<div {{ $attributes->merge(['class' => 'flex flex-wrap items-end justify-between gap-3']) }}>
    <div class="min-w-0">
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">{{ $title }}</h1>
        @if($subtitle)
            <p class="mt-0.5 max-w-3xl text-sm text-slate-500">{{ $subtitle }}</p>
        @endif
    </div>
    @if($slot->isNotEmpty())
        <div class="flex flex-wrap items-center gap-2">{{ $slot }}</div>
    @endif
</div>
