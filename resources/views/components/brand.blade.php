@props([
    'tone' => 'light',
    'size' => 36,
    'wordmark' => true,
])

{{-- VakilNiti logo: the scales mark plus the wordmark. "dark" tone is for navy backgrounds. --}}
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5']) }}>
    <img src="{{ asset('images/logo-mark.svg') }}" alt="" width="{{ $size }}" height="{{ $size }}" class="shrink-0" style="width: {{ $size }}px; height: {{ $size }}px">
    @if($wordmark)
        <span data-brand-word class="font-serif font-bold leading-none tracking-tight {{ $tone === 'dark' ? 'text-white' : 'text-[#142235]' }}" style="font-size: {{ round($size * 0.62) }}px">Vakil<span class="{{ $tone === 'dark' ? 'text-[#e6b84a]' : 'text-[#a87a0c]' }}">Niti</span></span>
    @else
        <span class="sr-only">VakilNiti</span>
    @endif
</span>
