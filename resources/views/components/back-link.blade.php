@props([
    'fallback' => route('dashboard'),
])

{{-- Goes back in browser history when the visitor came from this site, otherwise to the fallback URL. --}}
<a href="{{ $fallback }}" data-vn-back
   {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-semibold text-slate-700 hover:bg-slate-50']) }}>
    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5m7 7-7-7 7-7"/></svg>
    <span class="sr-only sm:not-sr-only">Back</span>
</a>

@once
    <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
        document.addEventListener('click', function (event) {
            const link = event.target.closest('[data-vn-back]');

            if (!link || event.metaKey || event.ctrlKey || event.shiftKey) {
                return;
            }

            let cameFromThisSite = false;

            try {
                cameFromThisSite = document.referrer !== '' && new URL(document.referrer).origin === window.location.origin;
            } catch (error) {
                cameFromThisSite = false;
            }

            if (cameFromThisSite && window.history.length > 1) {
                event.preventDefault();
                window.history.back();
            }
        });
    </script>
@endonce
