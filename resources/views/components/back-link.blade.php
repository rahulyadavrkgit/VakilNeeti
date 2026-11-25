@props([
    'fallback' => route('dashboard'),
])

{{--
    Back to the page the visitor came from on this site, otherwise to the fallback URL.
    After saving a form the page reloads itself, so "back" then means the page before it, not the unsaved form.
--}}
<a href="{{ $fallback }}" data-vn-back
   {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-semibold text-slate-700 hover:bg-slate-50']) }}>
    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5m7 7-7-7 7-7"/></svg>
    <span class="sr-only sm:not-sr-only">Back</span>
</a>

@once
    <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
        (function () {
            const key = 'vn-back:' + window.location.pathname;
            let referrer = null;

            try {
                referrer = document.referrer !== '' ? new URL(document.referrer) : null;
            } catch (error) {
                referrer = null;
            }

            const fromThisSite = referrer !== null && referrer.origin === window.location.origin;
            const fromThisPage = fromThisSite && referrer.pathname === window.location.pathname;

            try {
                if (fromThisSite && !fromThisPage) {
                    sessionStorage.setItem(key, referrer.href);
                } else if (!fromThisSite) {
                    sessionStorage.removeItem(key);
                }
            } catch (error) {
                // Private browsing can block storage; Back then uses history or the fallback.
            }

            document.addEventListener('click', function (event) {
                const link = event.target.closest('[data-vn-back]');

                if (!link || event.metaKey || event.ctrlKey || event.shiftKey) {
                    return;
                }

                if (fromThisPage) {
                    let previous = null;

                    try {
                        previous = sessionStorage.getItem(key);
                    } catch (error) {
                        previous = null;
                    }

                    if (previous) {
                        event.preventDefault();
                        window.location.href = previous;
                    }

                    return;
                }

                if (fromThisSite && window.history.length > 1) {
                    event.preventDefault();
                    window.history.back();
                }
            });
        })();
    </script>
@endonce
