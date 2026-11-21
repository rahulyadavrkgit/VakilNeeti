<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <meta name="robots" content="noindex, nofollow">
    <title>404 - Page Not Found | VakilNeeti</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system,
                BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: #0f172a;
            background:
                radial-gradient(circle at 15% 15%, rgba(59, 130, 246, .09), transparent 32%),
                radial-gradient(circle at 85% 85%, rgba(245, 158, 11, .10), transparent 30%),
                #f8fafc;
            display: flex;
            flex-direction: column;
        }

        .topbar {
            width: 100%;
            padding: 24px 7%;
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 11px;
            text-decoration: none;
            color: #0f172a;
        }

        .brand-mark {
            width: 42px;
            height: 42px;
            display: grid;
            place-items: center;
            border-radius: 12px;
            background: #0f172a;
            color: #fbbf24;
            font-size: 22px;
            font-weight: 800;
        }

        .brand-name {
            font-size: 20px;
            font-weight: 800;
            letter-spacing: -.6px;
        }

        .brand-name span {
            color: #2563eb;
        }

        main {
            flex: 1;
            display: grid;
            place-items: center;
            padding: 30px 20px 70px;
        }

        .error-card {
            width: 100%;
            max-width: 680px;
            padding: 55px 45px;
            text-align: center;
            background: rgba(255, 255, 255, .94);
            border: 1px solid #e2e8f0;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(15, 23, 42, .07);
        }

        .error-illustration {
            width: 116px;
            height: 116px;
            margin: 0 auto 25px;
            display: grid;
            place-items: center;
            border-radius: 32px;
            background: #eff6ff;
            color: #2563eb;
            font-size: 52px;
            font-weight: 800;
            transform: rotate(-5deg);
        }

        .error-code {
            margin: 0;
            color: #2563eb;
            font-size: clamp(76px, 15vw, 112px);
            font-weight: 900;
            line-height: 1;
            letter-spacing: -7px;
        }

        h1 {
            margin: 18px 0 10px;
            font-size: clamp(23px, 4vw, 30px);
            font-weight: 800;
            letter-spacing: -.8px;
        }

        .description {
            max-width: 440px;
            margin: 0 auto;
            color: #64748b;
            font-size: 15px;
            line-height: 1.8;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 12px;
            margin-top: 30px;
        }

        .button {
            min-height: 46px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0 22px;
            border: 1px solid transparent;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            transition: .2s ease;
        }

        .button-primary {
            background: #0f172a;
            color: white;
        }

        .button-primary:hover {
            background: #1e40af;
            transform: translateY(-2px);
        }

        .button-secondary {
            background: white;
            border-color: #cbd5e1;
            color: #334155;
        }

        .button-secondary:hover {
            background: #f1f5f9;
            border-color: #94a3b8;
        }

        .footer {
            padding: 20px;
            text-align: center;
            color: #94a3b8;
            font-size: 12px;
        }

        @media (max-width: 560px) {
            .topbar {
                padding: 18px 20px;
            }

            .error-card {
                padding: 42px 22px;
                border-radius: 18px;
            }

            .error-illustration {
                width: 90px;
                height: 90px;
                font-size: 42px;
            }

            .actions {
                flex-direction: column;
            }

            .button {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <header class="topbar">
        <a href="{{ route('dashboard') }}" class="brand" aria-label="VakilNeeti home">
            <img src="{{ asset('images/vakilneeti-logo.png') }}"
                 alt="VakilNeeti"
                 style="height:52px;width:auto;max-width:220px;object-fit:contain;">
        </a>
    </header>

    <main>
        <section class="error-card" aria-labelledby="error-heading">
            <div class="error-illustration" aria-hidden="true">?</div>

            <p class="error-code">404</p>

            <h1 id="error-heading">Page not found</h1>

            <p class="description">
                The page you are looking for may have been moved,
                removed, or is temporarily unavailable.
                Let's get you back to your legal workspace.
            </p>

            <div class="actions">
                <a href="{{ route('dashboard') }}" class="button button-primary">
                    Back to Dashboard
                </a>

                <button type="button" class="button button-secondary" onclick="goBack()">
                    Go Back
                </button>
            </div>
        </section>
    </main>

    <footer class="footer">
        &copy; {{ date('Y') }} VakilNeeti. All rights reserved.
    </footer>

    <script>
        function goBack() {
            if (window.history.length > 1) {
                window.history.back();
            } else {
                window.location.href = @json(route('dashboard'));
            }
        }
    </script>
</body>
</html>

