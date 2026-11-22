<nav aria-label="Breadcrumb" class="text-sm text-slate-500">
    <ol class="flex flex-wrap items-center gap-1.5">
        <li><a href="{{ route('dashboard') }}" class="hover:text-[#16776e] hover:underline">Home</a></li>
        <li aria-hidden="true">/</li>
        <li><a href="{{ route('courts.index') }}" class="hover:text-[#16776e] hover:underline">Courts</a></li>
        <li aria-hidden="true">/</li>
        <li aria-current="page" class="font-medium text-slate-700">{{ $current }}</li>
    </ol>
</nav>

@push('structured-data')
    <script type="application/ld+json" nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => route('dashboard')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Courts', 'item' => route('courts.index')],
                ['@type' => 'ListItem', 'position' => 3, 'name' => $current, 'item' => url()->current()],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}
    </script>
@endpush
