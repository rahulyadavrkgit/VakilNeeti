{{-- Light or dark before the page paints (used by every full page, and the live board shown inside the board viewer). --}}
<script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
        // Light or dark before the page paints, so it never flashes the wrong colours.
        (function () {
            var root = document.documentElement;
            var choice = root.dataset.theme || 'system';
            var media = window.matchMedia('(prefers-color-scheme: dark)');

            try {
                if (!root.hasAttribute('data-theme-saved')) {
                    choice = localStorage.getItem('vn-theme') || choice;
                }
            } catch (error) {}

            var apply = function () {
                var dark = choice === 'dark' || (choice === 'system' && media.matches);
                root.classList.toggle('dark', dark);
                root.dataset.theme = choice;
                document.querySelectorAll('meta[name="theme-color"]').forEach(function (meta) {
                    meta.content = dark ? '#0d1524' : '#142235';
                });
            };

            apply();
            media.addEventListener('change', apply);
            window.vnSetTheme = function (next) {
                choice = next;

                try {
                    localStorage.setItem('vn-theme', next);
                } catch (error) {}

                apply();
            };
        })();
</script>
