<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <meta name="robots" content="noindex, nofollow">
    <title>Page not found | VakilNiti</title>

    {{-- Standalone on purpose: a missing URL never reaches the session middleware the main layout needs. --}}
    <style>
        :root {
            --vn-ink: #142235;
            --vn-accent: #238b80;
            --vn-accent-dark: #16776e;
            --vn-accent-soft: #e8f4f2;
            --vn-canvas: #f4f6f8;
            --vn-line: #e2e8ef;
            --vn-muted: #64748b;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            font-family: 'Aptos', 'Segoe UI', 'Helvetica Neue', Arial, ui-sans-serif, sans-serif;
            color: var(--vn-ink);
            background: var(--vn-canvas);
        }

        header {
            padding: 20px 24px;
        }

        header a {
            display: inline-flex;
            border-radius: 10px;
            background: #fff;
            padding: 6px 10px;
            box-shadow: 0 1px 2px rgba(20, 34, 53, .06);
        }

        header img {
            height: 36px;
            width: auto;
            max-width: 170px;
            object-fit: contain;
        }

        main {
            flex: 1;
            display: grid;
            place-items: center;
            padding: 24px 16px 64px;
        }

        .card {
            width: 100%;
            max-width: 520px;
            padding: 44px 32px;
            text-align: center;
            background: #fff;
            border: 1px solid var(--vn-line);
            border-radius: 24px;
            box-shadow: 0 20px 50px rgba(20, 34, 53, .07);
        }

        .code {
            margin: 0;
            color: var(--vn-accent);
            font-size: clamp(64px, 14vw, 96px);
            font-weight: 800;
            line-height: 1;
            letter-spacing: -4px;
        }

        h1 {
            margin: 16px 0 8px;
            font-size: clamp(22px, 4vw, 28px);
            font-weight: 600;
            letter-spacing: -.5px;
        }

        p.lead {
            margin: 0;
            color: var(--vn-muted);
            font-size: 15px;
            line-height: 1.6;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 12px;
            margin-top: 28px;
        }

        .button {
            min-height: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 0 22px;
            border: 1px solid transparent;
            border-radius: 12px;
            font: inherit;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: background .15s ease, border-color .15s ease;
        }

        .button-primary { background: var(--vn-accent); color: #fff; }
        .button-primary:hover { background: var(--vn-accent-dark); }
        .button-secondary { background: #fff; border-color: #cbd5e1; color: #334155; }
        .button-secondary:hover { background: #f1f5f9; }
        .button:focus-visible { outline: 3px solid #55b9ad; outline-offset: 2px; }

        nav {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 8px;
            margin-top: 28px;
            padding-top: 22px;
            border-top: 1px solid var(--vn-line);
        }

        nav a {
            padding: 6px 12px;
            border-radius: 999px;
            background: var(--vn-accent-soft);
            color: var(--vn-accent-dark);
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
        }

        nav a:hover { background: #d9efeb; }

        @media (max-width: 480px) {
            .card { padding: 36px 20px; border-radius: 18px; }
            .actions { flex-direction: column; }
            .button { width: 100%; }
        }
    </style>
</head>

<body>
    <header>
        <a href="{{ route('dashboard') }}" aria-label="VakilNiti home">
            <img src="{{ asset('images/logo.svg') }}" alt="VakilNiti">
        </a>
    </header>

    <main>
        <section class="card" aria-labelledby="error-heading">
            <p class="code" aria-hidden="true">404</p>
            <h1 id="error-heading">Page not found</h1>
            <p class="lead">This page may have moved or no longer exists.</p>

            <div class="actions">
                <a href="{{ route('dashboard') }}" class="button button-primary">Go to Home</a>
                <a href="{{ route('dashboard') }}" class="button button-secondary" id="vn-404-back">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5m7 7-7-7 7-7"/></svg>
                    Back
                </a>
            </div>

            <nav aria-label="Popular pages">
                <a href="{{ route('cases.index') }}">My cases</a>
                <a href="{{ route('cases.calendar') }}">Calendar</a>
                <a href="{{ route('clients.index') }}">Clients</a>
                <a href="{{ route('court-directory.index') }}">Courts</a>
                <a href="{{ route('profile.index') }}">Profile</a>
            </nav>
        </section>
    </main>

    <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
        // Go back only when the visitor came from this site; otherwise the link goes Home.
        document.getElementById('vn-404-back')?.addEventListener('click', function (event) {
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
</body>
</html>
